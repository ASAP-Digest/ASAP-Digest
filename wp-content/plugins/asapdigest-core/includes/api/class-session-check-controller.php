<?php
/**
 * Session Check Controller
 *
 * @package ASAPDigest_Core
 * @created 05.02.25 | 01:45 PM PDT
 * @file-marker Session_Check_Controller
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles session validation directly from cookies
 */
class Session_Check_Controller extends WP_REST_Controller {
    /**
     * @var string
     */
    protected $namespace = 'asap/v1';

    /**
     * Constructor
     */
    public function __construct() {
        error_log('Session_Check_Controller: register_routes called');
    }

    /**
     * Register the routes
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/validate-session-get-user',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'validate_session'],
                    'permission_callback' => [$this, 'validate_sync_secret'],
                ],
            ]
        );
    }

    /**
     * Validate sync secret from request header
     * 
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function validate_sync_secret($request) {
        // Get the sync secret from header
        $sync_secret = $request->get_header('X-ASAP-Sync-Secret');
        
        // Check if secret exists and matches
        if (!$sync_secret) {
            return new WP_Error(
                'missing_sync_secret',
                'Missing sync secret header',
                ['status' => 403]
            );
        }
        
        // Get the actual secret value
        $actual_secret = defined('ASAP_SK_SYNC_SECRET') ? constant('ASAP_SK_SYNC_SECRET') : 'shared_secret_for_server_to_server_auth';
        
        if ($sync_secret !== $actual_secret) {
            return new WP_Error(
                'invalid_sync_secret',
                'Invalid sync secret',
                ['status' => 403]
            );
        }
        
        return true;
    }

    /**
     * Validate WP session from cookies
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function validate_session($request) {
        // Check if user is logged in based on incoming cookies
        $user = wp_get_current_user();
        
        // If no valid user found
        if (!$user || !$user->exists()) {
            return rest_ensure_response([
                'success' => false,
                'error' => 'wp_session_invalid',
                'message' => 'No valid WordPress session found'
            ]);
        }
        
        // Return user data for SK to create session
        return rest_ensure_response([
            'success' => true,
                'userData' => [
                'wpUserId' => $user->ID,
                'email' => $user->user_email,
                'username' => $user->user_login,
                'displayName' => $user->display_name,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'roles' => $user->roles,
                'metadata' => [
                    'description' => $user->description,
                    'nickname' => $user->nickname,
                    // Add any other metadata needed
                ]
            ]
        ]);
    }
}
