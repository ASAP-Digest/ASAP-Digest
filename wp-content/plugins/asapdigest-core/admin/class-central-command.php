<?php
/**
 * ASAP Digest Central Command
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Central_Command
 */

namespace ASAPDigest\Core;

use AsapDigest\Crawler\ContentSourceManager;
use function add_menu_page;
use function add_submenu_page;
use function plugin_dir_path;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Central_Command {
    /**
     * @var ASAP_Digest_Core Plugin instance
     */
    private $plugin;

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin = ASAP_Digest_Core::get_instance();
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
    }

    /**
     * Register admin menu items
     */
    public function register_menu() {
        // Add main menu
        add_menu_page(
            '⚡️ Central Command',
            '⚡️ Central Command',
            'manage_options',
            'asap-central-command',
            [$this, 'render_dashboard'],
            'dashicons-superhero',
            3
        );

        // Add submenus
        add_submenu_page(
            'asap-central-command',
            'Usage Analytics',
            'Usage Analytics',
            'manage_options',
            'asap-usage-analytics',
            [$this, 'render_usage_analytics']
        );

        add_submenu_page(
            'asap-central-command',
            'Service Costs',
            'Service Costs',
            'manage_options',
            'asap-service-costs',
            [$this, 'render_service_costs']
        );

        add_submenu_page(
            'asap-central-command',
            'Content Crawler',
            'Content Crawler',
            'manage_options',
            'asap-digest-crawler',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'asap-central-command',
            'Source Management',
            'Sources',
            'manage_options',
            'asap-digest-sources',
            [$this, 'render_sources']
        );

        add_submenu_page(
            'asap-central-command',
            'Content Moderation',
            'Moderation Queue',
            'manage_options',
            'asap-digest-moderation',
            [$this, 'render_moderation']
        );

        add_submenu_page(
            'asap-central-command',
            'Crawler Analytics',
            'Analytics',
            'manage_options',
            'asap-digest-crawler-analytics',
            [$this, 'render_analytics']
        );
    }

    /**
     * Register REST API endpoints for crawler management
     */
    public function register_api_endpoints() {
        register_rest_route('asap/v1', '/crawler/sources', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_sources'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route('asap/v1', '/crawler/sources', [
            'methods' => 'POST',
            'callback' => [$this, 'api_add_source'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route('asap/v1', '/crawler/sources/(?P<id>\\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'api_update_source'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route('asap/v1', '/crawler/queue', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_moderation_queue'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route('asap/v1', '/crawler/queue/approve/(?P<id>\\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'api_approve_content'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route('asap/v1', '/crawler/queue/reject/(?P<id>\\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'api_reject_content'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route('asap/v1', '/crawler/metrics', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_metrics'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
    }

    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        require_once plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }

    /**
     * Render usage analytics page
     */
    public function render_usage_analytics() {
        require_once plugin_dir_path(__FILE__) . 'views/usage-analytics.php';
    }

    /**
     * Render service costs page
     */
    public function render_service_costs() {
        require_once plugin_dir_path(__FILE__) . 'views/service-costs.php';
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submission() {
        if (!isset($_POST['asap_action'])) {
            return;
        }

        if (!check_admin_referer('asap_central_command')) {
            wp_die(__('Invalid nonce specified', 'asap-digest'));
        }

        $action = sanitize_text_field($_POST['asap_action']);

        switch ($action) {
            case 'update_service_cost':
                $this->handle_service_cost_update();
                break;
            // Add more action handlers as needed
        }
    }

    /**
     * Handle service cost update
     */
    private function handle_service_cost_update() {
        if (!isset($_POST['service_name'], $_POST['cost_per_unit'], $_POST['markup_percentage'])) {
            return;
        }

        $service_name = sanitize_text_field($_POST['service_name']);
        $cost_per_unit = floatval($_POST['cost_per_unit']);
        $markup_percentage = floatval($_POST['markup_percentage']);

        $this->plugin->get_usage_tracker()->update_service_cost(
            $service_name,
            $cost_per_unit,
            $markup_percentage
        );

        add_settings_error(
            'asap_messages',
            'service_cost_updated',
            __('Service cost updated successfully.', 'asap-digest'),
            'success'
        );
    }

    // --- Render methods (stubs) ---
    public function render_sources() { echo '<div class="wrap"><h1>Source Management</h1></div>'; }
    public function render_moderation() { echo '<div class="wrap"><h1>Moderation Queue</h1></div>'; }
    public function render_analytics() { echo '<div class="wrap"><h1>Crawler Analytics</h1></div>'; }

    // --- API callback stubs ---
    /**
     * Get all content sources (GET /asap/v1/crawler/sources)
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_get_sources($request) {
        $manager = new ContentSourceManager();
        $sources = $manager->load_sources();
        return rest_ensure_response(['sources' => $sources]);
    }

    /**
     * Add a new content source (POST /asap/v1/crawler/sources)
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_add_source($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $table = $wpdb->prefix . 'asap_content_sources';
        $data = [
            'name' => sanitize_text_field($params['name'] ?? ''),
            'type' => sanitize_text_field($params['type'] ?? ''),
            'url' => esc_url_raw($params['url'] ?? ''),
            'config' => maybe_serialize($params['config'] ?? []),
            'content_types' => maybe_serialize($params['content_types'] ?? []),
            'active' => !empty($params['active']) ? 1 : 0,
            'fetch_interval' => intval($params['fetch_interval'] ?? 3600),
            'min_interval' => intval($params['min_interval'] ?? 1800),
            'max_interval' => intval($params['max_interval'] ?? 86400),
            'quota_max_items' => isset($params['quota_max_items']) ? intval($params['quota_max_items']) : null,
            'quota_max_size' => isset($params['quota_max_size']) ? intval($params['quota_max_size']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        $result = $wpdb->insert($table, $data);
        if ($result === false) {
            return new \WP_Error('db_insert_error', 'Failed to add source', ['status' => 500]);
        }
        return rest_ensure_response(['success' => true, 'id' => $wpdb->insert_id]);
    }

    /**
     * Update a content source (PUT /asap/v1/crawler/sources/{id})
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_update_source($request) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();
        $table = $wpdb->prefix . 'asap_content_sources';
        $data = [];
        if (isset($params['name'])) $data['name'] = sanitize_text_field($params['name']);
        if (isset($params['type'])) $data['type'] = sanitize_text_field($params['type']);
        if (isset($params['url'])) $data['url'] = esc_url_raw($params['url']);
        if (isset($params['config'])) $data['config'] = maybe_serialize($params['config']);
        if (isset($params['content_types'])) $data['content_types'] = maybe_serialize($params['content_types']);
        if (isset($params['active'])) $data['active'] = !empty($params['active']) ? 1 : 0;
        if (isset($params['fetch_interval'])) $data['fetch_interval'] = intval($params['fetch_interval']);
        if (isset($params['min_interval'])) $data['min_interval'] = intval($params['min_interval']);
        if (isset($params['max_interval'])) $data['max_interval'] = intval($params['max_interval']);
        if (isset($params['quota_max_items'])) $data['quota_max_items'] = intval($params['quota_max_items']);
        if (isset($params['quota_max_size'])) $data['quota_max_size'] = intval($params['quota_max_size']);
        $data['updated_at'] = current_time('mysql');
        $result = $wpdb->update($table, $data, ['id' => $id]);
        if ($result === false) {
            return new \WP_Error('db_update_error', 'Failed to update source', ['status' => 500]);
        }
        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    /**
     * Get moderation queue (GET /asap/v1/crawler/queue)
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_get_moderation_queue($request) {
        global $wpdb;
        // Fetch posts with post_status = 'pending' for all relevant post types
        $post_types = [
            'asap_article', 'asap_podcast', 'asap_financial', 'asap_xpost',
            'asap_reddit', 'asap_event', 'asap_polymarket', 'asap_keyterm'
        ];
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_type, post_date, post_status FROM {$wpdb->posts} WHERE post_status = 'pending' AND post_type IN ($placeholders) ORDER BY post_date DESC LIMIT 100",
            ...$post_types
        ));
        return rest_ensure_response(['queue' => $posts]);
    }

    /**
     * Approve content in moderation queue (POST /asap/v1/crawler/queue/approve/{id})
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_approve_content($request) {
        $id = intval($request['id']);
        $result = wp_update_post(['ID' => $id, 'post_status' => 'publish']);
        if (is_wp_error($result)) {
            return new \WP_Error('moderation_approve_error', 'Failed to approve content', ['status' => 500]);
        }
        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    /**
     * Reject content in moderation queue (POST /asap/v1/crawler/queue/reject/{id})
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_reject_content($request) {
        $id = intval($request['id']);
        $result = wp_update_post(['ID' => $id, 'post_status' => 'trash']);
        if (is_wp_error($result)) {
            return new \WP_Error('moderation_reject_error', 'Failed to reject content', ['status' => 500]);
        }
        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    /**
     * Get crawler metrics (GET /asap/v1/crawler/metrics)
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_get_metrics($request) {
        global $wpdb;
        $prefix = $wpdb->prefix;
        // Aggregate metrics from source_metrics and storage_metrics tables
        $source_metrics = $wpdb->get_results("SELECT * FROM {$prefix}asap_source_metrics ORDER BY date DESC LIMIT 100");
        $storage_metrics = $wpdb->get_results("SELECT * FROM {$prefix}asap_storage_metrics ORDER BY date DESC LIMIT 100");
        return rest_ensure_response([
            'source_metrics' => $source_metrics,
            'storage_metrics' => $storage_metrics
        ]);
    }
} 