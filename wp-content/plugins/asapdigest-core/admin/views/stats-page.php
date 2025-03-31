<?php
/**
 * ASAP Digest Stats Page
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 */

if (!defined('ABSPATH')) {
    exit;
}

$database = ASAPDigest\Core\ASAP_Digest_Core::get_instance()->get_database();
$usage_tracker = ASAPDigest\Core\ASAP_Digest_Core::get_instance()->get_usage_tracker();

$stats = $database->get_digest_stats();
$usage_stats = $usage_tracker->get_stats();
$service_costs = $usage_tracker->get_total_service_costs('month');
?>

<div class="wrap asap-digest-admin">
    <h1><?php _e('ASAP Digest Statistics', 'asap-digest'); ?></h1>

    <div class="asap-digest-stats-overview">
        <div class="asap-digest-card">
            <h2><?php _e('Digest Performance', 'asap-digest'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Metric', 'asap-digest'); ?></th>
                        <th><?php _e('Value', 'asap-digest'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Total Digests Sent', 'asap-digest'); ?></td>
                        <td><?php echo esc_html($stats['total_digests_sent']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Total Posts Included', 'asap-digest'); ?></td>
                        <td><?php echo esc_html($stats['total_posts_included']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Average Posts per Digest', 'asap-digest'); ?></td>
                        <td>
                            <?php
                            if ($stats['total_digests_sent'] > 0) {
                                echo esc_html(round($stats['total_posts_included'] / $stats['total_digests_sent'], 2));
                            } else {
                                echo '0';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Last Digest Date', 'asap-digest'); ?></td>
                        <td>
                            <?php
                            echo $stats['last_digest_date'] 
                                ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats['last_digest_date'])))
                                : __('Never', 'asap-digest');
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="asap-digest-card">
            <h2><?php _e('Usage Metrics (This Month)', 'asap-digest'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Service', 'asap-digest'); ?></th>
                        <th><?php _e('Usage', 'asap-digest'); ?></th>
                        <th><?php _e('Users', 'asap-digest'); ?></th>
                        <th><?php _e('Cost', 'asap-digest'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($service_costs)) : ?>
                        <tr>
                            <td colspan="4"><?php _e('No usage data available for this month.', 'asap-digest'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($service_costs as $metric) : ?>
                            <tr>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $metric->metric_type))); ?></td>
                                <td><?php echo esc_html(number_format($metric->total_value)); ?></td>
                                <td><?php echo esc_html($metric->unique_users); ?></td>
                                <td><?php echo esc_html(number_format($metric->total_cost, 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="asap-digest-card">
            <h2><?php _e('Recent Events', 'asap-digest'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Event', 'asap-digest'); ?></th>
                        <th><?php _e('User', 'asap-digest'); ?></th>
                        <th><?php _e('Date', 'asap-digest'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usage_stats['events'])) : ?>
                        <tr>
                            <td colspan="3"><?php _e('No events recorded yet.', 'asap-digest'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php 
                        $events = array_slice($usage_stats['events'], -10);
                        foreach ($events as $event) : 
                            $user = get_userdata($event['user_id']);
                        ?>
                            <tr>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $event['name']))); ?></td>
                                <td>
                                    <?php 
                                    echo $user ? esc_html($user->display_name) : __('System', 'asap-digest');
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo esc_html(date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'),
                                        strtotime($event['timestamp'])
                                    )); 
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if (!empty($usage_stats['events'])) : ?>
                <p class="description">
                    <?php 
                    printf(
                        __('Showing last 10 events. Total events recorded: %d', 'asap-digest'),
                        $usage_stats['total_events']
                    ); 
                    ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="asap-digest-card">
            <h2><?php _e('Export Data', 'asap-digest'); ?></h2>
            <p>
                <button type="button" class="button" id="export-stats-csv">
                    <?php _e('Export to CSV', 'asap-digest'); ?>
                </button>
                <button type="button" class="button" id="export-stats-json">
                    <?php _e('Export to JSON', 'asap-digest'); ?>
                </button>
            </p>
            <p class="description">
                <?php _e('Export detailed statistics for further analysis.', 'asap-digest'); ?>
            </p>
        </div>
    </div>
</div> 