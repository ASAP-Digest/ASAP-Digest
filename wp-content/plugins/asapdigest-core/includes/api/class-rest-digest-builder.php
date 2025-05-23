<?php
/**
 * REST API Endpoint for Digest Building and Module Placement
 *
 * @package ASAPDigest_Core
 * @subpackage API
 */

namespace ASAPDigest\Core\API;

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

        // Hook into the REST API initialization
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
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
                'user_id' => [
                    'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); },
                    'required' => true,
                ],
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
            'permission_callback' => [ $this, 'get_items_permissions_check' ], // Implement appropriate permission checks
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
        // TODO: Implement logic to fetch real layout templates from a database or predefined source
        // For now, return dummy data matching the frontend structure
        $layout_templates = [
            [ 'id' => 'default-grid', 'name' => 'Default Grid Layout' ],
            [ 'id' => 'sidebar-layout', 'name' => 'Sidebar Layout' ],
            [ 'id' => 'card-layout', 'name' => 'Card Layout' ],
        ];

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'Layout templates fetched successfully.',
            'data' => $layout_templates,
        ], 200 );
    }

    /**
     * Handles fetching the list of available module CPTs.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_available_modules( $request ) {
        // TODO: Implement logic to fetch real module CPTs
        // For now, return dummy data
        $modules = [
            [ 'id' => 1, 'name' => 'Headline Module', 'description' => 'Displays a prominent headline.' ],
            [ 'id' => 2, 'name' => 'Content Block Module', 'description' => 'Displays a block of text.' ],
            [ 'id' => 3, 'name' => 'Image Module', 'description' => 'Displays an image.' ],
        ];

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'Available modules fetched successfully.',
            'data' => $modules,
        ], 200 );
    }

    /**
     * Handles creating a new draft digest.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_draft_digest( $request ) {
        // Extract data from request
        $user_id = (int) $request['user_id'];
        $layout_template_id = sanitize_text_field( $request['layout_template_id'] );

        // TODO: Implement logic to create a new draft digest entry in wp_asap_digests
        // You'll need to call a method in ASAP_Digest_Database to insert the new digest record
        // Ensure 'status' is set to 'draft' and 'layout_template_id' is stored
        // Return the newly created digest ID and possibly some basic info

        // Example placeholder response:
        $new_digest_id = 0; // Replace with actual new digest ID from DB insertion
        if ( $new_digest_id ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Draft digest created.',
                'digest_id' => $new_digest_id,
                'layout_template_id' => $layout_template_id,
                'status' => 'draft',
            ], 201 );
        } else {
             return new WP_Error( 'digest_creation_failed', 'Could not create draft digest.', ['status' => 500] );
        }

        // Implement logic to create a new draft digest entry in wp_asap_digests
        $data = [
            'user_id'            => $user_id,
            'layout_template_id' => $layout_template_id,
            'status'             => 'draft',
        ];

        // Assuming ASAP_Digest_Database has an insert method, e.g., insert_digest()
        // This method should handle sanitization and validation internally or rely on prior validation.
        $new_digest_id = $this->database->insert_digest( $data );

        if ( $new_digest_id ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Draft digest created.',
                'digest_id' => $new_digest_id,
                'layout_template_id' => $layout_template_id,
                'status' => 'draft',
            ], 201 );
        } else {
             // Log the database insertion error
             // ErrorLogger::log('rest_api', 'create_digest_db_error', 'Failed to insert new digest record.', ['user_id' => $user_id, 'layout_template_id' => $layout_template_id], 'error');
             return new WP_Error( 'digest_creation_failed', 'Could not create draft digest due to a database error.', ['status' => 500] );
        }
    }

    /**
     * Handles adding a module to a draft digest.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function add_module_to_digest( $request ) {
        $digest_id = (int) $request['digest_id'];
        $module_cpt_id = (int) $request['module_cpt_id'];

        // Optional placement data
        $grid_x = isset( $request['grid_x'] ) ? (int) $request['grid_x'] : 0;
        $grid_y = isset( $request['grid_y'] ) ? (int) $request['grid_y'] : 0;
        $grid_width = isset( $request['grid_width'] ) ? (int) $request['grid_width'] : 1; // Default width
        $grid_height = isset( $request['grid_height'] ) ? (int) $request['grid_height'] : 1; // Default height
        $order_in_grid = isset( $request['order_in_grid'] ) ? (int) $request['order_in_grid'] : 0;

        // TODO: Implement logic to add a new entry to wp_asap_digest_module_placements
        // - Validate that the digest_id exists and is in 'draft' status
        // - Validate that the module_cpt_id exists (e.g., is a valid CPT entry)
        // - Potentially trigger content ingestion/AI processing if the module is new (refer to Bridge plan)
        // - Call a method in ASAP_Digest_Database to insert the new module placement record
        // Return the newly created placement ID

         $new_placement_id = 0; // Replace with actual new placement ID from DB insertion
         if ( $new_placement_id ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Module added to digest.',
                'placement_id' => $new_placement_id,
            ], 201 );
        } else {
             // TODO: Validate that the digest_id exists and is in 'draft' status
             // TODO: Validate that the module_cpt_id exists (e.g., is a valid CPT entry)
             // TODO: Potentially trigger content ingestion/AI processing if the module is new (refer to Bridge plan)

             $data = [
                 'digest_id'     => $digest_id,
                 'module_cpt_id' => $module_cpt_id,
                 'grid_x'        => $grid_x,
                 'grid_y'        => $grid_y,
                 'grid_width'    => $grid_width,
                 'grid_height'   => $grid_height,
                 'order_in_grid' => $order_in_grid,
             ];

             // Assuming ASAP_Digest_Database has an insert_module_placement method
             $new_placement_id = $this->database->insert_module_placement( $data );

             if ( $new_placement_id ) {
                 return new WP_REST_Response( [
                    'success' => true,
                    'message' => 'Module added to digest.',
                    'placement_id' => $new_placement_id,
                ], 201 );
             } else {
                 // TODO: Log the database insertion error using ErrorLogger
                 return new WP_Error( 'add_module_failed', 'Could not add module to digest due to a database error.', ['status' => 500] );
             }
        }
    }

    /**
     * Handles updating the placement details of a module in a digest.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
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

        // TODO: Implement logic to update an entry in wp_asap_digest_module_placements
        // - Validate that the digest_id and placement_id exist
        // - Ensure the digest is in 'draft' status
        // - Call a method in ASAP_Digest_Database to update the placement record

         $updated = false; // Replace with actual result of update operation
         if ( $updated ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Module placement updated.',
            ], 200 );
        } else {
             // Consider more specific error handling (e.g., placement not found, digest not draft)
             return new WP_Error( 'update_placement_failed', 'Could not update module placement.', ['status' => 500] );
        }

        // TODO: Validate that the digest_id and placement_id exist and belong to the user
        // TODO: Ensure the digest is in 'draft' status

        // Ensure there is data to update
        if ( empty( $update_data ) ) {
             return new WP_Error( 'no_data_to_update', 'No valid placement data provided for update.', ['status' => 400] );
        }

        // Assuming ASAP_Digest_Database has an update_module_placement method
        // This method should handle validation (e.g., checking digest status internally)
        $updated = $this->database->update_module_placement( $placement_id, $digest_id, $update_data );

        if ( $updated !== false ) { // Check for false specifically, as 0 could mean no rows affected but not an error
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Module placement updated.',
                'updated_count' => $updated // $wpdb->update returns number of rows affected or false
            ], 200 );
        } else {
             // TODO: Log the database update error using ErrorLogger ($wpdb->last_error)
             // Consider more specific error handling (e.g., placement not found, digest not draft)
             return new WP_Error( 'update_placement_failed', 'Could not update module placement due to a database error or invalid parameters.', ['status' => 500] );
        }
    }

     /**
     * Handles removing a module from a digest.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function remove_module_from_digest( $request ) {
        $digest_id = (int) $request['digest_id'];
        $placement_id = (int) $request['placement_id'];

        // TODO: Implement logic to delete an entry from wp_asap_digest_module_placements
        // - Validate that the digest_id and placement_id exist and belong to the user
        // - Ensure the digest is in 'draft' status
        // - Call a method in ASAP_Digest_Database to delete the placement record

         $deleted = false; // Replace with actual result of delete operation
         if ( $deleted ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Module removed from digest.',
            ], 200 );
        } else {
             // Consider more specific error handling (e.g., placement not found, digest not draft)
             return new WP_Error( 'remove_module_failed', 'Could not remove module from digest.', ['status' => 500] );
        }

        // TODO: Validate that the digest_id and placement_id exist and belong to the user
        // TODO: Ensure the digest is in 'draft' status

        // Assuming ASAP_Digest_Database has a delete_module_placement method
        // This method should handle validation (e.g., checking digest status internally)
        $deleted = $this->database->delete_module_placement( $placement_id, $digest_id );

        if ( $deleted !== false ) { // Check for false specifically
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Module removed from digest.',
                'deleted_count' => $deleted // $wpdb->delete returns number of rows affected or false
            ], 200 );
        } else {
             // TODO: Log the database deletion error using ErrorLogger ($wpdb->last_error)
             // Consider more specific error handling (e.g., placement not found, digest not draft)
             return new WP_Error( 'remove_module_failed', 'Could not remove module from digest due to a database error or invalid parameters.', ['status' => 500] );
        }
    }

    /**
     * Handles retrieving a specific digest with its modules.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_digest( $request ) {
        $digest_id = (int) $request['digest_id'];

        // TODO: Implement logic to retrieve a digest and its module placements
        // - Fetch the digest record from wp_asap_digests
        // - Fetch all associated module placement records from wp_asap_digest_module_placements
        // - Join with wp_posts or wp_asap_ingested_content/wp_asap_ai_processed_content to get module content details
        // - Structure the response to include digest details and an array of modules with placement info and content previews
        // - Ensure user can only retrieve their own digests unless they have higher permissions

        $digest_data = null; // Replace with fetched digest data
        if ( $digest_data ) {
             return new WP_REST_Response( $digest_data, 200 );
        } else {
             // TODO: Ensure user can only retrieve their own digests unless they have higher permissions

             // Assuming ASAP_Digest_Database has a get_digest_with_placements method
             // This method should handle fetching the digest and its related module placements.
             // It might also join with other tables to get module details (e.g., title, type).
             $digest_data = $this->database->get_digest_with_placements( $digest_id );

             if ( $digest_data ) {
                 // TODO: Potentially format the response data structure further if needed
                 return new WP_REST_Response( $digest_data, 200 );
             } else {
                 // TODO: Log the error if needed (e.g., digest_id doesn't exist or database error)
                 return new WP_Error( 'digest_not_found', 'Digest not found.', ['status' => 404] );
             }
        }
    }

     /**
     * Handles listing digests for a specific user.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_users_digests( $request ) {
        $user_id = (int) $request['user_id'];
        $status = isset( $request['status'] ) ? sanitize_text_field( $request['status'] ) : null;

        // TODO: Implement logic to fetch a list of digests for the user
        // - Query wp_asap_digests table filtered by user_id
        // - Optionally filter by status if provided
        // - Return a list of digest summaries (e.g., ID, creation date, status, layout ID, perhaps number of modules)
        // - Ensure user can only list their own digests unless they have higher permissions

        $digests_list = []; // Replace with fetched list of digests
        if ( ! empty( $digests_list ) || ( isset( $request['status'] ) && $status === 'draft' ) ) { // Return empty list if querying for drafts and none exist
             return new WP_REST_Response( $digests_list, 200 );
        } else if ( isset( $request['status'] ) && $status !== 'draft' ) {
             return new WP_Error( 'no_digests_found', 'No published or archived digests found for this user.', ['status' => 404] );
        } else {
             // Handle case where no digests (of any status) are found
              return new WP_REST_Response( [], 200 );
        }

        // TODO: Ensure user can only list their own digests unless they have higher permissions
        // TODO: Add validation for user_id (e.g., does the user exist?)

        // Fetch digests from the database
        // Assuming get_user_digests method in ASAP_Digest_Database handles optional status filtering
        $digests_list = $this->database->get_user_digests( $user_id, $status );

        if ( ! empty( $digests_list ) ) {
             return new WP_REST_Response( $digests_list, 200 );
        } else {
             // Return an empty array if no digests are found, consistent with REST API best practices for collections
             return new WP_REST_Response( [], 200 );
        }
    }

    /**
     * Handles publishing a draft digest.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function publish_digest( $request ) {
         $digest_id = (int) $request['digest_id'];

        // TODO: Implement logic to change digest status from 'draft' to 'published'
        // - Validate that the digest_id exists and is in 'draft' status and belongs to the user
        // - Update the status column in wp_asap_digests

        $published = false; // Replace with actual result of update operation
        if ( $published ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Digest published.',
            ], 200 );
        } else {
             // Consider more specific error handling (e.g., digest not draft, not found)
             return new WP_Error( 'publish_failed', 'Could not publish digest.', ['status' => 500] );
        }

        // TODO: Validate that the digest_id exists and belongs to the user
        // TODO: Ensure the digest is in 'draft' status

        // Assuming ASAP_Digest_Database has an update_digest_status method
        // This method should handle validation (e.g., checking current status internally)
        $updated = $this->database->update_digest_status( $digest_id, 'published' );

        if ( $updated !== false ) { // Check for false specifically
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Digest published successfully.',
            ], 200 );
        } else {
             // TODO: Log the database update error using ErrorLogger ($wpdb->last_error)
             // Consider more specific error handling (e.g., digest not draft, not found, no rows affected)
             return new WP_Error( 'publish_failed', 'Could not publish digest due to a database error or invalid state.', ['status' => 500] );
        }
    }

     /**
     * Handles deleting a digest.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_digest( $request ) {
         $digest_id = (int) $request['digest_id'];

        // TODO: Implement logic to delete a digest
        // - Validate that the digest_id exists and belongs to the user
        // - Delete the record from wp_asap_digests (module placements should cascade delete)

        $deleted = false; // Replace with actual result of delete operation
        if ( $deleted ) {
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Digest deleted.',
            ], 200 );
        } else {
             return new WP_Error( 'delete_failed', 'Could not delete digest.', ['status' => 500] );
        }

        // TODO: Validate that the digest_id exists and belongs to the user

        // Assuming ASAP_Digest_Database has a delete_digest_by_id method
        // This method should handle deleting the digest record.
        // Note: The database schema includes ON DELETE CASCADE for module placements,
        // so deleting the digest should automatically delete associated placements.
        $deleted = $this->database->delete_digest_by_id( $digest_id );

        if ( $deleted !== false ) { // Check for false specifically
             return new WP_REST_Response( [
                'success' => true,
                'message' => 'Digest deleted successfully.',
                'deleted_count' => $deleted // $wpdb->delete returns number of rows affected or false
            ], 200 );
        } else {
             // TODO: Log the database deletion error using ErrorLogger ($wpdb->last_error)
             // Consider more specific error handling (e.g., digest not found, database error)
             return new WP_Error( 'delete_failed', 'Could not delete digest due to a database error or invalid ID.', ['status' => 500] );
        }
    }

    /**
     * Checks if a given request has permission to get items.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if permissions are granted, WP_Error otherwise.
     */
    public function get_items_permissions_check( $request ) {
        // TODO: Implement permission checks. E.g., is the user logged in? Can they access digests for the requested user_id?
        // For now, a placeholder:
         return current_user_can( 'read' ); // Or a more specific capability
    }

     /**
     * Checks if a given request has permission to get a single item.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if permissions are granted, WP_Error otherwise.
     */
    public function get_item_permissions_check( $request ) {
        // TODO: Implement permission checks. E.g., is the user logged in? Is this their digest?
         return current_user_can( 'read' ); // Or a more specific capability
    }

     /**
     * Checks if a given request has permission to update an item.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if permissions are granted, WP_Error otherwise.
     */
    public function update_item_permissions_check( $request ) {
        // TODO: Implement permission checks. E.g., is the user logged in? Can they edit this digest?
         return current_user_can( 'edit_posts' ); // Or a more specific capability
    }

     /**
     * Checks if a given request has permission to delete an item.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if permissions are granted, WP_Error otherwise.
     */
    public function delete_item_permissions_check( $request ) {
        // TODO: Implement permission checks. E.g., is the user logged in? Can they delete this digest?
         return current_user_can( 'delete_posts' ); // Or a more specific capability
    }
}

// Instantiate the controller to register the routes
new REST_Digest_Builder();