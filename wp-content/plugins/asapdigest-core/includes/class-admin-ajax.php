<?php
/**
 * ASAP Digest Admin AJAX Handler
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 */

namespace ASAPDigest\Core;

use WP_Error;
use function add_action;
use function check_ajax_referer;
use function current_user_can;
use function wp_send_json_error;
use function wp_send_json_success;
use ASAPDigest\Core\ErrorLogger;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Admin_Ajax {
    /**
     * @var ASAP_Digest_Core Core plugin instance
     */
    private $core;

    /**
     * Constructor
     * 
     * @param ASAP_Digest_Core $core Core plugin instance
     */
    public function __construct($core) {
        $this->core = $core;

        // Register AJAX actions
        add_action('wp_ajax_asap_digest_send_test', [$this, 'handle_send_test']);
        add_action('wp_ajax_asap_digest_preview_next', [$this, 'handle_preview_next']);
        add_action('wp_ajax_asap_digest_save_settings', [$this, 'handle_save_settings']);
        add_action('wp_ajax_asap_digest_export_stats', [$this, 'handle_export_stats']);
        add_action('wp_ajax_asap_digest_reset_settings', [$this, 'handle_reset_settings']);
    }

    /**
     * Handle sending a test digest
     */
    public function handle_send_test() {
        $this->verify_request();

        $result = $this->core->send_test_digest();
        if (is_wp_error($result)) {
            /**
             * Log test digest error using ErrorLogger utility.
             * Context: 'admin_ajax', error_type: 'send_test_failed', severity: 'error'.
             * Includes error message for debugging.
             */
            ErrorLogger::log('admin_ajax', 'send_test_failed', $result->get_error_message(), [], 'error');
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        }

        wp_send_json_success([
            'message' => __('Test digest sent successfully!', 'asap-digest')
        ]);
    }

    /**
     * Handle previewing next digest
     */
    public function handle_preview_next() {
        $this->verify_request();

        $preview = $this->core->get_next_digest_preview();
        if (is_wp_error($preview)) {
            /**
             * Log preview error using ErrorLogger utility.
             * Context: 'admin_ajax', error_type: 'preview_next_failed', severity: 'error'.
             * Includes error message for debugging.
             */
            ErrorLogger::log('admin_ajax', 'preview_next_failed', $preview->get_error_message(), [], 'error');
            wp_send_json_error([
                'message' => $preview->get_error_message()
            ]);
        }

        wp_send_json_success([
            'preview' => $preview
        ]);
    }

    /**
     * Handle saving settings
     */
    public function handle_save_settings() {
        $this->verify_request();

        $settings = filter_input_array(INPUT_POST, [
            'frequency' => FILTER_SANITIZE_STRING,
            'send_time' => FILTER_SANITIZE_STRING,
            'max_posts' => FILTER_VALIDATE_INT,
            'categories' => [
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY
            ],
            'session_length' => FILTER_VALIDATE_INT,
            'refresh_token_length' => FILTER_VALIDATE_INT,
            'max_sessions' => FILTER_VALIDATE_INT
        ]);

        $result = $this->core->update_settings($settings);
        if (is_wp_error($result)) {
            /**
             * Log save settings error using ErrorLogger utility.
             * Context: 'admin_ajax', error_type: 'save_settings_failed', severity: 'error'.
             * Includes error message and settings for debugging.
             */
            ErrorLogger::log('admin_ajax', 'save_settings_failed', $result->get_error_message(), [ 'settings' => $settings ], 'error');
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        }

        wp_send_json_success([
            'message' => __('Settings saved successfully!', 'asap-digest')
        ]);
    }

    /**
     * Handle exporting stats
     */
    public function handle_export_stats() {
        $this->verify_request();

        $format = filter_input(INPUT_POST, 'format', FILTER_SANITIZE_STRING);
        if (!in_array($format, ['csv', 'json'])) {
            /**
             * Log invalid export format using ErrorLogger utility.
             * Context: 'admin_ajax', error_type: 'invalid_export_format', severity: 'warning'.
             * Includes format for debugging.
             */
            ErrorLogger::log('admin_ajax', 'invalid_export_format', 'Invalid export format', [ 'format' => $format ], 'warning');
            wp_send_json_error([
                'message' => __('Invalid export format.', 'asap-digest')
            ]);
        }

        $stats = $this->core->get_stats();
        if (is_wp_error($stats)) {
            /**
             * Log export stats error using ErrorLogger utility.
             * Context: 'admin_ajax', error_type: 'export_stats_failed', severity: 'error'.
             * Includes error message for debugging.
             */
            ErrorLogger::log('admin_ajax', 'export_stats_failed', $stats->get_error_message(), [], 'error');
            wp_send_json_error([
                'message' => $stats->get_error_message()
            ]);
        }

        if ($format === 'json') {
            wp_send_json_success([
                'data' => $stats,
                'filename' => 'asap-digest-stats-' . date('Y-m-d') . '.json'
            ]);
        }

        // Format CSV
        $csv = $this->format_stats_csv($stats);
        wp_send_json_success([
            'data' => $csv,
            'filename' => 'asap-digest-stats-' . date('Y-m-d') . '.csv'
        ]);
    }

    /**
     * Handle resetting settings
     */
    public function handle_reset_settings() {
        $this->verify_request();

        $result = $this->core->reset_settings();
        if (is_wp_error($result)) {
            /**
             * Log reset settings error using ErrorLogger utility.
             * Context: 'admin_ajax', error_type: 'reset_settings_failed', severity: 'error'.
             * Includes error message for debugging.
             */
            ErrorLogger::log('admin_ajax', 'reset_settings_failed', $result->get_error_message(), [], 'error');
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        }

        wp_send_json_success([
            'message' => __('Settings reset successfully!', 'asap-digest')
        ]);
    }

    /**
     * Format stats for CSV export
     * 
     * @param array $stats Stats data
     * @return string CSV formatted data
     */
    private function format_stats_csv($stats) {
        $csv = [];
        
        // Headers
        $csv[] = implode(',', [
            'Date',
            'Digests Sent',
            'Posts Included',
            'Unique Users',
            'Event Type'
        ]);

        // Data rows
        foreach ($stats['events'] as $event) {
            $csv[] = implode(',', [
                esc_csv($event['date']),
                esc_csv($event['digests_sent']),
                esc_csv($event['posts_included']),
                esc_csv($event['unique_users']),
                esc_csv($event['type'])
            ]);
        }

        return implode("\n", $csv);
    }

    /**
     * Verify AJAX request
     * 
     * @throws WP_Error If request verification fails
     */
    private function verify_request() {
        check_ajax_referer('asap_digest_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'asap-digest')
            ]);
        }
    }
}

/**
 * Helper function to escape CSV values
 * 
 * @param mixed $value Value to escape
 * @return string Escaped value
 */
function esc_csv($value) {
    $value = str_replace('"', '""', $value);
    return '"' . $value . '"';
} 