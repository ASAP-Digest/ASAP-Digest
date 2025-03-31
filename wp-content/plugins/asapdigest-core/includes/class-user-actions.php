<?php
/**
 * Class ASAP_Digest_User_Actions
 * 
 * Handles user management actions for the Better Auth integration
 */
class ASAP_Digest_User_Actions {
    /**
     * Initialize the class and set up WordPress hooks
     */
    public function __construct() {
        add_action('wp_ajax_asap_ban_user', array($this, 'handle_ban_user'));
        add_action('wp_ajax_asap_lock_account', array($this, 'handle_lock_account'));
        add_action('wp_ajax_asap_reset_password', array($this, 'handle_reset_password'));
        add_action('wp_ajax_asap_view_activity', array($this, 'handle_view_activity'));
    }

    /**
     * Handle ban user action
     */
    public function handle_ban_user() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $user_id = intval($_POST['user_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'ban_user_' . $user_id)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found'));
        }

        // Update user meta to mark as banned
        update_user_meta($user_id, 'asap_user_banned', true);
        update_user_meta($user_id, 'asap_ban_date', current_time('mysql'));

        // Destroy all sessions for the user
        $sessions = WP_Session_Tokens::get_instance($user_id);
        $sessions->destroy_all();

        // Log the action
        $this->log_user_action($user_id, 'ban', 'User banned by admin');

        wp_send_json_success(array('message' => 'User has been banned successfully'));
    }

    /**
     * Handle lock account action
     */
    public function handle_lock_account() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $user_id = intval($_POST['user_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'lock_account_' . $user_id)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found'));
        }

        // Update user meta to mark as locked
        update_user_meta($user_id, 'asap_account_locked', true);
        update_user_meta($user_id, 'asap_lock_date', current_time('mysql'));

        // Destroy all sessions for the user
        $sessions = WP_Session_Tokens::get_instance($user_id);
        $sessions->destroy_all();

        // Log the action
        $this->log_user_action($user_id, 'lock', 'Account locked by admin');

        wp_send_json_success(array('message' => 'Account has been locked successfully'));
    }

    /**
     * Handle password reset action
     */
    public function handle_reset_password() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $user_id = intval($_POST['user_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'reset_password_' . $user_id)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found'));
        }

        // Generate password reset key
        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            wp_send_json_error(array('message' => 'Error generating password reset key'));
        }

        // Send password reset email
        $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login));
        $message = sprintf(
            __('Someone has requested a password reset for your account. If this was a mistake, just ignore this email and nothing will happen.%sTo reset your password, visit the following address: %s'),
            "\r\n\r\n",
            $reset_link
        );

        if (!wp_mail($user->user_email, 'Password Reset Request', $message)) {
            wp_send_json_error(array('message' => 'Error sending password reset email'));
        }

        // Log the action
        $this->log_user_action($user_id, 'password_reset', 'Password reset initiated by admin');

        wp_send_json_success(array('message' => 'Password reset email has been sent'));
    }

    /**
     * Handle view activity action
     */
    public function handle_view_activity() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $user_id = intval($_POST['user_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'view_activity_' . $user_id)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found'));
        }

        // Get user activity from logs
        $activity_logs = $this->get_user_activity_logs($user_id);
        if (empty($activity_logs)) {
            $activity_html = '<p>No activity records found.</p>';
        } else {
            $activity_html = '<table class="wp-list-table widefat fixed striped">';
            $activity_html .= '<thead><tr><th>Date</th><th>Action</th><th>Details</th></tr></thead><tbody>';
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

        wp_send_json_success(array('activity' => $activity_html));
    }

    /**
     * Log user action to database
     */
    private function log_user_action($user_id, $action, $details) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'asap_user_activity_log';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'details' => $details,
                'date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }

    /**
     * Get user activity logs from database
     */
    private function get_user_activity_logs($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'asap_user_activity_log';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY date DESC LIMIT 100",
            $user_id
        ));
    }
}

// Initialize the class
new ASAP_Digest_User_Actions(); 