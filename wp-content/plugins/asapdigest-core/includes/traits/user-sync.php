<?php
/**
 * User Synchronization Trait for Better Auth Integration
 * 
 * @package ASAPDigest_Core
 * @created 04.03.25 | 05:32 PM PDT
 * @file-marker ASAP_Digest_Better_Auth_User_Sync
 */

namespace ASAPDigest\Core\Traits;

use WP_Error;
use WP_User;
use function get_user_by;
use function get_user_meta;
use function update_user_meta;
use function wp_remote_post;

if (!defined('ABSPATH')) {
    exit;
}

trait User_Sync {
    /**
     * Maximum number of sync retries
     * @var int
     */
    private $max_sync_retries = 3;

    /**
     * Sync WordPress user to Better Auth
     *
     * @param int|WP_User $user User ID or WP_User object
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function sync_user_to_better_auth($user) {
        if (!$user instanceof WP_User) {
            $user = get_user_by('id', $user);
            if (!$user) {
                return new WP_Error(
                    'invalid_user',
                    __('Invalid user provided for sync.', 'asap-digest')
                );
            }
        }

        // Get existing Better Auth ID if any
        $ba_user_id = get_user_meta($user->ID, 'better_auth_user_id', true);
        
        try {
            global $wpdb;
            
            // Start transaction
            $wpdb->query('START TRANSACTION');

            $user_data = [
                'email' => $user->user_email,
                'username' => $user->user_login,
                'name' => $user->display_name,
                'metadata' => json_encode([
                    'wp_user_id' => $user->ID,
                    'roles' => $user->roles,
                    'registered' => $user->user_registered,
                    'locale' => get_user_locale($user->ID)
                ])
            ];

            if ($ba_user_id) {
                // Update existing Better Auth user
                $updated = $wpdb->update(
                    $wpdb->prefix . 'ba_users',
                    $user_data,
                    ['id' => $ba_user_id],
                    ['%s', '%s', '%s', '%s'],
                    ['%s']
                );

                if ($updated === false) {
                    throw new \Exception('Failed to update Better Auth user.');
                }
            } else {
                // Create new Better Auth user
                
                // 1. Generate a unique string ID (UUID)
                if (!function_exists('wp_generate_uuid4')) {
                    require_once ABSPATH . 'wp-includes/compat.php';
                }
                $ba_user_id = wp_generate_uuid4(); 

                // ---> ADD CHECK FOR UUID FAILURE <---
                if (!$ba_user_id) {
                    error_log('[ASAP Digest Sync Error] wp_generate_uuid4() failed to generate a UUID.');
                    throw new \Exception('Failed to generate UUID for Better Auth user.');
                }
                // ---> END CHECK <---

                // 2. Prepare user data *without* the ID field initially
                // $user_data['id'] = $ba_user_id; // REMOVED - ID will be handled by Better Auth/DB?
                $user_data['created_at'] = current_time('mysql');
                // $user_data now contains: email, username, name, metadata, created_at
                
                // ---- START DEBUG LOGGING ----
                error_log('[ASAP Digest Sync Debug] Attempting insert into ba_users. Data (excluding ID): ' . print_r($user_data, true));
                // ---- END DEBUG LOGGING ----

                // 3. Insert into ba_users (without explicitly providing ID)
                $inserted = $wpdb->insert(
                    $wpdb->prefix . 'ba_users',
                    $user_data,
                    // Adjusted format specifiers (removed one %s for id)
                    ['%s', '%s', '%s', '%s', '%s'] 
                );

                if (!$inserted) {
                    // Optionally log the $user_data and $wpdb->last_error here for debugging
                    error_log('[ASAP Digest Sync Error] Failed to insert into ba_users. Data: ' . print_r($user_data, true));
                    error_log('[ASAP Digest Sync Error] DB Error: ' . $wpdb->last_error);
                    throw new \Exception('Failed to create Better Auth user.');
                }

                // $ba_user_id is already set from wp_generate_uuid4()
                // Remove: $ba_user_id = $wpdb->insert_id;

                // 4. Create user mapping using the generated string ID
                $mapped = $wpdb->insert(
                    $wpdb->prefix . 'ba_wp_user_map',
                    [
                        'wp_user_id' => $user->ID,
                        'ba_user_id' => $ba_user_id, // Use the generated string ID
                        'created_at' => current_time('mysql')
                    ],
                    // Correct format specifiers: %d for wp_user_id, %s for ba_user_id
                    ['%d', '%s', '%s'] 
                );

                if (!$mapped) {
                     // Optionally log the error
                    error_log('[ASAP Digest Sync Error] Failed to insert into ba_wp_user_map. WP ID: ' . $user->ID . ', BA ID: ' . $ba_user_id);
                    error_log('[ASAP Digest Sync Error] DB Error: ' . $wpdb->last_error);
                    throw new \Exception('Failed to create user mapping.');
                }

                // Store Better Auth ID in user meta
                update_user_meta($user->ID, 'better_auth_user_id', $ba_user_id);
            }

            // Update metadata snapshot
            update_user_meta($user->ID, 'better_auth_metadata_snapshot', $user_data['metadata']);

            // Commit transaction
            $wpdb->query('COMMIT');
            
            // --- BEGIN ADDED CODE: Send data to SvelteKit endpoint ---
            $sync_url = defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development' 
                        ? 'https://localhost:5173/api/auth/sync' 
                        : 'https://asapdigest.com/api/auth/sync'; // Replace with actual production URL if different

            // --- MODIFIED: Retrieve ba_user_id directly from map table ---
            $retrieved_ba_user_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ba_user_id FROM {$wpdb->prefix}ba_wp_user_map WHERE wp_user_id = %d LIMIT 1",
                    $user->ID
                )
            );
            // --- END MODIFIED ---

            if ($retrieved_ba_user_id) { // Ensure we have the SK user ID from the DB query
                $post_body = json_encode([
                    'wpUserId' => $user->ID,
                    'skUserId' => $retrieved_ba_user_id // Use the ID retrieved from the DB
                ]);

                $response = wp_remote_post($sync_url, [
                    'method'    => 'POST',
                    'headers'   => ['Content-Type' => 'application/json; charset=utf-8'],
                    'body'      => $post_body,
                    'timeout'   => 15, // seconds
                    'blocking'  => false // Don't wait for the response to avoid blocking WP profile update
                ]);

                // Optional: Log if the request scheduling failed (is_wp_error)
                if (is_wp_error($response)) {
                    error_log('[ASAP Digest Sync Error] Failed to trigger SvelteKit sync endpoint: ' . $response->get_error_message());
                } else {
                    // Optional: Log success if needed for debugging
                     error_log('[ASAP Digest Sync Debug] Triggered SvelteKit sync POST for wpUserId: ' . $user->ID . ', skUserId: ' . $retrieved_ba_user_id);
                }
            } else {
                 error_log('[ASAP Digest Sync Error] Cannot trigger SvelteKit sync: Missing ba_user_id in map table for wpUserId: ' . $user->ID);
            }
            // --- END ADDED CODE ---

            return true;

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'sync_failed',
                sprintf(
                    __('Failed to sync user to Better Auth: %s', 'asap-digest'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Retry user sync with exponential backoff
     *
     * @param int|WP_User $user User ID or WP_User object
     * @param int $attempt Current attempt number
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function retry_user_sync($user, $attempt = 1) {
        if ($attempt > $this->max_sync_retries) {
            return new WP_Error(
                'max_retries_exceeded',
                __('Maximum sync retry attempts exceeded.', 'asap-digest')
            );
        }

        $result = $this->sync_user_to_better_auth($user);

        if (is_wp_error($result)) {
            // Wait with exponential backoff before retrying
            $wait_seconds = pow(2, $attempt - 1);
            sleep($wait_seconds);
            
            return $this->retry_user_sync($user, $attempt + 1);
        }

        return $result;
    }

    /**
     * Setup user sync hooks
     */
    public function setup_user_sync_hooks() {
        // Sync on user creation/update
        add_action('user_register', [$this, 'handle_user_sync']);
        add_action('profile_update', [$this, 'handle_user_sync']);
        add_action('updated_user_meta', [$this, 'handle_user_meta_sync'], 10, 4);
    }

    /**
     * Handle user sync events
     *
     * @param int $user_id User ID
     */
    public function handle_user_sync($user_id) {
        $this->retry_user_sync($user_id);
    }

    /**
     * Handle user meta sync events
     *
     * @param int $meta_id Meta ID
     * @param int $user_id User ID
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     */
    public function handle_user_meta_sync($meta_id, $user_id, $meta_key, $meta_value) {
        // Only sync on relevant meta changes
        $sync_meta_keys = [
            'nickname',
            'first_name',
            'last_name',
            'description',
            'locale',
            'better_auth_subscription_status',
            'better_auth_subscription_plan'
        ];

        if (in_array($meta_key, $sync_meta_keys)) {
            $this->retry_user_sync($user_id);
        }
    }
} 