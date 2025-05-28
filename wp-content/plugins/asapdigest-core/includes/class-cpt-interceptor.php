<?php
/**
 * CPT Interceptor
 * Intercepts WordPress CPT operations and redirects them to custom tables
 * 
 * @package ASAPDigest
 * @since 1.0.0
 */

namespace ASAPDigest\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CPT_Interceptor
 * 
 * Intercepts CPT operations for digests and modules and redirects to custom tables
 */
class CPT_Interceptor {
    
    /**
     * Custom table manager instance
     * @var Custom_Table_Manager
     */
    private $table_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->table_manager = new Custom_Table_Manager();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Intercept digest CPT operations
        add_action('wp_insert_post', [$this, 'intercept_digest_creation'], 10, 3);
        add_action('before_delete_post', [$this, 'intercept_digest_deletion'], 10, 2);
        add_filter('wp_insert_post_data', [$this, 'intercept_digest_update'], 10, 2);
        
        // Intercept module CPT operations
        add_action('wp_insert_post', [$this, 'intercept_module_creation'], 10, 3);
        add_action('before_delete_post', [$this, 'intercept_module_deletion'], 10, 2);
        add_filter('wp_insert_post_data', [$this, 'intercept_module_update'], 10, 2);
        
        // Intercept queries to redirect to custom tables
        add_action('pre_get_posts', [$this, 'intercept_digest_queries']);
        add_action('pre_get_posts', [$this, 'intercept_module_queries']);
        
