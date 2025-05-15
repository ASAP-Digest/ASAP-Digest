<?php
/**
 * AI Processors Bootstrap
 *
 * Initializes and registers all AI processor classes for content enhancement.
 *
 * @package ASAPDigest_Core
 * @subpackage AI\Processors
 * @since 3.1.0
 * @file-marker ASAP_Digest_AI_Processors_Bootstrap
 * @created 05/07/25 | 05:30 PM PDT
 */

namespace ASAPDigest\AI\Processors;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize all AI processor classes and register any necessary hooks.
 *
 * This bootstrap ensures all processor classes are properly loaded and registered
 * with WordPress. It also registers any processor-specific hooks and filters.
 *
 * @return void
 */
function bootstrap() {
    // Ensure required classes are loaded
    load_processor_classes();
    
    // Register processor-specific hooks
    register_processor_hooks();
    
    // Initialize admin settings for processors
    if (is_admin()) {
        register_admin_settings();
    }
    
    // Allow plugins to extend or modify processors
    do_action('asapdigest_ai_processors_loaded');
}

/**
 * Load all AI processor classes.
 *
 * @return void
 */
function load_processor_classes() {
    $processor_classes = [
        'EntityExtractor',
        'Classifier',
        'KeywordGenerator',
        'SentimentAnalyzer',
        'Summarizer'
    ];
    
    foreach ($processor_classes as $class) {
        $file = plugin_dir_path(__FILE__) . 'class-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

/**
 * Register processor-specific hooks.
 *
 * @return void
 */
function register_processor_hooks() {
    // Register hooks for content processing pipeline integration
    add_filter('asapdigest_process_content', __NAMESPACE__ . '\filter_enhance_content_with_ai', 10, 2);
    
    // Register hooks for admin display integration
    add_filter('asapdigest_display_content_meta', __NAMESPACE__ . '\filter_display_ai_metadata', 10, 2);
    
    // Register REST API endpoints for direct access to processors
    add_action('rest_api_init', __NAMESPACE__ . '\register_processor_endpoints');
}

/**
 * Register admin settings for AI processors.
 *
 * @return void
 */
function register_admin_settings() {
    add_action('admin_init', __NAMESPACE__ . '\register_processor_settings');
}

/**
 * Filter to enhance content with AI processors.
 *
 * @param array $content_data Content data to be processed
 * @param array $options Processing options
 * @return array Enhanced content data
 */
function filter_enhance_content_with_ai($content_data, $options = []) {
    // Skip AI processing if explicitly disabled
    if (isset($options['skip_ai_processing']) && $options['skip_ai_processing']) {
        return $content_data;
    }
    
    $content = isset($content_data['content']) ? $content_data['content'] : '';
    $title = isset($content_data['title']) ? $content_data['title'] : '';
    
    // Skip empty content
    if (empty($content)) {
        return $content_data;
    }
    
    try {
        // Apply AI enhancements based on options
        if (!isset($options['ai_enhancements']) || $options['ai_enhancements'] === true || 
            (is_array($options['ai_enhancements']) && in_array('all', $options['ai_enhancements']))) {
            // Apply all enhancements
            $content_data = apply_all_enhancements($content_data);
        } else if (is_array($options['ai_enhancements'])) {
            // Apply specific enhancements
            if (in_array('summary', $options['ai_enhancements'])) {
                $content_data = add_summary($content_data);
            }
            
            if (in_array('keywords', $options['ai_enhancements'])) {
                $content_data = add_keywords($content_data);
            }
            
            if (in_array('entities', $options['ai_enhancements'])) {
                $content_data = add_entities($content_data);
            }
            
            if (in_array('categories', $options['ai_enhancements'])) {
                $content_data = add_categories($content_data);
            }
            
            if (in_array('sentiment', $options['ai_enhancements'])) {
                $content_data = add_sentiment($content_data);
            }
        }
    } catch (\Exception $e) {
        // Log error but continue with original content
        if (class_exists('\ASAPDigest\Core\ErrorLogger')) {
            \ASAPDigest\Core\ErrorLogger::log('ai_processors', 'enhancement_error', $e->getMessage(), [
                'content_id' => isset($content_data['id']) ? $content_data['id'] : null,
                'content_length' => strlen($content),
                'options' => $options
            ], 'error');
        } else {
            error_log('[ASAP AI Processors] Error: ' . $e->getMessage());
        }
    }
    
    return $content_data;
}

/**
 * Apply all AI enhancements to content.
 *
 * @param array $content_data Content data
 * @return array Enhanced content data
 */
function apply_all_enhancements($content_data) {
    $content_data = add_summary($content_data);
    $content_data = add_keywords($content_data);
    $content_data = add_entities($content_data);
    $content_data = add_categories($content_data);
    $content_data = add_sentiment($content_data);
    
    return $content_data;
}

/**
 * Add AI-generated summary to content.
 *
 * @param array $content_data Content data
 * @return array Content data with summary
 */
function add_summary($content_data) {
    $content = isset($content_data['content']) ? $content_data['content'] : '';
    
    if (empty($content)) {
        return $content_data;
    }
    
    $summarizer = new Summarizer();
    $summary_options = [
        'max_tokens' => 150,
        'include_key_points' => true,
        'include_headline' => true
    ];
    
    $summary_result = $summarizer->summarize($content, $summary_options);
    
    // Add summary metadata to content data
    $content_data['ai_metadata'] = isset($content_data['ai_metadata']) ? $content_data['ai_metadata'] : [];
    $content_data['ai_metadata']['summary'] = $summary_result;
    
    return $content_data;
}

/**
 * Add AI-generated keywords to content.
 *
 * @param array $content_data Content data
 * @return array Content data with keywords
 */
function add_keywords($content_data) {
    $content = isset($content_data['content']) ? $content_data['content'] : '';
    
    if (empty($content)) {
        return $content_data;
    }
    
    $keyword_generator = new KeywordGenerator();
    $keyword_options = [
        'limit' => 15,
        'include_phrases' => true,
        'categorize' => true
    ];
    
    $keyword_result = $keyword_generator->generate($content, $keyword_options);
    
    // Add keyword metadata to content data
    $content_data['ai_metadata'] = isset($content_data['ai_metadata']) ? $content_data['ai_metadata'] : [];
    $content_data['ai_metadata']['keywords'] = $keyword_result;
    
    // Also add formatted tags
    $content_data['ai_metadata']['tags'] = $keyword_generator->format_as_tags($keyword_result, 10);
    
    return $content_data;
}

/**
 * Add AI-extracted entities to content.
 *
 * @param array $content_data Content data
 * @return array Content data with entities
 */
function add_entities($content_data) {
    $content = isset($content_data['content']) ? $content_data['content'] : '';
    
    if (empty($content)) {
        return $content_data;
    }
    
    $entity_extractor = new EntityExtractor();
    $entity_options = [
        'hierarchical' => true,
        'context_aware' => true
    ];
    
    $entity_result = $entity_extractor->extract($content, $entity_options);
    
    // Add entity metadata to content data
    $content_data['ai_metadata'] = isset($content_data['ai_metadata']) ? $content_data['ai_metadata'] : [];
    $content_data['ai_metadata']['entities'] = $entity_result;
    
    return $content_data;
}

/**
 * Add AI-generated categories to content.
 *
 * @param array $content_data Content data
 * @return array Content data with categories
 */
function add_categories($content_data) {
    $content = isset($content_data['content']) ? $content_data['content'] : '';
    
    if (empty($content)) {
        return $content_data;
    }
    
    $classifier = new Classifier();
    $classification_options = [
        'taxonomies' => ['topics', 'formats', 'tone', 'audience'],
        'multi_label' => true,
        'hierarchical' => true
    ];
    
    $classification_result = $classifier->classify($content, $classification_options);
    
    // Add classification metadata to content data
    $content_data['ai_metadata'] = isset($content_data['ai_metadata']) ? $content_data['ai_metadata'] : [];
    $content_data['ai_metadata']['categories'] = $classification_result;
    
    return $content_data;
}

/**
 * Add AI-analyzed sentiment to content.
 *
 * @param array $content_data Content data
 * @return array Content data with sentiment
 */
function add_sentiment($content_data) {
    $content = isset($content_data['content']) ? $content_data['content'] : '';
    
    if (empty($content)) {
        return $content_data;
    }
    
    $sentiment_analyzer = new SentimentAnalyzer();
    $sentiment_options = [
        'detailed' => true
    ];
    
    $sentiment_result = $sentiment_analyzer->analyze($content, $sentiment_options);
    
    // Add sentiment metadata to content data
    $content_data['ai_metadata'] = isset($content_data['ai_metadata']) ? $content_data['ai_metadata'] : [];
    $content_data['ai_metadata']['sentiment'] = $sentiment_result;
    
    return $content_data;
}

/**
 * Filter to display AI metadata in admin.
 *
 * @param string $html HTML to display
 * @param array $content_data Content data with AI metadata
 * @return string Modified HTML
 */
function filter_display_ai_metadata($html, $content_data) {
    if (!isset($content_data['ai_metadata']) || empty($content_data['ai_metadata'])) {
        return $html;
    }
    
    $ai_metadata = $content_data['ai_metadata'];
    $metadata_html = '<div class="asapdigest-ai-metadata">';
    
    // Add summary
    if (isset($ai_metadata['summary'])) {
        $metadata_html .= '<div class="asapdigest-summary">';
        $metadata_html .= '<h4>AI Summary</h4>';
        $metadata_html .= '<div class="summary-content">' . esc_html($ai_metadata['summary']['summary']) . '</div>';
        $metadata_html .= '</div>';
    }
    
    // Add keywords/tags
    if (isset($ai_metadata['tags'])) {
        $metadata_html .= '<div class="asapdigest-tags">';
        $metadata_html .= '<h4>Tags</h4>';
        $metadata_html .= '<div class="tags-content">' . esc_html($ai_metadata['tags']) . '</div>';
        $metadata_html .= '</div>';
    }
    
    // Add categories
    if (isset($ai_metadata['categories']) && isset($ai_metadata['categories']['primary_categories'])) {
        $metadata_html .= '<div class="asapdigest-categories">';
        $metadata_html .= '<h4>Categories</h4>';
        $metadata_html .= '<ul class="categories-list">';
        
        foreach ($ai_metadata['categories']['primary_categories'] as $taxonomy => $categories) {
            if ($taxonomy === 'sentiment') continue;
            
            $metadata_html .= '<li><strong>' . esc_html(ucfirst($taxonomy)) . ':</strong> ';
            $category_names = array_map(function($category) {
                return esc_html($category['category']) . ' (' . round($category['confidence'] * 100) . '%)';
            }, $categories);
            $metadata_html .= implode(', ', $category_names) . '</li>';
        }
        
        $metadata_html .= '</ul>';
        $metadata_html .= '</div>';
    }
    
    // Add sentiment
    if (isset($ai_metadata['sentiment'])) {
        $sentiment = $ai_metadata['sentiment'];
        $sentiment_label = isset($sentiment['sentiment']) ? $sentiment['sentiment'] : '';
        $sentiment_score = isset($sentiment['score']) ? $sentiment['score'] : 0;
        
        $sentiment_class = 'neutral';
        if ($sentiment_score < -0.25) {
            $sentiment_class = 'negative';
        } elseif ($sentiment_score > 0.25) {
            $sentiment_class = 'positive';
        }
        
        $metadata_html .= '<div class="asapdigest-sentiment">';
        $metadata_html .= '<h4>Sentiment</h4>';
        $metadata_html .= '<div class="sentiment-indicator sentiment-' . esc_attr($sentiment_class) . '">';
        $metadata_html .= esc_html(ucfirst($sentiment_label)) . ' (' . round($sentiment_score * 100) / 100 . ')';
        $metadata_html .= '</div>';
        $metadata_html .= '</div>';
    }
    
    $metadata_html .= '</div>';
    
    return $html . $metadata_html;
}

/**
 * Register REST API endpoints for AI processors.
 *
 * @return void
 */
function register_processor_endpoints() {
    register_rest_route('asap/v1', '/ai/processors/summarize', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\api_summarize',
        'permission_callback' => function() { return current_user_can('edit_posts'); }
    ]);
    
    register_rest_route('asap/v1', '/ai/processors/entities', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\api_extract_entities',
        'permission_callback' => function() { return current_user_can('edit_posts'); }
    ]);
    
    register_rest_route('asap/v1', '/ai/processors/classify', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\api_classify',
        'permission_callback' => function() { return current_user_can('edit_posts'); }
    ]);
    
    register_rest_route('asap/v1', '/ai/processors/keywords', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\api_generate_keywords',
        'permission_callback' => function() { return current_user_can('edit_posts'); }
    ]);
    
    register_rest_route('asap/v1', '/ai/processors/sentiment', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\api_analyze_sentiment',
        'permission_callback' => function() { return current_user_can('edit_posts'); }
    ]);
}

