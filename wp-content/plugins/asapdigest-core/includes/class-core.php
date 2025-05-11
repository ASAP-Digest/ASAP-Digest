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

error_log('ASAP_CORE_CLASS_DEBUG: START of class-core.php');

if (!defined('ABSPATH')) {
    error_log('ASAP_CORE_CLASS_DEBUG: ABSPATH not defined in class-core.php, exiting');
    exit;
}

// --- Ensure all trait dependencies are loaded before core classes (per wordpress-hook-protocol) ---
error_log('ASAP_CORE_CLASS_DEBUG: Before trait user-sync.php require');
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/traits/user-sync.php';
error_log('ASAP_CORE_CLASS_DEBUG: After trait user-sync.php require');

error_log('ASAP_CORE_CLASS_DEBUG: Before trait session-mgmt.php require');
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/traits/session-mgmt.php';
error_log('ASAP_CORE_CLASS_DEBUG: After trait session-mgmt.php require');

class ASAP_Digest_Core {
    /**
     * @var ASAP_Digest_Core|null The single instance of the class
     */
    private static $instance = null;
    private static $get_instance_call_count = 0; // DEBUG: Recursion counter
    private static $constructor_call_count = 0; // DEBUG: Recursion counter

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
    // private $admin_ui; // Intentionally not used directly as property for now

    /**
     * @var ASAP_Digest_Usage_Tracker Usage tracking instance
     */
    private $usage_tracker;

