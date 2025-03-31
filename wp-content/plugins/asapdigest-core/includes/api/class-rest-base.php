<?php
/**
 * ASAP Digest REST API Base Controller
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_REST_Base
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Controller;
use function rest_ensure_response;

if (!defined('ABSPATH')) {
    exit;
}

abstract class ASAP_Digest_REST_Base extends WP_REST_Controller {
    /**
     * @var string API namespace
     */
    protected $namespace = 'asap/v1';

    /**
     * @var string Route base
     */
    protected $rest_base;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the controller
     */
    protected function init() {
        // Child classes should override this method
    }

    /**
     * Register routes
     */
    public function register_routes() {
        // Child classes must implement this method
    }

    /**
     * Check if a given request has admin access
     */
    public function admin_permissions_check($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('Sorry, you are not allowed to do that.', 'asap-digest'),
                ['status' => rest_authorization_required_code()]
            );
        }
        return true;
    }

    /**
     * Check if a given request has valid authentication
     */
    public function permissions_check($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You must be logged in to do this.', 'asap-digest'),
                ['status' => rest_authorization_required_code()]
            );
        }
        return true;
    }

    /**
     * Prepare response for return
     */
    protected function prepare_response($response, $is_error = false) {
        if ($is_error && !is_wp_error($response)) {
            $response = new WP_Error('error', $response);
        }

        return rest_ensure_response($response);
    }

    /**
     * Prepare error response
     */
    protected function prepare_error_response($code, $message, $status = 400) {
        return $this->prepare_response(
            new WP_Error($code, $message, ['status' => $status]),
            true
        );
    }

    /**
     * Validate request parameters
     */
    protected function validate_params($params, $required = []) {
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                return $this->prepare_error_response(
                    'missing_param',
                    sprintf(__('Missing required parameter: %s', 'asap-digest'), $param)
                );
            }
        }
        return true;
    }

    /**
     * Get current user ID
     */
    protected function get_current_user_id() {
        return get_current_user_id();
    }

    /**
     * Get plugin instance
     */
    protected function get_plugin() {
        return \ASAPDigest\Core\ASAP_Digest_Core::get_instance();
    }

    /**
     * Get database instance
     */
    protected function get_database() {
        return $this->get_plugin()->get_database();
    }

    /**
     * Get usage tracker instance
     */
    protected function get_usage_tracker() {
        return $this->get_plugin()->get_usage_tracker();
    }

    /**
     * Get Better Auth instance
     */
    protected function get_better_auth() {
        return $this->get_plugin()->get_better_auth();
    }
} 