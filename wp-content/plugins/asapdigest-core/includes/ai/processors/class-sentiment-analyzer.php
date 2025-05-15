<?php
/**
 * Sentiment Analyzer Class
 *
 * Provides comprehensive sentiment analysis for content using AI services.
 *
 * @package ASAPDigest_Core
 * @subpackage AI\Processors
 * @since 3.1.0
 * @file-marker ASAP_Digest_SentimentAnalyzer
 * @created 05/07/25 | 04:45 PM PDT
 */

namespace ASAPDigest\AI\Processors;

use ASAPDigest\AI\AIServiceManager;
use ASAPDigest\Core\ErrorLogger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to analyze sentiment in content with detailed breakdown
 */
class SentimentAnalyzer {
    /**
     * AI Service Manager instance
     *
     * @var AIServiceManager
     */
    private $ai_service;
    
    /**
     * Sentiment model to use
     *
     * @var string
     */
    private $model = 'gpt-3.5-turbo';
    
    /**
     * Cache of sentiment analysis results
     *
     * @var array
     */
    private $sentiment_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_service = new AIServiceManager();
        
        // Set model from options if available
        $model_option = get_option('asap_ai_sentiment_model', '');
        if (!empty($model_option)) {
            $this->model = $model_option;
        }
    }
    
    /**
     * Analyze sentiment of content with detailed breakdown
     *
     * @param string $content Content to analyze
     * @param array $options Analysis options
     * @return array Sentiment analysis results
     */
    public function analyze($content, $options = []) {
        // Generate a cache key for this content
        $cache_key = md5($content) . '_' . md5(serialize($options));
        
        // Return cached results if available
        if (isset($this->sentiment_cache[$cache_key])) {
            return $this->sentiment_cache[$cache_key];
        }
        
        try {
            // Set up analysis options
            $analysis_options = [
                'model' => isset($options['model']) ? $options['model'] : $this->model,
                'detailed' => isset($options['detailed']) ? (bool) $options['detailed'] : true,
                'aspects' => isset($options['aspects']) ? $options['aspects'] : null,
                'response_format' => 'json'
            ];
            
            // Get basic sentiment classification
            $sentiment_categories = ['very_negative', 'negative', 'neutral', 'positive', 'very_positive'];
            $basic_sentiment = $this->ai_service->classify($content, $sentiment_categories, $analysis_options);
            
            // Get detailed sentiment if requested
            if ($analysis_options['detailed']) {
                $detailed_sentiment = $this->get_detailed_sentiment($content, $analysis_options);
            } else {
                $detailed_sentiment = null;
            }
            
            // Process and combine results
            $sentiment_results = $this->process_sentiment_results($basic_sentiment, $detailed_sentiment);
            
            // Cache the results
            $this->sentiment_cache[$cache_key] = $sentiment_results;
            
            return $sentiment_results;
        } catch (\Exception $e) {
            ErrorLogger::log('sentiment_analysis', 'analysis_error', $e->getMessage(), [
                'content_length' => strlen($content),
                'options' => $options
            ], 'error');
            
            // Fall back to simple rule-based analysis
            return $this->fallback_sentiment_analysis($content);
        }
    }
    
    /**
     * Get detailed sentiment breakdown using AI service
     *
     * @param string $content Content to analyze
     * @param array $options Analysis options
     * @return array|null Detailed sentiment analysis or null on failure
     */
    private function get_detailed_sentiment($content, $options) {
        try {
            // Define aspects to analyze if not provided
            $aspects = $options['aspects'] ?? [
                'tone' => ['objective', 'subjective', 'formal', 'informal', 'emotional'],
                'emotion' => ['joy', 'sadness', 'anger', 'fear', 'surprise', 'disgust'],
                'strength' => ['weak', 'moderate', 'strong']
            ];
            
            // Create aspect-specific classifiers
            $aspect_results = [];
            
            foreach ($aspects as $aspect => $categories) {
                // Skip if categories is empty
                if (empty($categories)) {
                    continue;
                }
                
                // Prepare options for this aspect
                $aspect_options = [
                    'model' => $options['model'],
                    'response_format' => 'json',
                    'aspect_specific' => true,
                    'aspect' => $aspect
                ];
                
                // Classify for this aspect
                $raw_result = $this->ai_service->classify($content, $categories, $aspect_options);
                
                // Format result
                if (!empty($raw_result)) {
                    // Normalize to standard format
                    $normalized = [];
                    
                    if (isset($raw_result[0]['category']) && isset($raw_result[0]['confidence'])) {
                        $normalized = $raw_result;
                    } elseif (isset($raw_result['category']) && isset($raw_result['confidence'])) {
                        $normalized = [['category' => $raw_result['category'], 'confidence' => $raw_result['confidence']]];
                    } else {
                        foreach ($raw_result as $key => $value) {
                            if (is_numeric($value)) {
                                $normalized[] = ['category' => $key, 'confidence' => (float) $value];
                            } elseif (is_array($value) && isset($value['confidence'])) {
                                $normalized[] = ['category' => $key, 'confidence' => (float) $value['confidence']];
                            }
                        }
                    }
                    
                    // Sort by confidence
                    usort($normalized, function($a, $b) {
                        return $b['confidence'] <=> $a['confidence'];
                    });
                    
                    $aspect_results[$aspect] = $normalized;
                }
            }
            
            return $aspect_results;
        } catch (\Exception $e) {
            ErrorLogger::log('sentiment_analysis', 'detailed_analysis_error', $e->getMessage(), [
                'content_length' => strlen($content)
            ], 'error');
            
            return null;
        }
    }
    
    /**
     * Process and standardize sentiment results
     *
     * @param array $basic_sentiment Basic sentiment classification
     * @param array|null $detailed_sentiment Detailed sentiment breakdown
     * @return array Combined sentiment results
     */
    private function process_sentiment_results($basic_sentiment, $detailed_sentiment) {
        $results = [
            'sentiment' => 'neutral',
            'score' => 0.0,
            'confidence' => 0.0,
            'provider' => 'ai_service'
        ];
        
        // Process basic sentiment
        $normalized = [];
        
        // Normalize format for consistency
        if (isset($basic_sentiment[0]['category']) && isset($basic_sentiment[0]['confidence'])) {
            $normalized = $basic_sentiment;
        } elseif (isset($basic_sentiment['category']) && isset($basic_sentiment['confidence'])) {
            $normalized = [['category' => $basic_sentiment['category'], 'confidence' => $basic_sentiment['confidence']]];
        } elseif (isset($basic_sentiment['classifications'])) {
            $normalized = $basic_sentiment['classifications'];
        } else {
            foreach ($basic_sentiment as $category => $value) {
                if (is_numeric($value)) {
                    $normalized[] = ['category' => $category, 'confidence' => (float) $value];
                } elseif (is_array($value) && isset($value['confidence'])) {
                    $normalized[] = ['category' => $category, 'confidence' => (float) $value['confidence']];
                }
            }
        }
        
        // Find highest confidence sentiment
        if (!empty($normalized)) {
            usort($normalized, function($a, $b) {
                return $b['confidence'] <=> $a['confidence'];
            });
            
            $top_sentiment = $normalized[0];
            $results['sentiment'] = $top_sentiment['category'];
            $results['confidence'] = $top_sentiment['confidence'];
            
            // Map sentiment label to score
            $score_map = [
                'very_negative' => -1.0,
                'negative' => -0.5,
                'neutral' => 0.0,
                'positive' => 0.5,
                'very_positive' => 1.0
            ];
            
            $results['score'] = isset($score_map[$top_sentiment['category']]) ? 
                $score_map[$top_sentiment['category']] : 0;
                
            // Add all classifications
            $results['classifications'] = $normalized;
        }
        
        // Add detailed sentiment if available
        if ($detailed_sentiment) {
            $results['aspects'] = $detailed_sentiment;
        }
        
        return $results;
    }
    
    /**
     * Simple rule-based sentiment analysis as fallback
     *
     * @param string $content Content to analyze
     * @return array Sentiment analysis results
     */
    private function fallback_sentiment_analysis($content) {
        // Define word lists for sentiment analysis
        $positive_words = [
            'good', 'great', 'excellent', 'positive', 'amazing', 'wonderful', 'beneficial',
            'love', 'happy', 'improved', 'success', 'best', 'perfect', 'effective',
            'recommend', 'impressive', 'exceptional', 'delighted', 'outstanding'
        ];
        
        $negative_words = [
            'bad', 'terrible', 'negative', 'awful', 'horrible', 'harmful', 'poor',
            'hate', 'sad', 'worse', 'failure', 'worst', 'awful', 'ineffective',
            'avoid', 'disappointing', 'unreliable', 'frustrated', 'mediocre'
        ];
        
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
            'sentiment' => $label,
            'score' => $sentiment_score,
            'confidence' => 0.7, // Fixed confidence for rule-based analysis
            'provider' => 'rule_based'
        ];
    }
    
    /**
     * Get sentiment description based on score
     *
     * @param float $score Sentiment score (-1.0 to 1.0)
     * @return string Human-readable description
     */
    public function get_sentiment_description($score) {
        if ($score < -0.75) {
            return 'Very negative';
        } elseif ($score < -0.25) {
            return 'Negative';
        } elseif ($score < 0.25) {
            return 'Neutral';
        } elseif ($score < 0.75) {
            return 'Positive';
        } else {
            return 'Very positive';
        }
    }
} 