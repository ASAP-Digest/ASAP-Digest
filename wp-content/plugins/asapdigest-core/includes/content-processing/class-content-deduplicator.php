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
        return ASAP_Digest_Content_Validator::generate_fingerprint($data);
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
                "SELECT id, type, title, source_url, publish_date, created_at 
                FROM {$content_table} WHERE id = %d",
                $duplicate_id
            ),
            ARRAY_A
        );
        
        return $details ?: false;
    }

    /**
     * Add content fingerprint to index
     *
     * @param int $content_id The content ID
     * @param string $fingerprint The content fingerprint
     * @param int $quality_score Quality score (1-100)
     * @return bool Success or failure
     */
    public function add_to_index($content_id, $fingerprint, $quality_score) {
        global $wpdb;
        
        $index_table = $this->db_prefix . 'asap_content_index';
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $index_table,
            [
                'ingested_content_id' => $content_id,
                'fingerprint' => $fingerprint,
                'quality_score' => $quality_score,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        
        return $result !== false;
    }

    /**
     * Update content fingerprint in index
     *
     * @param int $content_id The content ID
     * @param string $fingerprint The new content fingerprint
     * @param int $quality_score Quality score (1-100)
     * @return bool Success or failure
     */
    public function update_index($content_id, $fingerprint, $quality_score) {
        global $wpdb;
        
        $index_table = $this->db_prefix . 'asap_content_index';
        $now = current_time('mysql');
        
        // Check if record exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$index_table} WHERE ingested_content_id = %d",
                $content_id
            )
        );
        
        if ($exists) {
            // Update existing record
            $result = $wpdb->update(
                $index_table,
                [
                    'fingerprint' => $fingerprint,
                    'quality_score' => $quality_score,
                    'updated_at' => $now,
                ],
                ['ingested_content_id' => $content_id]
            );
            
            return $result !== false;
        } else {
            // Create new record
            return $this->add_to_index($content_id, $fingerprint, $quality_score);
        }
    }

    /**
     * Remove content from index
     *
     * @param int $content_id The content ID
     * @return bool Success or failure
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
     * Get similar content based on fingerprint
     *
     * @param string $fingerprint The content fingerprint
     * @param int $limit Maximum number of results
     * @return array Similar content items
     */
    public function get_similar_content($fingerprint, $limit = 5) {
        // This is a simplified implementation
        // A more advanced version would use techniques like
        // MinHash/Locality-Sensitive Hashing or similar content scoring
        
        global $wpdb;
        
        $index_table = $this->db_prefix . 'asap_content_index';
        $content_table = $this->db_prefix . 'asap_ingested_content';
        
        // Get fingerprints of similar content by exact match (basic implementation)
        $similar_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ic.id FROM {$content_table} ic
                INNER JOIN {$index_table} idx ON ic.id = idx.ingested_content_id
                WHERE idx.fingerprint = %s
                ORDER BY ic.created_at DESC
                LIMIT %d",
                $fingerprint,
                $limit
            )
        );
        
        if (empty($similar_ids)) {
            return [];
        }
        
        // Get content details
        $content_items = [];
        foreach ($similar_ids as $id) {
            $content_items[] = $this->get_duplicate_details($id);
        }
        
        return array_filter($content_items);
    }

    /**
     * Find potential duplicates by comparing fields
     *
     * @param array $content_data Content data
     * @param int $limit Maximum number of results
     * @return array Potential duplicate content items
     */
    public function find_potential_duplicates($content_data, $limit = 5) {
        global $wpdb;
        
        $content_table = $this->db_prefix . 'asap_ingested_content';
        
        // Create base query
        $query = "SELECT id, type, title, source_url, publish_date, created_at FROM {$content_table} WHERE 1=1";
        $params = [];
        
        // Add conditions for fields that are likely to indicate duplicates
        
        // Title similarity (direct match on simplified title)
        if (!empty($content_data['title'])) {
            $simplified_title = strtolower(preg_replace('/\s+/', ' ', trim($content_data['title'])));
            $query .= " AND LOWER(title) LIKE %s";
            $params[] = '%' . $wpdb->esc_like($simplified_title) . '%';
        }
        
        // Source URL - exact match is strong indicator
        if (!empty($content_data['source_url'])) {
            $source_url = strtolower(trim($content_data['source_url']));
            $query .= " AND source_url = %s";
            $params[] = $source_url;
        }
        
        // Source ID - exact match is strong indicator
        if (!empty($content_data['source_id'])) {
            $source_id = strtolower(trim($content_data['source_id']));
            $query .= " AND source_id = %s";
            $params[] = $source_id;
        }
        
        // Limit results
        $query .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;
        
        // Execute query with all params
        $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        
        return $results ?: [];
    }

    /**
     * Generate report of duplicate content
     *
     * @param array $args Report parameters
     * @return array Report data
     */
    public function get_duplication_report($args = []) {
        global $wpdb;
        
        $default_args = [
            'days' => 30,
            'limit' => 50,
            'min_duplicates' => 2,
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        $index_table = $this->db_prefix . 'asap_content_index';
        $content_table = $this->db_prefix . 'asap_ingested_content';
        
        // Get all fingerprints that appear multiple times
        $query = $wpdb->prepare(
            "SELECT fingerprint, COUNT(*) as duplicate_count
            FROM {$index_table}
            GROUP BY fingerprint
            HAVING COUNT(*) >= %d
            ORDER BY COUNT(*) DESC
            LIMIT %d",
            $args['min_duplicates'],
            $args['limit']
        );
        
        $duplicates = $wpdb->get_results($query, ARRAY_A);
        
        if (empty($duplicates)) {
            return [];
        }
        
        // Get details for each set of duplicates
        $report = [];
        
        foreach ($duplicates as $duplicate) {
            $fingerprint = $duplicate['fingerprint'];
            $count = $duplicate['duplicate_count'];
            
            // Get instances of this duplicate
            $instances = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ic.id, ic.type, ic.title, ic.source_url, 
                    ic.publish_date, ic.created_at, ic.quality_score
                    FROM {$content_table} ic
                    INNER JOIN {$index_table} idx ON ic.id = idx.ingested_content_id
                    WHERE idx.fingerprint = %s
                    ORDER BY ic.created_at ASC",
                    $fingerprint
                ),
                ARRAY_A
            );
            
            if (!empty($instances)) {
                $report[] = [
                    'fingerprint' => $fingerprint,
                    'count' => $count,
                    'instances' => $instances,
                    'first_seen' => $instances[0]['created_at'],
                    'latest_seen' => end($instances)['created_at'],
                ];
            }
        }
        
        return $report;
    }
} 