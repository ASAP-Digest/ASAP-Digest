<?php
/**
 * Content Quality Calculator Class
 *
 * Uses AI to calculate quality scores for content and provides
 * recommendations for improving content quality.
 *
 * @package ASAPDigest_Core
 * @since 2.3.0
 * @created 05.22.25 | 10:25 AM PDT
 * @file-marker ASAP_Digest_ContentQualityCalculator
 */

namespace ASAPDigest\Core\ContentProcessing;

use ASAPDigest\Core\ErrorLogger;
use AsapDigest\AI\AIServiceManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle content quality calculation using AI
 */
class ContentQualityCalculator {
    /**
     * AI Service Manager instance
     *
     * @var AIServiceManager
     */
    private $ai_service;
    
    /**
     * Minimum acceptable score threshold
     *
     * @var float
     */
    private $min_score_threshold = 6.0;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_service = new AIServiceManager();
    }
    
    /**
     * Set minimum acceptable score threshold
     *
     * @param float $threshold Minimum score (0-10)
     * @return void
     */
    public function set_min_threshold($threshold) {
        $this->min_score_threshold = max(0, min(10, (float)$threshold));
    }
    
    /**
     * Calculate quality score for content
     *
     * @param string $content The content to analyze
     * @param array $options Optional parameters for scoring
     * @return array Quality score data with dimensions and overall score
     */
    public function calculate_score($content, $options = []) {
        try {
            // Default empty result
            $default_result = [
                'overall' => 0,
                'coherence' => 0,
                'clarity' => 0,
                'accuracy' => 0,
                'relevance' => 0,
                'engagement' => 0,
                'pass' => false,
                'recommendations' => ['Unable to calculate quality score']
            ];
            
            // Truncate content if it's too long
            $max_length = isset($options['max_length']) ? (int)$options['max_length'] : 1500;
            if (strlen($content) > $max_length) {
                $content = substr($content, 0, $max_length);
            }
            
            // Skip empty or very short content
            if (strlen($content) < 50) {
                return array_merge($default_result, [
                    'recommendations' => ['Content is too short to analyze (minimum 50 characters)']
                ]);
            }
            
            // Get AI provider and calculate quality
            $provider_options = isset($options['provider_options']) ? $options['provider_options'] : [];
            $quality_data = $this->ai_service->calculate_quality_score($content, $provider_options);
            
            // Ensure we have a valid structure
            if (!is_array($quality_data) || !isset($quality_data['overall'])) {
                // Try to extract from different potential structures
                if (isset($quality_data['scores']) && is_array($quality_data['scores'])) {
                    // Extract from 'scores' field
                    $quality_data = $quality_data['scores'];
                }
                
                // If still no 'overall', calculate it
                if (!isset($quality_data['overall']) && isset($quality_data['coherence'])) {
                    $dimensions = ['coherence', 'clarity', 'accuracy', 'relevance', 'engagement'];
                    $sum = 0;
                    $count = 0;
                    
                    foreach ($dimensions as $dim) {
                        if (isset($quality_data[$dim]) && is_numeric($quality_data[$dim])) {
                            $sum += $quality_data[$dim];
                            $count++;
                        }
                    }
                    
                    if ($count > 0) {
                        $quality_data['overall'] = round($sum / $count, 1);
                    }
                }
            }
            
            // Ensure numeric values and limit to 0-10 range
            $dimensions = ['overall', 'coherence', 'clarity', 'accuracy', 'relevance', 'engagement'];
            foreach ($dimensions as $dim) {
                if (isset($quality_data[$dim])) {
                    $quality_data[$dim] = round(max(0, min(10, (float)$quality_data[$dim])), 1);
                } else {
                    $quality_data[$dim] = 0;
                }
            }
            
            // Determine if content passes minimum threshold
            $quality_data['pass'] = $quality_data['overall'] >= $this->min_score_threshold;
            
            // Extract or generate recommendations
            if (isset($quality_data['recommendations'])) {
                // Already has recommendations
            } elseif (isset($quality_data['explanations']) && is_array($quality_data['explanations'])) {
                // Convert explanations to recommendations
                $quality_data['recommendations'] = $this->generate_recommendations_from_explanations($quality_data);
            } else {
                // Generate basic recommendations based on scores
                $quality_data['recommendations'] = $this->generate_recommendations_from_scores($quality_data);
            }
            
            return $quality_data;
        } catch (\Exception $e) {
            ErrorLogger::log('content_quality', 'calculation_error', $e->getMessage(), [
                'content_length' => strlen($content),
                'options' => $options
            ], 'error');
            
            return $default_result;
        }
    }
    
    /**
     * Check if content passes quality threshold
     *
     * @param string $content The content to analyze
     * @param array $options Optional parameters
     * @return bool Whether content passes threshold
     */
    public function passes_quality_threshold($content, $options = []) {
        $score_data = $this->calculate_score($content, $options);
        return $score_data['pass'];
    }
    
    /**
     * Generate recommendations based on explanations
     *
     * @param array $quality_data Quality score data
     * @return array Recommendations
     */
    private function generate_recommendations_from_explanations($quality_data) {
        $recommendations = [];
        $dimensions = ['coherence', 'clarity', 'accuracy', 'relevance', 'engagement'];
        
        foreach ($dimensions as $dim) {
            if (isset($quality_data[$dim]) && $quality_data[$dim] < 7 && isset($quality_data['explanations'][$dim])) {
                $recommendations[] = ucfirst($dim) . ': ' . $quality_data['explanations'][$dim];
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Content quality is generally good, but could be improved for better engagement.';
        }
        
        return $recommendations;
    }
    
    /**
     * Generate recommendations based on scores alone
     *
     * @param array $quality_data Quality score data
     * @return array Recommendations
     */
    private function generate_recommendations_from_scores($quality_data) {
        $recommendations = [];
        
        if ($quality_data['coherence'] < 6) {
            $recommendations[] = 'Improve content structure and logical flow to enhance coherence.';
        }
        
        if ($quality_data['clarity'] < 6) {
            $recommendations[] = 'Simplify complex sentences and define technical terms to increase clarity.';
        }
        
        if ($quality_data['accuracy'] < 6) {
            $recommendations[] = 'Verify facts, sources, and claims to improve accuracy.';
        }
        
        if ($quality_data['relevance'] < 6) {
            $recommendations[] = 'Focus more tightly on the main topic and ensure all content is directly relevant.';
        }
        
        if ($quality_data['engagement'] < 6) {
            $recommendations[] = 'Make content more engaging through storytelling, examples, or stronger hooks.';
        }
        
        if (empty($recommendations)) {
            if ($quality_data['overall'] < $this->min_score_threshold) {
                $recommendations[] = 'Review the content holistically for quality improvements.';
            } else {
                $recommendations[] = 'Content meets basic quality standards but could be enhanced.';
            }
        }
        
        return $recommendations;
    }
} 