/**
 * API endpoint to summarize content.
 *
 * @param \WP_REST_Request $request Request object
 * @return \WP_REST_Response Response object
 */
function api_summarize($request) {
    try {
        $params = $request->get_json_params();
        
        if (empty($params['content'])) {
            return new \WP_Error('missing_content', 'Content parameter is required', ['status' => 400]);
        }
        
        $summarizer = new Summarizer();
        $options = !empty($params['options']) ? $params['options'] : [];
        $result = $summarizer->summarize($params['content'], $options);
        
        return rest_ensure_response($result);
    } catch (\Exception $e) {
        return new \WP_Error('summarize_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * API endpoint to extract entities.
 *
 * @param \WP_REST_Request $request Request object
 * @return \WP_REST_Response Response object
 */
function api_extract_entities($request) {
    try {
        $params = $request->get_json_params();
        
        if (empty($params['content'])) {
            return new \WP_Error('missing_content', 'Content parameter is required', ['status' => 400]);
        }
        
        $entity_extractor = new EntityExtractor();
        $options = !empty($params['options']) ? $params['options'] : [];
        $result = $entity_extractor->extract($params['content'], $options);
        
        return rest_ensure_response($result);
    } catch (\Exception $e) {
        return new \WP_Error('entity_extraction_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * API endpoint to classify content.
 *
 * @param \WP_REST_Request $request Request object
 * @return \WP_REST_Response Response object
 */
function api_classify($request) {
    try {
        $params = $request->get_json_params();
        
        if (empty($params['content'])) {
            return new \WP_Error('missing_content', 'Content parameter is required', ['status' => 400]);
        }
        
        $classifier = new Classifier();
        $options = !empty($params['options']) ? $params['options'] : [];
        $result = $classifier->classify($params['content'], $options);
        
        return rest_ensure_response($result);
    } catch (\Exception $e) {
        return new \WP_Error('classification_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * API endpoint to generate keywords.
 *
 * @param \WP_REST_Request $request Request object
 * @return \WP_REST_Response Response object
 */
function api_generate_keywords($request) {
    try {
        $params = $request->get_json_params();
        
        if (empty($params['content'])) {
            return new \WP_Error('missing_content', 'Content parameter is required', ['status' => 400]);
        }
        
        $keyword_generator = new KeywordGenerator();
        $options = !empty($params['options']) ? $params['options'] : [];
        $result = $keyword_generator->generate($params['content'], $options);
        
        return rest_ensure_response($result);
    } catch (\Exception $e) {
        return new \WP_Error('keyword_generation_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * API endpoint to analyze sentiment.
 *
 * @param \WP_REST_Request $request Request object
 * @return \WP_REST_Response Response object
 */
function api_analyze_sentiment($request) {
    try {
        $params = $request->get_json_params();
        
        if (empty($params['content'])) {
            return new \WP_Error('missing_content', 'Content parameter is required', ['status' => 400]);
        }
        
        $sentiment_analyzer = new SentimentAnalyzer();
        $options = !empty($params['options']) ? $params['options'] : [];
        $result = $sentiment_analyzer->analyze($params['content'], $options);
        
        return rest_ensure_response($result);
    } catch (\Exception $e) {
        return new \WP_Error('sentiment_analysis_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * Register admin settings for processors.
 *
 * @return void
 */
function register_processor_settings() {
    // Register settings for each processor
    register_setting('asap_ai_settings', 'asap_ai_summary_model', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    register_setting('asap_ai_settings', 'asap_ai_keyword_model', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    register_setting('asap_ai_settings', 'asap_ai_sentiment_model', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    register_setting('asap_ai_settings', 'asap_classification_taxonomies', [
        'type' => 'array',
        'sanitize_callback' => 'asapdigest_sanitize_array',
    ]);
}

// Initialize on load
bootstrap(); 