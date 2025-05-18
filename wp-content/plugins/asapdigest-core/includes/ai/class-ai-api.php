<?php
/**
 * AI API
 *
 * @package ASAPDigest_Core
 * @created 07.18.25 | 04:36 PM PDT
 * @file-marker ASAP_Digest_AI_API
 */

namespace ASAPDigest\AI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ASAP Digest AI API
 */
class ASAP_Digest_AI_API {

    /**
     * Initialize the API
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register routes
     */
    public function register_routes() {
        // Route for testing adapter connections
        register_rest_route('asapdigest/v1', '/ai/test-connection', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_adapter_connection'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // Route for getting supported providers
        register_rest_route('asapdigest/v1', '/ai/providers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_providers'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // Route for getting recommended models
        register_rest_route('asapdigest/v1', '/ai/models/recommended', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recommended_models'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // Route for getting details about a specific model
        register_rest_route('asapdigest/v1', '/ai/models/details', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_model_details'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // Route for getting AI usage statistics
        register_rest_route('asapdigest/v1', '/ai/usage', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_usage_stats'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
    }

    /**
     * Check admin permission
     * 
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Test adapter connection
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function test_adapter_connection($request) {
        $params = $request->get_json_params();
        $provider = sanitize_text_field($params['provider'] ?? '');
        $api_key = sanitize_text_field($params['api_key'] ?? '');

        if (empty($provider)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => 'Provider is required',
                    'code' => 'missing_provider'
                )
            ), 400);
        }

        // Get the adapter
        $ai_manager = new AIServiceManager();
        $adapter = $ai_manager->get_provider($provider);

        if (!$adapter) {
            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => 'Provider not found',
                    'code' => 'provider_not_found'
                )
            ), 404);
        }

        // Test connection
        $result = $adapter->test_connection($api_key);

        if (is_wp_error($result)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code(),
                    'provider' => $provider
                )
            ), 400);
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'message' => 'Connection successful!',
                'provider' => $provider
            )
        ), 200);
    }

    /**
     * Get all supported AI providers
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_providers($request) {
        $ai_manager = new AIServiceManager();
        $providers = [];
        
        // Call api_get_providers which is the existing method for this
        $response = $ai_manager->api_get_providers(new \WP_REST_Request());
        
        // Check if we got a WP_REST_Response and extract providers
        if ($response instanceof \WP_REST_Response) {
            $data = $response->get_data();
            if (isset($data['providers'])) {
                $providers = $data['providers'];
            }
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'providers' => $providers,
            )
        ), 200);
    }

    /**
     * Get recommended models from HuggingFace
     * 
     * @param WP_REST_Request $request REST API request
     * @return WP_REST_Response
     */
    public function get_recommended_models($request) {
        try {
            // Get the service manager
            $ai_manager = new AIServiceManager();
            
            // Try to get HuggingFace adapter
            $adapter = $ai_manager->get_provider('huggingface');
            
            if (!$adapter) {
                return new \WP_REST_Response([
                    'success' => false,
                    'data' => [
                        'message' => 'HuggingFace adapter not found or not configured',
                        'code' => 'adapter_not_found',
                        'provider' => 'huggingface'
                    ]
                ], 404);
            }
            
            // Get recommended models from adapter
            $models = $adapter->get_recommended_models();
            
            // Return models
            return new \WP_REST_Response([
                'success' => true,
                'data' => [
                    'models' => $models,
                    'provider' => 'huggingface',
                    'message' => 'Retrieved ' . count($models) . ' model categories'
                ]
            ]);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'data' => [
                    'message' => $e->getMessage(),
                    'code' => 'api_error',
                    'provider' => 'huggingface'
                ]
            ], 500);
        }
    }

    /**
     * Get model details from the specified provider
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_model_details($request) {
        $provider = sanitize_text_field($request->get_param('provider') ?? 'huggingface');
        $model_id = sanitize_text_field($request->get_param('model_id') ?? '');
        
        if (empty($model_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => 'Model ID is required',
                    'code' => 'missing_model_id'
                )
            ), 400);
        }
        
        // Get the adapter
        $ai_manager = new AIServiceManager();
        $adapter = $ai_manager->get_provider($provider);
        
        if (!$adapter) {
            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => 'Provider adapter not found',
                    'code' => 'adapter_not_found'
                )
            ), 404);
        }
        
        // Check if the adapter has get_model_details method
        if (!method_exists($adapter, 'get_model_details')) {
            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => 'This provider does not support model details',
                    'code' => 'unsupported_feature'
                )
            ), 400);
        }
        
        // Get model details
        $details = $adapter->get_model_details($model_id);
        
        if (is_wp_error($details)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => $details->get_error_message(),
                    'code' => $details->get_error_code(),
                    'provider' => $provider
                )
            ), 400);
        }
        
        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'details' => $details,
                'provider' => $provider
            )
        ), 200);
    }

    /**
     * Get AI usage statistics
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_usage_stats($request) {
        $ai_manager = new AIServiceManager();
        $usage = [];
        
        // Call api_get_usage which is the existing method for this
        $response = $ai_manager->api_get_usage(new \WP_REST_Request());
        
        // Check if we got a WP_REST_Response and extract usage data
        if ($response instanceof \WP_REST_Response) {
            $data = $response->get_data();
            if (isset($data['usage'])) {
                $usage = $data['usage'];
            }
        }
        
        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'usage' => $usage,
            )
        ), 200);
    }
} 