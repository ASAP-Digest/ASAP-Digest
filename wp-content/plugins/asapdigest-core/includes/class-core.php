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
     * @var \ASAPDigest\AI\AIServiceManager AI Service Manager instance
     */
    public $ai_service_manager;

    /**
     * @var \ASAPDigest\ContentProcessing\ContentValidator Content validator instance
     */
    public $content_validator;

    /**
     * @var \ASAPDigest\ContentProcessing\ContentDeduplicator Content deduplicator instance
     */
    public $content_deduplicator;

    /**
     * @var \ASAPDigest\ContentProcessing\ContentQuality Content quality instance
     */
    public $content_quality;

    /**
     * @var \ASAPDigest\CPT\Digest_CPT Digest CPT instance
     */
    public $digest_cpt;

    /**
     * @var \ASAPDigest\CPT\Module_CPT Module CPT instance
     */
    public $module_cpt;

    /**
     * @var \ASAPDigest\CPT\ASAP_Digest_Template_CPT Template CPT instance
     */
    public $template_cpt;

    /**
     * @var \ASAPDigest\Core\API\API API instance
     */
    public $api;

    /**
     * @var \ASAPDigest\Crawler\Crawler Crawler instance
     */
    public $crawler;

    /**
     * @var \ASAPDigest\Core\Content_Processing\Content_Processing Content processing instance
     */
    public $content_processing;

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
        // Define constants first, as they might be used in dependency loading
        $this->define_constants();
        $this->load_dependencies();
        $this->init_components();
        $this->define_hooks();
        add_action('admin_init', [ $this, 'register_ai_settings_group' ]);

        // Ensure correct hook priorities for CPT registration if needed
        // The CPT classes hook into 'init' directly. 
        // The old register_custom_types is also hooked to 'init' at priority 10.
        // This should be fine as long as the new classes also use priority 10 or default.

        error_log('ASAP_CORE_DEBUG: ASAP_Digest_Core constructed');
    }

    /**
     * Define ASAP Digest Core constants
     */
    private function define_constants() {
        if (!defined('ASAP_DIGEST_CORE_PATH')) {
            define('ASAP_DIGEST_CORE_PATH', plugin_dir_path(dirname(__FILE__)));
        }
        if (!defined('ASAP_DIGEST_CORE_URL')) {
            define('ASAP_DIGEST_CORE_URL', plugin_dir_url(dirname(__FILE__)));
        }
        if (!defined('ASAP_DIGEST_CORE_VERSION')) {
            define('ASAP_DIGEST_CORE_VERSION', '1.0.0'); // Example version
        }
        error_log('ASAP_CORE_DEBUG: Constants defined. ASAP_DIGEST_CORE_PATH: ' . ASAP_DIGEST_CORE_PATH);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        error_log('ASAP_CORE_DEBUG: Starting load_dependencies()');
        
        // Core Libraries - Database must be loaded first
        error_log('ASAP_CORE_DEBUG: Loading includes/class-database.php');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-database.php';
        error_log('ASAP_CORE_DEBUG: Loading includes/class-better-auth.php');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-better-auth.php';
        error_log('ASAP_CORE_DEBUG: Loading includes/class-usage-tracker.php');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-usage-tracker.php';
        error_log('ASAP_CORE_DEBUG: Loading includes/class-error-logger.php');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-error-logger.php';
        
        // API Components (load before using them in register_rest_routes)
        error_log('ASAP_CORE_DEBUG: Loading API components');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-base.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-digest.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-auth.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-ingested-content.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-session-check-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-sk-token-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-active-sessions-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-sk-user-sync.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-check-sync-token-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-rest-ai-config.php';
        
        // AI System
        error_log('ASAP_CORE_DEBUG: Loading AI system files');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/interfaces/class-ai-debuggable.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/interfaces/class-ai-provider-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/interfaces/interface-ai-provider.php';
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/adapters/class-openai-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/adapters/class-anthropic-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/adapters/class-huggingface-adapter.php';
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/class-ai-service-manager.php';
        
        // AI Processors
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/processors/class-summarizer.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/processors/class-entity-extractor.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/processors/class-classifier.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/processors/class-keyword-generator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ai/processors/class-sentiment-analyzer.php';
        
        // Crawler components
        error_log('ASAP_CORE_DEBUG: Loading crawler components');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/interfaces/class-content-source-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/adapters/class-api-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/adapters/class-rss-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/adapters/class-scraper-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-crawler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-scheduler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-storage.php';
        
        // Content Processing
        error_log('ASAP_CORE_DEBUG: Loading content processing files');
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/class-content-validator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/class-content-deduplicator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/class-content-quality.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/class-content-quality-calculator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/bootstrap.php';
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-content-processor.php';
        
        // Admin / UI
        error_log('ASAP_CORE_DEBUG: Loading UI components');
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-central-command.php';
        
        // Autoloader for classes
        spl_autoload_register([$this, 'autoloader']);

        // Load individual files that are not classes or are structured differently
        // Standardize require_once paths
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/utils.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/hooks.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/cpt/class-digest-cpt.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/cpt/class-module-cpt.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/cpt/class-asap-digest-template-cpt.php';
        
        error_log('ASAP_CORE_DEBUG: Completed load_dependencies()');
    }

    /**
     * Autoloader for plugin classes.
     *
     * @param string $class_name The name of the class to load.
     */
    public function autoloader($class_name) {
        // Check if the class name starts with the main plugin namespace
        $namespace = 'ASAPDigest\\Core\\';
        if (strpos($class_name, $namespace) !== 0) {
            // Not our namespace, let other autoloaders handle it.
            return;
        }

        // Remove the base namespace to get the relative class path
        $relative_class = str_replace($namespace, '', $class_name);

        // Build the file path (assuming PSR-4 like structure within includes/)
        // Replace namespace separators with directory separators
        // Prepend the plugin includes directory path
        $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, include it
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }

        // Add additional lookup paths if necessary (e.g., admin, api)
        $admin_file_path = plugin_dir_path(dirname(__FILE__)) . 'admin/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($admin_file_path)) {
             require_once $admin_file_path;
             return;
        }

        $api_file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/api/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($api_file_path)) {
             require_once $api_file_path;
             return;
        }

        // Add more specific paths if needed (e.g., includes/cpt)
         $cpt_file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/cpt/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($cpt_file_path)) {
             require_once $cpt_file_path;
             return;
        }

        // Optional: Log if a class within the namespace wasn't found
        // error_log("ASAP_CORE_DEBUG: Autoloader failed to find class: " . $class_name . " (Expected path: " . $file_path . ")");
    }

    /**
     * Initialize plugin components with proper dependency injection
     */
    private function init_components() {
        error_log('ASAP_CORE_DEBUG: Starting init_components()');
        
        try {
            // Check if the core classes are loaded
            if (!class_exists('ASAPDigest\\Core\\ASAP_Digest_Database')) {
                error_log('ASAP_CORE_DEBUG: ERROR - ASAP_Digest_Database class not found. Attempting to reload.');
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-database.php';
                
                if (!class_exists('ASAPDigest\\Core\\ASAP_Digest_Database')) {
                    throw new \Exception('Critical: Unable to load ASAP_Digest_Database class after explicit require.');
                }
            }
            
        // Core components
            error_log('ASAP_CORE_DEBUG: Instantiating core components');
        $this->database = new ASAP_Digest_Database();
            
            if (!class_exists('ASAPDigest\\Core\\ASAP_Digest_Better_Auth')) {
                error_log('ASAP_CORE_DEBUG: ERROR - ASAP_Digest_Better_Auth class not found. Attempting to reload.');
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-better-auth.php';
            }
        $this->better_auth = new ASAP_Digest_Better_Auth();
            
            if (!class_exists('ASAPDigest\\Core\\ASAP_Digest_Usage_Tracker')) {
                error_log('ASAP_CORE_DEBUG: ERROR - ASAP_Digest_Usage_Tracker class not found. Attempting to reload.');
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-usage-tracker.php';
            }
        $this->usage_tracker = new ASAP_Digest_Usage_Tracker($this->database);
        
        // Crawler components
            error_log('ASAP_CORE_DEBUG: Instantiating crawler components');
            if (!class_exists('ASAPDigest\\Crawler\\ContentSourceManager')) {
                error_log('ASAP_CORE_DEBUG: ERROR - ContentSourceManager class not found.');
            }
        $this->content_source_manager = new ContentSourceManager($this->database);
            
            // First initialize ContentStorage
            if (!class_exists('AsapDigest\\Crawler\\ContentStorage')) {
                error_log('ASAP_CORE_DEBUG: Loading ContentStorage class');
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-storage.php';
            }
            $content_storage = new \AsapDigest\Crawler\ContentStorage();

            // Check for content processor
            if (!class_exists('AsapDigest\\Crawler\\ContentProcessor')) {
                error_log('ASAP_CORE_DEBUG: ERROR - AsapDigest\\Crawler\\ContentProcessor class not found. Attempting to reload.');
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-content-processor.php';
            }
            $this->content_processor = new \AsapDigest\Crawler\ContentProcessor($content_storage);
        
        // Create crawler instance with dependencies
            error_log('ASAP_CORE_DEBUG: Instantiating content crawler and scheduler');
            if (!class_exists('ASAPDigest\\Crawler\\ContentCrawler')) {
                error_log('ASAP_CORE_DEBUG: ERROR - ContentCrawler class not found.');
            }
        $this->content_crawler = new ContentCrawler(
            $this->content_source_manager,
            $this->content_processor
        );
        
        // Initialize scheduler with a callable to the crawler's run method
            if (!class_exists('ASAPDigest\\Crawler\\Scheduler')) {
                error_log('ASAP_CORE_DEBUG: ERROR - Scheduler class not found.');
            }
        $this->scheduler = new Scheduler([$this->content_crawler, 'run']);
        
        // Initialize AI Service Manager and Processors
        $this->ai_service_manager = new \ASAPDigest\AI\AIServiceManager();
        // Assuming processors are registered within AIServiceManager or a bootstrap

        // Initialize new CPT registration classes
        $this->digest_cpt = new \ASAPDigest\CPT\Digest_CPT();
        $this->module_cpt = new \ASAPDigest\CPT\Module_CPT();
        $this->template_cpt = new \ASAPDigest\CPT\ASAP_Digest_Template_CPT();
        
        // Admin UI components (centralized in class-central-command.php)
            error_log('ASAP_CORE_DEBUG: Initializing Central Command');
            if (!class_exists('ASAPDigest\\Core\\ASAP_Digest_Central_Command')) {
                error_log('ASAP_CORE_DEBUG: ERROR - ASAP_Digest_Central_Command class not found. Attempting to reload.');
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-central-command.php';
            }
            new ASAP_Digest_Central_Command();
            
            error_log('ASAP_CORE_DEBUG: Completed init_components successfully');
        } catch (\Throwable $e) {
            error_log('ASAP_CORE_DEBUG: CRITICAL ERROR in init_components: ' . $e->getMessage());
            error_log('ASAP_CORE_DEBUG: File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            error_log('ASAP_CORE_DEBUG: Stack trace: ' . $e->getTraceAsString());
            // Do not rethrow - we want to continue even with partial initialization
        }
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
            $digest_api = new \ASAPDigest\Core\API\ASAP_Digest_REST_Digest();
        $digest_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Digest API routes');

            // Auth API
            $auth_api = new \ASAPDigest\Core\API\ASAP_Digest_REST_Auth();
        $auth_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Auth API routes');
        
            // Session check API
            $session_check_api = new \ASAPDigest\Core\API\Session_Check_Controller();
        $session_check_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Session Check API routes');
            
            // SK Token API (NEW)
            $sk_token_api = new \ASAPDigest\Core\API\SK_Token_Controller();
            $sk_token_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered SK Token API routes');
            
            // Active Sessions API (NEW)
            $active_sessions_api = new \ASAPDigest\Core\API\Active_Sessions_Controller();
            $active_sessions_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Active Sessions API routes');
            
            // SK User Sync API (OBSOLETE BUT REQUIRED FOR COMPATIBILITY)
            $sk_user_sync = new \ASAPDigest\Core\API\SK_User_Sync();
            $sk_user_sync->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered SK User Sync API routes');
            
            // Check Sync Token API (OBSOLETE BUT REQUIRED FOR COMPATIBILITY)
            $check_token_controller = new \ASAPDigest\Core\API\Check_Sync_Token_Controller();
            $check_token_controller->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Check Sync Token API routes');
            
            // Ingested Content API
            $ingested_content_api = new \ASAPDigest\Core\API\ASAP_Digest_REST_Ingested_Content();
            $ingested_content_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered Ingested Content API routes');
            
            // AI Config API (NEW)
            $ai_config_api = new \ASAPDigest\Core\API\REST_AI_Config();
            $ai_config_api->register_routes();
            error_log('ASAP_CORE_DEBUG: Registered AI Config API routes');
            
            // Nonce Endpoint
            register_rest_route('asap/v1', '/nonce', [
                'methods' => 'GET',
                'callback' => function($req) {
                    return rest_ensure_response(wp_create_nonce($req->get_param('action') ?: 'wp_rest'));
                },
                'permission_callback' => '__return_true'
            ]);
            error_log('ASAP_CORE_DEBUG: Registered Nonce API endpoint');

            // Register AI endpoints
            register_rest_route('asap/v1', '/ai/models/recommended', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_recommended_models'),
                'permission_callback' => array($this, 'check_admin_permission'),
            ));
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
        // $base_args = [
        //     'public' => true,
        //     'show_in_graphql' => true,
        //     'supports' => ['title', 'editor', 'thumbnail'],
        //     'has_archive' => true,
        //     'menu_icon' => 'dashicons-admin-post',
        // ];

        // Register each post type with unique GraphQL names
        // register_post_type('article', array_merge($base_args, [
        //     'label' => '⚡️ - Articles',
        //     'graphql_single_name' => 'Article',
        //     'graphql_plural_name' => 'Articles'
        // ]));

        // register_post_type('podcast', array_merge($base_args, [
        //     'label' => '⚡️ - Podcasts',
        //     'graphql_single_name' => 'Podcast',
        //     'graphql_plural_name' => 'Podcasts'
        // ]));

        // register_post_type('keyterm', array_merge($base_args, [
        //     'label' => '⚡️ - Key Terms',
        //     'graphql_single_name' => 'KeyTerm',
        //     'graphql_plural_name' => 'KeyTerms'
        // ]));

        // register_post_type('financial', array_merge($base_args, [
        //     'label' => '⚡️ - Financial Bites',
        //     'graphql_single_name' => 'Financial',
        //     'graphql_plural_name' => 'Financials'
        // ]));

        // register_post_type('xpost', array_merge($base_args, [
        //     'label' => '⚡️ - X Posts',
        //     'graphql_single_name' => 'XPost',
        //     'graphql_plural_name' => 'XPosts'
        // ]));

        // register_post_type('reddit', array_merge($base_args, [
        //     'label' => '⚡️ - Reddit Buzz',
        //     'graphql_single_name' => 'Reddit',
        //     'graphql_plural_name' => 'Reddits'
        // ]));

        // register_post_type('event', array_merge($base_args, [
        //     'label' => '⚡️ - Events',
        //     'graphql_single_name' => 'Event',
        //     'graphql_plural_name' => 'Events'
        // ]));

        // register_post_type('polymarket', array_merge($base_args, [
        //     'label' => '⚡️ - Polymarket',
        //     'graphql_single_name' => 'Polymarket',
        //     'graphql_plural_name' => 'Polymarkets'
        // ]));

        // The CPTs 'asap_digest' and 'asap_module' are now registered by their dedicated classes.
        // This function is hooked to 'init' with priority 10.
        // The new CPT classes also hook their registration to 'init'. 
        // Ensure their hooks run, or adjust priority if needed. For now, this function can be left empty or just contain comments.
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

    /**
     * Send a test digest (AJAX handler delegation)
     * @return WP_Error Always returns error (TODO: implement or delegate)
     */
    public function send_test_digest() {
        return new \WP_Error('not_implemented', __('send_test_digest not implemented in core.', 'asap-digest'));
    }
    /**
     * Get next digest preview (AJAX handler delegation)
     * @return WP_Error Always returns error (TODO: implement or delegate)
     */
    public function get_next_digest_preview() {
        return new \WP_Error('not_implemented', __('get_next_digest_preview not implemented in core.', 'asap-digest'));
    }
    /**
     * Update settings (AJAX handler delegation)
     * @param array $settings
     * @return WP_Error Always returns error (TODO: implement or delegate)
     */
    public function update_settings($settings) {
        return new \WP_Error('not_implemented', __('update_settings not implemented in core.', 'asap-digest'));
    }
    /**
     * Get usage stats (AJAX handler delegation)
     * @return array|WP_Error
     */
    public function get_stats() {
        return $this->usage_tracker ? $this->usage_tracker->get_stats() : new \WP_Error('no_usage_tracker', __('Usage tracker not available.', 'asap-digest'));
    }
    /**
     * Reset settings (AJAX handler delegation)
     * @return WP_Error Always returns error (TODO: implement or delegate)
     */
    public function reset_settings() {
        return new \WP_Error('not_implemented', __('reset_settings not implemented in core.', 'asap-digest'));
    }

    /**
     * Register AI settings group for Settings API
     */
    public function register_ai_settings_group() {
        register_setting('asap_ai_settings', 'asap_ai_settings');
    }

    /**
     * Get recommended models from HuggingFace
     * 
     * @param WP_REST_Request $request REST API request
     * @return WP_REST_Response
     */
    public function get_recommended_models($request) {
        try {
            // Get the HuggingFace adapter
            $adapter = new \ASAPDigest\AI\Adapters\HuggingFaceAdapter(['api_key' => get_option('asap_ai_huggingface_key', '')]);
            
            // Get recommended models
            $models = $adapter->get_recommended_models();
            
            if (empty($models)) {
                return new \WP_Error(
                    'asap_ai_empty_models',
                    'No recommended models were found.',
                    ['status' => 404]
                );
            }
            
            return rest_ensure_response([
                'models' => $models,
                'success' => true
            ]);
        } catch (\Exception $e) {
            error_log('ASAP_AI_ERROR: Error getting recommended models: ' . $e->getMessage());
            return new \WP_Error(
                'asap_ai_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Check if user has admin permission
     * 
     * @param WP_REST_Request $request
     * @return bool
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }
}

error_log('ASAP_CORE_CLASS_DEBUG: END of class-core.php'); 