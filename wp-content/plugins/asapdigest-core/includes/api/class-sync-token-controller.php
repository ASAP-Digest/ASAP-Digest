<?php
/**
 * Controller for handling Sync Token Validation via REST API.
 * 
 * @package ASAPDigest_Core
 * @created 04.16.25 | 12:25 PM PDT
 * @file-marker Sync_Token_Controller
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Sync_Token_Controller
 * Handles the /asap/v1/validate-sync-token endpoint.
 */
class Sync_Token_Controller extends WP_REST_Controller {

    /**
     * Namespace for the REST route.
     * @var string
     */
    protected $namespace = 'asap/v1';

    /**
     * Route base for the controller.
     * @var string
     */
    protected $rest_base = 'validate-sync-token';

    /**
     * Constructor - Register routes and hooks.
     */
    public function __construct() {
        // Existing route registration logic should remain here
        // if called directly (unlikely for REST Controllers, 
        // but good practice to assume it might be).
        // We will register the routes via the standard 'rest_api_init' hook.
        add_action('rest_api_init', [$this, 'register_routes']);

        // Add the new wp_login hook here
        add_action( 'wp_login', [ $this, 'handle_wp_login' ], 10, 2 );
        // Add the login_redirect hook
        add_filter( 'login_redirect', [ $this, 'modify_login_redirect' ], 10, 3 );
    }

    /**
     * Register the routes for the controller.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE, // Corresponds to POST
                    'callback'            => [$this, 'validate_token'],
                    'permission_callback' => '__return_true', // Allow access, validation is via token
                    'args'                => [
                        'token' => [
                            'required'          => true,
                            'validate_callback' => function($param, $request, $key) {
                                return is_string($param) && ! empty($param);
                            },
                            'sanitize_callback' => 'sanitize_text_field',
                            'description'       => __('The sync token to validate.', 'adc'),
                        ],
                    ],
                ],
            ]
        );

        // Hook into WordPress logout to delete the sync token.
        // THIS LINE IS NOW DUPLICATED - We added the hook in __construct.
        // It should be removed from here if __construct is always called.
        // For safety with standard WP REST practices, let's keep registration
        // tied to 'rest_api_init' and add the wp_login hook there too, 
        // removing it from the constructor.
        // add_action('wp_logout', [$this, 'handle_wp_logout'], 10, 1); 
        // ^^^ Let's remove this line from here if constructor isn't the main entry point.
        // Re-evaluating: The standard pattern is to add actions in the constructor
        // or an init method called by the main plugin file. Let's stick to the constructor.
        // The add_action for wp_logout can stay in register_routes IF register_routes is 
        // guaranteed to run. Let's assume it is via 'rest_api_init'.
        // The safest place for wp_login is likely the constructor or a dedicated init method.
        // Let's stick to the constructor approach initially added.
    }

    /**
     * Validates a sync token and deletes it if valid.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function validate_token(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ba_sync_tokens';
        $token = $request->get_param('token');

        // Find the token
        $token_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, wp_user_id FROM $table_name WHERE token = %s AND expires_at > NOW() LIMIT 1",
                $token
            ),
            ARRAY_A // Return associative array
        );

        if (empty($token_data)) {
            // Token not found or expired
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired sync token.', 'adc'),
                ['status' => 401]
            );
        }

        // Token is valid, retrieve user ID
        $wp_user_id = (int) $token_data['wp_user_id'];
        $token_db_id = (int) $token_data['id'];

        /* --- CRITICAL: Deleting token is REMOVED for persistent token strategy (Ticket #XYZ) --- 
        // Delete the token immediately after validation
        $deleted = $wpdb->delete(
            $table_name,
            ['id' => $token_db_id], // Delete by its primary key
            ['%d']
        );

        if ($deleted === false) {
            // Log error, but still return success as validation passed before delete attempt
            error_log("ASAP Digest: Failed to delete sync token ID $token_db_id after validation. DB Error: " . $wpdb->last_error);
        } elseif ($deleted > 0) {
            error_log("ASAP Digest: Successfully validated and DELETED sync token ID $token_db_id."); // Adjusted log for clarity if re-enabled
        }
        */
         error_log("ASAP Digest: Successfully validated sync token for WP User ID $wp_user_id (Token NOT deleted)."); // New log line
        // --- End Token Deletion Modification ---

