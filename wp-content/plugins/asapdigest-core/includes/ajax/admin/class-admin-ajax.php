<?php
/**
 * ASAP Digest Admin AJAX Handler
 *
 * Standardized handler for general admin AJAX operations
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax\Admin;

use AsapDigest\Core\Ajax\Base_AJAX;
use AsapDigest\Core\ErrorLogger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin AJAX Handler Class
 *
 * Handles general admin AJAX requests
 *
 * @since 3.0.0
 */
class Admin_Ajax extends Base_AJAX {
    
    /**
     * Required capability for this handler
     *
     * @var string
     */
    protected $capability = 'manage_options';
    
    /**
     * Core plugin instance
     *
     * @var object
     */
    private $core;
    
    /**
     * Nonce action for this handler
     *
     * @var string
     */
    protected $nonce_action = 'asap_digest_admin';
    
    /**
     * Initialize the handler
     *
     * @since 3.0.0
     * @param object $core Core plugin instance
     */
    public function __construct($core = null) {
        $this->core = $core;
        parent::__construct();
    }
    
    /**
     * Register AJAX actions
     *
     * @since 3.0.0
     * @return void
     */
    protected function register_actions() {
        add_action('wp_ajax_asap_digest_send_test', [$this, 'handle_send_test']);
        add_action('wp_ajax_asap_digest_preview_next', [$this, 'handle_preview_next']);
        add_action('wp_ajax_asap_digest_save_settings', [$this, 'handle_save_settings']);
        add_action('wp_ajax_asap_digest_export_stats', [$this, 'handle_export_stats']);
        add_action('wp_ajax_asap_digest_reset_settings', [$this, 'handle_reset_settings']);
    }
    
    /**
     * Handle sending a test digest
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_send_test() {
        // Verify request
        $this->verify_request();
        
        try {
            if (!$this->core) {
                throw new \Exception(__('Core plugin instance not available.', 'asapdigest-core'));
            }
            
            $result = $this->core->send_test_digest();
            
            if (is_wp_error($result)) {
                ErrorLogger::log('admin_ajax', 'send_test_failed', $result->get_error_message(), [], 'error');
                
                $this->send_error([
                    'message' => $result->get_error_message(),
                    'code' => 'send_test_failed'
                ]);
            }
            
            $this->send_success([
                'message' => __('Test digest sent successfully!', 'asapdigest-core')
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('admin_ajax', 'send_test_exception', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while sending the test digest.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle previewing next digest
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_preview_next() {
        // Verify request
        $this->verify_request();
        
        try {
            if (!$this->core) {
                throw new \Exception(__('Core plugin instance not available.', 'asapdigest-core'));
            }
            
            $preview = $this->core->get_next_digest_preview();
            
            if (is_wp_error($preview)) {
                ErrorLogger::log('admin_ajax', 'preview_next_failed', $preview->get_error_message(), [], 'error');
                
                $this->send_error([
                    'message' => $preview->get_error_message(),
                    'code' => 'preview_failed'
                ]);
            }
            
            $this->send_success([
                'preview' => $preview
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('admin_ajax', 'preview_next_exception', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while generating the preview.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle saving settings
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_save_settings() {
        // Verify request
        $this->verify_request();
        
        try {
            if (!$this->core) {
                throw new \Exception(__('Core plugin instance not available.', 'asapdigest-core'));
            }
            
            // Validate and sanitize input
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
            
            // Log settings
            ErrorLogger::log('admin_ajax', 'save_settings_request', 'Settings save request received', [
                'settings' => $settings
            ], 'info');
            
            $result = $this->core->update_settings($settings);
            
            if (is_wp_error($result)) {
                ErrorLogger::log('admin_ajax', 'save_settings_failed', $result->get_error_message(), [
                    'settings' => $settings
                ], 'error');
                
                $this->send_error([
                    'message' => $result->get_error_message(),
                    'code' => 'save_settings_failed'
                ]);
            }
            
            $this->send_success([
                'message' => __('Settings saved successfully!', 'asapdigest-core'),
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('admin_ajax', 'save_settings_exception', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while saving settings.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle exporting stats
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_export_stats() {
        // Verify request
        $this->verify_request();
        
        try {
            if (!$this->core) {
                throw new \Exception(__('Core plugin instance not available.', 'asapdigest-core'));
            }
            
            // Validate format
            $format = filter_input(INPUT_POST, 'format', FILTER_SANITIZE_STRING);
            if (!in_array($format, ['csv', 'json'])) {
                ErrorLogger::log('admin_ajax', 'invalid_export_format', 'Invalid export format', [
                    'format' => $format
                ], 'warning');
                
                $this->send_error([
                    'message' => __('Invalid export format.', 'asapdigest-core'),
                    'code' => 'invalid_format'
                ]);
            }
            
            // Get stats
            $stats = $this->core->get_stats();
            
            if (is_wp_error($stats)) {
                ErrorLogger::log('admin_ajax', 'export_stats_failed', $stats->get_error_message(), [], 'error');
                
                $this->send_error([
                    'message' => $stats->get_error_message(),
                    'code' => 'export_stats_failed'
                ]);
            }
            
            // Format response based on requested format
            if ($format === 'json') {
                $this->send_success([
                    'data' => $stats,
                    'filename' => 'asap-digest-stats-' . date('Y-m-d') . '.json'
                ]);
            } else {
                // Format CSV
                $csv = $this->format_stats_csv($stats);
                
                $this->send_success([
                    'data' => $csv,
                    'filename' => 'asap-digest-stats-' . date('Y-m-d') . '.csv'
                ]);
            }
        } catch (\Exception $e) {
            ErrorLogger::log('admin_ajax', 'export_stats_exception', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while exporting stats.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle resetting settings
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_reset_settings() {
        // Verify request
        $this->verify_request();
        
        try {
            if (!$this->core) {
                throw new \Exception(__('Core plugin instance not available.', 'asapdigest-core'));
            }
            
            $result = $this->core->reset_settings();
            
            if (is_wp_error($result)) {
                ErrorLogger::log('admin_ajax', 'reset_settings_failed', $result->get_error_message(), [], 'error');
                
                $this->send_error([
                    'message' => $result->get_error_message(),
                    'code' => 'reset_settings_failed'
                ]);
            }
            
            $this->send_success([
                'message' => __('Settings reset successfully!', 'asapdigest-core')
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('admin_ajax', 'reset_settings_exception', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while resetting settings.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Format stats for CSV export
     *
     * @since 3.0.0
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
                $this->esc_csv($event['date']),
                $this->esc_csv($event['digests_sent']),
                $this->esc_csv($event['posts_included']),
                $this->esc_csv($event['unique_users']),
                $this->esc_csv($event['type'])
            ]);
        }
        
        return implode("\n", $csv);
    }
    
    /**
     * Helper function to escape CSV values
     *
     * @since 3.0.0
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    private function esc_csv($value) {
        $value = str_replace('"', '""', $value);
        return '"' . $value . '"';
    }
} 