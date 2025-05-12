<?php
/**
 * ASAP Digest REST API Ingested Content Controller
 *
 * Error Handling & Logging:
 *   - All critical errors and exceptions are logged using the ErrorLogger utility (see \ASAPDigest\Core\ErrorLogger).
 *   - Errors are recorded in the wp_asap_error_log table with context, type, message, data, and severity.
 *   - PHP error_log is used as a fallback and for development/debugging.
 *   - This ensures a unified, queryable error log for admin monitoring and alerting.
 *
 * @see \ASAPDigest\Core\ErrorLogger
 * @package ASAPDigest_Core
 * @created 2025-05-10
 * @file-marker ASAP_Digest_REST_Ingested_Content
 */

namespace ASAPDigest\Core\API;

use ASAPDigest\Core\ErrorLogger;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_REST_Ingested_Content extends ASAP_Digest_REST_Base {
    /**
     * Constructor
     */
    public function __construct() {
        $this->rest_base = 'ingested-content';
        parent::__construct();
    }

    /**
     * Register routes for ingested content endpoints
     */
    public function register_routes() {
        // List ingested content
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_item'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                    'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
                ],
                'schema' => [$this, 'get_item_schema'],
            ]
        );

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
                'args' => [
                    'id' => [
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ],
                ],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
                'args' => [
                    'id' => [
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ],
                ],
            ],
            'schema' => [$this, 'get_item_schema'],
        ]);
        
        // New endpoint for finding similar content
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/similar', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_similar_content'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => [
                    'id' => [
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ],
                    'limit' => [
                        'default' => 5,
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0 && $param <= 20;
                        }
                    ],
                    'min_similarity' => [
                        'default' => 0,
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param >= 0 && $param <= 100;
                        }
                    ],
                ],
            ],
            'schema' => [$this, 'get_item_schema'],
        ]);
    }

    /**
     * List ingested content (GET)
     * @param WP_REST_Request $request
     * @return mixed
     */
    public function get_items($request) {
        global $wpdb;
        
        // Include content processing bootstrap
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/content-processing/bootstrap.php';
        
        $content_table = $wpdb->prefix . 'asap_ingested_content';
        $index_table = $wpdb->prefix . 'asap_content_index';
        
        // Extract and sanitize parameters
        $per_page = isset($request['per_page']) ? min(intval($request['per_page']), 100) : 20;
        $page = isset($request['page']) ? max(1, intval($request['page'])) : 1;
        $offset = ($page - 1) * $per_page;
        
        // Build the query
        $where_clauses = [];
        $where_values = [];
        
        // Filter by content type
        if (!empty($request['type'])) {
            $types = is_array($request['type']) ? $request['type'] : [$request['type']];
            $placeholders = implode(',', array_fill(0, count($types), '%s'));
            $where_clauses[] = "type IN ($placeholders)";
            $where_values = array_merge($where_values, array_map('sanitize_text_field', $types));
        }
        
        // Filter by minimum quality score
        if (isset($request['min_quality_score']) && intval($request['min_quality_score']) > 0) {
            $min_score = intval($request['min_quality_score']);
            $where_clauses[] = "quality_score >= %d";
            $where_values[] = $min_score;
        }
        
        // Filter by maximum quality score
        if (isset($request['max_quality_score']) && intval($request['max_quality_score']) <= 100) {
            $max_score = intval($request['max_quality_score']);
            $where_clauses[] = "quality_score <= %d";
            $where_values[] = $max_score;
        }
        
        // Filter by status
        if (!empty($request['status'])) {
            $statuses = is_array($request['status']) ? $request['status'] : [$request['status']];
            $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
            $where_clauses[] = "status IN ($placeholders)";
            $where_values = array_merge($where_values, array_map('sanitize_text_field', $statuses));
        }
        
        // Search by keyword in title, content, or summary
        if (!empty($request['search'])) {
            $search_term = sanitize_text_field($request['search']);
            $where_clauses[] = "(title LIKE %s OR content LIKE %s OR summary LIKE %s)";
            $search_pattern = '%' . $wpdb->esc_like($search_term) . '%';
            $where_values[] = $search_pattern;
            $where_values[] = $search_pattern;
            $where_values[] = $search_pattern;
        }
        
        // Date filtering
        if (!empty($request['date_from'])) {
            $date_from = sanitize_text_field($request['date_from']);
            $where_clauses[] = "publish_date >= %s";
            $where_values[] = $date_from;
        }
        
        if (!empty($request['date_to'])) {
            $date_to = sanitize_text_field($request['date_to']);
            $where_clauses[] = "publish_date <= %s";
            $where_values[] = $date_to;
        }
        
        // Build WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Order by
        $order_by = !empty($request['orderby']) ? sanitize_sql_orderby($request['orderby']) : 'created_at';
        $order = !empty($request['order']) && strtoupper($request['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Allowed columns for ordering
        $allowed_orderby_columns = ['id', 'type', 'title', 'publish_date', 'quality_score', 'created_at', 'updated_at'];
        if (!in_array($order_by, $allowed_orderby_columns)) {
            $order_by = 'created_at'; // Default
        }
        
        // Count total items (for pagination headers)
        $count_query = "SELECT COUNT(*) FROM $content_table $where_sql";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total_items = $wpdb->get_var($count_query);
        
        // Get items
        $query = "SELECT * FROM $content_table $where_sql ORDER BY $order_by $order LIMIT %d OFFSET %d";
        $prepared_values = array_merge($where_values, [$per_page, $offset]);
        $query = $wpdb->prepare($query, $prepared_values);
        $items = $wpdb->get_results($query, ARRAY_A);
        
        // Transform items for response
        $response_items = [];
        foreach ($items as $item) {
            // Add additional fields that might be useful for consumers
            $item['_links'] = [
                'self' => [
                    'href' => rest_url($this->namespace . '/' . $this->rest_base . '/' . $item['id']),
                ],
            ];
            
            // You could add related duplicates or similar content (using our deduplication system)
            if (!empty($request['include_similar']) && $request['include_similar'] === true) {
                $processor = asap_digest_get_content_processor();
                $related_items = []; // Placeholder for similar items
                
                // This is where you would use the content processor to find similar content
                // Example: $related_items = $processor->find_similar_content($item['id']);
                
                $item['similar_content'] = $related_items;
            }
            
            $response_items[] = $this->prepare_item_for_response($item, $request);
        }
        
        // Build response with pagination
        $response = rest_ensure_response($response_items);
        
        // Add pagination headers
        $total_pages = ceil($total_items / $per_page);
        
        $response->header('X-WP-Total', $total_items);
        $response->header('X-WP-TotalPages', $total_pages);
        
        // Add link headers for pagination (HATEOAS)
        $base = rest_url($this->namespace . '/' . $this->rest_base);
        $request_params = $request->get_query_params();
        
        // Replace page parameter in the query string for pagination links
        if ($page > 1) {
            $prev_page = $page - 1;
            $prev_link = add_query_arg(array_merge($request_params, ['page' => $prev_page]), $base);
            $response->link_header('prev', $prev_link);
        }
        
        if ($page < $total_pages) {
            $next_page = $page + 1;
            $next_link = add_query_arg(array_merge($request_params, ['page' => $next_page]), $base);
            $response->link_header('next', $next_link);
        }
        
        return $response;
    }

    /**
     * Prepare a single item for response
     */
    public function prepare_item_for_response($item, $request) {
        // You can apply additional transformations here if needed
        $data = [
            'id' => (int) $item['id'],
            'type' => $item['type'],
            'title' => $item['title'],
            'content' => $item['content'],
            'summary' => $item['summary'] ?? '',
            'source_url' => $item['source_url'],
            'source_id' => $item['source_id'] ?? '',
            'publish_date' => $item['publish_date'],
            'ingestion_date' => $item['ingestion_date'],
            'fingerprint' => $item['fingerprint'],
            'quality_score' => (int) $item['quality_score'],
            'status' => $item['status'],
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at'],
        ];
        
        // Include extra data if available
        if (!empty($item['extra']) && $this->is_valid_json($item['extra'])) {
            $data['extra'] = json_decode($item['extra'], true);
        }
        
        // Include _links if available
        if (!empty($item['_links'])) {
            $data['_links'] = $item['_links'];
        }
        
        // Include similar_content if available
        if (!empty($item['similar_content'])) {
            $data['similar_content'] = $item['similar_content'];
        }
        
        return $data;
    }

    /**
     * Check if a string is valid JSON
     */
    private function is_valid_json($string) {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Create new ingested content (POST)
     * @param WP_REST_Request $request
     * @return mixed
     */
    public function create_item($request) {
        global $wpdb;
        $params = $request->get_json_params();
        // Validate required fields
        $required = ['type', 'title', 'content', 'source_url'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                /**
                 * Log missing parameter using ErrorLogger utility.
                 * Context: 'rest_ingested_content', error_type: 'missing_param', severity: 'warning'.
                 * Includes missing field and params for debugging.
                 */
                ErrorLogger::log('rest_ingested_content', 'missing_param', 'Missing required parameter: ' . $field, [
                    'missing_field' => $field,
                    'params' => $params
                ], 'warning');
                return $this->prepare_error_response(
                    'missing_param',
                    sprintf(__('Missing required parameter: %s', 'asap-digest'), $field)
                );
            }
        }
        // Generate fingerprint
        $fields = [
            strtolower(trim($params['title'] ?? '')),
            strtolower(trim($params['content'] ?? '')),
            strtolower(trim($params['source_url'] ?? '')),
            strtolower(trim($params['publish_date'] ?? '')),
            strtolower(trim($params['source_id'] ?? '')),
        ];
        $canonical = implode('||', $fields);
        $fingerprint = hash('sha256', $canonical);
        // Check for duplicate
        $index_table = $wpdb->prefix . 'asap_content_index';
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ingested_content_id FROM {$index_table} WHERE fingerprint = %s LIMIT 1",
            $fingerprint
        ));
        if ($existing_id) {
            /**
             * Log duplicate content using ErrorLogger utility.
             * Context: 'rest_ingested_content', error_type: 'duplicate_content', severity: 'warning'.
             * Includes fingerprint and params for debugging.
             */
            ErrorLogger::log('rest_ingested_content', 'duplicate_content', 'Content with this fingerprint already exists.', [
                'fingerprint' => $fingerprint,
                'params' => $params
            ], 'warning');
            return $this->prepare_error_response(
                'duplicate_content',
                __('Content with this fingerprint already exists.', 'asap-digest'),
                409
            );
        }
        // Calculate quality score (simple version)
        $completeness = (!empty($params['title']) && !empty($params['content']) && !empty($params['summary'])) ? 1 : 0.5;
        $recency = (isset($params['publish_date']) && strtotime($params['publish_date']) > strtotime('-7 days')) ? 1 : 0.5;
        $length = (isset($params['content']) && strlen($params['content']) > 500) ? 1 : 0.5;
        $score = 0.4 * 1 + 0.3 * $completeness + 0.2 * $recency + 0.1 * $length;
        $quality_score = round($score * 100);
        // Insert into asap_ingested_content
        $now = current_time('mysql');
        $insert_data = [
            'type' => $params['type'],
            'title' => $params['title'],
            'content' => $params['content'],
            'summary' => $params['summary'] ?? '',
            'source_url' => $params['source_url'],
            'source_id' => $params['source_id'] ?? '',
            'publish_date' => $params['publish_date'] ?? $now,
            'ingestion_date' => $now,
            'fingerprint' => $fingerprint,
            'quality_score' => $quality_score,
            'status' => $params['status'] ?? 'published',
            'extra' => isset($params['extra']) ? wp_json_encode($params['extra']) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $result = $wpdb->insert($wpdb->prefix . 'asap_ingested_content', $insert_data);
        if (!$result) {
            /**
             * Log DB insert error using ErrorLogger utility.
             * Context: 'rest_ingested_content', error_type: 'db_insert_error', severity: 'error'.
             * Includes last_error and insert_data for debugging.
             */
            ErrorLogger::log('rest_ingested_content', 'db_insert_error', $wpdb->last_error ?: 'Failed to insert content.', [
                'insert_data' => $insert_data,
                'params' => $params
            ], 'error');
            return $this->prepare_error_response(
                'db_insert_error',
                $wpdb->last_error ?: __('Failed to insert content.', 'asap-digest'),
                500
            );
        }
        $ingested_id = intval($wpdb->insert_id);
        // Insert into content index
        $wpdb->insert($index_table, [
            'ingested_content_id' => $ingested_id,
            'fingerprint' => $fingerprint,
            'quality_score' => $quality_score,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->prepare_response(['id' => $ingested_id]);
    }

    /**
     * Get endpoint arguments for POST
     * @return array
     */
    private function get_item_args() {
        return [
            'type' => [ 'type' => 'string', 'required' => true ],
            'title' => [ 'type' => 'string', 'required' => true ],
            'content' => [ 'type' => 'string', 'required' => true ],
            'summary' => [ 'type' => 'string', 'required' => false ],
            'source_url' => [ 'type' => 'string', 'required' => true ],
            'source_id' => [ 'type' => 'string', 'required' => false ],
            'publish_date' => [ 'type' => 'string', 'required' => false ],
            'status' => [ 'type' => 'string', 'required' => false ],
            'extra' => [ 'type' => 'object', 'required' => false ],
        ];
    }

    /**
     * Get similar content based on a given content ID
     * Leverage our content processing pipeline for finding similar content
     */
    public function get_similar_content($request) {
        // Include content processing bootstrap if not already included
        if (!class_exists('ASAP_Digest_Content_Processor')) {
            require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/content-processing/bootstrap.php';
        }
        
        $content_id = (int) $request['id'];
        $limit = isset($request['limit']) ? (int) $request['limit'] : 5;
        $min_similarity = isset($request['min_similarity']) ? (int) $request['min_similarity'] : 0;
        
        // Get the content processor instance
        $processor = asap_digest_get_content_processor();
        
        // Get the content item
        $content_item = $processor->get_content($content_id);
        
        if (!$content_item) {
            /**
             * Log not found error using ErrorLogger utility.
             * Context: 'rest_ingested_content', error_type: 'not_found', severity: 'warning'.
             * Includes content_id and request for debugging.
             */
            ErrorLogger::log('rest_ingested_content', 'not_found', 'Content not found for get_similar_content', [
                'content_id' => $content_id,
                'request' => $request->get_params()
            ], 'warning');
            return new WP_Error(
                'rest_item_not_found',
                __('Content not found.'),
                ['status' => 404]
            );
        }
        
        // Initialize deduplicator for finding similar content
        $deduplicator = new \ASAP_Digest_Content_Deduplicator();
        
        // Option 1: Get exact duplicates (using fingerprint)
        // For exact duplicates, we can use the fingerprint from the content
        $similar_items = $deduplicator->get_similar_content($content_item['fingerprint'], $limit);
        
        // Option 2: For fuzzy matching based on fields (if not enough exact matches found)
        if (count($similar_items) < $limit) {
            $remaining_limit = $limit - count($similar_items);
            
            // Convert content_item array to a format expected by find_potential_duplicates
            $content_data = [
                'title' => $content_item['title'],
                'source_url' => $content_item['source_url'],
                'source_id' => $content_item['source_id'],
            ];
            
            // Find potential duplicates based on field similarity
            $potential_duplicates = $deduplicator->find_potential_duplicates($content_data, $remaining_limit);
            
            // Filter out the original item and any exact duplicates already found
            $existing_ids = array_column($similar_items, 'id');
            $existing_ids[] = $content_id; // Add original item ID
            
            $additional_items = [];
            foreach ($potential_duplicates as $duplicate) {
                if (!in_array($duplicate['id'], $existing_ids)) {
                    $additional_items[] = $duplicate;
                    $existing_ids[] = $duplicate['id']; // Prevent duplicates
                    
                    // Break if we've reached our limit
                    if (count($additional_items) >= $remaining_limit) {
                        break;
                    }
                }
            }
            
            // Merge the additional items
            $similar_items = array_merge($similar_items, $additional_items);
        }
        
        // Transform items for response and add similarity score
        $response_items = [];
        foreach ($similar_items as $item) {
            // Skip the original item if it somehow got included
            if ((int)$item['id'] === $content_id) {
                continue;
            }
            
            // Get the full item details
            $full_item = $processor->get_content((int)$item['id']);
            if (!$full_item) {
                continue; // Skip if we can't get the full details
            }
            
            // Calculate a simple similarity score (just for demonstration)
            // In a real implementation, this would be more sophisticated
            $similarity_score = $this->calculate_similarity_score($content_item, $full_item);
            
            // Skip items below the minimum similarity threshold
            if ($similarity_score < $min_similarity) {
                continue;
            }
            
            // Add similarity score to the item
            $full_item['similarity_score'] = $similarity_score;
            
            // Add to response items
            $response_items[] = $this->prepare_item_for_response($full_item, $request);
        }
        
        // Return response
        return rest_ensure_response($response_items);
    }

    /**
     * Calculate a similarity score between two content items
     * This is a simple implementation for demonstration purposes
     */
    private function calculate_similarity_score($item1, $item2) {
        // Initialize score components
        $title_similarity = 0;
        $content_similarity = 0;
        $source_similarity = 0;
        
        // Title similarity (simple approach - can be improved with more advanced text matching)
        if (!empty($item1['title']) && !empty($item2['title'])) {
            $title1 = strtolower($item1['title']);
            $title2 = strtolower($item2['title']);
            
            // Exact match = 100, otherwise use simple algorithm (can be improved)
            if ($title1 === $title2) {
                $title_similarity = 100;
            } else {
                // Simple Jaccard similarity for words (not perfect but better than nothing)
                $words1 = array_filter(explode(' ', $title1));
                $words2 = array_filter(explode(' ', $title2));
                
                $intersection = array_intersect($words1, $words2);
                $union = array_unique(array_merge($words1, $words2));
                
                $title_similarity = empty($union) ? 0 : (count($intersection) / count($union)) * 100;
            }
        }
        
        // Source similarity
        if (!empty($item1['source_url']) && !empty($item2['source_url'])) {
            // Same source = high similarity
            $source_similarity = ($item1['source_url'] === $item2['source_url']) ? 100 : 0;
        }
        
        // Simple content similarity (can be improved with proper text analysis)
        if (!empty($item1['content']) && !empty($item2['content'])) {
            // For this demo, just check if content lengths are within 20% of each other
            $length1 = strlen(strip_tags($item1['content']));
            $length2 = strlen(strip_tags($item2['content']));
            
            $length_ratio = min($length1, $length2) / max($length1, $length2);
            $content_similarity = $length_ratio * 100;
        }
        
        // Calculate weighted score
        $weight_title = 0.5;
        $weight_source = 0.3;
        $weight_content = 0.2;
        
        $score = ($title_similarity * $weight_title) + 
                 ($source_similarity * $weight_source) + 
                 ($content_similarity * $weight_content);
        
        // Return rounded score
        return round($score);
    }
} 