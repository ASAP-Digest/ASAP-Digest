<?php
/**
 * Better Auth Bootstrap
 * 
 * Initializes the Better Auth integration components
 * 
 * @package ASAPDigest_Core
 * @created 05.16.25 | 03:41 PM PDT
 * @file-marker ASAP_Digest_Auth_Bootstrap
 */

namespace ASAPDigest\Core\Auth;

use ASAPDigest\Core\API\Controllers\ASAP_Digest_Auth_Webhook_Controller;

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Load configuration
require_once dirname(__FILE__) . '/class-auth-config.php';
require_once dirname(__FILE__) . '/class-auth-sync.php';
require_once dirname(__FILE__) . '/class-user-actions.php';

// Initialize config
ASAP_Digest_Auth_Config::init();

// Register webhook endpoint
add_action('rest_api_init', function() {
    $webhook_controller = new ASAP_Digest_Auth_Webhook_Controller();
    $webhook_controller->register_routes();
});

// Add hooks for user sync
add_action('wp_login', function($user_login, $user) {
    ASAP_Digest_Auth_Sync::handle_login_auto_sync($user_login, $user);
}, 10, 2);

add_action('set_user_role', function($user_id, $role, $old_roles) {
    if (ASAP_Digest_Auth_Sync::should_auto_sync_user($user_id)) {
        // Check if user is already synced
        $better_auth_id = get_user_meta($user_id, 'better_auth_user_id', true);
        if (!$better_auth_id) {
            // Sync user with Better Auth
            ASAP_Digest_Auth_Sync::sync_wp_user_to_better_auth($user_id);
        }
    }
}, 10, 3);

// Add init hook for auto-sync
add_action('init', function() {
    // Check if user is logged in
    $current_user = wp_get_current_user();
    if ($current_user->ID && ASAP_Digest_Auth_Sync::should_auto_sync_user($current_user)) {
        // Check if user is already synced
        $better_auth_id = get_user_meta($current_user->ID, 'better_auth_user_id', true);
        if (!$better_auth_id) {
            // Sync user with Better Auth
            $result = ASAP_Digest_Auth_Sync::sync_wp_user_to_better_auth($current_user->ID);
            if (!is_wp_error($result)) {
                $better_auth_id = $result['ba_user_id'];
            }
        }

        // Ensure session is active
        if ($better_auth_id && !get_user_meta($current_user->ID, 'better_auth_session_token', true)) {
            // Create session
            // Implementation would be here
        }
    }
}, 20);

// Add admin column for Better Auth sync status
add_filter('manage_users_columns', function($columns) {
    $columns['better_auth_sync'] = __('Better Auth');
    return $columns;
});

