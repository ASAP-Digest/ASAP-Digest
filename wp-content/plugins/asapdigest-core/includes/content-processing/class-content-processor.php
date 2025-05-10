<?php
/**
 * Content Processor Class
 *
 * Central hub for content processing pipeline that ties together validation,
 * deduplication, and quality scoring.
 *
 * @package ASAP_Digest
 * @subpackage Content_Processing
 * @since 2.2.0
 * @file-marker ASAP_Digest_Content_Processor
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

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
        
        $this->validator = new ASAP_Digest_Content_Validator();
        $this->deduplicator = new ASAP_Digest_Content_Deduplicator();
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
            return $this->results;
        }
        
        // Step 2: Check for duplicates
        $fingerprint = $this->deduplicator->generate_fingerprint($content_data);
        $duplicate_id = $this->deduplicator->is_duplicate($fingerprint, $exclude_id);
        
        if ($duplicate_id) {
            $duplicate_details = $this->deduplicator->get_duplicate_details($duplicate_id);
            $this->results['errors']['duplicate'] = 'Content duplicate found (ID: ' . $duplicate_id . ')';
            $this->results['data']['duplicate'] = $duplicate_details;
            return $this->results;
        }
        
        // Step 3: Calculate quality score
        $quality_score = $this->validator->calculate_quality_score();
        
        // Compile final processed data
        $this->results['success'] = true;
        $this->results['data'] = [
            'content' => $content_data,
            'fingerprint' => $fingerprint,
            'quality_score' => $quality_score,
        ];
        
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
        
        // Check if we have valid processed data
        if (empty($processed_data) || empty($processed_data['data']['content'])) {
            $result['errors'][] = 'Invalid processed data provided';
            return $result;
        }
        
        $content_data = $processed_data['data']['content'];
        $fingerprint = $processed_data['data']['fingerprint'];
        $quality_score = $processed_data['data']['quality_score'];
        $now = current_time('mysql');
        
        // Prepare data for database
        $db_data = [
            'type' => sanitize_text_field($content_data['type']),
            'title' => sanitize_text_field($content_data['title']),
            'content' => wp_kses_post($content_data['content']),
            'summary' => isset($content_data['summary']) ? sanitize_text_field($content_data['summary']) : '',
            'source_url' => esc_url_raw($content_data['source_url']),
            'source_id' => isset($content_data['source_id']) ? sanitize_text_field($content_data['source_id']) : '',
            'publish_date' => isset($content_data['publish_date']) && $content_data['publish_date'] ? $content_data['publish_date'] : $now,
            'fingerprint' => $fingerprint,
            'quality_score' => $quality_score,
            'status' => isset($content_data['status']) ? sanitize_text_field($content_data['status']) : 'published',
            'updated_at' => $now,
        ];
        
        // Add extra JSON field if provided
        if (isset($content_data['extra']) && is_array($content_data['extra'])) {
            $db_data['extra'] = wp_json_encode($content_data['extra']);
        }
        
        $content_table = $wpdb->prefix . 'asap_ingested_content';
        
        // Update or insert
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
            $this->deduplicator->update_index($update_id, $fingerprint, $quality_score);
            
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
            $this->deduplicator->add_to_index($content_id, $fingerprint, $quality_score);
            
            $result['success'] = true;
            $result['content_id'] = $content_id;
            
            // Trigger create action
            do_action('asap_content_added', $content_id, $db_data);
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
} 