<?php
/**
 * Content Classifier Class
 *
 * Provides enhanced content classification with hierarchical categories
 * and improved accuracy.
 *
 * @package ASAPDigest_Core
 * @subpackage AI\Processors
 * @since 3.1.0
 * @file-marker ASAP_Digest_Classifier
 * @created 05/07/25 | 04:25 PM PDT
 */

namespace ASAPDigest\AI\Processors;

use ASAPDigest\AI\AIServiceManager;
use ASAPDigest\Core\ErrorLogger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle content classification with hierarchical categories
 */
class Classifier {
    /**
     * AI Service Manager instance
     *
     * @var AIServiceManager
     */
    private $ai_service;
    
    /**
     * Predefined category taxonomies
     *
     * @var array
     */
    private $taxonomies = [];
    
    /**
     * Cache of classification results
     *
     * @var array
     */
    private $classification_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_service = new AIServiceManager();
        
        // Initialize default taxonomies
        $this->initialize_default_taxonomies();
    }
    
    /**
     * Initialize default classification taxonomies
     */
    private function initialize_default_taxonomies() {
        // Standard topic categories
        $this->taxonomies['topics'] = [
            'technology' => ['ai', 'blockchain', 'cybersecurity', 'software', 'hardware', 'mobile', 'data-science'],
            'business' => ['finance', 'entrepreneurship', 'marketing', 'management', 'economy', 'startups'],
            'science' => ['physics', 'biology', 'chemistry', 'astronomy', 'medicine', 'research'],
            'politics' => ['policy', 'government', 'elections', 'legislation', 'international-relations'],
            'health' => ['wellness', 'nutrition', 'mental-health', 'fitness', 'medicine', 'healthcare'],
            'entertainment' => ['movies', 'music', 'television', 'games', 'celebrities', 'arts'],
            'sports' => ['basketball', 'football', 'soccer', 'baseball', 'tennis', 'olympics'],
            'environment' => ['climate-change', 'sustainability', 'conservation', 'renewable-energy'],
            'education' => ['learning', 'schools', 'universities', 'teaching', 'education-technology']
        ];
        
        // Content formats
        $this->taxonomies['formats'] = [
            'article', 'news', 'opinion', 'tutorial', 'review', 'interview',
            'press-release', 'case-study', 'analysis', 'report'
        ];
        
        // Content tone
        $this->taxonomies['tone'] = [
            'informative', 'persuasive', 'controversial', 'technical',
            'educational', 'entertaining', 'promotional', 'critical'
        ];
        
        // Content audience
        $this->taxonomies['audience'] = [
            'general', 'beginner', 'intermediate', 'expert', 'professional',
            'academic', 'technical', 'consumer'
        ];
        
        // Apply custom taxonomies from options
        $custom_taxonomies = get_option('asap_classification_taxonomies', []);
        if (is_array($custom_taxonomies) && !empty($custom_taxonomies)) {
            $this->taxonomies = array_merge($this->taxonomies, $custom_taxonomies);
        }
    }
    
    /**
     * Add or update custom taxonomies
     *
     * @param string $taxonomy_name Name of taxonomy
     * @param array $categories Categories for this taxonomy
     * @return bool Success
     */
    public function set_taxonomy($taxonomy_name, $categories) {
        if (empty($taxonomy_name) || !is_array($categories)) {
            return false;
        }
        
        $taxonomy_name = sanitize_key($taxonomy_name);
        
        // If categories is hierarchical (associative array)
        if (array_keys($categories) !== range(0, count($categories) - 1)) {
            $sanitized_categories = [];
            
            foreach ($categories as $parent => $children) {
                $parent_key = sanitize_key($parent);
                
                if (is_array($children)) {
                    $sanitized_children = array_map('sanitize_key', $children);
                    $sanitized_categories[$parent_key] = $sanitized_children;
                } else {
                    $sanitized_categories[$parent_key] = sanitize_key($children);
                }
            }
            
            $this->taxonomies[$taxonomy_name] = $sanitized_categories;
        } else {
            // Flat array of categories
            $this->taxonomies[$taxonomy_name] = array_map('sanitize_key', $categories);
        }
        
        // Save to options for persistence
        $custom_taxonomies = get_option('asap_classification_taxonomies', []);
        $custom_taxonomies[$taxonomy_name] = $this->taxonomies[$taxonomy_name];
        update_option('asap_classification_taxonomies', $custom_taxonomies);
        
        return true;
    }
    
    /**
     * Get available taxonomies
     *
     * @return array Taxonomy list
     */
    public function get_taxonomies() {
        return $this->taxonomies;
    }
    
    /**
     * Classify content across multiple taxonomies
     *
     * @param string $content Content to classify
     * @param array $options Classification options
     * @return array Classification results
     */
    public function classify($content, $options = []) {
        // Generate a cache key for this content
        $cache_key = md5($content) . '_' . md5(serialize($options));
        
        // Return cached results if available
        if (isset($this->classification_cache[$cache_key])) {
            return $this->classification_cache[$cache_key];
        }
        
        $classification_results = [];
        $taxonomies_to_use = isset($options['taxonomies']) && is_array($options['taxonomies']) ? 
            $options['taxonomies'] : array_keys($this->taxonomies);
        
        try {
            foreach ($taxonomies_to_use as $taxonomy) {
                if (!isset($this->taxonomies[$taxonomy])) {
                    continue;
                }
                
                $categories = $this->taxonomies[$taxonomy];
                
                // Flatten hierarchical categories if needed
                $flat_categories = $this->flatten_categories($categories);
                
                // Set up taxonomy-specific options
                $taxonomy_options = [
                    'min_confidence' => isset($options['min_confidence']) ? (float) $options['min_confidence'] : 0.6,
                    'multi_label' => isset($options['multi_label']) ? (bool) $options['multi_label'] : true,
                    'hierarchical' => isset($options['hierarchical']) ? (bool) $options['hierarchical'] : true
                ];
                
                // Classify content using AI service
                $raw_classifications = $this->ai_service->classify($content, $flat_categories, $taxonomy_options);
                
                // Process and enhance the classifications
                $processed_classifications = $this->process_classifications(
                    $raw_classifications,
                    $categories,
                    $taxonomy,
                    $taxonomy_options
                );
                
                $classification_results[$taxonomy] = $processed_classifications;
            }
            
            // Add sentiment analysis if requested
            if (isset($options['include_sentiment']) && $options['include_sentiment']) {
                $classification_results['sentiment'] = $this->analyze_sentiment($content);
            }
            
            // Cache the results
            $this->classification_cache[$cache_key] = [
                'classifications' => $classification_results,
                'primary_categories' => $this->get_primary_categories($classification_results),
                'content_length' => strlen($content)
            ];
            
            return $this->classification_cache[$cache_key];
        } catch (\Exception $e) {
            ErrorLogger::log('content_classification', 'classification_error', $e->getMessage(), [
                'content_length' => strlen($content),
                'options' => $options
            ], 'error');
            
            return [
                'classifications' => [],
                'primary_categories' => [],
                'content_length' => strlen($content),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Flatten hierarchical categories for classification
     *
     * @param array $categories Hierarchical or flat categories
     * @return array Flattened categories
     */
    private function flatten_categories($categories) {
        $flat_categories = [];
        
        // Check if categories is hierarchical (associative array)
        if (is_array($categories) && array_keys($categories) !== range(0, count($categories) - 1)) {
            foreach ($categories as $parent => $children) {
                $flat_categories[] = $parent;
                
                if (is_array($children)) {
                    foreach ($children as $child) {
                        $flat_categories[] = $child;
                    }
                }
            }
        } else {
            $flat_categories = $categories;
        }
        
        return $flat_categories;
    }
    
    /**
     * Process and enhance raw classifications
     *
     * @param array $raw_classifications Classifications from AI service
     * @param array $original_categories Original category structure
     * @param string $taxonomy Taxonomy name
     * @param array $options Classification options
     * @return array Processed and enhanced classifications
     */
    private function process_classifications($raw_classifications, $original_categories, $taxonomy, $options) {
        $processed = [];
        $hierarchical = [];
        
        // Ensure consistent format for raw classifications
        $normalized_classifications = $this->normalize_classifications($raw_classifications);
        
        // Filter by minimum confidence
        $min_confidence = isset($options['min_confidence']) ? (float) $options['min_confidence'] : 0.6;
        $filtered_classifications = array_filter($normalized_classifications, function($item) use ($min_confidence) {
            return $item['confidence'] >= $min_confidence;
        });
        
        // Sort by confidence
        usort($filtered_classifications, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        // Create hierarchical structure if requested and if categories are hierarchical
        if ($options['hierarchical'] && is_array($original_categories) && 
            array_keys($original_categories) !== range(0, count($original_categories) - 1)) {
            
            // Create parent-child relationships
            $parent_child_map = [];
            foreach ($original_categories as $parent => $children) {
                if (is_array($children)) {
                    foreach ($children as $child) {
                        $parent_child_map[$child] = $parent;
                    }
                }
            }
            
            // Group by parent categories
            foreach ($filtered_classifications as $classification) {
                $category = $classification['category'];
                
                // Check if this is a child category
                if (isset($parent_child_map[$category])) {
                    $parent = $parent_child_map[$category];
                    
                    if (!isset($hierarchical[$parent])) {
                        $hierarchical[$parent] = [
                            'confidence' => 0,
                            'subcategories' => []
                        ];
                    }
                    
                    $hierarchical[$parent]['subcategories'][] = [
                        'category' => $category,
                        'confidence' => $classification['confidence']
                    ];
                    
                    // Update parent confidence based on child confidence
                    $hierarchical[$parent]['confidence'] = max(
                        $hierarchical[$parent]['confidence'],
                        $classification['confidence'] * 0.9 // Slightly reduced confidence for inferred parent
                    );
                } else {
                    // This is a parent category or flat category
                    if (!isset($hierarchical[$category])) {
                        $hierarchical[$category] = [
                            'confidence' => $classification['confidence'],
                            'subcategories' => []
                        ];
                    } else {
                        // Update confidence if higher
                        $hierarchical[$category]['confidence'] = max(
                            $hierarchical[$category]['confidence'],
                            $classification['confidence']
                        );
                    }
                }
            }
            
            // Sort hierarchical by parent confidence
            uasort($hierarchical, function($a, $b) {
                return $b['confidence'] <=> $a['confidence'];
            });
            
            // Sort subcategories by confidence
            foreach ($hierarchical as &$parent) {
                usort($parent['subcategories'], function($a, $b) {
                    return $b['confidence'] <=> $a['confidence'];
                });
            }
        }
        
        return [
            'flat' => $filtered_classifications,
            'hierarchical' => $hierarchical,
            'taxonomy' => $taxonomy
        ];
    }
    
    /**
     * Normalize classifications to a consistent format
     *
     * @param array $classifications Raw classifications
     * @return array Normalized classifications
     */
    private function normalize_classifications($classifications) {
        $normalized = [];
        
        if (empty($classifications)) {
            return $normalized;
        }
        
        // Check different potential formats and normalize
        if (isset($classifications[0]['category']) && isset($classifications[0]['confidence'])) {
            // Already in expected format
            return $classifications;
        } elseif (isset($classifications['category']) && isset($classifications['confidence'])) {
            // Single category result
            return [['category' => $classifications['category'], 'confidence' => $classifications['confidence']]];
        } elseif (isset($classifications['classifications'])) {
            // Nested in classifications key
            return $this->normalize_classifications($classifications['classifications']);
        }
        
        // Try to extract from associative array (category => confidence format)
        foreach ($classifications as $category => $value) {
            if (is_numeric($value)) {
                $normalized[] = [
                    'category' => $category,
                    'confidence' => (float) $value
                ];
            } elseif (is_array($value) && isset($value['confidence'])) {
                $normalized[] = [
                    'category' => $category,
                    'confidence' => (float) $value['confidence']
                ];
            }
        }
        
        // If we still have nothing, try to handle arrays of strings
        if (empty($normalized)) {
            foreach ($classifications as $item) {
                if (is_string($item)) {
                    $normalized[] = [
                        'category' => $item,
                        'confidence' => 1.0 // Default confidence
                    ];
                }
            }
        }
        
        return $normalized;
    }
    
    /**
     * Get primary categories across all taxonomies
     *
     * @param array $classification_results Results from classify method
     * @param int $limit Maximum primary categories per taxonomy
     * @return array Primary categories
     */
    public function get_primary_categories($classification_results, $limit = 3) {
        $primary_categories = [];
        
        foreach ($classification_results as $taxonomy => $result) {
            if ($taxonomy === 'sentiment') {
                $primary_categories['sentiment'] = $result;
                continue;
            }
            
            if (empty($result['flat'])) {
                continue;
            }
            
            $primary_categories[$taxonomy] = array_slice($result['flat'], 0, $limit);
        }
        
        return $primary_categories;
    }
    
    /**
     * Analyze sentiment of content
     *
     * @param string $content Content to analyze
     * @return array Sentiment analysis with score and label
     */
    public function analyze_sentiment($content) {
        try {
            // Use AI service manager for sentiment analysis
            try {
                // Create AI service request options
                $options = [
                    'model' => 'gpt-3.5-turbo', // Faster model for sentiment
                    'response_format' => 'json',
                    'sentiment_specific' => true
                ];
                
                // Use classify with sentiment-specific categories
                $sentiment_categories = ['very_negative', 'negative', 'neutral', 'positive', 'very_positive'];
                $raw_result = $this->ai_service->classify($content, $sentiment_categories, $options);
                
                // Extract highest confidence sentiment
                $normalized = $this->normalize_classifications($raw_result);
                if (!empty($normalized)) {
                    usort($normalized, function($a, $b) {
                        return $b['confidence'] <=> $a['confidence'];
                    });
                    
                    $top_sentiment = $normalized[0];
                    
                    // Convert category to score (-1 to 1 range)
                    $score_map = [
                        'very_negative' => -1.0,
                        'negative' => -0.5,
                        'neutral' => 0.0,
                        'positive' => 0.5,
                        'very_positive' => 1.0
                    ];
                    
                    $score = isset($score_map[$top_sentiment['category']]) ? 
                        $score_map[$top_sentiment['category']] : 0;
                    
                    return [
                        'score' => $score,
                        'label' => $top_sentiment['category'],
                        'confidence' => $top_sentiment['confidence'],
                        'provider' => 'ai_service'
                    ];
                }
            } catch (\Exception $ai_error) {
                ErrorLogger::log('sentiment_analysis', 'ai_service_error', $ai_error->getMessage(), [
                    'content_length' => strlen($content)
                ], 'warning');
                // Continue to fallback
            }
            
            // Fallback to simple rule-based sentiment analysis
            $positive_words = ['good', 'great', 'excellent', 'positive', 'amazing', 'wonderful', 'beneficial'];
            $negative_words = ['bad', 'terrible', 'negative', 'awful', 'horrible', 'harmful', 'poor'];
            
            $content_lower = strtolower($content);
            $positive_count = 0;
            $negative_count = 0;
            
            foreach ($positive_words as $word) {
                $positive_count += substr_count($content_lower, $word);
            }
            
            foreach ($negative_words as $word) {
                $negative_count += substr_count($content_lower, $word);
            }
            
            $total_words = str_word_count($content);
            $sentiment_score = $total_words > 0 ? 
                ($positive_count - $negative_count) / sqrt($total_words) : 0;
            
            // Normalize to -1 to 1 range
            $sentiment_score = max(-1, min(1, $sentiment_score));
            
            // Determine label
            $label = 'neutral';
            if ($sentiment_score < -0.5) {
                $label = 'very_negative';
            } elseif ($sentiment_score < 0) {
                $label = 'negative';
            } elseif ($sentiment_score > 0.5) {
                $label = 'very_positive';
            } elseif ($sentiment_score > 0) {
                $label = 'positive';
            }
            
            return [
                'score' => $sentiment_score,
                'label' => $label,
                'provider' => 'rule_based'
            ];
        } catch (\Exception $e) {
            ErrorLogger::log('sentiment_analysis', 'sentiment_error', $e->getMessage(), [
                'content_length' => strlen($content)
            ], 'error');
            
            return [
                'score' => 0,
                'label' => 'neutral',
                'error' => $e->getMessage()
            ];
        }
    }
} 