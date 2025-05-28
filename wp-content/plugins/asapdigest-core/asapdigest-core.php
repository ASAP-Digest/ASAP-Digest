<?php
/**
 * Plugin Name:     [⚡️ ASAP Digest Core ]
 * Plugin URI:      https://asapdigest.com/
 * Description:     Core functionality for ASAP Digest app <a href="https://app.asapdigest.com" target="_blank">https://app.asapdigest.com</a>
 * Author:          ASAP Digest Team
 * Author URI:      https://asapdigest.com
 * Text Domain:     asapdigest-core
 * Domain Path:     /languages
 * Version:         0.1.1
 * 
 * @package         ASAPDigest_Core
 * @created         03.31.25 | 03:34 PM PDT
 * @file-marker     ASAP_Digest_Core_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('ASAP_DIGEST_SCHEMA_VERSION', '1.0.2');
define('ASAP_DIGEST_VERSION', '3.0.0');
define('ASAP_DIGEST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASAP_DIGEST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Define a temporary sync secret for development server-to-server communication
if (!defined('BETTER_AUTH_SECRET')) {
    define('BETTER_AUTH_SECRET', 'development-sync-secret-v6');
}

// Load Content Processing Pipeline
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/content-processing/bootstrap.php';

// Load AJAX System
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/bootstrap.php';

// Include Better Auth configuration
require_once ASAP_DIGEST_PLUGIN_DIR . 'better-auth-config.php';

// Include the API Base Controller FIRST
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-rest-base.php';

// Include the Activator class
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-activator.php';

// Include Custom Table Manager and CPT Interceptor
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-custom-table-manager.php';
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-cpt-interceptor.php';
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-graphql-resolvers.php';

// Load admin classes (per wordpress-class-organization protocol)
require_once ASAP_DIGEST_PLUGIN_DIR . 'admin/class-admin.php';
require_once ASAP_DIGEST_PLUGIN_DIR . 'admin/class-admin-ui.php';
require_once ASAP_DIGEST_PLUGIN_DIR . 'admin/class-admin-modules-list-table.php';
require_once ASAP_DIGEST_PLUGIN_DIR . 'admin/class-admin-digests-list-table.php';
require_once ASAP_DIGEST_PLUGIN_DIR . 'admin/class-custom-table-admin.php';

// Register activation/deactivation hooks
register_activation_hook(__FILE__, ['\ASAPDigest\Core\ASAP_Digest_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['\ASAPDigest\Core\ASAP_Digest_Activator', 'deactivate']);

// --- Ensure all core classes are loaded before admin classes (per wordpress-hook-protocol) ---
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-core.php';

// Include Central Command class BEFORE instantiation (per wordpress-class-organization protocol)
require_once ASAP_DIGEST_PLUGIN_DIR . 'admin/class-central-command.php';

/**
 * Initialize ASAP Digest Core plugin.
 * 
 * @since 1.0.0
 * @return ASAPDigest\Core\ASAP_Digest_Core The main plugin instance.
 */
function asap_digest_core() {
    return \ASAPDigest\Core\ASAP_Digest_Core::get_instance();
}

// Initialize the plugin
asap_digest_core();

// Initialize CPT Interceptor to redirect CPT operations to custom tables
add_action('init', function() {
    new \ASAPDigest\Core\CPT_Interceptor();
}, 5);

// Initialize GraphQL Resolvers for custom tables
add_action('init', function() {
    // Only initialize if WPGraphQL is active
    if (class_exists('WPGraphQL')) {
        new \ASAPDigest\Core\GraphQL_Resolvers();
    }
}, 10);

// Note: Custom_Table_Admin is instantiated by Central Command when needed
// This prevents duplicate instantiation and follows single responsibility principle

// Legacy non-namespaced function for backward compatibility
// This can be gradually phased out
function asap_init_core() {
    // Core functionality now handled by the ASAP_Digest_Core class
    return asap_digest_core();
}
// Keep for backward compatibility
add_action('plugins_loaded', 'asap_init_core', 5);

/**
 * @description Clean up plugin data on deactivation
 * @return void
 * @example
 * // Called during plugin deactivation
 * asap_cleanup_on_deactivation();
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_cleanup_on_deactivation() {
    // Delegate to the Activator class
    \ASAPDigest\Core\ASAP_Digest_Activator::deactivate();
}

/**
 * @description Enqueue admin styles for ASAP Digest Core
 * @hook add_action('admin_enqueue_scripts', 'asap_enqueue_admin_styles', 30)
 * @param string $hook The current admin page hook
 * @return void
 * @created 03.30.25 | 04:48 PM PDT
 */
