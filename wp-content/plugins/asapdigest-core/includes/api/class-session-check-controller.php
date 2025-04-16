<?php
/**
 * Controller for checking WordPress session status via REST API.
 *
 * @package ASAPDigest_Core
 * @created 2025/04/15 | 18:36:53 PDT
 * @file-marker SessionCheckController
 */

namespace ASAPDigest\Core\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Session_Check_Controller
 * Handles the /wp-json/asap/v1/check-wp-session endpoint.
 */
class Session_Check_Controller extends WP_REST_Controller {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->namespace = 'asap/v1';
        $this->rest_base = 'check-wp-session';
    }

    /**
     * Registers the routes for the objects of the controller.
     */
    public function register_routes() {
        error_log('Session_Check_Controller: register_routes called'); // DEBUG LOG

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'check_session_status'],
                    'permission_callback' => '__return_true', // Endpoint is public, relies on cookie auth handled by WP core
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );
    }

    /**
     * Checks the current WordPress session status.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response Response object.
     */
    public function check_session_status(WP_REST_Request $request) {
        // Implementation will go here in the next step
        $logged_in = is_user_logged_in();
        $user_id   = $logged_in ? get_current_user_id() : null;

        // Autosync setting omitted based on earlier analysis - needs implementation if required
        $autosync_active = false; // Defaulting to false

        $response_data = [
            'loggedIn'       => $logged_in,
            'autosyncActive' => $autosync_active,
            'userId'         => $user_id,
        ];

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Retrieves the item's schema, conforming to JSON Schema.
     *
     * @return array Item schema data.
     */
    public function get_public_item_schema() {
        if ($this->schema) {
            return $this->schema;
        }

        $this->schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'wp_session_status',
            'type'       => 'object',
            'properties' => [
                'loggedIn' => [
                    'description' => esc_html__('Whether a user is logged into WordPress.', 'asap-digest'),
                    'type'        => 'boolean',
                ],
                'autosyncActive' => [
                    'description' => esc_html__('Whether auto-sync is active for the user (Currently not implemented).', 'asap-digest'),
                    'type'        => 'boolean',
                ],
                'userId' => [
                    'description' => esc_html__('The WordPress user ID if logged in, otherwise null.', 'asap-digest'),
                    'type'        => ['integer', 'null'],
                    'context'     => ['view', 'edit', 'embed'],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->schema;
    }
}
