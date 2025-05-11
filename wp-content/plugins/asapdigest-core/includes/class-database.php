<?php
/**
 * ASAP Digest Database Handler
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Database
 */

namespace ASAPDigest\Core;

use Exception;
use WP_Error;
use function get_option;
use function update_option;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Database {
    /**
     * @var string Option name for digest settings
     */
    private $settings_option = 'asap_digest_settings';

    /**
     * @var string Option name for digest stats
     */
    private $stats_option = 'asap_digest_stats';

    /**
     * @var array List of plugin tables
     */
    private $tables = [
        'asap_digests',
        'asap_notifications',
        'asap_usage_metrics',
        'asap_service_costs',
        'asap_content_sources',
        'asap_source_metrics',
        'asap_storage_metrics',
        'asap_crawler_errors',
        'asap_moderation_log',
        'asap_content_index',
        'asap_ingested_content',
        'asap_duplicate_log',
        'asap_activity_log',
        'asap_moods',
        'asap_revisits',
        'asap_progress',
        'asap_performance',
        'asap_sync',
        'asap_sms_prefs',
        'asap_sms_digests',
        'ba_wp_user_map'
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the class
     */
    private function init() {
        // Set default settings if not exist
        if (!get_option($this->settings_option)) {
            $this->set_default_settings();
        }

        // Initialize stats if not exist
        if (!get_option($this->stats_option)) {
            $this->init_stats();
        }
    }

    /**
     * Set default digest settings
     */
    private function set_default_settings() {
        $defaults = [
            'frequency' => 'weekly',
            'send_time' => '09:00',
            'categories' => [],
            'max_posts' => 10
        ];

        update_option($this->settings_option, $defaults);
    }

    /**
     * Initialize digest stats
     */
    private function init_stats() {
        $initial_stats = [
            'total_digests_sent' => 0,
            'total_posts_included' => 0,
            'last_digest_date' => null,
            'next_digest_date' => null
        ];

        update_option($this->stats_option, $initial_stats);
    }

    /**
     * Get digest settings
     *
     * @return array|WP_Error Settings array or error
     */
    public function get_digest_settings() {
        $settings = get_option($this->settings_option);
        
        if (!$settings) {
            return new WP_Error(
                'settings_not_found',
                __('Could not retrieve digest settings.', 'asap-digest')
            );
        }

        return $settings;
    }

    /**
     * Update digest settings
     *
     * @param array $settings New settings
     * @return array|WP_Error Updated settings or error
     */
    public function update_digest_settings($settings) {
        $current = get_option($this->settings_option);
        
        if (!$current) {
            return new WP_Error(
                'settings_not_found',
                __('Could not retrieve current settings.', 'asap-digest')
            );
        }

        $updated = array_merge($current, $settings);
        
        if (!update_option($this->settings_option, $updated)) {
            return new WP_Error(
                'settings_update_failed',
                __('Could not update settings.', 'asap-digest')
            );
        }

        return $updated;
    }

    /**
     * Get digest stats
     *
     * @return array|WP_Error Stats array or error
     */
    public function get_digest_stats() {
        $stats = get_option($this->stats_option);
        
        if (!$stats) {
            return new WP_Error(
                'stats_not_found',
                __('Could not retrieve digest stats.', 'asap-digest')
            );
        }

        return $stats;
    }

    /**
     * Update digest stats
     *
     * @param array $stats New stats
     * @return array|WP_Error Updated stats or error
     */
    public function update_digest_stats($stats) {
        $current = get_option($this->stats_option);
        
        if (!$current) {
            return new WP_Error(
                'stats_not_found',
                __('Could not retrieve current stats.', 'asap-digest')
            );
        }

        $updated = array_merge($current, $stats);
        
        if (!update_option($this->stats_option, $updated)) {
            return new WP_Error(
                'stats_update_failed',
                __('Could not update stats.', 'asap-digest')
            );
        }

        return $updated;
    }

    /**
     * Create plugin database tables
     * 
     * @return bool True on success, false on failure
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Enable error logging
        $wpdb->show_errors();
        ob_start();

        try {
            // ASAP Digests Table
            $sql_digests = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_digests (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                content LONGTEXT NOT NULL,
                podcast_url TEXT DEFAULT NULL,
                sentiment_score VARCHAR(20) DEFAULT NULL,
                life_moment TEXT DEFAULT NULL,
                is_saved BOOLEAN DEFAULT FALSE,
                reminders TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_created_at (user_id, created_at),
                FULLTEXT (content)
            ) $charset_collate;";
            
            dbDelta($sql_digests);

            // ASAP Notifications Table
            $sql_notifications = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_notifications (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                endpoint TEXT NOT NULL,
                p256dh TEXT NOT NULL,
                auth TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY endpoint (endpoint(190))
            ) $charset_collate;";
            
            dbDelta($sql_notifications);

            // Content Sources Table
            $sql_content_sources = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_content_sources (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                type varchar(50) NOT NULL,
                url text NOT NULL,
                config longtext NOT NULL,
                content_types text NOT NULL,
                active tinyint(1) NOT NULL DEFAULT 1,
                last_fetch bigint(20) UNSIGNED DEFAULT NULL,
                last_status varchar(50) DEFAULT NULL,
                fetch_interval int(11) NOT NULL DEFAULT 3600,
                min_interval int(11) NOT NULL DEFAULT 1800,
                max_interval int(11) NOT NULL DEFAULT 86400,
                fetch_count int(11) NOT NULL DEFAULT 0,
                quota_max_items int(11) DEFAULT NULL,
                quota_max_size bigint(20) UNSIGNED DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY type_active (type, active),
                KEY last_fetch (last_fetch)
            ) $charset_collate;";
            
            dbDelta($sql_content_sources);

            // Source Metrics Table
            $sql_source_metrics = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_source_metrics (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                source_id bigint(20) UNSIGNED NOT NULL,
                date date NOT NULL,
                items_found int(11) NOT NULL DEFAULT 0,
                items_stored int(11) NOT NULL DEFAULT 0,
                items_rejected int(11) NOT NULL DEFAULT 0,
                processing_time float NOT NULL DEFAULT 0,
                error_count int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY source_date (source_id, date),
                KEY date (date)
            ) $charset_collate;";
            
            dbDelta($sql_source_metrics);

            // Storage Metrics Table
            $sql_storage_metrics = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_storage_metrics (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                source_id bigint(20) UNSIGNED NOT NULL,
                content_type varchar(50) NOT NULL,
                date date NOT NULL,
                item_count int(11) NOT NULL DEFAULT 0,
                total_size bigint(20) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY source_type_date (source_id, content_type, date),
                KEY date (date)
            ) $charset_collate;";
            
            dbDelta($sql_storage_metrics);

            // Crawler Errors Table
            $sql_crawler_errors = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_crawler_errors (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                source_id bigint(20) UNSIGNED DEFAULT NULL,
                error_type varchar(50) NOT NULL,
                message text NOT NULL,
                context longtext DEFAULT NULL,
                severity varchar(20) NOT NULL DEFAULT 'error',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY source_id (source_id),
                KEY error_type (error_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_crawler_errors);

            // Moderation Log Table
            $sql_moderation_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_moderation_log (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                content_id bigint(20) UNSIGNED NOT NULL,
                moderator_id bigint(20) UNSIGNED DEFAULT NULL,
                action varchar(50) NOT NULL,
                reason text DEFAULT NULL,
                notes text DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY content_id (content_id),
                KEY moderator_id (moderator_id),
                KEY action (action),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_moderation_log);

            // Ingested Content Table
            $sql_ingested_content = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_ingested_content (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                type varchar(32) NOT NULL DEFAULT 'article',
                title text NOT NULL,
                content longtext NOT NULL,
                summary text DEFAULT NULL,
                source_url text NOT NULL,
                source_id varchar(128) DEFAULT NULL,
                publish_date datetime DEFAULT NULL,
                ingestion_date datetime NOT NULL,
                fingerprint varchar(64) NOT NULL,
                quality_score tinyint UNSIGNED DEFAULT NULL,
                status varchar(32) NOT NULL DEFAULT 'pending',
                extra JSON DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY fingerprint (fingerprint),
                KEY type (type(32)),
                KEY source_id (source_id(128)),
                KEY publish_date (publish_date),
                KEY status (status(32))
            ) $charset_collate;";
            
            dbDelta($sql_ingested_content);

            // Content Index Table
            $sql_content_index = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_content_index (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ingested_content_id bigint(20) UNSIGNED NOT NULL,
                fingerprint varchar(64) NOT NULL,
                quality_score tinyint UNSIGNED DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                post_id bigint(20) UNSIGNED DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY fingerprint (fingerprint),
                UNIQUE KEY ingested_content_id (ingested_content_id),
                KEY post_id (post_id)
            ) $charset_collate;";
            
            dbDelta($sql_content_index);

            // Duplicate Log Table
            $sql_duplicate_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_duplicate_log (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                content_id bigint(20) UNSIGNED NOT NULL,
                duplicate_id bigint(20) UNSIGNED NOT NULL,
                fingerprint varchar(64) NOT NULL,
                resolution varchar(20) DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                resolved_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY content_id (content_id),
                KEY duplicate_id (duplicate_id),
                KEY fingerprint (fingerprint),
                KEY resolution (resolution)
            ) $charset_collate;";
            
            dbDelta($sql_duplicate_log);

            // Activity Log Table
            $sql_activity_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_activity_log (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                action_type varchar(50) NOT NULL,
                object_id bigint(20) UNSIGNED DEFAULT NULL,
                object_type varchar(50) DEFAULT NULL,
                details text DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY action_type (action_type),
                KEY object_id (object_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_activity_log);

            // Moods Table
            $sql_moods = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_moods (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                digest_id bigint(20) UNSIGNED NOT NULL,
                mood ENUM('happy','neutral','stressed','curious','surprised') NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY digest_id (digest_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_moods);

            // Revisits Table (Time Machine)
            $sql_revisits = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_revisits (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                digest_id bigint(20) UNSIGNED NOT NULL,
                revisit_date datetime NOT NULL,
                notification_sent boolean DEFAULT FALSE,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY user_digest (user_id, digest_id),
                KEY revisit_notif (revisit_date, notification_sent)
            ) $charset_collate;";
            
            dbDelta($sql_revisits);

            // User Progress Table
            $sql_progress = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_progress (
                user_id bigint(20) UNSIGNED NOT NULL,
                digests_read int NOT NULL DEFAULT 0,
                widgets_explored int NOT NULL DEFAULT 0,
                audio_minutes int NOT NULL DEFAULT 0,
                last_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id),
                KEY last_updated (last_updated)
            ) $charset_collate;";
            
            dbDelta($sql_progress);

            // Performance Table (System Metrics)
            $sql_performance = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_performance (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                metric varchar(50) NOT NULL,
                value float NOT NULL,
                timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY metric (metric),
                KEY timestamp (timestamp)
            ) $charset_collate;";
            
            dbDelta($sql_performance);

            // Sync Table (Multi-Device)
            $sql_sync = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_sync (
                user_id bigint(20) UNSIGNED NOT NULL,
                settings longtext NOT NULL,
                last_synced datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id)
            ) $charset_collate;";
            
            dbDelta($sql_sync);

            // SMS Preferences Table
            $sql_sms_prefs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_sms_prefs (
                user_id bigint(20) UNSIGNED NOT NULL,
                delivery_time time NOT NULL,
                format ENUM('sms','mms') NOT NULL DEFAULT 'sms',
                last_sent datetime DEFAULT NULL,
                PRIMARY KEY (user_id)
            ) $charset_collate;";
            
            dbDelta($sql_sms_prefs);

            // SMS Digests Table
            $sql_sms_digests = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_sms_digests (
                digest_id bigint(20) UNSIGNED NOT NULL,
                sms_text text NOT NULL,
                mms_url text DEFAULT NULL,
                sent_count int NOT NULL DEFAULT 0,
                PRIMARY KEY (digest_id)
            ) $charset_collate;";
            
            dbDelta($sql_sms_digests);

            // Usage Metrics Table
            $sql_usage_metrics = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_usage_metrics (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                metric_type varchar(50) NOT NULL,
                value DECIMAL(10,4) NOT NULL,
                cost DECIMAL(10,4) NOT NULL,
                timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_metric (user_id, metric_type)
            ) $charset_collate;";
            
            dbDelta($sql_usage_metrics);

            // Service Costs Table
            $sql_service_costs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_service_costs (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                service_name varchar(50) NOT NULL,
                cost_per_unit DECIMAL(10,4) NOT NULL,
                markup_percentage DECIMAL(5,2) NOT NULL,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY service (service_name)
            ) $charset_collate;";
            
            dbDelta($sql_service_costs);

            // Better Auth User Map Table
            $sql_ba_wp_user_map = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ba_wp_user_map (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                wp_user_id bigint(20) UNSIGNED NOT NULL,
                ba_user_id bigint(20) UNSIGNED NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY wp_user_id (wp_user_id),
                UNIQUE KEY ba_user_id (ba_user_id)
            ) $charset_collate;";
            
            dbDelta($sql_ba_wp_user_map);

            // Update schema version
            update_option('asap_digest_schema_version', ASAP_DIGEST_SCHEMA_VERSION);

            // Log success
            error_log('[ASAP Digest] Successfully created/updated all database tables');
            
            return true;
        } catch (Exception $e) {
            // Log error
            error_log('[ASAP Digest] Database table creation error: ' . $e->getMessage());
            error_log('[ASAP Digest] MySQL Error: ' . $wpdb->last_error);
            
            // Get any output from dbDelta
            $dbdelta_output = ob_get_clean();
            if (!empty($dbdelta_output)) {
                error_log('[ASAP Digest] dbDelta output: ' . $dbdelta_output);
            }
            
            // Re-throw the exception
            throw $e;
        }

        // Clean up
        ob_end_clean();
        $wpdb->hide_errors();
        
        return false;
    }

    /**
     * Handle database migrations
     * 
     * @return bool True on success, false on failure
     */
    public function handle_migrations() {
        $current_version = get_option('asap_digest_schema_version', '0.0.0');
        $target_version = ASAP_DIGEST_SCHEMA_VERSION;
        
        // If versions match, no migration needed
        if (version_compare($current_version, $target_version, '==')) {
            return true;
        }
        
        try {
            // Run migrations based on version
            if (version_compare($current_version, '1.0.0', '<')) {
                $this->migrate_to_1_0_0();
            }
            
            if (version_compare($current_version, '1.0.1', '<') && version_compare($target_version, '1.0.1', '>=')) {
                $this->migrate_to_1_0_1();
            }
            
            if (version_compare($current_version, '1.0.2', '<') && version_compare($target_version, '1.0.2', '>=')) {
                $this->migrate_to_1_0_2();
            }
            
            // Update schema version to target version
            update_option('asap_digest_schema_version', $target_version);
            
            error_log("[ASAP Digest] Database migrated from version {$current_version} to {$target_version}");
            return true;
        } catch (Exception $e) {
            error_log("[ASAP Digest] Database migration error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migration to version 1.0.0
     */
    private function migrate_to_1_0_0() {
        // Initial schema setup - already handled by create_tables()
        $this->create_tables();
    }
    
    /**
     * Migration to version 1.0.1
     */
    private function migrate_to_1_0_1() {
        global $wpdb;
        
        // Example: Add a new field to an existing table
        $wpdb->query("ALTER TABLE {$wpdb->prefix}asap_ingested_content ADD COLUMN ai_processed TINYINT DEFAULT 0 AFTER quality_score");
        
        error_log("[ASAP Digest] Applied migration for version 1.0.1");
    }
    
    /**
     * Migration to version 1.0.2
     */
    private function migrate_to_1_0_2() {
        global $wpdb;
        
        // Example: Add processing_time field to source_metrics if not exists
        $check_column = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}asap_source_metrics LIKE 'processing_time'");
        if (empty($check_column)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}asap_source_metrics ADD COLUMN processing_time FLOAT DEFAULT 0 AFTER items_rejected");
        }
        
        error_log("[ASAP Digest] Applied migration for version 1.0.2");
    }

    /**
     * Get table name with prefix
     * 
     * @param string $table Table name without prefix
     * @return string Table name with prefix
     */
    public function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . $table;
    }

    /**
     * Check if a table exists
     * 
     * @param string $table Table name without prefix
     * @return bool True if table exists, false otherwise
     */
    public function table_exists($table) {
        global $wpdb;
        $table_name = $this->get_table_name($table);
        $result = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        return $result === $table_name;
    }

    /**
     * Insert usage metric
     */
    public function insert_usage_metric($user_id, $metric_type, $value, $cost) {
        global $wpdb;
        return $wpdb->insert(
            $this->get_table_name('asap_usage_metrics'),
            [
                'user_id' => $user_id,
                'metric_type' => $metric_type,
                'value' => $value,
                'cost' => $cost
            ],
            ['%d', '%s', '%f', '%f']
        );
    }

    /**
     * Get service cost
     */
    public function get_service_cost($service_name) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT cost_per_unit, markup_percentage 
             FROM {$this->get_table_name('asap_service_costs')} 
             WHERE service_name = %s",
            $service_name
        ));
    }

    /**
     * Update service cost
     */
    public function update_service_cost($service_name, $cost_per_unit, $markup_percentage = 0.00) {
        global $wpdb;
        return $wpdb->replace(
            $this->get_table_name('asap_service_costs'),
            [
                'service_name' => $service_name,
                'cost_per_unit' => $cost_per_unit,
                'markup_percentage' => $markup_percentage
            ],
            ['%s', '%f', '%f']
        );
    }

    /**
     * Map WordPress user to Better Auth user
     */
    public function map_user($wp_user_id, $ba_user_id) {
        global $wpdb;
        return $wpdb->replace(
            $this->get_table_name('ba_wp_user_map'),
            [
                'wp_user_id' => $wp_user_id,
                'ba_user_id' => $ba_user_id
            ],
            ['%d', '%s']
        );
    }

    /**
     * Get Better Auth user ID by WordPress user ID
     */
    public function get_ba_user_id($wp_user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT ba_user_id 
             FROM {$this->get_table_name('ba_wp_user_map')} 
             WHERE wp_user_id = %d",
            $wp_user_id
        ));
    }

    /**
     * Get WordPress user ID by Better Auth user ID
     */
    public function get_wp_user_id($ba_user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT wp_user_id 
             FROM {$this->get_table_name('ba_wp_user_map')} 
             WHERE ba_user_id = %s",
            $ba_user_id
        ));
    }
} 