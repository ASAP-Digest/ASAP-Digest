<?php
/**
 * ASAP Digest Database Handler
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Database
 */

namespace ASAPDigest\Core;

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
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Usage Metrics Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_usage_metrics (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            metric_type VARCHAR(50) NOT NULL,
            value DECIMAL(10,4) NOT NULL,
            cost DECIMAL(10,4) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX user_metric (user_id, metric_type)
        ) $charset_collate;";
        dbDelta($sql);

        // Service Costs Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_service_costs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_name VARCHAR(50) NOT NULL,
            cost_per_unit DECIMAL(10,4) NOT NULL,
            markup_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY service (service_name)
        ) $charset_collate;";
        dbDelta($sql);

        // Better Auth User Map Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ba_wp_user_map (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT(20) UNSIGNED NOT NULL,
            ba_user_id VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user (wp_user_id),
            UNIQUE KEY ba_user (ba_user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Notifications Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_notifications (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            subscription_endpoint TEXT NOT NULL,
            auth_key VARCHAR(255) NOT NULL,
            p256dh_key VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Get table name with prefix
     */
    public function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . $table;
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