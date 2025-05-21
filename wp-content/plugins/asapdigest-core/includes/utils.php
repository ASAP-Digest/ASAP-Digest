<?php
/**
 * ASAP Digest Core Plugin Utilities.
 *
 * Provides common utility functions used throughout the plugin.
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

/**
 * Get the prefixed table name for a custom ASAP Digest table.
 *
 * @since 1.0.0
 *
 * @param string $table_suffix The suffix of the table name (e.g., 'ingested_content').
 * @return string The full prefixed table name.
 */
function asap_digest_get_table_name( $table_suffix ) {
	global $wpdb;
	return $wpdb->prefix . 'asap_' . $table_suffix;
}

/**
 * Log a debug message with an optional context.
 *
 * @since 1.0.0
 *
 * @param string $message The message to log.
 * @param mixed  $context Optional context data.
 */
function asap_digest_debug_log( $message, $context = null ) {
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG === true ) {
		$log_message = 'ASAP_CORE_DEBUG: ' . $message;
		if ( ! is_null( $context ) ) {
			$log_message .= '\n' . print_r( $context, true );
		}
		error_log( $log_message );
	}
}

// Add more utility functions as needed based on project requirements. 