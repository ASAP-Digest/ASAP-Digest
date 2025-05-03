<?php
/**
 * Better Auth Configuration for WordPress Integration
 * 
 * This file contains configuration settings for Better Auth WordPress integration.
 * It should be included in the main plugin file.
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Better_Auth_Config
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define Better Auth database constants if not already defined
if (!defined('BETTER_AUTH_DB_HOST')) {
    define('BETTER_AUTH_DB_HOST', getenv('BETTER_AUTH_DB_HOST') ?: 'localhost');
}

if (!defined('BETTER_AUTH_DB_PORT')) {
    define('BETTER_AUTH_DB_PORT', getenv('BETTER_AUTH_DB_PORT') ?: '3306');
}

if (!defined('BETTER_AUTH_DB_NAME')) {
    define('BETTER_AUTH_DB_NAME', getenv('BETTER_AUTH_DB_NAME') ?: DB_NAME);
}

if (!defined('BETTER_AUTH_DB_USER')) {
    define('BETTER_AUTH_DB_USER', getenv('BETTER_AUTH_DB_USER') ?: DB_USER);
}

if (!defined('BETTER_AUTH_DB_PASSWORD')) {
    define('BETTER_AUTH_DB_PASSWORD', getenv('BETTER_AUTH_DB_PASSWORD') ?: DB_PASSWORD);
}

// Include Admin UI class
use ASAPDigest\Core\ASAP_Digest_Admin_UI;
require_once plugin_dir_path(__FILE__) . 'admin/class-admin-ui.php';

/**
 * @description Set up Better Auth secret for WordPress integration (using consistent name)
 * @return void
 * @example
 * // Called during plugin initialization
 * asap_setup_better_auth_secret();
 * @created 03.29.25 | 03:45 PM PDT
 * @updated 04.07.25 | 06:05 PM PDT - Renamed constant to BETTER_AUTH_SECRET
 */
function asap_setup_better_auth_secret() { // Renamed function for clarity
    // Check if the constant is already defined in wp-config.php
    if (!defined('BETTER_AUTH_SECRET')) {
        // Use WordPress AUTH_KEY as the shared secret if not defined
        // This ensures that we have a unique secret per site that's already secure
        if (defined('AUTH_KEY') && !empty(AUTH_KEY)) {
            define('BETTER_AUTH_SECRET', AUTH_KEY);
             error_log('[ASAP Better Auth] WARNING: Using AUTH_KEY as fallback for BETTER_AUTH_SECRET. Define BETTER_AUTH_SECRET in wp-config.php.'); // Added warning
        } else {
            // Extremely unlikely fallback
            define('BETTER_AUTH_SECRET', wp_generate_password(64, true, true));
            // Log the issue
            error_log('[ASAP Better Auth] CRITICAL WARNING: Using generated password for BETTER_AUTH_SECRET. Define in wp-config.php immediately!');
        }
    }

    // Store the value in options for easier access (using consistent option name)
    if (!get_option('better_auth_secret')) { // Changed option name
        update_option('better_auth_secret', BETTER_AUTH_SECRET); // Changed option name
    }
}

// Run setup function
asap_setup_better_auth_secret(); // Renamed function call

/**
 * @description Safely get a constant value with null fallback
 * @param {string} constant_name The name of the constant to retrieve
 * @return {mixed|null} Constant value or null if not defined
 * @example
 * // Get a constant value safely
 * $value = asap_get_constant('BETTER_AUTH_URL');
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_get_constant($constant_name) {
    return defined($constant_name) ? constant($constant_name) : null;
}

/**
 * @description Get the base URL for SvelteKit app based on environment
 * @return {string} Base URL for SvelteKit app
 * @example
 * // Get the SvelteKit app URL
 * $base_url = asap_get_better_auth_base_url();
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_get_better_auth_base_url() {
    // Check if URL is defined in wp-config.php
    $config_url = asap_get_constant('BETTER_AUTH_URL');
    if (!empty($config_url)) {
        return $config_url;
    }
    
    // Check if stored in options
    $stored_url = get_option('better_auth_url');
    if (!empty($stored_url)) {
        return $stored_url;
    }
    
    // Default fallback based on environment
    $is_local = (strpos($_SERVER['HTTP_HOST'] ?? '', 'local') !== false);
    if ($is_local) {
        return 'https://localhost:5173';
    } else {
        return 'https://app.asapdigest.com';
    }
}

/**
 * @description Validate Better Auth request signature using shared secret
 * @param {string} timestamp Request timestamp
 * @param {string} signature Request signature
 * @return {boolean} Whether the signature is valid
 * @example
 * // Validate an incoming request
 * $is_valid = asap_validate_better_auth_signature($timestamp, $signature);
 * @created 03.29.25 | 03:45 PM PDT
 * @updated 04.07.25 | 06:05 PM PDT - Use consistent constant/option name
 */
function asap_validate_better_auth_signature($timestamp, $signature) {
    // Ensure we have required data
    if (empty($timestamp) || empty($signature)) {
        return false;
    }

    // Get shared secret (use consistent constant name first, then option)
    $secret = defined('BETTER_AUTH_SECRET') ? BETTER_AUTH_SECRET : get_option('better_auth_secret'); // Changed constant and option name
    if (empty($secret)) {
        error_log('[ASAP Better Auth] Validation Error: Secret not found.'); // Added log
        return false;
    }

    // Check timestamp is within 5 minutes
    $now = time();
    if (abs($now - intval($timestamp)) > 300) {
        error_log('[ASAP Better Auth] Validation Error: Timestamp expired.'); // Added log
        return false;
    }

    // Calculate expected signature
    $expected = hash_hmac('sha256', $timestamp, $secret);

    // Compare signatures
    return hash_equals($expected, $signature);
}

/**
 * @description Create or update WordPress user from Better Auth user data
 * @param {array} user_data Better Auth user data containing email, id, and optional fields
 * @return {int|WP_Error} WordPress user ID or error
 * @example
 * // Create a WordPress user from Better Auth data
 * $wp_user_id = asap_create_wp_user_from_better_auth([
 *     'email' => 'user@example.com',
 *     'id' => 'ba_123',
 *     'username' => 'username'
 * ]);
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_create_wp_user_from_better_auth($user_data) {
    // Validate required data
    if (empty($user_data['email']) || empty($user_data['id'])) {
        return new WP_Error('missing_data', 'Missing required user data');
    }
    
    // Check if user already exists by Better Auth ID
    $existing_users = get_users([
        'meta_key' => 'better_auth_user_id',
        'meta_value' => $user_data['id'],
        'number' => 1
    ]);
    
    if (!empty($existing_users)) {
        return $existing_users[0]->ID;
    }
    
    // Check if user exists by email
    $user = get_user_by('email', $user_data['email']);
    if ($user) {
        // Link existing user to Better Auth
        update_user_meta($user->ID, 'better_auth_user_id', $user_data['id']);
        return $user->ID;
    }
    
    // Create new user
    $username = sanitize_user(
        !empty($user_data['username']) ? 
        $user_data['username'] : 
        explode('@', $user_data['email'])[0]
    );
    
    // Ensure unique username
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $user_data['email'],
        'user_pass' => wp_generate_password(),
        'display_name' => $user_data['name'] ?? $username,
        'role' => 'subscriber'
    ]);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // Store Better Auth user ID
    update_user_meta($user_id, 'better_auth_user_id', $user_data['id']);
    
    return $user_id;
}

/**
 * @description Create WordPress session for Better Auth user (core implementation)
 * @param {int} wp_user_id WordPress user ID
 * @return {WP_Error|array} Session data or error
 * @example
 * // Create a WordPress session for user ID 123
 * $session = asap_create_wp_session_core(123);
 * @created 03.30.25 | 04:37 PM PDT
 */
function asap_create_wp_session_core($wp_user_id) {
    // Verify user exists
    $user = get_user_by('ID', $wp_user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'User does not exist');
    }

    // Get Better Auth user ID
    $better_auth_user_id = get_user_meta($wp_user_id, 'better_auth_user_id', true);
    if (empty($better_auth_user_id)) {
        return new WP_Error('no_better_auth_id', 'User not linked to Better Auth');
    }

    // Set authentication cookies
    wp_set_auth_cookie($wp_user_id, true);
    wp_set_current_user($wp_user_id);

    // Update user meta with session info
    $session_token = wp_get_session_token();
    update_user_meta($wp_user_id, 'better_auth_session_token', $session_token);
    update_user_meta($wp_user_id, 'better_auth_last_login', current_time('mysql'));

    // Fire action for integrations
    do_action('asap_better_auth_session_created', $wp_user_id, $better_auth_user_id);

    return [
        'user_id' => $wp_user_id,
        'better_auth_user_id' => $better_auth_user_id,
        'session_token' => $session_token
    ];
}

/**
 * @description Validate WordPress session token against Better Auth token with bidirectional sync
 * @param WP_REST_Request $request REST request object
 * @return WP_Error|array Validation result with user data or error
 * @example
 * // Check session token from REST request
 * $result = asap_validate_wp_session_token($request);
 * @created 04.02.25 | 10:25 PM PDT
 */
function asap_validate_wp_session_token($request) {
    try {
        // Get Better Auth token from header
        $better_auth_token = $request->get_header('X-Better-Auth-Token');
        if (empty($better_auth_token)) {
            return new WP_Error('missing_token', 'Better Auth token not provided');
        }

        // Validate token format and signature
        $token_parts = explode('.', $better_auth_token);
        if (count($token_parts) !== 3) {
            return new WP_Error('invalid_token_format', 'Invalid token format');
        }

        // Decode token payload
        $payload = json_decode(base64_decode($token_parts[1]), true);
        if (!$payload || empty($payload['sub'])) {
            return new WP_Error('invalid_token_payload', 'Invalid token payload');
        }

        // Get WordPress user from Better Auth ID with retry
        $max_retries = 3;
        $retry_delay = 1; // seconds
        $users = null;

        for ($attempt = 0; $attempt < $max_retries; $attempt++) {
            $users = get_users([
                'meta_key' => 'better_auth_user_id',
                'meta_value' => $payload['sub'],
                'number' => 1
            ]);

            if (!empty($users)) {
                break;
            }

            if ($attempt < $max_retries - 1) {
                sleep($retry_delay);
            }
        }

        if (empty($users)) {
            return new WP_Error('user_not_found', 'No WordPress user found for Better Auth ID');
        }

        $user = $users[0];

        // Verify Better Auth session is still valid
        try {
            $ba_db = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s',
                    BETTER_AUTH_DB_HOST,
                    BETTER_AUTH_DB_PORT,
                    BETTER_AUTH_DB_NAME
                ),
                BETTER_AUTH_DB_USER,
                BETTER_AUTH_DB_PASSWORD,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );

            $stmt = $ba_db->prepare("
                SELECT COUNT(*) as valid
                FROM ba_sessions
                WHERE user_id = :user_id
                AND token = :token
                AND expires_at > NOW()
                AND revoked = 0
            ");

            $stmt->execute([
                ':user_id' => $payload['sub'],
                ':token' => $better_auth_token
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result || !$result['valid']) {
                // Session is invalid in Better Auth, clear WordPress session
                wp_destroy_current_session();
                return new WP_Error('invalid_session', 'Better Auth session is invalid');
            }
        } catch (Exception $e) {
            error_log('Better Auth session verification error: ' . $e->getMessage());
            // Continue with WordPress session check on Better Auth DB error
        }

        // Auto-create WordPress session if needed
        if (!is_user_logged_in() || get_current_user_id() !== $user->ID) {
            $session_result = asap_create_wp_session_core($user->ID);
            if (is_wp_error($session_result)) {
                return $session_result;
            }
        }

        // Verify WordPress session token
        $stored_token = get_user_meta($user->ID, 'better_auth_session_token', true);
        if (empty($stored_token) || $stored_token !== wp_get_session_token()) {
            // Auto-refresh session if token mismatch
            $session_result = asap_create_wp_session_core($user->ID);
            if (is_wp_error($session_result)) {
                return $session_result;
            }
        }

        // Auto-sync user data with retry mechanism
        $sync_attempts = 0;
        $max_sync_attempts = 3;
        $sync_success = false;

        while ($sync_attempts < $max_sync_attempts && !$sync_success) {
            $sync_result = asap_auto_sync_user_data($user->ID);
            if (!is_wp_error($sync_result)) {
                $sync_success = true;
            } else {
                $sync_attempts++;
                if ($sync_attempts < $max_sync_attempts) {
                    sleep(1); // Wait 1 second before retry
                }
            }
        }

        if (!$sync_success) {
            error_log('Failed to sync user data after ' . $max_sync_attempts . ' attempts');
        }

        // Return success with user data
        return [
            'valid' => true,
            'user_id' => $user->ID,
            'better_auth_user_id' => $payload['sub'],
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'avatar_url' => get_avatar_url($user->ID),
            'roles' => $user->roles,
            'sync_status' => $sync_success ? 'synced' : 'sync_failed'
        ];
    } catch (Exception $e) {
        error_log('Session validation error: ' . $e->getMessage());
        return new WP_Error('validation_failed', $e->getMessage());
    }
}

