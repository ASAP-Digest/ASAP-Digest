<?php
/**
 * ASAP Digest Core Class
 * 
 * Main plugin singleton for initialization and central management of all components.
 * 
 * @package ASAPDigest_Core
 * @created 04.17.25 | 11:45 AM PDT
 * @file-marker ASAP_Digest_Core
 */

namespace ASAPDigest\Core;

use ASAPDigest\Core\API\ASAP_Digest_REST_Auth;
use ASAPDigest\Core\API\ASAP_Digest_REST_Digest;
use ASAPDigest\Core\API\ASAP_Digest_REST_Ingested_Content;
use ASAPDigest\Core\API\Active_Sessions_Controller;
use ASAPDigest\Core\API\Check_Sync_Token_Controller;
use ASAPDigest\Core\API\SK_Token_Controller;
use ASAPDigest\Core\API\SK_User_Sync;
use ASAPDigest\Core\API\Session_Check_Controller;
use ASAPDigest\Crawler\ContentCrawler;
use ASAPDigest\Crawler\ContentSourceManager;
use ASAPDigest\Crawler\Scheduler;
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

/**
 * Class ASAP_Digest_Core
 * 
 * @since 1.0.0
 */
final class ASAP_Digest_Core {
    /**
     * @var ASAP_Digest_Core|null The single instance of the class
     */
    private static $instance = null;

    /**
     * @var ASAP_Digest_Database Database management instance
     */
    public $database;

    /**
     * @var ASAP_Digest_Better_Auth Better Auth integration instance
     */
    public $better_auth;

    /**
     * @var ASAP_Digest_Usage_Tracker Usage tracking instance
     */
    public $usage_tracker;
    
    /**
     * @var ContentSourceManager Content source manager instance
     */
    public $content_source_manager;
    
    /**
     * @var ContentCrawler Content crawler instance
     */
    public $content_crawler;
    
    /**
     * @var \ASAP_Digest_Content_Processor Content processor instance
     */
    public $content_processor;

    /**
     * @var Scheduler Crawler scheduler instance
     */
    public $scheduler;

    /**
     * Ensures only one instance is loaded or can be loaded.
     * 
     * @return ASAP_Digest_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private to enforce singleton pattern
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
        // Most constants now defined in main plugin file
    }

    /**
     * Load dependencies
     *
     * @return void
     */
    private function load_dependencies() {
        // Core files
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-activator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-database.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-content-storage.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-better-auth.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-usage-tracker.php';
        
        // API files
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-base.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-auth.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-digest.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-ingested-content.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-active-sessions-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-session-check-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-sk-token-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-sk-user-sync.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-check-sync-token-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/controllers/class-auth-webhook-controller.php';
        
        // Authentication files
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/auth/bootstrap.php';
        
        // Content processing
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/bootstrap.php';
        
        // Crawler
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-crawler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-scheduler.php';
        
        // Admin
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-central-command.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin-ui.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class/class-admin-ajax.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/ajax-handlers.php';
    }

    /**
     * Initialize plugin components with proper dependency injection
     */
    private function init_components() {
        // Core components
        $this->database = new ASAP_Digest_Database();
        $this->better_auth = new ASAP_Digest_Better_Auth();
        $this->usage_tracker = new ASAP_Digest_Usage_Tracker($this->database);
        
        // Crawler components
        $this->content_source_manager = new ContentSourceManager($this->database);
        $this->content_processor = new \ASAP_Digest_Content_Processor();
        
        // Create crawler instance with dependencies
        $this->content_crawler = new ContentCrawler(
            $this->content_source_manager,
            $this->content_processor
        );
        
        // Initialize scheduler with a callable to the crawler's run method
        $this->scheduler = new Scheduler([$this->content_crawler, 'run']);
        
        // Admin UI components (centralized in class-central-command.php)
        new \ASAPDigest\Core\ASAP_Digest_Central_Command();
    }

