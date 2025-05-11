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
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_content_nonce', 'nonce');
    
    // Get content ID
    $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
    
    if ($content_id <= 0) {
        wp_send_json_error(['message' => 'Invalid content ID']);
        return;
    }
    
    // Get content details
    global $wpdb;
    $table_name = $wpdb->prefix . 'asap_ingested_content';
    
    $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $content_id));
    
    if (!$content) {
        wp_send_json_error(['message' => 'Content not found']);
        return;
    }
    
    // Format dates
    $publish_date = !empty($content->publish_date) ? date('Y-m-d', strtotime($content->publish_date)) : '';
    $created_at = !empty($content->created_at) ? date('Y-m-d H:i', strtotime($content->created_at)) : '';
    
    // Send response
    wp_send_json_success([
        'id' => $content->id,
        'title' => $content->title,
        'content' => $content->content,
        'summary' => $content->summary,
        'type' => $content->type,
        'status' => $content->status,
        'quality_score' => $content->quality_score,
        'source_url' => $content->source_url,
        'source_id' => $content->source_id,
        'publish_date' => $publish_date,
        'created_at' => $created_at,
    ]);
}
add_action('wp_ajax_asap_get_content_details', 'asap_digest_ajax_get_content_details');

/**
 * Search content for the content library
 */
function asap_digest_ajax_search_content() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_content_nonce', 'nonce');
    
    // Parse request parameters
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $min_quality = isset($_POST['min_quality']) ? intval($_POST['min_quality']) : 0;
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? min(100, max(10, intval($_POST['per_page']))) : 20;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Query the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'asap_ingested_content';
    
    // Build where clauses
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(title LIKE %s OR content LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($search) . '%';
        $params[] = '%' . $wpdb->esc_like($search) . '%';
    }
    
    if (!empty($type)) {
        $where[] = "type = %s";
        $params[] = $type;
    }
    
    if (!empty($status)) {
        $where[] = "status = %s";
        $params[] = $status;
    }
    
    if ($min_quality > 0) {
        $where[] = "quality_score >= %d";
        $params[] = $min_quality;
    }
    
    // Combine where clauses
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count query
    $count_sql = "SELECT COUNT(*) FROM {$table_name} {$where_sql}";
    $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $params));
    
    // Main query
    $sql = "SELECT * FROM {$table_name} {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $all_params = array_merge($params, [$per_page, $offset]);
    $items = $wpdb->get_results($wpdb->prepare($sql, $all_params));
    
    // Process items for display
    $processed_items = [];
    foreach ($items as $item) {
        // Extract hostname from source URL
        $source_hostname = '';
        if (!empty($item->source_url)) {
            $url_parts = parse_url($item->source_url);
            $source_hostname = isset($url_parts['host']) ? $url_parts['host'] : '';
            // Remove 'www.' if present
            $source_hostname = preg_replace('/^www\./', '', $source_hostname);
        }
        
        // Format dates
        $publish_date = !empty($item->publish_date) ? date('Y-m-d', strtotime($item->publish_date)) : '';
        $created_at = !empty($item->created_at) ? date('Y-m-d H:i', strtotime($item->created_at)) : '';
        
        // Add to processed items
        $processed_items[] = [
            'id' => $item->id,
            'title' => $item->title,
            'type' => $item->type,
            'status' => $item->status,
            'quality_score' => $item->quality_score,
            'source_url' => $item->source_url,
            'source_hostname' => $source_hostname,
            'publish_date' => $item->publish_date,
            'publish_date_formatted' => $publish_date,
            'created_at' => $item->created_at,
            'created_at_formatted' => $created_at,
        ];
    }
    
    // Calculate pagination
    $total_pages = ceil($total_items / $per_page);
    
    // Send response
    wp_send_json_success([
        'items' => $processed_items,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page,
    ]);
}
add_action('wp_ajax_asap_search_content', 'asap_digest_ajax_search_content');

/**
 * Handle bulk actions for content library
 */
