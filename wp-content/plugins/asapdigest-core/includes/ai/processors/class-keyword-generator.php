<?php
/**
 * Keyword Generator Class
 *
 * Extracts keywords, tags, and key phrases from content using AI services.
 *
 * @package ASAPDigest_Core
 * @subpackage AI\Processors
 * @since 3.1.0
 * @file-marker ASAP_Digest_KeywordGenerator
 * @created 05/07/25 | 05:00 PM PDT
 */

namespace ASAPDigest\AI\Processors;

use ASAPDigest\AI\AIServiceManager;
use ASAPDigest\Core\ErrorLogger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to generate and extract keywords from content
 */
class KeywordGenerator {
    /**
     * AI Service Manager instance
     *
     * @var AIServiceManager
     */
    private $ai_service;
    
    /**
     * Keyword extraction model to use
     *
     * @var string
     */
    private $model = 'gpt-3.5-turbo';
    
    /**
     * Cache of keyword extraction results
     *
     * @var array
     */
    private $keyword_cache = [];
    
    /**
     * Common stopwords to filter out
     *
     * @var array
     */
    private $stopwords = [
        'a', 'an', 'the', 'and', 'but', 'or', 'for', 'nor', 'on', 'at', 'to', 'from', 'by',
        'about', 'as', 'in', 'of', 'with', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'shall', 'should',
        'can', 'could', 'may', 'might', 'must', 'this', 'that', 'these', 'those', 'i',
        'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_service = new AIServiceManager();
        
        // Set model from options if available
        $model_option = get_option('asap_ai_keyword_model', '');
        if (!empty($model_option)) {
            $this->model = $model_option;
        }
    }
    
    /**
     * Generate keywords from content
     *
     * @param string $content Content to analyze
     * @param array $options Keyword generation options
     * @return array Generated keywords with scores
     */
    public function generate($content, $options = []) {
        // Generate a cache key for this content
        $cache_key = md5($content) . '_' . md5(serialize($options));
        
        // Return cached results if available
        if (isset($this->keyword_cache[$cache_key])) {
            return $this->keyword_cache[$cache_key];
        }
        
        try {
            // Set up generation options
            $generation_options = [
                'model' => isset($options['model']) ? $options['model'] : $this->model,
                'limit' => isset($options['limit']) ? (int) $options['limit'] : 15,
                'min_score' => isset($options['min_score']) ? (float) $options['min_score'] : 0.6,
                'include_phrases' => isset($options['include_phrases']) ? (bool) $options['include_phrases'] : true,
                'filter_stopwords' => isset($options['filter_stopwords']) ? (bool) $options['filter_stopwords'] : true,
                'categorize' => isset($options['categorize']) ? (bool) $options['categorize'] : false
            ];
            
            // Generate keywords using AI service
            $raw_keywords = $this->ai_service->generate_keywords($content, $generation_options);
            
            // Process and enhance the generated keywords
            $processed_keywords = $this->process_keywords($raw_keywords, $content, $generation_options);
            
            // Add additional keyword statistics if needed
            if ($generation_options['categorize']) {
                $processed_keywords['categories'] = $this->categorize_keywords($processed_keywords['keywords'], $content);
            }
            
            // Cache the results
            $this->keyword_cache[$cache_key] = $processed_keywords;
            
            return $processed_keywords;
        } catch (\Exception $e) {
            ErrorLogger::log('keyword_generation', 'generation_error', $e->getMessage(), [
                'content_length' => strlen($content),
                'options' => $options
            ], 'error');
            
            // Fall back to simple TF-IDF based keyword extraction
            return $this->fallback_keyword_extraction($content, $options);
        }
    }
    