    /**
     * Define WordPress hooks
     */
    private function define_hooks() {
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Register custom post types
        add_action('init', [$this, 'register_custom_types'], 10);
        
        // Register cleanup tasks
        add_action('wp', [$this, 'schedule_cleanup'], 10);
        
        // Modify cookie headers for cross-domain auth
        add_action('plugins_loaded', [$this, 'start_cookie_handling'], 0);
        
        // Add CORS headers filter if needed
        // add_filter('rest_pre_serve_request', [$this, 'add_cors_headers'], 15);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        error_log('ASAP_CORE_DEBUG: Registering REST routes');
        
        // Suppress PHP errors from contaminating the API response
        add_filter('rest_suppress_error_output', function() {
            return true;
        }, 10, 1);
        
        try {
            // Digest API
        $digest_api = new ASAP_Digest_REST_Digest();
        $digest_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Digest API routes');

            // Auth API
        $auth_api = new ASAP_Digest_REST_Auth();
        $auth_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Auth API routes');
        
            // Session check API
            $session_check_api = new Session_Check_Controller();
        $session_check_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Session Check API routes');
            
            // SK Token API (NEW)
            $sk_token_api = new SK_Token_Controller();
            $sk_token_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered SK Token API routes');
            
            // Active Sessions API (NEW)
            $active_sessions_api = new Active_Sessions_Controller();
            $active_sessions_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Active Sessions API routes');
            
            // SK User Sync API (OBSOLETE BUT REQUIRED FOR COMPATIBILITY)
            $sk_user_sync = new SK_User_Sync();
            $sk_user_sync->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered SK User Sync API routes');
            
            // Check Sync Token API (OBSOLETE BUT REQUIRED FOR COMPATIBILITY)
            $check_token_controller = new Check_Sync_Token_Controller();
            $check_token_controller->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Check Sync Token API routes');
            
            // Ingested Content API
            $ingested_content_api = new ASAP_Digest_REST_Ingested_Content();
            $ingested_content_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Ingested Content API routes');
            
            // Nonce Endpoint
            register_rest_route('asap/v1', '/nonce', [
                'methods' => 'GET',
                'callback' => function($req) {
                    return rest_ensure_response(wp_create_nonce($req->get_param('action') ?: 'wp_rest'));
                },
                'permission_callback' => '__return_true'
            ]);
            error_log('ASAP_CORE_DEBUG: Registered Nonce API endpoint');
        } catch (\Throwable $e) {
            error_log('ASAP_CORE_DEBUG: Error registering REST routes: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            error_log('ASAP_CORE_DEBUG: Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    /**
     * Register custom post types
     */
    public function register_custom_types() {
        // Base arguments shared across all post types
        $base_args = [
            'public' => true,
            'show_in_graphql' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => true,
            'menu_icon' => 'dashicons-admin-post',
        ];

        // Register each post type with unique GraphQL names
        register_post_type('article', array_merge($base_args, [
            'label' => '⚡️ - Articles',
            'graphql_single_name' => 'Article',
            'graphql_plural_name' => 'Articles'
        ]));

        register_post_type('podcast', array_merge($base_args, [
            'label' => '⚡️ - Podcasts',
            'graphql_single_name' => 'Podcast',
            'graphql_plural_name' => 'Podcasts'
        ]));

        register_post_type('keyterm', array_merge($base_args, [
            'label' => '⚡️ - Key Terms',
            'graphql_single_name' => 'KeyTerm',
            'graphql_plural_name' => 'KeyTerms'
        ]));

        register_post_type('financial', array_merge($base_args, [
            'label' => '⚡️ - Financial Bites',
            'graphql_single_name' => 'Financial',
            'graphql_plural_name' => 'Financials'
        ]));

        register_post_type('xpost', array_merge($base_args, [
            'label' => '⚡️ - X Posts',
            'graphql_single_name' => 'XPost',
            'graphql_plural_name' => 'XPosts'
        ]));

        register_post_type('reddit', array_merge($base_args, [
            'label' => '⚡️ - Reddit Buzz',
            'graphql_single_name' => 'Reddit',
            'graphql_plural_name' => 'Reddits'
        ]));

        register_post_type('event', array_merge($base_args, [
            'label' => '⚡️ - Events',
            'graphql_single_name' => 'Event',
            'graphql_plural_name' => 'Events'
        ]));

        register_post_type('polymarket', array_merge($base_args, [
            'label' => '⚡️ - Polymarket',
            'graphql_single_name' => 'Polymarket',
            'graphql_plural_name' => 'Polymarkets'
        ]));
    }
    
    /**
     * Schedule cleanup tasks
     */
    public function schedule_cleanup() {
        if (!wp_next_scheduled('asap_cleanup_data')) {
            wp_schedule_event(time(), 'daily', 'asap_cleanup_data');
        }
    }

    /**
     * Clean up old digests and notifications data
     */
    public function cleanup_data() {
        global $wpdb;
        $digests_table = $wpdb->prefix . 'asap_digests';
        $notifications_table = $wpdb->prefix . 'asap_notifications';
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        // Secure delete queries
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $digests_table WHERE created_at < %s",
            $cutoff_date
        ));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $notifications_table WHERE created_at < %s",
            $cutoff_date
        ));
    }
    
    /**
     * Start output buffering for cookie header modification
     */
    public function start_cookie_handling() {
        // Avoid buffering during admin requests, CLI processes, or AJAX
        if (is_admin() || (defined('WP_CLI') && WP_CLI) || wp_doing_ajax()) {
            return;
        }
        
        // Start the output buffer with our callback
        ob_start([$this, 'modify_cookie_headers']);
    }
    
    /**
     * Modify cookie headers to work cross-domain
     * 
     * @param string $buffer The output buffer content
     * @return string The original buffer content
     */
    public function modify_cookie_headers($buffer) {
        // Check if headers have already been sent
        if (headers_sent()) {
            return $buffer;
        }
        
        // Define WP auth cookie names for quick lookup
        $auth_cookie_names = [
            AUTH_COOKIE => true,
            SECURE_AUTH_COOKIE => true,
            LOGGED_IN_COOKIE => true,
        ];
        
        $final_headers = [];
        
        // Process all headers
        foreach (headers_list() as $header) {
            $header_lower = strtolower($header);
            
            // Check if this is a Set-Cookie header
            if (strpos($header_lower, 'set-cookie:') === 0) {
                // Extract cookie name
                if (preg_match('/^Set-Cookie:\s*([^=]+)=/i', $header, $matches)) {
                    $cookie_name = $matches[1];
                    
                    // Check if this is a WP auth cookie
                    if (isset($auth_cookie_names[$cookie_name])) {
                        // Remove existing SameSite and Secure attributes
                        $modified_header = preg_replace('/;\s*SameSite=(Lax|Strict|None)/i', '', $header);
                        $modified_header = preg_replace('/;\s*Secure/i', '', $modified_header);
                        
                        // Add required attributes
                        $modified_header .= '; SameSite=None; Secure';
                        
                        $final_headers[] = $modified_header;
                    } else {
                        // Preserve other cookies
                        $final_headers[] = $header;
                    }
                } else {
                    // Malformed cookie header
                    $final_headers[] = $header;
                }
            } else {
                // Not a cookie header
                $final_headers[] = $header;
            }
        }
        
        // Clear all original Set-Cookie headers
        header_remove('Set-Cookie');
        
        // Add our modified headers
        foreach ($final_headers as $final_header) {
            header($final_header, false);
        }
        
        return $buffer;
    }

    /**
     * Get database instance
     * 
     * @return ASAP_Digest_Database The database instance
     */
    public function get_database() {
        return $this->database;
    }

    /**
     * Get usage tracker instance
     * 
     * @return ASAP_Digest_Usage_Tracker The usage tracker instance
     */
    public function get_usage_tracker() {
        return $this->usage_tracker;
    }

    /**
     * Get Better Auth instance
     * 
     * @return ASAP_Digest_Better_Auth The Better Auth instance
     */
    public function get_better_auth() {
        return $this->better_auth;
    }
}

error_log('ASAP_CORE_CLASS_DEBUG: END of class-core.php'); 