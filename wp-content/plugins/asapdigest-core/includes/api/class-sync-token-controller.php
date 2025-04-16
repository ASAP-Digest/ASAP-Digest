<?php
/**
 * Controller for handling Sync Token Validation via REST API.
 * 
 * @package ASAPDigest_Core
 * @created 04.16.25 | 12:25 PM PDT
 * @file-marker Sync_Token_Controller
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
 * Class Sync_Token_Controller
 * Handles the /asap/v1/validate-sync-token endpoint.
 */
class Sync_Token_Controller extends WP_REST_Controller {

    /**
     * Namespace for the REST route.
     * @var string
     */
    protected $namespace = 'asap/v1';

    /**
     * Route base for the controller.
     * @var string
     */
    protected $rest_base = 'validate-sync-token';

    /**
     * Register the routes for the controller.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE, // Corresponds to POST
                    'callback'            => [$this, 'validate_token'],
                    'permission_callback' => '__return_true', // Allow access, validation is via token
                    'args'                => [
                        'token' => [
                            'required'          => true,
                            'validate_callback' => function($param, $request, $key) {
                                return is_string($param) && ! empty($param);
                            },
                            'sanitize_callback' => 'sanitize_text_field',
                            'description'       => __('The sync token to validate.', 'adc'),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Validates a sync token and deletes it if valid.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function validate_token(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ba_sync_tokens';
        $token = $request->get_param('token');

        // Find the token
        $token_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, wp_user_id FROM $table_name WHERE token = %s AND expires_at > NOW() LIMIT 1",
                $token
            ),
            ARRAY_A // Return associative array
        );

        if (empty($token_data)) {
            // Token not found or expired
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired sync token.', 'adc'),
                ['status' => 401]
            );
        }

        // Token is valid, retrieve user ID
        $wp_user_id = (int) $token_data['wp_user_id'];
        $token_db_id = (int) $token_data['id'];

        // --- CRITICAL: Delete the token immediately after validation --- 
        $deleted = $wpdb->delete(
            $table_name,
            ['id' => $token_db_id], // Delete by its primary key
            ['%d']
        );

        if ($deleted === false) {
            // Log error, but still return success as validation passed before delete attempt
            error_log("ASAP Digest: Failed to delete sync token ID $token_db_id after validation. DB Error: " . $wpdb->last_error);
        } elseif ($deleted > 0) {
            error_log("ASAP Digest: Successfully validated and deleted sync token ID $token_db_id.");
        }
        // --- End Token Deletion ---

        // Return success response with the WP User ID
        $response_data = [
            'valid' => true,
            'wpUserId' => $wp_user_id,
        ];

        return new WP_REST_Response($response_data, 200);
    }
} 