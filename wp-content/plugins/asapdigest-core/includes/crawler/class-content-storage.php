<?php
/**
 * Content Storage Class
 *
 * Handles storage and retrieval of processed content.
 *
 * @package ASAPDigest_Core
 * @created 05.17.25 | 12:15 PM PDT
 * @file-marker ContentStorage
 */

namespace AsapDigest\Crawler;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ContentStorage class for storing and retrieving processed content
 */
class ContentStorage {

    /**
     * Content table name
     *
     * @var string
     */
    private $content_table;

    /**
     * Content index table name
     *
     * @var string
     */
    private $index_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->content_table = $wpdb->prefix . 'asap_ingested_content';
        $this->index_table = $wpdb->prefix . 'asap_content_index';
    }

    /**
     * Store content in the database
     *
     * @param array $content_data Content data to store
     * @return int|false Content ID on success, false on failure
     */
    public function store($content_data) {
        global $wpdb;
        
        $now = current_time('mysql');
        
        // Prepare data for database
        $db_data = [
            'type' => isset($content_data['type']) ? sanitize_text_field($content_data['type']) : 'article',
            'title' => isset($content_data['title']) ? sanitize_text_field($content_data['title']) : '',
            'content' => isset($content_data['content']) ? $content_data['content'] : '',
            'summary' => isset($content_data['summary']) ? sanitize_text_field($content_data['summary']) : '',
            'source_url' => isset($content_data['source_url']) ? esc_url_raw($content_data['source_url']) : '',
            'source_id' => isset($content_data['source_id']) ? sanitize_text_field($content_data['source_id']) : '',
            'fingerprint' => isset($content_data['fingerprint']) ? sanitize_text_field($content_data['fingerprint']) : '',
            'quality_score' => isset($content_data['quality_score']) ? intval($content_data['quality_score']) : 0,
            'status' => isset($content_data['status']) ? sanitize_text_field($content_data['status']) : 'pending',
            'ingestion_date' => $now,
            'created_at' => $now,
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
        
        // Insert to database
        $result = $wpdb->insert($this->content_table, $db_data);
        
        if ($result === false) {
            $this->log_error('Error storing content: ' . $wpdb->last_error);
            return false;
        }
        
        $content_id = (int) $wpdb->insert_id;
        
        // Add to content index
        $index_result = $this->add_to_index($content_id, $db_data['fingerprint'], $db_data['quality_score']);
        
        if (!$index_result) {
            // If adding to index fails, attempt to delete the content record to maintain consistency
            $wpdb->delete($this->content_table, ['id' => $content_id]);
            $this->log_error('Error adding content to index for ID: ' . $content_id);
            return false;
        }
        
        // Trigger action for integrations
        do_action('asap_content_stored', $content_id, $content_data);
        
        return $content_id;
    }
    
    /**
     * Update existing content
     *
     * @param int $content_id Content ID to update
     * @param array $content_data Updated content data
     * @return int|false Content ID on success, false on failure
     */
    public function update($content_id, $content_data) {
        global $wpdb;
        
        // Verify content exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->content_table} WHERE id = %d",
            $content_id
        ));
        
        if (!$exists) {
            $this->log_error('Cannot update - content ID not found: ' . $content_id);
            return false;
        }
        
        $now = current_time('mysql');
        
        // Prepare data for database
        $db_data = [
            'updated_at' => $now,
        ];
        
        // Add optional fields if present
        $optional_fields = [
            'type', 'title', 'content', 'summary', 'source_url', 'source_id', 
            'fingerprint', 'quality_score', 'status'
        ];
        
        foreach ($optional_fields as $field) {
            if (isset($content_data[$field])) {
                if ($field === 'source_url') {
                    $db_data[$field] = esc_url_raw($content_data[$field]);
                } else {
                    $db_data[$field] = sanitize_text_field($content_data[$field]);
                }
            }
        }
        
        // Special handling for content which shouldn't be sanitized
        if (isset($content_data['content'])) {
            $db_data['content'] = $content_data['content'];
        }
        
        // Handle publish date
        if (isset($content_data['publish_date'])) {
            $db_data['publish_date'] = date('Y-m-d H:i:s', strtotime($content_data['publish_date']));
        }
        
        // Handle extra data as JSON
        if (isset($content_data['extra']) && is_array($content_data['extra'])) {
            $db_data['extra'] = json_encode($content_data['extra']);
        }
        
        // Update database
        $result = $wpdb->update(
            $this->content_table,
            $db_data,
            ['id' => $content_id]
        );
        
        if ($result === false) {
            $this->log_error('Error updating content: ' . $wpdb->last_error);
            return false;
        }
        
        // Update index if fingerprint or quality score changed
        if (isset($content_data['fingerprint']) || isset($content_data['quality_score'])) {
            $fingerprint = isset($content_data['fingerprint']) ? $content_data['fingerprint'] : null;
            $quality_score = isset($content_data['quality_score']) ? $content_data['quality_score'] : null;
            
            if ($fingerprint === null || $quality_score === null) {
                // Fetch current values for any missing fields
                $current = $wpdb->get_row($wpdb->prepare(
                    "SELECT fingerprint, quality_score FROM {$this->content_table} WHERE id = %d",
                    $content_id
                ));
                
                if ($current) {
                    if ($fingerprint === null) {
                        $fingerprint = $current->fingerprint;
                    }
                    if ($quality_score === null) {
                        $quality_score = $current->quality_score;
                    }
                }
            }
            
            $this->add_to_index($content_id, $fingerprint, $quality_score);
        }
        
        // Trigger action for integrations
        do_action('asap_content_updated', $content_id, $content_data);
        
        return $content_id;
    }
    
    /**
     * Get content by ID
     *
     * @param int $content_id Content ID
     * @return array|false Content data or false if not found
     */
    public function get($content_id) {
        global $wpdb;
        
        $content = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->content_table} WHERE id = %d",
            $content_id
        ), ARRAY_A);
        
        if (!$content) {
            return false;
        }
        
        // Parse JSON extra data if present
        if (!empty($content['extra']) && is_string($content['extra'])) {
            $extra = json_decode($content['extra'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $content['extra'] = $extra;
            }
        }
        
        return $content;
    }
    
    /**
     * Get multiple content items
     *
     * @param array $args Query arguments
     * @return array Content items
     */
    public function get_multiple($args = []) {
        global $wpdb;
        
        $defaults = [
            'type' => '',
            'status' => '',
            'source_id' => '',
            'offset' => 0,
            'limit' => 50,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => '',
            'min_quality' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $query = "SELECT * FROM {$this->content_table} WHERE 1=1";
        $query_args = [];
        
        // Add type filter
        if (!empty($args['type'])) {
            $query .= " AND type = %s";
            $query_args[] = $args['type'];
        }
        
        // Add status filter
        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $query_args[] = $args['status'];
        }
        
        // Add source_id filter
        if (!empty($args['source_id'])) {
            $query .= " AND source_id = %s";
            $query_args[] = $args['source_id'];
        }
        
        // Add quality filter
        if ((int)$args['min_quality'] > 0) {
            $query .= " AND quality_score >= %d";
            $query_args[] = (int)$args['min_quality'];
        }
        
        // Add search filter
        if (!empty($args['search'])) {
            $query .= " AND (title LIKE %s OR content LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Add ordering
        $allowed_order_fields = ['created_at', 'updated_at', 'title', 'quality_score', 'type', 'status'];
        $orderby = in_array($args['orderby'], $allowed_order_fields) ? $args['orderby'] : 'created_at';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        
        $query .= " ORDER BY {$orderby} {$order}";
        
        // Add pagination
        $offset = max(0, (int)$args['offset']);
        $limit = max(1, min(1000, (int)$args['limit']));
        
        $query .= " LIMIT %d, %d";
        $query_args[] = $offset;
        $query_args[] = $limit;
        
        // Execute query
        $items = $wpdb->get_results($wpdb->prepare($query, $query_args), ARRAY_A);
        
        // Process extra data
        foreach ($items as &$item) {
            if (!empty($item['extra']) && is_string($item['extra'])) {
                $extra = json_decode($item['extra'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $item['extra'] = $extra;
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Count content items matching criteria
     *
     * @param array $args Query arguments
     * @return int Count of items
     */
    public function count($args = []) {
        global $wpdb;
        
        $defaults = [
            'type' => '',
            'status' => '',
            'source_id' => '',
            'search' => '',
            'min_quality' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $query = "SELECT COUNT(*) FROM {$this->content_table} WHERE 1=1";
        $query_args = [];
        
        // Add type filter
        if (!empty($args['type'])) {
            $query .= " AND type = %s";
            $query_args[] = $args['type'];
        }
        
        // Add status filter
        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $query_args[] = $args['status'];
        }
        
        // Add source_id filter
        if (!empty($args['source_id'])) {
            $query .= " AND source_id = %s";
            $query_args[] = $args['source_id'];
        }
        
        // Add quality filter
        if ((int)$args['min_quality'] > 0) {
            $query .= " AND quality_score >= %d";
            $query_args[] = (int)$args['min_quality'];
        }
        
        // Add search filter
        if (!empty($args['search'])) {
            $query .= " AND (title LIKE %s OR content LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Execute query
        return (int) $wpdb->get_var($wpdb->prepare($query, $query_args));
    }
    
    /**
     * Delete content
     *
     * @param int $content_id Content ID
     * @return bool Success or failure
     */
    public function delete($content_id) {
        global $wpdb;
        
        // Get content before deletion (for hooks)
        $content = $this->get($content_id);
        
        if (!$content) {
            $this->log_error('Cannot delete - content ID not found: ' . $content_id);
            return false;
        }
        
        // First remove from index
        $this->remove_from_index($content_id);
        
        // Delete content
        $result = $wpdb->delete(
            $this->content_table,
            ['id' => $content_id]
        );
        
        if ($result === false) {
            $this->log_error('Error deleting content: ' . $wpdb->last_error);
            return false;
        }
        
        // Trigger action for integrations
        do_action('asap_content_deleted', $content_id, $content);
        
        return true;
    }
    
    /**
     * Add content to index
     *
     * @param int $content_id Content ID
     * @param string $fingerprint Content fingerprint
     * @param int $quality_score Content quality score
     * @return bool Success or failure
     */
    private function add_to_index($content_id, $fingerprint, $quality_score) {
        global $wpdb;
        
        // Check if already in index
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT content_id FROM {$this->index_table} WHERE content_id = %d",
            $content_id
        ));
        
        if ($exists) {
            // Update existing index entry
            $result = $wpdb->update(
                $this->index_table,
                [
                    'fingerprint' => $fingerprint,
                    'quality_score' => $quality_score,
                    'updated_at' => current_time('mysql'),
                ],
                ['content_id' => $content_id]
            );
        } else {
            // Insert new index entry
            $result = $wpdb->insert(
                $this->index_table,
                [
                    'content_id' => $content_id,
                    'fingerprint' => $fingerprint,
                    'quality_score' => $quality_score,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]
            );
        }
        
        if ($result === false) {
            $this->log_error('Error updating content index: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Remove content from index
     *
     * @param int $content_id Content ID
     * @return bool Success or failure
     */
    private function remove_from_index($content_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->index_table,
            ['content_id' => $content_id]
        );
        
        return $result !== false;
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message to log
     */
    private function log_error($message) {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('ASAP Content Storage: ' . $message);
        }
    }
} 