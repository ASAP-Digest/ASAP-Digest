<?php
/**
 * ASAP Digest Source AJAX Handler
 *
 * Standardized handler for content source-related AJAX operations
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax\Admin;

use AsapDigest\Core\Ajax\Base_AJAX;
use AsapDigest\Core\ErrorLogger;
use AsapDigest\Crawler\ContentCrawler;
use AsapDigest\Crawler\ContentSourceManager;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Source AJAX Handler Class
 *
 * Handles all AJAX requests related to content sources
 *
 * @since 3.0.0
 */
class Source_Ajax extends Base_AJAX {
    
    /**
     * Nonce action for this handler
     *
     * @var string
     */
    protected $nonce_action = 'asap_digest_sources_nonce';
    
    /**
     * Register AJAX actions
     *
     * @since 3.0.0
     * @return void
     */
    protected function register_actions() {
        add_action('wp_ajax_asap_get_sources', [$this, 'handle_get_sources']);
        add_action('wp_ajax_asap_get_source', [$this, 'handle_get_source']);
        add_action('wp_ajax_asap_save_source', [$this, 'handle_save_source']);
        add_action('wp_ajax_asap_delete_source', [$this, 'handle_delete_source']);
        add_action('wp_ajax_asap_run_source', [$this, 'handle_run_source']);
        
        // Additional standardized source handlers
        add_action('wp_ajax_asap_digest_get_content_sources', [$this, 'handle_get_content_sources']);
        add_action('wp_ajax_asap_digest_get_content_source', [$this, 'handle_get_content_source']);
        add_action('wp_ajax_asap_digest_add_content_source', [$this, 'handle_add_content_source']);
        add_action('wp_ajax_asap_digest_update_content_source', [$this, 'handle_update_content_source']);
        add_action('wp_ajax_asap_digest_delete_content_source', [$this, 'handle_delete_content_source']);
        add_action('wp_ajax_asap_digest_trigger_content_crawler', [$this, 'handle_trigger_content_crawler']);
    }
    
