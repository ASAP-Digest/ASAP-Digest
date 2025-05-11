<?php
/**
 * AJAX Handlers for ASAP Digest Admin
 *
 * @package ASAP_Digest
 * @subpackage Admin
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize AJAX handlers
 */
function asap_digest_init_ajax_handlers() {
    // Content Library AJAX handlers
    add_action('wp_ajax_asap_get_content_details', 'asap_digest_ajax_get_content_details');
    add_action('wp_ajax_asap_search_content', 'asap_digest_ajax_search_content');
    add_action('wp_ajax_asap_bulk_action_content', 'asap_digest_ajax_bulk_action_content');
    add_action('wp_ajax_asap_reindex_content', 'asap_digest_ajax_reindex_content');
    
    // Source Management AJAX handlers
    add_action('wp_ajax_asap_get_sources', 'asap_digest_ajax_get_sources');
    add_action('wp_ajax_asap_get_source', 'asap_digest_ajax_get_source');
    add_action('wp_ajax_asap_save_source', 'asap_digest_ajax_save_source');
    add_action('wp_ajax_asap_delete_source', 'asap_digest_ajax_delete_source');
    add_action('wp_ajax_asap_run_source', 'asap_digest_ajax_run_source');
    
    // Quality Settings AJAX handlers
    add_action('wp_ajax_asap_get_quality_settings', 'asap_digest_ajax_get_quality_settings');
    add_action('wp_ajax_asap_save_quality_settings', 'asap_digest_ajax_save_quality_settings');
}
add_action('init', 'asap_digest_init_ajax_handlers');

/**
 * Get content details for the content library modal
 */
function asap_digest_ajax_get_content_details() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_content_library_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get content ID
    $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
    
    if ($content_id <= 0) {
        wp_send_json_error(['message' => 'Invalid content ID']);
        exit;
    }
    
    // Load content processor
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/bootstrap.php';
    $processor = asap_digest_get_content_processor();
    
    // Get content details
    $content = $processor->get_content($content_id);
    
    if (!$content) {
        wp_send_json_error(['message' => 'Content not found']);
        exit;
    }
    
    // Format some fields for display
    if (!empty($content['publish_date'])) {
        $content['publish_date'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($content['publish_date']));
    }
    
    if (!empty($content['created_at'])) {
        $content['created_at'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($content['created_at']));
    }
    
    // Get quality assessment if available
    if (!empty($content['content'])) {
        $validator = new ASAP_Digest_Content_Validator($content);
        $content['quality_assessment'] = $validator->get_quality_assessment();
    }
    
    wp_send_json_success($content);
}

/**
 * Search content for the content library
 */