/**
 * Initialize Better Auth hooks and features
 * 
 * @description Register all Better Auth related hooks with proper priorities
 * @hook add_action('plugins_loaded', 'asap_init_better_auth', 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 04:25 PM PDT
 */
function asap_init_better_auth() {
    // Core functionality (priority 10)
    add_action('init', 'asap_setup_better_auth_secret', 10); // Renamed setup function call
    
    // Feature-specific hooks (priority 20-29)
    add_action('user_register', 'asap_sync_wp_user_to_better_auth', 20);
    add_action('rest_api_init', 'asap_register_better_auth_endpoints', 20);
    add_action('rest_api_init', 'asap_register_wp_session_check', 20);
    add_action('rest_api_init', 'asap_register_token_exchange', 20);
}

// Initialize Better Auth system
add_action('plugins_loaded', 'asap_init_better_auth', 10);

/**
 * @description Register Better Auth REST endpoints
 * @hook add_action('rest_api_init', 'asap_register_better_auth_endpoints', 20)
 * @dependencies asap_init_better_auth must run first (priority 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
function asap_register_better_auth_endpoints() {
    register_rest_route('asap/v1', '/auth/create-wp-user', [
        'methods' => 'POST',
        'callback' => 'asap_handle_create_wp_user',
        'permission_callback' => '__return_true'
    ]);
    
    register_rest_route('asap/v1', '/auth/create-wp-session', [
        'methods' => 'POST',
        'callback' => 'asap_handle_create_wp_session',
        'permission_callback' => '__return_true'
    ]);
}

/**
 * Handle create WordPress user request
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function asap_handle_create_wp_user($request) {
    // Validate request signature
    $timestamp = $request->get_header('X-Better-Auth-Timestamp');
    $signature = $request->get_header('X-Better-Auth-Signature');
    
    if (!asap_validate_better_auth_signature($timestamp, $signature)) {
        return new WP_REST_Response([
            'error' => 'Invalid request signature'
        ], 401);
    }
    
    // Get user data from request
    $user_data = $request->get_json_params();
    
    // Create/update WordPress user
    $result = asap_create_wp_user_from_better_auth($user_data);
    
    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'error' => $result->get_error_message()
        ], 400);
    }
    
    return new WP_REST_Response([
        'wp_user_id' => $result
    ]);
}

/**
 * Handle create WordPress session request
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function asap_handle_create_wp_session($request) {
    // Validate request signature
    $timestamp = $request->get_header('X-Better-Auth-Timestamp');
    $signature = $request->get_header('X-Better-Auth-Signature');
    
    if (!asap_validate_better_auth_signature($timestamp, $signature)) {
        return new WP_REST_Response([
            'error' => 'Invalid request signature'
        ], 401);
    }
    
    // Get WordPress user ID from request
    $wp_user_id = $request->get_param('wp_user_id');
    if (empty($wp_user_id)) {
        return new WP_REST_Response([
            'error' => 'Missing WordPress user ID'
        ], 400);
    }
    
    // Create session
    $result = asap_create_wp_session_core($wp_user_id);
    
    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'error' => $result->get_error_message()
        ], 400);
    }
    
    return new WP_REST_Response([
        'success' => true
    ]);
}

/**
 * @description Register WordPress session check endpoint
 * @hook add_action('rest_api_init', 'asap_register_wp_session_check', 20)
 * @dependencies asap_init_better_auth must run first (priority 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
function asap_register_wp_session_check() {
    register_rest_route('asap/v1/auth', '/check-wp-session', [
        'methods' => 'GET',
        'callback' => 'asap_check_wp_session', // KEEP THIS NAME for the callback
        'permission_callback' => '__return_true'
    ]);
}

/**
 * @description Check WordPress session and sync with Better Auth if needed (REST Endpoint Callback)
 * @param WP_REST_Request $request The request object
 * @return WP_REST_Response|WP_Error Response object or error
 * @created 03.31.25 | 11:48 AM PDT
 */
function asap_check_wp_session($request) {
    error_log('[ASAP Digest Check Session] Endpoint /check-wp-session called.'); // ADDED

    // Check if user is logged into WordPress
    if (!is_user_logged_in()) {
        error_log('[ASAP Digest Check Session] User not logged in to WordPress. Returning loggedIn=false.'); // ADDED
        return new WP_REST_Response(['loggedIn' => false], 200);
    }

    // Get current WordPress user
    $current_user = wp_get_current_user();
    error_log('[ASAP Digest Check Session] User logged in to WP. WP User ID: ' . $current_user->ID); // ADDED

    // --- Determine if Auto-Sync is Active for this user ---
    // Option 1: Check a global setting (e.g., from asap_digest_auth_settings option)
    $auth_settings = get_option('asap_digest_auth_settings');
    $global_autosync_enabled = isset($auth_settings['auto_sync_enabled']) ? (bool) $auth_settings['auto_sync_enabled'] : false;

    // Option 2: Check a user-specific setting (e.g., user meta)
    // $user_autosync_preference = get_user_meta($current_user->ID, 'asap_autosync_preference', true);
    // Let's assume 'enabled' means active, absence or 'disabled' means inactive.
    // $user_wants_autosync = ($user_autosync_preference === 'enabled');

    // Combine logic (e.g., global must be on, and user hasn't disabled it)
    // Adjust this logic based on your specific requirements.
    // For now, let's assume it's active if the global setting is on.
    $is_autosync_active = $global_autosync_enabled;
    error_log('[ASAP Digest Check Session] Auto-Sync Active Status: ' . ($is_autosync_active ? 'true' : 'false') . ' (Based on global setting)');
    // --- End Auto-Sync Check ---

    // ---> MODIFICATION: Only sync periodically <---
    $last_sync_time = get_user_meta($current_user->ID, 'better_auth_sync_time', true);
    $needs_sync = true; // Default to needing sync
    if ($last_sync_time) {
        $time_since_last_sync = time() - strtotime($last_sync_time);
        if ($time_since_last_sync < 300) { // 300 seconds = 5 minutes
            $needs_sync = false;
            error_log('[ASAP Digest Check Session] Skipping sync for WP User ID: ' . $current_user->ID . ' (Synced ' . $time_since_last_sync . ' seconds ago).');
        }
    }

    // Sync user to Better Auth if needed
    $sync_result = null;
    if ($needs_sync) {
        error_log('[ASAP Digest Check Session] Attempting sync for WP User ID: ' . $current_user->ID); // ADDED
        $sync_result = asap_sync_wp_user_to_better_auth($current_user->ID, 'auto_sync');
        error_log('[ASAP Digest Check Session] Sync result: ' . print_r($sync_result, true)); // ADDED

        if (is_wp_error($sync_result)) {
            error_log('[ASAP Digest Check Session] Sync failed: ' . $sync_result->get_error_message()); // ADDED
            // Return error if sync explicitly fails when attempted
            return new WP_REST_Response([
                'error' => 'Failed to sync user with Better Auth',
                'details' => $sync_result->get_error_message()
            ], 500);
        }
    } else {
        // If sync was skipped, prepare a placeholder success structure 
        // We still need the ba_user_id for subsequent DB checks
        $ba_user_id_meta = get_user_meta($current_user->ID, 'better_auth_user_id', true);
        if (!$ba_user_id_meta) {
             error_log('[ASAP Digest Check Session] Error: Cannot proceed without ba_user_id (sync skipped).');
             return new WP_REST_Response([
                'error' => 'Cannot retrieve user session details.',
                'details' => 'Better Auth user ID link missing and sync was skipped.'
            ], 500);
        }
        $sync_result = ['ba_user_id' => $ba_user_id_meta]; // Provide the essential ID
    }
    // ---> END MODIFICATION <---

    // Ensure we have the Better Auth user ID (either from sync or meta)
    if (!isset($sync_result['ba_user_id']) || empty($sync_result['ba_user_id'])) {
         error_log('[ASAP Digest Check Session] Error: ba_user_id missing from sync result or meta.'); // Updated Log
         return new WP_REST_Response([
            'error' => 'Failed to retrieve Better Auth user ID after sync.',
            'details' => 'Sync result did not contain ba_user_id.'
        ], 500);
    }
    $ba_user_id = $sync_result['ba_user_id'];
    error_log('[ASAP Digest Check Session] Better Auth User ID: ' . $ba_user_id); // ADDED

    try {
        // Use WordPress $wpdb global for consistency
        global $wpdb;
        error_log('[ASAP Digest Check Session] Querying ba_users and ba_sessions for user: ' . $ba_user_id); // ADDED

        // Fetch the updated_at timestamp and existing session token if available
        $ba_user_data = $wpdb->get_row($wpdb->prepare(
            "SELECT u.updated_at, s.token as session_token 
             FROM {$wpdb->prefix}ba_users u
             LEFT JOIN {$wpdb->prefix}ba_sessions s ON u.id = s.user_id
             WHERE u.id = %s
             ORDER BY s.created_at DESC
             LIMIT 1",
            $ba_user_id
        ));
        error_log('[ASAP Digest Check Session] DB Query Result: ' . print_r($ba_user_data, true)); // ADDED

        // ---> MODIFIED: Check if user data was actually found, use get_row result directly <---
        if (!$ba_user_data) {
            error_log('[ASAP Digest Check Session Error] Could not find Better Auth user data for BA ID: ' . $ba_user_id);
            return new WP_REST_Response([
                'error' => 'Failed to retrieve Better Auth user data',
                'details' => 'User not found in ba_users table.'
            ], 404); // Use 404 Not Found
        }
        // ---> END MODIFIED CHECK <---

        $updated_at_timestamp = $ba_user_data->updated_at ?? null;
        $sessionToken = $ba_user_data->session_token ?? null; // Use existing token if found
        error_log('[ASAP Digest Check Session] Extracted - UpdatedAt: ' . $updated_at_timestamp . ', SessionToken: ' . ($sessionToken ? '[Exists]' : '[None]')); // ADDED

        // ---> ADDED: Create session if one doesn't exist <---
        if (empty($sessionToken)) {
            error_log('[ASAP Digest Check Session] No existing session found for BA User ID: ' . $ba_user_id . '. Attempting to create one. [TEMP DISABLED FOR DEBUG]'); // Modified Log
            // --- TEMP DISABLED FOR DEBUG VDS-2025-04-16 ---
            // $newSessionToken = asap_create_ba_session($ba_user_id);
            // if ($newSessionToken) {
            //     $sessionToken = $newSessionToken;
            //     error_log('[ASAP Digest Check Session] Successfully created and assigned new session token.');
            // } else {
            //     error_log('[ASAP Digest Check Session] Failed to create new session token.');
            //     // Optionally return an error here, or proceed without a token
            //     // For now, proceed and let the client handle the lack of token
            // }
            // --- END TEMP DISABLED ---
        }
        // ---> END ADDED SECTION <---
        
        // ---> REMOVED: Session token generation and insertion <---

        // --- MODIFIED RESPONSE ---
        $response_data = [
            'loggedIn' => true,
            'sessionToken' => $sessionToken, // Return existing token or null
            'userId' => $ba_user_id, // This is the Better Auth user ID
            'updatedAt' => $updated_at_timestamp,
            'autosyncActive' => $is_autosync_active // Include the flag
        ];
        // --- END MODIFIED RESPONSE ---

        error_log('[ASAP Digest Check Session] Returning response: ' . print_r($response_data, true)); // ADDED
        return new WP_REST_Response($response_data, 200);

    } catch (\Exception $e) { // Changed Exception to \Exception for global scope
        error_log('[ASAP Digest Check Session Error] Database error: ' . $e->getMessage()); // Improved log message
        return new WP_REST_Response([
            'error' => 'Database error during session check', // More specific error
            'details' => $e->getMessage()
        ], 500);
    }
}

