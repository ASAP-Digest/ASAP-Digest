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
        add_action('init', [$this, 'create_default_templates'], 20); // Run after CPT registration
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

    /**
     * Create default layout templates if they don't exist
     */
    public function create_default_templates() {
        // Check if we already have templates
        $existing_templates = get_posts([
            'post_type' => 'asap_digest_template',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);

        if (!empty($existing_templates)) {
            return; // Templates already exist
        }

        // Create default templates based on the schema
        $default_templates = [
            [
                'name' => 'Classic Grid',
                'description' => 'Traditional 3-column layout with header and footer sections',
                'gridstack_config' => [
                    'cellHeight' => 80,
                    'verticalMargin' => 10,
                    'horizontalMargin' => 10,
                    'minRow' => 6,
                    'maxRow' => 12,
                    'column' => 12,
                    'animate' => true,
                    'float' => false
                ],
                'predefined_slots' => [
                    ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 2, 'id' => 'header', 'content' => 'Header Section'],
                    ['x' => 0, 'y' => 2, 'w' => 4, 'h' => 3, 'id' => 'left-column', 'content' => 'Left Column'],
                    ['x' => 4, 'y' => 2, 'w' => 4, 'h' => 3, 'id' => 'center-column', 'content' => 'Center Column'],
                    ['x' => 8, 'y' => 2, 'w' => 4, 'h' => 3, 'id' => 'right-column', 'content' => 'Right Column'],
                    ['x' => 0, 'y' => 5, 'w' => 12, 'h' => 1, 'id' => 'footer', 'content' => 'Footer Section']
                ]
            ],
            [
                'name' => 'Mobile-First Stack',
                'description' => 'Single-column layout optimized for mobile devices',
                'gridstack_config' => [
                    'cellHeight' => 60,
                    'verticalMargin' => 8,
                    'horizontalMargin' => 8,
                    'minRow' => 8,
                    'maxRow' => 20,
                    'column' => 12,
                    'animate' => true,
                    'float' => false
                ],
                'predefined_slots' => [
                    ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 2, 'id' => 'hero', 'content' => 'Hero Section'],
                    ['x' => 0, 'y' => 2, 'w' => 12, 'h' => 3, 'id' => 'main-content', 'content' => 'Main Content'],
                    ['x' => 0, 'y' => 5, 'w' => 12, 'h' => 2, 'id' => 'secondary', 'content' => 'Secondary Content'],
                    ['x' => 0, 'y' => 7, 'w' => 12, 'h' => 1, 'id' => 'footer', 'content' => 'Footer']
                ]
            ],
            [
                'name' => 'Dashboard Style',
                'description' => 'Widget-based layout with flexible positioning',
                'gridstack_config' => [
                    'cellHeight' => 70,
                    'verticalMargin' => 12,
                    'horizontalMargin' => 12,
                    'minRow' => 8,
                    'maxRow' => 16,
                    'column' => 12,
                    'animate' => true,
                    'float' => true
                ],
                'predefined_slots' => [
                    ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 3, 'id' => 'primary-widget', 'content' => 'Primary Widget'],
                    ['x' => 6, 'y' => 0, 'w' => 6, 'h' => 2, 'id' => 'stats-widget', 'content' => 'Stats Widget'],
                    ['x' => 6, 'y' => 2, 'w' => 3, 'h' => 2, 'id' => 'small-widget-1', 'content' => 'Small Widget 1'],
                    ['x' => 9, 'y' => 2, 'w' => 3, 'h' => 2, 'id' => 'small-widget-2', 'content' => 'Small Widget 2'],
                    ['x' => 0, 'y' => 3, 'w' => 12, 'h' => 2, 'id' => 'full-width', 'content' => 'Full Width Section']
                ]
            ],
            [
                'name' => 'Magazine Layout',
                'description' => 'Editorial-style layout with featured content areas',
                'gridstack_config' => [
                    'cellHeight' => 90,
                    'verticalMargin' => 15,
                    'horizontalMargin' => 15,
                    'minRow' => 6,
                    'maxRow' => 14,
                    'column' => 12,
                    'animate' => true,
                    'float' => false
                ],
                'predefined_slots' => [
                    ['x' => 0, 'y' => 0, 'w' => 8, 'h' => 4, 'id' => 'featured-story', 'content' => 'Featured Story'],
                    ['x' => 8, 'y' => 0, 'w' => 4, 'h' => 2, 'id' => 'trending-1', 'content' => 'Trending 1'],
                    ['x' => 8, 'y' => 2, 'w' => 4, 'h' => 2, 'id' => 'trending-2', 'content' => 'Trending 2'],
                    ['x' => 0, 'y' => 4, 'w' => 4, 'h' => 2, 'id' => 'article-1', 'content' => 'Article 1'],
                    ['x' => 4, 'y' => 4, 'w' => 4, 'h' => 2, 'id' => 'article-2', 'content' => 'Article 2'],
                    ['x' => 8, 'y' => 4, 'w' => 4, 'h' => 2, 'id' => 'article-3', 'content' => 'Article 3']
                ]
            ],
            [
                'name' => 'Minimal Clean',
                'description' => 'Simple, clean layout with focus on content',
                'gridstack_config' => [
                    'cellHeight' => 100,
                    'verticalMargin' => 20,
                    'horizontalMargin' => 20,
                    'minRow' => 4,
                    'maxRow' => 10,
                    'column' => 12,
                    'animate' => true,
                    'float' => false
                ],
                'predefined_slots' => [
                    ['x' => 2, 'y' => 0, 'w' => 8, 'h' => 3, 'id' => 'main-content', 'content' => 'Main Content'],
                    ['x' => 1, 'y' => 3, 'w' => 5, 'h' => 2, 'id' => 'left-content', 'content' => 'Left Content'],
                    ['x' => 6, 'y' => 3, 'w' => 5, 'h' => 2, 'id' => 'right-content', 'content' => 'Right Content']
                ]
            ]
        ];

        foreach ($default_templates as $template) {
            $post_id = wp_insert_post([
                'post_title' => $template['name'],
                'post_content' => $template['description'],
                'post_status' => 'publish',
                'post_type' => 'asap_digest_template',
                'meta_input' => [
                    'gridstack_config' => json_encode($template['gridstack_config']),
                    'predefined_slots' => json_encode($template['predefined_slots']),
                    'template_type' => 'gridstack',
                    'is_default' => true,
                    'created_by_system' => true
                ]
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                error_log("ASAP_CORE_DEBUG: Created default template: {$template['name']} (ID: {$post_id})");
            } else {
                error_log("ASAP_CORE_DEBUG: Failed to create template: {$template['name']}");
            }
        }
    }
} 