function asap_digest_ajax_search_content() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_content_library_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get search parameters
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $min_quality = isset($_POST['min_quality']) ? intval($_POST['min_quality']) : 0;
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? min(100, intval($_POST['per_page'])) : 20;
    
    // Build query
    global $wpdb;
    $table = $wpdb->prefix . 'asap_ingested_content';
    
    $where = [];
    $params = [];
    
    // Filter by search term
    if (!empty($search)) {
        $where[] = '(title LIKE %s OR content LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Filter by type
    if (!empty($type)) {
        $where[] = 'type = %s';
        $params[] = $type;
    }
    
    // Filter by status
    if (!empty($status)) {
        $where[] = 'status = %s';
        $params[] = $status;
    }
    
    // Filter by minimum quality score
    if ($min_quality > 0) {
        $where[] = 'quality_score >= %d';
        $params[] = $min_quality;
    }
    
    // Build WHERE clause
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total items
    $count_query = "SELECT COUNT(*) FROM $table $where_sql";
    if (!empty($params)) {
        $count_query = $wpdb->prepare($count_query, $params);
    }
    $total_items = $wpdb->get_var($count_query);
    
    // Calculate pagination
    $total_pages = ceil($total_items / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // Get items
    $orderby = 'created_at';
    $order = 'DESC';
    
    $query = "SELECT * FROM $table $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
    $params[] = $per_page;
    $params[] = $offset;
    
    $prepared_query = $wpdb->prepare($query, $params);
    $items = $wpdb->get_results($prepared_query, ARRAY_A);
    
    // Format items for response
    foreach ($items as &$item) {
        // Format dates
        if (!empty($item['publish_date'])) {
            $item['publish_date_formatted'] = date_i18n(get_option('date_format'), strtotime($item['publish_date']));
        }
        
        if (!empty($item['created_at'])) {
            $item['created_at_formatted'] = date_i18n(get_option('date_format'), strtotime($item['created_at']));
        }
        
        // Truncate content for preview
        if (!empty($item['content'])) {
            $item['content_preview'] = wp_trim_words(wp_strip_all_tags($item['content']), 30, '...');
        }
        
        // Parse source URL hostname
        if (!empty($item['source_url'])) {
            $parsed_url = parse_url($item['source_url']);
            $item['source_hostname'] = $parsed_url['host'] ?? '';
        }
    }
    
    wp_send_json_success([
        'items' => $items,
        'total' => (int) $total_items,
        'total_pages' => (int) $total_pages,
        'current_page' => (int) $page,
    ]);
}

/**
 * Handle bulk actions for content library
 */
function asap_digest_ajax_bulk_action_content() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_content_library_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get action and content IDs
    $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
    $content_ids = isset($_POST['content_ids']) ? array_map('intval', $_POST['content_ids']) : [];
    
    if (empty($action) || empty($content_ids)) {
        wp_send_json_error(['message' => 'Invalid action or no content selected']);
        exit;
    }
    
    // Load content processor
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/bootstrap.php';
    $processor = asap_digest_get_content_processor();
    
    $results = [
        'success' => 0,
        'failed' => 0,
        'message' => '',
    ];
    
    // Process action
    switch ($action) {
        case 'delete':
            foreach ($content_ids as $content_id) {
                if ($processor->delete($content_id)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            }
            
            $results['message'] = sprintf(
                _n(
                    '%d item deleted successfully.',
                    '%d items deleted successfully.',
                    $results['success'],
                    'asap-digest'
                ),
                $results['success']
            );
            
            if ($results['failed'] > 0) {
                $results['message'] .= ' ' . sprintf(
                    _n(
                        '%d item failed to delete.',
                        '%d items failed to delete.',
                        $results['failed'],
                        'asap-digest'
                    ),
                    $results['failed']
                );
            }
            break;
            
        case 'change_status':
            $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
            
            if (empty($new_status)) {
                wp_send_json_error(['message' => 'No status specified']);
                exit;
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'asap_ingested_content';
            
            foreach ($content_ids as $content_id) {
                $result = $wpdb->update(
                    $table,
                    ['status' => $new_status],
                    ['id' => $content_id]
                );
                
                if ($result !== false) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            }
            
            $results['message'] = sprintf(
                _n(
                    'Status changed to "%s" for %d item.',
                    'Status changed to "%s" for %d items.',
                    $results['success'],
                    'asap-digest'
                ),
                $new_status,
                $results['success']
            );
            
            if ($results['failed'] > 0) {
                $results['message'] .= ' ' . sprintf(
                    _n(
                        '%d item failed to update.',
                        '%d items failed to update.',
                        $results['failed'],
                        'asap-digest'
                    ),
                    $results['failed']
                );
            }
            break;
            
        default:
            wp_send_json_error(['message' => 'Unsupported action']);
            exit;
    }
    
    wp_send_json_success($results);
}

/**
 * Handle reindexing content
 */
function asap_digest_ajax_reindex_content() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get batch size
    $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
    
    // Load content processor
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/bootstrap.php';
    $processor = asap_digest_get_content_processor();
    
    // Reindex content
    $result = $processor->reindex_content($batch_size);
    
    wp_send_json_success($result);
}

/**
 * Get all content sources
 */
function asap_digest_ajax_get_sources() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Load source manager
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
    $source_manager = new AsapDigest\Crawler\ContentSourceManager();
    
    // Get sources
    $sources = $source_manager->load_sources();
    
    // Add extra info to sources
    foreach ($sources as &$source) {
        // Get last fetch time
        if ($source->last_fetch) {
            $source->last_fetch_formatted = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                $source->last_fetch
            );
        } else {
            $source->last_fetch_formatted = 'Never';
        }
        
        // Get fetch interval
        $source->fetch_interval_formatted = human_time_diff(0, $source->fetch_interval);
        
        // Get success rate
        global $wpdb;
        $total_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}asap_crawler_errors WHERE source_id = %d",
            $source->id
        ));
        
        $success_rate = $source->fetch_count > 0 ? 
            round((($source->fetch_count - $total_errors) / $source->fetch_count) * 100) : 0;
        
        $source->success_rate = $success_rate;
    }
    
    wp_send_json_success(['sources' => $sources]);
}

