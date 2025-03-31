<?php
/**
 * ASAP Digest REST API Digest Controller
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_REST_Digest
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_REST_Digest extends ASAP_Digest_REST_Base {
    /**
     * Constructor
     */
    public function __construct() {
        $this->rest_base = 'digest';
        parent::__construct();
    }

    /**
     * Register routes for digest endpoints
     */
    public function register_routes() {
        // Get digest settings
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/settings',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_settings'],
                    'permission_callback' => [$this, 'admin_permissions_check'],
                ]
            ]
        );

        // Update digest settings
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

        // Get digest stats
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/stats',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_stats'],
                    'permission_callback' => [$this, 'permissions_check'],
                ]
            ]
        );
    }

    /**
     * Get digest settings
     *
     * @param WP_REST_Request $request Request object
     * @return mixed Response object or WP_Error
     */
    public function get_settings($request) {
        $settings = $this->get_database()->get_digest_settings();
        
        if (is_wp_error($settings)) {
            return $this->prepare_error_response(
                'settings_fetch_failed',
                __('Failed to fetch digest settings.', 'asap-digest'),
                500
            );
        }

        return $this->prepare_response($settings);
    }

    /**
     * Update digest settings
     *
     * @param WP_REST_Request $request Request object
     * @return mixed Response object or WP_Error
     */
    public function update_settings($request) {
        $params = $request->get_params();
        
        $result = $this->get_database()->update_digest_settings($params);
        
        if (is_wp_error($result)) {
            return $this->prepare_error_response(
                'settings_update_failed',
                __('Failed to update digest settings.', 'asap-digest'),
                500
            );
        }

        // Track settings update
        $this->get_usage_tracker()->track_event('digest_settings_updated');

        return $this->prepare_response([
            'message' => __('Settings updated successfully.', 'asap-digest'),
            'settings' => $result
        ]);
    }

    /**
     * Get digest stats
     *
     * @param WP_REST_Request $request Request object
     * @return mixed Response object or WP_Error
     */
    public function get_stats($request) {
        $stats = $this->get_database()->get_digest_stats();
        
        if (is_wp_error($stats)) {
            return $this->prepare_error_response(
                'stats_fetch_failed',
                __('Failed to fetch digest stats.', 'asap-digest'),
                500
            );
        }

        return $this->prepare_response($stats);
    }

    /**
     * Get settings endpoint arguments
     *
     * @return array Endpoint arguments
     */
    private function get_settings_args() {
        return [
            'frequency' => [
                'type' => 'string',
                'required' => true,
                'enum' => ['daily', 'weekly', 'monthly']
            ],
            'send_time' => [
                'type' => 'string',
                'required' => true,
                'pattern' => '^\d{2}:\d{2}$'
            ],
            'categories' => [
                'type' => 'array',
                'required' => false,
                'items' => [
                    'type' => 'integer'
                ]
            ],
            'max_posts' => [
                'type' => 'integer',
                'required' => false,
                'minimum' => 1,
                'maximum' => 50
            ]
        ];
    }
} 