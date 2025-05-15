<?php
/**
 * ASAP Digest AI AJAX Handler
 *
 * Standardized handler for AI-related AJAX operations
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax\Admin;

use AsapDigest\AI\Adapters\AnthropicAdapter;
use AsapDigest\AI\Adapters\OpenAIAdapter;
use AsapDigest\Core\Ajax\Base_AJAX;
use AsapDigest\Core\ErrorLogger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI AJAX Handler Class
 *
 * Handles all AJAX requests related to AI operations
 *
 * @since 3.0.0
 */
class AI_Ajax extends Base_AJAX {
    
    /**
     * Nonce action for this handler
     *
     * @var string
     */
    protected $nonce_action = 'asap_digest_content_nonce';
    
    /**
     * Register AJAX actions
     *
     * @since 3.0.0
     * @return void
     */
    protected function register_actions() {
        add_action('wp_ajax_asap_test_ai_connection', [$this, 'handle_test_connection']);
    }
    
    /**
     * Handle AI connection test
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_test_connection() {
        // Verify request
        $this->verify_request();
        
        // Validate required parameters
        $this->validate_params(['provider', 'api_key']);
        
        // Get parameters
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        try {
            // Log the request (excluding API key for security)
            ErrorLogger::log('ajax', 'ai_test_request', 'Test AI connection request received', [
                'provider' => $provider,
                'api_key_length' => strlen($api_key),
            ], 'info');
            
            // Ensure required parameters are provided
            if (!$provider || !$api_key) {
                $this->send_error([
                    'message' => __('Provider and API key are required.', 'asapdigest-core'),
                    'code' => 'missing_parameters'
                ]);
            }
            
            // Load required AI adapters
            require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ai/class-ai-service-manager.php';
            
            $result = false;
            $error = '';
            $debug = '';
            
            // Initialize the appropriate adapter and test connection
            if ($provider === 'openai' && class_exists('AsapDigest\AI\Adapters\OpenAIAdapter')) {
                $adapter = new OpenAIAdapter(['api_key' => $api_key]);
                try {
                    $result = $adapter->test_connection();
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    ErrorLogger::log('ajax', 'openai_test_error', $error, [
                        'trace' => $e->getTraceAsString()
                    ], 'error');
                }
            } elseif ($provider === 'anthropic' && class_exists('AsapDigest\AI\Adapters\AnthropicAdapter')) {
                $adapter = new AnthropicAdapter(['api_key' => $api_key]);
                try {
                    $result = $adapter->test_connection();
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    ErrorLogger::log('ajax', 'anthropic_test_error', $error, [
                        'trace' => $e->getTraceAsString()
                    ], 'error');
                    
                    if (method_exists($adapter, 'get_last_response')) {
                        $debug = $adapter->get_last_response();
                    }
                }
            } else {
                $error = __('Provider not supported or adapter missing.', 'asapdigest-core');
                ErrorLogger::log('ajax', 'unsupported_provider', $error, [
                    'provider' => $provider
                ], 'error');
            }
            
            // Send the appropriate response
            if ($result === true) {
                ErrorLogger::log('ajax', 'ai_test_success', 'AI connection test successful', [
                    'provider' => $provider
                ], 'info');
                
                $this->send_success([
                    'message' => __('Connection successful!', 'asapdigest-core'),
                    'provider' => $provider
                ]);
            } else {
                $msg = $error ?: __('Connection failed.', 'asapdigest-core');
                if ($debug) {
                    $msg .= ' ' . __('Debug:', 'asapdigest-core') . ' ' . $debug;
                }
                
                $this->send_error([
                    'message' => $msg,
                    'code' => 'connection_failed',
                    'provider' => $provider
                ]);
            }
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'ai_test_exception', $e->getMessage(), [
                'provider' => $provider,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while testing the AI connection.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
} 