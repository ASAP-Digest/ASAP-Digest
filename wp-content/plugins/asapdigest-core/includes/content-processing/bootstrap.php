<?php
/**
 * Content Processing Bootstrap
 *
 * Bootstraps the content processing system by loading all required components.
 *
 * @package ASAPDigest_Core
 * @subpackage Content_Processing
 * @since 2.2.0
 * @file-marker ContentProcessingBootstrap
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load configuration
require_once __DIR__ . '/config.php';

/**
 * Get a content processing component instance
 * 
 * @param string $component_type Type of component to get
 * @param array $args Constructor arguments
 * @return object|null Component instance or null if not found
 */
function get_component($component_type, $args = []) {
    global $ASAP_CONTENT_COMPONENTS;
    
    if (!isset($ASAP_CONTENT_COMPONENTS[$component_type])) {
        return null;
    }
    
    $class_name = $ASAP_CONTENT_COMPONENTS[$component_type];
    
    if (!class_exists($class_name)) {
        return null;
    }
    
    return new $class_name(...$args);
}

/**
 * Initialize content processing components
 */
function asap_digest_init_content_processing() {
    // Register hooks related to content processing
    add_action('asap_content_added', 'asap_digest_log_content_action', 10, 2);
    add_action('asap_content_updated', 'asap_digest_log_content_action', 10, 2);
    add_action('asap_content_deleted', 'asap_digest_log_content_action', 10, 2);
    
    // Register integration hooks for crawler
    add_action('asap_content_crawled', 'asap_digest_process_crawled_content', 10, 1);
    
    // Add quality check filter
    add_filter('asap_content_quality_check', 'asap_digest_check_content_quality', 10, 1);
}

/**
 * Log content actions for audit purposes
 *
 * @param int $content_id Content ID
 * @param array $content_data Content data
 */
function asap_digest_log_content_action($content_id, $content_data) {
    // Basic logging implementation
    $action = current_action();
    $user_id = get_current_user_id();
    $time = current_time('mysql');
    
    // Log to activity log table if available
    global $wpdb;
    $table = $wpdb->prefix . 'asap_user_activity_log';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'object_type' => 'content',
                'object_id' => $content_id,
                'details' => wp_json_encode(array(
                    'title' => isset($content_data['title']) ? $content_data['title'] : '',
                    'type' => isset($content_data['type']) ? $content_data['type'] : '',
                )),
                'created_at' => $time,
            )
        );
    }
    
    // Log to WordPress error log for debugging
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log(sprintf(
            'ASAP Content Action: %s, ID: %d, User: %d, Title: %s',
            $action,
            $content_id,
            $user_id,
            isset($content_data['title']) ? $content_data['title'] : 'Unknown'
        ));
    }
}

/**
 * Process content that has been crawled
 *
 * This function serves as an integration point between the crawler and content processor
 *
 * @param array $content_data Crawled content data
 * @return array Processing results
 */
function asap_digest_process_crawled_content($content_data) {
    $processor = asap_digest_get_content_processor();
    
    // Process the content
    $process_result = $processor->process($content_data);
    
    // If processing successful, save the content
    if ($process_result['success']) {
        $save_result = $processor->save($process_result);
        
        if ($save_result['success']) {
            // Successfully processed and saved
            return [
                'success' => true,
                'content_id' => $save_result['content_id'],
                'message' => 'Content successfully processed and stored',
                'quality_score' => $process_result['data']['quality_score'],
            ];
        } else {
            // Error saving
            return [
                'success' => false,
                'errors' => $save_result['errors'],
                'message' => 'Content processed but failed to save',
            ];
        }
    } else {
        // Error processing
        return [
            'success' => false,
            'errors' => $process_result['errors'],
            'message' => 'Content failed processing step',
        ];
    }
}

/**
 * Check content quality (for use as a filter)
 * 
 * @param array $content_data Content data to check
 * @return array Result with quality assessment
 */
