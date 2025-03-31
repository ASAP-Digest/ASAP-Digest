<?php
/**
 * ASAP Digest Admin UI Handler
 * 
 * @package ASAPDigest_Core
 * @created 03.30.25 | 04:45 PM PDT
 */

namespace ASAPDigest\Core;

use function add_action;
use function esc_attr;
use function esc_html;
use function esc_textarea;
use function plugin_dir_url;
use function selected;
use function wp_enqueue_style;
use function wp_kses_post;
use function wp_parse_args;

// Define version constant if not already defined
if (!defined('ASAP_DIGEST_VERSION')) {
    define('ASAP_DIGEST_VERSION', '0.1.0');
}

class ASAP_Digest_Admin_UI {
    /**
     * Initialize the admin UI functionality
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_head', [$this, 'add_admin_inline_styles']);
    }

    /**
     * Enqueue admin-specific styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'asap-') === false) {
            return;
        }

        wp_enqueue_style(
            'asap-admin-styles',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/asap-admin.css',
            [],
            ASAP_DIGEST_VERSION
        );
    }

    /**
     * Add inline styles for WordPress admin customization
     */
    public function add_admin_inline_styles() {
        ?>
        <style>
            /* Custom admin menu icon styling */
            #adminmenu .toplevel_page_asap-central-command .wp-menu-image {
                background: none;
            }
            
            /* Enhance WordPress notice styling */
            .asap-central-command .notice {
                margin: 20px 0;
                border-left-width: 4px;
            }
            
            /* Status indicator animations */
            .asap-status-good,
            .asap-status-warning,
            .asap-status-error {
                transition: color 0.2s ease;
            }
            
            /* Enhanced button states */
            .asap-central-command .button {
                transition: all 0.2s ease;
            }
            
            .asap-central-command .button:hover {
                transform: translateY(-1px);
            }
            
            /* Card hover effects */
            .asap-card {
                transition: box-shadow 0.2s ease, transform 0.2s ease;
            }
            
            .asap-card:hover {
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transform: translateY(-1px);
            }
        </style>
        <?php
    }

    /**
     * Helper function to create a settings card
     */
    public static function create_card($title, $content, $classes = '') {
        $html = '<div class="asap-card ' . esc_attr($classes) . '">';
        if ($title) {
            $html .= '<h2>' . esc_html($title) . '</h2>';
        }
        $html .= $content;
        $html .= '</div>';
        return $html;
    }

    /**
     * Helper function to create a status indicator
     */
    public static function create_status_indicator($status, $text) {
        $class = 'asap-status-' . $status;
        $icon = '';
        
        switch ($status) {
            case 'good':
                $icon = '<span class="dashicons dashicons-yes-alt"></span>';
                break;
            case 'warning':
                $icon = '<span class="dashicons dashicons-warning"></span>';
                break;
            case 'error':
                $icon = '<span class="dashicons dashicons-dismiss"></span>';
                break;
            case 'inactive':
                $icon = '<span class="dashicons dashicons-marker"></span>';
                break;
        }
        
        return '<span class="' . esc_attr($class) . '">' . $icon . esc_html($text) . '</span>';
    }

    /**
     * Helper function to create a settings form field
     */
    public static function create_form_field($args) {
        $defaults = [
            'type' => 'text',
            'name' => '',
            'value' => '',
            'label' => '',
            'description' => '',
            'placeholder' => '',
            'options' => [],
            'class' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $html = '<div class="asap-form-row">';
        
        if ($args['label']) {
            $html .= '<label for="' . esc_attr($args['name']) . '">' . esc_html($args['label']) . '</label>';
        }
        
        switch ($args['type']) {
            case 'select':
                $html .= '<select name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['name']) . '" class="' . esc_attr($args['class']) . '">';
                foreach ($args['options'] as $value => $label) {
                    $html .= '<option value="' . esc_attr($value) . '" ' . selected($args['value'], $value, false) . '>' . esc_html($label) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'textarea':
                $html .= '<textarea name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['name']) . '" class="' . esc_attr($args['class']) . '" placeholder="' . esc_attr($args['placeholder']) . '">' . esc_textarea($args['value']) . '</textarea>';
                break;
                
            default:
                $html .= '<input type="' . esc_attr($args['type']) . '" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['name']) . '" value="' . esc_attr($args['value']) . '" class="' . esc_attr($args['class']) . '" placeholder="' . esc_attr($args['placeholder']) . '">';
        }
        
        if ($args['description']) {
            $html .= '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
} 