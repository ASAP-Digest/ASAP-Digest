<?php
/**
 * Admin View: Ingested Content Management
 *
 * @package ASAPDigest_Core
 * @since 2.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles
wp_enqueue_style('asap-content-library', plugin_dir_url(dirname(__FILE__)) . 'css/content-library.css', [], '2.3.0');
wp_enqueue_script('asap-content-library', plugin_dir_url(dirname(__FILE__)) . 'js/content-library.js', ['jquery'], '2.3.0', true);

// Localize script with AJAX URL and nonce
wp_localize_script('asap-content-library', 'asapDigestAdmin', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('asap_digest_content_nonce'),
]);

// Content types
$content_types = [
    'article' => __('Article', 'asapdigest-core'),
    'news' => __('News', 'asapdigest-core'),
    'blog' => __('Blog Post', 'asapdigest-core'),
    'podcast' => __('Podcast', 'asapdigest-core'),
    'video' => __('Video', 'asapdigest-core'),
];

// Statuses
$statuses = [
    'pending' => __('Pending', 'asapdigest-core'),
    'approved' => __('Approved', 'asapdigest-core'),
    'rejected' => __('Rejected', 'asapdigest-core'),
    'processing' => __('Processing', 'asapdigest-core'),
];

// Quality score ranges
$quality_ranges = [
    '0' => __('All Quality Levels', 'asapdigest-core'),
    '90' => __('Excellent (90+)', 'asapdigest-core'),
    '70' => __('Good (70+)', 'asapdigest-core'),
    '50' => __('Average (50+)', 'asapdigest-core'),
    '30' => __('Poor (30+)', 'asapdigest-core'),
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Content Library', 'asapdigest-core'); ?></h1>
    <hr class="wp-header-end">

    <div id="form-messages"></div>

    <div class="content-library-wrap">
        <!-- Filters -->
        <div class="form-filters">
            <div class="search-box">
                <input type="search" id="content-search" placeholder="<?php esc_attr_e('Search content...', 'asapdigest-core'); ?>">
            </div>
            
            <div>
                <select id="content-type-filter">
                    <option value=""><?php _e('All Types', 'asapdigest-core'); ?></option>
                    <?php foreach ($content_types as $type => $label) : ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <select id="content-status-filter">
                    <option value=""><?php _e('All Statuses', 'asapdigest-core'); ?></option>
                    <?php foreach ($statuses as $status => $label) : ?>
                        <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <select id="content-quality-filter">
                    <?php foreach ($quality_ranges as $min => $label) : ?>
                        <option value="<?php echo esc_attr($min); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Bulk Actions -->
        <div class="tablenav top">
            <div class="tablenav-bulk">
                <form id="bulk-action-form" method="post">
                    <div class="select-all-wrap">
                        <input type="checkbox" id="select-all">
                        <label for="select-all" class="screen-reader-text">Select all items</label>
                    </div>
                    
                    <select class="actions-select" name="bulk_action">
                        <option value=""><?php _e('Bulk Actions', 'asapdigest-core'); ?></option>
                        <option value="approve"><?php _e('Approve', 'asapdigest-core'); ?></option>
                        <option value="reject"><?php _e('Reject', 'asapdigest-core'); ?></option>
                        <option value="pending"><?php _e('Mark as Pending', 'asapdigest-core'); ?></option>
                        <option value="delete"><?php _e('Delete', 'asapdigest-core'); ?></option>
                    </select>
                    
                    <button type="submit" class="button action" disabled><?php _e('Apply', 'asapdigest-core'); ?></button>
                </form>
            </div>
        </div>
        
        <!-- Content Table -->
        <table class="wp-list-table widefat fixed striped content-library-table" id="content-library-table">
            <thead>
                <tr>
                    <th class="check-column">
                        <span class="screen-reader-text"><?php _e('Select All', 'asapdigest-core'); ?></span>
                    </th>
                    <th><?php _e('Title', 'asapdigest-core'); ?></th>
                    <th style="width: 80px;"><?php _e('Type', 'asapdigest-core'); ?></th>
                    <th style="width: 80px;"><?php _e('Status', 'asapdigest-core'); ?></th>
                    <th style="width: 100px;"><?php _e('Quality', 'asapdigest-core'); ?></th>
                    <th style="width: 120px;"><?php _e('Source', 'asapdigest-core'); ?></th>
                    <th style="width: 120px;"><?php _e('Published', 'asapdigest-core'); ?></th>
                    <th style="width: 120px;"><?php _e('Ingested', 'asapdigest-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" class="no-items"><?php _e('Loading content...', 'asapdigest-core'); ?></td>
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

<!-- Content Detail Modal -->
<div id="content-detail-modal" class="asap-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Content Details', 'asapdigest-core'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <h2 class="content-detail-title"></h2>
            <div class="content-detail-content"></div>
            
            <?php if (!empty($content['ai_metadata'])): ?>
                <?php $ai = json_decode($content['ai_metadata'], true); ?>
                <div class="content-ai-metadata">
                    <h3>AI-Enriched Data</h3>
                    <?php if (!empty($ai['summary'])): ?>
                        <div class="ai-summary"><strong>Summary:</strong> <?php echo esc_html($ai['summary']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ai['entities'])): ?>
                        <div class="ai-entities"><strong>Entities:</strong>
                            <ul>
                                <?php foreach ($ai['entities'] as $entity): ?>
                                    <li><?php echo esc_html(is_array($entity) ? ($entity['entity'] ?? $entity['name'] ?? json_encode($entity)) : $entity); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($ai['classifications'])): ?>
                        <div class="ai-classifications"><strong>Classifications:</strong>
                            <ul>
                                <?php foreach ($ai['classifications'] as $class): ?>
                                    <li><?php echo esc_html(is_array($class) ? ($class['category'] ?? $class['label'] ?? json_encode($class)) : $class); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($ai['keywords'])): ?>
                        <div class="ai-keywords"><strong>Keywords:</strong>
                            <ul>
                                <?php foreach ($ai['keywords'] as $kw): ?>
                                    <li><?php echo esc_html(is_array($kw) ? ($kw['keyword'] ?? $kw['term'] ?? json_encode($kw)) : $kw); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <button class="button" onclick="asapReEnrichAI(<?php echo (int)$content['id']; ?>)">Re-Run AI Enrichment</button>
                </div>
            <?php endif; ?>
            
            <div class="content-detail-actions">
                <div class="left-actions">
                    <a href="#" target="_blank" class="button view-original"><?php _e('View Original', 'asapdigest-core'); ?></a>
                </div>
                <div class="action-buttons">
                    <button type="button" class="button approve-content-btn"><?php _e('Approve', 'asapdigest-core'); ?></button>
                    <button type="button" class="button reject-content-btn"><?php _e('Reject', 'asapdigest-core'); ?></button>
                    <button type="button" class="button delete-content-btn"><?php _e('Delete', 'asapdigest-core'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="confirm-modal" class="asap-modal confirm-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Confirm Deletion', 'asapdigest-core'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p class="confirm-text"><?php _e('Are you sure you want to delete this content? This action cannot be undone.', 'asapdigest-core'); ?></p>
            <div class="action-buttons">
                <button type="button" class="button modal-close"><?php _e('Cancel', 'asapdigest-core'); ?></button>
                <button type="button" id="confirm-delete-btn" class="button button-primary"><?php _e('Yes, Delete', 'asapdigest-core'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulk-confirm-modal" class="asap-modal confirm-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Confirm Bulk Action', 'asapdigest-core'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p class="confirm-text"><?php _e('Are you sure you want to delete the selected items? This action cannot be undone.', 'asapdigest-core'); ?></p>
            <div class="action-buttons">
                <button type="button" class="button modal-close"><?php _e('Cancel', 'asapdigest-core'); ?></button>
                <button type="button" id="confirm-bulk-delete-btn" class="button button-primary"><?php _e('Yes, Delete', 'asapdigest-core'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
function asapReEnrichAI(contentId) {
    var nonce = typeof asapDigestAdmin !== 'undefined' ? asapDigestAdmin.nonce : '';
    var modal = document.getElementById('content-detail-modal');
    var aiSection = modal ? modal.querySelector('.content-ai-metadata') : null;
    if (aiSection) {
        aiSection.innerHTML = '<em>Re-enriching with AI, please wait...</em>';
    }
    var data = new FormData();
    data.append('action', 'asap_reenrich_content_ai');
    data.append('content_id', contentId);
    data.append('nonce', nonce);
    fetch(ajaxurl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
    })
    .then(function(response) { return response.json(); })
    .then(function(json) {
        if (json.success && json.data && json.data.ai_metadata) {
            var ai = json.data.ai_metadata;
            var html = '<h3>AI-Enriched Data</h3>';
            if (ai.summary) html += '<div class="ai-summary"><strong>Summary:</strong> ' + ai.summary + '</div>';
            if (ai.entities && ai.entities.length) {
                html += '<div class="ai-entities"><strong>Entities:</strong><ul>';
                ai.entities.forEach(function(entity) {
                    html += '<li>' + (entity.entity || entity.name || JSON.stringify(entity)) + '</li>';
                });
                html += '</ul></div>';
            }
            if (ai.classifications && ai.classifications.length) {
                html += '<div class="ai-classifications"><strong>Classifications:</strong><ul>';
                ai.classifications.forEach(function(cls) {
                    html += '<li>' + (cls.category || cls.label || JSON.stringify(cls)) + '</li>';
                });
                html += '</ul></div>';
            }
            if (ai.keywords && ai.keywords.length) {
                html += '<div class="ai-keywords"><strong>Keywords:</strong><ul>';
                ai.keywords.forEach(function(kw) {
                    html += '<li>' + (kw.keyword || kw.term || JSON.stringify(kw)) + '</li>';
                });
                html += '</ul></div>';
            }
            html += '<button class="button" onclick="asapReEnrichAI(' + contentId + ')">Re-Run AI Enrichment</button>';
            if (aiSection) aiSection.innerHTML = html;
        } else {
            var msg = (json.data && json.data.message) ? json.data.message : 'AI enrichment failed.';
            if (aiSection) aiSection.innerHTML = '<span style="color:red;">' + msg + '</span>';
            else alert(msg);
        }
    })
    .catch(function(err) {
        if (aiSection) aiSection.innerHTML = '<span style="color:red;">AJAX error: ' + err + '</span>';
        else alert('AJAX error: ' + err);
    });
}
</script> 