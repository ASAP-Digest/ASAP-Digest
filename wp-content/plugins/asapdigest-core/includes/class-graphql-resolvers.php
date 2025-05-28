<?php
/**
 * GraphQL Resolvers
 * Custom GraphQL types, queries, and mutations for ASAP Digest custom tables
 * 
 * @package ASAPDigest
 * @since 1.0.0
 */

namespace ASAPDigest\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GraphQL_Resolvers
 * 
 * Registers custom GraphQL resolvers for custom tables
 */
class GraphQL_Resolvers {
    
    /**
     * Custom table manager instance
     * @var Custom_Table_Manager
     */
    private $table_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->table_manager = new Custom_Table_Manager();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('graphql_register_types', [$this, 'register_types']);
        add_action('graphql_register_types', [$this, 'register_queries']);
        add_action('graphql_register_types', [$this, 'register_mutations']);
    }
    
    /**
     * Register custom GraphQL types
     */
    public function register_types() {
        
        // Register Digest type
        register_graphql_object_type('AsapDigest', [
            'description' => 'A digest from wp_asap_digests custom table',
            'fields' => [
                'id' => [
                    'type' => 'ID',
                    'description' => 'The digest ID',
                ],
                'userId' => [
                    'type' => 'ID',
                    'description' => 'The user ID who created the digest',
                ],
                'status' => [
                    'type' => 'String',
                    'description' => 'The digest status (draft, published, etc.)',
                ],
                'layoutTemplateId' => [
                    'type' => 'String',
                    'description' => 'The layout template identifier',
                ],
                'content' => [
                    'type' => 'String',
                    'description' => 'The digest content',
                ],
                'sentimentScore' => [
                    'type' => 'String',
                    'description' => 'The sentiment score',
                ],
                'lifeMoment' => [
                    'type' => 'String',
                    'description' => 'The user life moment',
                ],
                'isSaved' => [
                    'type' => 'Boolean',
                    'description' => 'Whether the digest is saved',
                ],
                'reminders' => [
                    'type' => 'String',
                    'description' => 'Reminder data',
                ],
                'createdAt' => [
                    'type' => 'String',
                    'description' => 'Creation timestamp',
                ],
                'updatedAt' => [
                    'type' => 'String',
                    'description' => 'Last update timestamp',
                ],
                'modules' => [
                    'type' => ['list_of' => 'AsapDigestModulePlacement'],
                    'description' => 'Module placements in this digest',
                    'resolve' => function($digest) {
                        return $this->table_manager->get_digest_module_placements($digest['id']);
                    }
                ]
            ]
        ]);
        
        // Register Module type
        register_graphql_object_type('AsapModule', [
            'description' => 'A module from wp_asap_modules custom table',
            'fields' => [
                'id' => [
                    'type' => 'ID',
                    'description' => 'The module ID',
                ],
                'type' => [
                    'type' => 'String',
                    'description' => 'The module type',
                ],
                'title' => [
                    'type' => 'String',
                    'description' => 'The module title',
                ],
                'content' => [
                    'type' => 'String',
                    'description' => 'The module content',
                ],
                'sourceUrl' => [
                    'type' => 'String',
                    'description' => 'The source URL',
                ],
                'sourceId' => [
                    'type' => 'String',
                    'description' => 'The source ID',
                ],
                'ingestedContentId' => [
                    'type' => 'ID',
                    'description' => 'Reference to ingested content',
                ],
                'aiProcessedContentId' => [
                    'type' => 'ID',
                    'description' => 'Reference to AI processed content',
                ],
                'publishDate' => [
                    'type' => 'String',
                    'description' => 'The publish date',
                ],
                'qualityScore' => [
                    'type' => 'Float',
                    'description' => 'The quality score',
                ],
                'status' => [
                    'type' => 'String',
                    'description' => 'The module status',
                ],
                'metadata' => [
                    'type' => 'String',
                    'description' => 'Additional metadata as JSON',
                ],
                'createdAt' => [
                    'type' => 'String',
                    'description' => 'Creation timestamp',
                ],
                'updatedAt' => [
                    'type' => 'String',
                    'description' => 'Last update timestamp',
                ]
            ]
        ]);
        
        // Register Module Placement type
        register_graphql_object_type('AsapDigestModulePlacement', [
            'description' => 'A module placement from wp_asap_digest_module_placements table',
            'fields' => [
                'id' => [
                    'type' => 'ID',
                    'description' => 'The placement ID',
                ],
                'digestId' => [
                    'type' => 'ID',
                    'description' => 'The digest ID',
                ],
                'moduleId' => [
                    'type' => 'ID',
                    'description' => 'The module ID',
                ],
                'moduleCptId' => [
                    'type' => 'ID',
                    'description' => 'Legacy CPT module ID',
                ],
                'gridX' => [
                    'type' => 'Int',
                    'description' => 'Grid X position',
                ],
                'gridY' => [
                    'type' => 'Int',
                    'description' => 'Grid Y position',
                ],
                'gridWidth' => [
                    'type' => 'Int',
                    'description' => 'Grid width',
                ],
                'gridHeight' => [
                    'type' => 'Int',
                    'description' => 'Grid height',
                ],
                'sortOrder' => [
                    'type' => 'Int',
                    'description' => 'Sort order',
                ],
                'createdAt' => [
                    'type' => 'String',
                    'description' => 'Creation timestamp',
                ],
                'module' => [
                    'type' => 'AsapModule',
                    'description' => 'The associated module',
                    'resolve' => function($placement) {
                        if ($placement['module_id']) {
                            return $this->table_manager->get_module($placement['module_id']);
                        }
                        return null;
                    }
                ]
            ]
        ]);
        
        // Register Layout Template type (for existing CPT templates)
        register_graphql_object_type('AsapLayoutTemplate', [
            'description' => 'A layout template',
            'fields' => [
                'id' => [
                    'type' => 'ID',
                    'description' => 'The template ID',
                ],
                'slug' => [
                    'type' => 'String',
                    'description' => 'The template slug',
                ],
                'title' => [
                    'type' => 'String',
                    'description' => 'The template title',
                ],
                'description' => [
                    'type' => 'String',
                    'description' => 'The template description',
                ],
                'gridstackConfig' => [
                    'type' => 'String',
                    'description' => 'Gridstack configuration as JSON',
                ],
                'defaultPlacements' => [
                    'type' => 'String',
                    'description' => 'Default module placements as JSON',
                ],
                'maxModules' => [
                    'type' => 'Int',
                    'description' => 'Maximum number of modules',
                ],
                'isActive' => [
                    'type' => 'Boolean',
                    'description' => 'Whether the template is active',
                ]
            ]
        ]);
        
        // Register input types for mutations
        register_graphql_input_type('CreateDigestInput', [
            'description' => 'Input for creating a new digest',
            'fields' => [
                'userId' => [
                    'type' => 'ID',
                    'description' => 'The user ID',
                ],
                'layoutTemplateId' => [
                    'type' => 'String',
                    'description' => 'The layout template identifier',
                ],
                'status' => [
                    'type' => 'String',
                    'description' => 'The digest status',
                ],
                'content' => [
                    'type' => 'String',
                    'description' => 'The digest content',
                ],
                'lifeMoment' => [
                    'type' => 'String',
                    'description' => 'The user life moment',
                ]
            ]
        ]);
        
        register_graphql_input_type('AddModuleToDigestInput', [
            'description' => 'Input for adding a module to a digest',
            'fields' => [
                'digestId' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The digest ID',
                ],
                'moduleId' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The module ID',
                ],
                'gridX' => [
                    'type' => 'Int',
                    'description' => 'Grid X position',
                ],
                'gridY' => [
                    'type' => 'Int',
                    'description' => 'Grid Y position',
                ],
                'gridWidth' => [
                    'type' => 'Int',
                    'description' => 'Grid width',
                ],
                'gridHeight' => [
                    'type' => 'Int',
                    'description' => 'Grid height',
                ],
                'sortOrder' => [
                    'type' => 'Int',
                    'description' => 'Sort order',
                ]
            ]
        ]);
        
        register_graphql_input_type('CreateModuleInput', [
            'description' => 'Input for creating a new module',
            'fields' => [
                'type' => [
                    'type' => ['non_null' => 'String'],
                    'description' => 'The module type',
                ],
                'title' => [
                    'type' => ['non_null' => 'String'],
                    'description' => 'The module title',
                ],
                'content' => [
                    'type' => ['non_null' => 'String'],
                    'description' => 'The module content',
                ],
                'sourceUrl' => [
                    'type' => 'String',
                    'description' => 'The source URL',
                ],
                'sourceId' => [
                    'type' => 'String',
                    'description' => 'The source ID',
                ],
                'publishDate' => [
                    'type' => 'String',
                    'description' => 'The publish date',
                ],
                'qualityScore' => [
                    'type' => 'Float',
                    'description' => 'The quality score',
                ],
                'metadata' => [
                    'type' => 'String',
                    'description' => 'Additional metadata as JSON',
                ]
            ]
        ]);
    }
    
    /**
     * Register custom GraphQL queries
     */
    public function register_queries() {
        
        // Get user digests
        register_graphql_field('RootQuery', 'userDigests', [
            'type' => ['list_of' => 'AsapDigest'],
            'description' => 'Get digests for a specific user',
            'args' => [
                'userId' => [
                    'type' => 'ID',
                    'description' => 'The user ID',
                ],
                'status' => [
                    'type' => 'String',
                    'description' => 'Filter by status',
                ]
            ],
            'resolve' => function($root, $args) {
                $user_id = $args['userId'] ?? get_current_user_id();
                $status = $args['status'] ?? null;
                
                return $this->table_manager->get_user_digests($user_id, $status);
            }
        ]);
        
        // Get single digest
        register_graphql_field('RootQuery', 'digest', [
            'type' => 'AsapDigest',
            'description' => 'Get a single digest by ID',
            'args' => [
                'id' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The digest ID',
                ]
            ],
            'resolve' => function($root, $args) {
                return $this->table_manager->get_digest($args['id']);
            }
        ]);
        
        // Get modules
        register_graphql_field('RootQuery', 'modules', [
            'type' => ['list_of' => 'AsapModule'],
            'description' => 'Get modules with optional filtering',
            'args' => [
                'type' => [
                    'type' => 'String',
                    'description' => 'Filter by module type',
                ],
                'status' => [
                    'type' => 'String',
                    'description' => 'Filter by status',
                ],
                'limit' => [
                    'type' => 'Int',
                    'description' => 'Limit number of results',
                ],
                'offset' => [
                    'type' => 'Int',
                    'description' => 'Offset for pagination',
                ]
            ],
            'resolve' => function($root, $args) {
                return $this->table_manager->get_modules($args);
            }
        ]);
        
        // Get single module
        register_graphql_field('RootQuery', 'module', [
            'type' => 'AsapModule',
            'description' => 'Get a single module by ID',
            'args' => [
                'id' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The module ID',
                ]
            ],
            'resolve' => function($root, $args) {
                return $this->table_manager->get_module($args['id']);
            }
        ]);
        
        // Get layout templates (from existing CPT)
        register_graphql_field('RootQuery', 'layoutTemplates', [
            'type' => ['list_of' => 'AsapLayoutTemplate'],
            'description' => 'Get available layout templates',
            'resolve' => function($root, $args) {
                // Query existing CPT layout templates
                $templates = get_posts([
                    'post_type' => 'asap_layout_template',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'meta_query' => [
                        [
                            'key' => 'is_active',
                            'value' => '1',
                            'compare' => '='
                        ]
                    ]
                ]);
                
                $result = [];
                foreach ($templates as $template) {
                    $result[] = [
                        'id' => $template->ID,
                        'slug' => $template->post_name,
                        'title' => $template->post_title,
                        'description' => $template->post_content,
                        'gridstackConfig' => get_post_meta($template->ID, 'gridstack_config', true),
                        'defaultPlacements' => get_post_meta($template->ID, 'default_placements', true),
                        'maxModules' => (int) get_post_meta($template->ID, 'max_modules', true),
                        'isActive' => get_post_meta($template->ID, 'is_active', true) === '1'
                    ];
                }
                
                return $result;
            }
        ]);
    }
    
    /**
     * Register custom GraphQL mutations
     */
    public function register_mutations() {
        
        // Create digest mutation
        register_graphql_field('RootMutation', 'createDigest', [
            'type' => 'AsapDigest',
            'description' => 'Create a new digest',
            'args' => [
                'input' => [
                    'type' => ['non_null' => 'CreateDigestInput'],
                    'description' => 'The digest data',
                ]
            ],
            'resolve' => function($root, $args) {
                $input = $args['input'];
                
                // Set default user ID if not provided
                if (!isset($input['userId'])) {
                    $input['userId'] = get_current_user_id();
                }
                
                // Set default status if not provided
                if (!isset($input['status'])) {
                    $input['status'] = 'draft';
                }
                
                $digest_id = $this->table_manager->create_digest($input);
                
                if ($digest_id) {
                    return $this->table_manager->get_digest($digest_id);
                }
                
                throw new \GraphQL\Error\UserError('Failed to create digest');
            }
        ]);
        
        // Create module mutation
        register_graphql_field('RootMutation', 'createModule', [
            'type' => 'AsapModule',
            'description' => 'Create a new module',
            'args' => [
                'input' => [
                    'type' => ['non_null' => 'CreateModuleInput'],
                    'description' => 'The module data',
                ]
            ],
            'resolve' => function($root, $args) {
                $input = $args['input'];
                
                $module_id = $this->table_manager->create_module($input);
                
                if ($module_id) {
                    return $this->table_manager->get_module($module_id);
                }
                
                throw new \GraphQL\Error\UserError('Failed to create module');
            }
        ]);
        
        // Add module to digest mutation
        register_graphql_field('RootMutation', 'addModuleToDigest', [
            'type' => 'AsapDigestModulePlacement',
            'description' => 'Add a module to a digest',
            'args' => [
                'input' => [
                    'type' => ['non_null' => 'AddModuleToDigestInput'],
                    'description' => 'The placement data',
                ]
            ],
            'resolve' => function($root, $args) {
                $input = $args['input'];
                
                $placement_id = $this->table_manager->add_module_to_digest(
                    $input['digestId'],
                    $input['moduleId'],
                    [
                        'grid_x' => $input['gridX'] ?? 0,
                        'grid_y' => $input['gridY'] ?? 0,
                        'grid_width' => $input['gridWidth'] ?? 1,
                        'grid_height' => $input['gridHeight'] ?? 1,
                        'sort_order' => $input['sortOrder'] ?? 0
                    ]
                );
                
                if ($placement_id) {
                    return $this->table_manager->get_module_placement($placement_id);
                }
                
                throw new \GraphQL\Error\UserError('Failed to add module to digest');
            }
        ]);
        
        // Update digest status mutation
        register_graphql_field('RootMutation', 'updateDigestStatus', [
            'type' => 'AsapDigest',
            'description' => 'Update digest status',
            'args' => [
                'id' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The digest ID',
                ],
                'status' => [
                    'type' => ['non_null' => 'String'],
                    'description' => 'The new status',
                ]
            ],
            'resolve' => function($root, $args) {
                $success = $this->table_manager->update_digest_status($args['id'], $args['status']);
                
                if ($success) {
                    return $this->table_manager->get_digest($args['id']);
                }
                
                throw new \GraphQL\Error\UserError('Failed to update digest status');
            }
        ]);
        
        // Remove module from digest mutation
        register_graphql_field('RootMutation', 'removeModuleFromDigest', [
            'type' => 'Boolean',
            'description' => 'Remove a module from a digest',
            'args' => [
                'digestId' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The digest ID',
                ],
                'moduleId' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The module ID',
                ]
            ],
            'resolve' => function($root, $args) {
                return $this->table_manager->remove_module_from_digest($args['digestId'], $args['moduleId']);
            }
        ]);
        
        // Save digest layout mutation
        register_graphql_field('RootMutation', 'saveDigestLayout', [
            'type' => 'Boolean',
            'description' => 'Save digest layout with module positions',
            'args' => [
                'digestId' => [
                    'type' => ['non_null' => 'ID'],
                    'description' => 'The digest ID',
                ],
                'layout' => [
                    'type' => ['non_null' => 'String'],
                    'description' => 'Layout data as JSON string',
                ]
            ],
            'resolve' => function($root, $args) {
                $layout_data = json_decode($args['layout'], true);
                
                if (!$layout_data) {
                    throw new \GraphQL\Error\UserError('Invalid layout data');
                }
                
                return $this->table_manager->save_digest_layout($args['digestId'], $layout_data);
            }
        ]);
    }
} 