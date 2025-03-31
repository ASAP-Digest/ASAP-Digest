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
</style> 