<?php
/**
 * ASAP Digest Better Auth Integration
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Better_Auth
 */

namespace ASAPDigest\Core;

use WP_Error;
use WP_User;
use ASAPDigest\Core\Traits\User_Sync;
use ASAPDigest\Core\Traits\Session_Mgmt;
use function get_option;
use function update_option;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Better_Auth {
    use User_Sync, Session_Mgmt;

    /**
     * @var string Option name for auth settings
     */
    private $settings_option = 'asap_digest_auth_settings';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the class
     */
    private function init() {
        // Set default settings if not exist
        if (!get_option($this->settings_option)) {
            $this->set_default_settings();
        }

        // Setup hooks for auto-sync
        $this->setup_user_sync_hooks();
        $this->setup_session_cleanup_hook();
        
        // Add authentication hooks
        add_action('wp_login', [$this, 'handle_wp_login'], 10, 2);
        add_action('wp_logout', [$this, 'handle_wp_logout']);
        add_action('delete_user', [$this, 'handle_user_deletion']);
    }

    /**
     * Set default auth settings
     */
    private function set_default_settings() {
        $defaults = [
            'session_length' => 3600, // 1 hour
            'refresh_token_length' => 604800, // 7 days
            'max_sessions' => 5,
            'auto_sync_enabled' => true,
            'sync_retry_attempts' => 3
        ];

        update_option($this->settings_option, $defaults);
    }

    /**
     * Get auth settings
     *
     * @return array|WP_Error Auth settings or error
     */
    public function get_auth_settings() {
        $settings = get_option($this->settings_option);
        
        if (!$settings) {
            return new WP_Error(
                'auth_settings_error',
                __('Could not retrieve auth settings.', 'asap-digest')
            );
        }

        return [
            'is_configured' => true,
            'settings' => $settings,
            'user_id' => get_current_user_id()
        ];
    }

    /**
     * Update auth settings
     *
     * @param array $settings New settings
     * @return array|WP_Error Updated settings or error
     */
    public function update_auth_settings($settings) {
        $current = get_option($this->settings_option);
        
        if (!$current) {
            return new WP_Error(
                'settings_update_error',
                __('Could not retrieve current settings.', 'asap-digest')
            );
        }

        $updated = array_merge($current, $settings);
        
        if (!update_option($this->settings_option, $updated)) {
            return new WP_Error(
                'settings_update_error',
                __('Could not update settings.', 'asap-digest')
            );
        }

        return $updated;
    }

    /**
     * Handle WordPress login
     *
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function handle_wp_login($user_login, $user) {
        // Ensure user is synced with Better Auth
        $sync_result = $this->retry_user_sync($user);
        
        if (!is_wp_error($sync_result)) {
            // Create Better Auth session
            $session_token = $this->create_session($user);
            
            if (!is_wp_error($session_token)) {
                // Set session cookie for cross-domain auth
                $this->set_auth_cookie($user->ID, $session_token);
            }
        }
    }

    /**
     * Handle WordPress logout
     */
    public function handle_wp_logout() {
        $user_id = get_current_user_id();
        if ($user_id) {
            $token = get_user_meta($user_id, 'better_auth_session_token', true);
            if ($token) {
                $this->destroy_session($token);
            }
        }
    }

    /**
     * Handle user deletion
     *
     * @param int $user_id User ID being deleted
     */
    public function handle_user_deletion($user_id) {
        global $wpdb;

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Get Better Auth user ID
            $ba_user_id = get_user_meta($user_id, 'better_auth_user_id', true);
            
            if ($ba_user_id) {
                // Delete sessions
                $wpdb->delete(
                    $wpdb->prefix . 'ba_sessions',
                    ['user_id' => $ba_user_id],
                    ['%d']
                );

                // Delete user mapping
                $wpdb->delete(
                    $wpdb->prefix . 'ba_wp_user_map',
                    ['wp_user_id' => $user_id],
                    ['%d']
                );

                // Delete Better Auth user
                $wpdb->delete(
                    $wpdb->prefix . 'ba_users',
                    ['id' => $ba_user_id],
                    ['%d']
                );
            }

            // Commit transaction
            $wpdb->query('COMMIT');

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            
            error_log(sprintf(
                'Failed to cleanup Better Auth data for user %d: %s',
                $user_id,
                $e->getMessage()
            ));
        }
    }

    /**
     * Set authentication cookie for cross-domain auth
     *
     * @param int $user_id User ID
     * @param string $token Session token
     */
    private function set_auth_cookie($user_id, $token) {
        $settings = get_option($this->settings_option);
        $session_length = isset($settings['session_length']) ? $settings['session_length'] : 3600;

        setcookie(
            'ba_auth_token',
            $token,
            [
                'expires' => time() + $session_length,
                'path' => '/',
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Validate current session
     *
     * @return bool|WP_Error True if valid, error if not
     */
    public function validate_session() {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'invalid_session',
                __('User is not logged in.', 'asap-digest')
            );
        }

        $user_id = get_current_user_id();
        $token = get_user_meta($user_id, 'better_auth_session_token', true);

        if (!$token) {
            return new WP_Error(
                'no_session_token',
                __('No session token found.', 'asap-digest')
            );
        }

        return $this->validate_session_token($token);
    }
} 