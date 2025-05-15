<?php
/**
 * Error Logger Class
 *
 * Provides centralized error logging functionality for the ASAP Digest plugin.
 * Logs errors to database and optionally to PHP error log.
 *
 * @package ASAPDigest_Core
 * @since 2.3.0
 * @created 05.22.25 | 10:15 AM PDT
 * @file-marker ASAP_Digest_ErrorLogger
 */

namespace ASAPDigest\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ErrorLogger
 * 
 * Static utility class for logging errors with context and severity.
 */
class ErrorLogger {
    /**
     * Log table name
     * 
     * @var string
     */
    private static $table_name = 'wp_asap_error_log';
    
    /**
     * Whether to also log to PHP error log
     * 
     * @var bool
     */
    private static $use_error_log = true;
    
    /**
     * Log an error
     * 
     * @param string $context Error context (e.g., 'ai_service', 'content_processor')
     * @param string $error_type Error type or code (e.g., 'no_provider', 'api_error')
     * @param string $message Error message
     * @param array $data Additional data for context
     * @param string $severity Error severity ('debug', 'info', 'warning', 'error', 'critical')
     * @return bool Success
     */
    public static function log($context, $error_type, $message, $data = [], $severity = 'error') {
        global $wpdb;
        
        // Validate severity
        $valid_severities = ['debug', 'info', 'warning', 'error', 'critical'];
        if (!in_array($severity, $valid_severities)) {
            $severity = 'error'; // Default to error
        }
        
        // Always log critical errors to PHP error log
        if ($severity === 'critical' || self::$use_error_log) {
            $log_message = sprintf(
                '[ASAP ERROR] [%s] [%s] [%s]: %s | %s',
                $context,
                $error_type,
                $severity,
                $message,
                json_encode($data)
            );
            error_log($log_message);
        }
        
        // Only log to database if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . self::$table_name . "'") === self::$table_name;
        
        if (!$table_exists) {
            // Attempt to create the table if it doesn't exist
            self::maybe_create_table();
            
            // Check again if table creation was successful
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . self::$table_name . "'") === self::$table_name;
            
            if (!$table_exists) {
                // Table couldn't be created, just log to PHP error log
                error_log('[ASAP ERROR] Error log table does not exist and could not be created.');
                return false;
            }
        }
        
        // Insert error into database
        $result = $wpdb->insert(
            self::$table_name,
            [
                'context' => $context,
                'error_type' => $error_type,
                'message' => $message,
                'data' => is_array($data) || is_object($data) ? json_encode($data) : (string)$data,
                'severity' => $severity,
                'created_at' => current_time('mysql'),
                'user_id' => get_current_user_id(),
            ],
            [
                '%s', // context
                '%s', // error_type
                '%s', // message
                '%s', // data
                '%s', // severity
                '%s', // created_at
                '%d', // user_id
            ]
        );
        
        return $result !== false;
    }
    
    /**
     * Create the error log table if it doesn't exist
     * 
     * @return bool Success
     */
    public static function maybe_create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE " . self::$table_name . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            context varchar(50) NOT NULL,
            error_type varchar(50) NOT NULL,
            message text NOT NULL,
            data longtext DEFAULT NULL,
            severity varchar(20) NOT NULL DEFAULT 'error',
            created_at datetime NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY  (id),
            KEY context (context),
            KEY error_type (error_type),
            KEY severity (severity),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        return dbDelta($sql) ? true : false;
    }
    
    /**
     * Get recent errors
     * 
     * @param int $limit Number of errors to retrieve
     * @param string $severity_min Minimum severity level
     * @param string $context Filter by context
     * @return array Recent errors
     */
    public static function get_recent_errors($limit = 50, $severity_min = 'error', $context = '') {
        global $wpdb;
        
        $valid_severities = ['debug', 'info', 'warning', 'error', 'critical'];
        $severity_index = array_search($severity_min, $valid_severities);
        
        if ($severity_index === false) {
            $severity_index = 3; // Default to 'error'
        }
        
        // Get severities at or above the minimum level
        $severity_levels = array_slice($valid_severities, $severity_index);
        $severity_placeholders = implode(',', array_fill(0, count($severity_levels), '%s'));
        
        $query = "SELECT * FROM " . self::$table_name . " WHERE severity IN ($severity_placeholders)";
        $params = $severity_levels;
        
        if (!empty($context)) {
            $query .= " AND context = %s";
            $params[] = $context;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = (int)$limit;
        
        $prepared = $wpdb->prepare($query, $params);
        return $wpdb->get_results($prepared);
    }
    
    /**
     * Clear old error logs
     * 
     * @param int $days_to_keep Number of days of logs to keep
     * @return int Number of rows deleted
     */
    public static function clear_old_logs($days_to_keep = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_to_keep days"));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::$table_name . " WHERE created_at < %s",
                $cutoff_date
            )
        );
    }
    
    /**
     * Set whether to use PHP error_log
     * 
     * @param bool $use Whether to use PHP error_log
     */
    public static function set_use_error_log($use) {
        self::$use_error_log = (bool)$use;
    }
} 