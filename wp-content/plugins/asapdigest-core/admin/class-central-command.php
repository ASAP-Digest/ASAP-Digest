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
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
    }

    /**
     * Register admin menu items
     */
    public function register_menus() {
        add_menu_page(
            'ASAP Digest',
            'ASAP Digest',
            'manage_options',
            'asap-digest',
            [$this, 'render_dashboard'],
            'dashicons-analytics',
            25
        );
        
        add_submenu_page(
            'asap-digest',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'asap-digest',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'asap-digest',
            'Crawler Sources',
            'Crawler Sources',
            'manage_options',
            'asap-crawler-sources',
            [$this, 'render_sources']
        );
        
        add_submenu_page(
            'asap-digest',
            'Moderation Queue',
            'Moderation Queue',
            'manage_options',
            'asap-moderation-queue',
            [$this, 'render_moderation']
        );
        
        add_submenu_page(
            'asap-digest',
            'Analytics',
            'Analytics',
            'manage_options',
            'asap-analytics',
            [$this, 'render_analytics']
        );
        
        add_submenu_page(
            'asap-digest',
            'AI Settings',
            'AI Settings',
            'manage_options',
            'asap-ai-settings',
            [$this, 'render_ai_settings']
        );
    }

    /**
     * Register REST API endpoints for crawler management
     */
    public function register_api_endpoints() {
        register_rest_route('asap/v1', '/crawler/sources', [
            'methods' => 'GET',
            'callback' => [ $this, 'api_get_sources' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/sources', [
            'methods' => 'POST',
            'callback' => [ $this, 'api_create_source' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/sources/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [ $this, 'api_get_source' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/sources/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [ $this, 'api_update_source' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/sources/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [ $this, 'api_delete_source' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/queue', [
            'methods' => 'GET',
            'callback' => [ $this, 'api_get_moderation_queue' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/queue/approve/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [ $this, 'api_approve_content' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/queue/reject/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [ $this, 'api_reject_content' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/content', [
            'methods' => 'GET',
            'callback' => [ $this, 'api_get_frontend_content' ],
            'permission_callback' => function() { return current_user_can('read'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/moderation-log/(?P<content_id>\d+)', [
            'methods' => 'GET',
            'callback' => [ $this, 'api_get_moderation_log' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/metrics', [
            'methods' => 'GET',
            'callback' => [ $this, 'api_get_metrics' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        
        register_rest_route('asap/v1', '/crawler/moderation-metrics', [
            'methods' => 'GET',
            'callback' => [ $this, 'api_get_moderation_metrics' ],
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

    /**
     * Render Source Management admin page (with AJAX-powered CRUD table)
     */
    public function render_sources() {
        ?>
        <div class="wrap">
            <h1>Source Management</h1>
            <button id="asap-add-source" class="button button-primary">Add New Source</button>
            <div id="asap-sources-table"></div>
            <div id="asap-source-modal" style="display:none;"></div>
        </div>
        <script>
        (function($){
            function fetchSources() {
                $('#asap-sources-table').html('<p>Loading...</p>');
                $.ajax({
                    url: '/wp-json/asap/v1/crawler/sources',
                    method: 'GET',
                    success: function(data) {
                        renderTable(data.sources || []);
                    },
                    error: function() {
                        $('#asap-sources-table').html('<p class="notice notice-error">Failed to load sources.</p>');
                    }
                });
            }
            function renderTable(sources) {
                if (!sources.length) {
                    $('#asap-sources-table').html('<p>No sources found.</p>');
                    return;
                }
                var html = '<table class="wp-list-table widefat fixed striped"><thead><tr>' +
                    '<th>Name</th><th>Type</th><th>URL</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                sources.forEach(function(src) {
                    html += '<tr data-id="'+src.id+'">' +
                        '<td>'+esc(src.name)+'</td>' +
                        '<td>'+esc(src.type)+'</td>' +
                        '<td>'+esc(src.url)+'</td>' +
                        '<td>'+(src.active ? 'Active' : 'Inactive')+'</td>' +
                        '<td>' +
                            '<button class="button asap-edit-source">Edit</button> ' +
                            '<button class="button asap-delete-source">Delete</button>' +
                        '</td>' +
                    '</tr>';
                });
                html += '</tbody></table>';
                $('#asap-sources-table').html(html);
            }
            function esc(str) {
                return String(str).replace(/[&<>"']/g, function(c) {
                    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c];
                });
            }
            // Add/Edit modal logic (simplified for brevity)
            function showSourceModal(source) {
                var isEdit = !!source;
                var modal = $('#asap-source-modal');
                var html = '<div class="asap-modal"><h2>'+(isEdit?'Edit':'Add')+' Source</h2>' +
                    '<form id="asap-source-form">' +
                    '<input type="hidden" name="id" value="'+(source?source.id:'')+'">' +
                    '<p><label>Name<br><input type="text" name="name" value="'+(source?esc(source.name):'')+'" required></label></p>' +
                    '<p><label>Type<br><input type="text" name="type" value="'+(source?esc(source.type):'')+'" required></label></p>' +
                    '<p><label>URL<br><input type="url" name="url" value="'+(source?esc(source.url):'')+'" required></label></p>' +
                    '<p><label>Status<br><select name="active"><option value="1"'+(source&&source.active?' selected':'')+'>Active</option><option value="0"'+(source&&!source.active?' selected':'')+'>Inactive</option></select></label></p>' +
                    '<p><button type="submit" class="button button-primary">'+(isEdit?'Update':'Add')+'</button> <button type="button" class="button asap-cancel-modal">Cancel</button></p>' +
                    '</form></div>';
                modal.html(html).show();
            }
            // Event handlers
            $(document).on('click', '#asap-add-source', function(){ showSourceModal(); });
            $(document).on('click', '.asap-edit-source', function(){
                var id = $(this).closest('tr').data('id');
                var row = $(this).closest('tr');
                showSourceModal({
                    id: id,
                    name: row.find('td:eq(0)').text(),
                    type: row.find('td:eq(1)').text(),
                    url: row.find('td:eq(2)').text(),
                    active: row.find('td:eq(3)').text() === 'Active'
                });
            });
            $(document).on('click', '.asap-delete-source', function(){
                if (!confirm('Delete this source?')) return;
                var id = $(this).closest('tr').data('id');
                $.ajax({
                    url: '/wp-json/asap/v1/crawler/sources/' + id,
                    method: 'DELETE',
                    success: function(){ fetchSources(); },
                    error: function(){ alert('Failed to delete source.'); }
                });
            });
            $(document).on('submit', '#asap-source-form', function(e){
                e.preventDefault();
                var form = $(this);
                var id = form.find('input[name="id"]').val();
                var data = {
                    name: form.find('input[name="name"]').val(),
                    type: form.find('input[name="type"]').val(),
                    url: form.find('input[name="url"]').val(),
                    active: form.find('select[name="active"]').val()
                };
                var method = id ? 'PUT' : 'POST';
                var url = '/wp-json/asap/v1/crawler/sources' + (id ? '/' + id : '');
                $.ajax({
                    url: url,
                    method: method,
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    success: function(){ $('#asap-source-modal').hide(); fetchSources(); },
                    error: function(){ alert('Failed to save source.'); }
                });
            });
            $(document).on('click', '.asap-cancel-modal', function(){ $('#asap-source-modal').hide(); });
            // Initial load
            $(document).ready(fetchSources);
        })(jQuery);
        </script>
        <style>
        .asap-modal { background: #fff; border: 1px solid #ccd0d4; padding: 24px; max-width: 400px; margin: 40px auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .asap-modal h2 { margin-top: 0; }
        #asap-source-modal { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.2); z-index: 9999; display: flex; align-items: center; justify-content: center; }
        </style>
        <?php
    }

    /**
     * Render Moderation Queue admin page (with AJAX-powered moderation table)
     */
    public function render_moderation() {
        ?>
        <div class="wrap">
            <h1>Moderation Queue</h1>
            <div id="asap-moderation-table"></div>
        </div>
        <script>
        (function($){
            function fetchQueue() {
                $('#asap-moderation-table').html('<p>Loading...</p>');
                $.ajax({
                    url: '/wp-json/asap/v1/crawler/moderation-queue',
                    method: 'GET',
                    success: function(data) {
                        var html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Title</th><th>Source</th><th>Actions</th></tr></thead><tbody>';
                        data.queue.forEach(function(item) {
                            html += '<tr>' +
                                '<td>' + item.id + '</td>' +
                                '<td>' + item.title + '</td>' +
                                '<td>' + (item.source_name || '') + '</td>' +
                                '<td>' +
                                    '<button class="button asap-approve" data-id="' + item.id + '">Approve</button> ' +
                                    '<button class="button asap-reject" data-id="' + item.id + '">Reject</button> ' +
                                    '<button class="button asap-view-log" data-id="' + item.id + '">View Log</button>' +
                                '</td>' +
                            '</tr>' +
                            '<tr class="asap-log-row" id="asap-log-row-' + item.id + '" style="display:none;"><td colspan="4"><div class="asap-log-content"></div></td></tr>';
                        });
                        html += '</tbody></table>';
                        $('#asap-moderation-table').html(html);
                    }
                });
            }
            fetchQueue();
            $(document).on('click', '.asap-view-log', function() {
                var id = $(this).data('id');
                var $logRow = $('#asap-log-row-' + id);
                if ($logRow.is(':visible')) {
                    $logRow.hide();
                    return;
                }
                $logRow.find('.asap-log-content').html('<p>Loading log...</p>');
                $logRow.show();
                $.ajax({
                    url: '/wp-json/asap/v1/crawler/moderation-log/' + id,
                    method: 'GET',
                    success: function(data) {
                        var log = data.log;
                        if (!log.length) {
                            $logRow.find('.asap-log-content').html('<em>No moderation history.</em>');
                            return;
                        }
                        var logHtml = '<table class="wp-list-table widefat"><thead><tr><th>Reviewer</th><th>Action</th><th>Decision</th><th>Reason</th><th>Timestamp</th></tr></thead><tbody>';
                        log.forEach(function(entry) {
                            var reviewer = entry.reviewer_name || entry.reviewer || '';
                            logHtml += '<tr>' +
                                '<td>' + reviewer + '</td>' +
                                '<td>' + entry.action + '</td>' +
                                '<td>' + entry.decision + '</td>' +
                                '<td>' + (entry.reason || '') + '</td>' +
                                '<td>' + entry.created_at + '</td>' +
                            '</tr>';
                        });
                        logHtml += '</tbody></table>';
                        $logRow.find('.asap-log-content').html(logHtml);
                    }
                });
            });
            // Approve/Reject handlers
            $(document).on('click', '.asap-approve', function(){
                var id = $(this).data('id');
                $.ajax({
                    url: '/wp-json/asap/v1/crawler/queue/approve/' + id,
                    method: 'POST',
                    success: function(){ fetchQueue(); },
                    error: function(){ alert('Failed to approve content.'); }
                });
            });
            $(document).on('click', '.asap-reject', function(){
                if (!confirm('Reject this content?')) return;
                var id = $(this).data('id');
                $.ajax({
                    url: '/wp-json/asap/v1/crawler/queue/reject/' + id,
                    method: 'POST',
                    success: function(){ fetchQueue(); },
                    error: function(){ alert('Failed to reject content.'); }
                });
            });
            // Initial load
            $(document).ready(fetchQueue);
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Render Crawler Analytics admin page (with AJAX-powered metrics tables)
     */
    public function render_analytics() {
        require_once plugin_dir_path(__FILE__) . 'views/analytics-dashboard.php';
    }

    /**
     * Render AI Settings page
     */
    public function render_ai_settings() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/ai-settings.php';
    }

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
     * Delete a content source (DELETE /asap/v1/crawler/sources/{id})
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_delete_source($request) {
        global $wpdb;
        $id = intval($request['id']);
        $table = $wpdb->prefix . 'asap_content_sources';
        // Check if source exists
        $source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$source) {
            return new \WP_Error('not_found', 'Source not found', ['status' => 404]);
        }
        $result = $wpdb->delete($table, ['id' => $id]);
        if ($result === false) {
            return new \WP_Error('db_delete_error', 'Failed to delete source', ['status' => 500]);
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
        $this->log_moderation_action($id, null, 'approve', get_current_user_id(), 'approved');
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
        $this->log_moderation_action($id, null, 'reject', get_current_user_id(), 'rejected');
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

    /**
     * Log a moderation action to the moderation log table
     *
     * @param int $content_id The ID of the moderated content (e.g., post ID)
     * @param int|null $source_id The ID of the content source (optional)
     * @param string $action The moderation action (approve/reject)
     * @param int|null $reviewer The user ID of the moderator (optional)
     * @param string $decision The decision (approved/rejected/flagged)
     * @param string|null $reason Optional reason or comment
     * @return bool True on success, false on failure
     */
    public function log_moderation_action($content_id, $source_id, $action, $reviewer, $decision, $reason = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_moderation_log';
        $result = $wpdb->insert($table, [
            'content_id' => $content_id,
            'source_id' => $source_id,
            'action' => $action,
            'reviewer' => $reviewer,
            'decision' => $decision,
            'reason' => $reason,
            'created_at' => current_time('mysql', 1)
        ]);
        return (bool) $result;
    }

    /**
     * Get moderation log/history for a content item (GET /asap/v1/crawler/moderation-log/{content_id})
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_get_moderation_log($request) {
        global $wpdb;
        $content_id = intval($request['content_id']);
        $table = $wpdb->prefix . 'asap_moderation_log';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE content_id = %d ORDER BY created_at DESC",
            $content_id
        ), ARRAY_A);
        // Fetch reviewer names
        foreach ($rows as &$row) {
            $reviewer_id = intval($row['reviewer']);
            $row['reviewer_name'] = '';
            if ($reviewer_id) {
                $user = get_userdata($reviewer_id);
                if ($user) {
                    $row['reviewer_name'] = $user->display_name ? $user->display_name : $user->user_email;
                }
            }
        }
        return rest_ensure_response(['log' => $rows]);
    }

    /**
     * Get moderation metrics/analytics (GET /asap/v1/crawler/moderation-metrics)
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_get_moderation_metrics($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_moderation_log';
        $users_table = $wpdb->users;
        // Total actions
        $total_actions = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        // Actions by type
        $actions_by_type = $wpdb->get_results("SELECT action, COUNT(*) as count FROM $table GROUP BY action", ARRAY_A);
        $actions_by_type_map = [];
        foreach ($actions_by_type as $row) {
            $actions_by_type_map[$row['action']] = (int)$row['count'];
        }
        // Actions by decision
        $actions_by_decision = $wpdb->get_results("SELECT decision, COUNT(*) as count FROM $table GROUP BY decision", ARRAY_A);
        $actions_by_decision_map = [];
        foreach ($actions_by_decision as $row) {
            $actions_by_decision_map[$row['decision']] = (int)$row['count'];
        }
        // Reviewer activity (all time)
        $reviewer_activity = $wpdb->get_results(
            "SELECT reviewer, COUNT(*) as count FROM $table WHERE reviewer IS NOT NULL AND reviewer != '' GROUP BY reviewer ORDER BY count DESC", ARRAY_A
        );
        $reviewer_activity_list = [];
        foreach ($reviewer_activity as $row) {
            $reviewer_id = intval($row['reviewer']);
            $reviewer_name = '';
            if ($reviewer_id) {
                $user = get_userdata($reviewer_id);
                if ($user) {
                    $reviewer_name = $user->display_name ? $user->display_name : $user->user_email;
                }
            }
            $reviewer_activity_list[] = [
                'reviewer_id' => $reviewer_id,
                'reviewer_name' => $reviewer_name,
                'count' => (int)$row['count']
            ];
        }
        // Recent activity (last 7 and 30 days)
        $now = current_time('mysql', 1);
        $last_7_days = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(%s, INTERVAL 7 DAY)", $now
        ));
        $last_30_days = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(%s, INTERVAL 30 DAY)", $now
        ));
        return rest_ensure_response([
            'total_actions' => $total_actions,
            'actions_by_type' => $actions_by_type_map,
            'actions_by_decision' => $actions_by_decision_map,
            'reviewer_activity' => $reviewer_activity_list,
            'recent_activity' => [
                'last_7_days' => $last_7_days,
                'last_30_days' => $last_30_days
            ]
        ]);
    }

    /**
     * Get approved content for the frontend selector (GET /asap/v1/crawler/content)
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_get_frontend_content($request) {
        global $wpdb;
        
        // Get parameters
        $page = intval($request->get_param('page') ?? 1);
        $per_page = intval($request->get_param('per_page') ?? 20);
        $search = sanitize_text_field($request->get_param('search') ?? '');
        $source_id = intval($request->get_param('source_id') ?? 0);
        $orderby = sanitize_text_field($request->get_param('orderby') ?? 'date');
        $order = strtoupper(sanitize_text_field($request->get_param('order') ?? 'DESC'));
        $status = sanitize_text_field($request->get_param('status') ?? 'approved');
        
        // Validate parameters
        if ($page < 1) $page = 1;
        if ($per_page < 1 || $per_page > 100) $per_page = 20;
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';
        
        // Validate orderby field
        $valid_orderby = ['id', 'title', 'date', 'publish_date', 'created_at'];
        if (!in_array($orderby, $valid_orderby)) $orderby = 'publish_date';
        
        // Map orderby field to database column
        $orderby_map = [
            'date' => 'publish_date',
            'id' => 'id',
            'title' => 'title',
            'publish_date' => 'publish_date',
            'created_at' => 'created_at'
        ];
        $orderby_column = $orderby_map[$orderby];
        
        // Build query
        $table = $wpdb->prefix . 'asap_moderation_queue';
        $query = "SELECT * FROM {$table} WHERE status = %s";
        $query_args = [$status];
        
        // Add search filter
        if (!empty($search)) {
            $query .= " AND (title LIKE %s OR content LIKE %s)";
            $search_pattern = '%' . $wpdb->esc_like($search) . '%';
            $query_args[] = $search_pattern;
            $query_args[] = $search_pattern;
        }
        
        // Add source filter
        if ($source_id > 0) {
            $query .= " AND source_id = %d";
            $query_args[] = $source_id;
        }
        
        // Count total items
        $count_query = str_replace('SELECT *', 'SELECT COUNT(*)', $query);
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
        
        // Add ordering and pagination
        $query .= " ORDER BY {$orderby_column} {$order} LIMIT %d OFFSET %d";
        $query_args[] = $per_page;
        $query_args[] = ($page - 1) * $per_page;
        
        // Execute query
        $items = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        // Calculate total pages
        $total_pages = ceil($total_items / $per_page);
        
        // Get sources for these items
        $source_ids = array_map(function($item) {
            return $item->source_id;
        }, $items);
        
        $sources = [];
        if (!empty($source_ids)) {
            $source_ids_str = implode(',', array_map('intval', array_unique($source_ids)));
            $sources_table = $wpdb->prefix . 'asap_content_sources';
            $sources_query = "SELECT id, name, url, type FROM {$sources_table} WHERE id IN ({$source_ids_str})";
            $sources = $wpdb->get_results($sources_query);
        }
        
        return rest_ensure_response([
            'items' => $items,
            'sources' => $sources,
            'total_items' => intval($total_items),
            'total_pages' => intval($total_pages),
            'current_page' => $page
        ]);
    }
} 