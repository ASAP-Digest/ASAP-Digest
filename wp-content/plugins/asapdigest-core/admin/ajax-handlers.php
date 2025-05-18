<?php
/**
 * AJAX Handlers for ASAP Digest Admin
 *
 * @package ASAP_Digest
 * @subpackage Admin
 * @since 2.2.0
 * @deprecated 3.0.0 Use the new AJAX handler system in includes/ajax/
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Log deprecation notice
if (WP_DEBUG) {
    trigger_error(
        sprintf(
            __('The file %s is deprecated since version %s! Use the new AJAX handler system in includes/ajax/ instead.', 'asapdigest-core'),
            __FILE__,
            '3.0.0'
        ),
        E_USER_DEPRECATED
    );
}

// Load the new AJAX system instead
require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/bootstrap.php';

// Set up deprecated function stubs that redirect to new system
// Legacy functions are kept as stubs to maintain backward compatibility
// Each function logs a deprecation notice and includes the proper replacement

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_get_content_details
 */
function asap_digest_ajax_get_content_details() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_get_content_details');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_search_content
 */
function asap_digest_ajax_search_content() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_search_content');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_bulk_action_content
 */
function asap_digest_ajax_bulk_action_content() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_bulk_action_content');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_reindex_content
 */
function asap_digest_ajax_reindex_content() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Content_Ajax::handle_reindex_content');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_sources
 */
function asap_digest_ajax_get_sources() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_sources');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_source
 */
function asap_digest_ajax_get_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_save_source
 */
function asap_digest_ajax_save_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_save_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_delete_source
 */
function asap_digest_ajax_delete_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_delete_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_run_source
 */
function asap_digest_ajax_run_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_run_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Quality_Ajax::handle_get_quality_settings
 */
function asap_digest_ajax_get_quality_settings() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Quality_Ajax::handle_get_quality_settings');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Quality_Ajax::handle_save_quality_settings
 */
function asap_digest_ajax_save_quality_settings() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Quality_Ajax::handle_save_quality_settings');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_content_sources
 */
function asap_digest_ajax_get_content_sources() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_content_sources');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_content_source
 */
function asap_digest_ajax_get_content_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_get_content_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_add_content_source
 */
function asap_digest_ajax_add_content_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_add_content_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_update_content_source
 */
function asap_digest_ajax_update_content_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_update_content_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_delete_content_source
 */
function asap_digest_ajax_delete_content_source() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_delete_content_source');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * @deprecated 3.0.0 Use AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_trigger_content_crawler
 */
function asap_digest_ajax_trigger_content_crawler() {
    _deprecated_function(__FUNCTION__, '3.0.0', 'AsapDigest\Core\Ajax\Admin\Source_Ajax::handle_trigger_content_crawler');
    // The functionality is now handled by the class-based AJAX handler system
}

/**
 * Updates a Hugging Face model's verification status
 * 
 * @since 3.1.0
 * @return void
 */
function handle_update_hf_model_verification() {
    // Check nonce
    if (!check_ajax_referer('asap_digest_content_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => 'Invalid security token.',
            'code' => 'invalid_nonce'
        ), 403);
    }
    
    // Check for required parameters
    $model_id = isset($_POST['model_id']) ? sanitize_text_field($_POST['model_id']) : '';
    $is_verified = isset($_POST['is_verified']) ? (bool)$_POST['is_verified'] : false;
    
    if (empty($model_id)) {
        wp_send_json_error(array(
            'message' => 'Missing required parameter: model_id',
            'code' => 'missing_parameter'
        ), 400);
    }
    
    // Get current verified and failed models
    $verified_models = get_option('asap_ai_verified_huggingface_models', array());
    $failed_models = get_option('asap_ai_failed_huggingface_models', array());
    
    if ($is_verified) {
        // Add to verified models if not already there
        if (!in_array($model_id, $verified_models)) {
            $verified_models[] = $model_id;
            update_option('asap_ai_verified_huggingface_models', $verified_models);
        }
        
        // Remove from failed models if it was there
        if (in_array($model_id, $failed_models)) {
            $failed_models = array_diff($failed_models, array($model_id));
            update_option('asap_ai_failed_huggingface_models', $failed_models);
        }
        
        wp_send_json_success(array(
            'message' => 'Model marked as verified',
            'model_id' => $model_id,
            'status' => 'verified'
        ));
    } else {
        // Mark as failed
        if (!in_array($model_id, $failed_models)) {
            $failed_models[] = $model_id;
            update_option('asap_ai_failed_huggingface_models', $failed_models);
        }
        
        // Remove from verified models if it was there
        if (in_array($model_id, $verified_models)) {
            $verified_models = array_diff($verified_models, array($model_id));
            update_option('asap_ai_verified_huggingface_models', $verified_models);
        }
        
        wp_send_json_success(array(
            'message' => 'Model marked as failed',
            'model_id' => $model_id,
            'status' => 'failed'
        ));
    }
}

/**
 * Handle removing all failed models
 * 
 * @since 3.1.0
 * @return void
 */
function handle_remove_failed_models() {
    // Check nonce
    if (!check_ajax_referer('asap_digest_content_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => 'Invalid security token.',
            'code' => 'invalid_nonce'
        ), 403);
    }
    
    // Get failed models
    $failed_models = get_option('asap_ai_failed_huggingface_models', array());
    
    if (empty($failed_models)) {
        wp_send_json_success(array(
            'message' => 'No failed models to remove',
            'removed_count' => 0
        ));
    }
    
    // Get custom models
    $custom_models = get_option('asap_ai_custom_huggingface_models', array());
    
    // Remove failed models from custom models
    $removed_count = 0;
    foreach ($failed_models as $model_id) {
        if (isset($custom_models[$model_id])) {
            unset($custom_models[$model_id]);
            $removed_count++;
        }
    }
    
    // Update custom models
    update_option('asap_ai_custom_huggingface_models', $custom_models);
    
    // Clear failed models
    update_option('asap_ai_failed_huggingface_models', array());
    
    wp_send_json_success(array(
        'message' => sprintf(_n(
            '%d failed model removed successfully',
            '%d failed models removed successfully',
            $removed_count,
            'asapdigest-core'
        ), $removed_count),
        'removed_count' => $removed_count,
        'failed_models' => $failed_models
    ));
}

// Register the AJAX handlers
add_action('wp_ajax_asap_update_hf_model_verification', 'handle_update_hf_model_verification');
add_action('wp_ajax_asap_remove_failed_models', 'handle_remove_failed_models');

// Do not add action hooks for these deprecated functions
// The new AJAX handler system registers its own hooks 