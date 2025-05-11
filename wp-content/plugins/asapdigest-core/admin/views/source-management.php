<?php
/**
 * Admin View: Content Source Management
 *
 * @package ASAPDigest_Core
 * @since 2.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles
wp_enqueue_style('asap-source-management', plugin_dir_url(dirname(__FILE__)) . 'css/source-management.css', [], '2.3.0');
wp_enqueue_script('asap-source-management', plugin_dir_url(dirname(__FILE__)) . 'js/source-management.js', ['jquery'], '2.3.0', true);

// Localize script with AJAX URL and nonce
wp_localize_script('asap-source-management', 'asapDigestAdmin', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'sources_nonce' => wp_create_nonce('asap_digest_sources_nonce'),
]);

// Source types
$source_types = [
    'rss' => __('RSS Feed', 'asapdigest-core'),
    'api' => __('API Endpoint', 'asapdigest-core'),
    'scraper' => __('Web Scraper', 'asapdigest-core'),
    'webhook' => __('Webhook', 'asapdigest-core'),
];

// Frequencies
$frequencies = [
    'hourly' => __('Hourly', 'asapdigest-core'),
    'twicedaily' => __('Twice Daily', 'asapdigest-core'),
    'daily' => __('Daily', 'asapdigest-core'),
    'weekly' => __('Weekly', 'asapdigest-core'),
];

// Statuses
$statuses = [
    'active' => __('Active', 'asapdigest-core'),
    'paused' => __('Paused', 'asapdigest-core'),
    'inactive' => __('Inactive', 'asapdigest-core'),
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Content Source Management', 'asapdigest-core'); ?></h1>
    <a href="#" id="add-source-btn" class="page-title-action"><?php _e('Add New Source', 'asapdigest-core'); ?></a>
    <hr class="wp-header-end">

    <div id="form-messages"></div>

    <div class="source-management-wrap">
        <!-- Filters -->
        <div class="form-filters">
            <div class="search-box">
                <input type="search" id="source-search" placeholder="<?php esc_attr_e('Search sources...', 'asapdigest-core'); ?>">
            </div>
            
            <div>
                <select id="source-type-filter">
                    <option value=""><?php _e('All Types', 'asapdigest-core'); ?></option>
                    <?php foreach ($source_types as $type => $label) : ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <select id="source-status-filter">
                    <option value=""><?php _e('All Statuses', 'asapdigest-core'); ?></option>
                    <?php foreach ($statuses as $status => $label) : ?>
                        <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Sources Table -->
        <table class="wp-list-table widefat fixed striped source-list-table" id="source-list-table">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('ID', 'asapdigest-core'); ?></th>
                    <th><?php _e('Name', 'asapdigest-core'); ?></th>
                    <th style="width: 100px;"><?php _e('Type', 'asapdigest-core'); ?></th>
                    <th style="width: 80px;"><?php _e('Status', 'asapdigest-core'); ?></th>
                    <th style="width: 80px;"><?php _e('Health', 'asapdigest-core'); ?></th>
                    <th style="width: 80px;"><?php _e('Items', 'asapdigest-core'); ?></th>
                    <th style="width: 150px;"><?php _e('Last Run', 'asapdigest-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="no-items"><?php _e('Loading sources...', 'asapdigest-core'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"></span>
                <span class="pagination-links"></span>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Source Modal -->
<div id="source-modal" class="asap-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Add Content Source', 'asapdigest-core'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="source-form">
                <!-- Hidden source ID for edit mode -->
                <input type="hidden" name="source_id" value="">
                
                <div class="form-grid">
                    <!-- Basic Info Section -->
                    <div class="form-section">
                        <h4 class="mb-0"><?php _e('Basic Information', 'asapdigest-core'); ?></h4>
                        
                        <div class="form-field">
                            <label for="source-name"><?php _e('Source Name', 'asapdigest-core'); ?> <span class="required">*</span></label>
                            <input type="text" id="source-name" name="name" required>
                            <p class="description"><?php _e('A descriptive name for this content source', 'asapdigest-core'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="source-type"><?php _e('Source Type', 'asapdigest-core'); ?> <span class="required">*</span></label>
                            <select id="source-type" name="type" required>
                                <?php foreach ($source_types as $type => $label) : ?>
                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Schedule Section -->
                    <div class="form-section">
                        <h4 class="mb-0"><?php _e('Schedule & Status', 'asapdigest-core'); ?></h4>
                        
                        <div class="form-field">
                            <label for="source-frequency"><?php _e('Crawl Frequency', 'asapdigest-core'); ?></label>
                            <select id="source-frequency" name="frequency">
                                <?php foreach ($frequencies as $freq => $label) : ?>
                                    <option value="<?php echo esc_attr($freq); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('How often to check for new content', 'asapdigest-core'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="source-status"><?php _e('Status', 'asapdigest-core'); ?></label>
                            <select id="source-status" name="status">
                                <?php foreach ($statuses as $status => $label) : ?>
                                    <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- URL Section (Full Width) -->
                    <div class="form-section full-width">
                        <div class="form-field">
                            <label for="source-url"><?php _e('Source URL', 'asapdigest-core'); ?> <span class="required">*</span></label>
                            <input type="url" id="source-url" name="url" required>
                            <p class="description"><?php _e('URL of the content source (RSS feed, API endpoint, or webpage)', 'asapdigest-core'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Type-specific Configuration Fields -->
                    <div class="form-section full-width config-fields">
                        <!-- RSS Feed Config -->
                        <div class="config-section" data-type="rss">
                            <h4><?php _e('RSS Feed Configuration', 'asapdigest-core'); ?></h4>
                            
                            <div class="form-field">
                                <label>
                                    <input type="checkbox" name="configuration[use_autodiscovery]" value="1">
                                    <?php _e('Use Feed Autodiscovery', 'asapdigest-core'); ?>
                                </label>
                                <p class="description"><?php _e('Try to discover the feed URL if the provided URL is a regular webpage', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="rss-items-limit"><?php _e('Maximum Items', 'asapdigest-core'); ?></label>
                                <input type="number" id="rss-items-limit" name="configuration[items_limit]" min="1" max="100" value="20">
                                <p class="description"><?php _e('Maximum number of items to process per crawl', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label>
                                    <input type="checkbox" name="configuration[full_content]" value="1">
                                    <?php _e('Fetch Full Content', 'asapdigest-core'); ?>
                                </label>
                                <p class="description"><?php _e('Attempt to fetch full content if RSS feed only contains summaries', 'asapdigest-core'); ?></p>
                            </div>
                        </div>
                        
                        <!-- API Endpoint Config -->
                        <div class="config-section" data-type="api">
                            <h4><?php _e('API Configuration', 'asapdigest-core'); ?></h4>
                            
                            <div class="form-field">
                                <label for="api-method"><?php _e('Request Method', 'asapdigest-core'); ?></label>
                                <select id="api-method" name="configuration[method]">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="api-auth-type"><?php _e('Authentication Type', 'asapdigest-core'); ?></label>
                                <select id="api-auth-type" name="configuration[auth_type]">
                                    <option value="none"><?php _e('None', 'asapdigest-core'); ?></option>
                                    <option value="basic"><?php _e('Basic Auth', 'asapdigest-core'); ?></option>
                                    <option value="token"><?php _e('Bearer Token', 'asapdigest-core'); ?></option>
                                    <option value="api_key"><?php _e('API Key', 'asapdigest-core'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="api-auth-header"><?php _e('Auth Header Name', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-auth-header" name="configuration[auth_header]" placeholder="Authorization">
                            </div>
                            
                            <div class="form-field">
                                <label for="api-auth-value"><?php _e('Auth Value', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-auth-value" name="configuration[auth_value]" placeholder="Token xyz123">
                                <p class="description"><?php _e('For Basic Auth, use username:password format', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="api-items-path"><?php _e('Items JSON Path', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-items-path" name="configuration[items_path]" placeholder="data.items">
                                <p class="description"><?php _e('JSON path to the array of items (e.g., data.items, results)', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="api-title-key"><?php _e('Title Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-title-key" name="configuration[title_key]" placeholder="title">
                            </div>
                            
                            <div class="form-field">
                                <label for="api-content-key"><?php _e('Content Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-content-key" name="configuration[content_key]" placeholder="content">
                            </div>
                            
                            <div class="form-field">
                                <label for="api-url-key"><?php _e('URL Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-url-key" name="configuration[url_key]" placeholder="url">
                            </div>
                            
                            <div class="form-field">
                                <label for="api-date-key"><?php _e('Date Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-date-key" name="configuration[date_key]" placeholder="published_at">
                            </div>
                            
                            <div class="form-field">
                                <label for="api-id-key"><?php _e('ID Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="api-id-key" name="configuration[id_key]" placeholder="id">
                            </div>
                        </div>
                        
                        <!-- Web Scraper Config -->
                        <div class="config-section" data-type="scraper">
                            <h4><?php _e('Web Scraper Configuration', 'asapdigest-core'); ?></h4>
                            
                            <div class="form-field">
                                <label for="scraper-title-selector"><?php _e('Title Selector', 'asapdigest-core'); ?></label>
                                <input type="text" id="scraper-title-selector" name="configuration[title_selector]" placeholder="h1.entry-title">
                                <p class="description"><?php _e('CSS selector for the title element', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="scraper-content-selector"><?php _e('Content Selector', 'asapdigest-core'); ?></label>
                                <input type="text" id="scraper-content-selector" name="configuration[content_selector]" placeholder="div.entry-content">
                                <p class="description"><?php _e('CSS selector for the content element', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="scraper-date-selector"><?php _e('Date Selector', 'asapdigest-core'); ?></label>
                                <input type="text" id="scraper-date-selector" name="configuration[date_selector]" placeholder="time.published">
                                <p class="description"><?php _e('CSS selector for the publication date element', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label>
                                    <input type="checkbox" name="configuration[use_js_rendering]" value="1">
                                    <?php _e('Use JavaScript Rendering', 'asapdigest-core'); ?>
                                </label>
                                <p class="description"><?php _e('Enable for sites that load content with JavaScript (may be slower)', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="scraper-user-agent"><?php _e('User Agent', 'asapdigest-core'); ?></label>
                                <input type="text" id="scraper-user-agent" name="configuration[user_agent]" placeholder="Mozilla/5.0...">
                                <p class="description"><?php _e('Custom User-Agent header to use for requests', 'asapdigest-core'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Webhook Config -->
                        <div class="config-section" data-type="webhook">
                            <h4><?php _e('Webhook Configuration', 'asapdigest-core'); ?></h4>
                            
                            <div class="form-field">
                                <label for="webhook-secret"><?php _e('Webhook Secret', 'asapdigest-core'); ?></label>
                                <input type="text" id="webhook-secret" name="configuration[webhook_secret]">
                                <p class="description"><?php _e('Secret key to verify webhook signatures', 'asapdigest-core'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="webhook-title-key"><?php _e('Title Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="webhook-title-key" name="configuration[title_key]" placeholder="title">
                            </div>
                            
                            <div class="form-field">
                                <label for="webhook-content-key"><?php _e('Content Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="webhook-content-key" name="configuration[content_key]" placeholder="content">
                            </div>
                            
                            <div class="form-field">
                                <label for="webhook-url-key"><?php _e('URL Field', 'asapdigest-core'); ?></label>
                                <input type="text" id="webhook-url-key" name="configuration[url_key]" placeholder="url">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button modal-close"><?php _e('Cancel', 'asapdigest-core'); ?></button>
            <button type="submit" form="source-form" class="button button-primary"><?php _e('Save Source', 'asapdigest-core'); ?></button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-confirm-modal" class="asap-modal confirm-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Confirm Deletion', 'asapdigest-core'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p class="confirm-text"><?php _e('Are you sure you want to delete this source? This action cannot be undone.', 'asapdigest-core'); ?></p>
            <div class="action-buttons">
                <button type="button" class="button modal-close"><?php _e('Cancel', 'asapdigest-core'); ?></button>
                <button type="button" id="confirm-delete-btn" class="button button-primary button-large"><?php _e('Yes, Delete', 'asapdigest-core'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
// Handle modal close buttons
jQuery(document).ready(function($) {
    // Close modal when clicking X or Cancel
    $('.modal-close').on('click', function() {
        $(this).closest('.asap-modal').removeClass('open');
    });
    
    // Close modal when clicking outside
    $('.asap-modal').on('click', function(e) {
        if ($(e.target).hasClass('asap-modal')) {
            $(this).removeClass('open');
        }
    });
});
</script> 