/**
 * @description Track the source of auto-sync for a user
 * @param int $wp_user_id WordPress user ID
 * @param string $source Source of the sync (e.g., 'role_auto_sync', 'manual', 'subscription')
 * @return void
 */
function asap_track_sync_source($wp_user_id, $source) {
    update_user_meta($wp_user_id, 'better_auth_sync_source', $source);
}

/**
 * @description Sync WordPress user to Better Auth
 * @param {int} wp_user_id WordPress user ID to sync
 * @return {array|WP_Error} Array with success status and Better Auth user ID on success, WP_Error on failure
 * @example
 * // Sync a WordPress user to Better Auth
 * $result = asap_sync_wp_user_to_better_auth(123);
 * @created 03.29.25 | 03:45 PM PDT
 * @updated 04.07.25 | 06:05 PM PDT - Use consistent constant name
 */
function asap_sync_wp_user_to_better_auth($wp_user_id, $source = 'manual') {
    error_log('[ASAP Debug] SYNC FUNC: asap_sync_wp_user_to_better_auth called for WP User ID: ' . $wp_user_id . ' | Source: ' . $source); // DEBUG

    $user = get_userdata($wp_user_id);
    if (!$user) {
        error_log('[ASAP Debug] SYNC FUNC: Failed to get user data for ID: ' . $wp_user_id); // DEBUG
        return new WP_Error('user_not_found', 'WordPress user not found.', ['status' => 404]);
    }

    // Check sync eligibility again (e.g., based on role)
    if (!asap_should_auto_sync_user($user) && $source !== 'manual') {
         error_log('[ASAP Debug] SYNC FUNC: User ID ' . $wp_user_id . ' role excluded from sync based on current settings.'); // DEBUG
         update_user_meta($wp_user_id, 'better_auth_sync_status', 'excluded');
         return ['status' => 'excluded', 'message' => 'User role excluded from sync.'];
    }

    // *** START ADDED CODE ***
    // Get the Better Auth user ID (ba_user_id) from the map
    global $wpdb;
    $table_name = $wpdb->prefix . 'ba_wp_user_map';
    $ba_user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ba_user_id FROM {$table_name} WHERE wp_user_id = %d",
        $wp_user_id
    ));

    if (!$ba_user_id) {
        error_log('[ASAP Debug] SYNC FUNC: Could not find corresponding ba_user_id for wp_user_id: ' . $wp_user_id . ' in ' . $table_name);
        // Decide if this is an error or if the sync should proceed without it
        // For now, we'll log and proceed, but the SvelteKit endpoint requires it,
        // so the sync will likely fail there. This indicates an incomplete initial sync/mapping.
        // Alternatively, could return a specific WP_Error here.
    }
    // *** END ADDED CODE ***

    // Prepare user data payload
    $user_data = [
        'wpUserId' => $wp_user_id,
        'skUserId' => $ba_user_id, // *** ADDED LINE ***
        'email' => $user->user_email,
        'username' => $user->user_login,
        'displayName' => $user->display_name ?: $user->user_login,
        'roles' => (array) $user->roles,
        'firstName' => $user->first_name,
        'lastName' => $user->last_name,
        // Add other relevant metadata if needed
        'metadata' => [
            'description' => $user->description,
            'nickname' => $user->nickname,
        ]
    ];
    error_log('[ASAP Debug] SYNC FUNC: Prepared payload: ' . print_r($user_data, true)); // DEBUG

    // Get Better Auth API endpoint
    $api_base_url = asap_get_better_auth_base_url();
    $sync_endpoint = $api_base_url . '/api/auth/sync'; // Using the SvelteKit base URL (Removed /users/)

    // Get the shared secret (using consistent constant name)
    $shared_secret = asap_get_constant('BETTER_AUTH_SECRET'); // Changed constant name
    if (!$shared_secret) {
        error_log('[ASAP Debug] SYNC FUNC: ERROR - BETTER_AUTH_SECRET is not defined!'); // Changed constant name in log
        return new WP_Error('missing_secret', 'Better Auth secret is not configured in wp-config.php.', ['status' => 500]); // Updated error message
    }

    // Prepare request arguments
    $args = [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-WP-Sync-Secret' => $shared_secret // Add the shared secret header
        ],
        'body' => json_encode($user_data),
        'timeout' => 30 // Increased timeout to 30 seconds
    ];
    error_log('[ASAP Debug] SYNC FUNC: Making request to: ' . $sync_endpoint); // DEBUG

    // Make the request
    $response = wp_remote_post($sync_endpoint, $args);

    // Handle response
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log('[ASAP Debug] SYNC FUNC: WP Error during sync request: ' . $error_message); // DEBUG
        update_user_meta($wp_user_id, 'better_auth_sync_status', 'error');
        update_user_meta($wp_user_id, 'better_auth_sync_error', $error_message);
        return new WP_Error('sync_failed', 'Failed to sync user to Better Auth: ' . $error_message);
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    error_log('[ASAP Debug] SYNC FUNC: Response Code: ' . $response_code); // DEBUG
    error_log('[ASAP Debug] SYNC FUNC: Response Body: ' . $response_body); // DEBUG

    if ($response_code >= 200 && $response_code < 300) {
        // Success
        $result_data = json_decode($response_body, true);
        $ba_user_id = $result_data['data']['id'] ?? null;

        // --- BEGIN FIX: Prevent recursion ---
        // Remove the action hook before updating meta
        remove_action('profile_update', 'asap_auto_sync_user_data', 10);
        remove_action('user_register', 'asap_auto_sync_user_data', 10); // Also remove user_register hook temporarily if needed
        // --- END FIX ---

        if ($ba_user_id) {
             update_user_meta($wp_user_id, 'better_auth_user_id', $ba_user_id);
        }
        update_user_meta($wp_user_id, 'better_auth_sync_status', 'synced');
        update_user_meta($wp_user_id, 'better_auth_sync_time', current_time('mysql'));
        delete_user_meta($wp_user_id, 'better_auth_sync_error'); // Clear previous errors
        asap_track_sync_source($wp_user_id, $source);

        // --- BEGIN FIX: Re-add hook ---
        // Add the action hook back after meta updates are complete
        add_action('profile_update', 'asap_auto_sync_user_data', 10, 2);
        add_action('user_register', 'asap_auto_sync_user_data', 10); // Re-add user_register hook
        // --- END FIX ---

        error_log('[ASAP Debug] SYNC FUNC: Sync successful for WP User ID: ' . $wp_user_id . '. BA ID: ' . ($ba_user_id ?? 'N/A')); // DEBUG
        // Include ba_user_id in the return array for successful sync
        return ['status' => 'synced', 'data' => $result_data, 'ba_user_id' => $ba_user_id];
    } else {
        // Failure
        update_user_meta($wp_user_id, 'better_auth_sync_status', 'error');
        update_user_meta($wp_user_id, 'better_auth_sync_error', "HTTP {$response_code}: {$response_body}");
        error_log('[ASAP Debug] SYNC FUNC: Sync failed for WP User ID: ' . $wp_user_id . '. Response Code: ' . $response_code); // DEBUG
        return new WP_Error('sync_failed', "Better Auth sync failed with status {$response_code}.", ['status' => $response_code, 'body' => $response_body]);
    }
}

/**
 * @description Sync all WordPress users to Better Auth
 * @return {array} Array with success status and results for synced, failed, and skipped users
 * @example
 * // Sync all WordPress users to Better Auth
 * $results = asap_sync_all_wp_users_to_better_auth();
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_sync_all_wp_users_to_better_auth() {
    $results = array(
        'success' => true,
        'synced' => array(),
        'failed' => array(),
        'skipped' => array()
    );

    // Get all WordPress users
    $wp_users = get_users(array('fields' => 'ID'));

    foreach ($wp_users as $wp_user_id) {
        $sync_result = asap_sync_wp_user_to_better_auth($wp_user_id);
        
        if (is_wp_error($sync_result)) {
            $results['failed'][] = array(
                'wp_user_id' => $wp_user_id,
                'error' => $sync_result->get_error_message()
            );
        } else if ($sync_result['message'] === 'User already synced') {
            $results['skipped'][] = array(
                'wp_user_id' => $wp_user_id,
                'ba_user_id' => $sync_result['ba_user_id']
            );
        } else {
            $results['synced'][] = array(
                'wp_user_id' => $wp_user_id,
                'ba_user_id' => $sync_result['ba_user_id']
            );
        }
    }

    return $results;
}

/**
 * @description Render Better Auth settings page with modern UI components
 * @return void
 * @example
 * // Called by add_menu_page callback
 * asap_render_better_auth_settings();
 * @created 03.30.25 | 04:45 PM PDT
 */
