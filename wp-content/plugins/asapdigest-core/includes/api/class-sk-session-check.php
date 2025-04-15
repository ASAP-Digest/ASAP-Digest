<?php
/**
 * REST API Endpoint for checking WordPress session status for SvelteKit Auto-Login.
 *
 * @package ASAPDigest_Core
 * @created 07.28.24 | 09:15 AM PDT
 * @file-marker SKSessCheck
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function add_action;
use function get_current_user_id;
use function get_option;
use function is_user_logged_in;
use function register_rest_route;

// Ensure this file is loaded within WordPress context.
if (!defined('ABSPATH')) {
    exit;
}

// Include the base class
require_once plugin_dir_path( __FILE__ ) . 'class-rest-base.php';

/**
 * Handles the /asap/v1/check-sk-session endpoint.
 */
class SK_Session_Check extends ASAP_Digest_REST_Base {

    /**
     * Register the REST API routes.
     *
     * @since TBD
     */
    public function register_routes() {
        register_rest_route(
            'asap/v1', // Namespace
            '/check-sk-session', // Route
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'check_session_status'],
                'permission_callback' => '__return_true', // Allow public access - validation happens inside
            ]
        );
    }

    /**
     * Callback for the /check-sk-session endpoint.
     *
     * Checks if the user making the request (via browser cookies) has a valid
     * WordPress session and if the SvelteKit auto-login feature is enabled.
     * Relies on the 'determine_current_user' filter for origin validation.
     *
     * @since TBD
     * @param WP_REST_Request $request The incoming request object.
     * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
     */
    public function check_session_status( WP_REST_Request $request ) {
        $is_logged_in = is_user_logged_in(); // This implicitly uses our origin filter
        $user_id      = $is_logged_in ? get_current_user_id() : null;
        
        // Retrieve the setting that enables/disables this feature globally.
        // Default to false (disabled) if the option doesn't exist.
        $autosync_enabled = (bool) get_option( 'asap_enable_sk_autologin', false );

        $response_data = [
            'loggedIn'       => $is_logged_in,
            'autosyncActive' => $autosync_enabled, // Based on the global setting
            'userId'         => $user_id,
        ];

        return new WP_REST_Response( $response_data, 200 );
    }
}

// Hook the route registration into the 'rest_api_init' action.
add_action('rest_api_init', function () {
    $controller = new SK_Session_Check();
    $controller->register_routes();
}); 