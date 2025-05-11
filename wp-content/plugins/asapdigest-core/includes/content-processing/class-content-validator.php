<?php
/**
 * Content Validator Class
 *
 * Provides standardized validation for content items to ensure they meet
 * quality standards before being saved or processed further.
 *
 * @package ASAP_Digest
 * @subpackage Content_Processing
 * @since 2.2.0
 * @file-marker ASAP_Digest_Content_Validator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content Validator class
 *
 * @since 2.2.0
 */
class ASAP_Digest_Content_Validator {

    /**
     * Validation results store
     *
     * @var array
     */
    private $errors = [];

    /**
     * Content data to validate
     *
     * @var array
     */
    private $content_data = [];

    /**
     * Constructor
     *
     * @param array $content_data Content data to validate
     */
    public function __construct($content_data = []) {
        $this->content_data = $content_data;
        $this->errors = [];
    }

    /**
     * Set content data
     *
     * @param array $content_data Content data to validate
     * @return ASAP_Digest_Content_Validator
     */
    public function set_content_data($content_data) {
        $this->content_data = $content_data;
        $this->errors = []; // Reset errors when content changes
        return $this;
    }

    /**
     * Validate content against all rules
     *
     * @return bool True if valid, false if any validation errors
     */
    public function validate() {
        $this->errors = [];
        
        // Run all validation methods
        $this->validate_required_fields();
        $this->validate_content_length();
        $this->validate_publish_date();
        $this->validate_source_url();
        $this->validate_content_quality();
        
        // Content is valid if no errors
        return empty($this->errors);
    }

    /**
     * Check required fields are present
     *
     * @return void
     */
    private function validate_required_fields() {
        $required_fields = [
            'type' => 'Content type is required',
            'title' => 'Title is required',
            'content' => 'Content is required',
            'source_url' => 'Source URL is required',
        ];
        
        foreach ($required_fields as $field => $error_msg) {
            if (empty($this->content_data[$field])) {
                $this->errors[$field] = $error_msg;
            }
        }
    }

    /**
     * Check content meets minimum length requirements
     *
     * @return void
     */
    private function validate_content_length() {
        // Skip if no content (already caught by required field check)
        if (empty($this->content_data['content'])) {
            return;
        }
        
        // Get plain content without HTML for length check
        $plain_content = wp_strip_all_tags($this->content_data['content']);
        
        // Different length requirements by content type
        $type = $this->content_data['type'] ?? 'article';
        
        $min_lengths = [
            'article' => 150,
            'blog' => 100,
            'news' => 75,
            'podcast' => 50,
            'video' => 50,
            'tweet' => 10,
            'default' => 75,
        ];
        
        $min_length = $min_lengths[$type] ?? $min_lengths['default'];
        
        if (strlen($plain_content) < $min_length) {
            $this->errors['content_length'] = sprintf(
                'Content is too short (%d characters). Minimum required for %s type is %d characters.',
                strlen($plain_content),
                $type,
                $min_length
            );
        }
    }

    /**
     * Validate publish date format
     *
     * @return void
     */
    private function validate_publish_date() {
        // Skip if no publish date (optional field)
        if (empty($this->content_data['publish_date'])) {
            return;
        }
        
        $date = $this->content_data['publish_date'];
        $timestamp = strtotime($date);
        
        // Check if date is valid
        if ($timestamp === false) {
            $this->errors['publish_date'] = 'Invalid publish date format';
        }
        
        // Check if date is in the past or a reasonable time in the future (e.g., scheduled content)
        $now = time();
        $max_future = $now + (365 * 24 * 60 * 60); // One year in the future
        
        if ($timestamp > $max_future) {
            $this->errors['publish_date'] = 'Publish date is too far in the future';
        }
    }

    /**
     * Validate source URL format and accessibility
     *
     * @return void
     */
    private function validate_source_url() {
        // Skip if no URL (already caught by required field check)
        if (empty($this->content_data['source_url'])) {
            return;
        }
        
        $url = $this->content_data['source_url'];
        
        // Basic URL format validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors['source_url'] = 'Invalid URL format';
            return;
        }
        
