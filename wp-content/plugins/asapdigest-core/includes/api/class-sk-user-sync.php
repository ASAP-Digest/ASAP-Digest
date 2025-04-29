<?php
/**
 * SvelteKit User Synchronization Endpoint
 * 
 * @package ASAPDigest_Core
 * @created 04.28.25 | 07:45 PM PDT
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SK_User_Sync
 * Handles user data synchronization from SvelteKit to WordPress
 */
class SK_User_Sync extends WP_REST_Controller {
    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'asap/v1';
        $this->rest_base = 'user-sync';
    }

    /**
     * Register routes
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'handle_sync'],
                'permission_callback' => [$this, 'check_sync_permission'],
                'args' => $this->get_endpoint_args_for_item_schema(true)
            ],
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_sync_status'],
                'permission_callback' => [$this, 'check_sync_permission']
            ]
        ]);
    }

    /**
     * Check if the request has permission to sync
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_sync_permission($request) {
        // Verify sync secret
        $secret = $request->get_header('X-WP-Sync-Secret');
        $expected_secret = defined('BETTER_AUTH_SECRET') ? BETTER_AUTH_SECRET : null;

        if (!$expected_secret) {
            error_log('[ASAP Digest] CRITICAL: BETTER_AUTH_SECRET not defined for SK sync.');
            return new WP_Error(
                'sync_configuration_error',
                'Sync secret not configured.',
                ['status' => 500]
            );
        }

        if (!$secret || $secret !== $expected_secret) {
            return new WP_Error(
                'invalid_sync_secret',
                'Invalid sync secret provided.',
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Get sync status
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_sync_status($request) {
        return new WP_REST_Response([
            'status' => 'available',
            'version' => '2.0'
        ]);
    }

    /**
     * Handle sync request
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_sync($request) {
        try {
            $params = $request->get_json_params();
            
            // Validate required fields
            if (empty($params['skUserId']) || empty($params['email'])) {
                return new WP_Error(
                    'missing_required_fields',
                    'Missing required fields (skUserId, email)',
                    ['status' => 400]
                );
            }

            // Get user by Better Auth ID
            $wp_user = $this->get_wp_user_by_better_auth_id($params['skUserId']);
            
            if (!$wp_user) {
                // User doesn't exist, create new user
                $wp_user = $this->create_wp_user($params);
                if (is_wp_error($wp_user)) {
                    return $wp_user;
                }
            }

            // Update user data
            $update_result = $this->update_wp_user($wp_user, $params);
            if (is_wp_error($update_result)) {
                return $update_result;
            }

            // Update sync metadata
            update_user_meta($wp_user->ID, 'better_auth_sync_time', current_time('mysql'));
            update_user_meta($wp_user->ID, 'better_auth_sync_status', 'synced');

            return new WP_REST_Response([
                'success' => true,
                'message' => 'User synchronized successfully',
                'wpUserId' => $wp_user->ID,
                'syncTime' => current_time('mysql')
            ]);

        } catch (\Exception $e) {
            error_log('[ASAP Digest] Error in SK sync handler: ' . $e->getMessage());
            return new WP_Error(
                'sync_error',
                'Error processing sync request: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get WordPress user by Better Auth ID
     * @param string $better_auth_id
     * @return WP_User|null
     */
    private function get_wp_user_by_better_auth_id($better_auth_id) {
        global $wpdb;
        
        // First try to get from the mapping table
        $wp_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT wp_user_id FROM {$wpdb->prefix}ba_wp_user_map WHERE ba_user_id = %s",
            $better_auth_id
        ));

        if ($wp_user_id) {
            return get_user_by('ID', $wp_user_id);
        }

        // Fallback to meta lookup
        $users = get_users([
            'meta_key' => 'better_auth_user_id',
            'meta_value' => $better_auth_id,
            'number' => 1
        ]);

        return !empty($users) ? $users[0] : null;
    }

    /**
     * Create new WordPress user
     * @param array $data User data from SvelteKit
     * @return WP_User|WP_Error
     */
    private function create_wp_user($data) {
        // Generate username from email
        $username = $this->generate_unique_username($data['email']);

        // Create user
        $user_id = wp_create_user(
            $username,
            wp_generate_password(),
            $data['email']
        );

        if (is_wp_error($user_id)) {
            error_log('[ASAP Digest] Error creating WP user: ' . $user_id->get_error_message());
            return $user_id;
        }

        $user = get_user_by('ID', $user_id);

        // Set display name
        if (!empty($data['displayName'])) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $data['displayName']
            ]);
        }

        // Add to mapping table
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'ba_wp_user_map',
            [
                'wp_user_id' => $user_id,
                'ba_user_id' => $data['skUserId']
            ],
            ['%d', '%s']
        );

        // Set metadata
        update_user_meta($user_id, 'better_auth_user_id', $data['skUserId']);
        update_user_meta($user_id, 'better_auth_sync_status', 'synced');
        update_user_meta($user_id, 'better_auth_sync_time', current_time('mysql'));

        return $user;
    }

    /**
     * Update WordPress user data
     * @param WP_User $user WordPress user object
     * @param array $data User data from SvelteKit
     * @return true|WP_Error
     */
    private function update_wp_user($user, $data) {
        $update_data = ['ID' => $user->ID];

        // Update email if changed
        if ($data['email'] !== $user->user_email) {
            $update_data['user_email'] = $data['email'];
        }

        // Update display name if provided
        if (!empty($data['displayName']) && $data['displayName'] !== $user->display_name) {
            $update_data['display_name'] = $data['displayName'];
        }

        // Update user if we have changes
        if (count($update_data) > 1) { // More than just ID
            $result = wp_update_user($update_data);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        // Update roles if provided
        if (!empty($data['roles']) && is_array($data['roles'])) {
            $this->sync_user_roles($user, $data['roles']);
        }

        // Update metadata if provided
        if (!empty($data['metadata']) && is_array($data['metadata'])) {
            foreach ($data['metadata'] as $key => $value) {
                update_user_meta($user->ID, 'better_auth_' . $key, $value);
            }
        }

        return true;
    }

    /**
     * Sync user roles
     * @param WP_User $user WordPress user object
     * @param array $roles Roles from SvelteKit
     */
    private function sync_user_roles($user, $roles) {
        // Role mapping
        $role_map = [
            'admin' => 'administrator',
            'editor' => 'editor',
            'author' => 'author',
            'subscriber' => 'subscriber'
            // Add more role mappings as needed
        ];

        // Remove existing roles
        $user->set_role('');

        // Add mapped roles
        foreach ($roles as $role) {
            if (isset($role_map[$role])) {
                $user->add_role($role_map[$role]);
            }
        }

        // Ensure user has at least subscriber role
        if (empty($user->roles)) {
            $user->set_role('subscriber');
        }
    }

    /**
     * Generate unique username from email
     * @param string $email
     * @return string
     */
    private function generate_unique_username($email) {
        $base = strtolower(explode('@', $email)[0]);
        $username = $base;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
} 