/**
 * Get a specific content source
 */
function asap_digest_ajax_get_source() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get source ID
    $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
    
    if ($source_id <= 0) {
        wp_send_json_error(['message' => 'Invalid source ID']);
        exit;
    }
    
    // Load source manager
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
    $source_manager = new AsapDigest\Crawler\ContentSourceManager();
    
    // Get source
    $source = $source_manager->get_source($source_id);
    
    if (!$source) {
        wp_send_json_error(['message' => 'Source not found']);
        exit;
    }
    
    wp_send_json_success(['source' => $source]);
}

/**
 * Save a content source (create or update)
 */
function asap_digest_ajax_save_source() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get source data
    $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
    $source_data = isset($_POST['source_data']) ? $_POST['source_data'] : [];
    
    if (empty($source_data)) {
        wp_send_json_error(['message' => 'No source data provided']);
        exit;
    }
    
    // Validate and sanitize data
    $sanitized_data = [];
    
    if (isset($source_data['name'])) {
        $sanitized_data['name'] = sanitize_text_field($source_data['name']);
    }
    
    if (isset($source_data['type'])) {
        $sanitized_data['type'] = sanitize_text_field($source_data['type']);
    }
    
    if (isset($source_data['url'])) {
        $sanitized_data['url'] = esc_url_raw($source_data['url']);
    }
    
    if (isset($source_data['active'])) {
        $sanitized_data['active'] = !empty($source_data['active']) ? 1 : 0;
    }
    
    if (isset($source_data['fetch_interval'])) {
        $sanitized_data['fetch_interval'] = intval($source_data['fetch_interval']);
    }
    
    if (isset($source_data['config']) && is_array($source_data['config'])) {
        $sanitized_data['config'] = [];
        
        foreach ($source_data['config'] as $key => $value) {
            $sanitized_key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized_data['config'][$sanitized_key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_data['config'][$sanitized_key] = sanitize_text_field($value);
            }
        }
    }
    
    if (isset($source_data['content_types']) && is_array($source_data['content_types'])) {
        $sanitized_data['content_types'] = array_map('sanitize_text_field', $source_data['content_types']);
    }
    
    // Load source manager
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
    $source_manager = new AsapDigest\Crawler\ContentSourceManager();
    
    // Save source
    if ($source_id > 0) {
        // Update existing source
        $result = $source_manager->update_source($source_id, $sanitized_data);
        $message = 'Source updated successfully';
    } else {
        // Add new source
        $result = $source_manager->add_source($sanitized_data);
        $source_id = $result;
        $message = 'Source added successfully';
    }
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to save source']);
        exit;
    }
    
    // Get updated source
    $source = $source_manager->get_source($source_id);
    
    wp_send_json_success([
        'message' => $message,
        'source' => $source,
    ]);
}

/**
 * Delete a content source
 */
function asap_digest_ajax_delete_source() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get source ID
    $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
    
    if ($source_id <= 0) {
        wp_send_json_error(['message' => 'Invalid source ID']);
        exit;
    }
    
    // Load source manager
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
    $source_manager = new AsapDigest\Crawler\ContentSourceManager();
    
    // Delete source
    $result = $source_manager->delete_source($source_id);
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to delete source']);
        exit;
    }
    
    wp_send_json_success(['message' => 'Source deleted successfully']);
}

/**
 * Run a content source
 */
function asap_digest_ajax_run_source() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get source ID
    $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
    
    if ($source_id <= 0) {
        wp_send_json_error(['message' => 'Invalid source ID']);
        exit;
    }
    
    // Load required classes
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-source-manager.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/crawler/class-content-crawler.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/content-processing/bootstrap.php';
    
    // Initialize components
    $source_manager = new AsapDigest\Crawler\ContentSourceManager();
    $processor = asap_digest_get_content_processor();
    $crawler = new AsapDigest\Crawler\ContentCrawler($source_manager, $processor);
    
    // Get source
    $source = $source_manager->get_source($source_id);
    
    if (!$source) {
        wp_send_json_error(['message' => 'Source not found']);
        exit;
    }
    
    // Run crawler for this source
    $result = $crawler->crawl_source($source);
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to crawl source']);
        exit;
    }
    
    wp_send_json_success([
        'message' => sprintf(
            'Source crawled successfully. Processed %d items, stored %d items.',
            $result['items_processed'] ?? 0,
            $result['items_stored'] ?? 0
        ),
        'result' => $result,
    ]);
}