    /**
     * Ensures only one instance is loaded or can be loaded.
     */
    public static function get_instance() {
        self::$get_instance_call_count++;
        error_log('ASAP_CORE_CLASS_DEBUG: get_instance() CALLED (' . self::$get_instance_call_count . ' times)');
        if (self::$get_instance_call_count > 5) { // Emergency break for deep recursion
            error_log('ASAP_CORE_CLASS_DEBUG: EMERGENCY BREAK - get_instance() called > 5 times, potential recursion detected in get_instance itself!');
            // debug_print_backtrace(); // Optionally print backtrace here
            return null; // Prevent further recursion
        }

        if (null === self::$instance) {
            error_log('ASAP_CORE_CLASS_DEBUG: get_instance() - self::$instance is NULL, creating new self()');
            self::$instance = new self();
            error_log('ASAP_CORE_CLASS_DEBUG: get_instance() - new self() CREATED');
        } else {
            error_log('ASAP_CORE_CLASS_DEBUG: get_instance() - self::$instance EXISTS, returning existing');
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        self::$constructor_call_count++;
        error_log('ASAP_CORE_CLASS_DEBUG: __construct() CALLED (' . self::$constructor_call_count . ' times)');
        if (self::$constructor_call_count > 5) { // Emergency break for deep recursion
            error_log('ASAP_CORE_CLASS_DEBUG: EMERGENCY BREAK - __construct() called > 5 times, potential recursion detected in constructor chain!');
            // debug_print_backtrace(); // Optionally print backtrace here
            return; // Prevent further recursion
        }

        error_log('ASAP_CORE_CLASS_DEBUG: __construct() - Before define_constants()');
        $this->define_constants();
        error_log('ASAP_CORE_CLASS_DEBUG: __construct() - After define_constants(), Before load_dependencies()');
        $this->load_dependencies();
        error_log('ASAP_CORE_CLASS_DEBUG: __construct() - After load_dependencies(), Before init_components()');
        $this->init_components();
        error_log('ASAP_CORE_CLASS_DEBUG: __construct() - After init_components(), Before define_hooks()');
        $this->define_hooks();
        error_log('ASAP_CORE_CLASS_DEBUG: __construct() - FINISHED');

        $this->init_content_processing();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        error_log('ASAP_CORE_CLASS_DEBUG: define_constants() CALLED');
        if (!defined('ASAP_DIGEST_VERSION')) {
            define('ASAP_DIGEST_VERSION', '0.1.0');
        }
        if (!defined('ASAP_DIGEST_PLUGIN_DIR')) {
            define('ASAP_DIGEST_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
        }
        error_log('ASAP_CORE_CLASS_DEBUG: define_constants() FINISHED');
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() CALLED');
        // Core classes
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-database.php');
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-database.php';
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-database.php');

        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-better-auth.php');
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-better-auth.php';
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-better-auth.php');

        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-admin-ui.php');
        require_once ASAP_DIGEST_PLUGIN_DIR . 'admin/class-admin-ui.php';
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-admin-ui.php');

        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-usage-tracker.php');
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-usage-tracker.php';
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-usage-tracker.php');

        // API classes
        // Note: Many API classes are already loaded in asapdigest-core.php before this class is even parsed.
        // We only need to ensure any *additional* ones are loaded if they are direct dependencies *of Core only*.
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-rest-base.php (API) - likely already loaded');
        // require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-rest-base.php'; // Already loaded in main plugin file
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-rest-base.php (API)');

        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-rest-digest.php (API)');
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-rest-digest.php';
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-rest-digest.php (API)');

        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-rest-auth.php (API) - likely already loaded');
        // require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-rest-auth.php'; // Already loaded in main plugin file
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-rest-auth.php (API)');

        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - Before class-session-check-controller.php (API) - likely already loaded');
        // require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-session-check-controller.php'; // Already loaded
        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() - After class-session-check-controller.php (API)');

        error_log('ASAP_CORE_CLASS_DEBUG: load_dependencies() FINISHED');
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        error_log('ASAP_CORE_CLASS_DEBUG: init_components() CALLED');
        // Initialize database management
        error_log('ASAP_CORE_CLASS_DEBUG: init_components() - Before new ASAP_Digest_Database()');
        $this->database = new ASAP_Digest_Database();
        error_log('ASAP_CORE_CLASS_DEBUG: init_components() - After new ASAP_Digest_Database()');
        
        // Initialize Better Auth integration
        error_log('ASAP_CORE_CLASS_DEBUG: init_components() - Before new ASAP_Digest_Better_Auth()');
        $this->better_auth = new ASAP_Digest_Better_Auth();
        error_log('ASAP_CORE_CLASS_DEBUG: init_components() - After new ASAP_Digest_Better_Auth()');
        
        // Do NOT instantiate ASAP_Digest_Admin_UI here; use only for static helpers (per menu registration protocol)
        // $this->admin_ui = new ASAP_Digest_Admin_UI();
        // error_log('ASAP_CORE_CLASS_DEBUG: init_components() - ASAP_Digest_Admin_UI NOT instantiated (correct)');
        
        // Initialize usage tracker
        error_log('ASAP_CORE_CLASS_DEBUG: init_components() - Before new ASAP_Digest_Usage_Tracker(passing $this->database)');
        if ($this->database) {
            $this->usage_tracker = new ASAP_Digest_Usage_Tracker($this->database);
            error_log('ASAP_CORE_CLASS_DEBUG: init_components() - After new ASAP_Digest_Usage_Tracker(with DB instance)');
        } else {
            error_log('ASAP_CORE_CLASS_DEBUG: init_components() - CRITICAL ERROR: $this->database is NULL before instantiating Usage_Tracker!');
            // Handle error: maybe don't instantiate usage_tracker or throw exception
        }
        error_log('ASAP_CORE_CLASS_DEBUG: init_components() FINISHED');
    }

    /**
     * Define WordPress hooks
     */
    private function define_hooks() {
        error_log('ASAP_CORE_CLASS_DEBUG: define_hooks() CALLED');
        // Activation hook
        // Note: The main activation hook is in asapdigest-core.php. This might be redundant or for a different purpose.
        // If it's for table creation, ensure $this->database is available.
        // register_activation_hook(ASAP_DIGEST_PLUGIN_DIR . 'asapdigest-core.php', [$this->database, 'create_tables']);
        // error_log('ASAP_CORE_CLASS_DEBUG: define_hooks() - Activation hook for DB tables commented out for now');
        
        // Initialize REST API endpoints
        error_log('ASAP_CORE_CLASS_DEBUG: define_hooks() - Before add_action rest_api_init for register_rest_routes');
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        error_log('ASAP_CORE_CLASS_DEBUG: define_hooks() - After add_action rest_api_init for register_rest_routes');
        error_log('ASAP_CORE_CLASS_DEBUG: define_hooks() FINISHED');
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() CALLED');

        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() - Before new ASAP_Digest_REST_Digest()');
        $digest_api = new ASAP_Digest_REST_Digest();
        $digest_api->register_routes();
        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() - After ASAP_Digest_REST_Digest()->register_routes()');

        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() - Before new ASAP_Digest_REST_Auth()');
        $auth_api = new ASAP_Digest_REST_Auth();
        $auth_api->register_routes();
        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() - After ASAP_Digest_REST_Auth()->register_routes()');
        
        // Register the new session check controller
        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() - Before new API\Session_Check_Controller()');
        $session_check_api = new API\Session_Check_Controller();
        $session_check_api->register_routes();
        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() - After API\Session_Check_Controller()->register_routes()');
        error_log('ASAP_CORE_CLASS_DEBUG: register_rest_routes() FINISHED');
    }

    /**
     * Get database instance
     */
    public function get_database() {
        error_log('ASAP_CORE_CLASS_DEBUG: get_database() CALLED');
        return $this->database;
    }

    /**
     * Get Better Auth instance
     */
    public function get_better_auth() {
        error_log('ASAP_CORE_CLASS_DEBUG: get_better_auth() CALLED');
        return $this->better_auth;
    }

    /**
     * Get usage tracker instance
     */
    public function get_usage_tracker() {
        error_log('ASAP_CORE_CLASS_DEBUG: get_usage_tracker() CALLED');
        return $this->usage_tracker;
    }

    /**
     * Send a test digest to the current user
     * 
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function send_test_digest() {
        error_log('ASAP_CORE_CLASS_DEBUG: send_test_digest() CALLED');
        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            return new WP_Error(
                'invalid_user',
                __('Could not determine current user.', 'asap-digest')
            );
        }

        // Get next digest content
        $digest_content = $this->get_next_digest_preview();

        if (is_wp_error($digest_content)) {
            return $digest_content;
        }

        // Send email
        $subject = sprintf(
            __('ASAP Digest Test - %s', 'asap-digest'),
            date('Y-m-d')
        );
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        if (!wp_mail($user->user_email, $subject, $digest_content, $headers)) {
            return new WP_Error(
                'send_failed',
                __('Could not send test digest email.', 'asap-digest')
            );
        }

        return true;
    }

    /**
     * Get preview of the next digest content
     *
     * @return string|WP_Error Digest content or error
     */
    public function get_next_digest_preview() {
        error_log('ASAP_CORE_CLASS_DEBUG: get_next_digest_preview() CALLED');
        // Get settings
        $settings = $this->get_settings();
        if (is_wp_error($settings)) {
            return $settings;
        }

        // Get posts
        $query_args = [
            'post_type' => $settings['categories'],
            'posts_per_page' => $settings['max_posts'],
            'post_status' => 'publish'
        ];
        $posts = get_posts($query_args);

        if (empty($posts)) {
            return new WP_Error(
                'no_posts',
                __('No posts found for the digest.', 'asap-digest')
            );
        }

        // Build digest content (simple version for preview)
        ob_start();
        ?>
        <h1><?php _e('Your Next Digest', 'asap-digest'); ?></h1>
        <ul>
            <?php foreach ($posts as $post) : ?>
                <li>
                    <h2><a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a></h2>
                    <p><?php echo esc_html(wp_trim_words($post->post_content, 50)); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    /**
     * Update plugin settings
     *
     * @param array $new_settings
     * @return array|WP_Error Updated settings or error
     */
    public function update_settings($new_settings) {
        error_log('ASAP_CORE_CLASS_DEBUG: update_settings() CALLED');
        $current_settings = $this->get_settings();
        if (is_wp_error($current_settings)) {
            return $current_settings;
        }

        $validated_settings = [];
        // Validate frequency
        if (isset($new_settings['frequency']) && in_array($new_settings['frequency'], ['daily', 'weekly', 'monthly'])) {
            $validated_settings['frequency'] = $new_settings['frequency'];
        }
        // Validate send_time (basic format check)
        if (isset($new_settings['send_time']) && preg_match('/^\d{2}:\d{2}$/', $new_settings['send_time'])) {
            $validated_settings['send_time'] = $new_settings['send_time'];
        }
        // Validate categories (ensure it's an array of strings)
        if (isset($new_settings['categories']) && is_array($new_settings['categories'])) {
            $validated_settings['categories'] = array_map('sanitize_text_field', $new_settings['categories']);
        }
        // Validate max_posts
        if (isset($new_settings['max_posts']) && is_numeric($new_settings['max_posts']) && $new_settings['max_posts'] > 0) {
            $validated_settings['max_posts'] = intval($new_settings['max_posts']);
        }

        $updated_settings = array_merge($current_settings, $validated_settings);

        if (!update_option('asap_digest_settings', $updated_settings)) {
            return new WP_Error(
                'settings_update_failed',
                __('Failed to update settings.', 'asap-digest')
            );
        }
        error_log('ASAP_CORE_CLASS_DEBUG: update_settings() FINISHED - Settings updated successfully.');
        return $updated_settings;
    }

    /**
     * Get plugin stats
     *
     * @return array|WP_Error Stats or error
     */
    public function get_stats() {
        error_log('ASAP_CORE_CLASS_DEBUG: get_stats() CALLED');
        $stats = get_option('asap_digest_stats');
        if (false === $stats) {
            // Initialize if not found
            $stats = [
                'total_digests_sent' => 0,
                'total_posts_included' => 0,
                'last_digest_date' => null,
            ];
            update_option('asap_digest_stats', $stats);
        }
        return $stats;
    }

    /**
     * Reset plugin settings to defaults
     *
     * @return array|WP_Error Default settings or error
     */
    public function reset_settings() {
        error_log('ASAP_CORE_CLASS_DEBUG: reset_settings() CALLED');
        $default_settings = [
            'frequency' => 'daily',
            'send_time' => '09:00',
            'categories' => ['post'], 
            'max_posts' => 5,      
        ];
        if (!update_option('asap_digest_settings', $default_settings)) {
            return new WP_Error(
                'settings_reset_failed',
                __('Failed to reset settings to defaults.', 'asap-digest')
            );
        }
        error_log('ASAP_CORE_CLASS_DEBUG: reset_settings() FINISHED - Settings reset to defaults.');
        return $default_settings;
    }

    /**
     * Get current plugin settings
     *
     * @return array Plugin settings
     */
    public function get_settings() {
        error_log('ASAP_CORE_CLASS_DEBUG: get_settings() CALLED');
        $defaults = [
            'frequency' => 'daily',
            'send_time' => '09:00',
            'categories' => ['post'],
            'max_posts' => 5,
        ];
        $settings = get_option('asap_digest_settings', $defaults);
        // Ensure settings are merged with defaults if some are missing
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Register admin pages and menus
     */
    public function register_admin_menus() {
        // Main plugin menu
        add_menu_page(
            'ASAP Digest', 
            'ASAP Digest', 
            'manage_options', 
            'asap-digest', 
            array($this, 'render_admin_dashboard'),
            'dashicons-rss',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'asap-digest',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'asap-digest',
            array($this, 'render_admin_dashboard')
        );
        
        // Content Sources submenu
        add_submenu_page(
            'asap-digest',
            'Content Sources',
            'Content Sources',
            'manage_options',
            'asap-content-sources',
            array($this, 'render_content_sources')
        );
        
        // Content Library submenu
        add_submenu_page(
            'asap-digest',
            'Content Library',
            'Content Library',
            'manage_options',
            'asap-content-library',
            array($this, 'render_content_library')
        );
        
        // API Settings submenu
        add_submenu_page(
            'asap-digest',
            'API Settings',
            'API Settings',
            'manage_options',
            'asap-api-settings',
            array($this, 'render_api_settings')
        );
        
        // Advanced Settings submenu
        add_submenu_page(
            'asap-digest',
            'Advanced Settings',
            'Advanced Settings',
            'manage_options',
            'asap-advanced-settings',
            array($this, 'render_advanced_settings')
        );
    }

    /**
     * Render admin dashboard page
     */
    public function render_admin_dashboard() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/dashboard.php';
    }

    /**
     * Render content sources admin page
     */
    public function render_content_sources() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/source-management.php';
    }

    /**
     * Render content library admin page
     */
    public function render_content_library() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/content-library.php';
    }

    /**
     * Render API settings admin page
     */
    public function render_api_settings() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/api-settings.php';
    }