function asap_digest_ajax_bulk_action_content() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_content_nonce', 'nonce');
    
    // Get parameters
    $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
    $content_ids = isset($_POST['content_ids']) ? array_map('intval', (array) $_POST['content_ids']) : [];
    
    if (empty($bulk_action) || empty($content_ids)) {
        wp_send_json_error(['message' => 'Missing required parameters']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'asap_ingested_content';
    $affected = 0;
    $message = '';
    
    // Process based on action
    switch ($bulk_action) {
        case 'delete':
            // Delete content
            foreach ($content_ids as $id) {
                $deleted = $wpdb->delete($table_name, ['id' => $id], ['%d']);
                if ($deleted) {
                    $affected++;
                }
            }
            $message = sprintf(_n('%d item deleted successfully.', '%d items deleted successfully.', $affected, 'asapdigest-core'), $affected);
            break;
            
        case 'change_status':
            // Change status
            $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
            
            if (empty($new_status) || !in_array($new_status, ['approved', 'rejected', 'pending', 'processing'])) {
                wp_send_json_error(['message' => 'Invalid status']);
                return;
            }
            
            foreach ($content_ids as $id) {
                $updated = $wpdb->update(
                    $table_name,
                    ['status' => $new_status],
                    ['id' => $id],
                    ['%s'],
                    ['%d']
                );
                
                if ($updated) {
                    $affected++;
                }
            }
            
            $message = sprintf(
                _n(
                    '%d item marked as %s successfully.',
                    '%d items marked as %s successfully.',
                    $affected,
                    'asapdigest-core'
                ),
                $affected,
                $new_status
            );
            break;
            
        default:
            wp_send_json_error(['message' => 'Invalid action']);
            return;
    }
    
    wp_send_json_success([
        'affected' => $affected,
        'message' => $message,
    ]);
}
add_action('wp_ajax_asap_bulk_action_content', 'asap_digest_ajax_bulk_action_content');

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

/**
 * AJAX Handler: Get Content Sources
 */
function asap_digest_ajax_get_content_sources() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Insufficient permissions',
            'code' => 'permission_denied',
        ], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_sources_nonce', 'nonce');
    
    // Parse request parameters
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 50;
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name';
    $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
    
    // Get source manager instance
    $source_manager = \AsapDigest\Crawler\ContentSourceManager::get_instance();
    
    // Build query args
    $args = [
        'search' => $search,
        'type' => $type,
        'status' => $status,
        'offset' => $offset,
        'limit' => $limit,
        'orderby' => $orderby,
        'order' => $order,
    ];
    
    // Get content sources and total count
    $sources = $source_manager->get_sources($args);
    $total = $source_manager->count_sources([
        'search' => $search,
        'type' => $type,
        'status' => $status,
    ]);
    
    // Format for response
    $formatted_sources = [];
    foreach ($sources as $source) {
        $formatted_sources[] = [
            'id' => intval($source['id']),
            'name' => $source['name'],
            'type' => $source['type'],
            'url' => $source['url'],
            'frequency' => $source['frequency'],
            'status' => $source['status'],
            'last_fetch' => $source['last_fetch'],
            'health' => $source['health'],
            'items_count' => intval($source['items_count']),
            'configuration' => json_decode($source['configuration'], true),
            'created_at' => $source['created_at'],
            'updated_at' => $source['updated_at'],
        ];
    }
    
    // Return response
    wp_send_json_success([
        'sources' => $formatted_sources,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    ]);
}
add_action('wp_ajax_asap_digest_get_content_sources', 'asap_digest_ajax_get_content_sources');

/**
 * AJAX Handler: Get Single Content Source
 */
function asap_digest_ajax_get_content_source() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Insufficient permissions',
            'code' => 'permission_denied',
        ], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_sources_nonce', 'nonce');
    
    // Get source ID
    $source_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$source_id) {
        wp_send_json_error([
            'message' => 'Missing source ID',
            'code' => 'missing_id',
        ], 400);
        return;
    }
    
    // Get source manager instance
    $source_manager = \AsapDigest\Crawler\ContentSourceManager::get_instance();
    
    // Get content source
    $source = $source_manager->get_source($source_id);
    
    if (!$source) {
        wp_send_json_error([
            'message' => 'Content source not found',
            'code' => 'not_found',
        ], 404);
        return;
    }
    
    // Format for response
    $formatted_source = [
        'id' => intval($source['id']),
        'name' => $source['name'],
        'type' => $source['type'],
        'url' => $source['url'],
        'frequency' => $source['frequency'],
        'status' => $source['status'],
        'last_fetch' => $source['last_fetch'],
        'health' => $source['health'],
        'items_count' => intval($source['items_count']),
        'configuration' => json_decode($source['configuration'], true),
        'created_at' => $source['created_at'],
        'updated_at' => $source['updated_at'],
    ];
    
    // Return response
    wp_send_json_success($formatted_source);
}
add_action('wp_ajax_asap_digest_get_content_source', 'asap_digest_ajax_get_content_source');

/**
 * AJAX Handler: Add Content Source
 */
