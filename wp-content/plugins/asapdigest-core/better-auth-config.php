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
 * @description Set up Better Auth shared secret for WordPress integration
 * @return void
 * @example
 * // Called during plugin initialization
 * asap_setup_better_auth_shared_secret();
 * @created 03.29.25 | 03:45 PM PDT
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
        return 'http://localhost:5173';
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
 */
function asap_validate_better_auth_signature($timestamp, $signature) {
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
 * @return {bool|WP_Error} True on success, WP_Error on failure
 * @created 03.30.25 | 03:37 PM PDT
 */
function asap_create_wp_session_core($wp_user_id) {
    // Verify user exists
    $user = get_user_by('ID', $wp_user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'User does not exist');
    }

    // Set auth cookie
    wp_set_auth_cookie($wp_user_id, true);
    
    // Set current user
    wp_set_current_user($wp_user_id);
    
    // Update user meta with session info
    $session_token = wp_get_session_token();
    update_user_meta($wp_user_id, 'better_auth_session_token', $session_token);
    update_user_meta($wp_user_id, 'better_auth_last_login', current_time('mysql'));
    
    // Fire action for other integrations
    do_action('better_auth_session_created', $wp_user_id, $session_token);
    
    return true;
}

/**
 * @description Validate WordPress session and Better Auth token
 * @param {WP_REST_Request} request The request object
 * @return {bool|WP_Error} True if valid, WP_Error if invalid
 * @created 03.30.25 | 03:37 PM PDT
 */
