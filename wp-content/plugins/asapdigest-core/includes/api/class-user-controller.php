<?php
/**
 * REST API Controller for User Details.
 *
 * @package ASAPDigest_Core
 * @created 04.29.25 | 11:45 PM PDT
 * @file-marker User_Details_Controller
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
 * Class User_Details_Controller
 * Handles the /asap/v1/user-details/{userId} endpoint.
 */
class User_Details_Controller extends WP_REST_Controller {

    /**
     * Namespace for the REST route.
     * @var string
     */
    protected $namespace = 'asap/v1';

    /**
     * Route base for the controller.
     * @var string
     */
    protected $rest_base = 'user-details';

    /**
     * Register the routes for the controller.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<userId>\d+)', // Route with numeric userId param
            [
                [
                    'methods'             => WP_REST_Server::READABLE, // Corresponds to GET
                    'callback'            => [$this, 'get_user_details'],
                    // IMPORTANT: Permission check - ensure requesting system is authorized.
                    // For now, let's assume internal use or use application passwords/secure method.
                    // A simple nonce check or capability check might be appropriate depending on context.
                    // Using 'manage_options' capability check as a placeholder for internal/admin access.
                    // This should be replaced with a more specific capability or a dedicated auth method (e.g., HMAC).
                    'permission_callback' => function () {
                         // TODO: Implement more robust permission check (e.g., HMAC signature from SvelteKit backend)
                         return current_user_can('manage_options'); 
                         // return $this->validate_internal_request($request); // Example if using HMAC
                    },
                    'args'                => [
                        'userId' => [
                            'required'          => true,
                            'validate_callback' => function($param, $request, $key) {
                                return is_numeric($param);
                            },
                            'sanitize_callback' => 'absint',
                            'description'       => __('The WordPress User ID.', 'adc'),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Retrieves user details for a given WordPress User ID.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_user_details(WP_REST_Request $request) {
        $user_id = $request->get_param('userId');

        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'adc'),
                ['status' => 404]
            );
        }

        // Prepare the data to return
        $user_data = [
            'wpUserId'    => $user->ID,
            'email'       => $user->user_email,
            'displayName' => $user->display_name,
            'username'    => $user->user_login, // Include username
            'roles'       => (array) $user->roles, // Ensure roles is an array
            // Add any other relevant details needed by SvelteKit
        ];

        return new WP_REST_Response($user_data, 200);
    }
    
    // Optional: Add a method for request validation if needed
    /*
    private function validate_internal_request(WP_REST_Request $request) {
        // Implement HMAC signature validation or other secure check here
        return true; // Placeholder
    }
    */

} // End class