function asap_digest_ajax_add_content_source() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Insufficient permissions',
            'code' => 'permission_denied',
        ], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_sources_nonce', 'nonce');
    
    // Parse request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        wp_send_json_error([
            'message' => 'Invalid request data',
            'code' => 'invalid_data',
        ], 400);
        return;
    }
    
    // Validate required fields
    $required_fields = ['name', 'type', 'url', 'frequency'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            wp_send_json_error([
                'message' => "Missing required field: {$field}",
                'code' => 'missing_field',
            ], 400);
            return;
        }
    }
    
    // Sanitize and validate data
    $source_data = [
        'name' => sanitize_text_field($data['name']),
        'type' => sanitize_text_field($data['type']),
        'url' => esc_url_raw($data['url']),
        'frequency' => sanitize_text_field($data['frequency']),
        'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active',
    ];
    
    // Validate URL format (basic check)
    if (!filter_var($source_data['url'], FILTER_VALIDATE_URL)) {
        wp_send_json_error([
            'message' => 'Invalid URL format',
            'code' => 'invalid_url',
        ], 400);
        return;
    }
    
    // Validate source type
    $source_manager = \AsapDigest\Crawler\ContentSourceManager::get_instance();
    $valid_types = $source_manager->get_supported_source_types();
    
    if (!in_array($source_data['type'], array_keys($valid_types))) {
        wp_send_json_error([
            'message' => 'Invalid source type',
            'code' => 'invalid_type',
            'valid_types' => $valid_types,
        ], 400);
        return;
    }
    
    // Validate frequency
    $valid_frequencies = ['hourly', 'twicedaily', 'daily', 'weekly'];
    
    if (!in_array($source_data['frequency'], $valid_frequencies)) {
        wp_send_json_error([
            'message' => 'Invalid frequency',
            'code' => 'invalid_frequency',
            'valid_frequencies' => $valid_frequencies,
        ], 400);
        return;
    }
    
    // Handle configuration (JSON)
    if (isset($data['configuration']) && is_array($data['configuration'])) {
        $source_data['configuration'] = json_encode($data['configuration']);
    } else {
        $source_data['configuration'] = '{}';
    }
    
    // Add content source
    $source_id = $source_manager->add_source($source_data);
    
    if (!$source_id) {
        wp_send_json_error([
            'message' => 'Failed to add content source',
            'code' => 'add_failed',
        ], 500);
        return;
    }
    
    // Get the new source
    $source = $source_manager->get_source($source_id);
    
    // Format for response
    $formatted_source = [
        'id' => intval($source['id']),
        'name' => $source['name'],
        'type' => $source['type'],
        'url' => $source['url'],
        'frequency' => $source['frequency'],
        'status' => $source['status'],
        'last_fetch' => $source['last_fetch'],
        'health' => $source['health'],
        'items_count' => intval($source['items_count']),
        'configuration' => json_decode($source['configuration'], true),
        'created_at' => $source['created_at'],
        'updated_at' => $source['updated_at'],
    ];
    
    // Return response
    wp_send_json_success([
        'message' => 'Content source added successfully',
        'source' => $formatted_source,
    ]);
}
add_action('wp_ajax_asap_digest_add_content_source', 'asap_digest_ajax_add_content_source');

/**
 * AJAX Handler: Update Content Source
 */
function asap_digest_ajax_update_content_source() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Insufficient permissions',
            'code' => 'permission_denied',
        ], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_sources_nonce', 'nonce');
    
    // Parse request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        wp_send_json_error([
            'message' => 'Invalid request data',
            'code' => 'invalid_data',
        ], 400);
        return;
    }
    
    // Validate source ID
    if (!isset($data['id']) || empty($data['id'])) {
        wp_send_json_error([
            'message' => 'Missing source ID',
            'code' => 'missing_id',
        ], 400);
        return;
    }
    
    $source_id = intval($data['id']);
    
    // Initialize source data
    $source_data = [];
    
    // Sanitize and validate data
    if (isset($data['name'])) {
        $source_data['name'] = sanitize_text_field($data['name']);
    }
    
    if (isset($data['url'])) {
        $url = esc_url_raw($data['url']);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error([
                'message' => 'Invalid URL format',
                'code' => 'invalid_url',
            ], 400);
            return;
        }
        $source_data['url'] = $url;
    }
    
    if (isset($data['type'])) {
        $type = sanitize_text_field($data['type']);
        $source_manager = \AsapDigest\Crawler\ContentSourceManager::get_instance();
        $valid_types = $source_manager->get_supported_source_types();
        
        if (!in_array($type, array_keys($valid_types))) {
            wp_send_json_error([
                'message' => 'Invalid source type',
                'code' => 'invalid_type',
                'valid_types' => $valid_types,
            ], 400);
            return;
        }
        $source_data['type'] = $type;
    }
    
    if (isset($data['frequency'])) {
        $frequency = sanitize_text_field($data['frequency']);
        $valid_frequencies = ['hourly', 'twicedaily', 'daily', 'weekly'];
        
        if (!in_array($frequency, $valid_frequencies)) {
            wp_send_json_error([
                'message' => 'Invalid frequency',
                'code' => 'invalid_frequency',
                'valid_frequencies' => $valid_frequencies,
            ], 400);
            return;
        }
        $source_data['frequency'] = $frequency;
    }
    
    if (isset($data['status'])) {
        $source_data['status'] = sanitize_text_field($data['status']);
    }
    
    // Handle configuration (JSON)
    if (isset($data['configuration']) && is_array($data['configuration'])) {
        $source_data['configuration'] = json_encode($data['configuration']);
    }
    
    // Get source manager instance
    $source_manager = \AsapDigest\Crawler\ContentSourceManager::get_instance();
    
    // Check if source exists
    $existing_source = $source_manager->get_source($source_id);
    
    if (!$existing_source) {
        wp_send_json_error([
            'message' => 'Content source not found',
            'code' => 'not_found',
        ], 404);
        return;
    }
    
    // Update content source
    $success = $source_manager->update_source($source_id, $source_data);
    
    if (!$success) {
        wp_send_json_error([
            'message' => 'Failed to update content source',
            'code' => 'update_failed',
        ], 500);
        return;
    }
    
    // Get the updated source
    $source = $source_manager->get_source($source_id);
    
    // Format for response
    $formatted_source = [
        'id' => intval($source['id']),
        'name' => $source['name'],
        'type' => $source['type'],
        'url' => $source['url'],
        'frequency' => $source['frequency'],
        'status' => $source['status'],
        'last_fetch' => $source['last_fetch'],
        'health' => $source['health'],
        'items_count' => intval($source['items_count']),
        'configuration' => json_decode($source['configuration'], true),
        'created_at' => $source['created_at'],
        'updated_at' => $source['updated_at'],
    ];
    
    // Return response
    wp_send_json_success([
        'message' => 'Content source updated successfully',
        'source' => $formatted_source,
    ]);
}
add_action('wp_ajax_asap_digest_update_content_source', 'asap_digest_ajax_update_content_source');

