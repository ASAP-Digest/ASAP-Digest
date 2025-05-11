<?php
/**
 * Better Auth Webhook Controller
 * 
 * Handles webhook endpoints for Better Auth integration
 * 
 * @package ASAPDigest_Core
 * @created 05.16.25 | 03:36 PM PDT
 * @file-marker ASAP_Digest_Auth_Webhook_Controller
 */

namespace ASAPDigest\Core\API\Controllers;

use ASAPDigest\Core\API\ASAP_Digest_REST_Base;
use ASAPDigest\Core\Auth\ASAP_Digest_Auth_Sync;
use WP_Error;
use WP_REST_Response;

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auth Webhook Controller class
 * 
 * Handles Better Auth webhook endpoints
 */
class ASAP_Digest_Auth_Webhook_Controller extends ASAP_Digest_REST_Base {
    /**
     * Constructor
     */
    public function __construct() {
        $this->rest_base = 'auth/webhook';
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'handle_session_webhook'],
                'permission_callback' => '__return_true'
            ]
        ]);
    }

    /**
     * Handle session webhook
     *
     * @param \WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function handle_session_webhook($request) {
        $data = $request->get_json_params();
        
        if (empty($data['event']) || empty($data['user_id'])) {
            return new WP_Error('invalid_data', 'Invalid webhook data', ['status' => 400]);
        }
        
        // Find the WordPress user from Better Auth ID
        $users = get_users([
            'meta_key' => 'better_auth_user_id',
            'meta_value' => $data['user_id'],
            'number' => 1
        ]);

        if (empty($users)) {
            return new WP_REST_Response(['error' => 'User not found'], 404);
        }

        $wp_user_id = $users[0]->ID;

        switch ($data['event']) {
            case 'session.created':
                // Update session token if provided
                if (!empty($data['session_token'])) {
                    update_user_meta($wp_user_id, 'better_auth_session_token', $data['session_token']);
                    update_user_meta($wp_user_id, 'better_auth_last_login', current_time('mysql'));
                }
                break;

            case 'session.ended':
                // Remove session token
                delete_user_meta($wp_user_id, 'better_auth_session_token');
                break;

            case 'user.deleted':
                // Optionally handle user deletion
                wp_delete_user($wp_user_id);
                break;
                
            case 'user.updated':
                // Handle user update
                if (!empty($data['roles'])) {
                    ASAP_Digest_Auth_Sync::sync_user_roles($wp_user_id, $data['roles']);
                }
                
                if (!empty($data['metadata'])) {
                    ASAP_Digest_Auth_Sync::sync_user_metadata($wp_user_id, $data['metadata']);
                }
                break;
        }

        do_action('asap_better_auth_webhook_' . str_replace('.', '_', $data['event']), $wp_user_id, $data);

        return new WP_REST_Response(['success' => true]);
    }
} 