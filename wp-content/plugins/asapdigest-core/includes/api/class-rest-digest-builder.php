<?php
namespace ASAPDigest\Core\API;

/**
 * REST API Endpoint for Digest Building and Module Placement
 *
 * @package ASAPDigest_Core
 * @subpackage API
 */

// Include the parent REST_Base class
require_once plugin_dir_path( __FILE__ ) . 'class-rest-base.php';

use ASAPDigest\Core\ASAP_Digest_Database;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class REST_Digest_Builder extends REST_Base {

    /**
     * @var ASAP_Digest_Database $database The database handler instance.
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'asap/v1'; // Use the existing ASAP namespace
        $this->rest_base = 'digest-builder'; // New REST base for this controller

        // Assuming ASAP_Digest_Database is a singleton or can be instantiated like this
        // You may need to adjust this based on the actual implementation in class-core.php
        $this->database = new ASAP_Digest_Database();

        // Note: register_routes() is now called directly from the core class
        // No need to hook into rest_api_init here since it's handled by the core
    }

    /**
     * Helper method to get WordPress user ID from Better Auth UUID
     * @param string $ba_user_id Better Auth user ID (UUID)
     * @return int|false WordPress user ID or false if not found
     */
    private function get_wp_user_id_from_ba_id($ba_user_id) {
        global $wpdb;
        
        if (empty($ba_user_id)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'ba_wp_user_map';
        $wp_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT wp_user_id FROM {$table_name} WHERE ba_user_id = %s LIMIT 1",
            $ba_user_id
        ));
        
        return $wp_user_id ? (int) $wp_user_id : false;
    }

    /**
     * Helper method to convert Better Auth UUID to WordPress user ID
     * 
     * @param string $ba_user_id Better Auth user ID (UUID)
     * @return int|false WordPress user ID or false if not found
     */
    private function get_wp_user_id_from_ba_uuid($ba_user_id) {
        global $wpdb;
        
        // Better Auth tables don't use WordPress prefix
        $table_name = 'ba_wp_user_map';
        $wp_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT wp_user_id FROM {$table_name} WHERE ba_user_id = %s",
            $ba_user_id
        ));
        
        return $wp_user_id ? (int) $wp_user_id : false;
    }

    /**
     * Authenticate user using Better Auth session token
     * 
     * @param WP_REST_Request $request Full details about the request
     * @return int|false WordPress user ID if authenticated, false otherwise
     */
    private function authenticate_with_better_auth($request) {
        // Check for Authorization header with Bearer token
        $auth_header = $request->get_header('Authorization');
        if (!$auth_header || !preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
            return false;
        }
        
        $session_token = $matches[1];
        
        // Query Better Auth sessions table to validate token
        global $wpdb;
        // Better Auth tables don't use WordPress prefix
        $sessions_table = 'ba_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, expires_at FROM {$sessions_table} WHERE token = %s AND expires_at > NOW()",
            $session_token
        ));
        
        if (!$session) {
            return false;
        }
        
        // Convert Better Auth user ID to WordPress user ID
        $wp_user_id = $this->get_wp_user_id_from_ba_uuid($session->user_id);
        
        return $wp_user_id;
    }

    /**
     * Enhanced authentication check that supports both WordPress sessions and Better Auth tokens
     * 
     * @param WP_REST_Request $request Full details about the request
     * @return int|false WordPress user ID if authenticated, false otherwise
     */
    private function get_authenticated_user_id($request) {
        // First try WordPress REST API authentication (works with nonce and cookies)
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID > 0) {
            error_log("ASAP_AUTH_DEBUG: Found WordPress user via wp_get_current_user: " . $current_user->ID);
            return $current_user->ID;
        }
        
        // If WordPress auth fails, try Better Auth token authentication
        $ba_user_id = $this->authenticate_with_better_auth($request);
        if ($ba_user_id) {
            error_log("ASAP_AUTH_DEBUG: Found user via Better Auth: " . $ba_user_id);
            return $ba_user_id;
        }
        
        error_log("ASAP_AUTH_DEBUG: No authentication found");
        return false;
    }

    /**
     * Register the REST API routes for the digest builder.
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/create-draft', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'create_draft_digest' ],
            'permission_callback' => [ $this, 'get_items_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                'layout_template_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_string( $param ) && ! empty( $param ); },
                    'required' => true,
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/add-module', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'add_module_to_digest' ],
            'permission_callback' => [ $this, 'update_item_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                'digest_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
                'module_cpt_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
                // Optional initial placement args
                'grid_x' => [ 'validate_callback' => 'is_numeric' ],
                'grid_y' => [ 'validate_callback' => 'is_numeric' ],
                'grid_width' => [ 'validate_callback' => 'is_numeric' ],
                'grid_height' => [ 'validate_callback' => 'is_numeric' ],
                'order_in_grid' => [ 'validate_callback' => 'is_numeric' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<digest_id>\d+)/update-placement/(?P<placement_id>\d+)', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update_module_placement' ],
            'permission_callback' => [ $this, 'update_item_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                'digest_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
                 'placement_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
                // Placement args to update
                'grid_x' => [ 'validate_callback' => 'is_numeric' ],
                'grid_y' => [ 'validate_callback' => 'is_numeric' ],
                'grid_width' => [ 'validate_callback' => 'is_numeric' ],
                'grid_height' => [ 'validate_callback' => 'is_numeric' ],
                'order_in_grid' => [ 'validate_callback' => 'is_numeric' ],
            ],
        ] );

         register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<digest_id>\d+)/remove-module/(?P<placement_id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'remove_module_from_digest' ],
            'permission_callback' => [ $this, 'delete_item_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                 'digest_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
                 'placement_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<digest_id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_digest' ],
            'permission_callback' => [ $this, 'get_item_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                 'digest_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
            ],
        ] );

         register_rest_route( $this->namespace, '/' . $this->rest_base . '/user/(?P<user_id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_users_digests' ],
            'permission_callback' => [ $this, 'get_items_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                 'user_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
                // Optional query args for filtering (e.g., status=draft/published)
                'status' => [
                    'validate_callback' => function( $param, $request, $key ) { return in_array( $param, ['draft', 'published', 'archived'] ); },
                ],
            ],
        ] );

        // Add route for current user's digests (no user_id required)
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/user-digests', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_current_users_digests' ],
            'permission_callback' => [ $this, 'get_items_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                // Optional query args for filtering (e.g., status=draft/published)
                'status' => [
                    'validate_callback' => function( $param, $request, $key ) { return in_array( $param, ['draft', 'published', 'archived'] ); },
                ],
            ],
        ] );

         register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<digest_id>\d+)/publish', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'publish_digest' ],
            'permission_callback' => [ $this, 'update_item_permissions_check' ], // Implement appropriate permission checks
            'args'                => [
                 'digest_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<digest_id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'delete_digest' ],
            'permission_callback' => [ $this, 'delete_item_permissions_check' ], // Implement appropriate permission checks
             'args'                => [
                 'digest_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/layouts', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_layout_templates' ],
            'permission_callback' => '__return_true', // Layout templates are publicly accessible
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/modules', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_available_modules' ],
            'permission_callback' => [ $this, 'get_items_permissions_check' ], // Implement appropriate permission checks
        ] );

        // TODO: Add API endpoint for managing predefined layout templates if needed (CRUD for layouts)
    }

    /**
     * Handles fetching the list of available layout templates.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_layout_templates( $request ) {
        error_log("ASAP_API_DEBUG: get_layout_templates called");
        
        // Fetch layout templates from the asap_digest_template CPT
        $template_posts = get_posts([
            'post_type' => 'asap_digest_template',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);
        
        error_log("ASAP_API_DEBUG: Found " . count($template_posts) . " template posts");

        $layout_templates = [];

        foreach ($template_posts as $template_post) {
            // Get template metadata
            $gridstack_config = get_post_meta($template_post->ID, 'gridstack_config', true);
            $predefined_slots = get_post_meta($template_post->ID, 'predefined_slots', true);
            $template_type = get_post_meta($template_post->ID, 'template_type', true);
            $is_default = get_post_meta($template_post->ID, 'is_default', true);

            // Parse JSON metadata
            $gridstack_config = $gridstack_config ? json_decode($gridstack_config, true) : [];
            $predefined_slots = $predefined_slots ? json_decode($predefined_slots, true) : [];

            // Format for frontend consumption
            $layout_templates[] = [
                'id' => $template_post->ID,
                'name' => $template_post->post_title,
                'description' => $template_post->post_content ?: 'Custom layout template',
                'preview_image' => get_the_post_thumbnail_url($template_post->ID, 'medium') ?: null,
                'max_modules' => count($predefined_slots),
                'gridstack_config' => array_merge([
                    'column' => 12,
                    'cellHeight' => 80,
                    'verticalMargin' => 10,
                    'horizontalMargin' => 10,
                    'animate' => true,
                    'float' => false,
                    'disableDrag' => false,
                    'disableResize' => false,
                    'handle' => '.drag-handle'
                ], $gridstack_config),
                'predefined_slots' => $predefined_slots,
                'template_type' => $template_type ?: 'gridstack',
                'is_default' => (bool) $is_default,
                'created_at' => $template_post->post_date,
                'modified_at' => $template_post->post_modified
            ];
        }

        // If no templates found, return empty array with message
        if (empty($layout_templates)) {
            error_log("ASAP_API_DEBUG: No layout templates found, returning empty array");
            return new WP_REST_Response([
                'success' => true,
                'message' => 'No layout templates found. Default templates will be created.',
                'data' => []
            ], 200);
        }

        error_log("ASAP_API_DEBUG: Returning " . count($layout_templates) . " layout templates");
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Layout templates fetched successfully.',
            'data' => $layout_templates,
            'count' => count($layout_templates)
        ], 200);
    }

    /**
     * Handles fetching the list of available module CPTs.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_available_modules( $request ) {
        // Fetch real module CPTs
        $modules = get_posts( [
            'post_type'      => 'asap_digest_module',
            'post_status'    => 'publish', // Only fetch published modules
            'posts_per_page' => -1,          // Get all matching modules
            'fields'         => 'ids',       // Fetch only IDs initially
        ] );

        if ( empty( $modules ) ) {
            return new WP_REST_Response( [], 200 ); // Return empty array if no modules found
        }

        $formatted_modules = [];
        foreach ( $modules as $module_id ) {
            // Fetch module title (name)
            $module_post = get_post( $module_id );
            if ( $module_post ) {
                 $formatted_modules[] = [
                    'id'          => $module_id,
                    'name'        => $module_post->post_title,
                    // TODO: Potentially add description or other relevant data
                ];
            }
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Available modules fetched successfully.', 'asap-digest-core' ),
            'data' => $formatted_modules,
        ], 200 );
    }

    /**
     * Handles creating a new draft digest.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_draft_digest( $request ) {
        // Get authenticated user ID
        $user_id = $this->get_authenticated_user_id($request);
        if (!$user_id) {
            return new WP_Error('asap_digest_auth_required', 'Authentication required.', ['status' => 401]);
        }

        // Extract data from request
        $layout_template_id = sanitize_text_field( $request['layout_template_id'] );

        // Validate input data
        if ( empty( $layout_template_id ) ) {
            return new WP_Error(
                'asap_digest_error', // Error code
                __( 'Layout Template ID is required.', 'asap-digest-core' ), // Error message
                [ 'status' => 400 ] // HTTP status code
            );
        }

        // Use the database handler to insert a new draft digest
        // Assuming ASAP_Digest_Database has a method like insert_digest
        $new_digest_id = $this->database->insert_digest(
            $user_id,
            $layout_template_id,
            'draft' // Set status to draft
        );

        if ( is_wp_error( $new_digest_id ) ) {
            // Handle database insertion error
             return new WP_Error(
                'asap_digest_db_error', // Error code
                __( 'Failed to create digest draft in database.', 'asap-digest-core' ), // Error message
                [ 'status' => 500 ] // HTTP status code
            );
        }

        if ( ! $new_digest_id ) {
             // Handle case where insertion failed but didn't return WP_Error (e.g., insert returned false/0)
             return new WP_Error(
                'asap_digest_insert_failed', // Error code
                __( 'Digest draft creation failed.', 'asap-digest-core' ), // Error message
                [ 'status' => 500 ] // HTTP status code
            );
        }

        // Return success response with the new digest ID
        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Digest draft created successfully.', 'asap-digest-core' ),
            'data' => [ 'digest_id' => $new_digest_id ]
        ], 200 );
    }

    /**
     * Handles adding a module placement to a digest.
     *
     * This endpoint allows adding a content module to a specific draft digest.
     * It records the placement details in the database.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $digest_id The ID of the target draft digest. (Required)
     *     @type int $module_cpt_id The ID of the module CPT to add. (Required)
     *     @type int $grid_x The x-coordinate on the grid. (Optional, default 0)
     *     @type int $grid_y The y-coordinate on the grid. (Optional, default 0)
     *     @type int $grid_width The width on the grid. (Optional, default 1)
     *     @type int $grid_height The height on the grid. (Optional, default 1)
     *     @type int $order_in_grid The order within the grid cell. (Optional, default 0)
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (containing new placement ID), WP_Error on failure.
     *
     * @created 05.23.25 | 06:23 PM PDT
     */
    public function add_module_to_digest( $request ) {
        // Extract data from request
        $digest_id = (int) $request['digest_id'];
        $module_cpt_id = (int) $request['module_cpt_id'];

        // Extract optional placement data
        $grid_x = isset( $request['grid_x'] ) ? (int) $request['grid_x'] : 0; // Default to 0
        $grid_y = isset( $request['grid_y'] ) ? (int) $request['grid_y'] : 0; // Default to 0
        $grid_width = isset( $request['grid_width'] ) && is_numeric( $request['grid_width'] ) && $request['grid_width'] > 0 ? (int) $request['grid_width'] : 1; // Default to 1, ensure positive
        $grid_height = isset( $request['grid_height'] ) && is_numeric( $request['grid_height'] ) && $request['grid_height'] > 0 ? (int) $request['grid_height'] : 1; // Default to 1, ensure positive
        $order_in_grid = isset( $request['order_in_grid'] ) ? (int) $request['order_in_grid'] : 0; // Default to 0

        // Validate required input data
        if ( empty( $digest_id ) || empty( $module_cpt_id ) ) {
            return new WP_Error(
                'asap_digest_error', // Error code
                __( 'Digest ID and Module CPT ID are required.', 'asap-digest-core' ), // Error message
                [ 'status' => 400 ] // HTTP status code
            );
        }

        // Prepare data for insertion
        $data = array(
            'digest_id'     => $digest_id,
            'module_cpt_id' => $module_cpt_id,
            'grid_x'        => $grid_x,
            'grid_y'        => $grid_y,
            'grid_width'    => $grid_width,
            'grid_height'   => $grid_height,
            'order_in_grid' => $order_in_grid,
        );

        // Use the database handler to insert the module placement
        // Assuming ASAP_Digest_Database has a method like insert_module_placement
        $new_placement_id = $this->database->insert_module_placement( $data );

        if ( is_wp_error( $new_placement_id ) ) {
            // Handle database insertion error
             return new WP_Error(
                'asap_digest_db_error', // Error code
                __( 'Failed to add module placement to database.', 'asap-digest-core' ), // Error message
                [ 'status' => 500 ] // HTTP status code
            );
        }

        if ( ! $new_placement_id ) {
             // Handle case where insertion failed but didn't return WP_Error
             return new WP_Error(
                'asap_digest_insert_failed', // Error code
                __( 'Module placement creation failed.', 'asap-digest-core' ), // Error message
                [ 'status' => 500 ] // HTTP status code
            );
        }

        // Return success response with the new placement ID
        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Module placement added successfully.', 'asap-digest-core' ),
            'data' => [ 'placement_id' => $new_placement_id ]
        ], 200 );
    }

    /**
     * Handles updating the placement details of a module in a digest.
     *
     * This endpoint allows updating the grid position, size, and order
     * of an existing module placement within a draft digest.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $digest_id The ID of the digest containing the placement. (Required, from URL)
     *     @type int $placement_id The ID of the module placement to update. (Required, from URL)
     *     @type int $grid_x The new x-coordinate. (Optional)
     *     @type int $grid_y The new y-coordinate. (Optional)
     *     @type int $grid_width The new width. (Optional, must be > 0)
     *     @type int $grid_height The new height. (Optional, must be > 0)
     *     @type int $order_in_grid The new order within the grid cell. (Optional)
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (with updated count), WP_Error on failure.
     *
     * @created 05.23.25 | 06:23 PM PDT
     */
    public function update_module_placement( $request ) {
        $digest_id = (int) $request['digest_id'];
        $placement_id = (int) $request['placement_id'];

        // Placement data to update (only include provided values)
        $update_data = [];
        if ( isset( $request['grid_x'] ) ) $update_data['grid_x'] = (int) $request['grid_x'];
        if ( isset( $request['grid_y'] ) ) $update_data['grid_y'] = (int) $request['grid_y'];
        if ( isset( $request['grid_width'] ) ) $update_data['grid_width'] = (int) $request['grid_width'];
        if ( isset( $request['grid_height'] ) ) $update_data['grid_height'] = (int) $request['grid_height'];
        if ( isset( $request['order_in_grid'] ) ) $update_data['order_in_grid'] = (int) $request['order_in_grid'];

        // Ensure there is data to update
        if ( empty( $update_data ) ) {
             return new WP_Error( 'no_data_to_update', 'No valid placement data provided for update.', ['status' => 400] );
        }

        // Get current user ID for ownership validation using enhanced authentication
        $current_user_id = $this->get_authenticated_user_id($request);
        if ( ! $current_user_id ) {
             return new WP_Error( 'asap_digest_auth_required', 'Authentication required.', ['status' => 401] );
        }

        // Assuming ASAP_Digest_Database has an update_module_placement method
        // This method should handle validation (e.g., checking digest status internally
        // and ownership based on the provided user ID).
        $updated = $this->database->update_module_placement( $placement_id, $digest_id, $current_user_id, $update_data );

        if ( $updated !== false && $updated >= 0 ) { // Check for false specifically, as 0 could mean no rows affected but not an error
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Module placement updated.',
                'updated_count' => $updated // $wpdb->update returns number of rows affected or false
            ], 200 );
        } else {
             // TODO: Log the database update error using ErrorLogger ($wpdb->last_error) if $updated === false
             // Consider more specific error handling based on the database method's return value if it indicates specific issues like not found or not draft.
             return new WP_Error( 'update_placement_failed', 'Could not update module placement due to a database error, invalid parameters, or insufficient permissions.', ['status' => 500] );
        }
    }

    /**
     * Handles removing a module from a digest.
     *
     * This endpoint allows removing a specific module placement
     * from a draft digest.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $digest_id The ID of the digest containing the placement. (Required, from URL)
     *     @type int $placement_id The ID of the module placement to remove. (Required, from URL)
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (with deleted count), WP_Error on failure.
     *
     * @created 05.23.25 | 06:23 PM PDT
     */
    public function remove_module_from_digest( $request ) {
        $digest_id = (int) $request['digest_id'];
        $placement_id = (int) $request['placement_id'];

        // Get current user ID for ownership validation using enhanced authentication
        $current_user_id = $this->get_authenticated_user_id($request);
        if ( ! $current_user_id ) {
             return new WP_Error( 'asap_digest_auth_required', 'Authentication required.', ['status' => 401] );
        }

        // Assuming ASAP_Digest_Database has a delete_module_placement method
        // This method should handle validation (e.g., checking digest status internally
        // and ownership based on the provided user ID).
        $deleted = $this->database->delete_module_placement( $placement_id, $digest_id, $current_user_id );

        if ( $deleted !== false && $deleted >= 0 ) { // Check for false specifically
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Module removed from digest.',
                'deleted_count' => $deleted // $wpdb->delete returns number of rows affected or false
            ], 200 );
        } else {
             // TODO: Log the database deletion error using ErrorLogger ($wpdb->last_error) if $deleted === false
             // Consider more specific error handling based on the database method's return value if it indicates specific issues like not found or not draft.
             return new WP_Error( 'remove_module_failed', 'Could not remove module from digest due to a database error, invalid parameters, or insufficient permissions.', ['status' => 500] );
        }
    }

    /**
     * Handles retrieving a specific digest with its modules.
     *
     * This endpoint fetches a specific digest and all of its associated
     * module placements, including relevant content details.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $digest_id The ID of the digest to retrieve. (Required, from URL)
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (with digest data),
     *     WP_Error on failure (e.g., not found, permissions).
     *
     * @created 05.23.25 | 06:23 PM PDT
     */
    public function get_digest( $request ) {
        $digest_id = (int) $request['digest_id'];

        // Get current user ID for ownership validation using enhanced authentication
        $current_user_id = $this->get_authenticated_user_id($request);
        if ( ! $current_user_id ) {
             return new WP_Error( 'asap_digest_auth_required', 'Authentication required.', ['status' => 401] );
        }

        // Assuming ASAP_Digest_Database has a get_digest_with_placements method
        // This method should handle fetching the digest and its related module placements.
        // It might also join with other tables to get module details (e.g., title, type).
        // It should also handle ownership validation based on the provided user ID.
        $digest_data = $this->database->get_digest_with_placements( $digest_id, $current_user_id );

        if ( $digest_data ) {
            // TODO: Potentially format the response data structure further if needed by the frontend
            return new WP_REST_Response( [
                 'success' => true,
                 'message' => 'Digest fetched successfully.',
                 'data' => $digest_data
             ], 200 );
        } else {
             // TODO: Log the error if needed (e.g., database error) if $digest_data === false or null
             // The database method should return false or null if not found or no permissions.
             return new WP_Error( 'digest_not_found_or_no_permissions', 'Digest not found or you do not have permissions to view it.', ['status' => 404] );
        }
    }

    /**
     * Handles listing digests for a specific user.
     *
     * This endpoint fetches a list of digests created by a specific user,
     * optionally filtered by status.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $user_id The ID of the user whose digests to retrieve. (Required, from URL)
     *     @type string $status Optional. Filter digests by status (e.g., 'draft', 'published', 'archived').
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (with list of digests),
     *     WP_Error on failure (e.g., permissions).
     *
     * @created 05.23.25 | 06:23 PM PDT
     */
    public function get_current_users_digests( $request ) {
        // Get authenticated user ID
        $wp_user_id = $this->get_authenticated_user_id($request);
        if (!$wp_user_id) {
            return new WP_Error('asap_digest_auth_required', 'Authentication required.', ['status' => 401]);
        }

        // Use the existing get_users_digests logic but with the authenticated user ID
        $request->set_param('user_id', $wp_user_id);
        return $this->get_users_digests($request);
    }

    /**
     * Handles fetching all digests for a specific user.
     *
     * This endpoint fetches all digests for a given user ID, with optional status filtering.
     * Users can only access their own digests unless they have admin capabilities.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $user_id The ID of the user whose digests to fetch. (Required, from URL)
     *     @type string $status Optional status filter ('draft', 'published', 'archived'). (Optional, from query)
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (with list of digests),
     *     WP_Error on failure (e.g., permissions).
     *
     * @created 05.23.25 | 06:23 PM PDT
     */
    public function get_users_digests( $request ) {
        $user_id = (int) $request['user_id'];
        $status = isset( $request['status'] ) ? sanitize_text_field( $request['status'] ) : null;

        // Get current user ID using enhanced authentication (supports both WordPress sessions and Better Auth tokens)
        $current_user_id = $this->get_authenticated_user_id($request);
        if ( ! $current_user_id ) {
             return new WP_Error( 'asap_digest_auth_required', 'Authentication required.', ['status' => 401] );
        }

        // Ensure the requested user ID matches the authenticated user ID, unless user has admin capabilities
        // TODO: Implement capability check for administrators to view other users' digests
        if ( $current_user_id !== $user_id /* && ! current_user_can( 'manage_options' ) */ ) {
             return new WP_Error( 'asap_digest_permission_denied', 'You do not have permission to view digests for this user.', ['status' => 403] );
        }

        // Fetch digests from the database
        // Assuming get_user_digests method in ASAP_Digest_Database handles optional status filtering
        $digests_list = $this->database->get_user_digests( $user_id, $status );

        if ( ! empty( $digests_list ) ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'User digests fetched successfully.',
                'data' => $digests_list
             ], 200 );
        } else {
             // Return an empty array if no digests are found, consistent with REST API best practices for collections
             return new WP_REST_Response( [
                 'success' => true,
                 'message' => 'No digests found for this user with the specified status.',
                 'data' => []
              ], 200 );
        }
    }

    /**
     * Handles publishing a draft digest.
     *
     * This endpoint changes the status of a draft digest to 'published'.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $digest_id The ID of the draft digest to publish. (Required, from URL)
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (with success message),
     *     WP_Error on failure (e.g., not found, not draft, permissions, database error).
     *
     * @created 05.23.25 | 06:23 PM PDT
     */
    public function publish_digest( $request ) {
         $digest_id = (int) $request['digest_id'];

        // Get current user ID for ownership validation using enhanced authentication
        $current_user_id = $this->get_authenticated_user_id($request);
        if ( ! $current_user_id ) {
             return new WP_Error( 'asap_digest_auth_required', 'Authentication required.', ['status' => 401] );
        }

        // Assuming ASAP_Digest_Database has an update_digest_status method
        // This method should handle validation (e.g., checking current status internally and ownership).
        $updated = $this->database->update_digest_status( $digest_id, 'published', $current_user_id );

        if ( $updated !== false ) { // Check for false specifically
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Digest published successfully.',
            ], 200 );
        } else {
             // TODO: Log the database update error using ErrorLogger ($wpdb->last_error) if the database method returns false due to DB error.
             // Consider more specific error handling based on the database method's return value if it indicates specific issues (e.g., digest not draft, not found, no rows affected).
             return new WP_Error( 'publish_failed', 'Could not publish digest due to a database error, invalid ID, or invalid state (must be draft). ', ['status' => 500] );
        }
    }

     /**
     * Handles deleting a digest.
     *
     * This endpoint deletes a digest and all its associated module placements.
     * The database schema includes ON DELETE CASCADE for module placements,
     * so deleting the digest automatically deletes associated placements.
     *
     * @param WP_REST_Request $request The REST API request. Expected parameters:
     *     @type int $digest_id The ID of the digest to delete. (Required, from URL)
     *
     * @return WP_REST_Response|WP_Error WP_REST_Response on success (with deletion confirmation),
     *     WP_Error on failure (e.g., not found, permissions, database error).
     *
     * @created 12.10.24 | 06:30 PM PDT
     */
    public function delete_digest( $request ) {
         $digest_id = (int) $request['digest_id'];

        // Get current user ID for ownership validation using enhanced authentication
        $current_user_id = $this->get_authenticated_user_id($request);
        if ( ! $current_user_id ) {
             return new WP_Error( 'asap_digest_auth_required', 'Authentication required.', ['status' => 401] );
        }

        // Assuming ASAP_Digest_Database has a delete_digest_by_id method
        // This method should handle ownership validation and deleting the digest record.
        // Note: The database schema includes ON DELETE CASCADE for module placements,
        // so deleting the digest should automatically delete associated placements.
        $deleted = $this->database->delete_digest_by_id( $digest_id, $current_user_id );

        if ( $deleted !== false && $deleted > 0 ) { // Check for false specifically and ensure rows were affected
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Digest deleted successfully.',
                'deleted_count' => $deleted // $wpdb->delete returns number of rows affected or false
            ], 200 );
        } else if ( $deleted === 0 ) {
             // No rows affected - digest not found or user doesn't own it
             return new WP_Error( 'digest_not_found_or_no_permissions', 'Digest not found or you do not have permission to delete it.', ['status' => 404] );
        } else {
             // Database error occurred
             return new WP_Error( 'delete_failed', 'Could not delete digest due to a database error.', ['status' => 500] );
        }
    }

    /**
     * Check permissions for fetching items (digest lists).
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error Whether the current user has permissions to fetch items.
     */
    public function get_items_permissions_check( $request ) {
        // Try both WordPress session and Better Auth token authentication
        $user_id = $this->get_authenticated_user_id($request);
        
        if (!$user_id) {
            return new WP_Error( 'asap_digest_auth_required', 'Authentication required to fetch digests.', ['status' => 401] );
        }

        // For user-specific digest lists, check if user can view the requested user's digests
        if ( isset( $request['user_id'] ) ) {
            $requested_user_id = (int) $request['user_id'];
            
            // Users can only access their own digests unless they're an admin
            if ( $user_id !== $requested_user_id && ! user_can( $user_id, 'manage_options' ) ) {
                return new WP_Error( 'asap_digest_forbidden', 'You can only access your own digests.', ['status' => 403] );
            }
        }

        return true;
    }

    /**
     * Check permissions for fetching a single item (individual digest).
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error Whether the current user has permissions to fetch the item.
     */
    public function get_item_permissions_check( $request ) {
        // Try both WordPress session and Better Auth token authentication
        $user_id = $this->get_authenticated_user_id($request);
        
        if (!$user_id) {
            return new WP_Error( 'asap_digest_auth_required', 'Authentication required to fetch digest.', ['status' => 401] );
        }

        // Additional ownership validation will be handled in the endpoint method itself
        // since we need to query the database to check digest ownership
        return true;
    }

    /**
     * Check permissions for updating items (digest operations).
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error Whether the current user has permissions to update items.
     */
    public function update_item_permissions_check( $request ) {
        // Try both WordPress session and Better Auth token authentication
        $user_id = $this->get_authenticated_user_id($request);
        
        if (!$user_id) {
            return new WP_Error( 'asap_digest_auth_required', 'Authentication required to update digests.', ['status' => 401] );
        }

        // Additional ownership validation will be handled in the endpoint methods themselves
        // since we need to query the database to check digest ownership
        return true;
    }

    /**
     * Check permissions for deleting items (digest deletion).
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error Whether the current user has permissions to delete items.
     */
    public function delete_item_permissions_check( $request ) {
        // Try both WordPress session and Better Auth token authentication
        $user_id = $this->get_authenticated_user_id($request);
        
        if (!$user_id) {
            return new WP_Error( 'asap_digest_auth_required', 'Authentication required to delete digests.', ['status' => 401] );
        }

        // Additional ownership validation will be handled in the delete_digest method itself
        // since we need to query the database to check digest ownership
        return true;
    }
}

// Note: This controller is now instantiated and registered in the main core class
// See: wp-content/plugins/asapdigest-core/includes/class-core.php -> register_rest_routes()