        // Return success response with the WP User ID
        $response_data = [
            'valid' => true,
            'wpUserId' => $wp_user_id,
        ];

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Handles the wp_logout action to delete the sync token for the user logging out.
     *
     * @param int $user_id ID of the user logging out.
     */
    public function handle_wp_logout( $user_id ) {
        if ( empty( $user_id ) ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ba_sync_tokens';

        error_log("ASAP Digest: User {$user_id} logging out. Attempting to delete sync token.");

        $deleted = $wpdb->delete(
            $table_name,
            [ 'wp_user_id' => $user_id ], // Delete by user ID
            [ '%d' ]                     // Format for the WHERE clause
        );

        if ( false === $deleted ) {
            error_log("ASAP Digest: Failed to delete sync token for user {$user_id}. DB Error: " . $wpdb->last_error);
        } elseif ( $deleted > 0 ) {
            error_log("ASAP Digest: Successfully deleted sync token for user {$user_id}.");
        } else {
            error_log("ASAP Digest: No sync token found to delete for user {$user_id}.");
        }
    }

    /**
     * Handles the wp_login action to generate and store a sync token.
     *
     * @param string $user_login User's login name.
     * @param WP_User $user WP_User object of the logged-in user.
     * @created 04.29.25 | 11:30 PM PDT
     */
    public function handle_wp_login( $user_login, $user ) {
        if ( ! $user instanceof \WP_User || empty( $user->ID ) ) {
            error_log("ASAP Digest: Invalid user object passed to handle_wp_login for user: " . $user_login);
            return; // Exit if user object is invalid
        }

        global $wpdb;
        // Assume table name is correct based on High Level plan
        $table_name = $wpdb->prefix . 'ba_sync_tokens'; 
        $user_id = $user->ID;

        // Generate a secure token (64 hex chars)
        $token = bin2hex(random_bytes(32));

        // Set expiry (e.g., 5 minutes from now)
        $expires_at = date('Y-m-d H:i:s', time() + 300); 

        error_log("ASAP Digest: User {$user_id} ({$user_login}) logged in. Generating sync token.");

        // Use replace to insert or update the token for this user
        $replaced = $wpdb->replace(
            $table_name,
            [
                'wp_user_id' => $user_id, 
                'token'      => $token,
                'created_at' => current_time( 'mysql', 1 ), // Use WP function for GMT time
                'expires_at' => $expires_at,
            ],
            [
                '%d', // wp_user_id
                '%s', // token
                '%s', // created_at
                '%s', // expires_at
            ]
        );

        if ( false === $replaced ) {
            error_log("ASAP Digest: Failed to replace/insert sync token for user {$user_id}. DB Error: " . $wpdb->last_error);
        } else {
            error_log("ASAP Digest: Successfully generated/updated sync token for user {$user_id}. Effected rows: " . $replaced);
        }
    }

    /**
     * Modifies the login redirect URL to include the sync token.
     *
     * @param string  $redirect_to           The redirect destination URL.
     * @param string  $requested_redirect_to The requested redirect destination URL (unused).
     * @param WP_User|WP_Error $user WP_User object if login was successful, WP_Error object otherwise.
     * @return string The modified redirect destination URL.
     * @created 04.29.25 | 11:41 PM PDT
     */
    public function modify_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
        // Only modify redirect if login was successful and user object is valid
        if ( ! is_wp_error( $user ) && $user instanceof \WP_User && ! empty( $user->ID ) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ba_sync_tokens';
            $user_id = $user->ID;

            // Retrieve the most recent valid token for this user
            // (The one generated by handle_wp_login should be the most recent)
            $token_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT token FROM {$table_name} WHERE wp_user_id = %d AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1",
                    $user_id
                ),
                ARRAY_A
            );

            if ( ! empty( $token_row['token'] ) ) {
                // Define the SvelteKit app URL (TODO: Make this configurable)
                $sveltekit_app_url = 'https://localhost:5173'; // Or getenv('SVELTEKIT_APP_URL')
                
                // Append the token as a query parameter
                $redirect_to_with_token = add_query_arg(
                    ['sync_token' => $token_row['token']],
                    $sveltekit_app_url 
                );
                
                error_log("ASAP Digest: Appending sync token to redirect for user {$user_id}.");
                return $redirect_to_with_token;
            } else {
                error_log("ASAP Digest: Could not find valid sync token for user {$user_id} during login redirect.");
            }
        }

        // Return the original redirect URL if login failed or no token found
        return $redirect_to;
    }

} // End class 