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
    public function validate_required_fields() {
        $required_fields = [
            'type' => 'Content type is required',
            'title' => 'Title is required',
            'content' => 'Content body is required',
            'source_url' => 'Source URL is required'
        ];
        
        foreach ($required_fields as $field => $message) {
            if (empty($this->content_data[$field])) {
                $this->errors[$field] = $message;
            }
        }
    }

    /**
     * Validate content length meets minimum requirements
     *
     * @return void
     */
    public function validate_content_length() {
        if (empty($this->content_data['content'])) {
            return; // Already caught by required fields
        }
        
        $min_lengths = [
            'title' => 5,
            'content' => 100,
            'summary' => 10
        ];
        
        foreach ($min_lengths as $field => $min_length) {
            if (isset($this->content_data[$field]) && 
                strlen(wp_strip_all_tags($this->content_data[$field])) < $min_length) {
                $this->errors[$field] = sprintf(
                    '%s must be at least %d characters long',
                    ucfirst($field),
                    $min_length
                );
            }
        }
    }

    /**
     * Validate publish date is valid
     *
     * @return void
     */
    public function validate_publish_date() {
        if (!empty($this->content_data['publish_date'])) {
            $timestamp = strtotime($this->content_data['publish_date']);
            
            if ($timestamp === false) {
                $this->errors['publish_date'] = 'Invalid publish date format';
            } elseif ($timestamp > time()) {
                // Future dates are allowed but add a warning
                $this->errors['publish_date_warning'] = 'Publish date is in the future';
            }
        }
    }

    /**
     * Validate source URL is properly formatted
     *
     * @return void
     */
    public function validate_source_url() {
        if (!empty($this->content_data['source_url'])) {
            // Check URL is valid format
            if (filter_var($this->content_data['source_url'], FILTER_VALIDATE_URL) === false) {
                $this->errors['source_url'] = 'Source URL is not a valid URL format';
            }
            
            // Check URL has required parts (scheme and host)
            $url_parts = parse_url($this->content_data['source_url']);
            if (!isset($url_parts['scheme']) || !isset($url_parts['host'])) {
                $this->errors['source_url'] = 'Source URL must include scheme (http/https) and domain';
            }
        }
    }

    /**
     * Check content for minimum quality standards
     *
     * @return void
     */
    public function validate_content_quality() {
        if (empty($this->content_data['content'])) {
            return; // Already caught by required fields
        }
        
        $content = wp_strip_all_tags($this->content_data['content']);
        
        // Check content/title ratio (title shouldn't be longer than 20% of content)
        if (!empty($this->content_data['title'])) {
            $title_length = strlen(wp_strip_all_tags($this->content_data['title']));
            $content_length = strlen($content);
            
            if ($content_length > 0 && $title_length > ($content_length * 0.2)) {
                $this->errors['title_length'] = 'Title is too long compared to content body';
            }
        }
        
        // Check for minimum word count
        $word_count = str_word_count($content);
        if ($word_count < 50) {
            $this->errors['content_words'] = 'Content should have at least 50 words';
        }
        
        // Detect potential content issues (simple spam detection)
        if ($this->detect_keyword_stuffing($content)) {
            $this->errors['keyword_stuffing'] = 'Content appears to contain keyword stuffing';
        }
    }

    /**
     * Simple keyword stuffing detection
     *
     * @param string $content Content to check
     * @return bool True if keyword stuffing detected
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
        $completeness = 0;
        if (!empty($this->content_data['title'])) $completeness += 0.25;
        if (!empty($this->content_data['content']) && 
            strlen(wp_strip_all_tags($this->content_data['content'])) > 100) {
            $completeness += 0.25;
        }
        if (!empty($this->content_data['summary'])) $completeness += 0.25;
        if (!empty($this->content_data['source_url'])) $completeness += 0.25;
        
        // Calculate recency score (25%)
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
        
        // Calculate content length score (25%)
        $length_score = 0.2; // Default to low score
        if (!empty($this->content_data['content'])) {
            $content_length = strlen(wp_strip_all_tags($this->content_data['content']));
            if ($content_length > 5000) {
                $length_score = 1.0; // Very detailed content
            } else if ($content_length > 2000) {
                $length_score = 0.8; // Detailed content
            } else if ($content_length > 1000) {
                $length_score = 0.6; // Medium-length content
            } else if ($content_length > 500) {
                $length_score = 0.4; // Brief content
            }
        }
        
        // Calculate content structure score (25%)
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
} 