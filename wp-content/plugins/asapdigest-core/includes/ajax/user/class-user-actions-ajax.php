<?php
/**
 * ASAP Digest User Actions AJAX Handler
 *
 * Standardized handler for user-related AJAX operations
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax\User;

use AsapDigest\Core\Ajax\Base_AJAX;
use AsapDigest\Core\ErrorLogger;
use WP_Session_Tokens;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Actions AJAX Handler Class
 *
 * Handles all AJAX requests related to user management
 *
 * @since 3.0.0
 */
class User_Actions_Ajax extends Base_AJAX {
    
    /**
     * Required capability for this handler
     *
     * @var string
     */
    protected $capability = 'manage_options';
    
    /**
     * Register AJAX actions
     *
     * @since 3.0.0
     * @return void
     */
    protected function register_actions() {
        add_action('wp_ajax_asap_ban_user', [$this, 'handle_ban_user']);
        add_action('wp_ajax_asap_lock_account', [$this, 'handle_lock_account']);
        add_action('wp_ajax_asap_reset_password', [$this, 'handle_reset_password']);
        add_action('wp_ajax_asap_view_activity', [$this, 'handle_view_activity']);
    }
    
    /**
     * Handle ban user action
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_ban_user() {
        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        // Verify request with user-specific nonce
        $this->verify_request('nonce', 'manage_options');
        
        // Additional nonce check for user-specific action
        if (!wp_verify_nonce($_POST['nonce'], 'ban_user_' . $user_id)) {
            $this->send_error([
                'message' => __('Invalid security token.', 'asapdigest-core'),
                'code' => 'invalid_nonce'
            ], 400);
        }
        
        try {
            // Validate user ID
            if ($user_id <= 0) {
                $this->send_error([
                    'message' => __('Invalid user ID.', 'asapdigest-core'),
                    'code' => 'invalid_user_id'
                ]);
            }
            
            // Get the user
            $user = get_user_by('id', $user_id);
            if (!$user) {
                $this->send_error([
                    'message' => __('User not found.', 'asapdigest-core'),
                    'code' => 'user_not_found'
                ], 404);
            }
            
            // Update user meta to mark as banned
            update_user_meta($user_id, 'asap_user_banned', true);
            update_user_meta($user_id, 'asap_ban_date', current_time('mysql'));
            
            // Destroy all sessions for the user
            $sessions = WP_Session_Tokens::get_instance($user_id);
            $sessions->destroy_all();
            
            // Log the action
            $this->log_user_action($user_id, 'ban', __('User banned by admin.', 'asapdigest-core'));
            
            // Log with ErrorLogger for system records
            ErrorLogger::log('user_actions', 'user_banned', sprintf('User %d banned by admin.', $user_id), [
                'user_id' => $user_id,
                'admin_id' => get_current_user_id()
            ], 'warning');
            
            $this->send_success([
                'message' => __('User has been banned successfully.', 'asapdigest-core'),
                'user_id' => $user_id
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'ban_user_error', $e->getMessage(), [
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while banning the user.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle lock account action
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_lock_account() {
        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        // Verify request with user-specific nonce
        $this->verify_request('nonce', 'manage_options');
        
        // Additional nonce check for user-specific action
        if (!wp_verify_nonce($_POST['nonce'], 'lock_account_' . $user_id)) {
            $this->send_error([
                'message' => __('Invalid security token.', 'asapdigest-core'),
                'code' => 'invalid_nonce'
            ], 400);
        }
        
        try {
            // Validate user ID
            if ($user_id <= 0) {
                $this->send_error([
                    'message' => __('Invalid user ID.', 'asapdigest-core'),
                    'code' => 'invalid_user_id'
                ]);
            }
            
            // Get the user
            $user = get_user_by('id', $user_id);
            if (!$user) {
                $this->send_error([
                    'message' => __('User not found.', 'asapdigest-core'),
                    'code' => 'user_not_found'
                ], 404);
            }
            
            // Update user meta to mark as locked
            update_user_meta($user_id, 'asap_account_locked', true);
            update_user_meta($user_id, 'asap_lock_date', current_time('mysql'));
            
            // Destroy all sessions for the user
            $sessions = WP_Session_Tokens::get_instance($user_id);
            $sessions->destroy_all();
            
            // Log the action
            $this->log_user_action($user_id, 'lock', __('Account locked by admin.', 'asapdigest-core'));
            
            // Log with ErrorLogger for system records
            ErrorLogger::log('user_actions', 'account_locked', sprintf('User %d account locked by admin.', $user_id), [
                'user_id' => $user_id,
                'admin_id' => get_current_user_id()
            ], 'warning');
            
            $this->send_success([
                'message' => __('Account has been locked successfully.', 'asapdigest-core'),
                'user_id' => $user_id
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'lock_account_error', $e->getMessage(), [
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while locking the account.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle password reset action
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_reset_password() {
        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        // Verify request with user-specific nonce
        $this->verify_request('nonce', 'manage_options');
        
        // Additional nonce check for user-specific action
        if (!wp_verify_nonce($_POST['nonce'], 'reset_password_' . $user_id)) {
            $this->send_error([
                'message' => __('Invalid security token.', 'asapdigest-core'),
                'code' => 'invalid_nonce'
            ], 400);
        }
        
        try {
            // Validate user ID
            if ($user_id <= 0) {
                $this->send_error([
                    'message' => __('Invalid user ID.', 'asapdigest-core'),
                    'code' => 'invalid_user_id'
                ]);
            }
            
            // Get the user
            $user = get_user_by('id', $user_id);
            if (!$user) {
                $this->send_error([
                    'message' => __('User not found.', 'asapdigest-core'),
                    'code' => 'user_not_found'
                ], 404);
            }
            
            // Generate password reset key
            $key = get_password_reset_key($user);
            if (is_wp_error($key)) {
                $this->send_error([
                    'message' => __('Error generating password reset key.', 'asapdigest-core'),
                    'code' => 'reset_key_error',
                    'details' => WP_DEBUG ? $key->get_error_message() : null
                ]);
            }
            
            // Send password reset email
            $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login));
            $message = sprintf(
                __('Someone has requested a password reset for your account. If this was a mistake, just ignore this email and nothing will happen.%sTo reset your password, visit the following address: %s', 'asapdigest-core'),
                "\r\n\r\n",
                $reset_link
            );
            
            if (!wp_mail($user->user_email, __('Password Reset Request', 'asapdigest-core'), $message)) {
                $this->send_error([
                    'message' => __('Error sending password reset email.', 'asapdigest-core'),
                    'code' => 'email_error'
                ]);
            }
            
            // Log the action
            $this->log_user_action($user_id, 'password_reset', __('Password reset initiated by admin.', 'asapdigest-core'));
            
            // Log with ErrorLogger for system records
            ErrorLogger::log('user_actions', 'password_reset_initiated', sprintf('Password reset for user %d initiated by admin.', $user_id), [
                'user_id' => $user_id,
                'admin_id' => get_current_user_id(),
                'user_email' => $user->user_email
            ], 'info');
            
            $this->send_success([
                'message' => __('Password reset email has been sent.', 'asapdigest-core'),
                'user_id' => $user_id
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'reset_password_error', $e->getMessage(), [
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while processing the password reset.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle view activity action
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_view_activity() {
        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        // Verify request with user-specific nonce
        $this->verify_request('nonce', 'manage_options');
        
        // Additional nonce check for user-specific action
        if (!wp_verify_nonce($_POST['nonce'], 'view_activity_' . $user_id)) {
            $this->send_error([
                'message' => __('Invalid security token.', 'asapdigest-core'),
                'code' => 'invalid_nonce'
            ], 400);
        }
        
        try {
            // Validate user ID
            if ($user_id <= 0) {
                $this->send_error([
                    'message' => __('Invalid user ID.', 'asapdigest-core'),
                    'code' => 'invalid_user_id'
                ]);
            }
            
            // Get the user
            $user = get_user_by('id', $user_id);
            if (!$user) {
                $this->send_error([
                    'message' => __('User not found.', 'asapdigest-core'),
                    'code' => 'user_not_found'
                ], 404);
            }
            
            // Get user activity from logs
            $activity_logs = $this->get_user_activity_logs($user_id);
            
            if (empty($activity_logs)) {
                $activity_html = '<p>' . __('No activity records found.', 'asapdigest-core') . '</p>';
            } else {
                $activity_html = '<table class="wp-list-table widefat fixed striped">';
                $activity_html .= '<thead><tr>';
                $activity_html .= '<th>' . __('Date', 'asapdigest-core') . '</th>';
                $activity_html .= '<th>' . __('Action', 'asapdigest-core') . '</th>';
                $activity_html .= '<th>' . __('Details', 'asapdigest-core') . '</th>';
                $activity_html .= '</tr></thead><tbody>';
                
                foreach ($activity_logs as $log) {
                    $activity_html .= sprintf(
                        '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                        esc_html($log->date),
                        esc_html($log->action),
                        esc_html($log->details)
                    );
                }
                
                $activity_html .= '</tbody></table>';
            }
            
            // Log access
            ErrorLogger::log('user_actions', 'activity_viewed', sprintf('User %d activity viewed by admin.', $user_id), [
                'user_id' => $user_id,
                'admin_id' => get_current_user_id(),
                'activity_count' => count($activity_logs)
            ], 'info');
            
            $this->send_success([
                'activity' => $activity_html,
                'raw_logs' => $activity_logs,
                'user_id' => $user_id
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'view_activity_error', $e->getMessage(), [
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while retrieving user activity.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Log user action to database
     *
     * @since 3.0.0
     * @param int $user_id The user ID
     * @param string $action The action performed
     * @param string $details Additional details
     * @return bool Success or failure
     */
    private function log_user_action($user_id, $action, $details) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'asap_user_activity_log';
        
        return $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'action' => $action,
                'details' => $details,
                'date' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get user activity logs from database
     *
     * @since 3.0.0
     * @param int $user_id The user ID
     * @param int $limit Maximum number of logs to retrieve
     * @return array Array of activity logs
     */
    private function get_user_activity_logs($user_id, $limit = 100) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'asap_user_activity_log';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY date DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }
} 