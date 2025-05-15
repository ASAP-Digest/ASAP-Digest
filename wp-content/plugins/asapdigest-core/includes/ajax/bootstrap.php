<?php
/**
 * ASAP Digest AJAX System Bootstrap
 *
 * Initialize and register all AJAX handlers
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize and load all AJAX handler classes
 * 
 * @since 3.0.0
 * @return \AsapDigest\Core\Ajax\AJAX_Manager|null
 */
function asap_digest_init_ajax_handlers() {
    // Load required files
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/class-base-ajax.php';
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/class-ajax-manager.php';
    
    // Load admin AJAX handlers
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-admin-ajax.php';
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-ai-ajax.php';
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-content-ajax.php';
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-quality-ajax.php';
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-source-ajax.php';
    
    // Load user AJAX handlers
    require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/user/class-user-actions-ajax.php';
    
    // Get core instance if available
    $core = function_exists('asap_digest_core') ? asap_digest_core() : null;
    
    // Initialize AJAX Manager
    $ajax_manager = new \AsapDigest\Core\Ajax\AJAX_Manager($core);
    
    // Register AJAX handlers
    $ajax_manager->register_handler(new \AsapDigest\Core\Ajax\Admin\Admin_Ajax($core));
    $ajax_manager->register_handler(new \AsapDigest\Core\Ajax\Admin\AI_Ajax());
    $ajax_manager->register_handler(new \AsapDigest\Core\Ajax\Admin\Content_Ajax());
    $ajax_manager->register_handler(new \AsapDigest\Core\Ajax\Admin\Quality_Ajax());
    $ajax_manager->register_handler(new \AsapDigest\Core\Ajax\Admin\Source_Ajax());
    $ajax_manager->register_handler(new \AsapDigest\Core\Ajax\User\User_Actions_Ajax());
    
    // Initialize all handlers
    $ajax_manager->init();
    
    return $ajax_manager;
}

// Initialize AJAX system
add_action('init', 'asap_digest_init_ajax_handlers', 20); 