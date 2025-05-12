<?php
/**
 * ASAP Digest Error Logger Utility
 *
 * Provides unified error logging for all plugin subsystems.
 * Logs errors to the database (wp_asap_error_log) and/or PHP error log.
 *
 * Usage:
 *   ErrorLogger::log('context', 'error_type', 'Error message', ['extra' => 'data'], 'critical');
 *
 * @package ASAPDigest_Core
 * @since 2.4.0
 */

namespace ASAPDigest\Core;

if (!defined('ABSPATH')) {
    exit;
}

class ErrorLogger {
    /**
     * Log an error to the database and/or PHP error log
     *
     * @param string $context   Subsystem or feature context (e.g. 'crawler', 'ai', 'api')
     * @param string $error_type Short error type or code (e.g. 'db_error', 'api_failure')
     * @param string $message   Human-readable error message
     * @param array  $data      Optional. Additional structured data (stack trace, args, etc)
     * @param string $severity  Optional. Severity: 'info', 'warning', 'error', 'critical'. Default 'error'.
     * @param bool   $php_log   Optional. Also log to PHP error log. Default true for error/critical, false for info/warning.
     * @return void
     */
    public static function log($context, $error_type, $message, $data = [], $severity = 'error', $php_log = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_error_log';
        $now = current_time('mysql', 1);
        $data_json = is_array($data) ? wp_json_encode($data) : (string)$data;

        // Insert into DB
        $wpdb->insert($table, [
            'context'   => sanitize_text_field($context),
            'error_type'=> sanitize_text_field($error_type),
            'message'   => sanitize_text_field($message),
            'data'      => $data_json,
            'severity'  => sanitize_text_field($severity),
            'created_at'=> $now,
        ]);

        // Fallback to PHP error log if DB insert fails
        if ($wpdb->last_error) {
            error_log("[ASAP ErrorLogger] DB log failed: {$wpdb->last_error}. Original error: [$context/$error_type] $message");
        }

        // Log to PHP error log if severity is error/critical, or if explicitly requested
        if ($php_log === null) {
            $php_log = in_array($severity, ['error', 'critical']);
        }
        if ($php_log) {
            $log_line = sprintf('[ASAP %s][%s][%s] %s | Data: %s', strtoupper($severity), $context, $error_type, $message, $data_json);
            error_log($log_line);
        }
    }
} 