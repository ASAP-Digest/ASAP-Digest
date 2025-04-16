<?php
/**
 * ASAP Digest REST API Auth Controller
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_REST_Auth
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_REST_Auth extends ASAP_Digest_REST_Base {
    /**
     * Constructor
     */
    public function __construct() {
        $this->rest_base = 'auth';
        parent::__construct();
    }

    /**
     * Register routes for auth endpoints
     */
    public function register_routes() {
        // Get auth status
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/status',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_status'],
                    'permission_callback' => [$this, 'permissions_check'],
                ]
            ]
        );

        // Update auth settings
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/settings',
            [
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'update_settings'],
                    'permission_callback' => [$this, 'admin_permissions_check'],
                    'args' => $this->get_settings_args()
                ]
            ]
        );

        // Validate session
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/validate',
            [
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'validate_session'],
                    'permission_callback' => [$this, 'permissions_check'],
                ]
            ]
        );
    }

    /**
     * Get auth status
     *
     * @param WP_REST_Request $request Request object
     * @return mixed Response object or WP_Error
     */
    public function get_status($request) {
        // $request parameter is part of the WP REST API callback signature, but not directly used in this specific implementation.
        $_ = $request; // Explicitly mark $request as used to satisfy linter
        $auth = $this->get_better_auth();
        $validation_result = $auth->validate_session();

        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        return $this->prepare_response([
            'loggedIn' => true,
            'wpUserId' => $this->get_current_user_id(),
        ]);
    }

    /**
     * Update auth settings
     *
     * @param WP_REST_Request $request Request object
     * @return mixed Response object or WP_Error
     */
    public function update_settings($request) {
        $params = $request->get_params();
        $auth = $this->get_better_auth();
        
        $result = $auth->update_auth_settings($params);
        
        if (is_wp_error($result)) {
            return $this->prepare_error_response(
                'settings_update_failed',
                __('Failed to update auth settings.', 'asap-digest'),
                500
            );
        }

        // Track settings update
        $this->get_usage_tracker()->track_event('auth_settings_updated');

        return $this->prepare_response([
            'message' => __('Auth settings updated successfully.', 'asap-digest'),
            'settings' => $result
        ]);
    }

    /**
     * Validate session
     *
     * @param WP_REST_Request $request Request object
     * @return mixed Response object or WP_Error
     */
    public function validate_session($request) {
        $auth = $this->get_better_auth();
        $result = $auth->validate_session();

        if (is_wp_error($result)) {
            return $this->prepare_error_response(
                'session_validation_failed',
                __('Session validation failed.', 'asap-digest'),
                401
            );
        }

        return $this->prepare_response([
            'valid' => true,
            'user_id' => get_current_user_id()
        ]);
    }

    /**
     * Get settings endpoint arguments
     *
     * @return array Endpoint arguments
     */
    private function get_settings_args() {
        return [
            'session_length' => [
                'type' => 'integer',
                'required' => true,
                'minimum' => 1800, // 30 minutes
                'maximum' => 86400 // 24 hours
            ],
            'refresh_token_length' => [
                'type' => 'integer',
                'required' => true,
                'minimum' => 86400, // 24 hours
                'maximum' => 2592000 // 30 days
            ],
            'max_sessions' => [
                'type' => 'integer',
                'required' => false,
                'minimum' => 1,
                'maximum' => 10
            ]
        ];
    }
} 