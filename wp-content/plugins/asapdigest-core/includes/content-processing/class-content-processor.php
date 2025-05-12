<?php
/**
 * Content Processor Class
 *
 * Central hub for content processing pipeline that ties together validation,
 * deduplication, and quality scoring.
 *
 * Error Handling & Logging:
 *   - All critical errors and exceptions are logged using the ErrorLogger utility (see \ASAPDigest\Core\ErrorLogger).
 *   - Errors are recorded in the wp_asap_error_log table with context, type, message, data, and severity.
 *   - PHP error_log is used as a fallback and for development/debugging.
 *   - This ensures a unified, queryable error log for admin monitoring and alerting.
 *
 * @see \ASAPDigest\Core\ErrorLogger
 * @package ASAP_Digest
 * @subpackage Content_Processing
 * @since 2.2.0
 * @file-marker ASAP_Digest_Content_Processor
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use ASAPDigest\Core\ErrorLogger;

/**
 * Content Processor class
 *
 * @since 2.2.0
 */
class ASAP_Digest_Content_Processor {

    /**
     * Content validator instance
     *
     * @var ASAP_Digest_Content_Validator
     */
    private $validator;

    /**
     * Content deduplicator instance
     *
     * @var ASAP_Digest_Content_Deduplicator
     */
    private $deduplicator;
    
    /**
     * Content quality instance
     *
     * @var ASAP_Digest_Content_Quality
     */
    private $quality;

    /**
     * Processing results
     *
     * @var array
     */
    private $results;

    /**
     * Constructor
     */
    public function __construct() {
        require_once(dirname(__FILE__) . '/class-content-validator.php');
        require_once(dirname(__FILE__) . '/class-content-deduplicator.php');
        require_once(dirname(__FILE__) . '/class-content-quality.php');
        
        $this->validator = new ASAP_Digest_Content_Validator();
        $this->deduplicator = new ASAP_Digest_Content_Deduplicator();
        $this->quality = new ASAP_Digest_Content_Quality();
        $this->results = [
            'success' => false,
            'errors' => [],
            'warnings' => [],
            'data' => [],
        ];
    }

