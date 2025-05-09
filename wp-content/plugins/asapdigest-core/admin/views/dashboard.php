<?php
/**
 * ASAP Digest Central Command Dashboard View
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker Dashboard_View
 */

use ASAPDigest\Core\ASAP_Digest_Admin_UI;

if (!defined('ABSPATH')) {
    exit;
}

// Get plugin instance
$plugin = ASAPDigest\Core\ASAP_Digest_Core::get_instance();
$usage_tracker = $plugin->get_usage_tracker();

// Get usage metrics for the current month
$service_costs = $usage_tracker->get_total_service_costs('month');

// Get crawler stats
global $wpdb;
$sources_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asap_content_sources");
$moderation_queue_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asap_moderation_queue WHERE status = 'pending'");
$moderation_approved_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asap_moderation_log WHERE decision = 'approved'");

// Get AI stats
$ai_usage = get_option('asap_ai_usage_stats', []);
$ai_total_calls = 0;
foreach ($ai_usage as $provider) {
    foreach ($provider as $task) {
        $ai_total_calls += $task['calls'] ?? 0;
    }
}

// Get crawler schedule
$next_run = wp_next_scheduled('asap_run_crawler');
$last_run = get_option('asap_crawler_last_run', '');
?>

<div class="wrap asap-central-command">
    <h1>⚡️ ASAP Digest Central Command</h1>
    
    <?php
    // Display any admin notices
    settings_errors('asap_messages');
    ?>

    <div class="asap-dashboard-grid">
        <!-- Service Usage Overview -->
        <?php
        echo ASAP_Digest_Admin_UI::create_card(
            'Service Usage Overview',
            '<div class="asap-metrics-grid">' .
            '<div class="asap-metric">' .
            '<h3>Total Services</h3>' .
            '<p class="asap-metric-value">' . count($service_costs) . '</p>' .
            '</div>' .
            '</div>',
            'asap-card-overview'
        );
        ?>

        <!-- Service Costs -->
        <?php
        $costs_content = '<table class="wp-list-table widefat fixed striped">';
        $costs_content .= '<thead><tr>';
        $costs_content .= '<th>Service</th>';
        $costs_content .= '<th>Total Usage</th>';
        $costs_content .= '<th>Total Cost</th>';
        $costs_content .= '<th>Unique Users</th>';
        $costs_content .= '</tr></thead><tbody>';

        $total_cost = 0;
        foreach ($service_costs as $service) {
            $costs_content .= '<tr>';
            $costs_content .= '<td>' . esc_html($service->metric_type) . '</td>';
            $costs_content .= '<td>' . number_format($service->total_value, 2) . '</td>';
            $costs_content .= '<td>$' . number_format($service->total_cost, 2) . '</td>';
            $costs_content .= '<td>' . number_format($service->unique_users) . '</td>';
            $costs_content .= '</tr>';
            $total_cost += $service->total_cost;
        }

        $costs_content .= '</tbody><tfoot><tr>';
        $costs_content .= '<th colspan="2">Total</th>';
        $costs_content .= '<th>$' . number_format($total_cost, 2) . '</th>';
        $costs_content .= '<th></th>';
        $costs_content .= '</tr></tfoot></table>';

        echo ASAP_Digest_Admin_UI::create_card(
            'Service Costs (This Month)',
            $costs_content,
            'asap-card-costs'
        );
        ?>

        <!-- Quick Actions -->
        <?php
        echo ASAP_Digest_Admin_UI::create_card(
            'Quick Actions',
            '<div class="asap-quick-actions">' .
            '<a href="?page=asap-usage-analytics" class="button button-primary">View Detailed Analytics</a> ' .
            '<a href="?page=asap-service-costs" class="button">Manage Service Costs</a>' .
            '</div>',
            'asap-card-actions'
        );
        ?>

        <!-- System Status -->
        <?php
        // Check various system statuses
        $db_status = $plugin->get_database()->get_table_name('asap_usage_metrics') ? 'good' : 'error';
        $auth_status = $plugin->get_better_auth() ? 'good' : 'error';

        $status_content = '<div class="asap-status-grid">';
        $status_content .= '<div class="asap-status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            $db_status,
            'Database Tables'
        );
        $status_content .= '</div>';

        $status_content .= '<div class="asap-status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            $auth_status,
            'Better Auth Integration'
        );
        $status_content .= '</div>';
        $status_content .= '</div>';

        echo ASAP_Digest_Admin_UI::create_card(
            'System Status',
            $status_content,
            'asap-card-status'
        );
        ?>

        <!-- Content Ingestion System -->
        <div class="asap-dashboard-card asap-card-primary">
            <div class="asap-card-header">
                <h2><span class="dashicons dashicons-rss"></span> Content Ingestion</h2>
                <span class="asap-card-badge"><?php echo esc_html($sources_count); ?> Sources</span>
            </div>
            <div class="asap-card-content">
                <p>Automatic content ingestion from RSS feeds, APIs, and websites.</p>
                
                <div class="asap-stats-grid">
                    <div class="asap-stat-box">
                        <span class="asap-stat-value"><?php echo esc_html($sources_count); ?></span>
                        <span class="asap-stat-label">Sources</span>
                    </div>
                    <div class="asap-stat-box">
                        <span class="asap-stat-value"><?php echo esc_html($moderation_queue_count); ?></span>
                        <span class="asap-stat-label">In Queue</span>
                    </div>
                    <div class="asap-stat-box">
                        <span class="asap-stat-value"><?php echo esc_html($moderation_approved_count); ?></span>
                        <span class="asap-stat-label">Approved</span>
                    </div>
                </div>
                
                <p><strong>Next Crawl:</strong> <?php echo $next_run ? date_i18n('F j, Y, g:i a', $next_run) : 'Not scheduled'; ?><br>
                <strong>Last Crawl:</strong> <?php echo $last_run ? date_i18n('F j, Y, g:i a', strtotime($last_run)) : 'Never'; ?></p>
            </div>
            <div class="asap-card-actions">
                <a href="<?php echo admin_url('admin.php?page=asap-crawler-sources'); ?>" class="button">Manage Sources</a>
                <a href="<?php echo admin_url('admin.php?page=asap-moderation-queue'); ?>" class="button">View Queue</a>
                <a href="<?php echo admin_url('admin.php?page=asap-analytics'); ?>" class="button">View Analytics</a>
            </div>
        </div>
        
        <!-- AI Enhancement System -->
        <div class="asap-dashboard-card asap-card-secondary">
            <div class="asap-card-header">
                <h2><span class="dashicons dashicons-superhero"></span> AI Enhancement</h2>
                <span class="asap-card-badge"><?php echo esc_html($ai_total_calls); ?> Processed</span>
            </div>
            <div class="asap-card-content">
                <p>Automatically enhance content with AI-powered summarization, entity extraction, and keyword generation.</p>
                
                <div class="asap-provider-badges">
                    <?php 
                    $providers = ['openai', 'huggingface', 'anthropic', 'google'];
                    foreach ($providers as $provider) {
                        $key = get_option("asap_ai_{$provider}_key", '');
                        $is_active = !empty($key);
                        $class = $is_active ? 'asap-provider-active' : 'asap-provider-inactive';
                        echo '<span class="asap-provider-badge ' . $class . '">' . ucfirst($provider) . '</span>';
                    }
                    ?>
                </div>
                
                <p>Enhanced content automatically receives AI-generated summaries, tags, and classifications for better organization.</p>
            </div>
            <div class="asap-card-actions">
                <a href="<?php echo admin_url('admin.php?page=asap-ai-settings'); ?>" class="button">Configure AI</a>
                <a href="<?php echo esc_url(get_admin_url(null, 'edit.php?post_type=asap_digest')); ?>" class="button">View Content</a>
            </div>
        </div>
        
        <!-- Content Highlights -->
        <div class="asap-dashboard-card">
            <div class="asap-card-header">
                <h2><span class="dashicons dashicons-welcome-widgets-menus"></span> Recent Content</h2>
            </div>
            <div class="asap-card-content">
                <?php
                $recent_items = $wpdb->get_results("
                    SELECT id, title, url, source_name, status, created_at 
                    FROM {$wpdb->prefix}asap_moderation_queue 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ");
                
                if ($recent_items): ?>
                    <table class="asap-recent-content">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_items as $item): ?>
                            <tr>
                                <td><a href="<?php echo esc_url($item->url); ?>" target="_blank"><?php echo esc_html($item->title); ?></a></td>
                                <td><?php echo esc_html($item->source_name); ?></td>
                                <td><span class="asap-status asap-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html(ucfirst($item->status)); ?></span></td>
                                <td><?php echo esc_html(human_time_diff(strtotime($item->created_at), current_time('timestamp'))); ?> ago</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No content found in the system yet.</p>
                <?php endif; ?>
            </div>
            <div class="asap-card-actions">
                <a href="<?php echo admin_url('admin.php?page=asap-moderation-queue'); ?>" class="button">View All Content</a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="asap-dashboard-card">
            <div class="asap-card-header">
                <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
            </div>
            <div class="asap-card-content">
                <div class="asap-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=asap-crawler-sources&action=new'); ?>" class="asap-quick-action">
                        <span class="dashicons dashicons-plus"></span>
                        <span>Add New Source</span>
                    </a>
                    <a href="#" id="asap-run-crawler-now" class="asap-quick-action">
                        <span class="dashicons dashicons-update"></span>
                        <span>Run Crawler Now</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=asap-moderation-queue'); ?>" class="asap-quick-action">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span>Moderate Content</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=asap-ai-settings'); ?>" class="asap-quick-action">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span>Configure AI</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .asap-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .asap-metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .asap-metric {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .asap-metric h3 {
        margin: 0 0 10px 0;
        color: #1d2327;
    }

    .asap-metric-value {
        font-size: 24px;
        font-weight: bold;
        margin: 0;
        color: #2271b1;
    }

    .asap-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .asap-status-item {
        padding: 10px;
        background: #fff;
        border-radius: 4px;
    }

    .asap-quick-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .asap-card-costs table {
        margin-top: 10px;
    }

    .asap-card-status .dashicons {
        margin-right: 5px;
    }

    .asap-status-good {
        color: #00a32a;
    }

    .asap-status-error {
        color: #d63638;
    }

    .asap-dashboard-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
    }

    .asap-card-primary {
        border-top: 4px solid #2271b1;
    }

    .asap-card-secondary {
        border-top: 4px solid #2c974b;
    }

    .asap-card-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .asap-card-header h2 {
        margin: 0;
        font-size: 18px;
        display: flex;
        align-items: center;
    }

    .asap-card-header h2 .dashicons {
        margin-right: 8px;
    }

    .asap-card-badge {
        background: #f0f0f1;
        color: #3c434a;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .asap-card-content {
        padding: 20px;
        flex-grow: 1;
    }

    .asap-card-actions {
        padding: 15px 20px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }

    .asap-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin: 15px 0;
    }

    .asap-stat-box {
        background: #f9f9f9;
        border: 1px solid #eee;
        border-radius: 4px;
        padding: 10px;
        text-align: center;
    }

    .asap-stat-value {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #2271b1;
    }

    .asap-stat-label {
        font-size: 12px;
        color: #666;
    }

    .asap-provider-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 15px 0;
    }

    .asap-provider-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .asap-provider-active {
        background: #e6f6e6;
        color: #2c974b;
        border: 1px solid #c3e6cb;
    }

    .asap-provider-inactive {
        background: #f8f9fa;
        color: #aaa;
        border: 1px solid #ddd;
    }

    .asap-recent-content {
        width: 100%;
        border-collapse: collapse;
    }

    .asap-recent-content th,
    .asap-recent-content td {
        text-align: left;
        padding: 8px;
        border-bottom: 1px solid #eee;
    }

    .asap-recent-content th {
        font-weight: 600;
        color: #444;
    }

    .asap-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }

    .asap-status-pending {
        background: #fff8e5;
        color: #996b00;
    }

    .asap-status-approved {
        background: #e6f6e6;
        color: #2c974b;
    }

    .asap-status-rejected {
        background: #ffe5e5;
        color: #c92b2b;
    }

    .asap-quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .asap-quick-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: #f9f9f9;
        border: 1px solid #eee;
        border-radius: 4px;
        text-decoration: none;
        color: #50575e;
        transition: all 0.2s ease;
    }

    .asap-quick-action:hover {
        background: #f0f0f1;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        color: #2271b1;
    }

    .asap-quick-action .dashicons {
        font-size: 24px;
        width: 24px;
        height: 24px;
        margin-bottom: 10px;
    }

    @media (max-width: 782px) {
        .asap-dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Run crawler now button
    $('#asap-run-crawler-now').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to run the crawler now? This may take a while.')) {
            $(this).html('<span class="dashicons dashicons-update spin"></span><span>Running...</span>');
            
            $.ajax({
                url: '/wp-json/asap/v1/crawler/run',
                method: 'POST',
                success: function(response) {
                    alert('Crawler completed successfully. ' + response.items_processed + ' items processed.');
                    location.reload();
                },
                error: function() {
                    alert('An error occurred while running the crawler.');
                    $('#asap-run-crawler-now').html('<span class="dashicons dashicons-update"></span><span>Run Crawler Now</span>');
                }
            });
        }
    });
});
</script> 