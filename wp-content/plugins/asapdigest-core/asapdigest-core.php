<?php
/**
 * Plugin Name:     ASAP Digest Core
 * Plugin URI:      https://asapdigest.com/
 * Description:     Core functionality for ASAP Digest app
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
    error_log('ASAP_CORE_DEBUG: ABSPATH not defined, exiting early in asapdigest-core.php');
    exit;
}
error_log('ASAP_CORE_DEBUG: START of asapdigest-core.php');

// Define constants
define('ASAP_DIGEST_SCHEMA_VERSION', '1.0.2');
define('ASAP_DIGEST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASAP_DIGEST_PLUGIN_URL', plugin_dir_url(__FILE__));
error_log('ASAP_CORE_DEBUG: Constants defined');

// Define a temporary sync secret for development server-to-server communication
if (!defined('BETTER_AUTH_SECRET')) {
    define('BETTER_AUTH_SECRET', 'development-sync-secret-v6');
}
error_log('ASAP_CORE_DEBUG: BETTER_AUTH_SECRET defined/checked');

// Load Content Processing Pipeline
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/content-processing/bootstrap.php';

// Include Better Auth configuration
error_log('ASAP_CORE_DEBUG: Before require_once better-auth-config.php');
require_once(ASAP_DIGEST_PLUGIN_DIR . 'better-auth-config.php');
error_log('ASAP_CORE_DEBUG: After require_once better-auth-config.php');

// Include the API Base Controller FIRST
error_log('ASAP_CORE_DEBUG: Before require_once includes/api/class-rest-base.php');
require_once(ASAP_DIGEST_PLUGIN_DIR . 'includes/api/class-rest-base.php');
error_log('ASAP_CORE_DEBUG: After require_once includes/api/class-rest-base.php');

// Include the Activator class
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-activator.php';

// Register activation/deactivation hooks
register_activation_hook(__FILE__, ['ASAPDigest\\Core\\ASAP_Digest_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['ASAPDigest\\Core\\ASAP_Digest_Activator', 'deactivate']);

// --- Ensure all core classes are loaded before admin classes (per wordpress-hook-protocol) ---
error_log('ASAP_CORE_DEBUG: Before require_once includes/class-core.php');
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/class-core.php';
error_log('ASAP_CORE_DEBUG: After require_once includes/class-core.php');

/**
 * Initialize ASAP Digest Core plugin.
 * 
 * @since 1.0.0
 * @return ASAPDigest\Core\ASAP_Digest_Core The main plugin instance.
 */
function asap_digest_core() {
    return ASAPDigest\Core\ASAP_Digest_Core::get_instance();
}

// Initialize the plugin
asap_digest_core();

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
    ASAPDigest\Core\ASAP_Digest_Activator::deactivate();
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

// Create a single shared instance for menu callbacks
if (!isset($GLOBALS['asap_digest_central_command'])) {
    $core_instance = \ASAPDigest\Core\ASAP_Digest_Core::get_instance();
    $GLOBALS['asap_digest_central_command'] = new \ASAPDigest\Core\ASAP_Digest_Central_Command($core_instance);
}
$asap_digest_central_command = $GLOBALS['asap_digest_central_command'];

// Register the menu with admin_menu (single location, per protocol)
add_action('admin_menu', [$asap_digest_central_command, 'register_menus'], 30);

/**
 * Add necessary CORS headers for WPGraphQL OPTIONS preflight requests.
 * Checks if the request origin is whitelisted and adds required headers.
 * Environment-aware for SvelteKit frontend origins.
 *
 * @param array $headers Existing headers potentially set by WPGraphQL or other filters.
 * @return array Modified headers array.
 */
function asap_filter_graphql_cors_headers( $headers ) {
    // Only act on OPTIONS preflight requests
    if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'OPTIONS' ) {
        return $headers;
    }

    // Determine allowed origin based on environment
    $allowed_origin = '';
    $current_env = wp_get_environment_type(); // development, staging, production, local

    if ( $current_env === 'development' || $current_env === 'local' ) {
        $allowed_origin = 'https://localhost:5173';
    } elseif ( $current_env === 'production' ) {
        $allowed_origin = 'https://app.asapdigest.com';
    } // Add 'staging' environment if needed later

    // Get the origin of the request
    $request_origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? trim( rtrim( $_SERVER['HTTP_ORIGIN'], '/' ) ) : ''; // Trim trailing slash

    // If the request origin matches our allowed origin for the environment...
    if ( ! empty( $request_origin ) && $request_origin === $allowed_origin ) {

        // Set the necessary CORS headers, potentially overriding WPGraphQL defaults if needed
        $headers['Access-Control-Allow-Origin'] = $allowed_origin; // Explicitly set allowed origin
        $headers['Access-Control-Allow-Methods'] = 'POST, GET, OPTIONS';
        $headers['Access-Control-Allow-Credentials'] = 'true';
        // Ensure Allow-Headers includes what the request asked for (Content-Type) and others potentially needed
        $headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type, X-WPGraphQL-Login-Token, X-WPGraphQL-Login-Refresh-Token, X-Better-Auth-Signature';
        // Optional: Add Max-Age
        // $headers['Access-Control-Max-Age'] = '600';

        error_log('[ASAP CORS Filter V3] Added CORS headers for GraphQL OPTIONS request from whitelisted origin: ' . $request_origin);

    } else {
         // Log why we didn't add headers if debugging is needed
         // error_log('[ASAP CORS Filter V3] Skipped adding CORS headers. Request Origin: \'' . $request_origin . '\' | Allowed Origin: \'' . $allowed_origin . '\'');
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