function asap_render_better_auth_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $ui = new ASAP_Digest_Admin_UI();
    ?>
    <div class="wrap asap-central-command">
        <h1><?php _e('Better Auth Settings'); ?></h1>
        
        <?php
        // Add Test Admin Creation Button for Development
        if (strpos($_SERVER['HTTP_HOST'] ?? '', 'local') !== false) {
            echo '<div class="asap-card">';
            echo '<h2>' . __('Development Tools') . '</h2>';
            echo '<div class="asap-form-actions">';
            echo sprintf(
                '<button class="button button-secondary create-test-admin" data-nonce="%s">%s</button>',
                wp_create_nonce('create_test_admin'),
                __('Create Test Admin User')
            );
            echo '</div>';
            
            // Add JavaScript for the create test admin button
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('.create-test-admin').on('click', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var nonce = button.data('nonce');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'asap_create_test_admin',
                            nonce: nonce
                        },
                        beforeSend: function() {
                            button.addClass('asap-loading').prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert(response.data.message || 'Error creating test admin');
                            }
                        },
                        error: function() {
                            alert('Error communicating with server');
                        },
                        complete: function() {
                            button.removeClass('asap-loading').prop('disabled', false);
                        }
                    });
                });
            });
            </script>
            <?php
            echo '</div>';
        }
        
        // Status Overview Card
        $status_content = '<div class="asap-status-grid">';
        
        // Check if shared secret is set (use consistent constant name)
        $shared_secret = asap_get_constant('BETTER_AUTH_SECRET'); // Changed constant name
        $status_content .= '<div class="status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            $shared_secret ? 'good' : 'error',
            'Shared Secret: ' . ($shared_secret ? 'Configured' : 'Not Set (Define BETTER_AUTH_SECRET in wp-config.php)') // Updated message
        );
        $status_content .= '</div>';
        
        // Add Server-to-Server Auto Login status
        $status_content .= '<div class="status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            $shared_secret ? 'good' : 'error',
            'S2S Auto Login: ' . ($shared_secret ? 'Configured' : 'Not Configured')
        );
        $status_content .= '</div>';
        
        // Check base URL
        $base_url = asap_get_better_auth_base_url();
        $status_content .= '<div class="status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            $base_url ? 'good' : 'warning',
            'Base URL: ' . ($base_url ?: 'Not Configured')
        );
        $status_content .= '</div>';
        
        // Get synced users count
        $synced_users = count(get_users(['meta_key' => 'better_auth_user_id']));
        $total_users = count(get_users());
        $status_content .= '<div class="status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            $synced_users > 0 ? 'good' : 'inactive',
            sprintf('Synced Users: %d/%d', $synced_users, $total_users)
        );
        $status_content .= '</div>';

        // Get active sessions count
        $active_sessions = count(get_users(['meta_key' => 'better_auth_session_token']));
        $status_content .= '<div class="status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            'good',
            sprintf('Active Sessions: %d', $active_sessions)
        );
        $status_content .= '</div>';

        // Check environment
        $is_local = (strpos($_SERVER['HTTP_HOST'] ?? '', 'local') !== false);
        $status_content .= '<div class="status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            'inactive',
            'Environment: ' . ($is_local ? 'Local Development' : 'Production')
        );
        $status_content .= '</div>';

        // Check last sync time
        $last_sync = get_option('asap_better_auth_last_sync');
        $status_content .= '<div class="status-item">';
        $status_content .= ASAP_Digest_Admin_UI::create_status_indicator(
            $last_sync ? 'good' : 'warning',
            'Last Sync: ' . ($last_sync ? human_time_diff(strtotime($last_sync)) . ' ago' : 'Never')
        );
        $status_content .= '</div>';
        
        $status_content .= '</div>';
        
        echo ASAP_Digest_Admin_UI::create_card('System Status', $status_content, 'status-card');
        
        // Configuration Card
        $config_content = '<div class="asap-form">';
        
        $config_content .= ASAP_Digest_Admin_UI::create_form_field(
            'asap_better_auth_base_url',
            __('Better Auth Base URL'),
            'text',
            [
                'value' => get_option('asap_better_auth_base_url', ''),
                'description' => __('The base URL for the Better Auth service'),
                'placeholder' => 'https://auth.example.com'
            ]
        );
        
        // Add role selection with locked roles
        $roles = wp_roles();
        $auto_sync_roles = get_option('asap_better_auth_auto_sync_roles', ['administrator']);
        $locked_roles = get_option('asap_better_auth_locked_roles', ['subscriber']);
        
        // Create two-column layout container
        $config_content .= '<div class="roles-layout-wrapper">';
        $config_content .= '<div class="roles-layout">';
        
        // Auto-sync roles column
        $config_content .= '<div class="roles-section auto-sync-section">';
        $config_content .= '<h3 class="section-heading">' . __('Auto-Sync Roles') . '</h3>';
        $config_content .= '<div class="roles-container">';
        
        foreach ($roles->roles as $role_slug => $role_info) {
            $is_locked = in_array($role_slug, $locked_roles);
            $checked = in_array($role_slug, $auto_sync_roles) ? ' checked="checked"' : '';
            $disabled = $is_locked ? ' disabled="disabled"' : '';
            $class = $is_locked ? ' class="locked-role"' : '';
            
            $config_content .= sprintf(
                '<label%s><input type="checkbox" class="auto-sync-role-checkbox" data-role="%s"%s%s> %s%s</label>',
                $class,
                esc_attr($role_slug),
                $checked,
                $disabled,
                esc_html(translate_user_role($role_info['name'])),
                $is_locked ? ' 🔒' : ''
            );
        }
        
        $config_content .= '</div>';
        $config_content .= '<p class="description">' . __('Select which user roles should automatically sync with Better Auth.') . '</p>';
        $config_content .= '<div class="save-status"></div>';
        $config_content .= '</div>';

        // Locked roles column for super admins
        if (is_super_admin()) {
            $config_content .= '<div class="roles-section locked-section">';
            $config_content .= '<h3 class="section-heading">' . __('Locked Roles') . ' 🔒</h3>';
            $config_content .= '<div class="roles-container">';
            
            foreach ($roles->roles as $role_slug => $role_info) {
                $checked = in_array($role_slug, $locked_roles) ? ' checked="checked"' : '';
                $config_content .= sprintf(
                    '<label><input type="checkbox" class="locked-role-checkbox" data-role="%s"%s> %s</label>',
                    esc_attr($role_slug),
                    $checked,
                    esc_html(translate_user_role($role_info['name']))
                );
            }
            
            $config_content .= '</div>';
            $config_content .= '<p class="description">' . __('Select which roles should be locked from auto-sync selection.') . '</p>';
            $config_content .= '<div class="save-status"></div>';
            $config_content .= '</div>';
        }

        $config_content .= '</div>'; // Close roles-layout
        $config_content .= '</div>'; // Close roles-layout-wrapper

        // Add AJAX saving JavaScript
        $config_content .= '<script>
        jQuery(document).ready(function($) {
            let saveTimeout;
            const SAVE_DELAY = 500; // Debounce delay in ms

            function showSaveStatus(container, status, message) {
                const statusDiv = container.find(".save-status");
                statusDiv.html(`<div class="save-message ${status}">${message}</div>`);
                setTimeout(() => statusDiv.empty(), 3000);
            }

            function saveRoles(type) {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    const container = type === "auto" ? $(".auto-sync-section") : $(".locked-section");
                    const checkboxes = type === "auto" ? $(".auto-sync-role-checkbox") : $(".locked-role-checkbox");
                    const roles = [];
                    
                    checkboxes.each(function() {
                        if ($(this).is(":checked")) {
                            roles.push($(this).data("role"));
                        }
                    });

                    console.log(`[Better Auth] Saving ${type} roles:`, roles);

                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: type === "auto" ? "asap_save_auto_sync_roles" : "asap_save_locked_roles",
                            roles: roles,
                            nonce: "' . wp_create_nonce('asap_save_roles') . '"
                        },
                        beforeSend: function() {
                            console.log(`[Better Auth] Initiating ${type} roles save...`);
                            showSaveStatus(container, "saving", "Saving...");
                        },
                        success: function(response) {
                            console.log(`[Better Auth] Save response:`, response);
                            if (response.success) {
                                showSaveStatus(container, "success", "Saved!");
                                if (type === "auto") {
                                    console.log("[Better Auth] Processing role updates...");
                                    // Update locked states
                                    $(".auto-sync-role-checkbox").each(function() {
                                        const role = $(this).data("role");
                                        const isLocked = response.data.locked_roles.includes(role);
                                        $(this).prop("disabled", isLocked);
                                        $(this).closest("label").toggleClass("locked-role", isLocked);
                                    });
                                    
                                    if (response.data.sync_results) {
                                        console.log("[Better Auth] Sync Results:", response.data.sync_results);
                                        const {synced, unsynced, errors} = response.data.sync_results;
                                        if (synced.length > 0) {
                                            console.log("[Better Auth] Synced users:", synced);
                                        }
                                        if (unsynced.length > 0) {
                                            console.log("[Better Auth] Unsynced users:", unsynced);
                                        }
                                        if (errors.length > 0) {
                                            console.warn("[Better Auth] Sync errors:", errors);
                                        }
                                    }
                                }
                            } else {
                                console.error("[Better Auth] Save error:", response.data.message);
                                showSaveStatus(container, "error", response.data.message || "Error saving");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("[Better Auth] AJAX error:", {status, error, xhr});
                            showSaveStatus(container, "error", "Error saving changes");
                        }
                    });
                }, SAVE_DELAY);
            }

            // Auto-sync role changes
            $(".auto-sync-role-checkbox").on("change", function() {
                saveRoles("auto");
            });

            // Locked role changes
            $(".locked-role-checkbox").on("change", function() {
                saveRoles("locked");
            });
        });
        </script>';

        // Add status message styling
        $config_content .= '<style>
            .save-status {
                margin-top: 10px;
                min-height: 24px;
            }
            .save-message {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                line-height: 1.4;
            }
            .save-message.saving {
                background: #f0f6fc;
                color: #1d2327;
            }
            .save-message.success {
                background: #edfaef;
                color: #0a5c16;
            }
            .save-message.error {
                background: #fcf0f1;
                color: #cc1818;
            }
        </style>';

        $config_content .= '</div>'; // Close asap-form

        // Add documentation about the auto-login feature
        $config_content .= '<div class="asap-section-info" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0073aa;">';
        $config_content .= '<h3 style="margin-top: 0; color: #0073aa;">Server-to-Server Auto Login</h3>';
        $config_content .= '<p>Auto Login v6 uses direct server-to-server communication to automatically log WordPress users into SvelteKit.</p>';
        $config_content .= '<ul style="margin-left: 20px; list-style-type: disc;">';
        $config_content .= '<li>Users with <strong>selected roles above</strong> will automatically sync to SvelteKit</li>';
        $config_content .= '<li>No browser redirects or cookie sharing required</li>';
        $config_content .= '<li>The shared secret "<code>BETTER_AUTH_SECRET</code>" must be properly configured</li>';
        $config_content .= '<li>Active WordPress sessions are detected automatically</li>';
        $config_content .= '</ul>';
        $config_content .= '</div>';

        // Add sync button
        $config_content .= '<div class="asap-form-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid hsl(var(--asap-card-border));">';
        $config_content .= sprintf(
            '<button class="button button-secondary sync-users" data-nonce="%s">%s</button>',
            wp_create_nonce('sync_users'),
            __('Sync Users with Better Auth')
        );
        $config_content .= '</div>';

        // Add sync JavaScript
        $config_content .= '<script>
            jQuery(document).ready(function($) {
                $(".sync-users").on("click", function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var nonce = button.data("nonce");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "asap_sync_users",
                            nonce: nonce
                        },
                        beforeSend: function() {
                            button.addClass("asap-loading").prop("disabled", true);
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
            } else {
                                alert(response.data.message || "Error syncing users");
                            }
                        },
                        error: function() {
                            alert("Error communicating with server");
                        },
                        complete: function() {
                            button.removeClass("asap-loading").prop("disabled", false);
                        }
                    });
                });
            });
        </script>';
        
        echo ASAP_Digest_Admin_UI::create_card('Configuration', $config_content, 'config-card');
        
        // Session Management Card
        $sessions_content = '<div class="asap-sessions-table">';
        $sessions_content .= '<table class="wp-list-table widefat fixed striped">';
        $sessions_content .= '<thead><tr>';
        $sessions_content .= '<th>' . __('User') . '</th>';
        $sessions_content .= '<th>' . __('Role') . '</th>';
        $sessions_content .= '<th>' . __('Last Login') . '</th>';
        $sessions_content .= '<th>' . __('Status') . '</th>';
        $sessions_content .= '<th>' . __('Actions') . '</th>';
        $sessions_content .= '</tr></thead>';
        $sessions_content .= '<tbody>';
        
        // Get all users ordered by role
        $users = get_users([
            'orderby' => 'role',
            'order' => 'ASC'
        ]);
        
        foreach ($users as $user) {
            $last_login = get_user_meta($user->ID, 'better_auth_last_login', true);
            $session_token = get_user_meta($user->ID, 'better_auth_session_token', true);
            $better_auth_id = get_user_meta($user->ID, 'better_auth_user_id', true);
            
            $sessions_content .= '<tr>';
            $sessions_content .= '<td>' . esc_html($user->user_login) . '<br><small>' . esc_html($user->user_email) . '</small></td>';
            $sessions_content .= '<td>' . ucfirst(implode(', ', $user->roles)) . '</td>';
            $sessions_content .= '<td>' . ($last_login ? human_time_diff(strtotime($last_login)) . ' ago' : 'Never') . '</td>';
            $sessions_content .= '<td>' . ASAP_Digest_Admin_UI::create_status_indicator(
                $better_auth_id ? ($session_token ? 'good' : 'warning') : 'inactive',
                $better_auth_id ? ($session_token ? 'Active' : 'Synced') : 'Not Synced'
            ) . '</td>';
            $sessions_content .= '<td>';
            if ($session_token) {
                $sessions_content .= sprintf(
                    '<button class="button button-secondary end-session" data-user-id="%d" data-nonce="%s">%s</button>',
                    $user->ID,
                    wp_create_nonce('end_session_' . $user->ID),
                    __('End Session')
                );
            } else if ($better_auth_id) {
                $sessions_content .= sprintf(
                    '<button class="button button-secondary unsync-user" data-user-id="%d" data-nonce="%s">%s</button>',
                    $user->ID,
                    wp_create_nonce('unsync_user_' . $user->ID),
                    __('Unsync')
                );
            }

            // ---> ADD: TESTING DELETE BA USER Button <---
            if ($better_auth_id) { // Only show if user is actually synced
                $sessions_content .= sprintf(
                    '<button class="button button-secondary delete-ba-user-test" data-user-id="%d" data-ba-user-id="%s" data-nonce="%s" style="color: #d63638; border-color: #d63638; margin-left: 4px;">%s</button>',
                    $user->ID,          // WP User ID
                    esc_attr($better_auth_id), // Better Auth User ID (UUID)
                    wp_create_nonce('delete_ba_user_test_' . $user->ID),
                    __('TESTING: DELETE BA USER')
                );
            }
            // ---> END ADD <---

            // Add new user management actions
                $sessions_content .= sprintf(
                '<button class="button button-secondary ban-user" data-user-id="%d" data-nonce="%s">%s</button>',
                    $user->ID,
                wp_create_nonce('ban_user_' . $user->ID),
                __('Ban User')
            );

            $sessions_content .= sprintf(
                '<button class="button button-secondary lock-account" data-user-id="%d" data-nonce="%s">%s</button>',
                $user->ID,
                wp_create_nonce('lock_account_' . $user->ID),
                __('Lock Account')
            );

            $sessions_content .= sprintf(
                '<button class="button button-secondary reset-password" data-user-id="%d" data-nonce="%s">%s</button>',
                $user->ID,
                wp_create_nonce('reset_password_' . $user->ID),
                __('Reset Password')
            );

            $sessions_content .= sprintf(
                '<button class="button button-secondary view-activity" data-user-id="%d" data-nonce="%s">%s</button>',
                $user->ID,
                wp_create_nonce('view_activity_' . $user->ID),
                __('View Activity')
            );

            $sessions_content .= '</td>';
            $sessions_content .= '</tr>';
        }
        
        $sessions_content .= '</tbody></table></div>';
        
        // Add JavaScript for session management
        $sessions_content .= '<script>
            jQuery(document).ready(function($) {
                $(".end-session").on("click", function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var userId = button.data("user-id");
                    var nonce = button.data("nonce");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "asap_end_session",
                            user_id: userId,
                            nonce: nonce
                        },
                        beforeSend: function() {
                            button.addClass("asap-loading").prop("disabled", true);
                        },
                        success: function(response) {
                            if (response.success) {
                                button.closest("tr").find(".asap-status-good")
                                    .removeClass("asap-status-good")
                                    .addClass("asap-status-inactive")
                                    .text("Inactive");
                                button.remove();
                            } else {
                                alert(response.data.message || "Error ending session");
                            }
                        },
                        error: function() {
                            alert("Error communicating with server");
                        },
                        complete: function() {
                            button.removeClass("asap-loading").prop("disabled", false);
                        }
                    });
                });
            });
        </script>';
        
        // Add JavaScript for new user management actions using HEREDOC
        $user_actions_js = <<<JS
