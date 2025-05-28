<?php
/**
 * Custom Table Manager
 * Handles operations for wp_asap_digests and wp_asap_modules custom tables
 * 
 * @package ASAPDigest
 * @since 1.0.0
 */

namespace ASAPDigest\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Custom_Table_Manager
 * 
 * Manages custom table operations for digests and modules
 */
class Custom_Table_Manager {
    
    /**
     * WordPress database instance
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Create a new digest in wp_asap_digests table
     * 
     * @param array $data Digest data
     * @return int|false Digest ID on success, false on failure
     */
    public function create_digest($data) {
        $defaults = [
            'user_id' => get_current_user_id(),
            'status' => 'draft',
            'layout_template_id' => null,
            'content' => '{}',
            'sentiment_score' => null,
            'life_moment' => null,
            'is_saved' => 0,
            'reminders' => null,
            'share_link' => null,
            'podcast_url' => null
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['user_id'])) {
            return false;
        }
        
        // Ensure content is JSON string
        if (is_array($data['content'])) {
            $data['content'] = wp_json_encode($data['content']);
        }
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'asap_digests',
            [
                'user_id' => $data['user_id'],
                'status' => $data['status'],
                'layout_template_id' => $data['layout_template_id'],
                'content' => $data['content'],
                'sentiment_score' => $data['sentiment_score'],
                'life_moment' => $data['life_moment'],
                'is_saved' => $data['is_saved'],
                'reminders' => $data['reminders'],
                'share_link' => $data['share_link'],
                'podcast_url' => $data['podcast_url']
            ],
            [
                '%d', // user_id
                '%s', // status
                '%s', // layout_template_id
                '%s', // content
                '%s', // sentiment_score
                '%s', // life_moment
                '%d', // is_saved
                '%s', // reminders
                '%s', // share_link
                '%s'  // podcast_url
            ]
        );
        
        if ($result === false) {
            error_log('Failed to create digest: ' . $this->wpdb->last_error);
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get digest by ID
     * 
     * @param int $digest_id Digest ID
     * @return object|null Digest object or null if not found
     */
    public function get_digest($digest_id) {
        $digest = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}asap_digests WHERE id = %d",
                $digest_id
            )
        );
        
        if ($digest && !empty($digest->content)) {
            $digest->content = json_decode($digest->content, true);
        }
        
        return $digest;
    }
    
    /**
     * Get digests by user ID
     * 
     * @param int $user_id User ID
     * @param string $status Optional status filter
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Array of digest objects
     */
    public function get_user_digests($user_id, $status = null, $limit = 20, $offset = 0) {
        $where_clause = "WHERE user_id = %d";
        $params = [$user_id];
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        $sql = "SELECT * FROM {$this->wpdb->prefix}asap_digests 
                {$where_clause} 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $digests = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params)
        );
        
        // Decode JSON content for each digest
        foreach ($digests as $digest) {
            if (!empty($digest->content)) {
                $digest->content = json_decode($digest->content, true);
            }
        }
        
        return $digests;
    }
    
    /**
     * Update digest
     * 
     * @param int $digest_id Digest ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update_digest($digest_id, $data) {
        // Ensure content is JSON string if it's an array
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = wp_json_encode($data['content']);
        }
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'asap_digests',
            $data,
            ['id' => $digest_id],
            null, // Let wpdb determine format
            ['%d'] // ID format
        );
        
        return $result !== false;
    }
    
    /**
     * Delete digest
     * 
     * @param int $digest_id Digest ID
     * @return bool Success status
     */
    public function delete_digest($digest_id) {
        // First delete associated module placements
        $this->wpdb->delete(
            $this->wpdb->prefix . 'asap_digest_module_placements',
            ['digest_id' => $digest_id],
            ['%d']
        );
        
        // Then delete the digest
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'asap_digests',
            ['id' => $digest_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Create a new module in wp_asap_modules table
     * 
     * @param array $data Module data
     * @return int|false Module ID on success, false on failure
     */
    public function create_module($data) {
        $defaults = [
            'type' => 'content',
            'title' => '',
            'content' => '',
            'source_url' => null,
            'source_id' => null,
            'ingested_content_id' => null,
            'ai_processed_content_id' => null,
            'publish_date' => null,
            'quality_score' => null,
            'status' => 'active',
            'metadata' => null
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['type']) || empty($data['title'])) {
            return false;
        }
        
        // Ensure metadata is JSON string
        if (is_array($data['metadata'])) {
            $data['metadata'] = wp_json_encode($data['metadata']);
        }
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'asap_modules',
            [
                'type' => $data['type'],
                'title' => $data['title'],
                'content' => $data['content'],
                'source_url' => $data['source_url'],
                'source_id' => $data['source_id'],
                'ingested_content_id' => $data['ingested_content_id'],
                'ai_processed_content_id' => $data['ai_processed_content_id'],
                'publish_date' => $data['publish_date'],
                'quality_score' => $data['quality_score'],
                'status' => $data['status'],
                'metadata' => $data['metadata']
            ],
            [
                '%s', // type
                '%s', // title
                '%s', // content
                '%s', // source_url
                '%s', // source_id
                '%d', // ingested_content_id
                '%d', // ai_processed_content_id
                '%s', // publish_date
                '%f', // quality_score
                '%s', // status
                '%s'  // metadata
            ]
        );
        
        if ($result === false) {
            error_log('Failed to create module: ' . $this->wpdb->last_error);
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get module by ID
     * 
     * @param int $module_id Module ID
     * @return object|null Module object or null if not found
     */
    public function get_module($module_id) {
        // Debug: Log the module ID being requested
        error_log('ASAP DEBUG: get_module called with ID: ' . $module_id);
        
        $module = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT m.* FROM {$this->wpdb->prefix}asap_modules m WHERE m.id = %d",
                $module_id
            )
        );
        
        // Debug: Log the query result
        if ($this->wpdb->last_error) {
            error_log('ASAP DEBUG: get_module database error: ' . $this->wpdb->last_error);
        }
        
        if ($module) {
            error_log('ASAP DEBUG: get_module found module: ' . $module->title);
            if (!empty($module->metadata)) {
                $module->metadata = json_decode($module->metadata, true);
            }
        } else {
            error_log('ASAP DEBUG: get_module - no module found with ID: ' . $module_id);
        }
        
        return $module;
    }
    
    /**
     * Get modules with optional filters
     * 
     * @param array $args Query arguments
     * @return array Array of module objects
     */
    public function get_modules($args = []) {
        $defaults = [
            'type' => null,
            'status' => null, // Changed to null to show all by default
            'limit' => 20,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = [];
        $params = [];
        
        // Only add status filter if status is specified
        if ($args['status']) {
            $where_conditions[] = "m.status = %s";
            $params[] = $args['status'];
        }
        
        if ($args['type']) {
            $where_conditions[] = "m.type = %s";
            $params[] = $args['type'];
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(' AND ', $where_conditions) : "";
        
        // Sanitize order_by to prevent SQL injection
        $allowed_order_by = ['id', 'type', 'title', 'status', 'quality_score', 'publish_date', 'created_at', 'updated_at'];
        if (!in_array($args['order_by'], $allowed_order_by)) {
            $args['order_by'] = 'created_at';
        }
        
        // Sanitize order direction
        $args['order'] = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT m.*
                FROM {$this->wpdb->prefix}asap_modules m
                {$where_clause}
                ORDER BY m.{$args['order_by']} {$args['order']}
                LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        $modules = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params)
        );
        
        // Decode JSON metadata for each module
        foreach ($modules as $module) {
            if (!empty($module->metadata)) {
                $module->metadata = json_decode($module->metadata, true);
            }
        }
        
        return $modules;
    }
    
    /**
     * Update module
     * 
     * @param int $module_id Module ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update_module($module_id, $data) {
        // Ensure metadata is JSON string if it's an array
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = wp_json_encode($data['metadata']);
        }
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'asap_modules',
            $data,
            ['id' => $module_id],
            null, // Let wpdb determine format
            ['%d'] // ID format
        );
        
        return $result !== false;
    }
    
    /**
     * Delete module
     * 
     * @param int $module_id Module ID
     * @return bool Success status
     */
    public function delete_module($module_id) {
        // First delete associated module placements
        $this->wpdb->delete(
            $this->wpdb->prefix . 'asap_digest_module_placements',
            ['module_id' => $module_id],
            ['%d']
        );
        
        // Then delete the module
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'asap_modules',
            ['id' => $module_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Add module placement to digest
     * 
     * @param array $data Placement data
     * @return int|false Placement ID on success, false on failure
     */
    public function add_module_placement($data) {
        $defaults = [
            'digest_id' => 0,
            'module_id' => 0,
            'module_cpt_id' => 0, // For backward compatibility
            'grid_x' => 0,
            'grid_y' => 0,
            'grid_width' => 1,
            'grid_height' => 1,
            'order_in_grid' => 0
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['digest_id']) || (empty($data['module_id']) && empty($data['module_cpt_id']))) {
            return false;
        }
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'asap_digest_module_placements',
            $data,
            [
                '%d', // digest_id
                '%d', // module_id
                '%d', // module_cpt_id
                '%d', // grid_x
                '%d', // grid_y
                '%d', // grid_width
                '%d', // grid_height
                '%d'  // order_in_grid
            ]
        );
        
        if ($result === false) {
            error_log('Failed to add module placement: ' . $this->wpdb->last_error);
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get module placements for a digest
     * 
     * @param int $digest_id Digest ID
     * @return array Array of placement objects with module data
     */
    public function get_digest_module_placements($digest_id) {
        $placements = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT p.*, 
                        m.type as module_type,
                        m.title as module_title,
                        m.content as module_content,
                        m.metadata as module_metadata,
                        apc.summary as ai_summary,
                        apc.keywords as ai_keywords
                 FROM {$this->wpdb->prefix}asap_digest_module_placements p
                 LEFT JOIN {$this->wpdb->prefix}asap_modules m ON p.module_id = m.id
                 LEFT JOIN {$this->wpdb->prefix}asap_ai_processed_content apc ON m.ai_processed_content_id = apc.id
                 WHERE p.digest_id = %d
                 ORDER BY p.order_in_grid ASC",
                $digest_id
            )
        );
        
        // Decode JSON metadata for each placement
        foreach ($placements as $placement) {
            if (!empty($placement->module_metadata)) {
                $placement->module_metadata = json_decode($placement->module_metadata, true);
            }
        }
        
        return $placements;
    }
    
    /**
     * Update module placement
     * 
     * @param int $placement_id Placement ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update_module_placement($placement_id, $data) {
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'asap_digest_module_placements',
            $data,
            ['id' => $placement_id],
            null, // Let wpdb determine format
            ['%d'] // ID format
        );
        
        return $result !== false;
    }
    
    /**
     * Remove module placement
     * 
     * @param int $placement_id Placement ID
     * @return bool Success status
     */
    public function remove_module_placement($placement_id) {
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'asap_digest_module_placements',
            ['id' => $placement_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Add a module to a digest (alias for add_module_placement)
     * 
     * @param int $digest_id Digest ID
     * @param int $module_id Module ID
     * @param array $placement_data Placement data
     * @return int|false Placement ID or false on failure
     */
    public function add_module_to_digest($digest_id, $module_id, $placement_data = []) {
        $data = [
            'digest_id' => $digest_id,
            'module_id' => $module_id,
            'grid_x' => $placement_data['grid_x'] ?? 0,
            'grid_y' => $placement_data['grid_y'] ?? 0,
            'grid_width' => $placement_data['grid_width'] ?? 1,
            'grid_height' => $placement_data['grid_height'] ?? 1,
            'order_in_grid' => $placement_data['sort_order'] ?? 0
        ];
        
        return $this->add_module_placement($data);
    }
    
    /**
     * Get module placement by ID
     * 
     * @param int $placement_id Placement ID
     * @return array|null
     */
    public function get_module_placement($placement_id) {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}asap_digest_module_placements WHERE id = %d",
            $placement_id
        );
        
        return $this->wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Update digest status
     * 
     * @param int $digest_id Digest ID
     * @param string $status New status
     * @return bool
     */
    public function update_digest_status($digest_id, $status) {
        return $this->update_digest($digest_id, ['status' => $status]);
    }
    
    /**
     * Remove module from digest
     * 
     * @param int $digest_id Digest ID
     * @param int $module_id Module ID
     * @return bool
     */
    public function remove_module_from_digest($digest_id, $module_id) {
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'asap_digest_module_placements',
            [
                'digest_id' => $digest_id,
                'module_id' => $module_id
            ],
            ['%d', '%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Save digest layout with module positions
     * 
     * @param int $digest_id Digest ID
     * @param array $layout_data Layout data
     * @return bool
     */
    public function save_digest_layout($digest_id, $layout_data) {
        // Start transaction
        $this->wpdb->query('START TRANSACTION');
        
        try {
            // Update each module placement
            foreach ($layout_data as $placement) {
                if (!isset($placement['module_id'])) {
                    continue;
                }
                
                // Find the placement ID for this digest/module combination
                $placement_id = $this->wpdb->get_var(
                    $this->wpdb->prepare(
                        "SELECT id FROM {$this->wpdb->prefix}asap_digest_module_placements 
                         WHERE digest_id = %d AND module_id = %d",
                        $digest_id,
                        $placement['module_id']
                    )
                );
                
                if ($placement_id) {
                    $result = $this->update_module_placement($placement_id, [
                        'grid_x' => $placement['grid_x'] ?? 0,
                        'grid_y' => $placement['grid_y'] ?? 0,
                        'grid_width' => $placement['grid_width'] ?? 1,
                        'grid_height' => $placement['grid_height'] ?? 1,
                        'order_in_grid' => $placement['sort_order'] ?? 0
                    ]);
                    
                    if (!$result) {
                        throw new \Exception('Failed to update placement');
                    }
                }
            }
            
            // Commit transaction
            $this->wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            // Rollback transaction
            $this->wpdb->query('ROLLBACK');
            return false;
        }
    }
} 