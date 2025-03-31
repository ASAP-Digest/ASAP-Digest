<?php
/**
 * ASAP Digest Settings Page
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 */

if (!defined('ABSPATH')) {
    exit;
}

$database = ASAPDigest\Core\ASAP_Digest_Core::get_instance()->get_database();
$settings = $database->get_digest_settings();
$better_auth = ASAPDigest\Core\ASAP_Digest_Core::get_instance()->get_better_auth();
$auth_status = $better_auth->get_auth_status();
?>

<div class="wrap asap-digest-admin">
    <h1><?php _e('ASAP Digest Settings', 'asap-digest'); ?></h1>

    <div class="asap-digest-settings">
        <form id="asap-digest-settings-form" method="post">
            <?php wp_nonce_field('asap_digest_settings', 'asap_digest_nonce'); ?>

            <div class="asap-digest-card">
                <h2><?php _e('Digest Configuration', 'asap-digest'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="frequency"><?php _e('Digest Frequency', 'asap-digest'); ?></label>
                        </th>
                        <td>
                            <select name="frequency" id="frequency">
                                <option value="daily" <?php selected($settings['frequency'], 'daily'); ?>>
                                    <?php _e('Daily', 'asap-digest'); ?>
                                </option>
                                <option value="weekly" <?php selected($settings['frequency'], 'weekly'); ?>>
                                    <?php _e('Weekly', 'asap-digest'); ?>
                                </option>
                                <option value="monthly" <?php selected($settings['frequency'], 'monthly'); ?>>
                                    <?php _e('Monthly', 'asap-digest'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="send_time"><?php _e('Send Time', 'asap-digest'); ?></label>
                        </th>
                        <td>
                            <input type="time" 
                                   name="send_time" 
                                   id="send_time" 
                                   value="<?php echo esc_attr($settings['send_time']); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Time is in 24-hour format and your server\'s timezone.', 'asap-digest'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_posts"><?php _e('Maximum Posts', 'asap-digest'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="max_posts" 
                                   id="max_posts" 
                                   value="<?php echo esc_attr($settings['max_posts']); ?>"
                                   min="1" 
                                   max="50" 
                                   class="small-text">
                            <p class="description">
                                <?php _e('Maximum number of posts to include in each digest (1-50).', 'asap-digest'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Categories', 'asap-digest'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <?php _e('Select categories to include in digests', 'asap-digest'); ?>
                                </legend>
                                <?php
                                $categories = get_categories(['hide_empty' => false]);
                                foreach ($categories as $category) :
                                ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="categories[]" 
                                               value="<?php echo esc_attr($category->term_id); ?>"
                                               <?php checked(in_array($category->term_id, $settings['categories'])); ?>>
                                        <?php echo esc_html($category->name); ?>
                                    </label><br>
                                <?php endforeach; ?>
                                <p class="description">
                                    <?php _e('Leave all unchecked to include posts from all categories.', 'asap-digest'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="asap-digest-card">
                <h2><?php _e('Authentication Settings', 'asap-digest'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="session_length"><?php _e('Session Length', 'asap-digest'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="session_length" 
                                   id="session_length" 
                                   value="<?php echo esc_attr($auth_status['settings']['session_length']); ?>"
                                   min="1800" 
                                   max="86400" 
                                   class="small-text">
                            <p class="description">
                                <?php _e('Session length in seconds (30 minutes to 24 hours).', 'asap-digest'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="refresh_token_length"><?php _e('Refresh Token Length', 'asap-digest'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="refresh_token_length" 
                                   id="refresh_token_length" 
                                   value="<?php echo esc_attr($auth_status['settings']['refresh_token_length']); ?>"
                                   min="86400" 
                                   max="2592000" 
                                   class="small-text">
                            <p class="description">
                                <?php _e('Refresh token length in seconds (24 hours to 30 days).', 'asap-digest'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_sessions"><?php _e('Maximum Sessions', 'asap-digest'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="max_sessions" 
                                   id="max_sessions" 
                                   value="<?php echo esc_attr($auth_status['settings']['max_sessions']); ?>"
                                   min="1" 
                                   max="10" 
                                   class="small-text">
                            <p class="description">
                                <?php _e('Maximum number of concurrent sessions per user (1-10).', 'asap-digest'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <input type="submit" 
                       name="submit" 
                       id="submit" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Save Changes', 'asap-digest'); ?>">
            </p>
        </form>
    </div>
</div> 