<?php
/**
 * @file-marker ASAP_Digest_AIServiceManager
 * @location /wp-content/plugins/asapdigest-core/includes/ai/class-ai-service-manager.php
 */

namespace ASAPDigest\AI;

use ASAPDigest\Core\ErrorLogger;

/**
 * AI Service Manager
 * 
 * Manages AI service providers, selects the appropriate provider for each task,
 * and provides a unified interface for AI operations.
 *
 * Error Handling & Logging:
 *   - All critical errors and exceptions are logged using the ErrorLogger utility (see \ASAPDigest\Core\ErrorLogger).
 *   - Errors are recorded in the wp_asap_error_log table with context, type, message, data, and severity.
 *   - PHP error_log is used as a fallback and for development/debugging.
 *   - This ensures a unified, queryable error log for admin monitoring and alerting.
 *
 * @see \ASAPDigest\Core\ErrorLogger
 */
class AIServiceManager {
    /**
     * @var array Registered AI provider adapters
     */
    private $providers = [];
    
    /**
     * @var string|null Default provider
     */
    private $default_provider = null;
    
    /**
     * @var array Provider preferences by task
     */
    private $task_preferences = [];
    
    /**
     * @var array Usage statistics
     */
    private $usage_stats = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load configuration
        $this->load_config();
        
