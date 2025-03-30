<?php
/**
 * Better Auth Configuration for WordPress Integration
 * 
 * This file contains configuration settings for Better Auth WordPress integration.
 * It should be included in the main plugin file.
 * 
 * @package ASAPDigest_Core
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Set up Better Auth shared secret
 * This will be used to validate requests from Better Auth to WordPress
 */
function asap_setup_better_auth_shared_secret() {
    // First, check if the constant is already defined in wp-config.php
    if (!defined('BETTER_AUTH_SHARED_SECRET')) {
        // Use WordPress AUTH_KEY as the shared secret if not defined
        // This ensures that we have a unique secret per site that's already secure
        if (defined('AUTH_KEY') && !empty(AUTH_KEY)) {
            define('BETTER_AUTH_SHARED_SECRET', AUTH_KEY);
        } else {
            // Extremely unlikely fallback
            define('BETTER_AUTH_SHARED_SECRET', wp_generate_password(64, true, true));
            
            // Log the issue
            error_log('[ASAP Better Auth] WARNING: Using generated password for BETTER_AUTH_SHARED_SECRET. Define in wp-config.php for security.');
        }
    }
    
    // Store the value in options for easier access
    if (!get_option('better_auth_shared_secret')) {
        update_option('better_auth_shared_secret', BETTER_AUTH_SHARED_SECRET);
    }
}

// Run setup function
asap_setup_better_auth_shared_secret();

/**
 * Safely get a constant value
 * 
 * @param string $constant_name Constant name
 * @return mixed Constant value or null if not defined
 */
function asap_get_constant($constant_name) {
    return defined($constant_name) ? constant($constant_name) : null;
}

/**
 * Get the base URL for SvelteKit app in the current environment
 * 
 * @return string Base URL for SvelteKit app
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
        return 'http://localhost:5173';
    } else {
        return 'https://app.asapdigest.com';
    }
}

/**
 * Add admin settings page for Better Auth configuration
 * This will be called from the main plugin file
 */
function asap_add_better_auth_settings_page() {
    add_options_page(
        'Better Auth Settings',
        'Better Auth',
        'manage_options',
        'better-auth-settings',
        'asap_render_better_auth_settings'
    );
}

/**
 * Render the Better Auth settings page
 */