function asap_enqueue_admin_styles($hook) {
    // Only load on our plugin's admin pages
    if (strpos($hook, 'asap') === false) {
        return;
    }

    wp_enqueue_style(
        'asap-admin-styles',
        ASAP_DIGEST_PLUGIN_URL . 'admin/css/asap-admin.css',
        [],
        ASAP_DIGEST_SCHEMA_VERSION
    );
}
add_action('admin_enqueue_scripts', 'asap_enqueue_admin_styles', 30);

// Load text domain
load_plugin_textdomain('adc', false, dirname(plugin_basename(__FILE__)) . '/languages/');

// --- ASAP Digest Admin Menu Registration (per wordpress-menu-registration-protocol) ---
// All admin menu and submenu registration is centralized here.
// Callback functions are defined below or required before registration.

// Register the menu with admin_menu (single location, per protocol)
add_action('admin_menu', function() {
    // Create Central Command instance only when needed (in admin context)
if (!isset($GLOBALS['asap_digest_central_command'])) {
    $core_instance = \ASAPDigest\Core\ASAP_Digest_Core::get_instance();
    $GLOBALS['asap_digest_central_command'] = new \ASAPDigest\Core\ASAP_Digest_Central_Command($core_instance);
}
    $GLOBALS['asap_digest_central_command']->register_menus();
}, 30);

/**
 * Fallback CORS headers for WPGraphQL when default handling is insufficient.
 * Runs after WPGraphQL's default CORS handling to supplement missing headers.
 * Environment-aware for SvelteKit frontend origins.
 *
 * @param array $headers Existing headers set by WPGraphQL and other filters.
 * @return array Modified headers array.
 */
function asap_filter_graphql_cors_headers( $headers ) {
    // Determine allowed origins based on environment
    $allowed_origins = [];
    $current_env = wp_get_environment_type(); // development, staging, production, local

    if ( $current_env === 'development' || $current_env === 'local' ) {
        // Allow multiple development origins
        $allowed_origins = [
            'https://localhost:5173',
            'http://localhost:5173',
            'https://127.0.0.1:5173',
            'http://127.0.0.1:5173'
        ];
    } elseif ( $current_env === 'production' ) {
        $allowed_origins = ['https://app.asapdigest.com'];
    } // Add 'staging' environment if needed later

    // Get the origin of the request
    $request_origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? trim( rtrim( $_SERVER['HTTP_ORIGIN'], '/' ) ) : ''; // Trim trailing slash

    // Check if the request origin is in our allowed origins list
    if ( ! empty( $request_origin ) && in_array( $request_origin, $allowed_origins ) ) {

        // Check if WPGraphQL has already set Access-Control-Allow-Origin
        if ( isset( $headers['Access-Control-Allow-Origin'] ) ) {
            // WPGraphQL has already handled CORS - don't interfere, just log
            error_log('[ASAP CORS Fallback] WPGraphQL already set CORS headers for origin: ' . $request_origin . '. Not interfering.');
            // Don't add any headers to prevent duplicates
        } else {
            // WPGraphQL didn't handle this origin, we need to step in
            error_log('[ASAP CORS Fallback] WPGraphQL did not handle origin: ' . $request_origin . '. Adding fallback CORS headers.');
            
            $headers['Access-Control-Allow-Origin'] = $request_origin;
            $headers['Access-Control-Allow-Methods'] = 'POST, GET, OPTIONS';
            $headers['Access-Control-Allow-Credentials'] = 'true';
            $headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type, X-WPGraphQL-Login-Token, X-WPGraphQL-Login-Refresh-Token, X-Better-Auth-Signature';
            $headers['Access-Control-Max-Age'] = '600';
        }

    } else {
         // Log why we didn't add headers for debugging
         error_log('[ASAP CORS Fallback] Skipped - origin not in allowed list. Request Origin: \'' . $request_origin . '\' | Allowed Origins: ' . implode(', ', $allowed_origins) . ' (Environment: ' . $current_env . ')');
    }

    // Always return the headers array (potentially modified or original)
    return $headers;
}

/**
 * Add necessary CORS headers using the 'send_headers' action hook.
 * Checks if the request is an OPTIONS preflight from a whitelisted origin
 * and adds required headers directly using header().
 * Environment-aware for SvelteKit frontend origins.
 */
// add_action( 'send_headers', 'asap_add_graphql_cors_headers_on_send' );
// TEMPORARILY DISABLED: GraphQL CORS filter to prevent duplicate headers
// The duplicate CORS headers issue needs to be resolved by configuring WPGraphQL properly
// rather than adding our own CORS handling on top of it.
// 
// TODO: Re-enable this after WPGraphQL CORS configuration is properly set up
// add_filter('graphql_response_headers_to_send', 'asap_filter_graphql_cors_headers', 15, 1);
