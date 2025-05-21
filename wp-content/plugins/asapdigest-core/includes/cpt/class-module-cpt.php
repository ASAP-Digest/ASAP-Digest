<?php
namespace ASAPDigest\CPT;

/**
 * Class Module_CPT
 *
 * Handles the registration of the 'asap_module' Custom Post Type.
 */
class Module_CPT {

    /**
     * Constructor.
     *
     * Hooks the registration method to WordPress's 'init' action.
     */
    public function __construct() {
        add_action('init', [$this, 'register']);
        add_action('init', [$this, 'register_taxonomies']);
    }

    /**
     * Registers the 'asap_module' Custom Post Type.
     */
    public function register() {
        $labels = [
            'name'                  => _x('Modules', 'Post Type General Name', 'asap-digest'),
            'singular_name'         => _x('Module', 'Post Type Singular Name', 'asap-digest'),
            'menu_name'             => __('Modules', 'asap-digest'),
            'name_admin_bar'        => __('Module', 'asap-digest'),
            'archives'              => __('Module Archives', 'asap-digest'),
            'attributes'            => __('Module Attributes', 'asap-digest'),
            'parent_item_colon'     => __('Parent Module:', 'asap-digest'),
            'all_items'             => __('All Modules', 'asap-digest'),
            'add_new_item'          => __('Add New Module', 'asap-digest'),
            'add_new'               => __('Add New', 'asap-digest'),
            'new_item'              => __('New Module', 'asap-digest'),
            'edit_item'             => __('Edit Module', 'asap-digest'),
            'update_item'           => __('Update Module', 'asap-digest'),
            'view_item'             => __('View Module', 'asap-digest'),
            'view_items'            => __('View Modules', 'asap-digest'),
            'search_items'          => __('Search Module', 'asap-digest'),
            'not_found'             => __('Not found', 'asap-digest'),
            'not_found_in_trash'    => __('Not found in Trash', 'asap-digest'),
            'featured_image'        => __('Featured Image', 'asap-digest'),
            'set_featured_image'    => __('Set featured image', 'asap-digest'),
            'remove_featured_image' => __('Remove featured image', 'asap-digest'),
            'use_featured_image'    => __('Use as featured image', 'asap-digest'),
            'insert_into_item'      => __('Insert into module', 'asap-digest'),
            'uploaded_to_this_item' => __('Uploaded to this module', 'asap-digest'),
            'items_list'            => __('Modules list', 'asap-digest'),
            'items_list_navigation' => __('Modules list navigation', 'asap-digest'),
            'filter_items_list'     => __('Filter modules list', 'asap-digest'),
        ];

        $args = [
            'label'                 => __('Module', 'asap-digest'),
            'description'           => __('Custom Post Type for ASAP Modules, components of a Digest.', 'asap-digest'),
            'labels'                => $labels,
            'supports'              => ['title', 'editor', 'author', 'thumbnail', 'custom-fields', 'revisions'],
            'hierarchical'          => false,
            'public'                => false, // Modules are not typically public standalone entities
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=asap_digest', // Show as submenu under Digests
            'menu_icon'             => 'dashicons-screenoptions',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true, // Allow fetching via REST/GraphQL as part of a digest
            'capability_type'       => 'post', // or 'asap_module' for custom capabilities
            'show_in_rest'          => true,
            'rest_base'             => 'asap_modules',
            'show_in_graphql'       => true,
            'graphql_single_name'   => 'module',
            'graphql_plural_name'   => 'modules',
        ];

        register_post_type('asap_module', $args);
    }

    /**
     * Registers taxonomies for the 'asap_module' CPT.
     */
    public function register_taxonomies() {
        // Optional: asap_module_category
        $category_labels = [
            'name'              => _x('Module Categories', 'taxonomy general name', 'asap-digest'),
            'singular_name'     => _x('Module Category', 'taxonomy singular name', 'asap-digest'),
            'search_items'      => __('Search Module Categories', 'asap-digest'),
            'all_items'         => __('All Module Categories', 'asap-digest'),
            'parent_item'       => __('Parent Module Category', 'asap-digest'),
            'parent_item_colon' => __('Parent Module Category:', 'asap-digest'),
            'edit_item'         => __('Edit Module Category', 'asap-digest'),
            'update_item'       => __('Update Module Category', 'asap-digest'),
            'add_new_item'      => __('Add New Module Category', 'asap-digest'),
            'new_item_name'     => __('New Module Category Name', 'asap-digest'),
            'menu_name'         => __('Module Categories', 'asap-digest'),
        ];
        $category_args = [
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'asap_module_category'],
            'show_in_rest'      => true,
            'show_in_graphql'   => true,
            'graphql_single_name' => 'moduleCategory',
            'graphql_plural_name' => 'moduleCategories',
        ];
        register_taxonomy('asap_module_category', ['asap_module'], $category_args);

        // Optional: asap_module_tag
        $tag_labels = [
            'name'                       => _x('Module Tags', 'taxonomy general name', 'asap-digest'),
            'singular_name'              => _x('Module Tag', 'taxonomy singular name', 'asap-digest'),
            'search_items'               => __('Search Module Tags', 'asap-digest'),
            'popular_items'              => __('Popular Module Tags', 'asap-digest'),
            'all_items'                  => __('All Module Tags', 'asap-digest'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Module Tag', 'asap-digest'),
            'update_item'                => __('Update Module Tag', 'asap-digest'),
            'add_new_item'               => __('Add New Module Tag', 'asap-digest'),
            'new_item_name'              => __('New Module Tag Name', 'asap-digest'),
            'separate_items_with_commas' => __('Separate module tags with commas', 'asap-digest'),
            'add_or_remove_items'        => __('Add or remove module tags', 'asap-digest'),
            'choose_from_most_used'      => __('Choose from the most used module tags', 'asap-digest'),
            'not_found'                  => __('No module tags found.', 'asap-digest'),
            'menu_name'                  => __('Module Tags', 'asap-digest'),
        ];
        $tag_args = [
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'asap_module_tag'],
            'show_in_rest'          => true,
            'show_in_graphql'       => true,
            'graphql_single_name'   => 'moduleTag',
            'graphql_plural_name'   => 'moduleTags',
        ];
        register_taxonomy('asap_module_tag', ['asap_module'], $tag_args);
    }
} 