<?php
/**
 * ASAP Digest Admin
 *
 * @package    ASAPDigest_Core
 * @subpackage Admin
 * @since      2.3.0
 * @file-marker ASAP_Digest_Admin
 */

namespace ASAPDigest\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class. 
 * 
 * Manages admin functionality for the ASAP Digest plugin.
 * Acts as a bridge between the core plugin and the admin UI components.
 */
class ASAP_Digest_Admin {

    /**
     * Instance of this class.
     *
     * @since  2.3.0
     * @access protected
     * @var    ASAP_Digest_Admin
     */
    protected static $instance = null;

    /**
     * Initialize the class and set its properties.
     *
     * @since 2.3.0
     */
    public function __construct() {
        // Add hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize other admin components as needed
        $this->init_components();
    }

    /**
     * Initialize admin components.
     *
     * @since 2.3.0
     * @return void
     */
    private function init_components() {
        // No initialization required yet
        // This is where we would initialize other admin components
    }

    /**
     * Return an instance of this class.
     *
     * @since  2.3.0
     * @return ASAP_Digest_Admin A single instance of this class.
     */
    public static function get_instance() {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Add admin menu items.
     * 
     * Note: This is a placeholder. The actual menu registration is handled by
     * the Central Command class in class-central-command.php
     *
     * @since 2.3.0
     * @return void
     */
    public function add_admin_menu() {
        // Menu registration is handled by Central Command
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 2.3.0
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueue_admin_scripts($hook) {
        // Enqueue admin scripts and styles
        if (strpos($hook, 'asap-digest') !== false) {
            wp_enqueue_style('asap-admin-css', plugin_dir_url(__FILE__) . 'css/admin.css', array(), '2.3.0');
            wp_enqueue_script('asap-admin-js', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), '2.3.0', true);
        }
    }
}

// Initialize the admin class
ASAP_Digest_Admin::get_instance(); 