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
        add_action('wp_ajax_asap_save_custom_hf_models', [$this, 'handle_save_custom_hf_models']);
        add_action('wp_ajax_asap_load_recommended_hf_models', [$this, 'handle_load_recommended_hf_models']);
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
        $model = sanitize_text_field($_POST['model'] ?? ''); // Get model parameter if provided
        
        try {
            // Log the request (excluding API key for security)
            ErrorLogger::log('ajax', 'ai_test_request', 'Test AI connection request received', [
                'provider' => $provider,
                'api_key_length' => strlen($api_key),
                'model' => $model ? $model : 'not provided',
            ], 'info');
            
            // Ensure required parameters are provided
            if (!$provider || !$api_key) {
                $this->send_error([
                    'message' => __('Provider and API key are required.', 'asapdigest-core'),
                    'code' => 'missing_parameters'
                ]);
            }
            
            // For Hugging Face, model is required
            if ($provider === 'huggingface' && empty($model)) {
                $this->send_error([
                    'message' => __('Hugging Face requires a model name.', 'asapdigest-core'),
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
            } elseif ($provider === 'huggingface' && class_exists('ASAPDigest\AI\Adapters\HuggingFaceAdapter')) {
                // Get the model, either from the request or fallback to the saved option
                $hf_model = !empty($model) ? $model : get_option('asap_ai_huggingface_model', 'distilbert-base-uncased');
                
                // Create adapter with both API key and model
                $adapter = new \ASAPDigest\AI\Adapters\HuggingFaceAdapter([
                    'api_key' => $api_key,
                    'models' => [
                        'test' => $hf_model // Use the provided or configured model for testing
                    ]
                ]);
                
                try {
                    // Pass the model name explicitly
                    $result = $adapter->test_connection($hf_model);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    ErrorLogger::log('ajax', 'huggingface_test_error', $error, [
                        'trace' => $e->getTraceAsString(),
                        'model' => $hf_model
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
                if (empty($error)) {
                    $error = __('Unknown error occurred. Please check your API key and network connection.', 'asapdigest-core');
                }
                $msg = $error;
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
    
    /**
     * Handle loading the recommended Hugging Face models page
     *
     * @since 3.1.0
     * @return void
     */
    public function handle_load_recommended_hf_models() {
        // Verify request
        $this->verify_request();
        
        // Include the recommended models page
        include_once ASAP_DIGEST_PLUGIN_DIR . 'admin/views/hf-models-recommended.php';
        
        // End the request
        wp_die();
    }
    
    /**
     * Handle saving custom Hugging Face models
     *
     * @since 3.1.0
     * @return void
     */
    public function handle_save_custom_hf_models() {
        // Verify request
        $this->verify_request();
        
        // Check if we're receiving the legacy format (direct models object)
        if (isset($_POST['models']) && is_array($_POST['models'])) {
            // Save the models directly
            update_option('asap_ai_custom_huggingface_models', $_POST['models']);
            
            // Return success
            wp_send_json_success([
                'message' => 'Models saved successfully',
                'models' => $_POST['models']
            ]);
            return;
        }
        
        // If not legacy format, validate operation parameter
        if (!isset($_POST['operation'])) {
            wp_send_json_error([
                'message' => 'Operation parameter is required'
            ]);
            return;
        }
        
        // Get operation
        $operation = sanitize_text_field($_POST['operation']);
        
        // Get current custom models
        $custom_models = get_option('asap_ai_custom_huggingface_models', []);
        
        // Handle the operation
        switch ($operation) {
            case 'add':
                // Validate required parameters
                if (!isset($_POST['model_id']) || !isset($_POST['model_label'])) {
                    wp_send_json_error([
                        'message' => 'Model ID and label are required'
                    ]);
                    return;
                }
                
                // Get parameters
                $model_id = sanitize_text_field($_POST['model_id']);
                $model_label = sanitize_text_field($_POST['model_label']);
                
                // Add the model
                $custom_models[$model_id] = $model_label;
                
                // Save the models
                update_option('asap_ai_custom_huggingface_models', $custom_models);
                
                // Return success
                wp_send_json_success([
                    'message' => 'Model added successfully',
                    'models' => $custom_models
                ]);
                break;
                
            case 'update':
                // Validate required parameters
                if (!isset($_POST['original_model_id']) || !isset($_POST['model_id']) || !isset($_POST['model_label'])) {
                    wp_send_json_error([
                        'message' => 'Original model ID, new model ID and label are required'
                    ]);
                    return;
                }
                
                // Get parameters
                $original_model_id = sanitize_text_field($_POST['original_model_id']);
                $model_id = sanitize_text_field($_POST['model_id']);
                $model_label = sanitize_text_field($_POST['model_label']);
                
                // Remove the original model
                if (isset($custom_models[$original_model_id])) {
                    unset($custom_models[$original_model_id]);
                }
                
                // Add the updated model
                $custom_models[$model_id] = $model_label;
                
                // Save the models
                update_option('asap_ai_custom_huggingface_models', $custom_models);
                
                // Return success
                wp_send_json_success([
                    'message' => 'Model updated successfully',
                    'models' => $custom_models
                ]);
                break;
                
            case 'delete':
                // Validate required parameters
                if (!isset($_POST['model_id'])) {
                    wp_send_json_error([
                        'message' => 'Model ID is required'
                    ]);
                    return;
                }
                
                // Get parameters
                $model_id = sanitize_text_field($_POST['model_id']);
                
                // Remove the model
                if (isset($custom_models[$model_id])) {
                    unset($custom_models[$model_id]);
                }
                
                // Save the models
                update_option('asap_ai_custom_huggingface_models', $custom_models);
                
                // Return success
                wp_send_json_success([
                    'message' => 'Model deleted successfully',
                    'models' => $custom_models
                ]);
                break;
                
            default:
                wp_send_json_error([
                    'message' => 'Invalid operation'
                ]);
                break;
        }
    }
} 