        // Scheme check
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            $this->errors['source_url'] = 'URL must use HTTP or HTTPS protocol';
        }
        
        // Optional: Check for URL reachability (costly, use with caution)
        if (defined('ASAP_VALIDATE_URL_REACHABILITY') && ASAP_VALIDATE_URL_REACHABILITY) {
            $response = wp_remote_head($url, ['timeout' => 5]);
            
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
                $this->errors['source_url_reachable'] = 'Source URL appears to be unreachable';
            }
        }
    }

    /**
     * Validate various content quality aspects
     *
     * @return void
     */
    private function validate_content_quality() {
        // Skip if no content (already caught by required field check)
        if (empty($this->content_data['content'])) {
            return;
        }
        
        $plain_content = wp_strip_all_tags($this->content_data['content']);
        
        // Check for keyword stuffing
        if ($this->detect_keyword_stuffing($plain_content)) {
            $this->errors['keyword_stuffing'] = 'Content appears to contain keyword stuffing';
        }
        
        // Detect excessive special characters or emojis
        $special_char_ratio = $this->get_special_char_ratio($plain_content);
        if ($special_char_ratio > 0.25) { // 25% threshold
            $this->errors['special_chars'] = 'Content contains excessive special characters or emojis';
        }
        
        // Check title/content duplication (title words heavily repeated in content)
        if (!empty($this->content_data['title']) && $this->detect_title_duplication()) {
            $this->errors['title_duplication'] = 'Title appears to be excessively repeated in content';
        }
    }

    /**
     * Detect keyword stuffing in content
     *
     * @param string $content Content to check
     * @return bool True if keyword stuffing is detected
     */
    private function detect_keyword_stuffing($content) {
        // Get normalized words
        $words = preg_split('/\s+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
        $total_words = count($words);
        
        if ($total_words < 100) {
            return false; // Skip short content
        }
        
        // Count word frequencies
        $word_counts = array_count_values($words);
        
        // Check for any single word using more than 5% of content
        $threshold = $total_words * 0.05;
        
        foreach ($word_counts as $word => $count) {
            // Skip common short words
            if (strlen($word) <= 3) {
                continue;
            }
            
            if ($count > $threshold) {
                return true; // Keyword stuffing detected
            }
        }
        
        return false;
    }

    /**
     * Get ratio of special characters in content
     *
     * @param string $content Content to check
     * @return float Ratio of special characters
     */
    private function get_special_char_ratio($content) {
        $content_length = strlen($content);
        
        if ($content_length === 0) {
            return 0;
        }
        
        // Count characters that are not alphanumeric or basic punctuation
        $special_chars_count = preg_match_all('/[^\p{L}\p{N}\s.,;:!?()-]/u', $content);
        
        return $special_chars_count / $content_length;
    }

    /**
     * Detect excessive title repetition in content
     *
     * @return bool True if title is excessively repeated in content
     */
    private function detect_title_duplication() {
        $title = strtolower($this->content_data['title']);
        $content = strtolower(wp_strip_all_tags($this->content_data['content']));
        
        // Get unique words from title (excluding stop words)
        $title_words = preg_split('/\s+/', $title, -1, PREG_SPLIT_NO_EMPTY);
        $title_words = $this->filter_stop_words($title_words);
        
        if (empty($title_words)) {
            return false; // No significant words in title
        }
        
        // Count occurrences of each title word in content
        $duplication_count = 0;
        $content_word_count = str_word_count($content);
        
        foreach ($title_words as $word) {
            if (strlen($word) < 4) {
                continue; // Skip very short words
            }
            
            // Count occurrences (word boundaries)
            $occurrences = preg_match_all('/\b' . preg_quote($word, '/') . '\b/i', $content);
            
            // More than 3 occurrences per 100 words of content is suspicious
            $occurrence_ratio = ($occurrences / $content_word_count) * 100;
            if ($occurrence_ratio > 3) {
                $duplication_count++;
            }
        }
        
        // If more than half of significant title words are duplicated excessively, flag content
        return ($duplication_count > (count($title_words) / 2));
    }

    /**
     * Filter common stop words from a word array
     *
     * @param array $words Word array to filter
     * @return array Filtered array without stop words
     */
    private function filter_stop_words($words) {
        $stop_words = [
            'a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were',
            'this', 'that', 'these', 'those', 'in', 'on', 'at', 'by', 'for',
            'with', 'about', 'of', 'to', 'from', 'as', 'like', 'how', 'when',
            'where', 'why', 'what', 'who', 'which', 'not', 'no', 'yes',
        ];
        
        return array_diff($words, $stop_words);
    }

    /**
     * Calculate content quality score
     *
     * @return int Quality score (1-100)
     */
    public function calculate_quality_score() {
        // Return lower score if content is invalid
        if (!empty($this->errors) && count($this->errors) > 2) {
            return 30; // Base score for content with multiple issues
        }
        
        // Calculate completeness score (25%)
        $completeness = $this->calculate_completeness_score();
        
        // Calculate recency score (25%)
        $recency = $this->calculate_recency_score();
        
        // Calculate content length score (25%)
        $content_length = strlen(wp_strip_all_tags($this->content_data['content'] ?? ''));
        $length_score = $this->calculate_length_score($content_length);
        
        // Calculate content structure score (25%)
        $structure_score = $this->calculate_structure_score();
        
        // Apply weights to calculate final score
        $final_score = (
            $completeness * 0.25 +
            $recency * 0.25 +
            $length_score * 0.25 +
            $structure_score * 0.25
        );
        
        // Convert to 1-100 scale and ensure limits
        return max(1, min(100, round($final_score * 100)));
    }

    /**
     * Calculate completeness score (0.0-1.0)
     * 
     * @return float Completeness score
     */
    private function calculate_completeness_score() {
        $completeness = 0;
        
        // Required fields
        if (!empty($this->content_data['title'])) $completeness += 0.25;
        if (!empty($this->content_data['content']) && 
            strlen(wp_strip_all_tags($this->content_data['content'])) > 100) {
            $completeness += 0.25;
        }
        
        // Optional but valuable fields
        if (!empty($this->content_data['summary'])) $completeness += 0.25;
        if (!empty($this->content_data['source_url'])) $completeness += 0.25;
        
        return $completeness;
    }

    /**
     * Calculate recency score (0.0-1.0)
     * 
     * @return float Recency score
     */
    private function calculate_recency_score() {
        $recency = 0.5; // Default medium recency
        
        if (!empty($this->content_data['publish_date'])) {
            $pub_time = strtotime($this->content_data['publish_date']);
            
            if ($pub_time) {
                $days_old = (time() - $pub_time) / (60 * 60 * 24);
                
                if ($days_old <= 1) {
                    $recency = 1.0; // Very recent (last 24 hours)
                } else if ($days_old <= 7) {
                    $recency = 0.8; // Recent (last week)
                } else if ($days_old <= 30) {
                    $recency = 0.6; // Somewhat recent (last month)
                } else if ($days_old <= 90) {
                    $recency = 0.4; // Not very recent (last quarter)
                } else {
                    $recency = 0.2; // Old content
                }
            }
        }
        
        return $recency;
    }

    /**
     * Calculate length score (0.0-1.0)
     * 
     * @param int $content_length Content length in characters
     * @return float Length score
     */
    private function calculate_length_score($content_length) {
        $length_score = 0.2; // Default to low score
        
        if ($content_length > 5000) {
            $length_score = 1.0; // Very detailed content
        } else if ($content_length > 2000) {
            $length_score = 0.8; // Detailed content
        } else if ($content_length > 1000) {
            $length_score = 0.6; // Medium-length content
        } else if ($content_length > 500) {
            $length_score = 0.4; // Brief content
        }
        
        return $length_score;
    }

    /**
     * Calculate structure score (0.0-1.0)
     * 
     * @return float Structure score
     */
    private function calculate_structure_score() {
        $structure_score = 0.2; // Default to low score
        
        if (!empty($this->content_data['content'])) {
            // Check for paragraphs
            $paragraphs = preg_split('/\n\s*\n|\r\n\s*\r\n|\r\s*\r/', $this->content_data['content']);
            $para_count = count($paragraphs);
            
            if ($para_count >= 5) {
                $structure_score = 0.8;
            } else if ($para_count >= 3) {
                $structure_score = 0.6;
            } else if ($para_count >= 2) {
                $structure_score = 0.4;
            }
            
            // Bonus for having HTML formatting
            if (strlen($this->content_data['content']) > strlen(wp_strip_all_tags($this->content_data['content']))) {
                $structure_score += 0.2;
                $structure_score = min(1.0, $structure_score); // Cap at 1.0
            }
        }
        
        return $structure_score;
    }

    /**
     * Get all validation errors
     *
     * @return array Validation errors
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Check if content has validation errors
     *
     * @return bool True if errors exist
     */
    public function has_errors() {
        return !empty($this->errors);
    }

    /**
     * Generate content fingerprint for deduplication
     *
     * @param array $data Content data
     * @return string SHA-256 hash fingerprint
     */
    public static function generate_fingerprint($data) {
        // Normalize input fields
        $normalized_title = isset($data['title']) ? 
            strtolower(preg_replace('/\s+/', ' ', trim($data['title']))) : '';
            
        $normalized_content = isset($data['content']) ? 
            strtolower(preg_replace('/\s+/', ' ', trim(wp_strip_all_tags($data['content'])))) : '';
            
        $normalized_url = isset($data['source_url']) ? 
            strtolower(trim($data['source_url'])) : '';
            
        $normalized_date = isset($data['publish_date']) && $data['publish_date'] ? 
            strtolower(trim($data['publish_date'])) : '';
            
        $normalized_source_id = isset($data['source_id']) ? 
            strtolower(trim($data['source_id'])) : '';
        
        // Create canonical string
        $canonical = implode('||', [
            $normalized_title,
            $normalized_content,
            $normalized_url,
            $normalized_date,
            $normalized_source_id
        ]);
        
        // Generate fingerprint
        return hash('sha256', $canonical);
    }

    /**
     * Get a detailed quality assessment
     * 
     * @return array Quality assessment details
     */
    public function get_quality_assessment() {
        $content = !empty($this->content_data['content']) ? 
            wp_strip_all_tags($this->content_data['content']) : '';
            
        $content_length = strlen($content);
        
        // Word count
        $word_count = str_word_count($content);
        
        // Average word length
        $avg_word_length = $word_count > 0 ? $content_length / $word_count : 0;
        
        // Readability (simple algorithm)
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        
        // Simple estimate of "grade level" - higher means more complex
        $readability = ($avg_word_length * 0.39) + ($avg_sentence_length * 0.05) - 15.59;
        $readability = max(1, min(18, $readability)); // Cap between 1-18 (grade levels)
        
        // Content density (text to HTML ratio if HTML present)
        $density = 1.0; // Default for plain text
        if (!empty($this->content_data['content'])) {
            $html_length = strlen($this->content_data['content']);
            $density = $html_length > 0 ? $content_length / $html_length : 1.0;
        }
        
        // Calculate quality score components
        $completeness_score = $this->calculate_completeness_score();
        $recency_score = $this->calculate_recency_score();
        $length_score = $this->calculate_length_score($content_length);
        $structure_score = $this->calculate_structure_score();
        
        // Weighted final score
        $final_score = $this->calculate_quality_score();

        // Determine paragraphs count
        $paragraphs = 0;
        if (!empty($this->content_data['content'])) {
            $paragraphs = count(preg_split('/\n\s*\n|\r\n\s*\r\n|\r\s*\r/', $this->content_data['content']));
        }
        
        return [
            'length' => [
                'characters' => $content_length,
                'words' => $word_count,
                'sentences' => $sentence_count,
                'score' => $length_score,
            ],
            'readability' => [
                'avg_word_length' => round($avg_word_length, 2),
                'avg_sentence_length' => round($avg_sentence_length, 2),
                'grade_level' => round($readability, 1),
            ],
            'completeness' => [
                'score' => $completeness_score,
                'has_title' => !empty($this->content_data['title']),
                'has_content' => !empty($this->content_data['content']),
                'has_summary' => !empty($this->content_data['summary']),
                'has_source' => !empty($this->content_data['source_url']),
            ],
            'recency' => [
                'score' => $recency_score,
                'publish_date' => $this->content_data['publish_date'] ?? null,
            ],
            'structure' => [
                'score' => $structure_score,
                'paragraphs' => $paragraphs,
                'html_density' => round($density, 2),
            ],
            'overall_score' => $final_score,
            'score_interpretation' => $this->get_score_interpretation($final_score),
        ];
    }

    /**
     * Get interpretation of quality score
     * 
     * @param int $score Quality score (1-100)
     * @return string Interpretation
     */
    private function get_score_interpretation($score) {
        if ($score >= 90) {
            return 'Excellent';
        } else if ($score >= 80) {
            return 'Very Good';
        } else if ($score >= 70) {
            return 'Good';
        } else if ($score >= 60) {
            return 'Above Average';
        } else if ($score >= 50) {
            return 'Average';
        } else if ($score >= 40) {
            return 'Below Average';
        } else if ($score >= 30) {
            return 'Poor';
        } else {
            return 'Very Poor';
        }
    }
} 