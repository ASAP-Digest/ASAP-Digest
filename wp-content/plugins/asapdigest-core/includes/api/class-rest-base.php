<?php
/**
 * ASAP Digest REST API Base Controller
 * 
 * Base controller class for all REST API endpoints.
 * 
 * @package ASAPDigest_Core
 * @created 05.16.25 | 03:37 PM PDT
 * @file-marker ASAP_Digest_REST_Base
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Controller;
use function rest_ensure_response;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API Base Controller class
 * 
 * Provides common functionality for all REST API controllers
 * 
 * @since 2.2.0
 */
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
        // Suppress PHP errors from appearing in REST API output
        add_filter('rest_suppress_error_output', '__return_true');
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
     * 
     * This method should be implemented by child classes to register routes
     * 
     * @return void
     */
    public function register_routes() {
        // Child classes should override this method
    }

    /**
     * Check read permission
     * 
     * @param mixed $request Request object
     * @return bool True if user can read, false otherwise
     */
    public function check_read_permission($request) {
        return current_user_can('read');
    }

    /**
     * Check create/edit permission
     * 
     * @param mixed $request Request object
     * @return bool True if user can edit posts, false otherwise
     */
    public function check_edit_permission($request) {
        return current_user_can('edit_posts');
    }

    /**
     * Check admin permission
     * 
     * @param mixed $request Request object
     * @return bool True if user is admin, false otherwise
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }

    /**
     * Format item for response
     * 
     * @param mixed $item Item to format
     * @param string $context Context (view, edit, etc)
     * @return array Formatted item
     */
    protected function format_item_for_response($item, $context = 'view') {
        return (array) $item;
    }

    /**
     * Get item schema
     * 
     * @return array Schema array
     */
    public function get_item_schema() {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'asap_' . $this->rest_base,
            'type' => 'object',
            'properties' => []
        ];
    }

    /**
     * Prepare item for database
     * 
     * @param array $request_data Request data
     * @return array|WP_Error Data or error
     */
    protected function prepare_item_for_database($request_data) {
        return $request_data;
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