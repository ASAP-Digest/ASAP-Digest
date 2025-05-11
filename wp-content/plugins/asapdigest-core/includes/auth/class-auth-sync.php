<?php
/**
 * Better Auth Synchronization Class
 * 
 * Handles user synchronization between WordPress and Better Auth
 * 
 * @package ASAPDigest_Core
 * @created 05.16.25 | 03:35 PM PDT
 * @file-marker ASAP_Digest_Auth_Sync
 */

namespace ASAPDigest\Core\Auth;

use WP_Error;
use WP_User;

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auth Sync Class
 * 
 * Manages user synchronization between WordPress and Better Auth
 */
class ASAP_Digest_Auth_Sync {
    /**
     * Sync WordPress user roles with Better Auth
     *
     * @param int $wp_user_id WordPress user ID
     * @param array $better_auth_roles Better Auth roles array
     * @return bool True on success, false on failure
     */
    public static function sync_user_roles($wp_user_id, $better_auth_roles) {
        // Role mapping between Better Auth and WordPress
        $role_map = [
            'admin' => 'administrator',
            'editor' => 'editor',
            'author' => 'author',
            'subscriber' => 'subscriber'
        ];

        $user = get_user_by('ID', $wp_user_id);
        if (!$user) {
            return false;
        }

        // Remove all existing roles
        $user->set_role('');

        // Add mapped roles
        foreach ($better_auth_roles as $ba_role) {
            if (isset($role_map[$ba_role])) {
                $user->add_role($role_map[$ba_role]);
            }
        }

        // If no roles were mapped, set default subscriber role
        if (empty($user->roles)) {
            $user->set_role('subscriber');
        }

        do_action('asap_better_auth_roles_synced', $wp_user_id, $better_auth_roles);
        return true;
    }

    /**
     * Sync user metadata between Better Auth and WordPress
     *
     * @param int $wp_user_id WordPress user ID
     * @param array $better_auth_metadata Better Auth metadata array
     * @return bool True on success, false on failure
     */
    public static function sync_user_metadata($wp_user_id, $better_auth_metadata) {
        // Metadata mapping between Better Auth and WordPress
        $meta_map = [
            'name' => 'display_name',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'avatar_url' => 'better_auth_avatar_url',
            'preferences' => 'better_auth_preferences',
            'last_login_at' => 'better_auth_last_login',
            'subscription_status' => 'better_auth_subscription_status',
            'subscription_plan' => 'better_auth_subscription_plan'
        ];

        $user = get_user_by('ID', $wp_user_id);
        if (!$user) {
            return false;
        }

        foreach ($better_auth_metadata as $ba_key => $value) {
            if (isset($meta_map[$ba_key])) {
                $wp_key = $meta_map[$ba_key];
                
                // Handle special cases
                if ($wp_key === 'display_name') {
                    wp_update_user([
                        'ID' => $wp_user_id,
                        'display_name' => sanitize_text_field($value)
                    ]);
                } else {
                    update_user_meta($wp_user_id, $wp_key, $value);
                }
            }
        }

        // Store complete metadata snapshot
        update_user_meta(
            $wp_user_id,
            'better_auth_metadata_snapshot',
            wp_json_encode($better_auth_metadata)
        );

        do_action('asap_better_auth_metadata_synced', $wp_user_id, $better_auth_metadata);
        return true;
    }

