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
        // Check if we're on any of our plugin's admin pages
        if (strpos($hook, 'asap-digest') !== false || (isset($_GET['page']) && strpos($_GET['page'], 'asap-') !== false)) {
            // Enqueue WordPress core dependencies first
            wp_enqueue_style('thickbox');
            wp_enqueue_script('thickbox');
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
            
            // Get plugin version for cache busting
            $version = defined('ASAP_DIGEST_VERSION') ? ASAP_DIGEST_VERSION : '2.3.0';
            
            // Enqueue our admin styles and scripts with proper dependencies
            wp_enqueue_style(
                'asap-admin-css',
                plugin_dir_url(__FILE__) . 'css/admin.css',
                array('thickbox', 'wp-jquery-ui-dialog'),  // Add jQuery UI dialog styles as dependency
                $version
            );
            
            wp_enqueue_script(
                'asap-admin-js',
                plugin_dir_url(__FILE__) . 'js/admin.js',
                array('jquery', 'thickbox', 'jquery-ui-dialog'),  // Add jQuery UI dialog as dependency
                $version,
                true  // Load in footer
            );
            
            // Add localized script data
            wp_localize_script('asap-admin-js', 'asap_admin_vars', array(
                'nonce' => wp_create_nonce('asap_admin_nonce'),
                'rest_nonce' => wp_create_nonce('wp_rest'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url(),
                'i18n' => array(
                    'testing' => __('Testing connection...', 'asapdigest-core'),
                    'success' => __('Connection successful!', 'asapdigest-core'),
                    'error' => __('Error: ', 'asapdigest-core')
                )
            ));
        }
    }
}

// Initialize the admin class
ASAP_Digest_Admin::get_instance(); 