        // Register hooks
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        
        // Register default providers
        $this->register_default_providers();
    }
    
    /**
     * Load provider configuration from options
     */
    private function load_config() {
        $config = get_option('asap_ai_config', []);
        
        if (!empty($config['default_provider'])) {
            $this->default_provider = $config['default_provider'];
        }
        
        if (!empty($config['task_preferences']) && is_array($config['task_preferences'])) {
            $this->task_preferences = $config['task_preferences'];
        }
    }
    
    /**
     * Register default AI providers
     */
    private function register_default_providers() {
        // Register OpenAI provider if class and API key exist
        if (class_exists('AsapDigest\\AI\\Adapters\\OpenAIAdapter')) {
            $openai_key = get_option('asap_ai_openai_key', '');
            if (!empty($openai_key)) {
                $this->register_provider('openai', new Adapters\OpenAIAdapter([
                    'api_key' => $openai_key,
                    'model' => get_option('asap_ai_openai_model', 'gpt-3.5-turbo'),
                ]));
            }
        }
        
        // Register Hugging Face provider if class and API key exist
        if (class_exists('AsapDigest\\AI\\Adapters\\HuggingFaceAdapter')) {
            $huggingface_key = get_option('asap_ai_huggingface_key', '');
            if (!empty($huggingface_key)) {
                $this->register_provider('huggingface', new Adapters\HuggingFaceAdapter([
                    'api_key' => $huggingface_key,
                ]));
            }
        }
        
        // Register Anthropic provider if class and API key exist
        if (class_exists('AsapDigest\\AI\\Adapters\\AnthropicAdapter')) {
            $anthropic_key = get_option('asap_ai_anthropic_key', '');
            if (!empty($anthropic_key)) {
                $this->register_provider('anthropic', new Adapters\AnthropicAdapter([
                    'api_key' => $anthropic_key,
                    'model' => get_option('asap_ai_anthropic_model', 'claude-2.1'),
                ]));
            }
        }
        
        // Allow third-party providers to be registered
        do_action('asap_register_ai_providers', $this);
    }
    
    /**
     * Register a provider adapter
     * 
     * @param string $name Provider name
     * @param object $provider Provider adapter instance
     * @return bool Success
     */
    public function register_provider($name, $provider) {
        // Check if provider has required methods
        if (!$this->validate_provider($provider)) {
            return false;
        }
        
        $this->providers[$name] = $provider;
        
        // Set as default if we don't have one yet
        if ($this->default_provider === null) {
            $this->default_provider = $name;
        }
        
        return true;
    }
    
    /**
     * Check if a provider implements the required interface
     * 
     * @param object $provider Provider instance
     * @return bool Is valid provider
     */
    private function validate_provider($provider) {
        // Check if provider implements the AI_Provider_Interface
        if (!($provider instanceof Interfaces\AI_Provider_Interface)) {
            ErrorLogger::log('ai_service', 'invalid_provider', 'Provider does not implement AI_Provider_Interface', [
                'provider_class' => get_class($provider)
            ], 'error');
            return false;
        }
        
        // Check required methods for backward compatibility with existing code
        $required_methods = [
            'summarize',
            'extract_entities',
            'classify',
            'generate_keywords',
            'calculate_quality_score',
            'test_connection',
            'get_capabilities',
        ];
        
        foreach ($required_methods as $method) {
            if (!method_exists($provider, $method)) {
                ErrorLogger::log('ai_service', 'missing_method', "Provider missing required method: {$method}", [
                    'provider_class' => get_class($provider)
                ], 'error');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get a provider instance by name
     * 
     * @param string $name Provider name
     * @return object|null Provider instance or null if not found
     */
    public function get_provider($name) {
        return isset($this->providers[$name]) ? $this->providers[$name] : null;
    }
    
    /**
     * Get the best provider for a task
     * 
     * @param string $task Task name
     * @return object|null Provider instance or null if none available
     */
    public function get_provider_for_task($task) {
        // Check if we have a preferred provider for this task
        if (isset($this->task_preferences[$task]) && isset($this->providers[$this->task_preferences[$task]])) {
            return $this->providers[$this->task_preferences[$task]];
        }
        
        // Fall back to default provider
        if ($this->default_provider !== null && isset($this->providers[$this->default_provider])) {
            return $this->providers[$this->default_provider];
        }
        
        // If all else fails, return the first available provider
        return !empty($this->providers) ? reset($this->providers) : null;
    }
    
    /**
     * Generate a text summary
     * 
     * @param string $text Text to summarize
     * @param array $options Summarization options
     * @return string Summary
     */
    public function summarize($text, $options = []) {
        $provider = $this->get_provider_for_task('summarize');
        if (!$provider) {
            /**
             * Log missing provider using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'no_provider', severity: 'error'.
             * Includes task and options for debugging.
             */
            ErrorLogger::log('ai_service', 'no_provider', 'No AI provider available for summarization', [
                'task' => 'summarize',
                'options' => $options
            ], 'error');
            throw new \Exception("No AI provider available for summarization");
        }
        $this->track_usage('summarize', $provider, strlen($text));
        try {
            return $provider->summarize($text, $options);
        } catch (\Exception $e) {
            /**
             * Log provider exception using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'provider_exception', severity: 'critical'.
             * Includes exception message, task, and options for debugging.
             */
            ErrorLogger::log('ai_service', 'provider_exception', $e->getMessage(), [
                'task' => 'summarize',
                'options' => $options,
                'text_length' => strlen($text)
            ], 'critical');
            throw $e;
        }
    }
    
    /**
     * Extract entities from text
     * 
     * @param string $text Text to analyze
     * @param array $options Extraction options
     * @return array Entities
     */
    public function extract_entities($text, $options = []) {
        $provider = $this->get_provider_for_task('extract_entities');
        if (!$provider) {
            /**
             * Log missing provider using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'no_provider', severity: 'error'.
             * Includes task and options for debugging.
             */
            ErrorLogger::log('ai_service', 'no_provider', 'No AI provider available for entity extraction', [
                'task' => 'extract_entities',
                'options' => $options
            ], 'error');
            throw new \Exception("No AI provider available for entity extraction");
        }
        $this->track_usage('extract_entities', $provider, strlen($text));
        try {
            return $provider->extract_entities($text, $options);
        } catch (\Exception $e) {
            /**
             * Log provider exception using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'provider_exception', severity: 'critical'.
             * Includes exception message, task, and options for debugging.
             */
            ErrorLogger::log('ai_service', 'provider_exception', $e->getMessage(), [
                'task' => 'extract_entities',
                'options' => $options,
                'text_length' => strlen($text)
            ], 'critical');
            throw $e;
        }
    }
    
    /**
     * Classify content into categories
     * 
     * @param string $text Text to classify
     * @param array $categories Categories to choose from
     * @param array $options Classification options
     * @return array Classifications with confidence scores
     */
    public function classify($text, $categories = [], $options = []) {
        $provider = $this->get_provider_for_task('classify');
        if (!$provider) {
            /**
             * Log missing provider using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'no_provider', severity: 'error'.
             * Includes task, categories, and options for debugging.
             */
            ErrorLogger::log('ai_service', 'no_provider', 'No AI provider available for classification', [
                'task' => 'classify',
                'categories' => $categories,
                'options' => $options
            ], 'error');
            throw new \Exception("No AI provider available for classification");
        }
        $this->track_usage('classify', $provider, strlen($text));
        try {
            return $provider->classify($text, $categories, $options);
        } catch (\Exception $e) {
            /**
             * Log provider exception using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'provider_exception', severity: 'critical'.
             * Includes exception message, task, categories, and options for debugging.
             */
            ErrorLogger::log('ai_service', 'provider_exception', $e->getMessage(), [
                'task' => 'classify',
                'categories' => $categories,
                'options' => $options,
                'text_length' => strlen($text)
            ], 'critical');
            throw $e;
        }
    }
    
    /**
     * Generate keywords from text
     * 
     * @param string $text Text to analyze
     * @param array $options Keyword generation options
     * @return array Keywords
     */
    public function generate_keywords($text, $options = []) {
        $provider = $this->get_provider_for_task('generate_keywords');
        if (!$provider) {
            /**
             * Log missing provider using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'no_provider', severity: 'error'.
             * Includes task and options for debugging.
             */
            ErrorLogger::log('ai_service', 'no_provider', 'No AI provider available for keyword generation', [
                'task' => 'generate_keywords',
                'options' => $options
            ], 'error');
            throw new \Exception("No AI provider available for keyword generation");
        }
        $this->track_usage('generate_keywords', $provider, strlen($text));
        try {
            return $provider->generate_keywords($text, $options);
        } catch (\Exception $e) {
            /**
             * Log provider exception using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'provider_exception', severity: 'critical'.
             * Includes exception message, task, and options for debugging.
             */
            ErrorLogger::log('ai_service', 'provider_exception', $e->getMessage(), [
                'task' => 'generate_keywords',
                'options' => $options,
                'text_length' => strlen($text)
            ], 'critical');
            throw $e;
        }
    }
    
    /**
     * Process an image for description or analysis
     * 
     * @param string $image_url URL to image
     * @param array $options Image processing options
     * @return array Image analysis results
     */
    public function process_image($image_url, $options = []) {
        $provider = $this->get_provider_for_task('process_image');
        if (!$provider || !method_exists($provider, 'process_image')) {
            /**
             * Log missing provider using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'no_provider', severity: 'error'.
             * Includes task and options for debugging.
             */
            ErrorLogger::log('ai_service', 'no_provider', 'No AI provider available for image processing', [
                'task' => 'process_image',
                'options' => $options
            ], 'error');
            throw new \Exception("No AI provider available for image processing");
        }
        $this->track_usage('process_image', $provider, 1); // Count 1 image
        try {
            return $provider->process_image($image_url, $options);
        } catch (\Exception $e) {
            /**
             * Log provider exception using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'provider_exception', severity: 'critical'.
             * Includes exception message, task, and options for debugging.
             */
            ErrorLogger::log('ai_service', 'provider_exception', $e->getMessage(), [
                'task' => 'process_image',
                'options' => $options,
                'image_url' => $image_url
            ], 'critical');
            throw $e;
        }
    }
    
    /**
     * Track provider usage
     * 
     * @param string $task Task name
     * @param object $provider Provider instance
     * @param int $size Size of input (characters, tokens, etc.)
     */
    private function track_usage($task, $provider, $size) {
        $provider_name = array_search($provider, $this->providers, true);
        if (!$provider_name) {
            return;
        }
        
        // Initialize usage stats
        if (!isset($this->usage_stats[$provider_name])) {
            $this->usage_stats[$provider_name] = [];
        }
        
        if (!isset($this->usage_stats[$provider_name][$task])) {
            $this->usage_stats[$provider_name][$task] = [
                'calls' => 0,
                'size' => 0,
            ];
        }
        
        // Update stats
        $this->usage_stats[$provider_name][$task]['calls']++;
        $this->usage_stats[$provider_name][$task]['size'] += $size;
        
        // Save to database periodically
        if ($this->usage_stats[$provider_name][$task]['calls'] % 10 === 0) {
            $this->save_usage_stats();
        }
    }
    
    /**
     * Save usage statistics to the database
     */
    private function save_usage_stats() {
        update_option('asap_ai_usage_stats', $this->usage_stats);
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        register_setting('asap_ai_settings', 'asap_ai_config', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_config'],
        ]);
        
        // Provider API keys
        register_setting('asap_ai_settings', 'asap_ai_openai_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        register_setting('asap_ai_settings', 'asap_ai_huggingface_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        register_setting('asap_ai_settings', 'asap_ai_anthropic_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        // Provider models
        register_setting('asap_ai_settings', 'asap_ai_openai_model', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        register_setting('asap_ai_settings', 'asap_ai_anthropic_model', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }
    
    /**
     * Sanitize AI config
     * 
     * @param array $value Config array
     * @return array Sanitized config
     */
    public function sanitize_config($value) {
        if (!is_array($value)) {
            return [];
        }
        
        $sanitized = [];
        
        if (isset($value['default_provider'])) {
            $sanitized['default_provider'] = sanitize_text_field($value['default_provider']);
        }
        
        if (isset($value['task_preferences']) && is_array($value['task_preferences'])) {
            $sanitized['task_preferences'] = [];
            foreach ($value['task_preferences'] as $task => $provider) {
                $sanitized['task_preferences'][sanitize_text_field($task)] = sanitize_text_field($provider);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_api_endpoints() {
        register_rest_route('asap/v1', '/ai/providers', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_providers'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        
        register_rest_route('asap/v1', '/ai/summarize', [
            'methods' => 'POST',
            'callback' => [$this, 'api_summarize'],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/ai/entities', [
            'methods' => 'POST',
            'callback' => [$this, 'api_extract_entities'],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/ai/classify', [
            'methods' => 'POST',
            'callback' => [$this, 'api_classify'],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/ai/keywords', [
            'methods' => 'POST',
            'callback' => [$this, 'api_generate_keywords'],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/ai/usage', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_usage'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
    }
    
    /**
     * API endpoint to get available providers
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_get_providers($request) {
        $providers = [];
        foreach ($this->providers as $name => $provider) {
            $providers[] = [
                'name' => $name,
                'capabilities' => $this->get_provider_capabilities($provider),
            ];
        }
        
        return rest_ensure_response([
            'providers' => $providers,
            'default_provider' => $this->default_provider,
            'task_preferences' => $this->task_preferences,
        ]);
    }
    
    /**
     * Get capabilities of a provider
     * 
     * @param object $provider Provider instance
     * @return array Capabilities
     */
    private function get_provider_capabilities($provider) {
        $capabilities = [
            'summarize' => method_exists($provider, 'summarize'),
            'extract_entities' => method_exists($provider, 'extract_entities'),
            'classify' => method_exists($provider, 'classify'),
            'generate_keywords' => method_exists($provider, 'generate_keywords'),
            'process_image' => method_exists($provider, 'process_image'),
        ];
        
        return $capabilities;
    }
    
    /**
     * API endpoint to summarize text
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_summarize($request) {
        try {
            $params = $request->get_json_params();
            
            if (empty($params['text'])) {
                return new \WP_Error('missing_text', 'Text parameter is required', ['status' => 400]);
            }
            
            $options = !empty($params['options']) ? $params['options'] : [];
            $summary = $this->summarize($params['text'], $options);
            
            return rest_ensure_response(['summary' => $summary]);
        } catch (\Exception $e) {
            return new \WP_Error('summarize_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * API endpoint to extract entities
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_extract_entities($request) {
        try {
            $params = $request->get_json_params();
            
            if (empty($params['text'])) {
                return new \WP_Error('missing_text', 'Text parameter is required', ['status' => 400]);
            }
            
            $options = !empty($params['options']) ? $params['options'] : [];
            $entities = $this->extract_entities($params['text'], $options);
            
            return rest_ensure_response(['entities' => $entities]);
        } catch (\Exception $e) {
            return new \WP_Error('entities_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * API endpoint to classify text
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_classify($request) {
        try {
            $params = $request->get_json_params();
            
            if (empty($params['text'])) {
                return new \WP_Error('missing_text', 'Text parameter is required', ['status' => 400]);
            }
            
            $categories = !empty($params['categories']) ? $params['categories'] : [];
            $options = !empty($params['options']) ? $params['options'] : [];
            $classifications = $this->classify($params['text'], $categories, $options);
            
            return rest_ensure_response(['classifications' => $classifications]);
        } catch (\Exception $e) {
            return new \WP_Error('classify_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * API endpoint to generate keywords
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_generate_keywords($request) {
        try {
            $params = $request->get_json_params();
            
            if (empty($params['text'])) {
                return new \WP_Error('missing_text', 'Text parameter is required', ['status' => 400]);
            }
            
            $options = !empty($params['options']) ? $params['options'] : [];
            $keywords = $this->generate_keywords($params['text'], $options);
            
            return rest_ensure_response(['keywords' => $keywords]);
        } catch (\Exception $e) {
            return new \WP_Error('keywords_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * API endpoint to get usage statistics
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_get_usage($request) {
        $usage = get_option('asap_ai_usage_stats', []);
        return rest_ensure_response(['usage' => $usage]);
    }
    
    /**
     * Calculate quality score for content
     * 
     * @param string $text Text to analyze
     * @param array $options Quality scoring options
     * @return array Quality score results with breakdown
     */
    public function calculate_quality_score($text, $options = []) {
        $provider = $this->get_provider_for_task('calculate_quality_score');
        if (!$provider) {
            /**
             * Log missing provider using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'no_provider', severity: 'error'.
             * Includes task and options for debugging.
             */
            ErrorLogger::log('ai_service', 'no_provider', 'No AI provider available for quality scoring', [
                'task' => 'calculate_quality_score',
                'options' => $options
            ], 'error');
            throw new \Exception("No AI provider available for quality scoring");
        }
        $this->track_usage('calculate_quality_score', $provider, strlen($text));
        try {
            return $provider->calculate_quality_score($text, $options);
        } catch (\Exception $e) {
            /**
             * Log provider exception using ErrorLogger utility.
             * Context: 'ai_service', error_type: 'provider_exception', severity: 'critical'.
             * Includes exception message, task, and options for debugging.
             */
            ErrorLogger::log('ai_service', 'provider_exception', $e->getMessage(), [
                'task' => 'calculate_quality_score',
                'options' => $options,
                'text_length' => strlen($text)
            ], 'critical');
            throw $e;
        }
    }
} 