<?php
/**
 * Content Summarizer Class
 *
 * Generates concise summaries for ingested content using AI services.
 *
 * @package ASAPDigest_Core
 * @subpackage AI\Processors
 * @since 3.1.0
 * @file-marker ASAP_Digest_Summarizer
 * @created 05/07/25 | 05:15 PM PDT
 */

namespace ASAPDigest\AI\Processors;

use ASAPDigest\AI\AIServiceManager;
use ASAPDigest\Core\ErrorLogger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to generate content summaries
 */
class Summarizer {
    /**
     * AI Service Manager instance
     *
     * @var AIServiceManager
     */
    private $ai_service;
    
    /**
     * Summary model to use
     *
     * @var string
     */
    private $model = 'gpt-3.5-turbo';
    
    /**
     * Cache of summary results
     *
     * @var array
     */
    private $summary_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_service = new AIServiceManager();
        
        // Set model from options if available
        $model_option = get_option('asap_ai_summary_model', '');
        if (!empty($model_option)) {
            $this->model = $model_option;
        }
    }
    
    /**
     * Generate summary for content
     *
     * @param string $content Content to summarize
     * @param array $options Summarization options
     * @return array Summary results
     */
    public function summarize($content, $options = []) {
        // Generate a cache key for this content
        $cache_key = md5($content) . '_' . md5(serialize($options));
        
        // Return cached results if available
        if (isset($this->summary_cache[$cache_key])) {
            return $this->summary_cache[$cache_key];
        }
        
        try {
            // Set up summarization options
            $summarization_options = [
                'model' => isset($options['model']) ? $options['model'] : $this->model,
                'max_tokens' => isset($options['max_tokens']) ? (int) $options['max_tokens'] : 150,
                'style' => isset($options['style']) ? $options['style'] : 'concise',
                'format' => isset($options['format']) ? $options['format'] : 'paragraph',
                'audience' => isset($options['audience']) ? $options['audience'] : 'general',
                'include_key_points' => isset($options['include_key_points']) ? (bool) $options['include_key_points'] : false,
                'include_headline' => isset($options['include_headline']) ? (bool) $options['include_headline'] : false
            ];
            
            // Summarize content using AI service
            $raw_summary = $this->ai_service->summarize($content, $summarization_options);
            
            // Generate summary enhancements if requested
            $summary_result = $this->process_summary($raw_summary, $content, $summarization_options);
            
            // Add headline if requested
            if ($summarization_options['include_headline']) {
                $summary_result['headline'] = $this->generate_headline($content, $raw_summary);
            }
            
            // Add key points if requested
            if ($summarization_options['include_key_points']) {
                $summary_result['key_points'] = $this->extract_key_points($content, $raw_summary);
            }
            
            // Add metadata
            $summary_result['meta'] = [
                'content_length' => strlen($content),
                'summary_length' => strlen($summary_result['summary']),
                'compression_ratio' => strlen($content) > 0 ? 
                    round(strlen($summary_result['summary']) / strlen($content), 2) : 0,
                'word_count_original' => str_word_count($content),
                'word_count_summary' => str_word_count($summary_result['summary']),
                'model' => $summarization_options['model'],
                'timestamp' => current_time('mysql')
            ];
            
            // Cache the results
            $this->summary_cache[$cache_key] = $summary_result;
            
            return $summary_result;
        } catch (\Exception $e) {
            ErrorLogger::log('content_summarization', 'summarization_error', $e->getMessage(), [
                'content_length' => strlen($content),
                'options' => $options
            ], 'error');
            
            // Fall back to simple extractive summarization
            return $this->fallback_summarization($content, $options);
        }
    }
    
    /**
     * Process raw summary into structured format
     *
     * @param string $raw_summary Summary from AI service
     * @param string $original_content Original content
     * @param array $options Summarization options
     * @return array Processed summary
     */
    private function process_summary($raw_summary, $original_content, $options) {
        // Clean up summary text
        $summary = trim($raw_summary);
        
        // Ensure proper formatting based on requested format
        if ($options['format'] === 'bullet_points' && strpos($summary, '• ') === false && strpos($summary, '- ') === false) {
            // Convert paragraph to bullet points if requested but not provided
            $sentences = preg_split('/(?<=[.!?])\s+/', $summary);
            $bullet_points = array_map(function($sentence) {
                return '• ' . $sentence;
            }, $sentences);
            
            $summary = implode("\n", $bullet_points);
        } elseif ($options['format'] === 'paragraph' && (strpos($summary, '• ') !== false || strpos($summary, '- ') !== false)) {
            // Convert bullet points to paragraph if requested but not provided
            $summary = str_replace(['• ', '- '], '', $summary);
            $summary = str_replace("\n", ' ', $summary);
        }
        
        // Get original content title if available
        $title = $this->extract_title($original_content);
        
        return [
            'summary' => $summary,
            'title' => $title,
            'format' => $options['format'],
            'style' => $options['style']
        ];
    }
    
    /**
     * Generate headline for content
     *
     * @param string $content Original content
     * @param string $summary Summary content
     * @return string Generated headline
     */
    private function generate_headline($content, $summary) {
        try {
            // First try to extract existing title from content
            $extracted_title = $this->extract_title($content);
            if (!empty($extracted_title)) {
                return $extracted_title;
            }
            
            // Generate headline using AI service
            $headline_options = [
                'model' => $this->model,
                'max_tokens' => 30,
                'headline_specific' => true
            ];
            
            // Use a separate prompt to generate a headline
            $system_prompt = "Generate a concise, attention-grabbing headline for the following content. Keep it under 10 words and make it engaging.";
            $headline = $this->ai_service->summarize($summary, $headline_options);
            
            // Clean up headline
            $headline = trim($headline);
            $headline = preg_replace('/^"(.+)"$/', '$1', $headline); // Remove quotes if present
            $headline = preg_replace('/^[Hh]eadline:\s*/', '', $headline); // Remove "Headline:" prefix
            
            return $headline;
        } catch (\Exception $e) {
            ErrorLogger::log('content_summarization', 'headline_error', $e->getMessage(), [
                'content_length' => strlen($content)
            ], 'warning');
            
            // Fall back to first sentence of summary
            $first_sentence = preg_split('/(?<=[.!?])\s+/', $summary, 2)[0];
            return $first_sentence;
        }
    }
    
    /**
     * Extract key points from content
     *
     * @param string $content Original content
     * @param string $summary Summary content
     * @return array Key points
     */
    private function extract_key_points($content, $summary) {
        try {
            // Use AI service to extract key points
            $key_points_options = [
                'model' => $this->model,
                'max_tokens' => 150,
                'key_points_specific' => true
            ];
            
            // Generate key points separately if not already in summary
            if (strpos($summary, '• ') === false && strpos($summary, '- ') === false) {
                $system_prompt = "Extract 3-5 key points from the following content. Format as bullet points starting with '• '.";
                $raw_key_points = $this->ai_service->summarize($content, $key_points_options);
                
                // Parse bullet points
                $key_points = [];
                if (preg_match_all('/[•\-]\s*([^\n]+)/', $raw_key_points, $matches)) {
                    $key_points = $matches[1];
                } else {
                    // Try splitting by newlines
                    $key_points = array_filter(array_map('trim', explode("\n", $raw_key_points)));
                }
                
                return $key_points;
            } else {
                // Extract bullet points from summary
                $key_points = [];
                if (preg_match_all('/[•\-]\s*([^\n]+)/', $summary, $matches)) {
                    $key_points = $matches[1];
                }
                
                return $key_points;
            }
        } catch (\Exception $e) {
            ErrorLogger::log('content_summarization', 'key_points_error', $e->getMessage(), [
                'content_length' => strlen($content)
            ], 'warning');
            
            // Fall back to extracted sentences
            return $this->extract_important_sentences($content, 3);
        }
    }
    
    /**
     * Extract title from content
     *
     * @param string $content Content to analyze
     * @return string|null Extracted title or null if not found
     */
    private function extract_title($content) {
        // Try to extract HTML title
        if (preg_match('/<title>([^<]+)<\/title>/i', $content, $matches)) {
            return trim($matches[1]);
        }
        
        // Try to extract h1 tag
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $content, $matches)) {
            return trim($matches[1]);
        }
        
        // Try first line if it's short
        $first_line = strtok($content, "\n");
        if (strlen($first_line) < 100 && !preg_match('/[.!?]/', $first_line)) {
            return trim($first_line);
        }
        
        return null;
    }
    
    /**
     * Simple extractive summarization as fallback
     *
     * @param string $content Content to summarize
     * @param array $options Summarization options
     * @return array Summary results
     */
    private function fallback_summarization($content, $options = []) {
        // Clean content (remove HTML tags)
        $clean_content = strip_tags($content);
        
        // Split into sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $clean_content);
        
        // For very short content, return as is
        if (count($sentences) <= 3) {
            return [
                'summary' => $clean_content,
                'title' => $this->extract_title($content),
                'format' => 'paragraph',
                'style' => 'original',
                'provider' => 'extractive',
                'meta' => [
                    'content_length' => strlen($content),
                    'summary_length' => strlen($clean_content),
                    'compression_ratio' => 1,
                    'word_count_original' => str_word_count($clean_content),
                    'word_count_summary' => str_word_count($clean_content),
                    'timestamp' => current_time('mysql')
                ]
            ];
        }
        
        // Score sentences based on position and keywords
        $sentence_scores = [];
        $total_sentences = count($sentences);
        
        // Extract frequent words (poor man's TF-IDF)
        $words = str_word_count(strtolower($clean_content), 1);
        $words = array_filter($words, function($word) {
            $stopwords = ['a', 'an', 'the', 'and', 'but', 'or', 'for', 'nor', 'on', 'at', 'to', 'from', 'by'];
            return !in_array($word, $stopwords) && strlen($word) > 2;
        });
        
        $word_freq = array_count_values($words);
        
        // Score each sentence
        foreach ($sentences as $i => $sentence) {
            $score = 0;
            
            // Position score (first and last sentences are important)
            if ($i === 0) {
                $score += 3;
            } elseif ($i === $total_sentences - 1) {
                $score += 2;
            } elseif ($i < $total_sentences / 5) {
                $score += 1; // First 20% of sentences
            }
            
            // Length score (prefer medium length sentences)
            $length = strlen($sentence);
            if ($length > 40 && $length < 200) {
                $score += 1;
            }
            
            // Keyword score
            $sentence_words = str_word_count(strtolower($sentence), 1);
            foreach ($sentence_words as $word) {
                if (isset($word_freq[$word]) && $word_freq[$word] > 1) {
                    $score += $word_freq[$word] / 10;
                }
            }
            
            $sentence_scores[$i] = $score;
        }
        
        // Determine number of sentences to keep
        $target_length = isset($options['max_tokens']) ? $options['max_tokens'] : 150;
        $avg_token_per_sentence = array_sum(array_map('strlen', $sentences)) / count($sentences) / 4; // Rough estimate: 4 chars per token
        $sentences_to_keep = min($total_sentences, max(3, ceil($target_length / $avg_token_per_sentence)));
        
        // Select top sentences
        arsort($sentence_scores);
        $top_sentence_indices = array_keys(array_slice($sentence_scores, 0, $sentences_to_keep, true));
        sort($top_sentence_indices); // Restore original order
        
        // Build summary
        $selected_sentences = [];
        foreach ($top_sentence_indices as $index) {
            $selected_sentences[] = $sentences[$index];
        }
        
        // Format based on options
        $format = isset($options['format']) ? $options['format'] : 'paragraph';
        
        if ($format === 'bullet_points') {
            $summary = implode("\n", array_map(function($sentence) {
                return '• ' . $sentence;
            }, $selected_sentences));
        } else {
            $summary = implode(' ', $selected_sentences);
        }
        
        return [
            'summary' => $summary,
            'title' => $this->extract_title($content),
            'format' => $format,
            'style' => 'extractive',
            'provider' => 'rule_based',
            'meta' => [
                'content_length' => strlen($clean_content),
                'summary_length' => strlen($summary),
                'compression_ratio' => strlen($clean_content) > 0 ? 
                    round(strlen($summary) / strlen($clean_content), 2) : 0,
                'word_count_original' => str_word_count($clean_content),
                'word_count_summary' => str_word_count($summary),
                'timestamp' => current_time('mysql')
            ]
        ];
    }
    
    /**
     * Extract important sentences for fallback summary
     *
     * @param string $content Content to analyze
     * @param int $count Number of sentences to extract
     * @return array Important sentences
     */
    private function extract_important_sentences($content, $count = 3) {
        // Clean content
        $clean_content = strip_tags($content);
        
        // Split into sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $clean_content);
        
        // For very short content, return all sentences
        if (count($sentences) <= $count) {
            return $sentences;
        }
        
        // Simple heuristic: take first sentence, last sentence, and a middle one
        $important_sentences = [$sentences[0]];
        
        if ($count >= 3) {
            $middle_index = floor(count($sentences) / 2);
            $important_sentences[] = $sentences[$middle_index];
        }
        
        $important_sentences[] = $sentences[count($sentences) - 1];
        
        // If more sentences needed, add evenly distributed ones
        if ($count > 3) {
            $step = count($sentences) / ($count - 2);
            for ($i = 1; $i < $count - 2; $i++) {
                $index = min(count($sentences) - 1, floor($i * $step));
                if (!in_array($sentences[$index], $important_sentences)) {
                    $important_sentences[] = $sentences[$index];
                }
            }
        }
        
        return $important_sentences;
    }
    
    /**
     * Get different summary styles for content
     *
     * @param string $content Content to summarize
     * @param array $styles Styles to generate
     * @return array Summaries in different styles
     */
    public function get_style_variants($content, $styles = ['concise', 'detailed', 'simple', 'technical']) {
        $variants = [];
        
        foreach ($styles as $style) {
            try {
                $options = [
                    'style' => $style,
                    'max_tokens' => $style === 'detailed' ? 300 : 150,
                    'audience' => $style === 'technical' ? 'expert' : 'general'
                ];
                
                $result = $this->summarize($content, $options);
                $variants[$style] = $result['summary'];
            } catch (\Exception $e) {
                $variants[$style] = "Error generating {$style} summary: " . $e->getMessage();
            }
        }
        
        return $variants;
    }
} 