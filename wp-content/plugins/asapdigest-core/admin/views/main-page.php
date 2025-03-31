<?php
/**
 * ASAP Digest Main Admin Page
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 */

if (!defined('ABSPATH')) {
    exit;
}

$database = ASAPDigest\Core\ASAP_Digest_Core::get_instance()->get_database();
$settings = $database->get_digest_settings();
$stats = $database->get_digest_stats();
?>

<div class="wrap asap-digest-admin">
    <h1><?php _e('ASAP Digest', 'asap-digest'); ?></h1>

    <div class="asap-digest-overview">
        <div class="asap-digest-card">
            <h2><?php _e('Digest Overview', 'asap-digest'); ?></h2>
            <div class="asap-digest-stats">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total Digests Sent', 'asap-digest'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['total_digests_sent']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total Posts Included', 'asap-digest'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['total_posts_included']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Last Digest Sent', 'asap-digest'); ?></span>
                    <span class="stat-value">
                        <?php echo $stats['last_digest_date'] ? esc_html(date_i18n(get_option('date_format'), strtotime($stats['last_digest_date']))) : __('Never', 'asap-digest'); ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Next Digest', 'asap-digest'); ?></span>
                    <span class="stat-value">
                        <?php echo $stats['next_digest_date'] ? esc_html(date_i18n(get_option('date_format'), strtotime($stats['next_digest_date']))) : __('Not Scheduled', 'asap-digest'); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="asap-digest-card">
            <h2><?php _e('Current Settings', 'asap-digest'); ?></h2>
            <div class="asap-digest-settings-overview">
                <div class="setting-item">
                    <span class="setting-label"><?php _e('Frequency', 'asap-digest'); ?></span>
                    <span class="setting-value"><?php echo esc_html(ucfirst($settings['frequency'])); ?></span>
                </div>
                <div class="setting-item">
                    <span class="setting-label"><?php _e('Send Time', 'asap-digest'); ?></span>
                    <span class="setting-value"><?php echo esc_html($settings['send_time']); ?></span>
                </div>
                <div class="setting-item">
                    <span class="setting-label"><?php _e('Max Posts per Digest', 'asap-digest'); ?></span>
                    <span class="setting-value"><?php echo esc_html($settings['max_posts']); ?></span>
                </div>
                <div class="setting-item">
                    <span class="setting-label"><?php _e('Categories', 'asap-digest'); ?></span>
                    <span class="setting-value">
                        <?php
                        if (empty($settings['categories'])) {
                            _e('All Categories', 'asap-digest');
                        } else {
                            $category_names = array_map(function($cat_id) {
                                $cat = get_category($cat_id);
                                return $cat ? $cat->name : '';
                            }, $settings['categories']);
                            echo esc_html(implode(', ', array_filter($category_names)));
                        }
                        ?>
                    </span>
                </div>
            </div>
            <p class="settings-link">
                <a href="<?php echo esc_url(admin_url('admin.php?page=asap-digest-settings')); ?>" class="button button-primary">
                    <?php _e('Manage Settings', 'asap-digest'); ?>
                </a>
            </p>
        </div>

        <div class="asap-digest-card">
            <h2><?php _e('Quick Actions', 'asap-digest'); ?></h2>
            <div class="asap-digest-actions">
                <button type="button" class="button" id="send-test-digest">
                    <?php _e('Send Test Digest', 'asap-digest'); ?>
                </button>
                <button type="button" class="button" id="preview-next-digest">
                    <?php _e('Preview Next Digest', 'asap-digest'); ?>
                </button>
                <button type="button" class="button" id="view-detailed-stats">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=asap-digest-stats')); ?>">
                        <?php _e('View Detailed Stats', 'asap-digest'); ?>
                    </a>
                </button>
            </div>
        </div>
    </div>
</div> 