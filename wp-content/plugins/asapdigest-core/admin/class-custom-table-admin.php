<?php
/**
 * Custom Table Admin Controller
 * Handles admin pages for custom tables (modules and digests)
 * 
 * @package ASAPDigest
 * @since 1.0.0
 */

namespace ASAPDigest\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Custom_Table_Admin
 * 
 * Manages admin interfaces for custom tables
 */
class Custom_Table_Admin {
    
    /**
     * Custom table manager instance
     * @var \ASAPDigest\Core\Custom_Table_Manager
     */
    private $table_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->table_manager = new \ASAPDigest\Core\Custom_Table_Manager();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_init', [$this, 'handle_actions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Handle admin actions (edit, delete, etc.)
     */
    public function handle_actions() {
        if (!isset($_GET['page'])) {
            return;
        }
        
        $page = sanitize_text_field($_GET['page']);
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if (!in_array($page, ['asap-modules', 'asap-digests'])) {
            return;
        }
        
        switch ($action) {
            case 'delete':
                $this->handle_delete_action($page);
                break;
            case 'edit':
                // Edit actions are handled in the render methods
                break;
        }
    }
    
    /**
     * Handle delete actions
     * 
     * @param string $page
     */
    private function handle_delete_action($page) {
        if ($page === 'asap-modules') {
            $this->handle_module_delete();
        } elseif ($page === 'asap-digests') {
            $this->handle_digest_delete();
        }
    }
    
