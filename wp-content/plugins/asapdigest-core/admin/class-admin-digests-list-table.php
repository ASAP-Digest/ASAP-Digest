<?php
/**
 * Admin Digests List Table
 * Custom WP_List_Table for displaying digests from wp_asap_digests custom table
 * 
 * @package ASAPDigest
 * @since 1.0.0
 */

namespace ASAPDigest\Admin;

if (!defined('ABSPATH')) {
    exit;
}

// Load WP_List_Table if not already loaded
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Admin_Digests_List_Table
 * 
 * Displays digests from custom table in WordPress admin
 */
class Admin_Digests_List_Table extends \WP_List_Table {
    
    /**
     * Custom table manager instance
     * @var \ASAPDigest\Core\Custom_Table_Manager
     */
    private $table_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'digest',
            'plural'   => 'digests',
            'ajax'     => false
        ]);
        
        $this->table_manager = new \ASAPDigest\Core\Custom_Table_Manager();
    }
    
    /**
     * Get table columns
     * 
     * @return array
     */
    public function get_columns() {
        return [
            'cb'                 => '<input type="checkbox" />',
            'id'                 => __('ID', 'asapdigest-core'),
            'user'               => __('User', 'asapdigest-core'),
            'status'             => __('Status', 'asapdigest-core'),
            'layout_template_id' => __('Layout Template', 'asapdigest-core'),
            'life_moment'        => __('Life Moment', 'asapdigest-core'),
            'is_saved'           => __('Saved', 'asapdigest-core'),
            'modules_count'      => __('Modules', 'asapdigest-core'),
            'created_at'         => __('Created', 'asapdigest-core')
        ];
    }
    
    /**
     * Get sortable columns
     * 
     * @return array
     */
    public function get_sortable_columns() {
        return [
            'id'         => ['id', false],
            'user'       => ['user_id', false],
            'status'     => ['status', false],
            'is_saved'   => ['is_saved', false],
            'created_at' => ['created_at', true] // Default sort
        ];
    }
    
    /**
     * Get bulk actions
     * 
     * @return array
     */
    public function get_bulk_actions() {
        return [
            'delete'    => __('Delete', 'asapdigest-core'),
            'publish'   => __('Publish', 'asapdigest-core'),
            'draft'     => __('Move to Draft', 'asapdigest-core')
        ];
    }
    
    /**
     * Prepare table items
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Get filter values
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        $user_filter = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        
        // Get sort parameters
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'created_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        
        // Get digests with pagination
        $digests = $this->get_digests_with_details($per_page, $offset, $status_filter, $user_filter, $orderby, $order);
        
        // Get total count for pagination
        $total_items = $this->get_total_digests_count($status_filter, $user_filter);
        
        // Set pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        // Set table data
        $this->items = $digests;
        
        // Set column headers
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns()
        ];
    }
    
    /**
     * Get digests with additional details
     * 
     * @param int $limit
     * @param int $offset
     * @param string $status_filter
     * @param int $user_filter
     * @param string $orderby
     * @param string $order
     * @return array
     */
    private function get_digests_with_details($limit, $offset, $status_filter = '', $user_filter = 0, $orderby = 'created_at', $order = 'DESC') {
        global $wpdb;
        
        $where_conditions = ['1=1'];
        $params = [];
        
        if ($status_filter) {
            $where_conditions[] = 'd.status = %s';
            $params[] = $status_filter;
        }
        
        if ($user_filter) {
            $where_conditions[] = 'd.user_id = %d';
            $params[] = $user_filter;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT d.*, 
                       u.display_name as user_display_name,
                       u.user_email,
                       COUNT(dmp.id) as modules_count
                FROM {$wpdb->prefix}asap_digests d
                LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}asap_digest_module_placements dmp ON d.id = dmp.digest_id
                WHERE {$where_clause}
                GROUP BY d.id
                ORDER BY d.{$orderby} {$order}
                LIMIT %d OFFSET %d";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get total digests count
     * 
     * @param string $status_filter
     * @param int $user_filter
     * @return int
     */
    private function get_total_digests_count($status_filter = '', $user_filter = 0) {
        global $wpdb;
        
        $where_conditions = ['1=1'];
        $params = [];
        
        if ($status_filter) {
            $where_conditions[] = 'status = %s';
            $params[] = $status_filter;
        }
        
        if ($user_filter) {
            $where_conditions[] = 'user_id = %d';
            $params[] = $user_filter;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}asap_digests WHERE {$where_clause}";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Display checkbox column
     * 
     * @param object $item
     * @return string
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="digest[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Display ID column
     * 
     * @param object $item
     * @return string
     */
    public function column_id($item) {
        $edit_url = admin_url('admin.php?page=asap-digests&action=edit&id=' . $item->id);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=asap-digests&action=delete&id=' . $item->id),
            'delete_digest_' . $item->id
        );
        
        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>', $edit_url, __('Edit', 'asapdigest-core')),
            'view' => sprintf('<a href="%s" target="_blank">%s</a>', $edit_url, __('View', 'asapdigest-core')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'%s\')">%s</a>', 
                $delete_url, 
                __('Are you sure you want to delete this digest?', 'asapdigest-core'),
                __('Delete', 'asapdigest-core')
            )
        ];
        
        return sprintf(
            '<strong><a href="%s">#%d</a></strong>%s',
            $edit_url,
            $item->id,
            $this->row_actions($actions)
        );
    }
    
    /**
     * Display user column
     * 
     * @param object $item
     * @return string
     */
    public function column_user($item) {
        if ($item->user_display_name) {
            return sprintf(
                '%s<br><small>%s</small>',
                esc_html($item->user_display_name),
                esc_html($item->user_email)
            );
        }
        
        return sprintf(__('User ID: %d', 'asapdigest-core'), $item->user_id);
    }
    
    /**
     * Display status column
     * 
     * @param object $item
     * @return string
     */
    public function column_status($item) {
        $status_labels = [
            'draft' => __('Draft', 'asapdigest-core'),
            'published' => __('Published', 'asapdigest-core'),
            'archived' => __('Archived', 'asapdigest-core')
        ];
        
        $status_classes = [
            'draft' => 'status-draft',
            'published' => 'status-published',
            'archived' => 'status-archived'
        ];
        
        $status = $item->status;
        $label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
        $class = isset($status_classes[$status]) ? $status_classes[$status] : 'status-default';
        
        return sprintf(
            '<span class="%s">%s</span>',
            $class,
            $label
        );
    }
    
    /**
     * Display layout template column
     * 
     * @param object $item
     * @return string
     */
    public function column_layout_template_id($item) {
        if (!$item->layout_template_id) {
            return '—';
        }
        
        return esc_html($item->layout_template_id);
    }
    
    /**
     * Display life moment column
     * 
     * @param object $item
     * @return string
     */
    public function column_life_moment($item) {
        if (!$item->life_moment) {
            return '—';
        }
        
        return esc_html($item->life_moment);
    }
    
    /**
     * Display saved column
     * 
     * @param object $item
     * @return string
     */
    public function column_is_saved($item) {
        if ($item->is_saved) {
            return '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>';
        }
        
        return '<span class="dashicons dashicons-minus" style="color: #ddd;"></span>';
    }
    
    /**
     * Display modules count column
     * 
     * @param object $item
     * @return string
     */
    public function column_modules_count($item) {
        $count = (int) $item->modules_count;
        
        if ($count === 0) {
            return '<span style="color: #999;">0</span>';
        }
        
        return sprintf(
            '<a href="%s">%d</a>',
            admin_url('admin.php?page=asap-digests&action=edit&id=' . $item->id . '#modules'),
            $count
        );
    }
    
    /**
     * Display created at column
     * 
     * @param object $item
     * @return string
     */
    public function column_created_at($item) {
        $date = date_i18n(get_option('date_format'), strtotime($item->created_at));
        $time = date_i18n(get_option('time_format'), strtotime($item->created_at));
        
        return sprintf(
            '%s<br><small>%s</small>',
            $date,
            $time
        );
    }
    
    /**
     * Default column display
     * 
     * @param object $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name) {
        return isset($item->$column_name) ? esc_html($item->$column_name) : '—';
    }
    
    /**
     * Display filters above the table
     */
    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        $user_filter = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        ?>
        <div class="alignleft actions">
            <select name="status">
                <option value=""><?php _e('All Statuses', 'asapdigest-core'); ?></option>
                <option value="draft" <?php selected($status_filter, 'draft'); ?>><?php _e('Draft', 'asapdigest-core'); ?></option>
                <option value="published" <?php selected($status_filter, 'published'); ?>><?php _e('Published', 'asapdigest-core'); ?></option>
                <option value="archived" <?php selected($status_filter, 'archived'); ?>><?php _e('Archived', 'asapdigest-core'); ?></option>
            </select>
            
            <?php
            // User dropdown for administrators
            if (current_user_can('manage_options')) {
                $users = get_users(['fields' => ['ID', 'display_name']]);
                ?>
                <select name="user_id">
                    <option value=""><?php _e('All Users', 'asapdigest-core'); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo $user->ID; ?>" <?php selected($user_filter, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
            }
            ?>
            
            <?php submit_button(__('Filter', 'asapdigest-core'), 'secondary', 'filter_action', false); ?>
        </div>
        <?php
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        $action = $this->current_action();
        
        if (!$action) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-digests')) {
            wp_die(__('Security check failed', 'asapdigest-core'));
        }
        
        $digest_ids = isset($_REQUEST['digest']) ? array_map('intval', $_REQUEST['digest']) : [];
        
        if (empty($digest_ids)) {
            return;
        }
        
        switch ($action) {
            case 'delete':
                foreach ($digest_ids as $digest_id) {
                    $this->table_manager->delete_digest($digest_id);
                }
                $message = sprintf(
                    _n('%d digest deleted.', '%d digests deleted.', count($digest_ids), 'asapdigest-core'),
                    count($digest_ids)
                );
                break;
                
            case 'publish':
                foreach ($digest_ids as $digest_id) {
                    $this->table_manager->update_digest_status($digest_id, 'published');
                }
                $message = sprintf(
                    _n('%d digest published.', '%d digests published.', count($digest_ids), 'asapdigest-core'),
                    count($digest_ids)
                );
                break;
                
            case 'draft':
                foreach ($digest_ids as $digest_id) {
                    $this->table_manager->update_digest_status($digest_id, 'draft');
                }
                $message = sprintf(
                    _n('%d digest moved to draft.', '%d digests moved to draft.', count($digest_ids), 'asapdigest-core'),
                    count($digest_ids)
                );
                break;
        }
        
        if (isset($message)) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }
} 