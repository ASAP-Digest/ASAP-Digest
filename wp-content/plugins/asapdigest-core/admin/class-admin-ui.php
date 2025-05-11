<?php
/**
 * ASAP Digest Admin UI Helper Class
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 */

namespace ASAPDigest\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin UI Helper Class
 */
class ASAP_Digest_Admin_UI {
    /**
     * Constructor
     */
    public function __construct() {
        // Do NOT register admin_menu here; menu registration is centralized (per menu registration protocol)
        // add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Register admin menu pages
     */
    // Removed per protocol: menu registration is centralized in Central_Command
    // public function register_menu_pages() {
    //     ...
    // }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook The current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (!$this->is_plugin_page($hook)) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'asap-digest-admin',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin.css',
            [],
            '1.0.0'
        );

        // Enqueue Chart.js only on analytics dashboard
        if (isset($_GET['page']) && $_GET['page'] === 'asap-digest-analytics') {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [],
                '4.4.1',
                true
            );
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'asap-digest-admin',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/admin.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize script
        wp_localize_script(
            'asap-digest-admin',
            'asapDigestAdmin',
            [
                'nonce' => wp_create_nonce('asap_digest_admin'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'i18n' => [
                    'confirmReset' => __('Are you sure you want to reset all settings?', 'asap-digest'),
                    'confirmTestDigest' => __('Send a test digest to your email?', 'asap-digest'),
                ]
            ]
        );
    }

    /**
     * Check if current page is a plugin page
     * 
     * @param string $hook The current admin page hook
     * @return bool
     */
    private function is_plugin_page($hook) {
        $plugin_pages = [
            'toplevel_page_asap-digest',
            'asap-digest_page_asap-digest-settings',
            'asap-digest_page_asap-digest-stats'
        ];

        return in_array($hook, $plugin_pages);
    }

    /**
     * Render main page
     */
    public function render_main_page() {
        require_once \ASAP_DIGEST_PLUGIN_DIR . 'admin/views/main-page.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        require_once \ASAP_DIGEST_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Render stats page
     */
    public function render_stats_page() {
        require_once \ASAP_DIGEST_PLUGIN_DIR . 'admin/views/stats-page.php';
    }

    /**
     * Create a card component with header and content
     *
     * @param string $title Card title
     * @param string $content Card content (HTML)
     * @param string $class Additional CSS class
     * @return string HTML for the card
     */
    public static function create_card($title, $content, $class = '') {
        $html = '<div class="asap-card ' . esc_attr($class) . '">';
        if ($title) {
            $html .= '<div class="asap-card-header">';
            $html .= '<h2>' . esc_html($title) . '</h2>';
            $html .= '</div>';
        }
        $html .= '<div class="asap-card-content">';
        $html .= $content;
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Create a form field with label and description
     *
     * @param string $id Field ID
     * @param string $label Field label
     * @param string $type Field type (text, number, etc)
     * @param array  $args Additional arguments
     * @return string HTML for the form field
     */
    public static function create_form_field($id, $label, $type = 'text', $args = []) {
        $defaults = [
            'value' => '',
            'class' => '',
            'description' => '',
            'placeholder' => '',
            'required' => false,
            'min' => '',
            'max' => '',
            'step' => '',
        ];

        $args = wp_parse_args($args, $defaults);
        $html = '<div class="asap-form-field">';
        
        // Label
        if ($label) {
            $html .= '<label for="' . esc_attr($id) . '">' . esc_html($label);
            if ($args['required']) {
                $html .= ' <span class="required">*</span>';
            }
            $html .= '</label>';
        }

        // Input field
        $field_attrs = [
            'type' => $type,
            'id' => $id,
            'name' => $id,
            'class' => 'regular-text ' . $args['class'],
            'value' => $args['value'],
            'placeholder' => $args['placeholder'],
        ];

        if ($args['required']) {
            $field_attrs['required'] = 'required';
        }

        if ($type === 'number') {
            if ($args['min'] !== '') {
                $field_attrs['min'] = $args['min'];
            }
            if ($args['max'] !== '') {
                $field_attrs['max'] = $args['max'];
            }
            if ($args['step'] !== '') {
                $field_attrs['step'] = $args['step'];
            }
        }

        $html .= '<input ' . self::build_attributes($field_attrs) . '>';

        // Description
        if ($args['description']) {
            $html .= '<p class="description">' . esc_html($args['description']) . '</p>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Create a select field with options
     *
     * @param string $id Field ID
     * @param string $label Field label
     * @param array  $options Array of options
     * @param array  $args Additional arguments
     * @return string HTML for the select field
     */
    public static function create_select_field($id, $label, $options, $args = []) {
        $defaults = [
            'value' => '',
            'class' => '',
            'description' => '',
            'required' => false,
        ];

        $args = wp_parse_args($args, $defaults);
        $html = '<div class="asap-form-field">';
        
        // Label
        if ($label) {
            $html .= '<label for="' . esc_attr($id) . '">' . esc_html($label);
            if ($args['required']) {
                $html .= ' <span class="required">*</span>';
            }
            $html .= '</label>';
        }

        // Select field
        $field_attrs = [
            'id' => $id,
            'name' => $id,
            'class' => 'regular-text ' . $args['class'],
        ];

        if ($args['required']) {
            $field_attrs['required'] = 'required';
        }

        $html .= '<select ' . self::build_attributes($field_attrs) . '>';
        foreach ($options as $value => $label) {
            $selected = selected($args['value'], $value, false);
            $html .= '<option value="' . esc_attr($value) . '"' . $selected . '>';
            $html .= esc_html($label);
            $html .= '</option>';
        }
        $html .= '</select>';

        // Description
        if ($args['description']) {
            $html .= '<p class="description">' . esc_html($args['description']) . '</p>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Create a status indicator component
     *
     * @param string $status_type Type of status (good, warning, error, inactive)
     * @param string $status_text Text to display with the status
     * @return string HTML for the status indicator
     */
    public static function create_status_indicator($status_type, $status_text) {
        $valid_types = ['good', 'warning', 'error', 'inactive'];
        $status_type = in_array($status_type, $valid_types) ? $status_type : 'inactive';
        
        $html = '<div class="asap-status-indicator">';
        $html .= '<span class="status-dot status-' . esc_attr($status_type) . '"></span>';
        $html .= '<span class="status-text">' . esc_html($status_text) . '</span>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Build HTML attributes string from array
     *
     * @param array $attributes Array of attribute key/value pairs
     * @return string HTML attributes string
     */
    private static function build_attributes($attributes) {
        $html = '';
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $html .= ' ' . esc_attr($key);
            } elseif ($value !== false && $value !== '') {
                $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        return $html;
    }
}

// Add default admin styles
add_action('admin_head', function() {
    ?>
    <style>
        .asap-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .asap-card-header {
            border-bottom: 1px solid #ccd0d4;
            padding: 15px 20px;
        }

        .asap-card-header h2 {
            margin: 0;
            font-size: 16px;
            line-height: 1.4;
        }

        .asap-card-content {
            padding: 20px;
        }

        .asap-form-field {
            margin-bottom: 15px;
        }

        .asap-form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .asap-form-field .description {
            color: #666;
            font-size: 13px;
            margin: 5px 0 0;
        }

        .asap-form-field .required {
            color: #d63638;
        }

        .asap-form-field input[type="text"],
        .asap-form-field input[type="number"],
        .asap-form-field select {
            width: 100%;
            max-width: 25em;
        }
    </style>
    <?php
}); 