/**
 * Get quality settings
 */
function asap_digest_ajax_get_quality_settings() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get quality settings from options
    $settings = get_option('asap_content_quality_settings', [
        'min_quality_score' => defined('ASAP_QUALITY_SCORE_MINIMUM') ? ASAP_QUALITY_SCORE_MINIMUM : 50,
        'auto_approve_threshold' => defined('ASAP_QUALITY_SCORE_EXCELLENT') ? ASAP_QUALITY_SCORE_EXCELLENT : 90,
        'auto_reject_threshold' => defined('ASAP_QUALITY_SCORE_AUTO_REJECT') ? ASAP_QUALITY_SCORE_AUTO_REJECT : 25,
        'rules' => [
            'completeness' => [
                'weight' => 0.3,
                'title_min_length' => 10,
                'content_min_length' => 100,
                'requires_image' => false
            ],
            'readability' => [
                'weight' => 0.2,
                'min_score' => 60
            ],
            'relevance' => [
                'weight' => 0.3,
                'keyword_match' => true
            ],
            'freshness' => [
                'weight' => 0.1,
                'max_age_days' => 30
            ],
            'enrichment' => [
                'weight' => 0.1,
                'require_metadata' => false
            ]
        ]
    ]);
    
    wp_send_json_success(['settings' => $settings]);
}

/**
 * Save quality settings
 */
function asap_digest_ajax_save_quality_settings() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        exit;
    }
    
    // Get settings data
    $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
    
    if (empty($settings)) {
        wp_send_json_error(['message' => 'No settings provided']);
        exit;
    }
    
    // Validate and sanitize
    $sanitized_settings = [];
    
    // Validate thresholds
    if (isset($settings['min_quality_score'])) {
        $sanitized_settings['min_quality_score'] = max(0, min(100, intval($settings['min_quality_score'])));
    }
    
    if (isset($settings['auto_approve_threshold'])) {
        $sanitized_settings['auto_approve_threshold'] = max(0, min(100, intval($settings['auto_approve_threshold'])));
    }
    
    if (isset($settings['auto_reject_threshold'])) {
        $sanitized_settings['auto_reject_threshold'] = max(0, min(100, intval($settings['auto_reject_threshold'])));
    }
    
    // Validate rules
    if (isset($settings['rules']) && is_array($settings['rules'])) {
        $sanitized_settings['rules'] = [];
        $total_weight = 0;
        
        foreach ($settings['rules'] as $rule => $config) {
            $sanitized_rule = sanitize_key($rule);
            $sanitized_settings['rules'][$sanitized_rule] = [];
            
            if (isset($config['weight'])) {
                $weight = floatval($config['weight']);
                $sanitized_settings['rules'][$sanitized_rule]['weight'] = $weight;
                $total_weight += $weight;
            }
            
            // Sanitize other rule config values
            foreach ($config as $key => $value) {
                if ($key === 'weight') {
                    continue; // Already handled
                }
                
                $sanitized_key = sanitize_key($key);
                
                if (is_bool($value)) {
                    $sanitized_settings['rules'][$sanitized_rule][$sanitized_key] = $value;
                } elseif (is_numeric($value)) {
                    $sanitized_settings['rules'][$sanitized_rule][$sanitized_key] = floatval($value);
                } else {
                    $sanitized_settings['rules'][$sanitized_rule][$sanitized_key] = sanitize_text_field($value);
                }
            }
        }
        
        // Normalize weights if they don't sum to 1.0
        if ($total_weight > 0 && abs($total_weight - 1.0) > 0.01) {
            foreach ($sanitized_settings['rules'] as $rule => $config) {
                if (isset($config['weight'])) {
                    $sanitized_settings['rules'][$rule]['weight'] = floatval($config['weight']) / $total_weight;
                }
            }
        }
    }
    
    // Save settings
    update_option('asap_content_quality_settings', $sanitized_settings);
    
    wp_send_json_success([
        'message' => 'Quality settings updated successfully',
        'settings' => $sanitized_settings,
    ]);
} 