<?php
/**
 * Content Library Admin View
 *
 * Displays and manages ingested content in a library view with filtering,
 * sorting, and bulk actions.
 *
 * @package ASAP_Digest
 * @subpackage Admin
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load content processing components
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/content-processing/bootstrap.php';

// Initialize processor for content management
$processor = asap_digest_get_content_processor();

// Handle bulk actions
if (isset($_POST['action']) && check_admin_referer('asap_content_library_actions')) {
    $selected_ids = isset($_POST['content_ids']) ? array_map('intval', $_POST['content_ids']) : [];
    
    if (!empty($selected_ids)) {
        // Handle bulk delete
        if ($_POST['action'] === 'delete') {
            $deleted_count = 0;
            
            foreach ($selected_ids as $content_id) {
                if ($processor->delete($content_id)) {
                    $deleted_count++;
                }
            }
            
            if ($deleted_count > 0) {
                echo '<div class="notice notice-success"><p>' . sprintf(
                    _n(
                        '%d item deleted successfully.',
                        '%d items deleted successfully.',
                        $deleted_count,
                        'asap-digest'
                    ),
                    $deleted_count
                ) . '</p></div>';
            }
        }
        
        // Handle bulk status change
        if ($_POST['action'] === 'change_status' && !empty($_POST['new_status'])) {
            $status = sanitize_text_field($_POST['new_status']);
            $updated_count = 0;
            
            global $wpdb;
            $table = $wpdb->prefix . 'asap_ingested_content';
            
            foreach ($selected_ids as $content_id) {
                $result = $wpdb->update(
                    $table,
                    ['status' => $status],
                    ['id' => $content_id]
                );
                
                if ($result !== false) {
                    $updated_count++;
                }
            }
            
            if ($updated_count > 0) {
                echo '<div class="notice notice-success"><p>' . sprintf(
                    _n(
                        'Status changed to "%s" for %d item.',
                        'Status changed to "%s" for %d items.',
                        $updated_count,
                        'asap-digest'
                    ),
                    $status,
                    $updated_count
                ) . '</p></div>';
            }
        }
    }
}

// Get filters from query string
$current_filters = [
    'type' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '',
    'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
    'min_quality' => isset($_GET['min_quality']) ? intval($_GET['min_quality']) : 0,
    'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
    'page' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1,
    'per_page' => isset($_GET['per_page']) ? min(100, intval($_GET['per_page'])) : 20,
    'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at',
    'order' => isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC',
];

// Build query
global $wpdb;
$table = $wpdb->prefix . 'asap_ingested_content';
$index_table = $wpdb->prefix . 'asap_content_index';

$where = [];
$params = [];

// Filter by type
if (!empty($current_filters['type'])) {
    $where[] = 'type = %s';
    $params[] = $current_filters['type'];
}

// Filter by status
if (!empty($current_filters['status'])) {
    $where[] = 'status = %s';
    $params[] = $current_filters['status'];
}

// Filter by minimum quality score
if ($current_filters['min_quality'] > 0) {
    $where[] = 'quality_score >= %d';
    $params[] = $current_filters['min_quality'];
}

// Search in title and content
if (!empty($current_filters['search'])) {
    $where[] = '(title LIKE %s OR content LIKE %s)';
    $search_term = '%' . $wpdb->esc_like($current_filters['search']) . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

// Build WHERE clause
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total items count for pagination
$count_query = "SELECT COUNT(*) FROM $table $where_sql";
if (!empty($params)) {
    $count_query = $wpdb->prepare($count_query, $params);
}
$total_items = $wpdb->get_var($count_query);

// Set up pagination
$total_pages = ceil($total_items / $current_filters['per_page']);
$offset = ($current_filters['page'] - 1) * $current_filters['per_page'];

// Order by column with proper escaping
$allowed_columns = ['id', 'title', 'type', 'status', 'quality_score', 'publish_date', 'created_at'];
$orderby = in_array($current_filters['orderby'], $allowed_columns) ? $current_filters['orderby'] : 'created_at';
$order = $current_filters['order'] === 'ASC' ? 'ASC' : 'DESC';

// Get items
$query = "SELECT * FROM $table $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
$params[] = $current_filters['per_page'];
$params[] = $offset;

$query = $wpdb->prepare($query, $params);
$items = $wpdb->get_results($query, ARRAY_A);

// Get available content types for filter
$types_query = "SELECT DISTINCT type FROM $table ORDER BY type";
$available_types = $wpdb->get_col($types_query);

// Get available statuses for filter
$statuses_query = "SELECT DISTINCT status FROM $table ORDER BY status";
$available_statuses = $wpdb->get_col($statuses_query);

// Output the admin page
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Content Library</h1>
    
    <!-- Search Form -->
    <form method="get" action="">
        <input type="hidden" name="page" value="asap-content-library">
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="type">
                    <option value=""><?php _e('All Types', 'asap-digest'); ?></option>
                    <?php foreach ($available_types as $type) : ?>
                        <option value="<?php echo esc_attr($type); ?>" <?php selected($current_filters['type'], $type); ?>>
                            <?php echo esc_html(ucfirst($type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'asap-digest'); ?></option>
                    <?php foreach ($available_statuses as $status) : ?>
                        <option value="<?php echo esc_attr($status); ?>" <?php selected($current_filters['status'], $status); ?>>
                            <?php echo esc_html(ucfirst($status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="min_quality">
                    <option value="0"><?php _e('Any Quality', 'asap-digest'); ?></option>
                    <option value="80" <?php selected($current_filters['min_quality'], 80); ?>><?php _e('High Quality (80+)', 'asap-digest'); ?></option>
                    <option value="60" <?php selected($current_filters['min_quality'], 60); ?>><?php _e('Good Quality (60+)', 'asap-digest'); ?></option>
                    <option value="40" <?php selected($current_filters['min_quality'], 40); ?>><?php _e('Average Quality (40+)', 'asap-digest'); ?></option>
                </select>
                
                <input type="text" name="search" value="<?php echo esc_attr($current_filters['search']); ?>" placeholder="<?php _e('Search...', 'asap-digest'); ?>">
                
                <input type="submit" class="button" value="<?php _e('Filter', 'asap-digest'); ?>">
                <a href="?page=asap-content-library" class="button"><?php _e('Reset', 'asap-digest'); ?></a>
            </div>
            
            <div class="tablenav-pages">
                <?php if ($total_pages > 1) : ?>
                    <span class="displaying-num">
                        <?php 
                        printf(
                            _n('%s item', '%s items', $total_items, 'asap-digest'), 
                            number_format_i18n($total_items)
                        ); 
                        ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        // First page link
                        if ($current_filters['page'] > 1) {
                            printf(
                                '<a class="first-page button" href="%s"><span>«</span></a>',
                                esc_url(add_query_arg(['paged' => 1]))
                            );
                        } else {
                            echo '<span class="first-page button disabled"><span>«</span></span>';
                        }
                        
                        // Previous page link
                        if ($current_filters['page'] > 1) {
                            printf(
                                '<a class="prev-page button" href="%s"><span>‹</span></a>',
                                esc_url(add_query_arg(['paged' => $current_filters['page'] - 1]))
                            );
                        } else {
                            echo '<span class="prev-page button disabled"><span>‹</span></span>';
                        }
                        
                        // Current page / total pages
                        printf(
                            '<span class="paging-input">%s of %s</span>',
                            $current_filters['page'],
                            $total_pages
                        );
                        
                        // Next page link
                        if ($current_filters['page'] < $total_pages) {
                            printf(
                                '<a class="next-page button" href="%s"><span>›</span></a>',
                                esc_url(add_query_arg(['paged' => $current_filters['page'] + 1]))
                            );
                        } else {
                            echo '<span class="next-page button disabled"><span>›</span></span>';
                        }
                        
                        // Last page link
                        if ($current_filters['page'] < $total_pages) {
                            printf(
                                '<a class="last-page button" href="%s"><span>»</span></a>',
                                esc_url(add_query_arg(['paged' => $total_pages]))
                            );
                        } else {
                            echo '<span class="last-page button disabled"><span>»</span></span>';
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </form>
    
    <!-- Content Table -->
    <form id="content-library-form" method="post" action="">
        <?php wp_nonce_field('asap_content_library_actions'); ?>
        <input type="hidden" name="action" id="bulk-action" value="">
        <input type="hidden" name="new_status" id="new-status" value="">
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </th>
                    <th class="column-id"><?php _e('ID', 'asap-digest'); ?></th>
                    <th class="column-title"><?php _e('Title', 'asap-digest'); ?></th>
                    <th class="column-type"><?php _e('Type', 'asap-digest'); ?></th>
                    <th class="column-status"><?php _e('Status', 'asap-digest'); ?></th>
                    <th class="column-quality"><?php _e('Quality', 'asap-digest'); ?></th>
                    <th class="column-source"><?php _e('Source', 'asap-digest'); ?></th>
                    <th class="column-date"><?php _e('Date', 'asap-digest'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'asap-digest'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="9"><?php _e('No content items found.', 'asap-digest'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="content_ids[]" value="<?php echo $item['id']; ?>">
                            </td>
                            <td><?php echo esc_html($item['id']); ?></td>
                            <td class="title-column">
                                <strong>
                                    <a href="#" class="content-view-link" data-id="<?php echo $item['id']; ?>">
                                        <?php echo esc_html($item['title']); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="content-view-link" data-id="<?php echo $item['id']; ?>">
                                            <?php _e('View', 'asap-digest'); ?>
                                        </a> | 
                                    </span>
                                    <span class="edit">
                                        <a href="#" class="content-edit-link" data-id="<?php echo $item['id']; ?>">
                                            <?php _e('Edit', 'asap-digest'); ?>
                                        </a> | 
                                    </span>
                                    <span class="trash">
                                        <a href="#" class="content-delete-link" data-id="<?php echo $item['id']; ?>">
                                            <?php _e('Delete', 'asap-digest'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html(ucfirst($item['type'])); ?></td>
                            <td>
                                <span class="content-status content-status-<?php echo esc_attr($item['status']); ?>">
                                    <?php echo esc_html(ucfirst($item['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="quality-score">
                                    <div class="quality-bar" style="width: <?php echo intval($item['quality_score']); ?>%;">
                                        <span class="quality-value"><?php echo intval($item['quality_score']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($item['source_url'])) : ?>
                                    <a href="<?php echo esc_url($item['source_url']); ?>" target="_blank">
                                        <?php echo esc_html(wp_parse_url($item['source_url'], PHP_URL_HOST)); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="na">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $publish_date = !empty($item['publish_date']) ? $item['publish_date'] : $item['created_at'];
                                echo esc_html(date_i18n(get_option('date_format'), strtotime($publish_date)));
                                ?>
                            </td>
                            <td>
                                <button type="button" class="button content-view-button" data-id="<?php echo $item['id']; ?>">
                                    <?php _e('View', 'asap-digest'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="cb-select-all-2">
                    </th>
                    <th class="column-id"><?php _e('ID', 'asap-digest'); ?></th>
                    <th class="column-title"><?php _e('Title', 'asap-digest'); ?></th>
                    <th class="column-type"><?php _e('Type', 'asap-digest'); ?></th>
                    <th class="column-status"><?php _e('Status', 'asap-digest'); ?></th>
                    <th class="column-quality"><?php _e('Quality', 'asap-digest'); ?></th>
                    <th class="column-source"><?php _e('Source', 'asap-digest'); ?></th>
                    <th class="column-date"><?php _e('Date', 'asap-digest'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'asap-digest'); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select id="bulk-action-selector-bottom">
                    <option value=""><?php _e('Bulk Actions', 'asap-digest'); ?></option>
                    <option value="delete"><?php _e('Delete', 'asap-digest'); ?></option>
                    <option value="publish"><?php _e('Change Status to Published', 'asap-digest'); ?></option>
                    <option value="pending"><?php _e('Change Status to Pending', 'asap-digest'); ?></option>
                    <option value="draft"><?php _e('Change Status to Draft', 'asap-digest'); ?></option>
                    <option value="private"><?php _e('Change Status to Private', 'asap-digest'); ?></option>
                </select>
                <input type="button" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'asap-digest'); ?>">
            </div>
        </div>
    </form>
    
    <!-- View/Edit Content Modal -->
    <div id="content-modal" class="content-modal" style="display: none;">
        <div class="content-modal-content">
            <span class="close-modal">&times;</span>
            <div id="content-view-container"></div>
        </div>
    </div>
    
    <!-- Styles and Scripts -->
    <style>
        /* Table styles */
        .column-id { width: 50px; }
        .column-type { width: 100px; }
        .column-status { width: 100px; }
        .column-quality { width: 120px; }
        .column-source { width: 180px; }
        .column-date { width: 120px; }
        .column-actions { width: 80px; }
        
        /* Quality score bar */
        .quality-score {
            background-color: #f0f0f0;
            border-radius: 4px;
            width: 100%;
            height: 20px;
            position: relative;
        }
        .quality-bar {
            background-color: #2271b1;
            height: 100%;
            border-radius: 4px;
            position: relative;
            min-width: 30px;
        }
        .quality-value {
            color: white;
            position: absolute;
            right: 5px;
            top: 1px;
            font-size: 12px;
        }
        
        /* Status indicators */
        .content-status {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .content-status-published {
            background-color: #46b450;
            color: white;
        }
        .content-status-pending {
            background-color: #f56e28;
            color: white;
        }
        .content-status-draft {
            background-color: #c3c4c7;
            color: #000;
        }
        .content-status-private {
            background-color: #2271b1;
            color: white;
        }
        
        /* Modal styles */
        .content-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .content-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #ddd;
            width: 80%;
            max-width: 900px;
            border-radius: 4px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }
        
        /* Content view styles */
        .content-view-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .content-view-body {
            margin-bottom: 20px;
        }
        .content-view-meta {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
        }
        .content-view-meta table {
            width: 100%;
            border-collapse: collapse;
        }
        .content-view-meta table th {
            text-align: left;
            width: 140px;
            padding: 5px 10px 5px 0;
            vertical-align: top;
        }
        .content-view-actions {
            margin-top: 20px;
            text-align: right;
        }
    </style>
    
    <script>
        jQuery(document).ready(function($) {
            // Bulk actions
            $('#doaction').on('click', function() {
                var selected_action = $('#bulk-action-selector-bottom').val();
                if (!selected_action) {
                    alert('<?php _e('Please select an action', 'asap-digest'); ?>');
                    return;
                }
                
                var selected_items = $('input[name="content_ids[]"]:checked');
                if (selected_items.length === 0) {
                    alert('<?php _e('Please select at least one item', 'asap-digest'); ?>');
                    return;
                }
                
                if (selected_action === 'delete') {
                    if (!confirm('<?php _e('Are you sure you want to delete the selected items?', 'asap-digest'); ?>')) {
                        return;
                    }
                    $('#bulk-action').val('delete');
                    $('#content-library-form').submit();
                } else {
                    // Status change
                    $('#bulk-action').val('change_status');
                    $('#new-status').val(selected_action);
                    $('#content-library-form').submit();
                }
            });
            
            // Check all checkboxes
            $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
                $('input[name="content_ids[]"]').prop('checked', $(this).prop('checked'));
            });
            
            // View content modal
            $('.content-view-button, .content-view-link').on('click', function(e) {
                e.preventDefault();
                var contentId = $(this).data('id');
                
                // AJAX call to get content details
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'asap_get_content_details',
                        content_id: contentId,
                        nonce: '<?php echo wp_create_nonce('asap_digest_content_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var content = response.data;
                            var html = '<div class="content-view-header">';
                            html += '<h2>' + content.title + '</h2>';
                            html += '</div>';
                            
                            html += '<div class="content-view-body">';
                            html += content.content;
                            html += '</div>';
                            
                            html += '<div class="content-view-meta">';
                            html += '<table>';
                            html += '<tr><th><?php _e('Content ID', 'asap-digest'); ?>:</th><td>' + content.id + '</td></tr>';
                            html += '<tr><th><?php _e('Type', 'asap-digest'); ?>:</th><td>' + content.type + '</td></tr>';
                            html += '<tr><th><?php _e('Status', 'asap-digest'); ?>:</th><td>' + content.status + '</td></tr>';
                            html += '<tr><th><?php _e('Quality Score', 'asap-digest'); ?>:</th><td>' + content.quality_score + '</td></tr>';
                            html += '<tr><th><?php _e('Published Date', 'asap-digest'); ?>:</th><td>' + content.publish_date + '</td></tr>';
                            html += '<tr><th><?php _e('Source URL', 'asap-digest'); ?>:</th><td><a href="' + content.source_url + '" target="_blank">' + content.source_url + '</a></td></tr>';
                            html += '</table>';
                            html += '</div>';
                            
                            html += '<div class="content-view-actions">';
                            html += '<button type="button" class="button button-secondary close-content-modal"><?php _e('Close', 'asap-digest'); ?></button>';
                            html += '</div>';
                            
                            $('#content-view-container').html(html);
                            $('#content-modal').show();
                        } else {
                            alert(response.data.message || '<?php _e('Error loading content details', 'asap-digest'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error loading content details', 'asap-digest'); ?>');
                    }
                });
            });
            
            // Edit content (placeholder - can be expanded later)
            $('.content-edit-link').on('click', function(e) {
                e.preventDefault();
                alert('<?php _e('Edit functionality will be available in a future update.', 'asap-digest'); ?>');
            });
            
            // Delete content
            $('.content-delete-link').on('click', function(e) {
                e.preventDefault();
                var contentId = $(this).data('id');
                
                if (confirm('<?php _e('Are you sure you want to delete this item?', 'asap-digest'); ?>')) {
                    // Create a temporary form and submit it
                    var tempForm = $('<form method="post" action="">' +
                        '<?php echo wp_nonce_field('asap_content_library_actions', '_wpnonce', true, false); ?>' +
                        '<input type="hidden" name="action" value="delete">' +
                        '<input type="hidden" name="content_ids[]" value="' + contentId + '">' +
                        '</form>');
                    $('body').append(tempForm);
                    tempForm.submit();
                }
            });
            
            // Close modal
            $('.close-modal, .close-content-modal').on('click', function() {
                $('#content-modal').hide();
            });
            
            // Close modal on outside click
            $(window).on('click', function(event) {
                if ($(event.target).is('#content-modal')) {
                    $('#content-modal').hide();
                }
            });
        });
    </script>
</div> 