    /**
     * Handle module deletion
     */
    private function handle_module_delete() {
        if (!isset($_GET['id'])) {
            return;
        }
        
        $module_id = intval($_GET['id']);
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
        
        if (!wp_verify_nonce($nonce, 'delete_module_' . $module_id)) {
            wp_die(__('Security check failed', 'asapdigest-core'));
        }
        
        if (!current_user_can('delete_posts')) {
            wp_die(__('You do not have permission to delete modules', 'asapdigest-core'));
        }
        
        $result = $this->table_manager->delete_module($module_id);
        
        if ($result) {
            $redirect_url = add_query_arg([
                'page' => 'asap-modules',
                'deleted' => '1'
            ], admin_url('admin.php'));
        } else {
            $redirect_url = add_query_arg([
                'page' => 'asap-modules',
                'error' => 'delete_failed'
            ], admin_url('admin.php'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Handle digest deletion
     */
    private function handle_digest_delete() {
        if (!isset($_GET['id'])) {
            return;
        }
        
        $digest_id = intval($_GET['id']);
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
        
        if (!wp_verify_nonce($nonce, 'delete_digest_' . $digest_id)) {
            wp_die(__('Security check failed', 'asapdigest-core'));
        }
        
        if (!current_user_can('delete_posts')) {
            wp_die(__('You do not have permission to delete digests', 'asapdigest-core'));
        }
        
        $result = $this->table_manager->delete_digest($digest_id);
        
        if ($result) {
            $redirect_url = add_query_arg([
                'page' => 'asap-digests',
                'deleted' => '1'
            ], admin_url('admin.php'));
        } else {
            $redirect_url = add_query_arg([
                'page' => 'asap-digests',
                'error' => 'delete_failed'
            ], admin_url('admin.php'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our custom table admin pages
        if (!isset($_GET['page']) || !in_array($_GET['page'], ['asap-modules', 'asap-digests'])) {
            return;
        }
        
        // Enqueue WordPress admin styles for list tables
        wp_enqueue_style('wp-admin');
        wp_enqueue_style('list-tables');
        
        // Custom styles for our admin pages
        wp_add_inline_style('wp-admin', '
            .status-active { color: #46b450; font-weight: bold; }
            .status-inactive { color: #dc3232; }
            .status-draft { color: #ffb900; }
            .status-published { color: #46b450; font-weight: bold; }
            .status-archived { color: #999; }
            .quality-high { color: #46b450; font-weight: bold; }
            .quality-medium { color: #ffb900; }
            .quality-low { color: #dc3232; }
            .wrap .page-title-action { margin-left: 8px; }
        ');
    }
    
    /**
     * Render modules admin page
     */
    public function render_modules_page() {
        // Security check
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'asapdigest-core'));
        }
        
        // Handle form submissions and actions
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'edit' || $action === 'add') {
            $this->render_module_edit_page();
            return;
        }
        
        // Create list table instance
        $list_table = new \ASAPDigest\Admin\Admin_Modules_List_Table();
        
        // Process bulk actions
        $list_table->process_bulk_action();
        
        // Prepare items
        $list_table->prepare_items();
        
        // Display admin notices
        $this->display_admin_notices();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php _e('Modules', 'asapdigest-core'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=asap-modules&action=add'); ?>" class="page-title-action">
                <?php _e('Add New Module', 'asapdigest-core'); ?>
            </a>
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="page" value="asap-modules" />
                <?php $list_table->search_box(__('Search Modules', 'asapdigest-core'), 'module'); ?>
            </form>
            
            <form method="post">
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render digests admin page
     */
    public function render_digests_page() {
        // Security check
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'asapdigest-core'));
        }
        
        // Handle form submissions and actions
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'edit' || $action === 'add') {
            $this->render_digest_edit_page();
            return;
        }
        
        // Create list table instance
        $list_table = new \ASAPDigest\Admin\Admin_Digests_List_Table();
        
        // Process bulk actions
        $list_table->process_bulk_action();
        
        // Prepare items
        $list_table->prepare_items();
        
        // Display admin notices
        $this->display_admin_notices();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php _e('Digests', 'asapdigest-core'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=asap-digests&action=add'); ?>" class="page-title-action">
                <?php _e('Add New Digest', 'asapdigest-core'); ?>
            </a>
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="page" value="asap-digests" />
                <?php $list_table->search_box(__('Search Digests', 'asapdigest-core'), 'digest'); ?>
            </form>
            
            <form method="post">
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render module edit page
     */
    private function render_module_edit_page() {
        $module_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $module = null;
        
        if ($module_id) {
            $module = $this->table_manager->get_module($module_id);
            if (!$module) {
                wp_die(__('Module not found', 'asapdigest-core'));
            }
        }
        
        // Handle form submission
        if ($_POST && isset($_POST['save_module'])) {
            $this->handle_module_save($module_id);
        }
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo $module_id ? __('Edit Module', 'asapdigest-core') : __('Add New Module', 'asapdigest-core'); ?>
            </h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_module_' . $module_id, 'module_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="module_title"><?php _e('Title', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="module_title" name="module_title" 
                                   value="<?php echo $module ? esc_attr($module->title) : ''; ?>" 
                                   class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="module_type"><?php _e('Type', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <select id="module_type" name="module_type" required>
                                <option value=""><?php _e('Select Type', 'asapdigest-core'); ?></option>
                                <option value="news" <?php selected($module ? $module->type : '', 'news'); ?>><?php _e('News', 'asapdigest-core'); ?></option>
                                <option value="weather" <?php selected($module ? $module->type : '', 'weather'); ?>><?php _e('Weather', 'asapdigest-core'); ?></option>
                                <option value="quote" <?php selected($module ? $module->type : '', 'quote'); ?>><?php _e('Quote', 'asapdigest-core'); ?></option>
                                <option value="content" <?php selected($module ? $module->type : '', 'content'); ?>><?php _e('Content', 'asapdigest-core'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="module_content"><?php _e('Content', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <textarea id="module_content" name="module_content" rows="10" class="large-text"><?php echo $module ? esc_textarea($module->content) : ''; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="module_status"><?php _e('Status', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <select id="module_status" name="module_status">
                                <option value="active" <?php selected($module ? $module->status : 'active', 'active'); ?>><?php _e('Active', 'asapdigest-core'); ?></option>
                                <option value="inactive" <?php selected($module ? $module->status : '', 'inactive'); ?>><?php _e('Inactive', 'asapdigest-core'); ?></option>
                                <option value="draft" <?php selected($module ? $module->status : '', 'draft'); ?>><?php _e('Draft', 'asapdigest-core'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="module_source_url"><?php _e('Source URL', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="module_source_url" name="module_source_url" 
                                   value="<?php echo $module ? esc_attr($module->source_url) : ''; ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="module_quality_score"><?php _e('Quality Score', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="module_quality_score" name="module_quality_score" 
                                   value="<?php echo $module ? esc_attr($module->quality_score) : ''; ?>" 
                                   step="0.01" min="0" max="1" class="small-text" />
                            <p class="description"><?php _e('Quality score between 0 and 1', 'asapdigest-core'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button($module_id ? __('Update Module', 'asapdigest-core') : __('Create Module', 'asapdigest-core'), 'primary', 'save_module'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render digest edit page
     */
    private function render_digest_edit_page() {
        $digest_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $digest = null;
        
        if ($digest_id) {
            $digest = $this->table_manager->get_digest($digest_id);
            if (!$digest) {
                wp_die(__('Digest not found', 'asapdigest-core'));
            }
        }
        
        // Handle form submission
        if ($_POST && isset($_POST['save_digest'])) {
            $this->handle_digest_save($digest_id);
        }
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo $digest_id ? __('Edit Digest', 'asapdigest-core') : __('Add New Digest', 'asapdigest-core'); ?>
            </h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_digest_' . $digest_id, 'digest_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="digest_status"><?php _e('Status', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <select id="digest_status" name="digest_status">
                                <option value="draft" <?php selected($digest ? $digest->status : 'draft', 'draft'); ?>><?php _e('Draft', 'asapdigest-core'); ?></option>
                                <option value="published" <?php selected($digest ? $digest->status : '', 'published'); ?>><?php _e('Published', 'asapdigest-core'); ?></option>
                                <option value="archived" <?php selected($digest ? $digest->status : '', 'archived'); ?>><?php _e('Archived', 'asapdigest-core'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="digest_layout_template_id"><?php _e('Layout Template', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="digest_layout_template_id" name="digest_layout_template_id" 
                                   value="<?php echo $digest ? esc_attr($digest->layout_template_id) : ''; ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="digest_life_moment"><?php _e('Life Moment', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="digest_life_moment" name="digest_life_moment" 
                                   value="<?php echo $digest ? esc_attr($digest->life_moment) : ''; ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="digest_is_saved"><?php _e('Saved', 'asapdigest-core'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="digest_is_saved" name="digest_is_saved" value="1" 
                                   <?php checked($digest ? $digest->is_saved : false, 1); ?> />
                            <label for="digest_is_saved"><?php _e('Mark as saved', 'asapdigest-core'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <?php if ($digest_id): ?>
                <h2><?php _e('Modules', 'asapdigest-core'); ?></h2>
                <div id="modules">
                    <?php $this->render_digest_modules($digest_id); ?>
                </div>
                <?php endif; ?>
                
                <?php submit_button($digest_id ? __('Update Digest', 'asapdigest-core') : __('Create Digest', 'asapdigest-core'), 'primary', 'save_digest'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render digest modules section
     * 
     * @param int $digest_id
     */
    private function render_digest_modules($digest_id) {
        $placements = $this->table_manager->get_digest_module_placements($digest_id);
        
        if (empty($placements)) {
            echo '<p>' . __('No modules assigned to this digest.', 'asapdigest-core') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Module', 'asapdigest-core') . '</th>';
        echo '<th>' . __('Type', 'asapdigest-core') . '</th>';
        echo '<th>' . __('Position', 'asapdigest-core') . '</th>';
        echo '<th>' . __('Size', 'asapdigest-core') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($placements as $placement) {
            echo '<tr>';
            echo '<td>' . esc_html($placement->module_title ?: 'Unknown Module') . '</td>';
            echo '<td>' . esc_html($placement->module_type ?: '—') . '</td>';
            echo '<td>' . sprintf('(%d, %d)', $placement->grid_x, $placement->grid_y) . '</td>';
            echo '<td>' . sprintf('%d × %d', $placement->grid_width, $placement->grid_height) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Handle module save
     * 
     * @param int $module_id
     */
    private function handle_module_save($module_id) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['module_nonce'], 'save_module_' . $module_id)) {
            wp_die(__('Security check failed', 'asapdigest-core'));
        }
        
        // Prepare data
        $data = [
            'title' => sanitize_text_field($_POST['module_title']),
            'type' => sanitize_text_field($_POST['module_type']),
            'content' => wp_kses_post($_POST['module_content']),
            'status' => sanitize_text_field($_POST['module_status']),
            'source_url' => esc_url_raw($_POST['module_source_url']),
            'quality_score' => !empty($_POST['module_quality_score']) ? floatval($_POST['module_quality_score']) : null
        ];
        
        if ($module_id) {
            // Update existing module
            $result = $this->table_manager->update_module($module_id, $data);
            $message = $result ? 'updated' : 'update_failed';
        } else {
            // Create new module
            $result = $this->table_manager->create_module($data);
            $message = $result ? 'created' : 'create_failed';
            if ($result) {
                $module_id = $result;
            }
        }
        
        // Redirect with message
        $redirect_url = add_query_arg([
            'page' => 'asap-modules',
            'action' => 'edit',
            'id' => $module_id,
            'message' => $message
        ], admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Handle digest save
     * 
     * @param int $digest_id
     */
    private function handle_digest_save($digest_id) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['digest_nonce'], 'save_digest_' . $digest_id)) {
            wp_die(__('Security check failed', 'asapdigest-core'));
        }
        
        // Prepare data
        $data = [
            'status' => sanitize_text_field($_POST['digest_status']),
            'layout_template_id' => sanitize_text_field($_POST['digest_layout_template_id']),
            'life_moment' => sanitize_text_field($_POST['digest_life_moment']),
            'is_saved' => isset($_POST['digest_is_saved']) ? 1 : 0
        ];
        
        if ($digest_id) {
            // Update existing digest
            $result = $this->table_manager->update_digest($digest_id, $data);
            $message = $result ? 'updated' : 'update_failed';
        } else {
            // Create new digest
            $data['user_id'] = get_current_user_id();
            $result = $this->table_manager->create_digest($data);
            $message = $result ? 'created' : 'create_failed';
            if ($result) {
                $digest_id = $result;
            }
        }
        
        // Redirect with message
        $redirect_url = add_query_arg([
            'page' => 'asap-digests',
            'action' => 'edit',
            'id' => $digest_id,
            'message' => $message
        ], admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Display admin notices
     */
    private function display_admin_notices() {
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Item deleted successfully.', 'asapdigest-core') . '</p></div>';
        }
        
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $messages = [
                'created' => __('Item created successfully.', 'asapdigest-core'),
                'updated' => __('Item updated successfully.', 'asapdigest-core'),
                'create_failed' => __('Failed to create item.', 'asapdigest-core'),
                'update_failed' => __('Failed to update item.', 'asapdigest-core')
            ];
            
            if (isset($messages[$message])) {
                $class = in_array($message, ['created', 'updated']) ? 'notice-success' : 'notice-error';
                echo '<div class="notice ' . $class . ' is-dismissible"><p>' . $messages[$message] . '</p></div>';
            }
        }
        
        if (isset($_GET['error'])) {
            $error = sanitize_text_field($_GET['error']);
            $errors = [
                'delete_failed' => __('Failed to delete item.', 'asapdigest-core')
            ];
            
            if (isset($errors[$error])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . $errors[$error] . '</p></div>';
            }
        }
    }
} 