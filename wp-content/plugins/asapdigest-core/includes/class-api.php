/**
 * Register routes
 */
public function register_routes() {
    // ... existing routes ...
    
    // Add new route for getting recommended HuggingFace models
    register_rest_route('asapdigest/v1', '/ai/models/recommended', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_recommended_models'),
        'permission_callback' => array($this, 'check_admin_permission'),
    ));
    
    // ... other existing routes ...
}

/**
 * Get recommended models from HuggingFace
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
public function get_recommended_models($request) {
    // Get the adapter
    $ai_manager = new ASAP_Digest_AI_Manager();
    $adapter = $ai_manager->get_adapter('huggingface');
    
    if (!$adapter) {
        return new WP_REST_Response(array(
            'success' => false,
            'data' => array(
                'message' => 'HuggingFace adapter not found',
                'code' => 'adapter_not_found'
            )
        ), 404);
    }
    
    // Get recommended models
    $models = $adapter->get_recommended_models();
    
    return new WP_REST_Response(array(
        'success' => true,
        'data' => array(
            'models' => $models,
        )
    ), 200);
} 