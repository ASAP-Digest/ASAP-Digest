<?php
/**
 * Content Deduplicator Class
 *
 * Manages content deduplication by generating and checking fingerprints
 * to prevent duplicate content across the system.
 *
 * @package ASAP_Digest
 * @subpackage Content_Processing
 * @since 2.2.0
 * @file-marker ASAP_Digest_Content_Deduplicator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content Deduplicator class
 *
 * @since 2.2.0
 */
class ASAP_Digest_Content_Deduplicator {

    /**
     * Database prefix
     *
     * @var string
     */
    private $db_prefix;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->db_prefix = $wpdb->prefix;
    }

    /**
     * Generate content fingerprint
     *
     * @param array $data Content data
     * @return string SHA-256 fingerprint
     */
    public function generate_fingerprint($data) {
        // Use the ASAP_Digest_Content_Validator's fingerprint method
        // for consistency across the system
        if (method_exists('ASAP_Digest_Content_Validator', 'generate_fingerprint')) {
            return ASAP_Digest_Content_Validator::generate_fingerprint($data);
        }

        // Fallback if method doesn't exist
        $normalized_content = $this->normalize_content_for_fingerprint($data);
        
        // Create canonical string from normalized content
        $canonical = implode('||', $normalized_content);
        
        // Generate fingerprint
        return hash('sha256', $canonical);
    }

    /**
     * Normalize content data for fingerprint generation
     * 
     * @param array $data Content data
     * @return array Normalized fields for fingerprint
     */
    public function normalize_content_for_fingerprint($data) {
        // Normalize title (remove extra spaces, lowercase)
        $title = strtolower(trim($data['title'] ?? ''));
        
        // Normalize content (strip HTML, remove extra whitespace, lowercase)
        $content = '';
        if (!empty($data['content'])) {
            $content = wp_strip_all_tags($data['content']);
            $content = preg_replace('/\s+/', ' ', $content); // Convert multiple spaces to single space
            $content = strtolower(trim($content));
        }
        
        // Normalize URL (lowercase, remove tracking parameters)
        $url = strtolower(trim($data['source_url'] ?? ''));
        // Remove common tracking parameters from URL
        if ($url) {
            $url_parts = parse_url($url);
            if (!empty($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
                // Remove common tracking parameters
                $tracking_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'];
                foreach ($tracking_params as $param) {
                    if (isset($query_params[$param])) {
                        unset($query_params[$param]);
                    }
                }
                // Rebuild URL without tracking parameters
                $url_parts['query'] = http_build_query($query_params);
                $url = $this->build_url($url_parts);
            }
        }
        
        // Normalize publish date
        $publish_date = '';
        if (!empty($data['publish_date'])) {
            // Convert to standard format YYYY-MM-DD
            $timestamp = strtotime($data['publish_date']);
            if ($timestamp) {
                $publish_date = date('Y-m-d', $timestamp);
            } else {
                $publish_date = strtolower(trim($data['publish_date']));
            }
        }
        
        // Normalize source ID
        $source_id = strtolower(trim($data['source_id'] ?? ''));
        
        return [
            'title' => $title,
            'content' => $content,
            'url' => $url,
            'publish_date' => $publish_date,
            'source_id' => $source_id
        ];
    }
    
    /**
     * Rebuild URL from parsed components
     *
     * @param array $parts URL parts
     * @return string Rebuilt URL
     */
    private function build_url($parts) {
        $scheme   = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host     = isset($parts['host']) ? $parts['host'] : '';
        $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user     = isset($parts['user']) ? $parts['user'] : '';
        $pass     = isset($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parts['path']) ? $parts['path'] : '';
        $query    = isset($parts['query']) && !empty($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Check if content fingerprint already exists
     *
     * @param string $fingerprint The content fingerprint
     * @param int $exclude_id ID to exclude from check (for updates)
     * @return bool|int False if unique, existing ID if duplicate
     */
    public function is_duplicate($fingerprint, $exclude_id = 0) {
        global $wpdb;
        
        $index_table = $this->db_prefix . 'asap_content_index';
        
        if ($exclude_id > 0) {
            $query = $wpdb->prepare(
                "SELECT ingested_content_id FROM {$index_table} 
                WHERE fingerprint = %s AND ingested_content_id != %d 
                LIMIT 1",
                $fingerprint,
                $exclude_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT ingested_content_id FROM {$index_table} 
                WHERE fingerprint = %s LIMIT 1",
                $fingerprint
            );
        }
        
        $existing_id = $wpdb->get_var($query);
        
        if ($existing_id) {
            return (int) $existing_id;
        }
        
        return false;
    }

    /**
     * Get duplicate content details
     *
     * @param int $duplicate_id The duplicate content ID
     * @return array|false Content details or false if not found
     */
    public function get_duplicate_details($duplicate_id) {
        global $wpdb;
        
        $content_table = $this->db_prefix . 'asap_ingested_content';
        
        $details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, type, title, source_url, publish_date, created_at, 
                        quality_score, status, summary 
                FROM {$content_table} WHERE id = %d",
                $duplicate_id
            ),
            ARRAY_A
        );
        
        return $details ?: false;
    }

    /**
     * Find similar content based on fingerprint
     *
     * @param string $fingerprint Content fingerprint
     * @param int $limit Maximum number of results to return
     * @return array Similar content items
     */
    public function get_similar_content($fingerprint, $limit = 5) {
        global $wpdb;
        
        $index_table = $this->db_prefix . 'asap_content_index';
        $content_table = $this->db_prefix . 'asap_ingested_content';
        
        $query = $wpdb->prepare(
            "SELECT c.id, c.type, c.title, c.source_url, c.publish_date, 
                    c.quality_score, c.status, c.created_at
            FROM {$index_table} i
            JOIN {$content_table} c ON i.ingested_content_id = c.id
            WHERE i.fingerprint = %s
            LIMIT %d",
            $fingerprint,
            $limit
        );
        
        $similar = $wpdb->get_results($query, ARRAY_A);
        
        return $similar ?: [];
    }

    /**
     * Find potential duplicate content based on title similarity
     *
     * @param array $content_data Content data
     * @param int $limit Maximum number of results to return
     * @return array Potential duplicate content items
     */
    public function find_potential_duplicates($content_data, $limit = 5) {
        global $wpdb;
        
        $content_table = $this->db_prefix . 'asap_ingested_content';
        
        // Safety check for title
        if (empty($content_data['title'])) {
            return [];
        }
        
        // Normalize and extract terms from the title
        $title = trim($content_data['title']);
        $title_terms = $this->extract_title_terms($title);
        
        if (empty($title_terms)) {
            return [];
        }
        
        // Build LIKE clauses for title terms
        $like_clauses = [];
        $like_params = [];
        
        foreach ($title_terms as $term) {
            if (strlen($term) > 3) { // Skip short terms
                $like_clauses[] = "title LIKE %s";
                $like_params[] = '%' . $wpdb->esc_like($term) . '%';
            }
        }
        
        if (empty($like_clauses)) {
            return [];
        }
        
        // Build the query with multiple LIKE conditions
        $sql = "SELECT id, type, title, source_url, publish_date, quality_score, status, created_at
                FROM {$content_table} 
                WHERE " . implode(' OR ', $like_clauses);
                
        // Add content type filter if available
        if (!empty($content_data['type'])) {
            $sql .= $wpdb->prepare(" AND type = %s", $content_data['type']);
        }
        
        // Exclude the current content ID if provided
        if (!empty($content_data['id'])) {
            $sql .= $wpdb->prepare(" AND id != %d", $content_data['id']);
        }
        
        // Add limit
        $sql .= " LIMIT " . intval($limit);
        
        // Prepare the final query with all parameters
        $query = $wpdb->prepare($sql, $like_params);
        
        // Execute the query
        $potential_duplicates = $wpdb->get_results($query, ARRAY_A);
        
        return $potential_duplicates ?: [];
    }

    /**
     * Extract relevant terms from a title for duplicate detection
     *
     * @param string $title Content title
     * @return array Array of key terms
     */
    private function extract_title_terms($title) {
        // Convert to lowercase
        $title = strtolower($title);
        
        // Remove special characters
        $title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $title);
        
        // Split into words
        $words = preg_split('/\s+/', $title, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out common stop words
        $stop_words = ['a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 
                      'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'like', 
                      'from', 'of', 'that', 'this', 'these', 'those'];
        
        $filtered_words = array_filter($words, function($word) use ($stop_words) {
            return !in_array($word, $stop_words) && strlen($word) > 2;
        });
        
        return array_values($filtered_words); // Reindex array
    }

    /**
     * Add content to the index
     *
     * @param int $content_id Content ID
     * @param string $fingerprint Content fingerprint
     * @param int $quality_score Content quality score
     * @return bool True on success, false on failure
     */
    public function add_to_index($content_id, $fingerprint, $quality_score) {
        global $wpdb;
        
        $index_table = $this->db_prefix . 'asap_content_index';
        $now = current_time('mysql');
        
        // Check if already in index
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$index_table} WHERE ingested_content_id = %d",
            $content_id
        ));
        
        if ($existing) {
            // Update existing index
            $result = $wpdb->update(
                $index_table,
                [
                    'fingerprint' => $fingerprint,
                    'quality_score' => $quality_score,
                    'updated_at' => $now
                ],
                ['ingested_content_id' => $content_id]
            );
        } else {
            // Insert new index
            $result = $wpdb->insert(
                $index_table,
                [
                    'ingested_content_id' => $content_id,
                    'fingerprint' => $fingerprint,
                    'quality_score' => $quality_score,
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
        }
        
        return $result !== false;
    }

    /**
     * Remove content from the index
     *
     * @param int $content_id Content ID
     * @return bool True on success, false on failure
     */
    public function remove_from_index($content_id) {
        global $wpdb;
        
        $index_table = $this->db_prefix . 'asap_content_index';
        
        $result = $wpdb->delete(
            $index_table,
            ['ingested_content_id' => $content_id]
        );
        
        return $result !== false;
    }

    /**
     * Reindex existing content
     * 
     * Processes existing content in the asap_ingested_content table
     * and adds it to the index if missing.
     *
     * @param int $batch_size Number of items to process per batch
     * @return array Reindexing results
     */
    public function reindex_content($batch_size = 50) {
        global $wpdb;
        
        $content_table = $this->db_prefix . 'asap_ingested_content';
        $index_table = $this->db_prefix . 'asap_content_index';
        
        // Get items that need indexing (not in the index table)
        $items_to_process = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id, c.type, c.title, c.content, c.source_url, c.source_id, 
                        c.publish_date, c.quality_score 
                FROM {$content_table} c
                LEFT JOIN {$index_table} i ON c.id = i.ingested_content_id
                WHERE i.ingested_content_id IS NULL
                LIMIT %d",
                $batch_size
            ),
            ARRAY_A
        );
        
        $results = [
            'processed' => count($items_to_process),
            'success' => 0,
            'errors' => 0,
            'duplicates' => 0,
            'message' => '',
        ];
        
        if (empty($items_to_process)) {
            $results['message'] = 'No content found that needs reindexing.';
            return $results;
        }
        
        foreach ($items_to_process as $item) {
            // Generate fingerprint
            $fingerprint = $this->generate_fingerprint($item);
            
            // Check for duplicates
            $duplicate_id = $this->is_duplicate($fingerprint, $item['id']);
            
            if ($duplicate_id) {
                // Found duplicate - log but continue with indexing
                $results['duplicates']++;
                
                // Log duplicates for further review
                $this->log_duplicate($item['id'], $duplicate_id, $fingerprint);
            }
            
            // Add to index regardless (we still want to track this content)
            $quality_score = !empty($item['quality_score']) ? $item['quality_score'] : 50; // default if not set
            $added = $this->add_to_index($item['id'], $fingerprint, $quality_score);
            
            if ($added) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
        }
        
        // Update message
        $results['message'] = sprintf(
            'Processed %d items: %d indexed, %d duplicates found, %d errors',
            $results['processed'],
            $results['success'],
            $results['duplicates'],
            $results['errors']
        );
        
        return $results;
    }

    /**
     * Log duplicate content for further review
     * 
     * @param int $content_id Current content ID
     * @param int $duplicate_id Existing duplicate ID
     * @param string $fingerprint Content fingerprint
     */
    private function log_duplicate($content_id, $duplicate_id, $fingerprint) {
        global $wpdb;
        
        $log_table = $this->db_prefix . 'asap_duplicate_log';
        $now = current_time('mysql');
        
        // Check if log table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$log_table}'");
        
        if (!$table_exists) {
            // Create log table if it doesn't exist
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$log_table} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                content_id bigint(20) NOT NULL,
                duplicate_id bigint(20) NOT NULL,
                fingerprint varchar(64) NOT NULL,
                resolution varchar(20) DEFAULT NULL,
                created_at datetime NOT NULL,
                resolved_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY content_id (content_id),
                KEY duplicate_id (duplicate_id),
                KEY fingerprint (fingerprint),
                KEY resolution (resolution)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Insert log entry
        $wpdb->insert(
            $log_table,
            [
                'content_id' => $content_id,
                'duplicate_id' => $duplicate_id,
                'fingerprint' => $fingerprint,
                'created_at' => $now
            ]
        );
    }

    /**
     * Generate duplicate content report
     * 
     * @param array $args Report arguments
     *               - days (int): Number of days to look back
     *               - limit (int): Maximum number of results
     *               - status (string): Filter by resolution status
     * @return array Report data
     */
    public function generate_duplicate_report($args = []) {
        global $wpdb;
        
        // Default arguments
        $defaults = [
            'days' => defined('ASAP_DUPLICATES_LOOKBACK_DAYS') ? ASAP_DUPLICATES_LOOKBACK_DAYS : 30,
            'limit' => 100,
            'status' => null, // null = all, 'pending', 'resolved', 'kept_new', 'kept_existing'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $log_table = $this->db_prefix . 'asap_duplicate_log';
        $content_table = $this->db_prefix . 'asap_ingested_content';
        
        // Check if log table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$log_table}'");
        
        if (!$table_exists) {
            return [
                'status' => 'error',
                'message' => 'Duplicate log table does not exist',
                'duplicates' => [],
                'total' => 0
            ];
        }
        
        // Build query
        $query = "
            SELECT 
                l.id AS log_id,
                l.content_id,
                l.duplicate_id,
                l.fingerprint,
                l.resolution,
                l.created_at,
                l.resolved_at,
                c1.title AS content_title,
                c1.source_url AS content_url,
                c1.quality_score AS content_score,
                c1.status AS content_status,
                c2.title AS duplicate_title,
                c2.source_url AS duplicate_url,
                c2.quality_score AS duplicate_score,
                c2.status AS duplicate_status
            FROM {$log_table} l
            LEFT JOIN {$content_table} c1 ON l.content_id = c1.id
            LEFT JOIN {$content_table} c2 ON l.duplicate_id = c2.id
            WHERE l.created_at >= %s
        ";
        
        $params = [date('Y-m-d H:i:s', strtotime("-{$args['days']} days"))];
        
        // Add status filter if provided
        if (!is_null($args['status'])) {
            if ($args['status'] === 'pending') {
                $query .= " AND l.resolution IS NULL";
            } else if ($args['status'] === 'resolved') {
                $query .= " AND l.resolution IS NOT NULL";
            } else {
                $query .= " AND l.resolution = %s";
                $params[] = $args['status'];
            }
        }
        
        // Add order and limit
        $query .= " ORDER BY l.created_at DESC LIMIT %d";
        $params[] = $args['limit'];
        
        // Prepare and execute query
        $prepared_query = $wpdb->prepare($query, $params);
        $duplicates = $wpdb->get_results($prepared_query, ARRAY_A);
        
        // Get total count for pagination
        $count_query = "
            SELECT COUNT(*) 
            FROM {$log_table} 
            WHERE created_at >= %s
        ";
        
        $count_params = [date('Y-m-d H:i:s', strtotime("-{$args['days']} days"))];
        
        // Add status filter to count query if provided
        if (!is_null($args['status'])) {
            if ($args['status'] === 'pending') {
                $count_query .= " AND resolution IS NULL";
            } else if ($args['status'] === 'resolved') {
                $count_query .= " AND resolution IS NOT NULL";
            } else {
                $count_query .= " AND resolution = %s";
                $count_params[] = $args['status'];
            }
        }
        
        $prepared_count_query = $wpdb->prepare($count_query, $count_params);
        $total = $wpdb->get_var($prepared_count_query);
        
        return [
            'status' => 'success',
            'message' => sprintf('Found %d duplicate entries', count($duplicates)),
            'duplicates' => $duplicates,
            'total' => (int) $total
        ];
    }

    /**
     * Resolve duplicate content
     * 
     * @param int $log_id Duplicate log ID
     * @param string $resolution Resolution (kept_new, kept_existing)
     * @return bool True on success, false on failure
     */
    public function resolve_duplicate($log_id, $resolution) {
        global $wpdb;
        
        $log_table = $this->db_prefix . 'asap_duplicate_log';
        $now = current_time('mysql');
        
        // Valid resolutions
        $valid_resolutions = ['kept_new', 'kept_existing', 'ignored', 'manually_resolved'];
        
        if (!in_array($resolution, $valid_resolutions)) {
            return false;
        }
        
        // Update resolution
        $result = $wpdb->update(
            $log_table,
            [
                'resolution' => $resolution,
                'resolved_at' => $now
            ],
            ['id' => $log_id]
        );
        
        return $result !== false;
    }
} 