<?php
/**
 * Active Sessions Controller for Server-to-Server Authentication
 * 
 * @package ASAPDigest_Core
 * @created 05.02.25 | 05:45 PM PDT
 * @file-marker Active_Sessions_Controller
 */

namespace ASAPDigest\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controller for handling server-to-server session validation without relying on cookies
 */
class Active_Sessions_Controller extends ASAP_Digest_REST_Base {

    /**
     * Register routes for the controller
     */
    public function register_routes() {
        error_log('[ASAP S2S] Registering Active Sessions Controller routes');
        register_rest_route(
            'asap/v1',
            '/get-active-sessions',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'get_active_sessions' ),
                'permission_callback' => array( $this, 'validate_server_request' ),
            )
        );
        error_log('[ASAP S2S] Active Sessions Controller routes registered successfully');
    }

    /**
     * Validate the server-to-server request using the shared secret
     * 
     * @param \WP_REST_Request $request The REST request.
     * @return bool True if authorized, false otherwise.
     */
    public function validate_server_request( $request ) {
        error_log('[ASAP S2S] Validating server-to-server request');
        
        // Get the shared secret from WordPress configuration
        $sync_secret = defined( 'BETTER_AUTH_SECRET' ) ? constant('BETTER_AUTH_SECRET') : '';
        if ( empty( $sync_secret ) ) {
            error_log( '[ASAP S2S] Error: BETTER_AUTH_SECRET not defined in wp-config.php' );
            return false;
        }
        error_log('[ASAP S2S] Found BETTER_AUTH_SECRET with length: ' . strlen($sync_secret));

        // Get the secret from the request header
        // Check both header naming conventions (case sensitivity can matter)
        $request_secret = $request->get_header( 'X-ASAP-Sync-Secret' );
        if ( empty( $request_secret ) ) {
            // Try alternative header names
            $request_secret = $request->get_header( 'x-asap-sync-secret' );
            if ( empty( $request_secret ) ) {
                error_log( '[ASAP S2S] Error: Missing X-ASAP-Sync-Secret header in server request (checked both case variants)' );
                return false;
            }
            error_log('[ASAP S2S] Found x-asap-sync-secret header (lowercase variant)');
        } else {
            error_log('[ASAP S2S] Found X-ASAP-Sync-Secret header (standard format)');
        }
        error_log('[ASAP S2S] Request includes sync secret header with length: ' . strlen($request_secret));

        // Validate the secret
        if ( $request_secret !== $sync_secret ) {
            error_log( '[ASAP S2S] Error: Invalid sync secret provided in server request. Secrets do not match.' );
            return false;
        }

        // Log successful validation
        $client_ip = $request->get_header( 'x-asap-client-ip' );
        $request_source = $request->get_header( 'x-asap-request-source' );
        error_log( sprintf( 
            '[ASAP S2S] Server-to-server request validated successfully. Source: %s, Client IP: %s', 
            $request_source ? $request_source : 'unknown',
            $client_ip ? $client_ip : 'unknown'
        ));

        return true;
    }

    /**
     * Get all active WordPress sessions
     * This endpoint does NOT rely on cookies; it checks for active sessions in the database
     * 
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response The REST response.
     */
    public function get_active_sessions( $request ) {
        global $wpdb;
        
        error_log('[ASAP S2S] Starting active sessions retrieval');

        // Get request parameters
        $params = $request->get_json_params();
        $request_source = isset( $params['requestSource'] ) ? sanitize_text_field( $params['requestSource'] ) : 'unknown';
        $timestamp = isset( $params['timestamp'] ) ? intval( $params['timestamp'] ) : 0;
        
        // Log the request details
        error_log( sprintf( 
            '[ASAP S2S] Processing active sessions request. Source: %s, Timestamp: %s, Raw Params: %s', 
            $request_source,
            $timestamp ? date( 'Y-m-d H:i:s', $timestamp / 1000 ) : 'invalid',
            json_encode($params)
        ));

        // Query the database for users with active sessions
        error_log('[ASAP S2S] Querying database for users with session_tokens meta');
        $query = $wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
            WHERE meta_key = %s AND meta_value LIKE %s 
            ORDER BY umeta_id DESC LIMIT 10",
            'session_tokens',
            '%'
        );
        error_log('[ASAP S2S] Running query: ' . $query);
        
        $active_user_ids = $wpdb->get_col($query);
        
        if ( empty( $active_user_ids ) ) {
            error_log( '[ASAP S2S] No active WordPress sessions found in database query.' );
            return rest_ensure_response( array(
                'success' => false,
                'error' => 'no_active_wp_sessions'
            ));
        }

        // Log found users
        error_log( sprintf( 
            '[ASAP S2S] Found %d potentially active users with IDs: %s', 
            count( $active_user_ids ),
            implode(', ', $active_user_ids)
        ));

        // Get auto-sync roles setting
        $auto_sync_roles = get_option( 'asap_better_auth_auto_sync_roles', array( 'administrator' ) );
        error_log('[ASAP S2S] Auto-sync roles configuration: ' . implode(', ', $auto_sync_roles));
        
        // Prepare active session data for users who should be synced based on roles
        $active_sessions = array();
        
        foreach ( $active_user_ids as $user_id ) {
            error_log('[ASAP S2S] Processing user ID: ' . $user_id);
            
            $user = get_userdata( $user_id );
            
            if ( ! $user ) {
                error_log('[ASAP S2S] User ID ' . $user_id . ' not found in WordPress');
                continue;
            }
            
            error_log('[ASAP S2S] User found: ' . $user->user_login . ' with roles: ' . implode(', ', $user->roles));
            
            // Verify this user has active sessions
            $sessions = get_user_meta( $user_id, 'session_tokens', true );
            
            if ( empty( $sessions ) || ! is_array( $sessions ) ) {
                error_log('[ASAP S2S] User ' . $user->user_login . ' has no active session tokens');
                continue;
            }
            
            error_log('[ASAP S2S] User ' . $user->user_login . ' has ' . count($sessions) . ' session tokens');
            
            // Check if any sessions are recent/active
            $has_recent_session = false;
            $expiration_timestamps = [];
            
            foreach ( $sessions as $session ) {
                if ( isset( $session['expiration'] ) ) {
                    $expiration_timestamps[] = date('Y-m-d H:i:s', $session['expiration']);
                    if ( $session['expiration'] > time() ) {
                        $has_recent_session = true;
                        error_log('[ASAP S2S] User ' . $user->user_login . ' has an active session expiring at: ' . date('Y-m-d H:i:s', $session['expiration']));
                    }
                }
            }
            
            if ( ! $has_recent_session ) {
                error_log('[ASAP S2S] User ' . $user->user_login . ' has no recent/valid sessions. Expirations: ' . implode(', ', $expiration_timestamps));
                continue;
            }
            
            // Check if user should be synced based on role settings
            $should_sync = false;
            $matching_roles = [];
            
            foreach ( $user->roles as $role ) {
                if ( in_array( $role, $auto_sync_roles ) ) {
                    $should_sync = true;
                    $matching_roles[] = $role;
                }
            }
            
            if ( ! $should_sync ) {
                error_log( sprintf( 
                    '[ASAP S2S] User %s has role(s) %s that is not in auto-sync roles %s. Skipping.', 
                    $user->user_login, 
                    implode(', ', $user->roles),
                    implode(', ', $auto_sync_roles)
                ));
                continue;
            }
            
            error_log('[ASAP S2S] User ' . $user->user_login . ' qualifies for sync based on roles: ' . implode(', ', $matching_roles));
            
            // If we get here, the user has active sessions and should be synced
            $userdata = array(
                'wpUserId'    => $user_id,
                'username'    => $user->user_login,
                'email'       => $user->user_email,
                'displayName' => $user->display_name,
                'firstName'   => get_user_meta( $user_id, 'first_name', true ),
                'lastName'    => get_user_meta( $user_id, 'last_name', true ),
                'roles'       => $user->roles,
                'avatarUrl'   => get_avatar_url( $user_id, array( 'size' => 96 ) ),
                'metadata'    => array(
                    'registered' => $user->user_registered,
                    'nicename'   => $user->user_nicename,
                ),
            );
            
            error_log('[ASAP S2S] Adding user data for ' . $user->user_login . ' to response: ' . json_encode($userdata));
            $active_sessions[] = $userdata;
        }
        
        if ( empty( $active_sessions ) ) {
            error_log( '[ASAP S2S] No eligible active sessions found after filtering by role.' );
            return rest_ensure_response( array(
                'success' => false,
                'error' => 'no_eligible_active_sessions'
            ));
        }
        
        error_log( sprintf( 
            '[ASAP S2S] Returning %d active sessions data (users: %s)', 
            count( $active_sessions ),
            implode(', ', array_column($active_sessions, 'username'))
        ));
        
        return rest_ensure_response( array(
            'success' => true,
            'activeSessions' => $active_sessions,
            'timestamp' => time(),
        ));
    }
} 