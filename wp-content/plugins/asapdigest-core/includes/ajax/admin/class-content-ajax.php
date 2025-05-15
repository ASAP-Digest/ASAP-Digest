<?php
/**
 * ASAP Digest Content AJAX Handler
 *
 * Standardized handler for content-related AJAX operations
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax\Admin;

use AsapDigest\Core\Ajax\Base_AJAX;
use AsapDigest\Core\ErrorLogger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content AJAX Handler Class
 *
 * Handles all AJAX requests related to content management
 *
 * @since 3.0.0
 */
class Content_Ajax extends Base_AJAX {
    
    /**
     * Nonce action for this handler
     *
     * @var string
     */
    protected $nonce_action = 'asap_digest_content_nonce';
    
    /**
     * Register AJAX actions
     *
     * @since 3.0.0
     * @return void
     */
    protected function register_actions() {
        add_action('wp_ajax_asap_get_content_details', [$this, 'handle_get_content_details']);
        add_action('wp_ajax_asap_search_content', [$this, 'handle_search_content']);
        add_action('wp_ajax_asap_bulk_action_content', [$this, 'handle_bulk_action_content']);
        add_action('wp_ajax_asap_reindex_content', [$this, 'handle_reindex_content']);
        add_action('wp_ajax_asap_reenrich_content_ai', [$this, 'handle_reenrich_content_ai']);
    }
    
    /**
     * Handle getting content details for the content library modal
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_get_content_details() {
        // Verify request
        $this->verify_request();
        
        // Validate required parameters
        $this->validate_params(['content_id']);
        
        // Get content ID
        $content_id = intval($_POST['content_id']);
        
        if ($content_id <= 0) {
            $this->send_error([
                'message' => __('Invalid content ID', 'asapdigest-core'),
                'code' => 'invalid_id'
            ]);
        }
        
        try {
            // Get content details
            global $wpdb;
            $table_name = $wpdb->prefix . 'asap_ingested_content';
            
            $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $content_id));
            
            if (!$content) {
                $this->send_error([
                    'message' => __('Content not found', 'asapdigest-core'),
                    'code' => 'not_found'
                ], 404);
            }
            
            // Format dates
            $publish_date = !empty($content->publish_date) ? date('Y-m-d', strtotime($content->publish_date)) : '';
            $created_at = !empty($content->created_at) ? date('Y-m-d H:i', strtotime($content->created_at)) : '';
            
            // Format AI metadata
            $ai_metadata = !empty($content->ai_metadata) ? json_decode($content->ai_metadata, true) : null;
            
            // Send response
            $this->send_success([
                'id' => $content->id,
                'title' => $content->title,
                'content' => $content->content,
                'summary' => $content->summary,
                'type' => $content->type,
                'status' => $content->status,
                'quality_score' => $content->quality_score,
                'source_url' => $content->source_url,
                'source_id' => $content->source_id,
                'ai_metadata' => $ai_metadata,
                'publish_date' => $publish_date,
                'created_at' => $created_at,
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'content_details_error', $e->getMessage(), [
                'content_id' => $content_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while retrieving content details.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle searching content for the content library
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_search_content() {
        // Verify request
        $this->verify_request();
        
        try {
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
            $this->send_success([
                'items' => $processed_items,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
                'filters' => [
                    'search' => $search,
                    'type' => $type,
                    'status' => $status,
                    'min_quality' => $min_quality
                ]
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'search_content_error', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while searching content.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle bulk actions for content library
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_bulk_action_content() {
        // Verify request
        $this->verify_request();
        
        // Validate required parameters
        $this->validate_params(['bulk_action', 'content_ids']);
        
        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $content_ids = array_map('intval', (array) $_POST['content_ids']);
        
        if (empty($bulk_action) || empty($content_ids)) {
            $this->send_error([
                'message' => __('Missing required parameters', 'asapdigest-core'),
                'code' => 'missing_params'
            ]);
        }
        
        try {
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
                    $message = sprintf(
                        _n('%d item deleted successfully.', '%d items deleted successfully.', $affected, 'asapdigest-core'),
                        $affected
                    );
                    break;
                    
                case 'change_status':
                    // Validate status
                    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
                    
                    if (empty($new_status) || !in_array($new_status, ['approved', 'rejected', 'pending', 'processing'])) {
                        $this->send_error([
                            'message' => __('Invalid status', 'asapdigest-core'),
                            'code' => 'invalid_status'
                        ]);
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
                    $this->send_error([
                        'message' => __('Invalid action', 'asapdigest-core'),
                        'code' => 'invalid_action'
                    ]);
            }
            
            $this->send_success([
                'affected' => $affected,
                'message' => $message,
                'action' => $bulk_action,
                'content_ids' => $content_ids
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'bulk_action_error', $e->getMessage(), [
                'action' => $bulk_action,
                'content_ids' => $content_ids,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while processing bulk action.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle reindexing content
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_reindex_content() {
        // Verify request
        $this->verify_request('nonce', 'manage_options');
        
        try {
            // Get batch size
            $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
            
            // Load content processor
            require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/content-processing/bootstrap.php';
            $processor = asap_digest_get_content_processor();
            
            // Reindex content
            $result = $processor->reindex_content($batch_size);
            
            $this->send_success([
                'message' => sprintf(
                    __('Successfully reindexed %d content items.', 'asapdigest-core'),
                    count($result['processed'] ?? [])
                ),
                'result' => $result
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'reindex_content_error', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while reindexing content.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle re-enriching content with AI
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_reenrich_content_ai() {
        // Verify request
        $this->verify_request();
        
        // Validate required parameters
        $this->validate_params(['content_id']);
        
        $content_id = intval($_POST['content_id']);
        
        try {
            global $wpdb;
            $table = $wpdb->prefix . 'asap_ingested_content';
            $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $content_id), ARRAY_A);
            
            if (!$content) {
                $this->send_error([
                    'message' => __('Content not found.', 'asapdigest-core'),
                    'code' => 'not_found'
                ], 404);
            }
            
            require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/content-processing/class-content-processor.php';
            $processor = new \ASAP_Digest_Content_Processor();
            $result = $processor->process($content);
            
            if (!empty($result['success'])) {
                // Save new ai_metadata
                $ai_metadata = $result['data']['content']['ai_metadata'] ?? '';
                $wpdb->update($table, ['ai_metadata' => $ai_metadata], ['id' => $content_id], ['%s'], ['%d']);
                
                $this->send_success([
                    'message' => __('Content has been successfully re-enriched with AI.', 'asapdigest-core'),
                    'ai_metadata' => json_decode($ai_metadata, true)
                ]);
            } else {
                $msg = $result['errors'] ? implode('; ', (array)$result['errors']) : __('AI enrichment failed.', 'asapdigest-core');
                
                $this->send_error([
                    'message' => $msg,
                    'code' => 'enrichment_failed'
                ]);
            }
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'reenrich_content_error', $e->getMessage(), [
                'content_id' => $content_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while re-enriching content.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
} 