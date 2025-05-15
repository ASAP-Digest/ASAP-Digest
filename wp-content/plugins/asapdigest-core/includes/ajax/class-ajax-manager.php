<?php
/**
 * ASAP Digest Core AJAX Manager
 *
 * Central manager for registering all AJAX handlers
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Manager Class
 *
 * Centralizes registration of all AJAX handlers for the plugin
 *
 * @since 3.0.0
 */
class AJAX_Manager {
    
    /**
     * Plugin core instance
     *
     * @var object
     */
    private $core;
    
    /**
     * Handler classes
     *
     * @var array
     */
    private $handlers = [];
    
    /**
     * Initialize the class and set up hooks
     *
     * @since 3.0.0
     * @param object $core Main plugin instance
     * @return void
     */
    public function __construct($core = null) {
        $this->core = $core;
        
        // Register initialization hook
        add_action('init', [$this, 'register_handlers']);
    }
    
    /**
     * Load all AJAX handler classes
     *
     * @since 3.0.0
     * @return void
     */
    private function load_handlers() {
        // Load Admin handlers
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-admin-ajax.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-content-ajax.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-source-ajax.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-quality-ajax.php';
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/admin/class-ai-ajax.php';
        
        // Load User handlers
        require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/user/class-user-actions-ajax.php';
        
        // Load Public handlers (if any)
        // require_once ASAP_DIGEST_PLUGIN_DIR . 'includes/ajax/public/class-public-ajax.php';
    }
    
    /**
     * Register all AJAX handlers
     *
     * @since 3.0.0
     * @return void
     */
    public function register_handlers() {
        // Load handler classes
        $this->load_handlers();
        
        // Initialize Admin handlers
        $this->handlers['admin'] = new Admin\Admin_Ajax($this->core);
        $this->handlers['content'] = new Admin\Content_Ajax();
        $this->handlers['source'] = new Admin\Source_Ajax();
        $this->handlers['quality'] = new Admin\Quality_Ajax();
        $this->handlers['ai'] = new Admin\AI_Ajax();
        
        // Initialize User handlers
        $this->handlers['user_actions'] = new User\User_Actions_Ajax();
        
        // Initialize Public handlers
        // $this->handlers['public'] = new Public\Public_Ajax();
        
        // Allow other plugins/modules to register their handlers
        $this->handlers = apply_filters('asap_digest_ajax_handlers', $this->handlers, $this->core);
    }

    /**
     * Register a handler class
     *
     * @since 3.0.0
     * @param Base_AJAX $handler Handler instance to register
     * @return void
     */
    public function register_handler($handler) {
        if ($handler instanceof Base_AJAX) {
            $this->handlers[] = $handler;
        }
    }

    /**
     * Initialize all registered handlers
     *
     * @since 3.0.0
     * @return void
     */
    public function init() {
        foreach ($this->handlers as $handler) {
            // The handler constructor calls init() which sets up action hooks
        }
    }
} 