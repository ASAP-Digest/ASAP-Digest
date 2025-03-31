<?php
/**
 * ASAP Digest Central Command
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Central_Command
 */

namespace ASAPDigest\Core;

use function add_menu_page;
use function add_submenu_page;
use function plugin_dir_path;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Central_Command {
    /**
     * @var ASAP_Digest_Core Plugin instance
     */
    private $plugin;

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin = ASAP_Digest_Core::get_instance();
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * Register admin menu items
     */
    public function register_menu() {
        // Add main menu
        add_menu_page(
            '⚡️ Central Command',
            '⚡️ Central Command',
            'manage_options',
            'asap-central-command',
            [$this, 'render_dashboard'],
            'dashicons-superhero',
            3
        );

        // Add submenus
        add_submenu_page(
            'asap-central-command',
            'Usage Analytics',
            'Usage Analytics',
            'manage_options',
            'asap-usage-analytics',
            [$this, 'render_usage_analytics']
        );

        add_submenu_page(
            'asap-central-command',
            'Service Costs',
            'Service Costs',
            'manage_options',
            'asap-service-costs',
            [$this, 'render_service_costs']
        );
    }

    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        require_once plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }

    /**
     * Render usage analytics page
     */
    public function render_usage_analytics() {
        require_once plugin_dir_path(__FILE__) . 'views/usage-analytics.php';
    }

    /**
     * Render service costs page
     */
    public function render_service_costs() {
        require_once plugin_dir_path(__FILE__) . 'views/service-costs.php';
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submission() {
        if (!isset($_POST['asap_action'])) {
            return;
        }

        if (!check_admin_referer('asap_central_command')) {
            wp_die(__('Invalid nonce specified', 'asap-digest'));
        }

        $action = sanitize_text_field($_POST['asap_action']);

        switch ($action) {
            case 'update_service_cost':
                $this->handle_service_cost_update();
                break;
            // Add more action handlers as needed
        }
    }

    /**
     * Handle service cost update
     */
    private function handle_service_cost_update() {
        if (!isset($_POST['service_name'], $_POST['cost_per_unit'], $_POST['markup_percentage'])) {
            return;
        }

        $service_name = sanitize_text_field($_POST['service_name']);
        $cost_per_unit = floatval($_POST['cost_per_unit']);
        $markup_percentage = floatval($_POST['markup_percentage']);

        $this->plugin->get_usage_tracker()->update_service_cost(
            $service_name,
            $cost_per_unit,
            $markup_percentage
        );

        add_settings_error(
            'asap_messages',
            'service_cost_updated',
            __('Service cost updated successfully.', 'asap-digest'),
            'success'
        );
    }
} 