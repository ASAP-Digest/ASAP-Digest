<?php
namespace ASAPDigest\CPT;

/**
 * Class Digest_CPT
 *
 * Handles the registration of the 'asap_digest' Custom Post Type.
 */
class Digest_CPT {

    /**
     * Constructor.
     *
     * Hooks the registration method to WordPress's 'init' action.
     */
    public function __construct() {
        add_action('init', [$this, 'register']);
    }

    /**
     * Registers the 'asap_digest' Custom Post Type.
     */
    public function register() {
        $labels = [
            'name'                  => _x('Digests', 'Post Type General Name', 'asap-digest'),
            'singular_name'         => _x('Digest', 'Post Type Singular Name', 'asap-digest'),
            'menu_name'             => __('Digests', 'asap-digest'),
            'name_admin_bar'        => __('Digest', 'asap-digest'),
            'archives'              => __('Digest Archives', 'asap-digest'),
            'attributes'            => __('Digest Attributes', 'asap-digest'),
            'parent_item_colon'     => __('Parent Digest:', 'asap-digest'),
            'all_items'             => __('All Digests', 'asap-digest'),
            'add_new_item'          => __('Add New Digest', 'asap-digest'),
            'add_new'               => __('Add New', 'asap-digest'),
            'new_item'              => __('New Digest', 'asap-digest'),
            'edit_item'             => __('Edit Digest', 'asap-digest'),
            'update_item'           => __('Update Digest', 'asap-digest'),
            'view_item'             => __('View Digest', 'asap-digest'),
            'view_items'            => __('View Digests', 'asap-digest'),
            'search_items'          => __('Search Digest', 'asap-digest'),
            'not_found'             => __('Not found', 'asap-digest'),
            'not_found_in_trash'    => __('Not found in Trash', 'asap-digest'),
            'featured_image'        => __('Featured Image', 'asap-digest'),
            'set_featured_image'    => __('Set featured image', 'asap-digest'),
            'remove_featured_image' => __('Remove featured image', 'asap-digest'),
            'use_featured_image'    => __('Use as featured image', 'asap-digest'),
            'insert_into_item'      => __('Insert into digest', 'asap-digest'),
            'uploaded_to_this_item' => __('Uploaded to this digest', 'asap-digest'),
            'items_list'            => __('Digests list', 'asap-digest'),
            'items_list_navigation' => __('Digests list navigation', 'asap-digest'),
            'filter_items_list'     => __('Filter digests list', 'asap-digest'),
        ];

        $args = [
            'label'                 => __('Digest', 'asap-digest'),
            'description'           => __('Custom Post Type for ASAP Digests.', 'asap-digest'),
            'labels'                => $labels,
            'supports'              => ['title', 'editor', 'author', 'thumbnail', 'custom-fields', 'revisions'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-networking',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post', // or 'asap_digest' for custom capabilities
            'show_in_rest'          => true,
            'rest_base'             => 'asap_digests',
            'show_in_graphql'       => true,
            'graphql_single_name'   => 'digest',
            'graphql_plural_name'   => 'digests',
        ];

        register_post_type('asap_digest', $args);
    }
} 