    /**
     * Check if a user should be auto-synced based on their roles
     *
     * @param int|WP_User $user User ID or WP_User object
     * @return bool Whether the user should be auto-synced
     */
    public static function should_auto_sync_user($user) {
        $user = is_numeric($user) ? get_user_by('id', $user) : $user;
        if (!$user) {
            return false;
        }

        $auto_sync_roles = get_option('asap_better_auth_auto_sync_roles', ['administrator']);
        
        // Check if user has any auto-sync roles
        foreach ($user->roles as $role) {
            if (in_array($role, $auto_sync_roles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a WordPress user is synced with Better Auth
     *
     * @param int $wp_user_id WordPress user ID
     * @return bool True if user is synced, false otherwise
     */
    public static function is_user_synced($wp_user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ba_wp_user_map';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE wp_user_id = %d",
            $wp_user_id
        ));
        
        return (int)$result > 0;
    }

    /**
     * Sync WordPress user to Better Auth
     *
     * @param int $wp_user_id WordPress user ID
     * @param string $sync_source Source of the sync (e.g., 'login', 'auto_sync')
     * @return array|WP_Error Result array on success, WP_Error on failure
     */
    public static function sync_wp_user_to_better_auth($wp_user_id, $sync_source = 'manual') {
        // Implementation would be here
        // This would make an API call to the Better Auth service
        // For now, we're just returning a placeholder
        
        return new WP_Error('not_implemented', 'This method is a placeholder and needs to be fully implemented');
    }

    /**
     * Unsync a WordPress user from Better Auth
     *
     * @param int $wp_user_id WordPress user ID to unsync
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function unsync_wp_user_from_better_auth($wp_user_id) {
        global $wpdb;

        // Get WordPress user data
        $wp_user = get_userdata($wp_user_id);
        if (!$wp_user) {
            return new WP_Error('invalid_user', 'WordPress user not found');
        }

        // Get Better Auth user ID
        $better_auth_id = get_user_meta($wp_user_id, 'better_auth_user_id', true);
        if (empty($better_auth_id)) {
            return new WP_Error('not_synced', 'User is not synced with Better Auth');
        }

        // Remove mapping from database
        $wpdb->delete(
            $wpdb->prefix . 'ba_wp_user_map',
            ['wp_user_id' => $wp_user_id],
            ['%d']
        );

        // Remove Better Auth metadata
        delete_user_meta($wp_user_id, 'better_auth_user_id');
        delete_user_meta($wp_user_id, 'better_auth_session_token');
        delete_user_meta($wp_user_id, 'better_auth_last_login');
        delete_user_meta($wp_user_id, 'better_auth_last_sync');
        delete_user_meta($wp_user_id, 'better_auth_metadata_snapshot');

        // Fire action for integrations
        do_action('asap_better_auth_user_unsynced', $wp_user_id, $better_auth_id);

        return true;
    }

    /**
     * Handle user login auto sync
     *
     * @param string $user_login Username
     * @param \WP_User $user User object
     * @return void
     */
    public static function handle_login_auto_sync($user_login, $user) {
        if (self::should_auto_sync_user($user)) {
            // Sync user with Better Auth on login
            self::sync_wp_user_to_better_auth($user->ID, 'login');
            
            // Update login timestamp
            update_user_meta($user->ID, 'better_auth_last_login', current_time('mysql'));
        }
        
        do_action('asap_better_auth_login_processed', $user->ID, $user);
    }

    /**
     * Auto sync user data between WordPress and Better Auth
     *
     * @param int $user_id WordPress user ID
     * @param mixed $context Optional context (previous user data object or other context)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function auto_sync_user_data($user_id, $context = null) {
        // Only proceed if user should be auto-synced
        if (!self::should_auto_sync_user($user_id)) {
            return false;
        }
        
        // Check if user is already synced
        $better_auth_id = get_user_meta($user_id, 'better_auth_user_id', true);
        if (!$better_auth_id) {
            // If not synced, perform initial sync
            return self::sync_wp_user_to_better_auth($user_id, 'auto_sync');
        }
        
        // Get current user data
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'WordPress user not found');
        }
        
        // Prepare metadata to sync
        $metadata = [
            'name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'roles' => $user->roles,
        ];
        
        // This would make an API call to the Better Auth service to update the user
        // For now, we're just logging the data and returning success
        error_log('Auto-syncing user data for user ID: ' . $user_id);
        
        // Update last sync time
        update_user_meta($user_id, 'better_auth_last_sync', current_time('mysql'));
        
        // Fire action for integrations
        do_action('asap_better_auth_user_data_synced', $user_id, $metadata, $context);
        
        return true;
    }
} 