function asap_check_wp_session($request) {
    // Get Better Auth token from header
    $better_auth_token = $request->get_header('X-Better-Auth-Token');
    if (empty($better_auth_token)) {
        return new WP_Error('missing_token', 'Better Auth token is required');
    }
    
    // Validate token timestamp and signature
    $parts = explode('.', $better_auth_token);
    if (count($parts) !== 2) {
        return new WP_Error('invalid_token', 'Invalid token format');
    }
    
    list($timestamp, $signature) = $parts;
    if (!asap_validate_better_auth_signature($timestamp, $signature)) {
        return new WP_Error('invalid_signature', 'Invalid token signature');
    }
    
    // Check WordPress session
    $wp_session = wp_get_session_token();
    if (empty($wp_session)) {
        return new WP_Error('no_session', 'No WordPress session found');
    }
    
    // Verify session matches Better Auth
    $user_id = get_current_user_id();
    $stored_token = get_user_meta($user_id, 'better_auth_session_token', true);
    
    if ($stored_token !== $wp_session) {
        return new WP_Error('session_mismatch', 'Session token mismatch');
    }
    
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
 * Register endpoint to check WordPress session
 */
function asap_register_wp_session_check() {
    register_rest_route('asap/v1/auth', '/check-wp-session', [
        'methods' => 'GET',
        'callback' => 'asap_check_wp_session',
        'permission_callback' => '__return_true'
    ]);
}

/**
 * @description Sync WordPress user to Better Auth
 * @param {int} wp_user_id WordPress user ID to sync
 * @return {array|WP_Error} Array with success status and Better Auth user ID on success, WP_Error on failure
 * @example
 * // Sync a WordPress user to Better Auth
 * $result = asap_sync_wp_user_to_better_auth(123);
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_sync_wp_user_to_better_auth($wp_user_id) {
    global $wpdb;

    // Get WordPress user data
    $wp_user = get_userdata($wp_user_id);
    if (!$wp_user) {
        return new WP_Error('invalid_user', 'WordPress user not found');
    }

    // Check if user is already synced
    $existing_ba_user = $wpdb->get_row($wpdb->prepare(
        "SELECT ba_user_id FROM {$wpdb->prefix}ba_wp_user_map WHERE wp_user_id = %d",
        $wp_user_id
    ));

    if ($existing_ba_user) {
        return array(
            'success' => true,
            'ba_user_id' => $existing_ba_user->ba_user_id,
            'message' => 'User already synced'
        );
    }

    try {
        // Connect to Better Auth database
        $ba_db = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                defined('DB_HOST') ? DB_HOST : 'localhost',
                '10018', // Local by Flywheel MySQL port
                DB_NAME
            ),
            DB_USER,
            DB_PASSWORD,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );

        // Start transaction
        $ba_db->beginTransaction();

        // Create Better Auth user
        $stmt = $ba_db->prepare("
            INSERT INTO ba_users (
                email,
                username,
                name,
                metadata,
                created_at,
                updated_at
            ) VALUES (
                :email,
                :username,
                :name,
                :metadata,
                NOW(),
                NOW()
            )
        ");

        // Get all user roles and capabilities
        $roles = $wp_user->roles;
        $all_caps = $wp_user->allcaps;

        $metadata = json_encode(array(
            'wp_user_id' => $wp_user_id,
            'wp_roles' => $roles,
            'wp_capabilities' => $all_caps,
            'wp_user_level' => $wp_user->user_level,
            'wp_user_status' => get_user_meta($wp_user_id, 'wp_user_status', true),
            'wp_display_name' => $wp_user->display_name,
            'wp_nickname' => $wp_user->nickname,
            'wp_first_name' => $wp_user->first_name,
            'wp_last_name' => $wp_user->last_name,
            'wp_description' => $wp_user->description,
            'wp_user_registered' => $wp_user->user_registered
        ));

        $stmt->execute(array(
            ':email' => $wp_user->user_email,
            ':username' => $wp_user->user_login,
            ':name' => $wp_user->display_name,
            ':metadata' => $metadata
        ));

        $ba_user_id = $ba_db->lastInsertId();

        // Create mapping record
        $wpdb->insert(
            $wpdb->prefix . 'ba_wp_user_map',
            array(
                'wp_user_id' => $wp_user_id,
                'ba_user_id' => $ba_user_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );

        // Commit transaction
        $ba_db->commit();

        return array(
            'success' => true,
            'ba_user_id' => $ba_user_id,
            'message' => 'User synced successfully'
        );

    } catch (Exception $e) {
        if (isset($ba_db) && $ba_db->inTransaction()) {
            $ba_db->rollBack();
        }
        return new WP_Error('sync_failed', $e->getMessage());
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
 * Hook to sync new WordPress users to Better Auth automatically
 * @created 03.29.25 | 03:34 PM PDT
 */
add_action('user_register', 'asap_sync_wp_user_to_better_auth');

/**
 * Add REST API endpoint to trigger manual sync
 * @created 03.29.25 | 03:34 PM PDT
 */
add_action('rest_api_init', function() {
    register_rest_route('asap/v1', '/auth/sync-wp-users', array(
        'methods' => 'POST',
        'callback' => function(WP_REST_Request $request) {
            // Verify admin privileges
            if (!current_user_can('administrator')) {
                return new WP_Error(
                    'rest_forbidden',
                    'Only administrators can sync users',
                    array('status' => 403)
                );
            }

            // Get specific user ID from request or sync all if not provided
            $wp_user_id = $request->get_param('wp_user_id');
            
            if ($wp_user_id) {
                return asap_sync_wp_user_to_better_auth($wp_user_id);
            } else {
                return asap_sync_all_wp_users_to_better_auth();
            }
        },
        'permission_callback' => function() {
            return current_user_can('administrator');
        }
    ));
});

/**
 * @description Create WordPress to Better Auth user mapping table
 * @return void
 * @example
 * // Create the mapping table
 * asap_create_ba_wp_user_map_table();
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_create_ba_wp_user_map_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'ba_wp_user_map';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        wp_user_id bigint(20) unsigned NOT NULL,
        ba_user_id varchar(255) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY wp_user_id (wp_user_id),
        UNIQUE KEY ba_user_id (ba_user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create mapping table on plugin activation
register_activation_hook(__FILE__, 'asap_create_ba_wp_user_map_table');

// Register REST endpoints
add_action('rest_api_init', 'asap_register_better_auth_endpoints');
add_action('rest_api_init', 'asap_register_wp_session_check');

/**
 * @description Add Better Auth settings submenu under Central Command
 * @return void
 * @example
 * // Called during admin_menu action
 * asap_add_better_auth_settings_submenu();
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_add_better_auth_settings_submenu() {
    // Only add submenu if parent menu exists
    global $submenu;
    if (isset($submenu['asap-central-command'])) {
        add_submenu_page(
            'asap-central-command',
            'Better Auth Settings',
            'Auth Settings',
            'manage_options',
            'asap-auth-settings',
            'asap_render_better_auth_settings'
        );
    }
}

// Add the settings page under Central Command with lower priority to ensure parent menu exists
add_action('admin_menu', 'asap_add_better_auth_settings_submenu', 30);

/**
 * Render the Central Command dashboard
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_render_central_command_dashboard() {
    global $wpdb;

    // Create the mapping table if it doesn't exist
    asap_create_ba_wp_user_map_table();
    
    ?>
    <div class="wrap">
        <h1>⚡️ ASAP Digest Central Command</h1>
        
        <div class="card">
            <h2>Quick Stats</h2>
            <p>Welcome to ASAP Digest Central Command. This dashboard provides an overview of your system.</p>
            
            <?php
            // Get some basic stats
            $total_users = count_users();
            
            // Check if table exists before counting
            $table_name = $wpdb->prefix . 'ba_wp_user_map';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            $synced_users = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_name") : 0;
            
            // Check if digest post type exists before counting
            $digest_counts = post_type_exists('digest') ? wp_count_posts('digest') : null;
            $total_digests = $digest_counts ? ($digest_counts->publish ?? 0) : 0;
            ?>
            
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>Total Users</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $total_users['total_users']; ?></p>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>Synced Users</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $synced_users; ?></p>
                    <?php if (!$table_exists): ?>
                        <p style="color: #e67e22;"><span class="dashicons dashicons-warning"></span> Sync table not found. Please deactivate and reactivate the plugin.</p>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>Total Digests</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $total_digests; ?></p>
                    <?php if (!post_type_exists('digest')): ?>
                        <p style="color: #e67e22;"><span class="dashicons dashicons-warning"></span> Digest post type not registered.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Quick Actions</h2>
            <p>Access common tasks and management tools:</p>
            
            <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=asap-auth-sync'); ?>" class="button button-primary">
                    Manage Auth Sync
                </a>
                <a href="<?php echo admin_url('admin.php?page=asap-digest-management'); ?>" class="button button-primary">
                    Manage Digests
                </a>
                <a href="<?php echo admin_url('admin.php?page=asap-user-stats'); ?>" class="button button-primary">
                    View User Stats
                </a>
                <a href="<?php echo admin_url('admin.php?page=asap-settings'); ?>" class="button button-primary">
                    Configure Settings
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the digest management page
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_render_digest_management() {
    ?>
    <div class="wrap">
        <h1>Digest Management</h1>
        <p>Manage your ASAP Digests here. This feature is coming soon.</p>
    </div>
    <?php
}

/**
 * Render the user stats page
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_render_user_stats() {
    ?>
    <div class="wrap">
        <h1>User Statistics</h1>
        <p>View detailed user statistics here. This feature is coming soon.</p>
    </div>
    <?php
}

/**
 * Render the settings page
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_render_settings() {
    ?>
    <div class="wrap">
        <h1>ASAP Settings</h1>
        <p>Configure ASAP Digest settings here. This feature is coming soon.</p>
    </div>
    <?php
}

/**
 * Render Better Auth Sync admin page
 * @created 03.29.25 | 03:34 PM PDT
 */
function asap_render_better_auth_sync_page() {
    // Handle form submission
    if (isset($_POST['sync_action']) && check_admin_referer('better_auth_sync')) {
        if ($_POST['sync_action'] === 'sync_all') {
            $results = asap_sync_all_wp_users_to_better_auth();
        } else if ($_POST['sync_action'] === 'sync_selected' && !empty($_POST['user_ids'])) {
            $results = array(
                'success' => true,
                'synced' => array(),
                'failed' => array(),
                'skipped' => array()
            );
            
            foreach ($_POST['user_ids'] as $user_id) {
                $sync_result = asap_sync_wp_user_to_better_auth(intval($user_id));
                if (is_wp_error($sync_result)) {
                    $results['failed'][] = array(
                        'wp_user_id' => $user_id,
                        'error' => $sync_result->get_error_message()
                    );
                } else if ($sync_result['message'] === 'User already synced') {
                    $results['skipped'][] = array(
                        'wp_user_id' => $user_id,
                        'ba_user_id' => $sync_result['ba_user_id']
                    );
                } else {
                    $results['synced'][] = array(
                        'wp_user_id' => $user_id,
                        'ba_user_id' => $sync_result['ba_user_id']
                    );
                }
            }
        }
    }

    // Get all WordPress users
    $users = get_users(array(
        'orderby' => 'registered',
        'order' => 'DESC'
    ));

    // Get sync status for each user
    global $wpdb;
    $sync_statuses = $wpdb->get_results("
        SELECT wp_user_id, ba_user_id, created_at
        FROM {$wpdb->prefix}ba_wp_user_map
    ", OBJECT_K);

    ?>
    <div class="wrap">
        <h1>Better Auth User Sync</h1>
        
        <?php if (isset($results)): ?>
            <div class="notice notice-info">
                <h3>Sync Results:</h3>
                <?php if (!empty($results['synced'])): ?>
                    <p>Successfully synced <?php echo count($results['synced']); ?> users.</p>
                <?php endif; ?>
                <?php if (!empty($results['skipped'])): ?>
                    <p>Skipped <?php echo count($results['skipped']); ?> already synced users.</p>
                <?php endif; ?>
                <?php if (!empty($results['failed'])): ?>
                    <p>Failed to sync <?php echo count($results['failed']); ?> users:</p>
                    <ul>
                        <?php foreach ($results['failed'] as $failure): ?>
                            <li>User ID <?php echo esc_html($failure['wp_user_id']); ?>: <?php echo esc_html($failure['error']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('better_auth_sync'); ?>
            
            <p>
                <button type="submit" name="sync_action" value="sync_all" class="button button-primary">
                    Sync All Users
                </button>
            </p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column">
                            <input type="checkbox" id="users-select-all">
                        </th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Better Auth Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="user_ids[]" value="<?php echo esc_attr($user->ID); ?>">
                            </td>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                            <td>
                                <?php if (isset($sync_statuses[$user->ID])): ?>
                                    <span class="dashicons dashicons-yes" style="color: green;"></span>
                                    Synced (BA ID: <?php echo esc_html($sync_statuses[$user->ID]->ba_user_id); ?>)
                                    <br>
                                    <small>Synced on: <?php echo esc_html($sync_statuses[$user->ID]->created_at); ?></small>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" style="color: red;"></span>
                                    Not synced
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="submit" name="sync_action" value="sync_selected" class="button button-secondary">
                    Sync Selected Users
                </button>
            </p>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('#users-select-all').on('change', function() {
                $('input[name="user_ids[]"]').prop('checked', $(this).prop('checked'));
            });
        });
        </script>
    </div>
    <?php
}

/**
 * @description Render the Better Auth settings page with Central Command styling
 * @return void
 * @example
 * // Called when viewing the Better Auth settings page
 * asap_render_better_auth_settings();
 * @created 03.29.25 | 03:45 PM PDT
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
        
        echo '<div class="notice notice-success is-dismissible"><p>✅ Better Auth settings updated successfully!</p></div>';
    }
    
    // Get current status indicators
    $secret_status = defined('BETTER_AUTH_SHARED_SECRET') 
        ? '<span class="asap-status-good">Defined in wp-config.php ✓</span>'
        : '<span class="asap-status-warning">Using stored value</span>';
    
    $url_status = defined('BETTER_AUTH_URL')
        ? '<span class="asap-status-good">Defined in wp-config.php ✓</span>'
        : '<span class="asap-status-warning">Using stored value</span>';
    
    // Display the settings form with Central Command styling
    ?>
    <div class="wrap asap-central-command">
        <h1><i class="dashicons dashicons-shield"></i> Better Auth Integration</h1>
        
        <div class="asap-card">
            <h2>Configuration Status</h2>
            <div class="asap-status-grid">
                <div class="asap-status-item">
                    <strong>Shared Secret:</strong> <?php echo $secret_status; ?>
                </div>
                <div class="asap-status-item">
                    <strong>Base URL:</strong> <?php echo $url_status; ?>
                </div>
            </div>
        </div>

        <div class="asap-card">
            <h2>Settings</h2>
            <form method="post" action="">
                <?php wp_nonce_field('better_auth_settings_nonce'); ?>
                
                <div class="asap-form-row">
                    <label for="better_auth_url">Better Auth URL</label>
                    <input type="url" name="better_auth_url" id="better_auth_url" 
                           value="<?php echo esc_attr(asap_get_better_auth_base_url()); ?>" 
                           class="regular-text">
                    <p class="description">The base URL for your SvelteKit application (e.g., http://localhost:5173 or https://app.asapdigest.com)</p>
                </div>

                <div class="asap-form-row">
                    <label for="better_auth_shared_secret">Shared Secret</label>
                    <input type="password" name="better_auth_shared_secret" id="better_auth_shared_secret" 
                           placeholder="Leave empty to keep current value" class="regular-text">
                    <p class="description">Secret key for validating requests between Better Auth and WordPress. 
                    For maximum security, define BETTER_AUTH_SHARED_SECRET in wp-config.php instead.</p>
                </div>

                <div class="asap-form-actions">
                    <input type="submit" name="better_auth_settings_submit" class="button button-primary" 
                           value="Save Changes">
                </div>
            </form>
        </div>

        <div class="asap-card">
            <h2>Documentation</h2>
            <p>Better Auth is the authentication system used by ASAP Digest to manage user sessions across WordPress and SvelteKit.</p>
            <ul class="asap-doc-list">
                <li><strong>wp-config.php Constants:</strong> For enhanced security, define BETTER_AUTH_SHARED_SECRET and BETTER_AUTH_URL in wp-config.php</li>
                <li><strong>Environment Variables:</strong> Make sure these match your SvelteKit .env configuration</li>
                <li><strong>Session Handling:</strong> Better Auth manages sessions across both platforms automatically</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * @description Handle token exchange between Better Auth and WordPress
 * @param {WP_REST_Request} request The request object
 * @return {WP_REST_Response|WP_Error} Response with exchanged token or error
 * @created 03.30.25 | 03:37 PM PDT
 */
function asap_handle_token_exchange($request) {
    $better_auth_token = $request->get_header('X-Better-Auth-Token');
    if (empty($better_auth_token)) {
        return new WP_Error('missing_token', 'Better Auth token is required', ['status' => 400]);
    }

    // Validate Better Auth token
    $validation = asap_validate_better_auth_signature(
        $request->get_header('X-Better-Auth-Timestamp'),
        $better_auth_token
    );

    if (is_wp_error($validation)) {
        return $validation;
    }

    // Get user from token
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('no_user', 'No user found for token', ['status' => 401]);
    }

    // Create WordPress session
    $session_result = asap_create_wp_session_core($user_id);
    if (is_wp_error($session_result)) {
        return $session_result;
    }

    // Get WordPress session token
    $wp_session_token = wp_get_session_token();
    
    // Return both tokens
    return rest_ensure_response([
        'wp_token' => $wp_session_token,
        'better_auth_token' => $better_auth_token,
        'user_id' => $user_id
    ]);
}

/**
 * @description Register token exchange endpoint
 * @return void
 * @created 03.30.25 | 03:37 PM PDT
 */
function asap_register_token_exchange() {
    register_rest_route('asap/v1', '/auth/exchange-token', [
        'methods' => 'POST',
        'callback' => 'asap_handle_token_exchange',
        'permission_callback' => function() {
            return true; // We'll validate in the handler
        }
    ]);
}
add_action('rest_api_init', 'asap_register_token_exchange'); 