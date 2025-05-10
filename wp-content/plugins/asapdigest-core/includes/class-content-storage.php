<?php
/**
 * @file-marker ASAP_Digest_ContentStorage
 * @location /wp-content/plugins/asapdigest-core/includes/class-content-storage.php
 */

namespace AsapDigest\Crawler;

class ContentStorage {
    private $post_type_map;
    private $acf_field_map;
    private $required_fields;

    public function __construct() {
        $this->post_type_map = $this->get_post_type_mapping();
        $this->acf_field_map = $this->get_acf_field_mapping();
        $this->required_fields = $this->get_required_fields();
    }

    /**
     * Store a normalized content item in wp_asap_ingested_content (not wp_posts).
     * Implements deduplication (by fingerprint) and quality scoring.
     * @param array $content
     * @return int|false Ingested Content ID or false on error
     */
    public function store($content) {
        // Validate required fields
        $type = $content['type'] ?? 'article';
        foreach ($this->required_fields[$type] ?? [] as $field) {
            if (empty($content[$field])) {
                do_action('asap_digest_storage_error', 'missing_required_field', $field, $content);
                return false;
            }
        }
        // --- [ Generate fingerprint for deduplication ] ---
        $fingerprint = $this->generate_fingerprint($content);
        // --- [ Check for existing by fingerprint in wp_asap_content_index ] ---
        $existing_id = $this->find_by_fingerprint($fingerprint);
        if ($existing_id) {
            do_action('asap_digest_storage_skipped', 'duplicate_fingerprint', $existing_id, $content);
            return $existing_id;
        }
        // --- [ Calculate quality score ] ---
        $quality_score = $this->calculate_quality_score($content);
        // --- [ Insert into wp_asap_ingested_content ] ---
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
            do_action('asap_digest_storage_error', 'ingested_content_insert_error', $wpdb->last_error, $content);
            return false;
        }
        $ingested_id = intval($wpdb->insert_id);
        // --- [ Insert into wp_asap_content_index for deduplication and scoring ] ---
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
            do_action('asap_digest_storage_error', 'index_insert_error', $wpdb->last_error, $ingested_id, $content);
        }
        // Update storage metrics (stub)
        $this->update_storage_metrics($content['source_id'] ?? 0, $type, strlen(maybe_serialize($content)));
        do_action('asap_digest_content_stored', $ingested_id, $content);
        return $ingested_id;
    }

    /**
     * Generate a SHA256 fingerprint for deduplication.
     * @param array $content
     * @return string
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
     * Find existing ingested content by fingerprint (uses wp_asap_content_index).
     * @param string $fingerprint
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
     * Calculate a quality score for the content (0-100).
     * @param array $content
     * @return int
     */
    private function calculate_quality_score($content) {
        $completeness = (!empty($content['title']) && !empty($content['content']) && !empty($content['summary'])) ? 1 : 0.5;
        $recency = (isset($content['publish_date']) && strtotime($content['publish_date']) > strtotime('-7 days')) ? 1 : 0.5;
        $length = (isset($content['content']) && strlen($content['content']) > 500) ? 1 : 0.5;
        $score = 0.4 * 1 + 0.3 * $completeness + 0.2 * $recency + 0.1 * $length;
        return round($score * 100);
    }

    // --- Helper methods (stubs) ---
    private function get_post_type_mapping() { return [
        'article' => 'asap_article',
        'podcast' => 'asap_podcast',
        'financial' => 'asap_financial',
        'xpost' => 'asap_xpost',
        'reddit' => 'asap_reddit',
        'event' => 'asap_event',
        'polymarket' => 'asap_polymarket',
        'keyterm' => 'asap_keyterm',
    ]; }
    private function get_acf_field_mapping() { return []; }
    private function get_required_fields() { return [
        'article' => ['title', 'content', 'source_url'],
        'podcast' => ['title', 'content', 'source_url'],
        'financial' => ['title', 'content', 'source_url'],
        'xpost' => ['title', 'content', 'source_url'],
        'reddit' => ['title', 'content', 'source_url'],
        'event' => ['title', 'content', 'source_url'],
        'polymarket' => ['title', 'content', 'source_url'],
        'keyterm' => ['title', 'content', 'source_url'],
    ]; }
    private function should_store_full_content($content) { return true; }
    private function determine_post_status($content) { return 'publish'; }
    private function update_selective_acf_fields($post_id, $content) { /* stub */ }
    private function set_intelligent_taxonomies($post_id, $content) { /* stub */ }
    private function update_storage_metrics($source_id, $type, $size) { /* stub */ }
    private function find_by_source_url($url) { return false; }
    private function should_update($existing_id, $content) { return true; }
} 