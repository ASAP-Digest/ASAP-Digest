<?php
/**
 * Ingested Content Admin View
 * Enhanced UI for managing ingested content (CRUD)
 * @file-marker ASAP_Digest_Admin_Ingested_Content_View
 */
if (!defined('ABSPATH')) exit;

// Include content processing bootstrap
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/content-processing/bootstrap.php';

global $wpdb;
$table = $wpdb->prefix . 'asap_ingested_content';
$index_table = $wpdb->prefix . 'asap_content_index';
$success = '';
$error = '';
$processor = asap_digest_get_content_processor();

// Check for edit action via GET parameters
$edit_item = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $record_id = intval($_GET['id']);
    $edit_item = $processor->get_content($record_id);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
    // Delete action
    if (isset($_POST['delete_record'])) {
        $record_id = intval($_POST['record_id']);
        $result = $processor->delete($record_id);
        
        if ($result) {
            $success = 'Content deleted successfully.';
        } else {
            $error = 'Deletion error: Unable to delete content.';
        }
    }
    // Update action
    else if (isset($_POST['update_record']) && !empty($_POST['record_id'])) {
        $record_id = intval($_POST['record_id']);
        
        // Prepare content data
        $content_data = array(
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'content' => wp_kses_post($_POST['content'] ?? ''),
            'summary' => sanitize_text_field($_POST['summary'] ?? ''),
            'source_url' => esc_url_raw($_POST['source_url'] ?? ''),
            'source_id' => sanitize_text_field($_POST['source_id'] ?? ''),
            'publish_date' => sanitize_text_field($_POST['publish_date'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'published'),
        );
        
        // Process and validate content
        $process_result = $processor->process($content_data, $record_id);
        
        if (!$process_result['success']) {
            // Show validation errors
            $error = 'Validation error: ';
            if (!empty($process_result['errors'])) {
                $error .= implode(', ', array_values($process_result['errors']));
            } else {
                $error .= 'Unknown validation error';
            }
        } else {
            // Save processed content
            $save_result = $processor->save($process_result, $record_id);
            
            if ($save_result['success']) {
                $success = 'Content updated successfully. Quality score: ' . $process_result['data']['quality_score'];
                $edit_item = null;
            } else {
                $error = 'Database error: ' . implode(', ', $save_result['errors']);
            }
        }
    }
    // Insert new content action
    else {
        // Prepare content data
        $content_data = array(
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'content' => wp_kses_post($_POST['content'] ?? ''),
            'summary' => sanitize_text_field($_POST['summary'] ?? ''),
            'source_url' => esc_url_raw($_POST['source_url'] ?? ''),
            'source_id' => sanitize_text_field($_POST['source_id'] ?? ''),
            'publish_date' => sanitize_text_field($_POST['publish_date'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'published'),
        );
        
        // Process and validate content
        $process_result = $processor->process($content_data);
        
        if (!$process_result['success']) {
            // Show validation errors
            $error = 'Validation error: ';
            if (!empty($process_result['errors'])) {
                $error .= implode(', ', array_values($process_result['errors']));
            } else {
                $error .= 'Unknown validation error';
            }
        } else {
            // Save processed content
            $save_result = $processor->save($process_result);
            
            if ($save_result['success']) {
                $success = 'Content added successfully. Quality score: ' . $process_result['data']['quality_score'];
            } else {
                $error = 'Database error: ' . implode(', ', $save_result['errors']);
            }
        }
    }
}

// Fetch recent ingested content with optional search filtering
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
if($search) {
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE title LIKE %s ORDER BY created_at DESC", '%' . $wpdb->esc_like($search) . '%'), ARRAY_A);
} else {
    $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 20", ARRAY_A);
}
?>
<div class="wrap">
    <h1>Ingested Content</h1>
    <?php if ($success): ?>
        <div class="notice notice-success"><p><?php echo esc_html($success); ?></p></div>
    <?php elseif ($error): ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>
    
    <!-- Search Form -->
    <form method="get" action="">
        <input type="hidden" name="page" value="asap-ingested-content" />
        <input type="text" name="s" placeholder="Search by Title..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" />
        <input type="submit" class="button" value="Search" />
        <?php if(isset($_GET['s']) && $_GET['s'] !== ''): ?>
            <a href="<?php echo admin_url('admin.php?page=asap-ingested-content'); ?>" class="button">Clear</a>
        <?php endif; ?>
    </form>
    
    <?php if ($edit_item): ?>
        <h2>Edit Content</h2>
    <?php else: ?>
        <h2>Add New Content</h2>
    <?php endif; ?>
    <form method="post" action="">
        <?php if ($edit_item): ?>
            <input type="hidden" name="record_id" value="<?php echo intval($edit_item['id']); ?>">
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label for="type">Type</label></th>
                <td>
                    <select name="type" id="type" required>
                        <option value="article" <?php selected(($edit_item['type'] ?? ($_POST['type'] ?? 'article')), 'article'); ?>>Article</option>
                        <option value="podcast" <?php selected(($edit_item['type'] ?? ($_POST['type'] ?? '')), 'podcast'); ?>>Podcast</option>
                        <option value="video" <?php selected(($edit_item['type'] ?? ($_POST['type'] ?? '')), 'video'); ?>>Video</option>
                        <option value="other" <?php selected(($edit_item['type'] ?? ($_POST['type'] ?? '')), 'other'); ?>>Other</option>
                    </select>
                    <br /><small>Select the content type. Default is Article.</small>
                </td>
            </tr>
            <tr>
                <th><label for="title">Title</label></th>
                <td>
                    <input name="title" id="title" type="text" placeholder="Enter content title" value="<?php echo esc_attr($edit_item['title'] ?? ($_POST['title'] ?? '')); ?>" required>
                    <br /><small>Provide a descriptive title for the content.</small>
                </td>
            </tr>
            <tr>
                <th><label for="content">Content</label></th>
                <td>
                    <textarea name="content" id="content" rows="4" cols="60" placeholder="Enter full content here" required><?php echo esc_textarea($edit_item['content'] ?? ($_POST['content'] ?? '')); ?></textarea>
                    <br /><small>Enter the main body of the content.</small>
                </td>
            </tr>
            <tr>
                <th><label for="summary">Summary</label></th>
                <td>
                    <input name="summary" id="summary" type="text" placeholder="Optional summary" value="<?php echo esc_attr($edit_item['summary'] ?? ($_POST['summary'] ?? '')); ?>">
                    <br /><small>Optionally provide a short summary or excerpt.</small>
                </td>
            </tr>
            <tr>
                <th><label for="source_url">Source URL</label></th>
                <td>
                    <input name="source_url" id="source_url" type="url" placeholder="https://example.com/source" value="<?php echo esc_attr($edit_item['source_url'] ?? ($_POST['source_url'] ?? '')); ?>" required>
                    <br /><small>Enter the original URL of the source content.</small>
                </td>
            </tr>
            <tr>
                <th><label for="source_id">Source ID</label></th>
                <td>
                    <input name="source_id" id="source_id" type="text" placeholder="Source system ID" value="<?php echo esc_attr($edit_item['source_id'] ?? ($_POST['source_id'] ?? '')); ?>">
                    <br /><small>Unique identifier from the source system.</small>
                </td>
            </tr>
            <tr>
                <th><label for="publish_date">Publish Date</label></th>
                <td>
                    <input name="publish_date" id="publish_date" type="datetime-local" placeholder="YYYY-MM-DDTHH:MM" value="<?php echo esc_attr($edit_item['publish_date'] ?? ($_POST['publish_date'] ?? '')); ?>">
                    <br /><small>Specify when the content was originally published.</small>
                </td>
            </tr>
            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select name="status" id="status" required>
                        <option value="published" <?php selected(($edit_item['status'] ?? ($_POST['status'] ?? 'published')), 'published'); ?>>Published</option>
                        <option value="pending" <?php selected(($edit_item['status'] ?? ($_POST['status'] ?? '')), 'pending'); ?>>Pending</option>
                        <option value="rejected" <?php selected(($edit_item['status'] ?? ($_POST['status'] ?? '')), 'rejected'); ?>>Rejected</option>
                    </select>
                    <br /><small>Select the content status.</small>
                </td>
            </tr>
        </table>
        <?php if ($edit_item): ?>
            <?php submit_button('Update Content', 'primary', 'update_record'); ?>
            <a href="<?php echo admin_url('admin.php?page=asap-ingested-content'); ?>" class="button">Cancel Editing</a>
        <?php else: ?>
            <?php submit_button('Add Content'); ?>
        <?php endif; ?>
    </form>
    <h2>Recent Ingested Content</h2>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Title</th>
                <th>Status</th>
                <th>Quality Score</th>
                <th>Source URL</th>
                <th>Publish Date</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($items): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo intval($item['id']); ?></td>
                        <td><?php echo esc_html($item['type']); ?></td>
                        <td><?php echo esc_html($item['title']); ?></td>
                        <td><?php echo esc_html($item['status']); ?></td>
                        <td><?php echo esc_html($item['quality_score']); ?></td>
                        <td><a href="<?php echo esc_url($item['source_url']); ?>" target="_blank">link</a></td>
                        <td><?php echo esc_html($item['publish_date']); ?></td>
                        <td><?php echo esc_html($item['created_at']); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=asap-ingested-content&action=edit&id=' . intval($item['id'])); ?>" class="button">Edit</a>
                            <form method="post" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this content?');">
                                <input type="hidden" name="record_id" value="<?php echo intval($item['id']); ?>">
                                <?php submit_button('Delete', 'delete', 'delete_record', false); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9">No content found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div> 