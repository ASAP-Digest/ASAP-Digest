<?php
/**
 * ASAP Digest Usage Tracker
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Usage_Tracker
 */

namespace ASAPDigest\Core;

use WP_Error;
use function current_time;
use function get_option;
use function update_option;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Usage_Tracker {
    /**
     * @var ASAP_Digest_Database Database instance
     */
    private $database;

    /**
     * @var string Option name for usage stats
     */
    private $stats_option = 'asap_digest_usage_stats';

    /**
     * Constructor
     * @param ASAP_Digest_Database $database_instance The database handler instance.
     */
    public function __construct(ASAP_Digest_Database $database_instance) {
        error_log('ASAP_USAGE_TRACKER_DEBUG: __construct() CALLED');
        if (!$database_instance) {
            error_log('ASAP_USAGE_TRACKER_DEBUG: CRITICAL - No database instance provided to Usage_Tracker constructor!');
            // Optionally throw an exception or handle error appropriately
            // For now, to prevent fatal error on null, but this indicates a deeper issue if it happens:
            if (ASAP_Digest_Core::get_instance()) { // Last resort, but avoid if possible
                 $this->database = ASAP_Digest_Core::get_instance()->get_database();
                 error_log('ASAP_USAGE_TRACKER_DEBUG: Fallback to get_instance for database in Usage_Tracker. This is not ideal.');
            } else {
                 error_log('ASAP_USAGE_TRACKER_DEBUG: Fallback FAILED. Core instance is null.');
                 // Cannot proceed without a database instance
                 // throw new \Exception("ASAP_Digest_Database instance is required for ASAP_Digest_Usage_Tracker.");
                 return; // Or handle more gracefully
            }
        } else {
            $this->database = $database_instance;
            error_log('ASAP_USAGE_TRACKER_DEBUG: Database instance received and set.');
        }
        $this->init();
        error_log('ASAP_USAGE_TRACKER_DEBUG: __construct() FINISHED');
    }

    /**
     * Initialize the class
     */
    private function init() {
        if (!get_option($this->stats_option)) {
            $this->init_stats();
        }
    }

    /**
     * Initialize usage stats
     */
    private function init_stats() {
        $initial_stats = [
            'events' => [],
            'last_tracked' => null,
            'total_events' => 0
        ];

        update_option($this->stats_option, $initial_stats);
    }

    /**
     * Track an event
     *
     * @param string $event_name Name of the event to track
     * @param array $data Optional data to store with the event
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function track_event($event_name, $data = []) {
        $stats = get_option($this->stats_option);
        
        if (!$stats) {
            return new WP_Error(
                'stats_not_found',
                __('Could not retrieve usage stats.', 'asap-digest')
            );
        }

        $event = [
            'name' => $event_name,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'data' => $data
        ];

        $stats['events'][] = $event;
        $stats['last_tracked'] = current_time('mysql');
        $stats['total_events']++;

        // Keep only last 1000 events
        if (count($stats['events']) > 1000) {
            array_shift($stats['events']);
        }

        if (!update_option($this->stats_option, $stats)) {
            return new WP_Error(
                'event_tracking_failed',
                __('Could not track event.', 'asap-digest')
            );
        }

        return true;
    }

    /**
     * Get usage stats
     *
     * @return array|WP_Error Stats array or error
     */
    public function get_stats() {
        $stats = get_option($this->stats_option);
        
        if (!$stats) {
            return new WP_Error(
                'stats_not_found',
                __('Could not retrieve usage stats.', 'asap-digest')
            );
        }

        return $stats;
    }

    /**
     * Clear usage stats
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function clear_stats() {
        $this->init_stats();
        return true;
    }

    /**
     * Track service usage
     */
    public function track_usage($user_id, $metric_type, $value) {
        // Get service cost data
        $cost_data = $this->database->get_service_cost($metric_type);
        
        if (!$cost_data) {
            // Use default cost if not configured
            $cost = $value * 0.001; // Default cost of $0.001 per unit
        } else {
            // Calculate cost with markup
            $base_cost = $value * $cost_data->cost_per_unit;
            $markup = $base_cost * ($cost_data->markup_percentage / 100);
            $cost = $base_cost + $markup;
        }

        // Insert usage metric
        return $this->database->insert_usage_metric($user_id, $metric_type, $value, $cost);
    }

    /**
     * Get user usage metrics
     */
    public function get_user_metrics($user_id, $timeframe = 'month') {
        global $wpdb;
        
        $table = $this->database->get_table_name('asap_usage_metrics');
        
        switch ($timeframe) {
            case 'day':
                $time_sql = 'DATE(timestamp) = CURDATE()';
                break;
            case 'week':
                $time_sql = 'YEARWEEK(timestamp) = YEARWEEK(CURDATE())';
                break;
            case 'month':
            default:
                $time_sql = 'YEAR(timestamp) = YEAR(CURDATE()) AND MONTH(timestamp) = MONTH(CURDATE())';
                break;
        }

        $sql = $wpdb->prepare(
            "SELECT metric_type, 
                    SUM(value) as total_value, 
                    SUM(cost) as total_cost,
                    COUNT(*) as usage_count
             FROM $table 
             WHERE user_id = %d 
             AND $time_sql
             GROUP BY metric_type",
            $user_id
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Get total service costs
     */
    public function get_total_service_costs($timeframe = 'month') {
        global $wpdb;
        
        $table = $this->database->get_table_name('asap_usage_metrics');
        
        switch ($timeframe) {
            case 'day':
                $time_sql = 'DATE(timestamp) = CURDATE()';
                break;
            case 'week':
                $time_sql = 'YEARWEEK(timestamp) = YEARWEEK(CURDATE())';
                break;
            case 'month':
            default:
                $time_sql = 'YEAR(timestamp) = YEAR(CURDATE()) AND MONTH(timestamp) = MONTH(CURDATE())';
                break;
        }

        // No variables in $sql, so do NOT use $wpdb->prepare()
        $sql = "SELECT metric_type, 
                       SUM(value) as total_value, 
                       SUM(cost) as total_cost,
                       COUNT(DISTINCT user_id) as unique_users
                FROM $table 
                WHERE $time_sql
                GROUP BY metric_type";

        // Direct query is correct here. If you add variables, use $wpdb->prepare().
        return $wpdb->get_results($sql);
    }

    /**
     * Update service cost configuration
     */
    public function update_service_cost($service_name, $cost_per_unit, $markup_percentage = 0.00) {
        return $this->database->update_service_cost($service_name, $cost_per_unit, $markup_percentage);
    }

    /**
     * Get service cost configuration
     */
    public function get_service_cost($service_name) {
        return $this->database->get_service_cost($service_name);
    }

    /**
     * Get user's current billing cycle usage
     */
    public function get_billing_cycle_usage($user_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('asap_usage_metrics');
        
        // Get usage since the start of the current billing cycle
        // Assuming billing cycle starts on the 1st of each month
        $sql = $wpdb->prepare(
            "SELECT metric_type, 
                    SUM(value) as total_value, 
                    SUM(cost) as total_cost
             FROM $table 
             WHERE user_id = %d 
             AND timestamp >= DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY)
             GROUP BY metric_type",
            $user_id
        );

        return $wpdb->get_results($sql);
    }
} 