function asap_render_better_auth_settings() {
    // Security check
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings if form is submitted
    if (isset($_POST['better_auth_settings_submit'])) {
        check_admin_referer('better_auth_settings_nonce');
        
        $better_auth_url = sanitize_text_field($_POST['better_auth_url']);
        update_option('better_auth_url', $better_auth_url);
        
        // Only update shared secret if provided and not empty
        if (!empty($_POST['better_auth_shared_secret'])) {
            $shared_secret = sanitize_text_field($_POST['better_auth_shared_secret']);
            update_option('better_auth_shared_secret', $shared_secret);
        }
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    // Display the settings form
    ?>
    <div class="wrap">
        <h1>Better Auth Integration Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('better_auth_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="better_auth_url">Better Auth URL</label>
                    </th>
                    <td>
                        <input type="url" name="better_auth_url" id="better_auth_url" 
                               value="<?php echo esc_attr(asap_get_better_auth_base_url()); ?>" 
                               class="regular-text">
                        <p class="description">The base URL for your SvelteKit application (e.g., http://localhost:5173 or https://app.asapdigest.com)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="better_auth_shared_secret">Shared Secret</label>
                    </th>
                    <td>
                        <input type="password" name="better_auth_shared_secret" id="better_auth_shared_secret" 
                               placeholder="Leave empty to keep current value" class="regular-text">
                        <p class="description">Secret key for validating requests between Better Auth and WordPress. 
                        For maximum security, define BETTER_AUTH_SHARED_SECRET in wp-config.php instead.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Current Configuration</th>
                    <td>
                        <p><strong>Shared Secret:</strong> 
                        <?php 
                        if (defined('BETTER_AUTH_SHARED_SECRET')) {
                            echo '<span style="color:green;">Defined in wp-config.php ✓</span>';
                        } else {
                            echo '<span style="color:orange;">Using stored value or fallback</span>';
                        }
                        ?>
                        </p>
                        <p><strong>Base URL:</strong> 
                        <?php 
                        if (defined('BETTER_AUTH_URL')) {
                            echo '<span style="color:green;">Defined in wp-config.php ✓</span>';
                        } else {
                            echo '<span style="color:orange;">Using stored value or fallback</span>';
                        }
                        ?>
                        </p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="better_auth_settings_submit" id="submit" class="button button-primary" 
                       value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}

/**
 * Validate Better Auth request signature
 * 
 * @param string $timestamp Request timestamp
 * @param string $signature Request signature
 * @return bool Whether the signature is valid
 */
function asap_validate_better_auth_request($timestamp, $signature) {
    // Ensure we have required data
    if (empty($timestamp) || empty($signature)) {
        return false;
    }
    
    // Get shared secret
    $secret = defined('BETTER_AUTH_SHARED_SECRET') ? BETTER_AUTH_SHARED_SECRET : get_option('better_auth_shared_secret');
    if (empty($secret)) {
        return false;
    }
    
    // Check timestamp is within 5 minutes
    $now = time();
    if (abs($now - intval($timestamp)) > 300) {
        return false;
    }
    
    // Calculate expected signature
    $expected = hash_hmac('sha256', $timestamp, $secret);
    
    // Compare signatures
    return hash_equals($expected, $signature);
}

/**
 * Create or update WordPress user from Better Auth user data
 * 
 * @param array $user_data Better Auth user data
 * @return int|WP_Error WordPress user ID or error
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
 * Create WordPress session for Better Auth user
 * 
 * @param int $wp_user_id WordPress user ID
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function asap_create_wp_session($wp_user_id) {
    $user = get_user_by('id', $wp_user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'Invalid WordPress user ID');
    }
    
    // Clear any existing sessions for user
    WP_Session_Tokens::get_instance($user->ID)->destroy_all();
    
    // Create new session
    $session = WP_Session_Tokens::get_instance($user->ID);
    $token = $session->create(time() + DAY_IN_SECONDS);
    
    // Set auth cookies
    wp_set_auth_cookie($user->ID, true, is_ssl());
    wp_set_current_user($user->ID);
    
    return true;
}

/**
 * Register Better Auth REST endpoints
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
    
    if (!asap_validate_better_auth_request($timestamp, $signature)) {
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
    
    if (!asap_validate_better_auth_request($timestamp, $signature)) {
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
    $result = asap_create_wp_session($wp_user_id);
    
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
 * Check for existing WordPress session and create Better Auth session if needed
 * 
 * @return array|WP_Error Response data or error
 */
function asap_check_wp_session() {
    // Check if user is logged into WordPress
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', 'No WordPress session found', ['status' => 401]);
    }
    
    // Get current WordPress user
    $wp_user = wp_get_current_user();
    
    // Get Better Auth user ID from user meta
    $better_auth_user_id = get_user_meta($wp_user->ID, 'better_auth_user_id', true);
    
    if (!$better_auth_user_id) {
        // Create Better Auth user if it doesn't exist
        $response = wp_remote_post(asap_get_better_auth_base_url() . '/api/auth/create-user', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Better-Auth-Timestamp' => time(),
                'X-Better-Auth-Signature' => hash_hmac('sha256', time(), BETTER_AUTH_SHARED_SECRET)
            ],
            'body' => json_encode([
                'email' => $wp_user->user_email,
                'name' => $wp_user->display_name,
                'username' => $wp_user->user_login,
                'metadata' => [
                    'wp_user_id' => $wp_user->ID
                ]
            ])
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!$body || !isset($body['id'])) {
            return new WP_Error('create_user_failed', 'Failed to create Better Auth user');
        }
        
        $better_auth_user_id = $body['id'];
        update_user_meta($wp_user->ID, 'better_auth_user_id', $better_auth_user_id);
    }
    
    // Create Better Auth session
    $response = wp_remote_post(asap_get_better_auth_base_url() . '/api/auth/create-session', [
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Better-Auth-Timestamp' => time(),
            'X-Better-Auth-Signature' => hash_hmac('sha256', time(), BETTER_AUTH_SHARED_SECRET)
        ],
        'body' => json_encode([
            'userId' => $better_auth_user_id
        ])
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!$body || !isset($body['sessionToken'])) {
        return new WP_Error('create_session_failed', 'Failed to create Better Auth session');
    }
    
    return [
        'sessionToken' => $body['sessionToken'],
        'userId' => $better_auth_user_id
    ];
}

/**
 * Register endpoint to check WordPress session
 */
function asap_register_wp_session_check() {
    register_rest_route('asap/v1/auth', '/check-wp-session', [
        'methods' => 'GET',
        'callback' => 'asap_check_wp_session',
        'permission_callback' => '__return_true'
    ]);
}

// Register REST endpoints
add_action('rest_api_init', 'asap_register_better_auth_endpoints');
add_action('rest_api_init', 'asap_register_wp_session_check'); 