    /**
     * Process content data
     *
     * @param array $content_data Content data
     * @param int $exclude_id ID to exclude from duplication check (for updates)
     * @return array Processing results
     */
    public function process($content_data, $exclude_id = 0) {
        $this->results = [
            'success' => false,
            'errors' => [],
            'warnings' => [],
            'data' => [],
        ];
        
        // Step 1: Validate content
        $this->validator->set_content_data($content_data);
        $is_valid = $this->validator->validate();
        
        if (!$is_valid) {
            $this->results['errors'] = $this->validator->get_errors();
            /**
             * Log validation failure using ErrorLogger utility.
             * Context: 'content_processing', error_type: 'validation_failed', severity: 'warning'.
             * Includes validation errors and content data for debugging.
             */
            ErrorLogger::log('content_processing', 'validation_failed', 'Content validation failed', [
                'errors' => $this->validator->get_errors(),
                'content_data' => $content_data
            ], 'warning');
            return $this->results;
        }
        
        // Step 2: Check for duplicates
        $fingerprint = $this->deduplicator->generate_fingerprint($content_data);
        $duplicate_id = $this->deduplicator->is_duplicate($fingerprint, $exclude_id);
        
        if ($duplicate_id) {
            $duplicate_details = $this->deduplicator->get_duplicate_details($duplicate_id);
            $this->results['errors']['duplicate'] = 'Content duplicate found (ID: ' . $duplicate_id . ')';
            $this->results['data']['duplicate'] = $duplicate_details;
            /**
             * Log deduplication failure using ErrorLogger utility.
             * Context: 'content_processing', error_type: 'duplicate', severity: 'warning'.
             * Includes duplicate ID, details, and content data for debugging.
             */
            ErrorLogger::log('content_processing', 'duplicate', 'Content duplicate found', [
                'duplicate_id' => $duplicate_id,
                'duplicate_details' => $duplicate_details,
                'content_data' => $content_data
            ], 'warning');
            return $this->results;
        }
        
        // Step 3: Perform comprehensive quality assessment
        $this->quality->set_content_data($content_data);
        $quality_assessment = $this->quality->assess();
        $quality_score = $quality_assessment['score'];
        
        // Step 4: AI enrichment (summarization, entities, classification, keywords)
        $ai_metadata = [
            'summary' => '',
            'entities' => [],
            'classifications' => [],
            'keywords' => []
        ];
        try {
            require_once dirname(__FILE__, 2) . '/ai/class-ai-service-manager.php';
            $ai_manager = new \AsapDigest\AI\AIServiceManager();
            // Summarize
            $ai_metadata['summary'] = $ai_manager->summarize($content_data['content']);
        } catch (\Exception $e) {
            ErrorLogger::log('ai_enrichment', 'summarize_error', $e->getMessage(), ['content_data' => $content_data], 'warning');
        }
        try {
            $ai_metadata['entities'] = $ai_manager->extract_entities($content_data['content']);
        } catch (\Exception $e) {
            ErrorLogger::log('ai_enrichment', 'entities_error', $e->getMessage(), ['content_data' => $content_data], 'warning');
        }
        try {
            $ai_metadata['classifications'] = $ai_manager->classify($content_data['content'], []);
        } catch (\Exception $e) {
            ErrorLogger::log('ai_enrichment', 'classify_error', $e->getMessage(), ['content_data' => $content_data], 'warning');
        }
        try {
            $ai_metadata['keywords'] = $ai_manager->generate_keywords($content_data['content']);
        } catch (\Exception $e) {
            ErrorLogger::log('ai_enrichment', 'keywords_error', $e->getMessage(), ['content_data' => $content_data], 'warning');
        }
        // Store AI metadata as JSON
        $content_data['ai_metadata'] = json_encode($ai_metadata);
        
        // Optionally check if quality score is below auto-reject threshold
        if (defined('ASAP_QUALITY_SCORE_AUTO_REJECT') && $quality_score < ASAP_QUALITY_SCORE_AUTO_REJECT) {
            $this->results['errors']['quality_score'] = sprintf(
                'Content quality score (%d) is below minimum threshold (%d)',
                $quality_score,
                ASAP_QUALITY_SCORE_AUTO_REJECT
            );
            $this->results['data']['quality_assessment'] = $quality_assessment;
            /**
             * Log quality score auto-reject using ErrorLogger utility.
             * Context: 'content_processing', error_type: 'quality_score', severity: 'warning'.
             * Includes score, threshold, and content data for debugging.
             */
            ErrorLogger::log('content_processing', 'quality_score', 'Content quality score below threshold', [
                'score' => $quality_score,
                'threshold' => ASAP_QUALITY_SCORE_AUTO_REJECT,
                'content_data' => $content_data
            ], 'warning');
            return $this->results;
        }
        
        // Compile final processed data
        $this->results['success'] = true;
        $this->results['data'] = [
            'content' => $content_data,
            'fingerprint' => $fingerprint,
            'quality_score' => $quality_score,
            'quality_assessment' => $quality_assessment,
        ];
        
        // Add warnings for low quality score
        if (defined('ASAP_QUALITY_SCORE_MINIMUM') && $quality_score < ASAP_QUALITY_SCORE_MINIMUM) {
            $this->results['warnings']['quality_score'] = sprintf(
                'Content quality score (%d) is below recommended threshold (%d)',
                $quality_score,
                ASAP_QUALITY_SCORE_MINIMUM
            );
            // Add quality improvement suggestions
            $this->results['warnings']['suggestions'] = $quality_assessment['suggestions'];
        }
        
        return $this->results;
    }

