<?php
/**
 * REST API Endpoint Controller: Check Sync Token Status
 *
 * Provides an endpoint for the SvelteKit frontend to check if a valid,
 * unexpired sync token exists for the currently logged-in WordPress user.
 * 
 * @package         ASAPDigest_Core
 * @subpackage      API
 * @created         04.29.25 | 12:38 AM PDT 
 * @file-marker     Check_Sync_Token_Controller
 */

namespace ASAPDigest\Core\API;

// Necessary WordPress classes
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// Ensure WordPress environment is loaded
if (!defined('ABSPATH')) {
    exit;
}

class Check_Sync_Token_Controller extends WP_REST_Controller {
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->namespace = 'asap/v1'; // Namespace for ASAP Digest API
        $this->rest_base = 'check-sync-token'; // Endpoint base slug
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @see register_rest_route()
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE, // GET request
                    'callback'            => [$this, 'check_token_status'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args'                => [], // No specific args needed for this GET request
                ],
                'schema' => null, // No formal schema needed for this simple endpoint
            ]
        );
    }

    /**
     * Checks if the current user has permissions to access this endpoint.
     *
     * Only logged-in WordPress users should be able to check for their own token.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function check_permission($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You are not currently logged in.'),
                ['status' => 401]
            );
        }
        // Any logged-in user can check for their own token status.
        return true;
    }

    /**
     * Checks the status of a sync token for the current user.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function check_token_status($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ba_sync_tokens';
        $wp_user_id = get_current_user_id(); // Get ID of the user making the request

        if (empty($wp_user_id)) {
            // This shouldn't happen if check_permission passed, but good to double-check.
            return new WP_Error(
                'rest_user_invalid',
                __('Could not determine current user.'),
                ['status' => 500] 
            );
        }

        // Query for a valid, non-expired token for this user
        $token_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$table_name} WHERE wp_user_id = %d AND expires_at > NOW() LIMIT 1",
            $wp_user_id
        ));

        // Prepare the response data
        $response_data = [
            'tokenExists' => !empty($token_exists) // Convert query result (1 or null) to boolean
        ];

        // Return a successful response
        return new WP_REST_Response($response_data, 200);
    }

}
