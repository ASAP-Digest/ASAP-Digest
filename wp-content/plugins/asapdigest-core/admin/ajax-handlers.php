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

// Do not add action hooks for these deprecated functions
// The new AJAX handler system registers its own hooks 