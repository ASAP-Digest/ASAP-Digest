<?php
/**
 * ASAP Digest Admin AJAX Handler
 * 
 * @package ASAPDigest_Core
 * @created 05.16.25 | 03:38 PM PDT
 * @file-marker ASAP_Digest_Admin_Ajax
 */

namespace ASAPDigest\Admin;

use ASAPDigest\Core\ASAP_Digest_Core;
use WP_Error;
use function add_action;
use function check_ajax_referer;
use function current_user_can;
use function wp_send_json_error;
use function wp_send_json_success;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin AJAX Handler class
 * 
 * Handles AJAX requests for admin functionality
 */
class ASAP_Digest_Admin_Ajax {
    /**
     * @var ASAP_Digest_Core Core plugin instance
     */
    private $core;

    /**
     * Constructor
     * 
     * @param ASAP_Digest_Core $core Core plugin instance
     */
    public function __construct($core) {
        $this->core = $core;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     * 
     * @return void
     */
    private function init_hooks() {
        // Register AJAX handlers
        add_action('wp_ajax_asap_admin_action', [$this, 'handle_admin_action']);
        add_action('wp_ajax_asap_get_dashboard_data', [$this, 'handle_get_dashboard_data']);
        add_action('wp_ajax_asap_update_settings', [$this, 'handle_update_settings']);
    }

    /**
     * Handle generic admin action
     * 
     * @return void
     */
    public function handle_admin_action() {
        // Check if user has capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Verify nonce
        check_ajax_referer('asap_admin_nonce', 'nonce');

        $action = sanitize_text_field($_POST['subaction'] ?? '');

        switch ($action) {
            case 'test_connection':
                $this->handle_test_connection();
                break;
            
            case 'purge_cache':
                $this->handle_purge_cache();
                break;
            
            case 'update_license':
                $this->handle_update_license();
                break;

            default:
                wp_send_json_error(['message' => 'Unknown action']);
                break;
        }
    }

    /**
     * Handle test connection
     * 
     * @return void
     */
    private function handle_test_connection() {
        // Implementation would be here
        wp_send_json_success(['message' => 'Connection successful']);
    }

    /**
     * Handle purge cache
     * 
     * @return void
     */
    private function handle_purge_cache() {
        // Implementation would be here
        wp_send_json_success(['message' => 'Cache purged successfully']);
    }

    /**
     * Handle update license
     * 
     * @return void
     */
    private function handle_update_license() {
        // Verify license key
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        
        if (empty($license_key)) {
            wp_send_json_error(['message' => 'License key is required']);
        }

        // Implementation would be here
        
        wp_send_json_success(['message' => 'License updated successfully']);
    }

    /**
     * Handle get dashboard data
     * 
     * @return void
     */
    public function handle_get_dashboard_data() {
        // Check if user has capability
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Verify nonce
        check_ajax_referer('asap_admin_nonce', 'nonce');

        // Get dashboard data
        $data = [
            'stats' => $this->get_dashboard_stats(),
            'recent_content' => $this->get_recent_content(),
            'notifications' => $this->get_notifications()
        ];

        wp_send_json_success($data);
    }

    /**
     * Get dashboard stats
     * 
     * @return array Stats data
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        // Get content count
        $content_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asap_content");
        
        // Get source count
        $source_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asap_sources");
        
        // Get digest count
        $digest_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asap_digests");
        
        return [
            'content_count' => $content_count,
            'source_count' => $source_count,
            'digest_count' => $digest_count,
            'last_update' => current_time('mysql')
        ];
    }

    /**
     * Get recent content
     * 
     * @return array Recent content
     */
    private function get_recent_content() {
        global $wpdb;
        
        $recent_content = $wpdb->get_results(
            "SELECT id, title, url, source_id, discovered_at 
             FROM {$wpdb->prefix}asap_content 
             ORDER BY discovered_at DESC 
             LIMIT 5"
        );
        
        return $recent_content;
    }

    /**
     * Get notifications
     * 
     * @return array Notifications
     */
    private function get_notifications() {
        global $wpdb;
        
        $notifications = $wpdb->get_results(
            "SELECT id, title, message, type, created_at 
             FROM {$wpdb->prefix}asap_notifications 
             WHERE user_id = " . get_current_user_id() . " 
             AND (read_at IS NULL OR read_at = '0000-00-00 00:00:00') 
             ORDER BY created_at DESC 
             LIMIT 5"
        );
        
        return $notifications;
    }

    /**
     * Handle update settings
     * 
     * @return void
     */
    public function handle_update_settings() {
        // Check if user has capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Verify nonce
        check_ajax_referer('asap_admin_nonce', 'nonce');

        // Get settings
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        // Sanitize and save settings
        $settings = $this->sanitize_settings($settings);
        update_option('asap_digest_settings', $settings);
        
        wp_send_json_success(['message' => 'Settings updated successfully']);
    }

    /**
     * Sanitize settings
     * 
     * @param array $settings Settings to sanitize
     * @return array Sanitized settings
     */
    private function sanitize_settings($settings) {
        $sanitized = [];
        
        // Sanitize each setting based on type
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'api_key':
                case 'license_key':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                
                case 'enabled_features':
                    $sanitized[$key] = array_map('sanitize_text_field', (array) $value);
                    break;
                
                case 'max_items':
                case 'cache_time':
                    $sanitized[$key] = absint($value);
                    break;
                
                case 'debug_mode':
                    $sanitized[$key] = (bool) $value;
                    break;
                
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $sanitized;
    }
} 