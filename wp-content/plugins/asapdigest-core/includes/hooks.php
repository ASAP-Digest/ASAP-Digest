<?php
/**
 * ASAP Digest Core Plugin Hooks.
 *
 * Registers WordPress actions and filters used by the plugin.
 *
 * @package   ASAPDigest\Core
 * @since     1.0.0
 * @copyright 2023-present ASAP Digest
 * @license   GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Core Plugin Hooks ---
// These hooks are essential for the plugin's core functionality.
// Avoid adding component-specific hooks here.

// Register activation and deactivation hooks.
// These are typically called from the main plugin file (asapdigest-core.php)
// but the function definitions would be in class-activator.php.
// register_activation_hook( ASAP_DIGEST_CORE_PLUGIN_FILE, [ 'ASAPDigest\Core\ASAP_Digest_Activator', 'activate' ] );
// register_deactivation_hook( ASAP_DIGEST_CORE_PLUGIN_FILE, [ 'ASAPDigest\Core\ASAP_Digest_Activator', 'deactivate' ] );

// Hook into plugins_loaded to get the core plugin instance.
// The debug log shows asap_digest_core() is called here.
// add_action( 'plugins_loaded', 'asap_digest_core' );

// Hook into init for tasks like registering CPTs, taxonomies, etc.
// add_action( 'init', [ 'ASAPDigest\Core\ASAP_Digest_Core', 'init_plugin' ] ); // Example if init_plugin method exists

// Hook into rest_api_init to register custom REST API routes.
// add_action( 'rest_api_init', [ 'ASAPDigest\Core\API\ASAP_Digest_REST_Base', 'register_routes' ] ); // Example base registration
// add_action( 'rest_api_init', [ 'ASAPDigest\Core\API\class-rest-ingested-content', 'register_routes' ] ); // Example specific controller

// Hook into admin_menu to add admin pages.
// add_action( 'admin_menu', [ 'ASAPDigest\Admin\ASAP_Digest_Central_Command', 'add_plugin_admin_menu' ] );

// --- Component-Specific Hooks ---
// Hooks related to specific components (e.g., Crawler, AI, UI) should ideally be registered within those components' classes.
// However, if standalone functions are used, they can be added here.

// Example: Hook for content processing (from includes/content-processing/bootstrap.php)
// add_action('asap_content_crawled', 'asap_digest_process_crawled_content', 10, 1);

// Example: Filter for AI content enhancement (from includes/ai/bootstrap.php)
// add_filter('asapdigest_content_processed', 'ASAPDigest\\AI\\filter_enhance_content', 10, 2);

// Add more action and filter registrations as needed. 