        // Override post retrieval for digests and modules
        add_filter('the_posts', [$this, 'override_digest_posts'], 10, 2);
        add_filter('the_posts', [$this, 'override_module_posts'], 10, 2);
    }
    
    /**
     * Intercept digest creation and redirect to custom table
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function intercept_digest_creation($post_id, $post, $update) {
        if ($post->post_type !== 'asap_digest' || $update) {
            return;
        }
        
        // Extract digest data from post
        $digest_data = [
            'user_id' => $post->post_author,
            'status' => $this->map_post_status_to_digest_status($post->post_status),
            'layout_template_id' => get_post_meta($post_id, '_asap_digest_layout_template_slug', true),
            'content' => $this->extract_digest_content($post_id),
            'sentiment_score' => get_post_meta($post_id, '_asap_digest_aggregated_sentiment_score', true),
            'life_moment' => get_post_meta($post_id, '_asap_digest_life_moment', true),
            'is_saved' => (bool) get_post_meta($post_id, '_asap_digest_is_saved', true),
            'reminders' => get_post_meta($post_id, '_asap_digest_reminders', true),
            'share_link' => get_post_meta($post_id, '_asap_digest_share_link', true),
            'podcast_url' => get_post_meta($post_id, '_asap_digest_podcast_url', true)
        ];
        
        // Create in custom table
        $custom_digest_id = $this->table_manager->create_digest($digest_data);
        
        if ($custom_digest_id) {
            // Store the custom table ID as meta for reference
            update_post_meta($post_id, '_asap_custom_digest_id', $custom_digest_id);
            
            // Migrate module placements if any exist
            $this->migrate_digest_module_placements($post_id, $custom_digest_id);
            
            error_log("Digest CPT {$post_id} redirected to custom table with ID {$custom_digest_id}");
        }
    }
    
    /**
     * Intercept module creation and redirect to custom table
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function intercept_module_creation($post_id, $post, $update) {
        if ($post->post_type !== 'asap_module' || $update) {
            return;
        }
        
        // Extract module data from post
        $module_data = [
            'type' => get_post_meta($post_id, '_asap_module_type', true) ?: 'content',
            'title' => $post->post_title,
            'content' => $post->post_content,
            'source_url' => get_post_meta($post_id, '_asap_module_original_source_url', true),
            'source_id' => get_post_meta($post_id, '_asap_module_source_ingested_content_id', true),
            'ingested_content_id' => get_post_meta($post_id, '_asap_module_source_ingested_content_id', true),
            'ai_processed_content_id' => get_post_meta($post_id, '_asap_module_ai_processed_content_id', true),
            'publish_date' => get_post_meta($post_id, '_asap_module_publish_date', true),
            'quality_score' => get_post_meta($post_id, '_asap_module_quality_score', true),
            'status' => $this->map_post_status_to_module_status($post->post_status),
            'metadata' => $this->extract_module_metadata($post_id)
        ];
        
        // Create in custom table
        $custom_module_id = $this->table_manager->create_module($module_data);
        
        if ($custom_module_id) {
            // Store the custom table ID as meta for reference
            update_post_meta($post_id, '_asap_custom_module_id', $custom_module_id);
            
            // Update any existing module placements to reference the new custom module
            $this->update_module_placements_reference($post_id, $custom_module_id);
            
            error_log("Module CPT {$post_id} redirected to custom table with ID {$custom_module_id}");
        }
    }
    
    /**
     * Intercept digest deletion
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function intercept_digest_deletion($post_id, $post) {
        if ($post->post_type !== 'asap_digest') {
            return;
        }
        
        $custom_digest_id = get_post_meta($post_id, '_asap_custom_digest_id', true);
        if ($custom_digest_id) {
            $this->table_manager->delete_digest($custom_digest_id);
            error_log("Deleted custom digest {$custom_digest_id} for CPT {$post_id}");
        }
    }
    
    /**
     * Intercept module deletion
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function intercept_module_deletion($post_id, $post) {
        if ($post->post_type !== 'asap_module') {
            return;
        }
        
        $custom_module_id = get_post_meta($post_id, '_asap_custom_module_id', true);
        if ($custom_module_id) {
            $this->table_manager->delete_module($custom_module_id);
            error_log("Deleted custom module {$custom_module_id} for CPT {$post_id}");
        }
    }
    
    /**
     * Intercept digest queries and redirect to custom table
     * 
     * @param \WP_Query $query Query object
     */
    public function intercept_digest_queries($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }
        
        if ($query->get('post_type') === 'asap_digest') {
            // Mark this query for custom handling
            $query->set('_use_custom_digest_table', true);
        }
    }
    
    /**
     * Intercept module queries and redirect to custom table
     * 
     * @param \WP_Query $query Query object
     */
    public function intercept_module_queries($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }
        
        if ($query->get('post_type') === 'asap_module') {
            // Mark this query for custom handling
            $query->set('_use_custom_module_table', true);
        }
    }
    
    /**
     * Override digest posts with custom table data
     * 
     * @param array $posts Array of post objects
     * @param \WP_Query $query Query object
     * @return array Modified posts array
     */
    public function override_digest_posts($posts, $query) {
        if (!$query->get('_use_custom_digest_table')) {
            return $posts;
        }
        
        // Get digests from custom table
        $user_id = $query->get('author');
        $status = $this->map_wp_status_to_digest_status($query->get('post_status'));
        
        $custom_digests = $this->table_manager->get_user_digests($user_id, $status);
        
        // Convert custom digest objects to WP_Post-like objects
        $converted_posts = [];
        foreach ($custom_digests as $digest) {
            $converted_posts[] = $this->convert_digest_to_post($digest);
        }
        
        return $converted_posts;
    }
    
    /**
     * Override module posts with custom table data
     * 
     * @param array $posts Array of post objects
     * @param \WP_Query $query Query object
     * @return array Modified posts array
     */
    public function override_module_posts($posts, $query) {
        if (!$query->get('_use_custom_module_table')) {
            return $posts;
        }
        
        // Get modules from custom table
        $args = [
            'type' => $query->get('meta_value'), // If filtering by type
            'status' => $this->map_wp_status_to_module_status($query->get('post_status')),
            'limit' => $query->get('posts_per_page') ?: 20,
            'offset' => ($query->get('paged') - 1) * ($query->get('posts_per_page') ?: 20)
        ];
        
        $custom_modules = $this->table_manager->get_modules($args);
        
        // Convert custom module objects to WP_Post-like objects
        $converted_posts = [];
        foreach ($custom_modules as $module) {
            $converted_posts[] = $this->convert_module_to_post($module);
        }
        
        return $converted_posts;
    }
    
    /**
     * Extract digest content from post meta
     * 
     * @param int $post_id Post ID
     * @return string JSON encoded content
     */
    private function extract_digest_content($post_id) {
        $content = [];
        
        // Extract various content fields
        $gridstack_config = get_post_meta($post_id, '_asap_digest_gridstack_config_json', true);
        if ($gridstack_config) {
            $content['gridstack_config'] = json_decode($gridstack_config, true);
        }
        
        $compiled_content = get_post_meta($post_id, '_asap_digest_compiled_content_json', true);
        if ($compiled_content) {
            $content['compiled_content'] = json_decode($compiled_content, true);
        }
        
        return wp_json_encode($content);
    }
    
    /**
     * Extract module metadata from post meta
     * 
     * @param int $post_id Post ID
     * @return string JSON encoded metadata
     */
    private function extract_module_metadata($post_id) {
        $metadata = [];
        
        // Extract various metadata fields
        $ai_title = get_post_meta($post_id, '_asap_module_ai_generated_title', true);
        if ($ai_title) {
            $metadata['ai_generated_title'] = $ai_title;
        }
        
        $ai_summary = get_post_meta($post_id, '_asap_module_ai_generated_summary', true);
        if ($ai_summary) {
            $metadata['ai_generated_summary'] = $ai_summary;
        }
        
        $display_config = get_post_meta($post_id, '_asap_module_display_config_json', true);
        if ($display_config) {
            $metadata['display_config'] = json_decode($display_config, true);
        }
        
        return wp_json_encode($metadata);
    }
    
    /**
     * Migrate module placements from CPT reference to custom table reference
     * 
     * @param int $cpt_digest_id CPT digest ID
     * @param int $custom_digest_id Custom table digest ID
     */
    private function migrate_digest_module_placements($cpt_digest_id, $custom_digest_id) {
        global $wpdb;
        
        // Update existing placements to reference the new custom digest
        $wpdb->update(
            $wpdb->prefix . 'asap_digest_module_placements',
            ['digest_id' => $custom_digest_id],
            ['digest_id' => $cpt_digest_id],
            ['%d'],
            ['%d']
        );
    }
    
    /**
     * Update module placements to reference custom module instead of CPT
     * 
     * @param int $cpt_module_id CPT module ID
     * @param int $custom_module_id Custom table module ID
     */
    private function update_module_placements_reference($cpt_module_id, $custom_module_id) {
        global $wpdb;
        
        // Update existing placements to reference the new custom module
        $wpdb->update(
            $wpdb->prefix . 'asap_digest_module_placements',
            ['module_id' => $custom_module_id],
            ['module_cpt_id' => $cpt_module_id],
            ['%d'],
            ['%d']
        );
    }
    
    /**
     * Convert custom digest object to WP_Post-like object
     * 
     * @param object $digest Custom digest object
     * @return \WP_Post Post object
     */
    private function convert_digest_to_post($digest) {
        $post_data = [
            'ID' => $digest->id,
            'post_author' => $digest->user_id,
            'post_date' => $digest->created_at,
            'post_date_gmt' => get_gmt_from_date($digest->created_at),
            'post_content' => wp_json_encode($digest->content),
            'post_title' => "Digest #{$digest->id}",
            'post_excerpt' => '',
            'post_status' => $digest->status,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => "digest-{$digest->id}",
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => $digest->created_at,
            'post_modified_gmt' => get_gmt_from_date($digest->created_at),
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => home_url("/?post_type=asap_digest&p={$digest->id}"),
            'menu_order' => 0,
            'post_type' => 'asap_digest',
            'post_mime_type' => '',
            'comment_count' => 0,
            'filter' => 'raw'
        ];
        
        return new \WP_Post((object) $post_data);
    }
    
    /**
     * Convert custom module object to WP_Post-like object
     * 
     * @param object $module Custom module object
     * @return \WP_Post Post object
     */
    private function convert_module_to_post($module) {
        $post_data = [
            'ID' => $module->id,
            'post_author' => 1, // Default to admin
            'post_date' => $module->created_at,
            'post_date_gmt' => get_gmt_from_date($module->created_at),
            'post_content' => $module->content,
            'post_title' => $module->title,
            'post_excerpt' => '',
            'post_status' => $module->status === 'active' ? 'publish' : $module->status,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => sanitize_title($module->title),
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => $module->updated_at,
            'post_modified_gmt' => get_gmt_from_date($module->updated_at),
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => home_url("/?post_type=asap_module&p={$module->id}"),
            'menu_order' => 0,
            'post_type' => 'asap_module',
            'post_mime_type' => '',
            'comment_count' => 0,
            'filter' => 'raw'
        ];
        
        return new \WP_Post((object) $post_data);
    }
    
    /**
     * Map WordPress post status to digest status
     * 
     * @param string $post_status WordPress post status
     * @return string Digest status
     */
    private function map_post_status_to_digest_status($post_status) {
        $status_map = [
            'draft' => 'draft',
            'publish' => 'published',
            'private' => 'private',
            'trash' => 'deleted'
        ];
        
        return $status_map[$post_status] ?? 'draft';
    }
    
    /**
     * Map WordPress post status to module status
     * 
     * @param string $post_status WordPress post status
     * @return string Module status
     */
    private function map_post_status_to_module_status($post_status) {
        $status_map = [
            'draft' => 'draft',
            'publish' => 'active',
            'private' => 'private',
            'trash' => 'inactive'
        ];
        
        return $status_map[$post_status] ?? 'active';
    }
    
    /**
     * Map WordPress status to digest status for queries
     * 
     * @param string $wp_status WordPress status
     * @return string Digest status
     */
    private function map_wp_status_to_digest_status($wp_status) {
        if (empty($wp_status)) {
            return null;
        }
        
        return $this->map_post_status_to_digest_status($wp_status);
    }
    
    /**
     * Map WordPress status to module status for queries
     * 
     * @param string $wp_status WordPress status
     * @return string Module status
     */
    private function map_wp_status_to_module_status($wp_status) {
        if (empty($wp_status)) {
            return 'active';
        }
        
        return $this->map_post_status_to_module_status($wp_status);
    }
} 