<?php
/**
 * ASAP Digest REST API - AI Config Controller
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 * @created 05.15.25 | 10:20 PM PDT
 * @file-marker REST_AI_Config
 */

namespace AsapDigest\Core\API;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Config REST Controller
 *
 * Handles AI configuration and AI processor test endpoints
 *
 * @since 3.0.0
 */
class REST_AI_Config extends WP_REST_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'asap/v1';
        $this->rest_base = 'ai';
    }
    
    /**
     * Register routes
     */
    public function register_routes() {
        // GET /asap/v1/ai/providers - List available AI providers
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/providers',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_providers'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                ],
            ]
        );
        
        // POST /asap/v1/ai/summarize - Generate content summaries
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/summarize',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'summarize_text'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args'                => $this->get_text_processing_args(),
                ],
            ]
        );
        
        // POST /asap/v1/ai/entities - Extract entities from content
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/entities',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'extract_entities'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args'                => $this->get_text_processing_args(),
                ],
            ]
        );
        
        // POST /asap/v1/ai/keywords - Extract keywords from content
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/keywords',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'extract_keywords'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args'                => $this->get_text_processing_args(),
                ],
            ]
        );
        
        // POST /asap/v1/ai/classify - Classify content
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/classify',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'classify_text'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args'                => $this->get_text_processing_args(),
                ],
            ]
        );
        
        // GET /asap/v1/ai/usage - Get AI usage statistics
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/usage',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_usage'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                ],
            ]
        );
    }
    
    /**
     * Get common arguments for text processing endpoints
     *
     * @return array
     */
    protected function get_text_processing_args() {
        return [
            'text' => [
                'required'          => true,
                'type'              => 'string',
                'description'       => __('The text to process', 'asapdigest-core'),
                'validate_callback' => function($param) {
                    return is_string($param) && !empty($param);
                },
            ],
            'options' => [
                'required'          => false,
                'type'              => 'object',
                'description'       => __('Processing options', 'asapdigest-core'),
                'default'           => [],
            ],
        ];
    }
    
    /**
     * Check if a given request has permission to get items
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request) {
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to access this resource.', 'asapdigest-core'),
                ['status' => 403]
            );
        }
        return true;
    }
    
    /**
     * Check if a given request has permission to update items
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request) {
        return $this->get_items_permissions_check($request);
    }
    
    /**
     * Get available AI providers
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_providers($request) {
        try {
            // Check if the AI Service Manager exists and is initialized
            if (!class_exists('\\ASAPDigest\\AI\\AIServiceManager')) {
                require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ai/class-ai-service-manager.php';
            }
            
            $service_manager = new \ASAPDigest\AI\AIServiceManager();
            $providers = array();
            // Loop through available providers
            foreach (array('openai', 'huggingface', 'anthropic') as $provider_name) {
                $provider = $service_manager->get_provider($provider_name);
                if ($provider) {
                    $providers[] = array(
                        'name' => $provider_name,
                        'capabilities' => method_exists($provider, 'get_capabilities') ? $provider->get_capabilities() : array()
                    );
                }
            }
            
            return new WP_REST_Response($providers, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'ai_providers_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Generate a summary for the provided text
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function summarize_text($request) {
        try {
            $text = $request->get_param('text');
            $options = $request->get_param('options') ?: [];
            
            // Load the required AI processors
            if (!class_exists('\\ASAPDigest\\AI\\Processors\\Summarizer')) {
                require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ai/processors/class-summarizer.php';
            }
            
            $summarizer = new \ASAPDigest\AI\Processors\Summarizer();
            $summary = $summarizer->summarize($text, $options);
            
            return new WP_REST_Response(['summary' => $summary], 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'summarization_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Extract entities from the provided text
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function extract_entities($request) {
        try {
            $text = $request->get_param('text');
            $options = $request->get_param('options') ?: [];
            
            // Load the required AI processors
            if (!class_exists('\\ASAPDigest\\AI\\Processors\\EntityExtractor')) {
                require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ai/processors/class-entity-extractor.php';
            }
            
            $entity_extractor = new \ASAPDigest\AI\Processors\EntityExtractor();
            $entities = $entity_extractor->extract($text, $options);
            
            return new WP_REST_Response(['entities' => $entities], 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'entity_extraction_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Extract keywords from the provided text
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function extract_keywords($request) {
        try {
            $text = $request->get_param('text');
            $options = $request->get_param('options') ?: [];
            
            // Load the required AI processors
            if (!class_exists('\\ASAPDigest\\AI\\Processors\\KeywordGenerator')) {
                require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ai/processors/class-keyword-generator.php';
            }
            
            $keyword_generator = new \ASAPDigest\AI\Processors\KeywordGenerator();
            $keywords = $keyword_generator->generate($text, $options);
            
            return new WP_REST_Response(['keywords' => $keywords], 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'keyword_extraction_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Classify the provided text
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function classify_text($request) {
        try {
            $text = $request->get_param('text');
            $options = $request->get_param('options') ?: [];
            
            // Load the required AI processors
            if (!class_exists('\\ASAPDigest\\AI\\Processors\\Classifier')) {
                require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ai/processors/class-classifier.php';
            }
            
            $classifier = new \ASAPDigest\AI\Processors\Classifier();
            $classification = $classifier->classify($text, $options);
            
            return new WP_REST_Response(['classification' => $classification], 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'classification_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get AI usage statistics
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_usage($request) {
        try {
            // Check if the AI Service Manager exists and is initialized
            if (!class_exists('\\ASAPDigest\\AI\\AIServiceManager')) {
                require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ai/class-ai-service-manager.php';
            }
            
            $service_manager = new \ASAPDigest\AI\AIServiceManager();
            $usage = get_option('asap_ai_usage_stats', array());
            
            return new WP_REST_Response($usage, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'ai_usage_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
} 