    /**
     * Render advanced settings admin page
     */
    public function render_advanced_settings() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/advanced-settings.php';
    }

    /**
     * Initialize content processing components
     */
    public function init_content_processing() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/bootstrap.php';
        
        // Register hooks related to content processing
        add_action('asap_content_added', array($this, 'log_content_action'), 10, 2);
        add_action('asap_content_updated', array($this, 'log_content_action'), 10, 2);
        add_action('asap_content_deleted', array($this, 'log_content_action'), 10, 2);
        
        // Register duplicate detection hooks
        add_action('asap_duplicate_content_detected', array($this, 'handle_duplicate_content'), 10, 3);
    }

    /**
     * Log content actions for audit purposes
     *
     * @param int $content_id Content ID
     * @param array $content_data Content data
     */
    public function log_content_action($content_id, $content_data) {
        // Log to custom log table
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'asap_activity_log';
        $action_type = current_filter(); // gets current action hook as the action type
        $user_id = get_current_user_id();
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action_type' => $action_type,
                'object_id' => $content_id,
                'object_type' => 'content',
                'details' => json_encode(array(
                    'title' => $content_data['title'] ?? '',
                    'type' => $content_data['type'] ?? '',
                    'source_url' => $content_data['source_url'] ?? '',
                )),
                'created_at' => current_time('mysql'),
            )
        );
    }

    /**
     * Handle duplicate content detection
     *
     * @param int $content_id Content ID
     * @param int $duplicate_id Duplicate content ID
     * @param string $fingerprint Content fingerprint
     */
    public function handle_duplicate_content($content_id, $duplicate_id, $fingerprint) {
        // Log the duplicate detection
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'asap_activity_log';
        $user_id = get_current_user_id();
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action_type' => 'duplicate_detected',
                'object_id' => $content_id,
                'object_type' => 'content',
                'details' => json_encode(array(
                    'duplicate_id' => $duplicate_id,
                    'fingerprint' => $fingerprint,
                )),
                'created_at' => current_time('mysql'),
            )
        );
        
        // Optionally handle automatically based on settings
        if (defined('ASAP_DEDUPE_KEEP_HIGHEST_QUALITY') && ASAP_DEDUPE_KEEP_HIGHEST_QUALITY) {
            $this->auto_resolve_duplicate($content_id, $duplicate_id);
        }
    }

    /**
     * Auto-resolve duplicate content based on quality scores
     *
     * @param int $content_id Content ID
     * @param int $duplicate_id Duplicate content ID
     */
    private function auto_resolve_duplicate($content_id, $duplicate_id) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'asap_ingested_content';
        
        // Get quality scores
        $content_score = $wpdb->get_var($wpdb->prepare(
            "SELECT quality_score FROM $content_table WHERE id = %d",
            $content_id
        ));
        
        $duplicate_score = $wpdb->get_var($wpdb->prepare(
            "SELECT quality_score FROM $content_table WHERE id = %d",
            $duplicate_id
        ));
        
        // Compare scores and keep the higher quality content
        if ($content_score > $duplicate_score) {
            // New content has higher quality, remove duplicate
            $processor = asap_digest_get_content_processor();
            $processor->delete($duplicate_id);
            
            // Log the decision
            $this->log_auto_duplicate_resolution($content_id, $duplicate_id, 'kept_new');
        } else {
            // Existing content has higher or equal quality, keep it
            $processor = asap_digest_get_content_processor();
            $processor->delete($content_id);
            
            // Log the decision
            $this->log_auto_duplicate_resolution($content_id, $duplicate_id, 'kept_existing');
        }
    }

    /**
     * Log auto duplicate resolution
     *
     * @param int $content_id Content ID
     * @param int $duplicate_id Duplicate content ID
     * @param string $decision Decision made (kept_new or kept_existing)
     */
    private function log_auto_duplicate_resolution($content_id, $duplicate_id, $decision) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'asap_activity_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => 0, // System action
                'action_type' => 'auto_duplicate_resolved',
                'object_id' => ($decision === 'kept_new') ? $content_id : $duplicate_id,
                'object_type' => 'content',
                'details' => json_encode(array(
                    'content_id' => $content_id,
                    'duplicate_id' => $duplicate_id,
                    'decision' => $decision,
                )),
                'created_at' => current_time('mysql'),
            )
        );
    }

    /**
     * Hook into WordPress init
     */
    public function init() {
        // Initialize REST API
        $this->init_rest_api();
        
        // Initialize admin menus
        add_action('admin_menu', array($this, 'register_admin_menus'));
        
        // Initialize content processing system
        $this->init_content_processing();
        
        // Initialize shortcodes
        $this->init_shortcodes();
        
        // Register custom post types, taxonomies, etc.
        $this->register_custom_types();
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
    }
    
    /**
     * Initialize REST API endpoints
     */
    public function init_rest_api() {
        // Load REST API controllers
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-api.php';
        
        // Initialize API
        $api = new ASAP_Digest_REST_API();
        $api->init();
    }
    
    /**
     * Initialize shortcodes
     */
    public function init_shortcodes() {
        // Load shortcode handlers
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/shortcodes/shortcodes.php';
    }
    
    /**
     * Register custom post types and taxonomies
     */
    public function register_custom_types() {
        // Custom post type registration would go here
    }
    
    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        // AJAX handlers registration would go here
        add_action('wp_ajax_asap_reindex_content', array($this, 'ajax_reindex_content'));
        add_action('wp_ajax_asap_run_source', array($this, 'ajax_run_source'));
    }
    
    /**
     * AJAX handler for reindexing content
     */
    public function ajax_reindex_content() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            return;
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Get batch size
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        
        // Reindex content
        $result = asap_digest_reindex_content($batch_size);
        
        // Return result
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for running a content source
     */
    public function ajax_run_source() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            return;
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Get source ID
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        
        if ($source_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid source ID.'));
            return;
        }
        
        // Load content source manager and crawler
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-crawler.php';
        
        $source_manager = new AsapDigest\Crawler\ContentSourceManager();
        $source = $source_manager->get_source($source_id);
        
        if (!$source) {
            wp_send_json_error(array('message' => 'Source not found.'));
            return;
        }
        
        // Run crawler
        $processor = asap_digest_get_content_processor();
        $crawler = new AsapDigest\Crawler\ContentCrawler($source_manager, $processor);
        $result = $crawler->crawl_source($source);
        
        // Return result
        wp_send_json_success($result);
    }
}

error_log('ASAP_CORE_CLASS_DEBUG: END of class-core.php'); 