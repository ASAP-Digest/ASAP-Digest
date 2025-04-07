<?php
/**
 * Session Management Trait for Better Auth Integration
 * 
 * @package ASAPDigest_Core
 * @created 04.03.25 | 05:32 PM PDT
 * @file-marker ASAP_Digest_Better_Auth_Session_Mgmt
 */

namespace ASAPDigest\Core\Traits;

use WP_Error;
use WP_User;
use function get_user_by;
use function get_user_meta;
use function update_user_meta;

if (!defined('ABSPATH')) {
    exit;
}

trait Session_Mgmt {
    /**
     * Create a new session for a user
     *
     * @param int|WP_User $user User ID or WP_User object
     * @return string|WP_Error Session token on success, WP_Error on failure
     */
    public function create_session($user) {
        if (!$user instanceof WP_User) {
            $user = get_user_by('id', $user);
            if (!$user) {
                return new WP_Error(
                    'invalid_user',
                    __('Invalid user provided for session creation.', 'asap-digest')
                );
            }
        }

        // Get Better Auth user ID
        $ba_user_id = get_user_meta($user->ID, 'better_auth_user_id', true);
        if (!$ba_user_id) {
            return new WP_Error(
                'no_better_auth_id',
                __('User not synced with Better Auth.', 'asap-digest')
            );
        }

        try {
            global $wpdb;
            
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Generate session token
            $token = wp_generate_password(64, false);
            
            // Calculate expiration (24 hours from now)
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Insert session
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'ba_sessions',
                [
                    'user_id' => $ba_user_id,
                    'token' => $token,
                    'expires_at' => $expires_at,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s']
            );

            if (!$inserted) {
                throw new \Exception('Failed to create session.');
            }

            // Store session token in user meta
            update_user_meta($user->ID, 'better_auth_session_token', $token);
            update_user_meta($user->ID, 'better_auth_last_login', current_time('mysql'));

            // Commit transaction
            $wpdb->query('COMMIT');
            
            return $token;

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'session_creation_failed',
                sprintf(
                    __('Failed to create session: %s', 'asap-digest'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Validate a session token
     *
     * @param string $token Session token
     * @return bool|WP_Error True if valid, WP_Error if not
     */
    public function validate_session_token($token) {
        global $wpdb;

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.metadata 
             FROM {$wpdb->prefix}ba_sessions s
             JOIN {$wpdb->prefix}ba_users u ON s.user_id = u.id
             WHERE s.token = %s 
             AND s.expires_at > NOW()",
            $token
        ));

        if (!$session) {
            return new WP_Error(
                'invalid_session',
                __('Invalid or expired session token.', 'asap-digest')
            );
        }

        return true;
    }

    /**
     * Destroy a session
     *
     * @param string $token Session token
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function destroy_session($token) {
        global $wpdb;

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Get session info
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, m.wp_user_id 
                 FROM {$wpdb->prefix}ba_sessions s
                 JOIN {$wpdb->prefix}ba_wp_user_map m ON s.user_id = m.ba_user_id
                 WHERE s.token = %s",
                $token
            ));

            if ($session) {
                // Delete session
                $deleted = $wpdb->delete(
                    $wpdb->prefix . 'ba_sessions',
                    ['token' => $token],
                    ['%s']
                );

                if ($deleted === false) {
                    throw new \Exception('Failed to delete session.');
                }

                // Clear session token from user meta
                delete_user_meta($session->wp_user_id, 'better_auth_session_token');
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            
            return true;

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'session_destruction_failed',
                sprintf(
                    __('Failed to destroy session: %s', 'asap-digest'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Clean up expired sessions
     *
     * @return int|WP_Error Number of sessions cleaned up, WP_Error on failure
     */
    public function cleanup_expired_sessions() {
        global $wpdb;

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Get expired sessions with user mappings
            $expired_sessions = $wpdb->get_results(
                "SELECT s.token, m.wp_user_id 
                 FROM {$wpdb->prefix}ba_sessions s
                 JOIN {$wpdb->prefix}ba_wp_user_map m ON s.user_id = m.ba_user_id
                 WHERE s.expires_at <= NOW()"
            );

            // Delete expired sessions
            $deleted = $wpdb->query(
                "DELETE FROM {$wpdb->prefix}ba_sessions 
                 WHERE expires_at <= NOW()"
            );

            if ($deleted === false) {
                throw new \Exception('Failed to delete expired sessions.');
            }

            // Clear session tokens from user meta
            foreach ($expired_sessions as $session) {
                delete_user_meta($session->wp_user_id, 'better_auth_session_token');
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            
            return $deleted;

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'cleanup_failed',
                sprintf(
                    __('Failed to cleanup expired sessions: %s', 'asap-digest'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Setup session cleanup hook
     */
    public function setup_session_cleanup_hook() {
        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('asap_digest_cleanup_sessions')) {
            wp_schedule_event(time(), 'daily', 'asap_digest_cleanup_sessions');
        }

        add_action('asap_digest_cleanup_sessions', [$this, 'cleanup_expired_sessions']);
    }
} 