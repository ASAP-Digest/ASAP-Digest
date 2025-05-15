<?php
/**
 * ASAP Digest Base AJAX Handler Class
 *
 * Abstract base class for all AJAX handlers
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax;

use AsapDigest\Core\ErrorLogger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Base AJAX Handler Class
 *
 * Provides common functionality for all AJAX handlers
 *
 * @since 3.0.0
 */
abstract class Base_AJAX {
    
    /**
     * Required capability for this handler
     *
     * @var string
     */
    protected $capability = 'manage_options';
    
    /**
     * Nonce action name for this handler
     *
     * @var string
     */
    protected $nonce_action = 'asap_digest_ajax_nonce';
    
    /**
     * Initialize the handler
     *
     * @since 3.0.0
     */
    public function __construct() {
        $this->register_actions();
    }
    
    /**
     * Register AJAX actions
     *
     * @since 3.0.0
     * @return void
     */
    abstract protected function register_actions();
    
    /**
     * Verify AJAX request
     *
     * Checks user capability and nonce
     *
     * @since 3.0.0
     * @param string $nonce_field Field name for the nonce
     * @param string|null $capability Custom capability to check
     * @return bool True if verified, dies on failure
     */
    protected function verify_request($nonce_field = 'nonce', $capability = null) {
        // Check nonce
        if (!isset($_REQUEST[$nonce_field]) || !wp_verify_nonce($_REQUEST[$nonce_field], $this->nonce_action)) {
            $this->send_error([
                'message' => __('Security verification failed.', 'asapdigest-core'),
                'code'    => 'invalid_nonce'
            ], 400);
        }
        
        // Check user capabilities
        $required_capability = $capability ?: $this->capability;
        if ($required_capability && !current_user_can($required_capability)) {
            $this->send_error([
                'message' => __('You do not have permission to perform this action.', 'asapdigest-core'),
                'code'    => 'insufficient_permissions'
            ], 403);
        }
        
        return true;
    }
    
    /**
     * Validate required parameters
     *
     * @since 3.0.0
     * @param array $required_params List of required parameter names
     * @param string $method Request method to check (POST, GET, or REQUEST)
     * @return bool True if all parameters exist
     */
    protected function validate_params($required_params, $method = 'POST') {
        $data = [];
        
        switch (strtoupper($method)) {
            case 'GET':
                $data = $_GET;
                break;
            case 'REQUEST':
                $data = $_REQUEST;
                break;
            case 'POST':
            default:
                $data = $_POST;
                break;
        }
        
        foreach ($required_params as $param) {
            if (!isset($data[$param])) {
                $this->send_error([
                    'message' => sprintf(__('Missing required parameter: %s', 'asapdigest-core'), $param),
                    'code'    => 'missing_parameter'
                ], 400);
            }
        }
        
        return true;
    }
    
    /**
     * Send JSON error response and end execution
     *
     * @since 3.0.0
     * @param array|string $data Error data or message
     * @param int $status HTTP status code
     * @return void
     */
    protected function send_error($data, $status = 400) {
        $error_data = is_string($data) ? ['message' => $data] : $data;
        
        // Log the error if we have the ErrorLogger available
        if (class_exists('\\AsapDigest\\Core\\ErrorLogger')) {
            $context = [
                'request' => $_REQUEST,
                'status' => $status
            ];
            
            ErrorLogger::log(
                'ajax', 
                $error_data['code'] ?? 'ajax_error', 
                $error_data['message'] ?? 'AJAX Error', 
                $context, 
                'error'
            );
        }
        
        wp_send_json_error($error_data, $status);
    }
    
    /**
     * Send JSON success response
     *
     * @since 3.0.0
     * @param array|string $data Success data or message
     * @return void
     */
    protected function send_success($data) {
        $success_data = is_string($data) ? ['message' => $data] : $data;
        wp_send_json_success($success_data);
    }
} 