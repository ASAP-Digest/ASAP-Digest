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
     * Store a normalized content item as a WP post with ACF and taxonomy.
     * @param array $content
     * @return int|false WP Post ID or false on error
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
        $post_type = $this->post_type_map[$type] ?? 'post';
        $post_data = [
            'post_title'   => $content['title'],
            'post_content' => $this->should_store_full_content($content) ? ($content['content'] ?? '') : '',
            'post_excerpt' => $content['summary'] ?? '',
            'post_type'    => $post_type,
            'post_status'  => $this->determine_post_status($content),
            'post_date'    => $content['publish_date'] ?? current_time('mysql'),
        ];
        // Check for existing by source_url
        $existing_id = $this->find_by_source_url($content['source_url'] ?? '');
        if ($existing_id) {
            if (!$this->should_update($existing_id, $content)) {
                do_action('asap_digest_storage_skipped', 'no_significant_changes', $existing_id, $content);
                return $existing_id;
            }
            $post_data['ID'] = $existing_id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        if (is_wp_error($post_id)) {
            do_action('asap_digest_storage_error', 'wp_insert_error', $post_id->get_error_message(), $content);
            return false;
        }
        // Update ACF fields (stub)
        $this->update_selective_acf_fields($post_id, $content);
        // Set taxonomies (stub)
        $this->set_intelligent_taxonomies($post_id, $content);
        // Store minimal metadata
        update_post_meta($post_id, 'asap_source_url', $content['source_url'] ?? '');
        update_post_meta($post_id, 'asap_source_id', $content['source_id'] ?? '');
        update_post_meta($post_id, 'asap_ingestion_date', current_time('mysql'));
        // Update storage metrics (stub)
        $this->update_storage_metrics($content['source_id'] ?? 0, $type, strlen(maybe_serialize($content)));
        do_action('asap_digest_content_stored', $post_id, $content);
        return $post_id;
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