    /**
     * Save processed content
     *
     * @param array $processed_data Processed content data from process() method
     * @param int $update_id Optional ID to update (if updating existing content)
     * @return array Result with content_id on success
     */
    public function save($processed_data, $update_id = 0) {
        global $wpdb;
        
        $result = [
            'success' => false,
            'errors' => [],
            'content_id' => 0,
        ];
        
        // Verify data structure
        if (!isset($processed_data['success']) || !$processed_data['success'] || 
            !isset($processed_data['data']) || !is_array($processed_data['data'])) {
            $result['errors'][] = 'Invalid processed data provided';
            /**
             * Log invalid processed data using ErrorLogger utility.
             * Context: 'content_processing', error_type: 'invalid_processed_data', severity: 'error'.
             * Includes processed_data and update_id for debugging.
             */
            ErrorLogger::log('content_processing', 'invalid_processed_data', 'Invalid processed data provided to save()', [
                'processed_data' => $processed_data,
                'update_id' => $update_id
            ], 'error');
            return $result;
        }
        $content_data = $processed_data['data']['content'];
        $fingerprint = $processed_data['data']['fingerprint'];
        $quality_score = $processed_data['data']['quality_score'];
        // Check if we should use ContentStorage integration
        if (defined('ASAP_USE_CONTENT_STORAGE_INTEGRATION') && ASAP_USE_CONTENT_STORAGE_INTEGRATION) {
            // Get the ContentStorage class name from config
            $storage_class = defined('ASAP_CONTENT_STORAGE_CLASS') ? ASAP_CONTENT_STORAGE_CLASS : 'AsapDigest\\Crawler\\ContentStorage';
            // Check if class exists
            if (class_exists($storage_class)) {
                try {
                    // Initialize storage class
                    $storage = new $storage_class();
                    // Add quality information to content data
                    $content_data['quality_score'] = $quality_score;
                    $content_data['fingerprint'] = $fingerprint;
                    if (!empty($processed_data['data']['quality_assessment'])) {
                        // Add quality assessment to extra data
                        if (!isset($content_data['extra'])) {
                            $content_data['extra'] = [];
                        }
                        $content_data['extra']['quality_assessment'] = $processed_data['data']['quality_assessment'];
                    }
                    // Store content using ContentStorage
                    $storage_id = $update_id ? $storage->update($update_id, $content_data) : $storage->store($content_data);
                    if ($storage_id) {
                        $result['success'] = true;
                        $result['content_id'] = $storage_id;
                    } else {
                        $result['errors'][] = 'Failed to store content using ContentStorage';
                        /**
                         * Log storage failure using ErrorLogger utility.
                         * Context: 'content_processing', error_type: 'storage_failed', severity: 'error'.
                         * Includes content_data and update_id for debugging.
                         */
                        ErrorLogger::log('content_processing', 'storage_failed', 'Failed to store content using ContentStorage', [
                            'content_data' => $content_data,
                            'update_id' => $update_id
                        ], 'error');
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = 'Exception during ContentStorage: ' . $e->getMessage();
                    /**
                     * Log ContentStorage exception using ErrorLogger utility.
                     * Context: 'content_processing', error_type: 'storage_exception', severity: 'critical'.
                     * Includes exception message, stack trace, content_data, and update_id for debugging.
                     */
                    ErrorLogger::log('content_processing', 'storage_exception', $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'content_data' => $content_data,
                        'update_id' => $update_id
                    ], 'critical');
                }
            } else {
                $result['errors'][] = 'ContentStorage class not found: ' . $storage_class;
                /**
                 * Log missing ContentStorage class using ErrorLogger utility.
                 * Context: 'content_processing', error_type: 'storage_class_missing', severity: 'error'.
                 * Includes storage_class and content_data for debugging.
                 */
                ErrorLogger::log('content_processing', 'storage_class_missing', 'ContentStorage class not found', [
                    'storage_class' => $storage_class,
                    'content_data' => $content_data
                ], 'error');
            }
        } else {
            // Default storage implementation if ContentStorage integration is disabled or failed
            // Prepare data for database
            try {
                $content_table = $wpdb->prefix . 'asap_ingested_content';
                $now = current_time('mysql');
                
                $db_data = [
                    'type' => sanitize_text_field($content_data['type'] ?? 'article'),
                    'title' => sanitize_text_field($content_data['title'] ?? ''),
                    'content' => $content_data['content'] ?? '',
                    'summary' => sanitize_text_field($content_data['summary'] ?? ''),
                    'source_url' => esc_url_raw($content_data['source_url'] ?? ''),
                    'source_id' => sanitize_text_field($content_data['source_id'] ?? ''),
                    'fingerprint' => $fingerprint,
                    'quality_score' => $quality_score,
                    'status' => sanitize_text_field($content_data['status'] ?? 'pending'),
                    'updated_at' => $now,
                ];
                
                // Handle publish date
                if (!empty($content_data['publish_date'])) {
                    $db_data['publish_date'] = date('Y-m-d H:i:s', strtotime($content_data['publish_date']));
                }
                
                // Handle extra data as JSON
                if (!empty($content_data['extra']) && is_array($content_data['extra'])) {
                    $db_data['extra'] = json_encode($content_data['extra']);
                }
                
                // Update or insert based on update_id
                if ($update_id > 0) {
                    // Update existing content
                    $result_query = $wpdb->update(
                        $content_table,
                        $db_data,
                        ['id' => $update_id]
                    );
                    
                    if ($result_query === false) {
                        $result['errors'][] = 'Database error updating content: ' . $wpdb->last_error;
                        return $result;
                    }
                    
                    // Update index
                    $index_result = $this->deduplicator->add_to_index($update_id, $fingerprint, $quality_score);
                    
                    if (!$index_result) {
                        $result['errors'][] = 'Error updating content index';
                        return $result;
                    }
                    
                    $result['success'] = true;
                    $result['content_id'] = $update_id;
                    
                    // Trigger update action
                    do_action('asap_content_updated', $update_id, $db_data);
                } else {
                    // Insert new content
                    $db_data['ingestion_date'] = $now;
                    $db_data['created_at'] = $now;
                    
                    $result_query = $wpdb->insert($content_table, $db_data);
                    
                    if ($result_query === false) {
                        $result['errors'][] = 'Database error inserting content: ' . $wpdb->last_error;
                        return $result;
                    }
                    
                    $content_id = (int) $wpdb->insert_id;
                    
                    // Add to index
                    $index_result = $this->deduplicator->add_to_index($content_id, $fingerprint, $quality_score);
                    
                    if (!$index_result) {
                        // If adding to index fails, attempt to delete the content to maintain consistency
                        $wpdb->delete($content_table, ['id' => $content_id]);
                        $result['errors'][] = 'Error adding content to index';
                        return $result;
                    }
                    
                    $result['success'] = true;
                    $result['content_id'] = $content_id;
                    
                    // Trigger create action
                    do_action('asap_content_added', $content_id, $db_data);
                }
            } catch (\Exception $e) {
                $result['errors'][] = 'Exception during DB insert: ' . $e->getMessage();
                /**
                 * Log DB insert exception using ErrorLogger utility.
                 * Context: 'content_processing', error_type: 'db_insert_exception', severity: 'critical'.
                 * Includes exception message, stack trace, content_data, and update_id for debugging.
                 */
                ErrorLogger::log('content_processing', 'db_insert_exception', $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'content_data' => $content_data,
                    'update_id' => $update_id
                ], 'critical');
            }
        }
        return $result;
    }

    /**
     * Delete content
     *
     * @param int $content_id Content ID to delete
     * @return bool Success or failure
     */
    public function delete($content_id) {
        global $wpdb;
        
        $content_table = $wpdb->prefix . 'asap_ingested_content';
        
        // Get content before deletion for hooks
        $content = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$content_table} WHERE id = %d", $content_id),
            ARRAY_A
        );
        
        if (!$content) {
            // Content not found
            return false;
        }
        
        // Delete from index first
        $this->deduplicator->remove_from_index($content_id);
        
        // Delete content
        $result = $wpdb->delete(
            $content_table,
            ['id' => $content_id]
        );
        
        if ($result && $content) {
            // Trigger delete action
            do_action('asap_content_deleted', $content_id, $content);
        }
        
        return $result !== false;
    }

    /**
     * Get content by ID
     *
     * @param int $content_id Content ID
     * @return array|false Content data or false if not found
     */
    public function get_content($content_id) {
        global $wpdb;
        
        $content_table = $wpdb->prefix . 'asap_ingested_content';
        
        $content = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$content_table} WHERE id = %d", $content_id),
            ARRAY_A
        );
        
        // Parse JSON extra data if present
        if ($content && !empty($content['extra']) && is_string($content['extra'])) {
            $extra = json_decode($content['extra'], true);
            if (is_array($extra)) {
                $content['extra'] = $extra;
            }
        }
        
        return $content ?: false;
    }

    /**
     * Get processing results
     *
     * @return array Processing results
     */
    public function get_results() {
        return $this->results;
    }

    /**
     * Find similar content
     *
     * @param array $content_data Content data to find similar content for
     * @param int $limit Maximum number of similar items to return (default 5)
     * @return array Similar content items
     */
    public function find_similar_content($content_data, $limit = 5) {
        // Generate fingerprint for this content
        $fingerprint = $this->deduplicator->generate_fingerprint($content_data);
        
        // Get similar content by fingerprint (exact match)
        $similar_items = $this->deduplicator->get_similar_content($fingerprint, $limit);
        
        // If we didn't find enough items, try more fuzzy matching
        if (count($similar_items) < $limit) {
            $remaining_limit = $limit - count($similar_items);
            
            // Find potential duplicates based on field similarity
            $potential_duplicates = $this->deduplicator->find_potential_duplicates($content_data, $remaining_limit);
            
            // Filter out any exact duplicates already found
            $existing_ids = array_column($similar_items, 'id');
            
            $additional_items = [];
            foreach ($potential_duplicates as $duplicate) {
                if (!in_array($duplicate['id'], $existing_ids)) {
                    $additional_items[] = $duplicate;
                    $existing_ids[] = $duplicate['id']; // Prevent duplicates
                    
                    // Break if we've reached our limit
                    if (count($additional_items) >= $remaining_limit) {
                        break;
                    }
                }
            }
            
            // Merge additional items with exact matches
            $similar_items = array_merge($similar_items, $additional_items);
        }
        
        return $similar_items;
    }

    /**
     * Get content statistics
     *
     * @return array Content statistics
     */
    public function get_content_stats() {
        global $wpdb;
        
        $content_table = $wpdb->prefix . 'asap_ingested_content';
        
        // Get total content count
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$content_table}");
        
        // Get counts by status
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$content_table} GROUP BY status",
            ARRAY_A
        );
        
        // Get counts by type
        $type_counts = $wpdb->get_results(
            "SELECT type, COUNT(*) as count FROM {$content_table} GROUP BY type",
            ARRAY_A
        );
        
        // Get quality score distribution
        $quality_distribution = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN quality_score >= 90 THEN 'excellent'
                    WHEN quality_score >= 70 THEN 'good'
                    WHEN quality_score >= 50 THEN 'average'
                    WHEN quality_score >= 30 THEN 'poor'
                    ELSE 'very_poor'
                END as quality_range,
                COUNT(*) as count
            FROM {$content_table}
            GROUP BY quality_range",
            ARRAY_A
        );
        
        // Get recent content
        $recent_content = $wpdb->get_results(
            "SELECT id, title, type, quality_score, created_at
            FROM {$content_table}
            ORDER BY created_at DESC
            LIMIT 5",
            ARRAY_A
        );
        
        return [
            'total_count' => (int) $total_count,
            'status_counts' => $status_counts,
            'type_counts' => $type_counts,
            'quality_distribution' => $quality_distribution,
            'recent_content' => $recent_content,
        ];
    }

    /**
     * Reindex content
     *
     * @param int $batch_size Number of items to process per batch
     * @return array Processing results
     */
    public function reindex_content($batch_size = 50) {
        return $this->deduplicator->reindex_content($batch_size);
    }

    /**
     * Generate duplicate content report
     *
     * @param array $args Report parameters
     * @return array Report data
     */
    public function generate_duplicate_report($args = []) {
        return $this->deduplicator->generate_duplicate_report($args);
    }
} 