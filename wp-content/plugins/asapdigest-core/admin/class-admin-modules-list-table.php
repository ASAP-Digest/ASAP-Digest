<?php
/**
 * Admin Modules List Table
 * Custom WP_List_Table for displaying modules from wp_asap_modules custom table
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
 * Class Admin_Modules_List_Table
 * 
 * Displays modules from custom table in WordPress admin
 */
class Admin_Modules_List_Table extends \WP_List_Table {
    
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
            'singular' => 'module',
            'plural'   => 'modules',
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
            'cb'           => '<input type="checkbox" />',
            'title'        => __('Title', 'asapdigest-core'),
            'type'         => __('Type', 'asapdigest-core'),
            'status'       => __('Status', 'asapdigest-core'),
            'quality_score' => __('Quality Score', 'asapdigest-core'),
            'publish_date' => __('Publish Date', 'asapdigest-core'),
            'created_at'   => __('Created', 'asapdigest-core')
        ];
    }
    
    /**
     * Get sortable columns
     * 
     * @return array
     */
    public function get_sortable_columns() {
        return [
            'title'        => ['title', false],
            'type'         => ['type', false],
            'status'       => ['status', false],
            'quality_score' => ['quality_score', false],
            'publish_date' => ['publish_date', false],
            'created_at'   => ['created_at', true] // Default sort
        ];
    }
    
    /**
     * Get bulk actions
     * 
     * @return array
     */
    public function get_bulk_actions() {
        return [
            'delete'     => __('Delete', 'asapdigest-core'),
            'activate'   => __('Activate', 'asapdigest-core'),
            'deactivate' => __('Deactivate', 'asapdigest-core')
        ];
    }
    
    /**
     * Prepare table items
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Get search term
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Get filter values
        $type_filter = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : '';
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        
        // Get sort parameters
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'created_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        
        // Build query args
        $args = [
            'limit' => $per_page,
            'offset' => $offset,
            'order_by' => $orderby,
            'order' => $order
        ];
        
        if ($type_filter) {
            $args['type'] = $type_filter;
        }
        
        // Set status filter only if explicitly selected by admin
        if ($status_filter) {
            $args['status'] = $status_filter;
        }
        // Note: No default status filter - admin should see ALL modules
        
        // Get modules
        $modules = $this->table_manager->get_modules($args);
        
        // Get total count for pagination
        $total_items = $this->get_total_modules_count($search, $type_filter, $status_filter);
        
        // Set pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        // Set table data
        $this->items = $modules;
        
        // Set column headers
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns()
        ];
    }
    
    /**
     * Get total modules count
     * 
     * @param string $search
     * @param string $type_filter
     * @param string $status_filter
     * @return int
     */
    private function get_total_modules_count($search = '', $type_filter = '', $status_filter = '') {
        global $wpdb;
        
        $where_conditions = [];
        $params = [];
        
        if ($search) {
            $where_conditions[] = '(title LIKE %s OR content LIKE %s)';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if ($type_filter) {
            $where_conditions[] = 'type = %s';
            $params[] = $type_filter;
        }
        
        if ($status_filter) {
            $where_conditions[] = 'status = %s';
            $params[] = $status_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}asap_modules {$where_clause}";
        
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
            '<input type="checkbox" name="module[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Display title column
     * 
     * @param object $item
     * @return string
     */
    public function column_title($item) {
        $edit_url = admin_url('admin.php?page=asap-modules&action=edit&id=' . $item->id);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=asap-modules&action=delete&id=' . $item->id),
            'delete_module_' . $item->id
        );
        
        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>', $edit_url, __('Edit', 'asapdigest-core')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'%s\')">%s</a>', 
                $delete_url, 
                __('Are you sure you want to delete this module?', 'asapdigest-core'),
                __('Delete', 'asapdigest-core')
            )
        ];
        
        return sprintf(
            '<strong><a href="%s">%s</a></strong>%s',
            $edit_url,
            esc_html($item->title),
            $this->row_actions($actions)
        );
    }
    
    /**
     * Display type column
     * 
     * @param object $item
     * @return string
     */
    public function column_type($item) {
        $type_labels = [
            'news' => __('News', 'asapdigest-core'),
            'weather' => __('Weather', 'asapdigest-core'),
            'quote' => __('Quote', 'asapdigest-core'),
            'content' => __('Content', 'asapdigest-core')
        ];
        
        return isset($type_labels[$item->type]) ? $type_labels[$item->type] : ucfirst($item->type);
    }
    
    /**
     * Display status column
     * 
     * @param object $item
     * @return string
     */
    public function column_status($item) {
        $status_class = $item->status === 'active' ? 'status-active' : 'status-inactive';
        return sprintf(
            '<span class="%s">%s</span>',
            $status_class,
            ucfirst($item->status)
        );
    }
    
    /**
     * Display quality score column
     * 
     * @param object $item
     * @return string
     */
    public function column_quality_score($item) {
        if ($item->quality_score === null) {
            return '—';
        }
        
        $score = (float) $item->quality_score;
        $class = '';
        
        if ($score >= 0.8) {
            $class = 'quality-high';
        } elseif ($score >= 0.6) {
            $class = 'quality-medium';
        } else {
            $class = 'quality-low';
        }
        
        return sprintf(
            '<span class="%s">%.2f</span>',
            $class,
            $score
        );
    }
    
    /**
     * Display publish date column
     * 
     * @param object $item
     * @return string
     */
    public function column_publish_date($item) {
        if (!$item->publish_date) {
            return '—';
        }
        
        return date_i18n(get_option('date_format'), strtotime($item->publish_date));
    }
    
    /**
     * Display created at column
     * 
     * @param object $item
     * @return string
     */
    public function column_created_at($item) {
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at));
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
        
        $type_filter = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : '';
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        ?>
        <div class="alignleft actions">
            <select name="type">
                <option value=""><?php _e('All Types', 'asapdigest-core'); ?></option>
                <option value="news" <?php selected($type_filter, 'news'); ?>><?php _e('News', 'asapdigest-core'); ?></option>
                <option value="weather" <?php selected($type_filter, 'weather'); ?>><?php _e('Weather', 'asapdigest-core'); ?></option>
                <option value="quote" <?php selected($type_filter, 'quote'); ?>><?php _e('Quote', 'asapdigest-core'); ?></option>
                <option value="content" <?php selected($type_filter, 'content'); ?>><?php _e('Content', 'asapdigest-core'); ?></option>
            </select>
            
            <select name="status">
                <option value=""><?php _e('All Statuses', 'asapdigest-core'); ?></option>
                <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Active', 'asapdigest-core'); ?></option>
                <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php _e('Inactive', 'asapdigest-core'); ?></option>
                <option value="draft" <?php selected($status_filter, 'draft'); ?>><?php _e('Draft', 'asapdigest-core'); ?></option>
            </select>
            
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
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-modules')) {
            wp_die(__('Security check failed', 'asapdigest-core'));
        }
        
        $module_ids = isset($_REQUEST['module']) ? array_map('intval', $_REQUEST['module']) : [];
        
        if (empty($module_ids)) {
            return;
        }
        
        switch ($action) {
            case 'delete':
                foreach ($module_ids as $module_id) {
                    $this->table_manager->delete_module($module_id);
                }
                $message = sprintf(
                    _n('%d module deleted.', '%d modules deleted.', count($module_ids), 'asapdigest-core'),
                    count($module_ids)
                );
                break;
                
            case 'activate':
                foreach ($module_ids as $module_id) {
                    $this->table_manager->update_module($module_id, ['status' => 'active']);
                }
                $message = sprintf(
                    _n('%d module activated.', '%d modules activated.', count($module_ids), 'asapdigest-core'),
                    count($module_ids)
                );
                break;
                
            case 'deactivate':
                foreach ($module_ids as $module_id) {
                    $this->table_manager->update_module($module_id, ['status' => 'inactive']);
                }
                $message = sprintf(
                    _n('%d module deactivated.', '%d modules deactivated.', count($module_ids), 'asapdigest-core'),
                    count($module_ids)
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