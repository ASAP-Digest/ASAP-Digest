<?php
/**
 * Content Processing Bootstrap
 *
 * Initializes the content processing pipeline components.
 *
 * @package ASAP_Digest
 * @subpackage Content_Processing
 * @since 2.2.0
 * @file-marker ASAP_Digest_Content_Processing_Bootstrap
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize content processing components
 */
function asap_digest_init_content_processing() {
    // Include component files
    require_once plugin_dir_path(__FILE__) . 'class-content-validator.php';
    require_once plugin_dir_path(__FILE__) . 'class-content-deduplicator.php';
    require_once plugin_dir_path(__FILE__) . 'class-content-processor.php';
    
    // Register hooks related to content processing
    add_action('asap_content_added', 'asap_digest_log_content_action', 10, 2);
    add_action('asap_content_updated', 'asap_digest_log_content_action', 10, 2);
    add_action('asap_content_deleted', 'asap_digest_log_content_action', 10, 2);
}

/**
 * Log content actions for audit purposes
 *
 * @param int $content_id Content ID
 * @param array $content_data Content data
 */
function asap_digest_log_content_action($content_id, $content_data) {
    // Basic logging implementation
    $action = current_action();
    $user_id = get_current_user_id();
    $time = current_time('mysql');
    
    // Log to activity log table if available
    global $wpdb;
    $table = $wpdb->prefix . 'asap_user_activity_log';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'object_type' => 'content',
                'object_id' => $content_id,
                'details' => wp_json_encode(array(
                    'title' => isset($content_data['title']) ? $content_data['title'] : '',
                    'type' => isset($content_data['type']) ? $content_data['type'] : '',
                )),
                'created_at' => $time,
            )
        );
    }
    
    // You can also use WordPress error log for debugging
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log(sprintf(
            'ASAP Content Action: %s, ID: %d, User: %d, Title: %s',
            $action,
            $content_id,
            $user_id,
            isset($content_data['title']) ? $content_data['title'] : 'Unknown'
        ));
    }
}

/**
 * Get content processor instance
 * 
 * @return ASAP_Digest_Content_Processor Content processor instance
 */
function asap_digest_get_content_processor() {
    static $processor = null;
    
    if ($processor === null) {
        $processor = new ASAP_Digest_Content_Processor();
    }
    
    return $processor;
}

// Initialize the content processing pipeline
asap_digest_init_content_processing(); 