add_action('manage_users_custom_column', function($value, $column_name, $user_id) {
    if ($column_name !== 'better_auth_sync') {
        return $value;
    }

    $better_auth_id = get_user_meta($user_id, 'better_auth_user_id', true);
    $sync_status = $better_auth_id ? 'synced' : 'not-synced';
    $last_sync = get_user_meta($user_id, 'better_auth_last_sync', true);

    // Output HTML for the column
    ob_start();
    ?>
    <div class="better-auth-status">
        <?php echo \ASAPDigest\Admin\ASAP_Digest_Admin_UI::create_status_indicator(
            $better_auth_id ? 'good' : 'warning',
            $better_auth_id ? __('Synced') : __('Not Synced')
        ); ?>
        
        <?php if ($last_sync): ?>
            <small class="last-sync">
                <?php printf(__('Last: %s ago'), human_time_diff(strtotime($last_sync))); ?>
            </small>
        <?php endif; ?>

        <div class="row-actions">
            <?php if ($better_auth_id): ?>
                <span class="unsync">
                    <a href="#" class="unsync-user" 
                       data-user-id="<?php echo esc_attr($user_id); ?>"
                       data-nonce="<?php echo wp_create_nonce('unsync_user_' . $user_id); ?>">
                        <?php _e('Unsync'); ?>
                    </a>
                </span>
            <?php else: ?>
                <span class="sync">
                    <a href="#" class="sync-user" 
                       data-user-id="<?php echo esc_attr($user_id); ?>"
                       data-nonce="<?php echo wp_create_nonce('sync_user_' . $user_id); ?>">
                        <?php _e('Sync Now'); ?>
                    </a>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!isset($GLOBALS['better_auth_column_js_added'])): ?>
        <script>
        jQuery(document).ready(function($) {
            $('.sync-user').on('click', function(e) {
                e.preventDefault();
                var link = $(this);
                var userId = link.data('user-id');
                var nonce = link.data('nonce');
                var statusContainer = link.closest('.better-auth-status');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'asap_sync_single_user',
                        user_id: userId,
                        nonce: nonce
                    },
                    beforeSend: function() {
                        statusContainer.addClass('asap-loading');
                        link.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            statusContainer.find('.asap-status-indicator')
                                .removeClass('asap-status-warning')
                                .addClass('asap-status-good')
                                .find('span').text('Synced');
                            
                            if (response.data.last_sync) {
                                var lastSyncText = sprintf(
                                    '<?php _e('Last: %s ago'); ?>', 
                                    response.data.last_sync
                                );
                                statusContainer.find('.last-sync').text(lastSyncText);
                            }

                            // Replace sync button with unsync button
                            var unsyncButton = $('<span class="unsync"><a href="#" class="unsync-user" data-user-id="' + userId + '" data-nonce="' +
                                wp_create_nonce('unsync_user_' + userId) + '"><?php _e('Unsync'); ?></a></span>');
                            link.closest('.row-actions').html(unsyncButton);
                            initUnsyncHandlers();
                        } else {
                            alert(response.data.message || 'Error syncing user');
                        }
                    },
                    error: function() {
                        alert('Error communicating with server');
                    },
                    complete: function() {
                        statusContainer.removeClass('asap-loading');
                        link.prop('disabled', false);
                    }
                });
            });

            function initUnsyncHandlers() {
                $('.unsync-user').off('click').on('click', function(e) {
                    e.preventDefault();
                    var link = $(this);
                    var userId = link.data('user-id');
                    var nonce = link.data('nonce');
                    var statusContainer = link.closest('.better-auth-status');
                    
                    if (!confirm('<?php _e('Are you sure you want to unsync this user from Better Auth?'); ?>')) {
                        return;
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'asap_unsync_single_user',
                            user_id: userId,
                            nonce: nonce
                        },
                        beforeSend: function() {
                            statusContainer.addClass('asap-loading');
                            link.prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                statusContainer.find('.asap-status-indicator')
                                    .removeClass('asap-status-good')
                                    .addClass('asap-status-warning')
                                    .find('span').text('Not Synced');
                                
                                statusContainer.find('.last-sync').remove();
                                
                                // Replace unsync button with sync button
                                var syncButton = $('<span class="sync"><a href="#" class="sync-user" data-user-id="' + userId + '" data-nonce="' +
                                    wp_create_nonce('sync_user_' + userId) + '"><?php _e('Sync Now'); ?></a></span>');
                                link.closest('.row-actions').html(syncButton);
                            } else {
                                alert(response.data.message || 'Error unsyncing user');
                            }
                        },
                        error: function() {
                            alert('Error communicating with server');
                        },
                        complete: function() {
                            statusContainer.removeClass('asap-loading');
                            link.prop('disabled', false);
                        }
                    });
                });
            }

            // Initialize unsync handlers
            initUnsyncHandlers();
        });
        </script>
        <?php $GLOBALS['better_auth_column_js_added'] = true; ?>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}, 10, 3);

// Register AJAX handlers
add_action('wp_ajax_asap_sync_single_user', function() {
    // Verify permissions
    if (!current_user_can('edit_users')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // Verify nonce
    $user_id = intval($_POST['user_id'] ?? 0);
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'sync_user_' . $user_id)) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Sync user
    $result = ASAP_Digest_Auth_Sync::sync_wp_user_to_better_auth($user_id);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    
    // Update last sync time
    $now = current_time('mysql');
    update_user_meta($user_id, 'better_auth_last_sync', $now);
    
    wp_send_json_success([
        'message' => 'User synced successfully',
        'last_sync' => human_time_diff(strtotime($now))
    ]);
});

add_action('wp_ajax_asap_unsync_single_user', function() {
    // Verify permissions
    if (!current_user_can('edit_users')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // Verify nonce
    $user_id = intval($_POST['user_id'] ?? 0);
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'unsync_user_' . $user_id)) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Unsync user
    $result = ASAP_Digest_Auth_Sync::unsync_wp_user_from_better_auth($user_id);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    
    wp_send_json_success([
        'message' => 'User unsynced successfully'
    ]);
}); 