    /**
     * Handle getting all content sources
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_get_sources() {
        // Verify request
        $this->verify_request('nonce', 'manage_options');
        
        try {
            // Get source manager
            $source_manager = $this->get_source_manager();
            
            // Get sources
            $sources = $source_manager->load_sources(false);
            
            // Add extra info to sources
            foreach ($sources as &$source) {
                // Format last fetch time
                if ($source->last_fetch) {
                    $source->last_fetch_formatted = date_i18n(
                        get_option('date_format') . ' ' . get_option('time_format'),
                        $source->last_fetch
                    );
                } else {
                    $source->last_fetch_formatted = __('Never', 'asapdigest-core');
                }
                
                // Format fetch interval
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
            
            $this->send_success(['sources' => $sources]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'get_sources_error', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while retrieving content sources.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle getting a specific content source
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_get_source() {
        // Verify request
        $this->verify_request('nonce', 'manage_options');
        
        // Validate parameters
        $this->validate_params(['source_id']);
        
        $source_id = intval($_POST['source_id']);
        
        try {
            if ($source_id <= 0) {
                $this->send_error([
                    'message' => __('Invalid source ID', 'asapdigest-core'),
                    'code' => 'invalid_id'
                ]);
            }
            
            // Get source manager
            $source_manager = $this->get_source_manager();
            
            // Get source
            $source = $source_manager->get_source($source_id);
            
            if (!$source) {
                $this->send_error([
                    'message' => __('Source not found', 'asapdigest-core'),
                    'code' => 'not_found'
                ], 404);
            }
            
            $this->send_success(['source' => $source]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'get_source_error', $e->getMessage(), [
                'source_id' => $source_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while retrieving the content source.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle saving a content source (create or update)
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_save_source() {
        // Verify request
        $this->verify_request('nonce', 'manage_options');
        
        // Validate parameters
        $this->validate_params(['source_data']);
        
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        $source_data = $_POST['source_data'];
        
        try {
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
            
            // Get source manager
            $source_manager = $this->get_source_manager();
            
            // Save source
            if ($source_id > 0) {
                // Update existing source
                $result = $source_manager->update_source($source_id, $sanitized_data);
                $message = __('Source updated successfully', 'asapdigest-core');
            } else {
                // Add new source
                $result = $source_manager->add_source($sanitized_data);
                $source_id = $result;
                $message = __('Source added successfully', 'asapdigest-core');
            }
            
            if (!$result) {
                $this->send_error([
                    'message' => __('Failed to save source', 'asapdigest-core'),
                    'code' => 'save_failed'
                ]);
            }
            
            // Get updated source
            $source = $source_manager->get_source($source_id);
            
            $this->send_success([
                'message' => $message,
                'source' => $source,
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'save_source_error', $e->getMessage(), [
                'source_id' => $source_id,
                'source_data' => $source_data,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while saving the content source.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle deleting a content source
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_delete_source() {
        // Verify request
        $this->verify_request('nonce', 'manage_options');
        
        // Validate parameters
        $this->validate_params(['source_id']);
        
        $source_id = intval($_POST['source_id']);
        
        try {
            if ($source_id <= 0) {
                $this->send_error([
                    'message' => __('Invalid source ID', 'asapdigest-core'),
                    'code' => 'invalid_id'
                ]);
            }
            
            // Get source manager
            $source_manager = $this->get_source_manager();
            
            // Delete source
            $result = $source_manager->delete_source($source_id);
            
            if (!$result) {
                $this->send_error([
                    'message' => __('Failed to delete source', 'asapdigest-core'),
                    'code' => 'delete_failed'
                ]);
            }
            
            $this->send_success([
                'message' => __('Source deleted successfully', 'asapdigest-core'),
                'source_id' => $source_id
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'delete_source_error', $e->getMessage(), [
                'source_id' => $source_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while deleting the content source.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle running a content source
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_run_source() {
        // Verify request
        $this->verify_request('nonce', 'manage_options');
        
        // Validate parameters
        $this->validate_params(['source_id']);
        
        $source_id = intval($_POST['source_id']);
        
        try {
            if ($source_id <= 0) {
                $this->send_error([
                    'message' => __('Invalid source ID', 'asapdigest-core'),
                    'code' => 'invalid_id'
                ]);
            }
            
            // Get source manager
            $source_manager = $this->get_source_manager();
            
            // Get source
            $source = $source_manager->get_source($source_id);
            
            if (!$source) {
                $this->send_error([
                    'message' => __('Source not found', 'asapdigest-core'),
                    'code' => 'not_found'
                ], 404);
            }
            
            // Get processor
            require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/content-processing/bootstrap.php';
            $processor = asap_digest_get_content_processor();
            
            // Get crawler
            $crawler = new ContentCrawler($source_manager, $processor);
            
            // Run crawler for this source
            $result = $crawler->crawl_source($source);
            
            if (!$result) {
                $this->send_error([
                    'message' => __('Failed to crawl source', 'asapdigest-core'),
                    'code' => 'crawl_failed'
                ]);
            }
            
            $this->send_success([
                'message' => sprintf(
                    __('Source crawled successfully. Processed %d items, stored %d items.', 'asapdigest-core'),
                    $result['items_processed'] ?? 0,
                    $result['items_stored'] ?? 0
                ),
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'run_source_error', $e->getMessage(), [
                'source_id' => $source_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while running the content source.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle getting content sources (enhanced version)
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_get_content_sources() {
        // Verify request
        $this->verify_request();
        
        try {
            // Parse request parameters
            $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
            $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
            $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 50;
            $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name';
            $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
            
            // Get source manager
            $source_manager = $this->get_source_manager();
            
            // Load all sources
            $all_sources = $source_manager->load_sources(false);
            
            // Filter and process sources manually since the methods don't exist
            $filtered_sources = [];
            $total = 0;
            
            foreach ($all_sources as $source) {
                // Apply filters
                $match = true;
                
                if (!empty($search) && stripos($source->name, $search) === false && stripos($source->url, $search) === false) {
                    $match = false;
                }
                
                if (!empty($type) && $source->type !== $type) {
                    $match = false;
                }
                
                if (!empty($status)) {
                    $current_status = $source->active ? 'active' : 'inactive';
                    if ($current_status !== $status) {
                        $match = false;
                    }
                }
                
                if ($match) {
                    $total++;
                    $filtered_sources[] = $source;
                }
            }
            
            // Sort sources
            usort($filtered_sources, function($a, $b) use ($orderby, $order) {
                $result = 0;
                if ($orderby === 'name') {
                    $result = strcmp($a->name, $b->name);
                } elseif ($orderby === 'type') {
                    $result = strcmp($a->type, $b->type);
                } elseif ($orderby === 'date' || $orderby === 'created_at') {
                    $result = strtotime($a->created_at) - strtotime($b->created_at);
                }
                
                return $order === 'DESC' ? -$result : $result;
            });
            
            // Apply pagination
            $sources = array_slice($filtered_sources, $offset, $limit);
            
            // Format for response
            $formatted_sources = [];
            foreach ($sources as $source) {
                $formatted_sources[] = [
                    'id' => intval($source->id),
                    'name' => $source->name,
                    'type' => $source->type,
                    'url' => $source->url,
                    'frequency' => isset($source->fetch_interval) ? $source->fetch_interval : 'daily',
                    'status' => $source->active ? 'active' : 'inactive',
                    'last_fetch' => $source->last_fetch,
                    'health' => 'good', // Default value
                    'items_count' => 0, // Would need a separate query to get this
                    'configuration' => json_decode($source->config, true),
                    'created_at' => $source->created_at,
                    'updated_at' => $source->updated_at,
                ];
            }
            
            // Return response
            $this->send_success([
                'sources' => $formatted_sources,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'get_content_sources_error', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while retrieving content sources.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle getting a single content source (enhanced version)
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_get_content_source() {
        // Verify request
        $this->verify_request();
        
        // Validate parameters
        if (!isset($_GET['id'])) {
            $this->send_error([
                'message' => __('Missing source ID', 'asapdigest-core'),
                'code' => 'missing_id'
            ], 400);
        }
        
        $source_id = intval($_GET['id']);
        
        try {
            // Get source manager
            $source_manager = $this->get_source_manager();
            
            // Get content source
            $source = $source_manager->get_source($source_id);
            
            if (!$source) {
                $this->send_error([
                    'message' => __('Content source not found', 'asapdigest-core'),
                    'code' => 'not_found'
                ], 404);
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
            $this->send_success($formatted_source);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'get_content_source_error', $e->getMessage(), [
                'source_id' => $source_id,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while retrieving the content source.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get source manager instance
     *
     * @since 3.0.0
     * @return ContentSourceManager
     */
    private function get_source_manager() {
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/crawler/class-content-source-manager.php';
        return new ContentSourceManager();
    }
} 