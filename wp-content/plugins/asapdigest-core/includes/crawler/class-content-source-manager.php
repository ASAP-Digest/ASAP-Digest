<?php
/**
 * @file-marker ASAP_Digest_ContentSourceManager
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/class-content-source-manager.php
 */

namespace AsapDigest\Crawler;

/**
 * Manages content sources for the crawler.
 * Handles source CRUD operations, schedules, and database operations.
 */
class ContentSourceManager {
    /**
     * Single instance of the class
     *
     * @var ContentSourceManager
     */
    private static $instance = null;
    
    /**
     * @var string Sources table name
     */
    private $sources_table;
    
    /**
     * @var string Source metrics table name
     */
    private $metrics_table;
    
    /**
     * @var string Crawler metrics table name
     */
    private $crawler_metrics_table;
    
    /**
     * @var string Crawler errors table name
     */
    private $errors_table;
    
    /**
     * Get the singleton instance
     *
     * @return ContentSourceManager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->sources_table = $wpdb->prefix . 'asap_content_sources';
        $this->metrics_table = $wpdb->prefix . 'asap_source_metrics';
        $this->crawler_metrics_table = $wpdb->prefix . 'asap_crawler_metrics';
        $this->errors_table = $wpdb->prefix . 'asap_crawler_errors';
        
        // Register hooks for table creation
        add_action('plugins_loaded', [$this, 'create_tables']);
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sources table
        $sources_sql = "CREATE TABLE {$this->sources_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            type varchar(50) NOT NULL,
            url varchar(255) NOT NULL,
            config longtext,
            content_types text,
            active tinyint(1) NOT NULL DEFAULT 1,
            fetch_interval int(11) NOT NULL DEFAULT 3600,
            min_interval int(11) NOT NULL DEFAULT 1800,
            max_interval int(11) NOT NULL DEFAULT 86400,
            last_fetch datetime DEFAULT NULL,
            next_fetch datetime DEFAULT NULL,
            quota_max_items int(11) DEFAULT NULL,
            quota_max_size int(11) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY type (type),
            KEY active (active),
            KEY next_fetch (next_fetch)
        ) $charset_collate;";
        
        dbDelta($sources_sql);
        
        // Source metrics table
        $metrics_sql = "CREATE TABLE {$this->metrics_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_id bigint(20) UNSIGNED NOT NULL,
            items_found int(11) NOT NULL DEFAULT 0,
            items_processed int(11) NOT NULL DEFAULT 0,
            errors int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY source_id (source_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($metrics_sql);
        
        // Crawler metrics table for overall performance
        $crawler_metrics_sql = "CREATE TABLE {$this->crawler_metrics_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            run_date datetime NOT NULL,
            sources_processed int(11) NOT NULL DEFAULT 0,
            items_found int(11) NOT NULL DEFAULT 0,
            items_processed int(11) NOT NULL DEFAULT 0,
            errors int(11) NOT NULL DEFAULT 0,
            duration_seconds int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY run_date (run_date)
        ) $charset_collate;";
        
        dbDelta($crawler_metrics_sql);
        
        // Crawler errors table
        $errors_sql = "CREATE TABLE {$this->errors_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_id bigint(20) UNSIGNED NOT NULL,
            error_type varchar(50) NOT NULL,
            message text NOT NULL,
            context longtext,
            severity varchar(20) NOT NULL DEFAULT 'error',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY source_id (source_id),
            KEY error_type (error_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($errors_sql);
    }
    
    /**
     * Get all sources
     * 
     * @param bool $active_only Whether to return only active sources
     * @return array Sources
     */
    public function load_sources($active_only = false) {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->sources_table}";
        
        if ($active_only) {
            $query .= " WHERE active = 1";
        }
        
