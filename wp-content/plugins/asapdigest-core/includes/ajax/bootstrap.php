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
    
    // Add debug action for AI AJAX handler
    add_action('admin_notices', function() {
        if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'ai-settings') {
            echo '<div class="notice notice-info is-dismissible"><p>AI AJAX handler debug: ';
            
            // Check if the handler is registered
            $exists = has_action('wp_ajax_asap_test_ai_connection');
            if ($exists) {
                echo 'AI connection test handler is properly registered.';
            } else {
                echo '<strong style="color:red;">Warning:</strong> AI connection test handler is NOT registered!';
            }
            
            echo '</p></div>';
        }
    });
    
    // Initialize all handlers
    $ajax_manager->init();
    
    return $ajax_manager;
}

// Initialize AJAX system
add_action('init', 'asap_digest_init_ajax_handlers', 20); 