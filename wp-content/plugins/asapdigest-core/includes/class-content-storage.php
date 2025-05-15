<?php
/**
 * Content Storage Class
 * 
 * Handles storing ingested content into the database with deduplication and quality scoring.
 *
 * @package ASAPDigest_Core
 * @created 05.20.25 | 09:10 AM PDT
 * @file-marker ASAP_Digest_ContentStorage
 * @location /wp-content/plugins/asapdigest-core/includes/class-content-storage.php
 */

namespace ASAPDigest\Crawler;

/**
 * Class ContentStorage
 * 
 * Manages storage and retrieval of processed content in the database.
 *
 * @since 2.3.0
 */
class ContentStorage {
    /**
     * Mapping of content types to post types
     *
     * @var array
     */
    private $post_type_map;
    
    /**
     * Mapping of content fields to ACF fields
     *
     * @var array
     */
    private $acf_field_map;
    
    /**
     * Required fields for each content type
     *
     * @var array
     */
    private $required_fields;

    /**
     * Constructor
     */
    public function __construct() {
        $this->post_type_map = $this->get_post_type_mapping();
        $this->acf_field_map = $this->get_acf_field_mapping();
        $this->required_fields = $this->get_required_fields();
    }

    /**
     * Store a normalized content item in wp_asap_ingested_content
     *
     * Implements deduplication (by fingerprint) and quality scoring.
     *
     * @since 2.3.0
     * 
     * @param array $content The content item to store
     * @return int|false Ingested Content ID or false on error
     */
    public function store($content) {
        // Validate required fields
        $type = $content['type'] ?? 'article';
        foreach ($this->required_fields[$type] ?? [] as $field) {
            if (empty($content[$field])) {
                /**
                 * Fires when content storage encounters a missing required field error
                 *
                 * @since 2.3.0
                 *
                 * @param string $field   The name of the missing field
                 * @param array  $content The content item that failed validation
                 */
                do_action('asapdigest_storage_error', 'missing_required_field', $field, $content);
                return false;
            }
        }
        
        // Generate fingerprint for deduplication
        $fingerprint = $this->generate_fingerprint($content);
        
        // Check for existing by fingerprint in wp_asap_content_index
        $existing_id = $this->find_by_fingerprint($fingerprint);
        if ($existing_id) {
            /**
             * Fires when content is skipped due to duplicate fingerprint
             *
             * @since 2.3.0
             *
             * @param int   $existing_id The ID of the existing content with the same fingerprint
             * @param array $content     The content item that was skipped
             */
            do_action('asapdigest_storage_skipped', 'duplicate_fingerprint', $existing_id, $content);
            return $existing_id;
        }
        
        // Calculate quality score
        $quality_score = $this->calculate_quality_score($content);
        
        // Insert into wp_asap_ingested_content
        global $wpdb;
        $ingested_table = $wpdb->prefix . 'asap_ingested_content';
        $now = current_time('mysql');
        $insert_data = [
            'type' => $type,
            'title' => $content['title'],
            'content' => $this->should_store_full_content($content) ? ($content['content'] ?? '') : '',
            'summary' => $content['summary'] ?? '',
            'source_url' => $content['source_url'] ?? '',
            'source_id' => $content['source_id'] ?? '',
            'publish_date' => $content['publish_date'] ?? $now,
            'ingestion_date' => $now,
            'fingerprint' => $fingerprint,
            'quality_score' => $quality_score,
            'status' => $content['status'] ?? 'published',
            'extra' => isset($content['extra']) ? wp_json_encode($content['extra']) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $result = $wpdb->insert($ingested_table, $insert_data);
        if (!$result) {
            /**
             * Fires when content insertion into the database fails
             *
             * @since 2.3.0
             *
             * @param string $error   The database error message
             * @param array  $content The content item that failed to be inserted
             */
            do_action('asapdigest_storage_error', 'ingested_content_insert_error', $wpdb->last_error, $content);
            return false;
        }
        $ingested_id = intval($wpdb->insert_id);
        
        // Insert into wp_asap_content_index for deduplication and scoring
        $index_table = $wpdb->prefix . 'asap_content_index';
        $wpdb->insert($index_table, [
            'ingested_content_id' => $ingested_id,
            'fingerprint' => $fingerprint,
            'quality_score' => $quality_score,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // Error handling for index insert
        if ($wpdb->last_error) {
            /**
             * Fires when content index insertion fails
             *
             * @since 2.3.0
             *
             * @param string $error       The database error message
             * @param int    $ingested_id The ID of the ingested content
             * @param array  $content     The content item
             */
            do_action('asapdigest_storage_error', 'index_insert_error', $wpdb->last_error, $ingested_id, $content);
        }
        
        // Update storage metrics
        $this->update_storage_metrics($content['source_id'] ?? 0, $type, strlen(maybe_serialize($content)));
        
        /**
         * Fires when content is successfully stored in the database
         *
         * @since 2.3.0
         *
         * @param int   $ingested_id The ID of the stored content
         * @param array $content     The content item that was stored
         */
        do_action('asapdigest_content_stored', $ingested_id, $content);
        
        return $ingested_id;
    }

    /**
     * Generate a SHA256 fingerprint for deduplication
     *
     * @since 2.3.0
     * 
     * @param array $content The content item
     * @return string SHA256 hash fingerprint
     */
    private function generate_fingerprint($content) {
        $fields = [
            strtolower(trim($content['title'] ?? '')),
            strtolower(trim($content['content'] ?? '')),
            strtolower(trim($content['source_url'] ?? '')),
            strtolower(trim($content['publish_date'] ?? '')),
            strtolower(trim($content['source_id'] ?? '')),
        ];
        $canonical = implode('||', $fields);
        return hash('sha256', $canonical);
    }

    /**
     * Find existing ingested content by fingerprint
     *
     * @since 2.3.0
     * 
     * @param string $fingerprint SHA256 hash fingerprint
     * @return int|false Ingested Content ID or false
     */
    private function find_by_fingerprint($fingerprint) {
        global $wpdb;
        $index_table = $wpdb->prefix . 'asap_content_index';
        $sql = $wpdb->prepare("SELECT ingested_content_id FROM {$index_table} WHERE fingerprint = %s LIMIT 1", $fingerprint);
        $id = $wpdb->get_var($sql);
        return $id ? intval($id) : false;
    }

    /**
     * Calculate a quality score for the content (0-100)
     *
     * @since 2.3.0
     * 
     * @param array $content The content item
     * @return int Quality score (0-100)
     */
    private function calculate_quality_score($content) {
        $completeness = (!empty($content['title']) && !empty($content['content']) && !empty($content['summary'])) ? 1 : 0.5;
        $recency = (isset($content['publish_date']) && strtotime($content['publish_date']) > strtotime('-7 days')) ? 1 : 0.5;
        $length = (isset($content['content']) && strlen($content['content']) > 500) ? 1 : 0.5;
        $score = 0.4 * 1 + 0.3 * $completeness + 0.2 * $recency + 0.1 * $length;
        
        /**
         * Filters the calculated quality score for content
         *
         * @since 2.3.0
         *
         * @param int   $score   The calculated quality score (0-100)
         * @param array $content The content item being scored
         */
        $score = apply_filters('asapdigest_content_quality_score', round($score * 100), $content);
        
        return $score;
    }

    // --- Helper methods ---
    
    /**
     * Get mapping of content types to post types
     *
     * @return array
     */
    private function get_post_type_mapping() { 
        return [
            'article' => 'asap_article',
            'podcast' => 'asap_podcast',
            'financial' => 'asap_financial',
            'xpost' => 'asap_xpost',
            'reddit' => 'asap_reddit',
            'event' => 'asap_event',
            'polymarket' => 'asap_polymarket',
            'keyterm' => 'asap_keyterm',
        ]; 
    }
    
    /**
     * Get mapping of content fields to ACF fields
     *
     * @return array
     */
    private function get_acf_field_mapping() { 
        return []; 
    }
    
    /**
     * Get required fields for each content type
     *
     * @return array
     */
    private function get_required_fields() { 
        return [
            'article' => ['title', 'content', 'source_url'],
            'podcast' => ['title', 'content', 'source_url'],
            'financial' => ['title', 'content', 'source_url'],
            'xpost' => ['title', 'content', 'source_url'],
            'reddit' => ['title', 'content', 'source_url'],
            'event' => ['title', 'content', 'source_url'],
            'polymarket' => ['title', 'content', 'source_url'],
            'keyterm' => ['title', 'content', 'source_url'],
        ]; 
    }
    
    /**
     * Determine if full content should be stored
     *
     * @param array $content The content item
     * @return bool
     */
    private function should_store_full_content($content) { 
        return true; 
    }
    
    /**
     * Determine post status for content
     *
     * @param array $content The content item
     * @return string WordPress post status
     */
    private function determine_post_status($content) { 
        return 'publish'; 
    }
    
    /**
     * Update selective ACF fields for a post
     *
     * @param int   $post_id The post ID
     * @param array $content The content item
     */
    private function update_selective_acf_fields($post_id, $content) { 
        /* stub */ 
    }
    
    /**
     * Set intelligent taxonomies for a post
     *
     * @param int   $post_id The post ID
     * @param array $content The content item
     */
    private function set_intelligent_taxonomies($post_id, $content) { 
        /* stub */ 
    }
    
    /**
     * Update storage metrics
     *
     * @param int    $source_id The source ID
     * @param string $type      The content type
     * @param int    $size      The content size in bytes
     */
    private function update_storage_metrics($source_id, $type, $size) { 
        /* stub */ 
    }
    
    /**
     * Find content by source URL
     *
     * @param string $url The source URL
     * @return int|false Post ID or false
     */
    private function find_by_source_url($url) { 
        return false; 
    }
    
    /**
     * Determine if existing content should be updated
     *
     * @param int   $existing_id The existing content ID
     * @param array $content     The new content item
     * @return bool
     */
    private function should_update($existing_id, $content) { 
        return true; 
    }
} 