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