<?php
/**
 * ASAP Digest Core Class
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Core
 */

namespace ASAPDigest\Core;

use ASAPDigest\Core\API\ASAP_Digest_REST_Auth;
use ASAPDigest\Core\API\ASAP_Digest_REST_Digest;
use ASAPDigest\Core\ASAP_Digest_Admin_UI;
use ASAPDigest\Core\ASAP_Digest_Better_Auth;
use ASAPDigest\Core\ASAP_Digest_Database;
use ASAPDigest\Core\ASAP_Digest_Usage_Tracker;
use \WP_Error;
use function add_action;
use function date;
use function get_option;
use function get_posts;
use function in_array;
use function ob_get_clean;
use function ob_start;
use function plugin_dir_path;
use function preg_match;
use function register_activation_hook;
use function sprintf;
use function strtotime;
use function term_exists;
use function update_option;
use function wp_get_current_user;
use function wp_mail;
use function wp_parse_args;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Core {
    /**
     * @var ASAP_Digest_Core|null The single instance of the class
     */
    private static $instance = null;

    /**
     * @var ASAP_Digest_Database Database management instance
     */
    private $database;

    /**
     * @var ASAP_Digest_Better_Auth Better Auth integration instance
     */
    private $better_auth;

    /**
     * @var ASAP_Digest_Admin_UI Admin UI instance
     */
    private $admin_ui;

    /**
     * @var ASAP_Digest_Usage_Tracker Usage tracking instance
     */
    private $usage_tracker;

    /**
     * Ensures only one instance is loaded or can be loaded.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_components();
        $this->define_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        if (!defined('ASAP_DIGEST_VERSION')) {
            define('ASAP_DIGEST_VERSION', '0.1.0');
        }
        if (!defined('ASAP_DIGEST_PLUGIN_DIR')) {
            define('ASAP_DIGEST_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
        }
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-database.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-better-auth.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-admin-ui.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-usage-tracker.php';

        // API classes
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-rest-base.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-digest.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-auth.php';
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize database management
        $this->database = new ASAP_Digest_Database();
        
        // Initialize Better Auth integration
        $this->better_auth = new ASAP_Digest_Better_Auth();
        
        // Initialize admin UI
        $this->admin_ui = new ASAP_Digest_Admin_UI();
        
        // Initialize usage tracker
        $this->usage_tracker = new ASAP_Digest_Usage_Tracker();
    }

    /**
     * Define WordPress hooks
     */
    private function define_hooks() {
        // Activation hook
        register_activation_hook(ASAP_DIGEST_PLUGIN_DIR . 'asapdigest-core.php', [$this->database, 'create_tables']);
        
        // Initialize REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        error_log('ASAP_Digest_Core: register_rest_routes called'); // DEBUG LOG

        $digest_api = new ASAP_Digest_REST_Digest();
        $digest_api->register_routes();

        $auth_api = new ASAP_Digest_REST_Auth();
        $auth_api->register_routes();
    }

    /**
     * Get database instance
     */
    public function get_database() {
        return $this->database;
    }

    /**
     * Get Better Auth instance
     */
    public function get_better_auth() {
        return $this->better_auth;
    }

    /**
     * Get usage tracker instance
     */
    public function get_usage_tracker() {
        return $this->usage_tracker;
    }

    /**
     * Send a test digest to the current user
     * 
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function send_test_digest() {
        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            return new WP_Error(
                'invalid_user',
                __('Could not determine current user.', 'asap-digest')
            );
        }

        // Get next digest content
        $preview = $this->get_next_digest_preview();
        if (is_wp_error($preview)) {
            return $preview;
        }

        // Send email
        $subject = __('ASAP Digest Test Email', 'asap-digest');
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        $sent = wp_mail($user->user_email, $subject, $preview, $headers);
        if (!$sent) {
            return new WP_Error(
                'mail_failed',
                __('Failed to send test digest email.', 'asap-digest')
            );
        }

        // Track event
        $this->usage_tracker->track_event('test_digest_sent', [
            'user_id' => $user->ID,
            'email' => $user->user_email
        ]);

        return true;
    }

    /**
     * Get a preview of the next digest
     * 
     * @return string|WP_Error HTML content on success, WP_Error on failure
     */
    public function get_next_digest_preview() {
        $settings = $this->get_settings();
        if (is_wp_error($settings)) {
            return $settings;
        }

        // Get posts since last digest
        $args = [
            'post_type' => 'post',
            'posts_per_page' => $settings['max_posts'],
            'orderby' => 'date',
            'order' => 'DESC',
            'category__in' => $settings['categories'],
            'date_query' => [
                'after' => date('Y-m-d H:i:s', strtotime('-1 ' . $settings['frequency']))
            ]
        ];

        $posts = get_posts($args);
        if (empty($posts)) {
            return new WP_Error(
                'no_posts',
                __('No posts found for next digest.', 'asap-digest')
            );
        }

        // Build digest content
        ob_start();
        include ASAP_DIGEST_PLUGIN_DIR . 'templates/digest-email.php';
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Update plugin settings
     * 
     * @param array $settings New settings values
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function update_settings($settings) {
        if (!is_array($settings)) {
            return new WP_Error(
                'invalid_settings',
                __('Invalid settings data provided.', 'asap-digest')
            );
        }

        // Validate required fields
        $required = ['frequency', 'send_time', 'max_posts'];
        foreach ($required as $field) {
            if (!isset($settings[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Required field "%s" is missing.', 'asap-digest'), $field)
                );
            }
        }

        // Validate frequency
        if (!in_array($settings['frequency'], ['daily', 'weekly', 'monthly'])) {
            return new WP_Error(
                'invalid_frequency',
                __('Invalid digest frequency.', 'asap-digest')
            );
        }

        // Validate send time (24-hour format)
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $settings['send_time'])) {
            return new WP_Error(
                'invalid_time',
                __('Invalid send time format. Use 24-hour format (HH:MM).', 'asap-digest')
            );
        }

        // Validate max posts
        $settings['max_posts'] = intval($settings['max_posts']);
        if ($settings['max_posts'] < 1 || $settings['max_posts'] > 50) {
            return new WP_Error(
                'invalid_max_posts',
                __('Max posts must be between 1 and 50.', 'asap-digest')
            );
        }

        // Validate categories
        if (isset($settings['categories']) && is_array($settings['categories'])) {
            $settings['categories'] = array_map('intval', $settings['categories']);
            $settings['categories'] = array_filter($settings['categories'], function($cat_id) {
                return term_exists($cat_id, 'category');
            });
        } else {
            $settings['categories'] = [];
        }

        // Update settings
        $result = update_option('asap_digest_settings', $settings);
        if (!$result) {
            return new WP_Error(
                'update_failed',
                __('Failed to update settings.', 'asap-digest')
            );
        }

        // Track event
        $this->usage_tracker->track_event('settings_updated', [
            'frequency' => $settings['frequency'],
            'max_posts' => $settings['max_posts'],
            'categories_count' => count($settings['categories'])
        ]);

        return true;
    }

    /**
     * Get plugin statistics
     * 
     * @return array|WP_Error Stats data on success, WP_Error on failure
     */
    public function get_stats() {
        return $this->usage_tracker->get_stats();
    }

    /**
     * Reset plugin settings to defaults
     * 
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function reset_settings() {
        $defaults = [
            'frequency' => 'daily',
            'send_time' => '09:00',
            'max_posts' => 10,
            'categories' => [],
            'session_length' => 3600,
            'refresh_token_length' => 604800,
            'max_sessions' => 5
        ];

        $result = update_option('asap_digest_settings', $defaults);
        if (!$result) {
            return new WP_Error(
                'reset_failed',
                __('Failed to reset settings.', 'asap-digest')
            );
        }

        // Track event
        $this->usage_tracker->track_event('settings_reset');

        return true;
    }

    /**
     * Get plugin settings
     * 
     * @return array|WP_Error Settings array on success, WP_Error on failure
     */
    public function get_settings() {
        $defaults = [
            'frequency' => 'daily',
            'send_time' => '09:00',
            'max_posts' => 10,
            'categories' => [],
            'session_length' => 3600,
            'refresh_token_length' => 604800,
            'max_sessions' => 5
        ];

        $settings = get_option('asap_digest_settings', $defaults);
        if (!is_array($settings)) {
            return new WP_Error(
                'invalid_settings',
                __('Invalid settings data in database.', 'asap-digest')
            );
        }

        return wp_parse_args($settings, $defaults);
    }
} 