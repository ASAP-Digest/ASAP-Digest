<?php
/**
 * ASAP Digest Template CPT
 *
 * @package ASAPDigest_Core
 * @created 05.16.24 | 03:43 PM PDT
 * @file-marker Template_CPT
 */

namespace ASAPDigest\CPT;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ASAP_Digest_Template_CPT
 *
 * Handles the registration of the 'asap_digest_template' Custom Post Type.
 */
class ASAP_Digest_Template_CPT {

    /**
     * Constructor.
     *
     * Hooks the registration method to WordPress's 'init' action.
     */
    public function __construct() {
        add_action('init', [$this, 'register']);
    }

    /**
     * Registers the 'asap_digest_template' Custom Post Type.
     */
    public function register() {
        $labels = [
            'name'                  => _x('Digest Templates', 'Post Type General Name', 'asap-digest'),
            'singular_name'         => _x('Digest Template', 'Post Type Singular Name', 'asap-digest'),
            'menu_name'             => __('Templates', 'asap-digest'),
            'name_admin_bar'        => __('Digest Template', 'asap-digest'),
            'archives'              => __('Digest Template Archives', 'asap-digest'),
            'attributes'            => __('Digest Template Attributes', 'asap-digest'),
            'parent_item_colon'     => __('Parent Digest Template:', 'asap-digest'),
            'all_items'             => __('All Digest Templates', 'asap-digest'),
            'add_new_item'          => __('Add New Digest Template', 'asap-digest'),
            'add_new'               => __('Add New', 'asap-digest'),
            'new_item'              => __('New Digest Template', 'asap-digest'),
            'edit_item'             => __('Edit Digest Template', 'asap-digest'),
            'update_item'           => __('Update Digest Template', 'asap-digest'),
            'view_item'             => __('View Digest Template', 'asap-digest'),
            'view_items'            => __('View Digest Templates', 'asap-digest'),
            'search_items'          => __('Search Digest Templates', 'asap-digest'),
            'not_found'             => __('Not Found', 'asap-digest'),
            'not_found_in_trash'    => __('Not Found in Trash', 'asap-digest'),
            'featured_image'        => __('Featured Image', 'asap-digest'),
            'set_featured_image'    => __('Set Featured Image', 'asap-digest'),
            'remove_featured_image' => __('Remove Featured Image', 'asap-digest'),
            'use_featured_image'    => __('Use as Featured Image', 'asap-digest'),
            'insert_into_item'      => __('Insert into Digest Template', 'asap-digest'),
            'uploaded_to_this_item' => __('Uploaded to this Digest Template', 'asap-digest'),
            'items_list'            => __('Digest Templates list', 'asap-digest'),
            'items_list_navigation' => __('Digest Templates list navigation', 'asap-digest'),
            'filter_items_list'     => __('Filter Digest Templates list', 'asap-digest'),
        ];
        $args = [
            'label'                 => __('Digest Template', 'asap-digest'),
            'description'           => __('Predefined layouts for ASAP Digests.', 'asap-digest'),
            'labels'                => $labels,
            'supports'              => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true, // Required for the Block Editor and REST API
            'rewrite'               => ['slug' => 'digest-template'],
        ];

        register_post_type('asap_digest_template', $args);
    }
} 