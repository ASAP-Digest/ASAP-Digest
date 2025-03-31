<?php
/**
 * ASAP Digest Usage Analytics View
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker Usage_Analytics_View
 */

use ASAPDigest\Core\ASAP_Digest_Admin_UI;

if (!defined('ABSPATH')) {
    exit;
}

// Get plugin instance
$plugin = ASAPDigest\Core\ASAP_Digest_Core::get_instance();
$usage_tracker = $plugin->get_usage_tracker();

// Get usage metrics for different timeframes
$daily_metrics = $usage_tracker->get_total_service_costs('day');
$weekly_metrics = $usage_tracker->get_total_service_costs('week');
$monthly_metrics = $usage_tracker->get_total_service_costs('month');

// Calculate totals
$daily_total = array_reduce($daily_metrics, function($carry, $item) {
    return $carry + $item->total_cost;
}, 0);

$weekly_total = array_reduce($weekly_metrics, function($carry, $item) {
    return $carry + $item->total_cost;
}, 0);

$monthly_total = array_reduce($monthly_metrics, function($carry, $item) {
    return $carry + $item->total_cost;
}, 0);
?>

<div class="wrap asap-central-command">
    <h1>Usage Analytics</h1>
    
    <?php
    // Display any admin notices
    settings_errors('asap_messages');
    ?>

    <div class="asap-dashboard-grid">
        <!-- Cost Overview -->
        <?php
        $overview_content = '<div class="asap-metrics-grid">';
        
        // Daily Cost
        $overview_content .= '<div class="asap-metric">';
        $overview_content .= '<h3>Today\'s Cost</h3>';
        $overview_content .= '<p class="asap-metric-value">$' . number_format($daily_total, 2) . '</p>';
        $overview_content .= '</div>';
        
        // Weekly Cost
        $overview_content .= '<div class="asap-metric">';
        $overview_content .= '<h3>This Week\'s Cost</h3>';
        $overview_content .= '<p class="asap-metric-value">$' . number_format($weekly_total, 2) . '</p>';
        $overview_content .= '</div>';
        
        // Monthly Cost
        $overview_content .= '<div class="asap-metric">';
        $overview_content .= '<h3>This Month\'s Cost</h3>';
        $overview_content .= '<p class="asap-metric-value">$' . number_format($monthly_total, 2) . '</p>';
        $overview_content .= '</div>';
        
        $overview_content .= '</div>';

        echo ASAP_Digest_Admin_UI::create_card(
            'Cost Overview',
            $overview_content,
            'asap-card-overview'
        );
        ?>

        <!-- Service Usage Details -->
        <?php
        $usage_content = '<div class="asap-tabs">';
        $usage_content .= '<div class="asap-tab-headers">';
        $usage_content .= '<button class="asap-tab-header active" data-tab="daily">Daily</button>';
        $usage_content .= '<button class="asap-tab-header" data-tab="weekly">Weekly</button>';
        $usage_content .= '<button class="asap-tab-header" data-tab="monthly">Monthly</button>';
        $usage_content .= '</div>';

        // Daily Tab
        $usage_content .= '<div class="asap-tab-content active" id="daily">';
        $usage_content .= create_metrics_table($daily_metrics);
        $usage_content .= '</div>';

        // Weekly Tab
        $usage_content .= '<div class="asap-tab-content" id="weekly">';
        $usage_content .= create_metrics_table($weekly_metrics);
        $usage_content .= '</div>';

        // Monthly Tab
        $usage_content .= '<div class="asap-tab-content" id="monthly">';
        $usage_content .= create_metrics_table($monthly_metrics);
        $usage_content .= '</div>';

        $usage_content .= '</div>';

        echo ASAP_Digest_Admin_UI::create_card(
            'Service Usage Details',
            $usage_content,
            'asap-card-usage'
        );

        /**
         * Create metrics table
         */
        function create_metrics_table($metrics) {
            $content = '<table class="wp-list-table widefat fixed striped">';
            $content .= '<thead><tr>';
            $content .= '<th>Service</th>';
            $content .= '<th>Total Usage</th>';
            $content .= '<th>Total Cost</th>';
            $content .= '<th>Unique Users</th>';
            $content .= '</tr></thead><tbody>';

            foreach ($metrics as $metric) {
                $content .= '<tr>';
                $content .= '<td>' . esc_html($metric->metric_type) . '</td>';
                $content .= '<td>' . number_format($metric->total_value, 2) . '</td>';
                $content .= '<td>$' . number_format($metric->total_cost, 2) . '</td>';
                $content .= '<td>' . number_format($metric->unique_users) . '</td>';
                $content .= '</tr>';
            }

            $content .= '</tbody></table>';
            return $content;
        }
        ?>
    </div>
</div>

<style>
    .asap-dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    .asap-metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

    .asap-tabs {
        margin-top: 15px;
    }

    .asap-tab-headers {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .asap-tab-header {
        padding: 8px 16px;
        border: none;
        background: #f0f0f1;
        cursor: pointer;
        border-radius: 4px;
    }

    .asap-tab-header.active {
        background: #2271b1;
        color: #fff;
    }

    .asap-tab-content {
        display: none;
    }

    .asap-tab-content.active {
        display: block;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabHeaders = document.querySelectorAll('.asap-tab-header');
    const tabContents = document.querySelectorAll('.asap-tab-content');

    tabHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const tabId = header.dataset.tab;

            // Update active states
            tabHeaders.forEach(h => h.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            header.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script> 