        $query .= " ORDER BY name ASC";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get a single source by ID
     * 
     * @param int $id Source ID
     * @return object|null Source or null if not found
     */
    public function get_source($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->sources_table} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Add a new content source
     * 
     * @param array $data Source data
     * @return int|false Source ID on success, false on failure
     */
    public function add_source($data) {
        global $wpdb;
        
        $defaults = [
            'name' => '',
            'type' => '',
            'url' => '',
            'config' => [],
            'content_types' => [],
            'active' => 1,
            'fetch_interval' => 3600,
            'min_interval' => 1800,
            'max_interval' => 86400,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $source_data = wp_parse_args($data, $defaults);
        
        // Serialize arrays
        if (is_array($source_data['config'])) {
            $source_data['config'] = maybe_serialize($source_data['config']);
        }
        
        if (is_array($source_data['content_types'])) {
            $source_data['content_types'] = maybe_serialize($source_data['content_types']);
        }
        
        // Set next fetch time
        $source_data['next_fetch'] = date('Y-m-d H:i:s', time() + $source_data['fetch_interval']);
        
        $result = $wpdb->insert($this->sources_table, $source_data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update an existing content source
     * 
     * @param int $id Source ID
     * @param array $data Source data
     * @return bool Success
     */
    public function update_source($id, $data) {
        global $wpdb;
        
        // Get current source data
        $current = $this->get_source($id);
        if (!$current) {
            return false;
        }
        
        // Update data
        $update_data = array_merge((array)$current, $data);
        
        // Handle arrays
        if (isset($update_data['config']) && is_array($update_data['config'])) {
            $update_data['config'] = maybe_serialize($update_data['config']);
        }
        
        if (isset($update_data['content_types']) && is_array($update_data['content_types'])) {
            $update_data['content_types'] = maybe_serialize($update_data['content_types']);
        }
        
        // Set updated timestamp
        $update_data['updated_at'] = current_time('mysql');
        
        // If fetch interval changed, update next_fetch
        if (isset($data['fetch_interval']) && $data['fetch_interval'] != $current->fetch_interval) {
            $update_data['next_fetch'] = date('Y-m-d H:i:s', time() + $data['fetch_interval']);
        }
        
        $result = $wpdb->update(
            $this->sources_table,
            $update_data,
            ['id' => $id]
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a content source
     * 
     * @param int $id Source ID
     * @return bool Success
     */
    public function delete_source($id) {
        global $wpdb;
        
        return $wpdb->delete($this->sources_table, ['id' => $id], ['%d']) !== false;
    }
    
    /**
     * Get sources that are due for crawling
     * 
     * @param int $limit Maximum number of sources to return
     * @return array Sources due for crawling
     */
    public function get_due_sources($limit = 50) {
        global $wpdb;
        
        // Get active sources where next_fetch is in the past
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->sources_table} 
            WHERE active = 1 
            AND (next_fetch IS NULL OR next_fetch <= %s)
            ORDER BY next_fetch ASC
            LIMIT %d",
            current_time('mysql'),
            $limit
        ));
    }
    
    /**
     * Update source status after crawling
     * 
     * @param int $id Source ID
     * @param bool $success Whether the crawl was successful
     * @param array $metrics Optional crawl metrics
     * @return bool Update success
     */
    public function update_source_status($id, $success, $metrics = []) {
        global $wpdb;
        
        // Get current source
        $source = $this->get_source($id);
        if (!$source) {
            return false;
        }
        
        // Calculate next fetch time with adaptive scheduling
        $interval = $source->fetch_interval;
        
        // Adjust interval based on success and metrics
        if ($success) {
            // If successful and found items, possibly decrease interval (more frequent)
            if (!empty($metrics['new_items']) && $metrics['new_items'] > 5) {
                // Many new items, crawl more frequently
                $interval = max($source->min_interval, $interval * 0.8);
            } elseif (empty($metrics['new_items']) || $metrics['new_items'] == 0) {
                // No new items, crawl less frequently
                $interval = min($source->max_interval, $interval * 1.2);
            }
        } else {
            // If failed, increase interval (less frequent)
            $interval = min($source->max_interval, $interval * 1.5);
        }
        
        // Update source
        $update_data = [
            'last_fetch' => current_time('mysql'),
            'next_fetch' => date('Y-m-d H:i:s', time() + $interval),
            'fetch_interval' => $interval,
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->update(
            $this->sources_table,
            $update_data,
            ['id' => $id]
        );
        
        return $result !== false;
    }
    
    /**
     * Get performance metrics for a source
     * 
     * @param int $id Source ID
     * @param int $days Number of days to look back
     * @return array Performance metrics
     */
    public function get_source_metrics($id, $days = 30) {
        global $wpdb;
        
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->metrics_table}
            WHERE source_id = %d
            AND created_at >= %s
            ORDER BY created_at DESC",
            $id,
            $start_date
        ));
    }
    
    /**
     * Get error history for a source
     * 
     * @param int $id Source ID
     * @param int $limit Maximum number of errors to return
     * @return array Error history
     */
    public function get_source_errors($id, $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->errors_table}
            WHERE source_id = %d
            ORDER BY created_at DESC
            LIMIT %d",
            $id,
            $limit
        ));
    }
    
    /**
     * Get overall crawler metrics
     * 
     * @param int $days Number of days to look back
     * @return array Crawler metrics
     */
    public function get_crawler_metrics($days = 30) {
        global $wpdb;
        
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->crawler_metrics_table}
            WHERE created_at >= %s
            ORDER BY created_at DESC",
            $start_date
        ));
    }
    
    /**
     * Get supported source types
     *
     * @return array Array of supported source types
     */
    public function get_supported_source_types() {
        return [
            'rss' => __('RSS Feed', 'asapdigest-core'),
            'api' => __('API Endpoint', 'asapdigest-core'),
            'scraper' => __('Web Scraper', 'asapdigest-core'),
            'webhook' => __('Webhook', 'asapdigest-core'),
        ];
    }
} 