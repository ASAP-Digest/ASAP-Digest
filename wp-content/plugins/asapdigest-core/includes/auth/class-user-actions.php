<?php
/**
 * ASAP Digest User Actions Management
 * 
 * @package ASAPDigest_Core
 * @created 05.16.25 | 03:40 PM PDT
 * @file-marker ASAP_Digest_User_Actions
 */

namespace ASAPDigest\Core\Auth;

use WP_Error;
use WP_Session_Tokens;
use function add_action;
use function current_user_can;
use function get_password_reset_key;
use function get_user_by;
use function network_site_url;
use function update_user_meta;
use function wp_mail;
use function wp_verify_nonce;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Actions class
 * 
 * Handles user management actions for the Better Auth integration
 */
class ASAP_Digest_User_Actions {
    /**
     * @var ASAP_Digest_Auth Auth integration instance
     */
    private $auth;

    /**
     * Constructor
     * 
     * @param ASAP_Digest_Auth $auth Auth integration instance
     */
    public function __construct($auth) {
        $this->auth = $auth;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     * 
     * @return void
     */
    private function init_hooks() {
        // User login and registration
        add_action('wp_login', [$this, 'handle_login'], 10, 2);
        add_action('user_register', [$this, 'handle_registration']);
        
        // Password reset
        add_action('retrieve_password_key', [$this, 'handle_password_reset_key'], 10, 2);
        add_action('password_reset', [$this, 'handle_password_reset'], 10, 2);
        
        // User profile updates
        add_action('profile_update', [$this, 'handle_profile_update'], 10, 2);
        
        // User deletion
        add_action('delete_user', [$this, 'handle_user_deletion']);
    }

    /**
     * Handle user login
     * 
     * @param string $user_login Username
     * @param \WP_User $user User object
     * @return void
     */
    public function handle_login($user_login, $user) {
        // Check if user should be auto-synced
        if (ASAP_Digest_Auth_Sync::should_auto_sync_user($user)) {
            // Sync user with Better Auth
            ASAP_Digest_Auth_Sync::sync_wp_user_to_better_auth($user->ID, 'login');
        }
        
        // Log login
        update_user_meta($user->ID, 'asap_last_login', current_time('mysql'));
        
        // Fire action for integrations
        do_action('asap_user_login', $user->ID, $user);
    }

    /**
     * Handle user registration
     * 
     * @param int $user_id User ID
     * @return void
     */
    public function handle_registration($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }
        
        // Check if user should be auto-synced
        if (ASAP_Digest_Auth_Sync::should_auto_sync_user($user)) {
            // Sync user with Better Auth
            ASAP_Digest_Auth_Sync::sync_wp_user_to_better_auth($user_id, 'registration');
        }
        
        // Fire action for integrations
        do_action('asap_user_registered', $user_id, $user);
    }

    /**
     * Handle password reset key
     * 
     * @param string $key Password reset key
     * @param string $user_login Username
     * @return void
     */
    public function handle_password_reset_key($key, $user_login) {
        $user = get_user_by('login', $user_login);
        if (!$user) {
            return;
        }
        
        // Store reset key in user meta
        update_user_meta($user->ID, 'asap_password_reset_key', $key);
        
        // Fire action for integrations
        do_action('asap_password_reset_key_generated', $user->ID, $key);
    }

    /**
     * Handle password reset
     * 
     * @param \WP_User $user User object
     * @param string $new_password New password
     * @return void
     */
    public function handle_password_reset($user, $new_password) {
        // Remove reset key
        delete_user_meta($user->ID, 'asap_password_reset_key');
        
        // Check if user is synced with Better Auth
        $better_auth_id = get_user_meta($user->ID, 'better_auth_user_id', true);
        if ($better_auth_id) {
            // Update Better Auth password
            // This would normally make an API call to the Better Auth service
            // Implementation would be here
        }
        
        // Fire action for integrations
        do_action('asap_password_reset', $user->ID, $new_password);
    }

    /**
     * Handle profile update
     * 
     * @param int $user_id User ID
     * @param \WP_User $old_user_data Old user data
     * @return void
     */
    public function handle_profile_update($user_id, $old_user_data) {
        // Auto-sync user data
        ASAP_Digest_Auth_Sync::auto_sync_user_data($user_id, $old_user_data);
        
        // Fire action for integrations
        do_action('asap_profile_updated', $user_id, $old_user_data);
    }

    /**
     * Handle user deletion
     * 
     * @param int $user_id User ID
     * @return void
     */
    public function handle_user_deletion($user_id) {
        // Check if user is synced with Better Auth
        $better_auth_id = get_user_meta($user_id, 'better_auth_user_id', true);
        if ($better_auth_id) {
            // Delete Better Auth user
            // This would normally make an API call to the Better Auth service
            // Implementation would be here
        }
        
        // Fire action for integrations
        do_action('asap_user_deleted', $user_id, $better_auth_id);
    }

    /**
     * Auto-sync user data
     * 
     * @param int $user_id User ID
     * @param mixed $context Context (optional)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function auto_sync_user_data($user_id, $context = null) {
        return ASAP_Digest_Auth_Sync::auto_sync_user_data($user_id, $context);
    }
} 