    /**
     * Process and enhance generated keywords
     *
     * @param array $raw_keywords Keywords from AI service
     * @param string $content Original content for context
     * @param array $options Generation options
     * @return array Processed and enhanced keywords
     */
    private function process_keywords($raw_keywords, $content, $options) {
        $normalized_keywords = [];
        
        // Normalize raw keywords to consistent format
        if (isset($raw_keywords[0]['keyword']) && isset($raw_keywords[0]['score'])) {
            $normalized_keywords = $raw_keywords;
        } elseif (isset($raw_keywords['keywords']) && is_array($raw_keywords['keywords'])) {
            $normalized_keywords = $raw_keywords['keywords'];
        } else {
            // Try to extract from different formats
            foreach ($raw_keywords as $key => $value) {
                if (is_string($key) && is_numeric($value)) {
                    $normalized_keywords[] = [
                        'keyword' => $key,
                        'score' => (float) $value
                    ];
                } elseif (is_string($value)) {
                    $normalized_keywords[] = [
                        'keyword' => $value,
                        'score' => 1.0 // Default score
                    ];
                } elseif (is_array($value) && isset($value['keyword']) && isset($value['score'])) {
                    $normalized_keywords[] = $value;
                }
            }
        }
        
        // Apply stopword filtering if requested
        if ($options['filter_stopwords']) {
            $normalized_keywords = array_filter($normalized_keywords, function($item) {
                $keyword = strtolower(trim($item['keyword']));
                // Only filter single words that are stopwords
                if (strpos($keyword, ' ') === false) {
                    return !in_array($keyword, $this->stopwords);
                }
                return true;
            });
        }
        
        // Filter by minimum score
        $filtered_keywords = array_filter($normalized_keywords, function($item) use ($options) {
            return $item['score'] >= $options['min_score'];
        });
        
        // Sort by score
        usort($filtered_keywords, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Apply limit
        $limited_keywords = array_slice($filtered_keywords, 0, $options['limit']);
        
        // Add context if available
        $keywords_with_context = [];
        foreach ($limited_keywords as $keyword) {
            $keyword_item = [
                'keyword' => $keyword['keyword'],
                'score' => $keyword['score']
            ];
            
            // Find keyword in content for context
            $pos = stripos($content, $keyword['keyword']);
            if ($pos !== false) {
                $start = max(0, $pos - 30);
                $length = min(strlen($content) - $start, strlen($keyword['keyword']) + 60);
                $context = substr($content, $start, $length);
                $keyword_item['context'] = trim($context);
                
                // Count occurrences
                $keyword_item['occurrences'] = substr_count(strtolower($content), strtolower($keyword['keyword']));
            } else {
                $keyword_item['occurrences'] = 0;
            }
            
            $keywords_with_context[] = $keyword_item;
        }
        
        // Separate single terms and phrases if requested
        $result = [
            'keywords' => $keywords_with_context,
            'keyword_count' => count($keywords_with_context)
        ];
        
        if ($options['include_phrases']) {
            $result['terms'] = array_filter($keywords_with_context, function($item) {
                return strpos($item['keyword'], ' ') === false;
            });
            
            $result['phrases'] = array_filter($keywords_with_context, function($item) {
                return strpos($item['keyword'], ' ') !== false;
            });
            
            $result['term_count'] = count($result['terms']);
            $result['phrase_count'] = count($result['phrases']);
        }
        
        return $result;
    }
    
    /**
     * Categorize keywords into semantic groups
     *
     * @param array $keywords Processed keywords
     * @param string $content Original content for context
     * @return array Keyword categories
     */
    private function categorize_keywords($keywords, $content) {
        try {
            // Extract just the keyword strings
            $keyword_strings = array_map(function($item) {
                return $item['keyword'];
            }, $keywords);
            
            // Use the classifier to group keywords
            $classifier = new Classifier();
            $categories = [
                'topic' => [],
                'entity' => [],
                'technical' => [],
                'action' => [],
                'descriptor' => []
            ];
            
            // Use simple heuristics to categorize keywords
            foreach ($keywords as $keyword) {
                $keyword_text = strtolower($keyword['keyword']);
                $score = $keyword['score'];
                
                // Check if it's a proper noun (entity)
                if (preg_match('/^[A-Z][a-z]+/', $keyword['keyword'])) {
                    $categories['entity'][] = [
                        'keyword' => $keyword['keyword'],
                        'score' => $score
                    ];
                    continue;
                }
                
                // Check for technical terms
                if (strpos($keyword_text, 'technology') !== false || 
                    strpos($keyword_text, 'system') !== false || 
                    strpos($keyword_text, 'algorithm') !== false || 
                    strpos($keyword_text, 'software') !== false) {
                    $categories['technical'][] = [
                        'keyword' => $keyword['keyword'],
                        'score' => $score
                    ];
                    continue;
                }
                
                // Check for action words (usually verbs)
                if (preg_match('/ing$|ed$|ize$|ise$/', $keyword_text)) {
                    $categories['action'][] = [
                        'keyword' => $keyword['keyword'],
                        'score' => $score
                    ];
                    continue;
                }
                
                // Check for descriptors (adjectives)
                if (preg_match('/ful$|less$|ive$|able$|ible$|al$|ous$/', $keyword_text)) {
                    $categories['descriptor'][] = [
                        'keyword' => $keyword['keyword'],
                        'score' => $score
                    ];
                    continue;
                }
                
                // Default to topic
                $categories['topic'][] = [
                    'keyword' => $keyword['keyword'],
                    'score' => $score
                ];
            }
            
            return $categories;
        } catch (\Exception $e) {
            ErrorLogger::log('keyword_generation', 'categorization_error', $e->getMessage(), [
                'content_length' => strlen($content)
            ], 'error');
            
            return [];
        }
    }
    
    /**
     * Simple TF-IDF based keyword extraction as fallback
     *
     * @param string $content Content to analyze
     * @param array $options Extraction options
     * @return array Extracted keywords
     */
    private function fallback_keyword_extraction($content, $options = []) {
        $limit = isset($options['limit']) ? (int) $options['limit'] : 15;
        $include_phrases = isset($options['include_phrases']) ? (bool) $options['include_phrases'] : true;
        
        // Simple word frequency analysis
        $content_lower = strtolower($content);
        $words = str_word_count($content_lower, 1);
        
        // Remove stopwords
        $words = array_filter($words, function($word) {
            return !in_array($word, $this->stopwords) && strlen($word) > 2;
        });
        
        // Count word frequencies
        $word_counts = array_count_values($words);
        
        // Extract phrases if requested
        $phrases = [];
        if ($include_phrases) {
            preg_match_all('/\b(\w+(?:\s+\w+){1,3})\b/i', $content_lower, $matches);
            if (!empty($matches[1])) {
                $phrase_candidates = $matches[1];
                
                // Filter phrases with stopwords at boundaries
                $filtered_phrases = array_filter($phrase_candidates, function($phrase) {
                    $words = explode(' ', $phrase);
                    // Ignore if first or last word is a stopword
                    return !in_array($words[0], $this->stopwords) && 
                           !in_array(end($words), $this->stopwords) &&
                           count($words) >= 2 && strlen($phrase) > 5;
                });
                
                // Count phrase frequencies
                $phrase_counts = array_count_values($filtered_phrases);
                
                // Convert to keyword format
                foreach ($phrase_counts as $phrase => $count) {
                    if ($count > 1) { // Only include phrases that appear multiple times
                        $phrases[] = [
                            'keyword' => $phrase,
                            'score' => min(1.0, $count / 10),
                            'occurrences' => $count
                        ];
                    }
                }
                
                // Sort phrases by occurrence count
                usort($phrases, function($a, $b) {
                    return $b['occurrences'] <=> $a['occurrences'];
                });
                
                $phrases = array_slice($phrases, 0, $limit/2); // Use half the limit for phrases
            }
        }
        
        // Convert words to keyword format
        $keywords = [];
        foreach ($word_counts as $word => $count) {
            // Normalize score based on word frequency
            $score = min(1.0, $count / 20); // Cap at 1.0
            
            $keywords[] = [
                'keyword' => $word,
                'score' => $score,
                'occurrences' => $count
            ];
        }
        
        // Sort by occurrence count
        usort($keywords, function($a, $b) {
            return $b['occurrences'] <=> $a['occurrences'];
        });
        
        // Apply limit
        $keywords = array_slice($keywords, 0, $limit - count($phrases));
        
        // Combine words and phrases
        $combined_keywords = array_merge($keywords, $phrases);
        
        // Sort by score
        usort($combined_keywords, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $result = [
            'keywords' => $combined_keywords,
            'keyword_count' => count($combined_keywords),
            'provider' => 'rule_based'
        ];
        
        if ($include_phrases) {
            $result['terms'] = $keywords;
            $result['phrases'] = $phrases;
            $result['term_count'] = count($keywords);
            $result['phrase_count'] = count($phrases);
        }
        
        return $result;
    }
    
    /**
     * Get primary keywords (highest scoring)
     *
     * @param array $keyword_results Results from generate() method
     * @param int $limit Maximum number of primary keywords
     * @return array Primary keywords
     */
    public function get_primary_keywords($keyword_results, $limit = 5) {
        if (empty($keyword_results['keywords'])) {
            return [];
        }
        
        // Sort by score and return top keywords
        $keywords = $keyword_results['keywords'];
        usort($keywords, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($keywords, 0, $limit);
    }
    
    /**
     * Format keywords as tags (comma-separated string)
     *
     * @param array $keyword_results Results from generate() method
     * @param int $limit Maximum number of tags to include
     * @return string Comma-separated tags
     */
    public function format_as_tags($keyword_results, $limit = 10) {
        if (empty($keyword_results['keywords'])) {
            return '';
        }
        
        $primary_keywords = $this->get_primary_keywords($keyword_results, $limit);
        $tags = array_map(function($item) {
            return $item['keyword'];
        }, $primary_keywords);
        
        return implode(', ', $tags);
    }
} 