function asap_digest_check_content_quality($content_data) {
    $quality = new ASAP_Digest_Content_Quality($content_data);
    $assessment = $quality->assess();
    
    return [
        'passes' => $quality->passes_quality_threshold(),
        'score' => $assessment['score'],
        'category' => $assessment['category'],
        'assessment' => $assessment,
        'suggestions' => $assessment['suggestions'],
    ];
}

/**
 * Get an instance of the content processor
 *
 * @return ASAP_Digest_Content_Processor
 */
function asap_digest_get_content_processor() {
    static $processor = null;
    
    if ($processor === null) {
        $processor = new ASAP_Digest_Content_Processor();
    }
    
    return $processor;
}

/**
 * Process content through the content processing pipeline
 *
 * @param array $content_data Content data to process
 * @param int $exclude_id ID to exclude from duplication check (for updates)
 * @return array Processing results
 */
function asap_digest_process_content($content_data, $exclude_id = 0) {
    $processor = asap_digest_get_content_processor();
    return $processor->process($content_data, $exclude_id);
}

/**
 * Save processed content to the database
 *
 * @param array $processed_data Processed content data from process() method
 * @param int $update_id Optional ID to update (if updating existing content)
 * @return array Result with content_id on success
 */
function asap_digest_save_content($processed_data, $update_id = 0) {
    $processor = asap_digest_get_content_processor();
    return $processor->save($processed_data, $update_id);
}

/**
 * Find content similar to the given content data
 *
 * @param array $content_data Content data to find similar content for
 * @param int $limit Maximum number of similar items to return (default 5)
 * @return array Similar content items
 */
function asap_digest_find_similar_content($content_data, $limit = 5) {
    $processor = asap_digest_get_content_processor();
    return $processor->find_similar_content($content_data, $limit);
}

/**
 * Generate fingerprint for content data
 *
 * @param array $content_data Content data
 * @return string Fingerprint
 */
function asap_digest_generate_fingerprint($content_data) {
    return ASAP_Digest_Content_Validator::generate_fingerprint($content_data);
}

/**
 * Check if content is a duplicate
 *
 * @param array $content_data Content data
 * @param int $exclude_id ID to exclude from check (for updates)
 * @return bool|int False if unique, existing ID if duplicate
 */
function asap_digest_is_duplicate($content_data, $exclude_id = 0) {
    $deduplicator = new ASAP_Digest_Content_Deduplicator();
    $fingerprint = asap_digest_generate_fingerprint($content_data);
    return $deduplicator->is_duplicate($fingerprint, $exclude_id);
}

/**
 * Analyze content quality
 *
 * @param array $content_data Content data to analyze
 * @return array Quality assessment results
 */
function asap_digest_analyze_content_quality($content_data) {
    $quality = new ASAP_Digest_Content_Quality($content_data);
    return $quality->assess();
}

/**
 * Reindex content
 *
 * @param int $batch_size Batch size
 * @return array Results
 */
function asap_digest_reindex_content($batch_size = 50) {
    $processor = asap_digest_get_content_processor();
    return $processor->reindex_content($batch_size);
}

/**
 * Generate duplicate content report
 *
 * @param array $args Report arguments
 * @return array Report data
 */
function asap_digest_duplicate_report($args = []) {
    $processor = asap_digest_get_content_processor();
    return $processor->generate_duplicate_report($args);
}

/**
 * Initialize the content processing pipeline
 */
function initialize() {
    // Register hooks, filters or other initialization logic here
    
    // Example: Load components required on init
    $validator = get_component('validator');
    $deduplicator = get_component('deduplicator');
    
    // Initialize them if needed
    if ($validator) {
        // $validator->init();
    }
    
    if ($deduplicator) {
        // $deduplicator->init();
    }
}

// Initialize the content processing pipeline
asap_digest_init_content_processing(); 
initialize(); 