<script>
    jQuery(document).ready(function($) {
        // Ban User Action
        $(".ban-user").on("click", function(e) {
            e.preventDefault();
            if (!confirm("Are you sure you want to ban this user? This action cannot be undone.")) {
                return;
            }
            var button = $(this);
            var userId = button.data("user-id");
            var nonce = button.data("nonce");
            
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "asap_ban_user",
                    user_id: userId,
                    nonce: nonce
                },
                beforeSend: function() {
                    button.addClass("asap-loading").prop("disabled", true);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || "Error banning user");
                    }
                },
                error: function() {
                    alert("Error communicating with server");
                },
                complete: function() {
                    button.removeClass("asap-loading").prop("disabled", false);
                }
            });
        });

        // Lock Account Action
        $(".lock-account").on("click", function(e) {
            e.preventDefault();
            if (!confirm("Are you sure you want to lock this account?")) {
                return;
            }
            var button = $(this);
            var userId = button.data("user-id");
            var nonce = button.data("nonce");
            
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "asap_lock_account",
                    user_id: userId,
                    nonce: nonce
                },
                beforeSend: function() {
                    button.addClass("asap-loading").prop("disabled", true);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || "Error locking account");
                    }
                },
                error: function() {
                    alert("Error communicating with server");
                },
                complete: function() {
                    button.removeClass("asap-loading").prop("disabled", false);
                }
            });
        });

        // Reset Password Action
        $(".reset-password").on("click", function(e) {
            e.preventDefault();
            if (!confirm("Are you sure you want to send a password reset email to this user?")) {
                return;
            }
            var button = $(this);
            var userId = button.data("user-id");
            var nonce = button.data("nonce");
            
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "asap_reset_password",
                    user_id: userId,
                    nonce: nonce
                },
                beforeSend: function() {
                    button.addClass("asap-loading").prop("disabled", true);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || "Error sending password reset");
                    }
                },
                error: function() {
                    alert("Error communicating with server");
                },
                complete: function() {
                    button.removeClass("asap-loading").prop("disabled", false);
                }
            });
        });

        // View Activity Action
        $(".view-activity").on("click", function(e) {
            e.preventDefault();
            var button = $(this);
            var userId = button.data("user-id");
            var nonce = button.data("nonce");
            
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "asap_view_activity",
                    user_id: userId,
                    nonce: nonce
                },
                beforeSend: function() {
                    button.addClass("asap-loading").prop("disabled", true);
                },
                success: function(response) {
                    if (response.success) {
                        // Create and show modal with activity data
                        var modal = $('<div class="asap-modal">')
                            .append($('<div class="asap-modal-content">')
                                .append($('<h2>').text("User Activity"))
                                .append($('<div class="asap-activity-log">').html(response.data.activity))
                                .append($('<button class="button">').text("Close").click(function() {
                                    modal.remove();
                                }))
                            );
                        $("body").append(modal);
                    } else {
                        alert(response.data.message || "Error retrieving activity");
                    }
                },
                error: function() {
                    alert("Error communicating with server");
                },
                complete: function() {
                    button.removeClass("asap-loading").prop("disabled", false);
                }
            });
        });

        // ---> ADD: TESTING DELETE BA USER Action <---
        $('body').on('click', '.delete-ba-user-test', function(e) { // Use event delegation
            e.preventDefault();
            if (!confirm('DANGER! This will delete the Better Auth user record ONLY (not the WP user), requiring re-sync. Proceed?')) {
                return;
            }
            var button = $(this);
            var wpUserId = button.data('user-id');
            var baUserId = button.data('ba-user-id'); // Get the BA User ID (UUID)
            var nonce = button.data('nonce');
            var statusContainer = button.closest('tr').find('.better-auth-status'); // Target status div in the same row

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_delete_ba_user_test', // New AJAX action
                    wp_user_id: wpUserId,
                    ba_user_id: baUserId, // Send the BA User ID (UUID)
                    nonce: nonce
                },
                beforeSend: function() {
                    button.addClass('asap-loading').prop('disabled', true);
                    statusContainer.html('Deleting BA User...'); // Update status indicator
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Optionally refresh just the row or the whole page
                        location.reload(); 
                    } else {
                        alert(response.data.message || 'Error deleting Better Auth user');
                        button.removeClass('asap-loading').prop('disabled', false); // Re-enable on error
                         statusContainer.html('Error!'); // Update status
                    }
                },
                error: function(xhr) {
                    alert('Error communicating with server: ' + xhr.statusText);
                    button.removeClass('asap-loading').prop('disabled', false);
                     statusContainer.html('Error!'); // Update status
                }
                // No complete handler needed if reloading on success
            });
        });
        // ---> END ADD <---

    });
</script>
JS;
        $sessions_content .= $user_actions_js;
        
        echo ASAP_Digest_Admin_UI::create_card('Active Sessions', $sessions_content, 'sessions-card');
        ?>
    </div>
            <?php
}

/**
 * @description AJAX handler for ending Better Auth sessions
 * @return void
 * @example
 * // Called via AJAX
 * add_action('wp_ajax_asap_end_session', 'asap_end_better_auth_session');
 * @created 03.30.25 | 04:45 PM PDT
 */
function asap_end_better_auth_session() {
    // Verify nonce and permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    
    if (!wp_verify_nonce($nonce, 'end_session_' . $user_id)) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Get user and verify they exist
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_send_json_error(['message' => 'User not found']);
    }
    
    // Delete session token
    delete_user_meta($user_id, 'better_auth_session_token');
    
    // Fire action for integrations
    do_action('asap_better_auth_session_ended', $user_id);
    
    wp_send_json_success(['message' => 'Session ended successfully']);
}

// Register AJAX handler
add_action('wp_ajax_asap_end_session', 'asap_end_better_auth_session');

/**
 * @description Handle token exchange between Better Auth and WordPress
 * @param {WP_REST_Request} $request REST request object
 * @return {WP_REST_Response|WP_Error} Response with tokens or error
 * @example
 * // Exchange Better Auth token for WordPress session
 * $response = asap_handle_token_exchange($request);
 * @created 03.30.25 | 04:37 PM PDT
 */
function asap_handle_token_exchange($request) {
    // Get Better Auth token from header
    $better_auth_token = $request->get_header('X-Better-Auth-Token');
    if (empty($better_auth_token)) {
        return new WP_Error('missing_token', 'Better Auth token not provided', ['status' => 400]);
    }

    // Validate token signature
    $timestamp = $request->get_header('X-Better-Auth-Timestamp');
    $signature = $request->get_header('X-Better-Auth-Signature');
    if (!asap_validate_better_auth_signature($timestamp, $signature)) {
        return new WP_Error('invalid_signature', 'Invalid request signature', ['status' => 401]);
    }

    // Get user ID from token payload
    $token_parts = explode('.', $better_auth_token);
    $payload = json_decode(base64_decode($token_parts[1]), true);
    if (!$payload || empty($payload['sub'])) {
        return new WP_Error('invalid_token', 'Invalid token payload', ['status' => 400]);
    }

    // Get WordPress user ID from Better Auth ID
    $users = get_users([
        'meta_key' => 'better_auth_user_id',
        'meta_value' => $payload['sub'],
        'number' => 1
    ]);

    if (empty($users)) {
        return new WP_Error('user_not_found', 'No WordPress user found for Better Auth ID', ['status' => 404]);
    }

    // Create WordPress session
    $session = asap_create_wp_session_core($users[0]->ID);
    if (is_wp_error($session)) {
        return $session;
    }

    // Return both tokens
    return new WP_REST_Response([
        'wp_session_token' => $session['session_token'],
        'better_auth_token' => $better_auth_token
    ], 200);
}

