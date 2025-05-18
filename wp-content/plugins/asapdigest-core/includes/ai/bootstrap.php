<?php
/**
 * AI System Bootstrap
 *
 * Initializes and registers AI components for the ASAP Digest Core plugin.
 *
 * @package ASAPDigest_Core
 * @subpackage AI
 * @since 3.1.0
 * @file-marker ASAP_Digest_AI_Bootstrap
 * @created 05/07/25 | 06:00 PM PDT
 */

namespace ASAPDigest\AI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize all AI system components and register hooks.
 *
 * @return void
 */
function bootstrap() {
    // Load core AI components
    load_core_components();
    
    // Load processor components
    load_processor_components();
    
    // Register global AI hooks
    register_ai_hooks();
    
    // Initialize AI API class
    $ai_api = new ASAP_Digest_AI_API();
    $ai_api->init();
    
    // Allow plugins to extend or modify AI system
    do_action('asapdigest_ai_system_loaded');
}

/**
 * Load core AI components.
 *
 * @return void
 */
function load_core_components() {
    $component_files = [
        // Core AI Service Manager
        'class-ai-service-manager.php',
        
        // API Classes
        'class-ai-api.php',
        
        // Interfaces
        'interfaces/interface-ai-provider.php',
        'interfaces/class-ai-debuggable.php',
        
        // Adapters
        'adapters/class-openai-adapter.php',
        'adapters/class-anthropic-adapter.php',
        'adapters/class-huggingface-adapter.php'
    ];
    
    foreach ($component_files as $file) {
        $file_path = plugin_dir_path(__FILE__) . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}

/**
 * Load AI processor components.
 *
 * @return void
 */
function load_processor_components() {
    // Load processor bootstrap which will handle loading all processor classes
    $processors_bootstrap = plugin_dir_path(__FILE__) . 'processors/bootstrap.php';
    if (file_exists($processors_bootstrap)) {
        require_once $processors_bootstrap;
    }
}

/**
 * Register global AI hooks.
 *
 * @return void
 */
function register_ai_hooks() {
    // Hook AI enhancements into the content handling pipeline
    add_filter('asapdigest_content_processed', __NAMESPACE__ . '\filter_enhance_content', 10, 2);
    
    // Hook into admin initialization to register settings
    add_action('admin_init', __NAMESPACE__ . '\register_ai_settings');
    
    // Register REST API endpoints for AI services
    add_action('rest_api_init', __NAMESPACE__ . '\register_ai_endpoints');
}

/**
 * Filter to enhance content with AI features.
 *
 * @param array $content_data Content data
 * @param array $options Processing options
 * @return array Enhanced content data
 */
function filter_enhance_content($content_data, $options = []) {
    // Skip if AI processing is disabled in options
    if (isset($options['skip_ai']) && $options['skip_ai']) {
        return $content_data;
    }
    
    // Apply processor enhancements if processors are loaded
    if (function_exists('\ASAPDigest\AI\Processors\filter_enhance_content_with_ai')) {
        $content_data = \ASAPDigest\AI\Processors\filter_enhance_content_with_ai($content_data, $options);
    }
    
    return $content_data;
}

/**
 * Register AI settings.
 *
 * @return void
 */
function register_ai_settings() {
    // AI provider settings
    register_setting('asap_ai_settings', 'asap_ai_default_provider', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    register_setting('asap_ai_settings', 'asap_ai_task_preferences', [
        'type' => 'array',
        'sanitize_callback' => function($value) {
            if (!is_array($value)) {
                return [];
            }
            
            $sanitized = [];
            foreach ($value as $task => $provider) {
                $sanitized[sanitize_text_field($task)] = sanitize_text_field($provider);
            }
            
            return $sanitized;
        },
    ]);
    
    // Usage tracking settings
    register_setting('asap_ai_settings', 'asap_ai_usage_budget', [
        'type' => 'number',
        'sanitize_callback' => 'absint',
    ]);
    
    register_setting('asap_ai_settings', 'asap_ai_usage_alert_threshold', [
        'type' => 'number',
        'sanitize_callback' => function($value) {
            return max(0, min(100, intval($value)));
        },
    ]);
}

/**
 * Register REST API endpoints for AI.
 *
 * @return void
 */
function register_ai_endpoints() {
    // Endpoint to list available providers
    register_rest_route('asap/v1', '/ai/providers', [
        'methods' => 'GET',
        'callback' => function() {
            $ai_service = new AIServiceManager();
            return $ai_service->api_get_providers(new \WP_REST_Request());
        },
        'permission_callback' => function() { 
            return current_user_can('manage_options'); 
        }
    ]);
    
    // Additional endpoints will be registered by the AI service manager and processors
}

// Initialize the AI system
bootstrap(); 