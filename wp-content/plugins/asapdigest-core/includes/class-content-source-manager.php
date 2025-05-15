<?php
/**
 * @file-marker ASAP_Digest_ContentSourceManager
 * @location /wp-content/plugins/asapdigest-core/includes/class-content-source-manager.php
 */

namespace ASAPDigest\Crawler;

/**
 * Manages content sources for the ingestion system: loading, scheduling, and updating.
 */
class ContentSourceManager {
    private $db;
    private $sources = [];
    
    public function __construct($db = null) {
        global $wpdb;
        $this->db = $db ?: $wpdb;
        $this->sources = $this->load_sources();
    }
    
    /**
     * Load all active sources from the database.
     * @return array
     */
    public function load_sources() {
        $table = $this->db->prefix . 'asap_content_sources';
        return $this->db->get_results("SELECT * FROM {$table} WHERE active = 1");
    }
    
    /**
     * Get sources that are due for fetching based on last_fetch and fetch_interval.
     * @return array
     */
    public function get_due_sources() {
        $now = time();
        return array_filter($this->sources, function($source) use ($now) {
            return $now >= ($source->last_fetch + $source->fetch_interval);
        });
    }
    
    /**
     * Update the status and fetch interval of a source after a fetch attempt.
     * @param int $source_id
     * @param bool $success
     * @param array $stats
     * @return int|false Rows affected or false
     */
    public function update_source_status($source_id, $success, $stats = []) {
        $table = $this->db->prefix . 'asap_content_sources';
        $new_interval = $this->calculate_optimal_interval(
            $source_id, 
            $stats['items_found'] ?? 0,
            $stats['new_items'] ?? 0
        );
        $fetch_count = (int)$this->db->get_var($this->db->prepare(
            "SELECT fetch_count FROM {$table} WHERE id = %d",
            $source_id
        )) + 1;
        return $this->db->update(
            $table,
            [
                'last_fetch' => time(),
                'last_status' => $success ? 'success' : 'failed',
                'fetch_interval' => $new_interval,
                'fetch_count' => $fetch_count
            ],
            ['id' => $source_id]
        );
    }
    
    /**
     * Calculate the optimal fetch interval for a source based on recent activity.
     * @param int $source_id
     * @param int $items_found
     * @param int $new_items
     * @return int New fetch interval in seconds
     */
    private function calculate_optimal_interval($source_id, $items_found, $new_items) {
        $source = $this->get_source($source_id);
        $current_interval = $source->fetch_interval;
        $min_interval = $source->min_interval;
        $max_interval = $source->max_interval;
        // If no new content, gradually increase interval (up to max_interval)
        if ($new_items == 0 && $items_found > 0) {
            return min((int)($current_interval * 1.5), $max_interval);
        }
        // If substantial new content, decrease interval (down to min_interval)
        if ($new_items > 5) {
            return max((int)($current_interval * 0.8), $min_interval);
        }
        return $current_interval;
    }
    
    /**
     * Get a single source by ID.
     * @param int $source_id
     * @return object|null
     */
    public function get_source($source_id) {
        $table = $this->db->prefix . 'asap_content_sources';
        return $this->db->get_row($this->db->prepare("SELECT * FROM {$table} WHERE id = %d", $source_id));
    }
} 