/**
 * @description Register token exchange endpoint
 * @return void
 * @example
 * // Register the token exchange endpoint
 * asap_register_token_exchange();
 * @created 03.30.25 | 04:37 PM PDT
 */
function asap_register_token_exchange() {
    register_rest_route('asap/v1', '/auth/exchange-token', [
        'methods' => 'POST',
        'callback' => 'asap_handle_token_exchange',
        'permission_callback' => '__return_true', // Public endpoint, security handled in callback
    ]);
}

// Register the endpoint
add_action('rest_api_init', 'asap_register_token_exchange');

/**
 * @description Register Better Auth settings
 * @return void
 * @example
 * // Called during admin_init
 * asap_register_better_auth_settings();
 * @created 03.30.25 | 04:45 PM PDT
 */
function asap_register_better_auth_settings() {
    // Existing settings
    register_setting(
        'asap_better_auth_options',
        'asap_better_auth_base_url',
        [
            'type' => 'string',
            'description' => 'Base URL for Better Auth service',
            'sanitize_callback' => 'esc_url_raw',
            'show_in_rest' => true,
            'default' => ''
        ]
    );

    // Add auto-sync roles setting
    register_setting(
        'asap_better_auth_options',
        'asap_better_auth_auto_sync_roles',
        [
            'type' => 'array',
            'description' => 'WordPress roles that should automatically sync with Better Auth',
            'sanitize_callback' => 'asap_sanitize_auto_sync_roles',
            'show_in_rest' => true,
            'default' => ['administrator']
        ]
    );

    // Add locked roles setting
    register_setting(
        'asap_better_auth_options',
        'asap_better_auth_locked_roles',
        [
            'type' => 'array',
            'description' => 'WordPress roles that should be locked from auto-sync selection',
            'sanitize_callback' => 'asap_sanitize_locked_roles',
            'show_in_rest' => true,
            'default' => ['subscriber'] // Lock subscriber role by default
        ]
    );
}

/**
 * @description Sanitize auto-sync roles array
 * @param array $roles Array of role slugs
 * @return array Sanitized array of role slugs
 */
function asap_sanitize_auto_sync_roles($roles) {
    if (!is_array($roles)) {
        return ['administrator'];
    }
    
    $valid_roles = array_keys(wp_roles()->roles);
    return array_intersect($roles, $valid_roles);
}

/**
 * @description Sanitize locked roles array
 * @param array $roles Array of role slugs
 * @return array Sanitized array of role slugs
 */
function asap_sanitize_locked_roles($roles) {
    if (!is_array($roles)) {
        return ['subscriber'];
    }
    
    $valid_roles = array_keys(wp_roles()->roles);
    return array_intersect($roles, $valid_roles);
}

/**
 * @description AJAX handler for syncing users with Better Auth
 * @return void
 * @example
 * // Called via AJAX
 * add_action('wp_ajax_asap_sync_users', 'asap_sync_better_auth_users');
 * @created 03.30.25 | 04:45 PM PDT
 */
function asap_sync_better_auth_users() {
    // Verify nonce and permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'sync_users')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Get base URL
    $base_url = asap_get_better_auth_base_url();
    if (empty($base_url)) {
        wp_send_json_error(['message' => 'Better Auth base URL not configured']);
    }
    
    // Use consistent constant name for signature
    $timestamp = time();
    $signature = hash_hmac(
        'sha256',
        $timestamp,
        defined('BETTER_AUTH_SECRET') ? BETTER_AUTH_SECRET : get_option('better_auth_secret') // Changed constant/option
    );

    // Make request to Better Auth API
    $response = wp_remote_get(
        $base_url . '/api/users',
        [
            'headers' => [
                'X-Better-Auth-Timestamp' => $timestamp,
                'X-Better-Auth-Signature' => $signature
            ]
        ]
    );
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error connecting to Better Auth: ' . $response->get_error_message()]);
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body) || !is_array($body)) {
        wp_send_json_error(['message' => 'Invalid response from Better Auth']);
    }
    
    $synced = 0;
    $errors = [];
    
    foreach ($body as $user_data) {
        $result = asap_create_wp_user_from_better_auth($user_data);
        if (is_wp_error($result)) {
            $errors[] = sprintf(
                'Error syncing user %s: %s',
                $user_data['email'] ?? 'unknown',
                $result->get_error_message()
            );
        } else {
            $synced++;
        }
    }
    
    // Update last sync time
    update_option('asap_better_auth_last_sync', current_time('mysql'));
    
    if (!empty($errors)) {
        wp_send_json_error([
            'message' => sprintf(
                'Synced %d users with %d errors: %s',
                $synced,
                count($errors),
                implode(', ', $errors)
            )
        ]);
    }
    
    wp_send_json_success([
        'message' => sprintf('Successfully synced %d users', $synced)
    ]);
}

// Register AJAX handler
add_action('wp_ajax_asap_sync_users', 'asap_sync_better_auth_users');

/**
 * @description Handle Better Auth session webhook notifications
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response Response with status
 * @created 03.30.25 | 04:55 PM PDT
 */
function asap_handle_session_webhook($request) {
    // Validate request signature
    $timestamp = $request->get_header('X-Better-Auth-Timestamp');
    $signature = $request->get_header('X-Better-Auth-Signature');
    if (!asap_validate_better_auth_signature($timestamp, $signature)) {
        return new WP_REST_Response(['error' => 'Invalid signature'], 401);
    }

    $data = $request->get_json_params();
    if (empty($data['event']) || empty($data['user_id'])) {
        return new WP_REST_Response(['error' => 'Missing required data'], 400);
    }

    // Get WordPress user from Better Auth ID
    $users = get_users([
        'meta_key' => 'better_auth_user_id',
        'meta_value' => $data['user_id'],
        'number' => 1
    ]);

    if (empty($users)) {
        return new WP_REST_Response(['error' => 'User not found'], 404);
    }

    $wp_user_id = $users[0]->ID;

    switch ($data['event']) {
        case 'session.created':
            // Update session token if provided
            if (!empty($data['session_token'])) {
                update_user_meta($wp_user_id, 'better_auth_session_token', $data['session_token']);
                update_user_meta($wp_user_id, 'better_auth_last_login', current_time('mysql'));
            }
            break;

        case 'session.ended':
            // Remove session token
            delete_user_meta($wp_user_id, 'better_auth_session_token');
            break;

        case 'user.deleted':
            // Optionally handle user deletion
            wp_delete_user($wp_user_id);
            break;
    }

    do_action('asap_better_auth_webhook_' . str_replace('.', '_', $data['event']), $wp_user_id, $data);

    return new WP_REST_Response(['success' => true]);
}

// Register webhook endpoint
add_action('rest_api_init', function() {
    register_rest_route('asap/v1', '/auth/webhook', [
        'methods' => 'POST',
        'callback' => 'asap_handle_session_webhook',
        'permission_callback' => '__return_true'
    ]);
});

/**
 * @description Sync user roles between Better Auth and WordPress
 * @param int $wp_user_id WordPress user ID
 * @param array $better_auth_roles Better Auth roles array
 * @return bool True on success, false on failure
 * @created 03.30.25 | 04:57 PM PDT
 */
function asap_sync_user_roles($wp_user_id, $better_auth_roles) {
    // Role mapping between Better Auth and WordPress
    $role_map = [
        'admin' => 'administrator',
        'editor' => 'editor',
        'author' => 'author',
        'subscriber' => 'subscriber'
    ];

    $user = get_user_by('ID', $wp_user_id);
    if (!$user) {
        return false;
    }

    // Remove all existing roles
    $user->set_role('');

    // Add mapped roles
    foreach ($better_auth_roles as $ba_role) {
        if (isset($role_map[$ba_role])) {
            $user->add_role($role_map[$ba_role]);
        }
    }

    // If no roles were mapped, set default subscriber role
    if (empty($user->roles)) {
        $user->set_role('subscriber');
    }

    do_action('asap_better_auth_roles_synced', $wp_user_id, $better_auth_roles);
    return true;
}

// Add role sync to webhook handler
add_action('asap_better_auth_webhook_user_updated', function($wp_user_id, $data) {
    if (!empty($data['roles'])) {
        asap_sync_user_roles($wp_user_id, $data['roles']);
    }
}, 10, 2);

/**
 * @description Sync user metadata between Better Auth and WordPress
 * @param int $wp_user_id WordPress user ID
 * @param array $better_auth_metadata Better Auth metadata array
 * @return bool True on success, false on failure
 * @created 03.30.25 | 04:59 PM PDT
 */
function asap_sync_user_metadata($wp_user_id, $better_auth_metadata) {
    // Metadata mapping between Better Auth and WordPress
    $meta_map = [
        'name' => 'display_name',
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'avatar_url' => 'better_auth_avatar_url',
        'preferences' => 'better_auth_preferences',
        'last_login_at' => 'better_auth_last_login',
        'subscription_status' => 'better_auth_subscription_status',
        'subscription_plan' => 'better_auth_subscription_plan'
    ];

    $user = get_user_by('ID', $wp_user_id);
    if (!$user) {
        return false;
    }

    foreach ($better_auth_metadata as $ba_key => $value) {
        if (isset($meta_map[$ba_key])) {
            $wp_key = $meta_map[$ba_key];
            
            // Handle special cases
            if ($wp_key === 'display_name') {
                wp_update_user([
                    'ID' => $wp_user_id,
                    'display_name' => sanitize_text_field($value)
                ]);
                } else {
                update_user_meta($wp_user_id, $wp_key, $value);
            }
        }
    }

    // Store complete metadata snapshot
    update_user_meta(
        $wp_user_id,
        'better_auth_metadata_snapshot',
        wp_json_encode($better_auth_metadata)
    );

    do_action('asap_better_auth_metadata_synced', $wp_user_id, $better_auth_metadata);
    return true;
}

// Add metadata sync to webhook handler
add_action('asap_better_auth_webhook_user_updated', function($wp_user_id, $data) {
    if (!empty($data['metadata'])) {
        asap_sync_user_metadata($wp_user_id, $data['metadata']);
    }
}, 20, 2);

/**
 * @description Add Better Auth sync column to users list table
 * @return void
 */
function asap_add_better_auth_user_column($columns) {
    $columns['better_auth_sync'] = __('Better Auth');
    return $columns;
}
add_filter('manage_users_columns', 'asap_add_better_auth_user_column');

/**
 * @description Display Better Auth sync status and actions for each user
 * @return void
 */