/**
 * AJAX Handler: Delete Content Source
 */
function asap_digest_ajax_delete_content_source() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Insufficient permissions',
            'code' => 'permission_denied',
        ], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_sources_nonce', 'nonce');
    
    // Get source ID
    $source_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    
    if (!$source_id) {
        wp_send_json_error([
            'message' => 'Missing source ID',
            'code' => 'missing_id',
        ], 400);
        return;
    }
    
    // Get source manager instance
    $source_manager = \AsapDigest\Crawler\ContentSourceManager::get_instance();
    
    // Check if source exists
    $existing_source = $source_manager->get_source($source_id);
    
    if (!$existing_source) {
        wp_send_json_error([
            'message' => 'Content source not found',
            'code' => 'not_found',
        ], 404);
        return;
    }
    
    // Delete content source
    $success = $source_manager->delete_source($source_id);
    
    if (!$success) {
        wp_send_json_error([
            'message' => 'Failed to delete content source',
            'code' => 'delete_failed',
        ], 500);
        return;
    }
    
    // Return response
    wp_send_json_success([
        'message' => 'Content source deleted successfully',
        'id' => $source_id,
    ]);
}
add_action('wp_ajax_asap_digest_delete_content_source', 'asap_digest_ajax_delete_content_source');

/**
 * AJAX Handler: Manual Trigger Content Crawler
 */
function asap_digest_ajax_trigger_content_crawler() {
    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Insufficient permissions',
            'code' => 'permission_denied',
        ], 403);
        return;
    }
    
    // Security check
    check_ajax_referer('asap_digest_sources_nonce', 'nonce');
    
    // Get source ID
    $source_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    
    // Get source manager instance
    $source_manager = \AsapDigest\Crawler\ContentSourceManager::get_instance();
    
    // Check if source exists (if ID provided)
    if ($source_id) {
        $existing_source = $source_manager->get_source($source_id);
        
        if (!$existing_source) {
            wp_send_json_error([
                'message' => 'Content source not found',
                'code' => 'not_found',
            ], 404);
            return;
        }
    }
    
    // Get content crawler instance
    $content_crawler = \AsapDigest\Crawler\ContentCrawler::get_instance();
    
    try {
        // Trigger crawler for specific source or all sources
        if ($source_id) {
            // Run crawler for specific source
            $result = $content_crawler->run(['source_id' => $source_id]);
            $message = "Crawler triggered successfully for source ID: {$source_id}";
        } else {
            // Run crawler for all active sources
            $result = $content_crawler->run(['status' => 'active']);
            $message = "Crawler triggered successfully for all active sources";
        }
        
        // Return response
        wp_send_json_success([
            'message' => $message,
            'result' => $result,
        ]);
    } catch (\Exception $e) {
        // Log error
        error_log('ASAP Digest Crawler Error: ' . $e->getMessage());
        
        // Return error response
        wp_send_json_error([
            'message' => 'Failed to trigger content crawler: ' . $e->getMessage(),
            'code' => 'crawler_error',
        ], 500);
    }
}
add_action('wp_ajax_asap_digest_trigger_content_crawler', 'asap_digest_ajax_trigger_content_crawler'); 