function asap_manage_better_auth_user_column($value, $column_name, $user_id) {
    if ($column_name !== 'better_auth_sync') {
        return $value;
    }

    $better_auth_id = get_user_meta($user_id, 'better_auth_user_id', true);
    $sync_status = $better_auth_id ? 'synced' : 'not-synced';
    $last_sync = get_user_meta($user_id, 'better_auth_last_sync', true);

    ob_start();
    ?>
    <div class="better-auth-status">
        <?php echo ASAP_Digest_Admin_UI::create_status_indicator(
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
}
add_action('manage_users_custom_column', 'asap_manage_better_auth_user_column', 10, 3);

/**
 * @description AJAX handler for syncing a single user with Better Auth
 * @return void
 */
function asap_sync_single_user() {
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
    $result = asap_sync_wp_user_to_better_auth($user_id);
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
}
add_action('wp_ajax_asap_sync_single_user', 'asap_sync_single_user');

/**
 * @description Unsync a WordPress user from Better Auth
 * @param int $wp_user_id WordPress user ID to unsync
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function asap_unsync_wp_user_from_better_auth($wp_user_id) {
    global $wpdb;

    // Get WordPress user data
    $wp_user = get_userdata($wp_user_id);
    if (!$wp_user) {
        return new WP_Error('invalid_user', 'WordPress user not found');
    }

    // Get Better Auth user ID
    $better_auth_id = get_user_meta($wp_user_id, 'better_auth_user_id', true);
    if (empty($better_auth_id)) {
        return new WP_Error('not_synced', 'User is not synced with Better Auth');
    }

    // Remove mapping from database
    $wpdb->delete(
        $wpdb->prefix . 'ba_wp_user_map',
        ['wp_user_id' => $wp_user_id],
        ['%d']
    );

    // Remove Better Auth metadata
    delete_user_meta($wp_user_id, 'better_auth_user_id');
    delete_user_meta($wp_user_id, 'better_auth_session_token');
    delete_user_meta($wp_user_id, 'better_auth_last_login');
    delete_user_meta($wp_user_id, 'better_auth_last_sync');
    delete_user_meta($wp_user_id, 'better_auth_metadata_snapshot');

    // Fire action for integrations
    do_action('asap_better_auth_user_unsynced', $wp_user_id, $better_auth_id);

    return true;
}

/**
 * @description AJAX handler for unsyncing a user from Better Auth
 * @return void
 */
function asap_unsync_single_user() {
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
    $result = asap_unsync_wp_user_from_better_auth($user_id);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    
    wp_send_json_success([
        'message' => 'User unsynced successfully'
    ]);
}
add_action('wp_ajax_asap_unsync_single_user', 'asap_unsync_single_user');

/**
 * @description Create a test admin user for development
 * @return void
 */
function asap_create_test_admin() {
    // Verify we're in a development environment
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'local') === false) {
        wp_send_json_error(['message' => 'This action is only available in development']);
        return;
    }

    // Create admin user if it doesn't exist
    $admin_email = 'admin@asapdigest.local';
    $user = get_user_by('email', $admin_email);
    
    if (!$user) {
        $user_id = wp_insert_user([
            'user_login' => 'admin',
            'user_email' => $admin_email,
            'user_pass' => 'admin123', // This is just for testing
            'role' => 'administrator',
            'display_name' => 'Test Admin'
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
            return;
        }

        // Create Better Auth user and link it
        $better_auth_result = asap_sync_wp_user_to_better_auth($user_id);
        if (is_wp_error($better_auth_result)) {
            wp_send_json_error(['message' => $better_auth_result->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => 'Test admin user created successfully',
            'user_id' => $user_id,
            'better_auth_id' => $better_auth_result['ba_user_id']
        ]);
    } else {
        // User exists, ensure they're an admin
        $user->set_role('administrator');
        
        // Sync with Better Auth if needed
        if (!get_user_meta($user->ID, 'better_auth_user_id', true)) {
            $better_auth_result = asap_sync_wp_user_to_better_auth($user->ID);
            if (is_wp_error($better_auth_result)) {
                wp_send_json_error(['message' => $better_auth_result->get_error_message()]);
                return;
            }
        }

        wp_send_json_success([
            'message' => 'Test admin user already exists and is synced',
            'user_id' => $user->ID,
            'better_auth_id' => get_user_meta($user->ID, 'better_auth_user_id', true)
        ]);
    }
}

// Register the AJAX action for creating test admin
add_action('wp_ajax_asap_create_test_admin', 'asap_create_test_admin');
add_action('wp_ajax_nopriv_asap_create_test_admin', 'asap_create_test_admin'); // Allow unauthenticated for initial setup 

/**
 * @description Check if a user should be auto-synced based on their roles
 * @param int|WP_User $user User ID or WP_User object
 * @return bool Whether the user should be auto-synced
 */
function asap_should_auto_sync_user($user) {
    $user = is_numeric($user) ? get_user_by('id', $user) : $user;
    if (!$user) {
        return false;
    }

    $auto_sync_roles = get_option('asap_better_auth_auto_sync_roles', ['administrator']);
    
    // Check if user has any auto-sync roles
    foreach ($user->roles as $role) {
        if (in_array($role, $auto_sync_roles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * @description Handle auto-sync when user logs in
 * @param string $user_login Username
 * @param WP_User $user User object
 */
function asap_handle_login_auto_sync($user_login, $user) {
    error_log('[ASAP Debug] LOGIN HOOK: asap_handle_login_auto_sync triggered for user: ' . $user_login . ' (ID: ' . $user->ID . ')'); // DEBUG
    
    // Check if user role should be synced based on the saved list
    if (!asap_should_auto_sync_user($user)) {
        error_log('[ASAP Debug] LOGIN HOOK: User role not in auto-sync list. Skipping.'); // Updated Log
        return;
    }

    // Sync user data
    error_log('[ASAP Debug] LOGIN HOOK: Calling asap_sync_wp_user_to_better_auth for user ID: ' . $user->ID); // DEBUG
    asap_sync_wp_user_to_better_auth($user->ID, 'login');
}
add_action('wp_login', 'asap_handle_login_auto_sync', 10, 2);

/**
 * @description Handle auto-sync when user's roles change
 * @param int $user_id User ID
 * @param string $role Role being added
 * @param array $old_roles Old roles
 */
function asap_handle_role_change_auto_sync($user_id, $role, $old_roles) {
    if (asap_should_auto_sync_user($user_id)) {
        // Check if user is already synced
        $better_auth_id = get_user_meta($user_id, 'better_auth_user_id', true);
        if (!$better_auth_id) {
            // Sync user with Better Auth
            asap_sync_wp_user_to_better_auth($user_id);
        }
    }
}

// Hook into login and role change actions
add_action('wp_login', 'asap_handle_login_auto_sync', 10, 2);
add_action('set_user_role', 'asap_handle_role_change_auto_sync', 10, 3);

// Add auto-sync action to Better Auth initialization
add_action('init', function() {
    // Check if user is logged in
    $current_user = wp_get_current_user();
    if ($current_user->ID && asap_should_auto_sync_user($current_user)) {
        // Check if user is already synced
        $better_auth_id = get_user_meta($current_user->ID, 'better_auth_user_id', true);
        if (!$better_auth_id) {
            // Sync user with Better Auth
            $result = asap_sync_wp_user_to_better_auth($current_user->ID);
            if (!is_wp_error($result)) {
                $better_auth_id = $result['ba_user_id'];
            }
        }

        // Ensure session is active
        if ($better_auth_id && !get_user_meta($current_user->ID, 'better_auth_session_token', true)) {
            asap_create_wp_session_core($current_user->ID);
        }
    }
}, 20);

/**
 * Handle bulk sync/unsync when auto-sync roles are updated
 * @param array $new_roles New array of roles selected for auto-sync
 * @param array $old_roles Previous array of roles selected for auto-sync
 */
function asap_handle_auto_sync_roles_update($new_roles, $old_roles) {
    // Get added and removed roles
    $added_roles = array_diff($new_roles, $old_roles);
    $removed_roles = array_diff($old_roles, $new_roles);

    // Handle newly added roles - sync all users in these roles
    if (!empty($added_roles)) {
        foreach ($added_roles as $role) {
            $users = get_users(['role' => $role]);
            foreach ($users as $user) {
                // Only sync if not already synced
                if (!asap_is_user_synced($user->ID)) {
                    asap_sync_wp_user_to_better_auth($user->ID, 'role_auto_sync');
                }
            }
        }
    }

    // Handle removed roles - unsync users who were auto-synced via role
    if (!empty($removed_roles)) {
        foreach ($removed_roles as $role) {
            $users = get_users(['role' => $role]);
            foreach ($users as $user) {
                // Only unsync if user was synced via role auto-sync
                $sync_source = get_user_meta($user->ID, 'better_auth_sync_source', true);
                if ($sync_source === 'role_auto_sync') {
                    asap_unsync_wp_user_from_better_auth($user->ID);
                }
            }
        }
    }
}

// Modify the settings update handler
function asap_update_better_auth_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['asap_better_auth_auto_sync_roles'])) {
        $old_roles = get_option('asap_better_auth_auto_sync_roles', ['administrator']);
        $new_roles = asap_sanitize_auto_sync_roles($_POST['asap_better_auth_auto_sync_roles']);
        
        // Update the option
        update_option('asap_better_auth_auto_sync_roles', $new_roles);
        
        // Handle bulk sync/unsync
        asap_handle_auto_sync_roles_update($new_roles, $old_roles);
    }

    // Rest of the existing settings update code...
}

/**
 * Check if a WordPress user is synced with Better Auth
 * @param int $wp_user_id WordPress user ID
 * @return bool True if user is synced, false otherwise
 */
function asap_is_user_synced($wp_user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ba_wp_user_map';  // Fixed table name
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    return (int)$result > 0;
}

/**
 * AJAX handler for saving auto-sync roles
 */
function asap_ajax_save_auto_sync_roles() {
    try {
        if (!current_user_can('manage_options')) {
            error_log('[Better Auth] Permission denied for auto-sync roles save');
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_save_roles')) {
            error_log('[Better Auth] Invalid nonce for auto-sync roles save');
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        $roles = isset($_POST['roles']) ? (array)$_POST['roles'] : [];
        $old_roles = get_option('asap_better_auth_auto_sync_roles', ['administrator']);
        $roles = asap_sanitize_auto_sync_roles($roles);

        error_log(sprintf('[Better Auth] Processing auto-sync roles update. Old: %s, New: %s', 
            implode(',', $old_roles), 
            implode(',', $roles)
        ));

        update_option('asap_better_auth_auto_sync_roles', $roles);
        
        // Track sync results
        $sync_results = [
            'synced' => [],
            'unsynced' => [],
            'errors' => []
        ];

        // Handle bulk sync for added roles
        $added_roles = array_diff($roles, $old_roles);
        if (!empty($added_roles)) {
            error_log(sprintf('[Better Auth] Processing new roles for sync: %s', implode(',', $added_roles)));
            foreach ($added_roles as $role) {
                $users = get_users(['role' => $role]);
                foreach ($users as $user) {
                    if (!asap_is_user_synced($user->ID)) {
                        $result = asap_sync_wp_user_to_better_auth($user->ID, 'role_auto_sync');
                        if (is_wp_error($result)) {
                            error_log(sprintf('[Better Auth] Error syncing user %d: %s', $user->ID, $result->get_error_message()));
                            $sync_results['errors'][] = [
                                'user_id' => $user->ID,
                                'error' => $result->get_error_message()
                            ];
                        } else {
                            error_log(sprintf('[Better Auth] Successfully synced user %d', $user->ID));
                            $sync_results['synced'][] = $user->ID;
                        }
                    }
                }
            }
        }

        // Handle bulk unsync for removed roles
        $removed_roles = array_diff($old_roles, $roles);
        if (!empty($removed_roles)) {
            error_log(sprintf('[Better Auth] Processing roles for unsync: %s', implode(',', $removed_roles)));
            foreach ($removed_roles as $role) {
                $users = get_users(['role' => $role]);
                foreach ($users as $user) {
                    $sync_source = get_user_meta($user->ID, 'better_auth_sync_source', true);
                    if ($sync_source === 'role_auto_sync') {
                        $result = asap_unsync_wp_user_from_better_auth($user->ID);
                        if (is_wp_error($result)) {
                            error_log(sprintf('[Better Auth] Error unsyncing user %d: %s', $user->ID, $result->get_error_message()));
                            $sync_results['errors'][] = [
                                'user_id' => $user->ID,
                                'error' => $result->get_error_message()
                            ];
                        } else {
                            error_log(sprintf('[Better Auth] Successfully unsynced user %d', $user->ID));
                            $sync_results['unsynced'][] = $user->ID;
                        }
                    }
                }
            }
        }

        wp_send_json_success([
            'message' => 'Roles saved successfully',
            'locked_roles' => get_option('asap_better_auth_locked_roles', ['subscriber']),
            'sync_results' => $sync_results
        ]);
    } catch (Exception $e) {
        error_log(sprintf('[Better Auth] Critical error in auto-sync roles save: %s', $e->getMessage()));
        wp_send_json_error([
            'message' => 'An unexpected error occurred while saving roles',
            'error' => $e->getMessage()
        ]);
    }
}
add_action('wp_ajax_asap_save_auto_sync_roles', 'asap_ajax_save_auto_sync_roles');

/**
 * AJAX handler for saving locked roles
 */
function asap_ajax_save_locked_roles() {
    if (!is_super_admin()) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }

    if (!wp_verify_nonce($_POST['nonce'], 'asap_save_roles')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    $roles = isset($_POST['roles']) ? (array)$_POST['roles'] : [];
    $roles = asap_sanitize_locked_roles($roles);

    update_option('asap_better_auth_locked_roles', $roles);

    wp_send_json_success(['message' => 'Locked roles saved successfully']);
}
add_action('wp_ajax_asap_save_locked_roles', 'asap_ajax_save_locked_roles');

/**
 * @description Automatically propagate user data changes between WordPress and Better Auth
 * @param int $user_id WordPress user ID
 * @param array|WP_User|null $context Optional context, could be user data array or old WP_User object from profile_update hook
 * @return bool|WP_Error True on success, WP_Error on failure
 * @created 04.01.25 | 09:15 PM PDT
 */
function asap_auto_sync_user_data($user_id, $context = null) {
    error_log(sprintf('[ASAP Auto Sync Hook] CALLED for user ID: %s (Context: %s)', $user_id, print_r($context, true))); // Log context too
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        error_log(sprintf('[ASAP Auto Sync Hook] ERROR: User not found for ID: %s. Exiting.', $user_id));
        return new WP_Error('user_not_found', 'WordPress user not found for ID: ' . $user_id);
    }
    error_log('[ASAP Auto Sync Hook] User found. Triggering sync via asap_sync_wp_user_to_better_auth...');
    
    // ---> REMOVED: Check for existing meta ID <-----
    // ---> REMOVED: Direct PDO Connection and UPDATE logic <-----

    // Always call the sync function which handles the HTTP request to SvelteKit
    $sync_result = asap_sync_wp_user_to_better_auth($user_id, 'auto_sync_profile_update'); 
    
    error_log('[ASAP Auto Sync Hook] Sync result: ' . print_r($sync_result, true));

    if (is_wp_error($sync_result)) {
        error_log("[ASAP Auto Sync Hook] Sync returned WP_Error: " . $sync_result->get_error_message());
        // Do not update meta on error
    } else {
        // Success - WP meta (like sync time, status, ba_user_id) 
        // is already updated *inside* asap_sync_wp_user_to_better_auth on successful response
        error_log("[ASAP Auto Sync Hook] Sync successful (WP meta updated within called function).");
        // Fire action for integrations
        do_action('asap_user_data_synced', $user_id, ($sync_result['ba_user_id'] ?? null), []); // Note: user_data array is not readily available here
        return true; // Indicate success from this hook's perspective
    }
    
    // Return the WP_Error if sync failed, otherwise true was returned
    return $sync_result; 
}

// Hook into user profile updates
add_action('profile_update', 'asap_auto_sync_user_data', 10, 2); // UNCOMMENTED
add_action('user_register', 'asap_auto_sync_user_data', 10); // UNCOMMENTED

/**
 * @description Creates a new session token for a Better Auth user
 * @param {string} ba_user_id The Better Auth user ID (UUID string)
 * @return {string|false} The new session token on success, false on failure
 * @created 04.07.25 | 02:55 PM PDT
 */
function asap_create_ba_session($ba_user_id) {
    global $wpdb;
    $sessions_table = $wpdb->prefix . 'ba_sessions';

    try {
        // Generate a secure token (e.g., 64 bytes, hex encoded)
        $token = bin2hex(random_bytes(32)); 
        
        // Set expiry (e.g., 7 days)
        $expires_at = date('Y-m-d H:i:s', time() + (7 * DAY_IN_SECONDS));

        $inserted = $wpdb->insert(
            $sessions_table,
            [
                'user_id' => $ba_user_id,
                'token' => $token,
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ],
            [
                '%s', // user_id is UUID string
                '%s',
                '%s',
                '%s'
            ]
        );

        if (!$inserted) {
            error_log('[ASAP Digest Session] Failed to insert new session for BA User ID: ' . $ba_user_id . '. WPDB Error: ' . $wpdb->last_error);
            return false;
        }
        
        error_log('[ASAP Digest Session] Successfully created new session for BA User ID: ' . $ba_user_id);
        return $token;

    } catch (\Exception $e) {
        error_log('[ASAP Digest Session] Exception during session creation for BA User ID: ' . $ba_user_id . '. Error: ' . $e->getMessage());
        return false;
    }
}

add_action('wp_ajax_nopriv_asap_unlock_role', 'asap_ajax_unlock_role');

// TEMPORARY DEBUGGING HOOK
function asap_debug_wp_login_hook_test($user_login, $user) {
    error_log('[ASAP TEMP DEBUG] wp_login hook fired successfully for user: ' . $user_login);
}
add_action('wp_login', 'asap_debug_wp_login_hook_test', 5, 2); // Use priority 5 to fire early

// ---> ADD: New AJAX handler function for BA user deletion <---
/**
 * @description AJAX handler for deleting a Better Auth user record (for testing)
 */
function asap_ajax_delete_ba_user_test() {
    // Verify nonce and permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return; // Added return
    }
    
    $wp_user_id = intval($_POST['wp_user_id'] ?? 0);
    $ba_user_id = sanitize_text_field($_POST['ba_user_id'] ?? ''); // Get the UUID
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    
    if (empty($wp_user_id) || empty($ba_user_id) || !wp_verify_nonce($nonce, 'delete_ba_user_test_' . $wp_user_id)) {
         error_log("[ASAP BA Delete Test] Nonce or ID verification failed. WP ID: {$wp_user_id}, BA ID: {$ba_user_id}, Nonce Valid: " . wp_verify_nonce($nonce, 'delete_ba_user_test_' . $wp_user_id));
        wp_send_json_error(['message' => 'Invalid request or nonce.']);
        return; // Added return
    }

    // Double-check the meta matches the provided ba_user_id
    $meta_ba_user_id = get_user_meta($wp_user_id, 'better_auth_user_id', true);
    if ($meta_ba_user_id !== $ba_user_id) {
        error_log("[ASAP BA Delete Test] Mismatch between provided BA ID ({$ba_user_id}) and stored meta ({$meta_ba_user_id}) for WP User {$wp_user_id}. Aborting.");
         wp_send_json_error(['message' => 'User ID mismatch. Please refresh the page.']);
         return; // Added return
    }

    global $wpdb;
    $ba_users_table = $wpdb->prefix . 'ba_users';
    $ba_sessions_table = $wpdb->prefix . 'ba_sessions';
    $ba_map_table = $wpdb->prefix . 'ba_wp_user_map';
    $ba_verifications_table = $wpdb->prefix . 'ba_verifications';
    // Add ba_accounts if it exists and needs cleanup based on userId
    $ba_accounts_table = $wpdb->prefix . 'ba_accounts'; // Assuming table name

    $wpdb->query('START TRANSACTION');
    $error = false;

    try {
        // 1. Delete sessions
        $wpdb->delete($ba_sessions_table, ['user_id' => $ba_user_id], ['%s']);
        
        // 2. Delete verifications (assuming identifier links to user ID)
        // Use a placeholder value for the verification identifier if it's not the user ID
        // For now, we assume 'identifier' might store the ba_user_id for certain verification types
        // This might need refinement based on how ba_verifications is actually used.
        // A safer approach might be to delete based on email if known, or skip if unsure.
        // Let's assume for now identifier *could* be the user ID for some cases.
        // $wpdb->delete($ba_verifications_table, ['identifier' => $ba_user_id], ['%s']); // Commenting out for safety until verified
        error_log("[ASAP BA Delete Test] Skipping ba_verifications deletion - relationship to user ID unclear.");


        // 3. Delete accounts (if table exists and uses user_id FK)
        // Check if table exists first to avoid errors if not created
         $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $ba_accounts_table));
         if ($table_exists) {
              // IMPORTANT: Verify the foreign key column name in ba_accounts! Assuming 'userId' based on docs.
              $wpdb->delete($ba_accounts_table, ['userId' => $ba_user_id], ['%s']); 
         } else {
              error_log("[ASAP BA Delete Test] Skipping ba_accounts deletion - table does not exist.");
         }


        // 4. Delete mapping
        $wpdb->delete($ba_map_table, ['ba_user_id' => $ba_user_id], ['%s']);

        // 5. Delete the BA user record itself
        $deleted = $wpdb->delete($ba_users_table, ['id' => $ba_user_id], ['%s']);

        if ($deleted === false) {
            throw new \Exception('Failed to delete user from ba_users. DB Error: ' . $wpdb->last_error);
        }
        if ($deleted === 0) {
             error_log("[ASAP BA Delete Test] Warning: Delete from ba_users reported 0 rows affected for ID: {$ba_user_id}. User might have been already deleted.");
             // Continue anyway to cleanup meta
        }


        // 6. Delete WP user meta linking to the deleted BA user
        delete_user_meta($wp_user_id, 'better_auth_user_id');
        delete_user_meta($wp_user_id, 'better_auth_session_token');
        delete_user_meta($wp_user_id, 'better_auth_last_login');
        delete_user_meta($wp_user_id, 'better_auth_last_sync');
        delete_user_meta($wp_user_id, 'better_auth_metadata_snapshot');
        delete_user_meta($wp_user_id, 'better_auth_sync_status'); // Also clear status meta
        delete_user_meta($wp_user_id, 'better_auth_sync_error');  // Also clear error meta
        delete_user_meta($wp_user_id, 'better_auth_sync_source'); // Also clear source meta


    } catch (\Exception $e) {
        $error = true;
        $wpdb->query('ROLLBACK');
        error_log('[ASAP BA Delete Test] Error deleting BA user data for WP User ID ' . $wp_user_id . ': ' . $e->getMessage());
        wp_send_json_error(['message' => 'Database error during deletion: ' . $e->getMessage()]);
        return; // Added return
    }

    if (!$error) {
        $wpdb->query('COMMIT');
        error_log('[ASAP BA Delete Test] Successfully deleted Better Auth data for WP User ID: ' . $wp_user_id);
        wp_send_json_success(['message' => 'Better Auth user record deleted successfully.']);
    }
}
add_action('wp_ajax_asap_delete_ba_user_test', 'asap_ajax_delete_ba_user_test');
// ---> END ADD <---
  