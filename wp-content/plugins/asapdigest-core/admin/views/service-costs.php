<?php
/**
 * ASAP Digest Service Costs Management View
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker Service_Costs_View
 */

use ASAPDigest\Core\ASAP_Digest_Admin_UI;

if (!defined('ABSPATH')) {
    exit;
}

// Get plugin instance
$plugin = ASAPDigest\Core\ASAP_Digest_Core::get_instance();
$usage_tracker = $plugin->get_usage_tracker();

// Get all service costs
$services = [
    'openai' => $usage_tracker->get_service_cost('openai'),
    'aws' => $usage_tracker->get_service_cost('aws'),
    'stripe' => $usage_tracker->get_service_cost('stripe')
];
?>

<div class="wrap asap-central-command">
    <h1>Service Costs Management</h1>
    
    <?php
    // Display any admin notices
    settings_errors('asap_messages');
    ?>

    <div class="asap-dashboard-grid">
        <!-- Service Cost Configuration -->
        <?php
        foreach ($services as $service_name => $cost_data) {
            $form_fields = [
                [
                    'type' => 'text',
                    'name' => 'cost_per_unit',
                    'label' => 'Cost Per Unit ($)',
                    'value' => $cost_data ? $cost_data->cost_per_unit : '0.001',
                    'class' => 'regular-text',
                    'description' => 'Base cost per unit of service usage'
                ],
                [
                    'type' => 'text',
                    'name' => 'markup_percentage',
                    'label' => 'Markup Percentage (%)',
                    'value' => $cost_data ? $cost_data->markup_percentage : '0',
                    'class' => 'regular-text',
                    'description' => 'Additional markup percentage to apply'
                ]
            ];

            $form_content = '<form method="post" action="">';
            $form_content .= wp_nonce_field('asap_central_command', '_wpnonce', true, false);
            $form_content .= '<input type="hidden" name="asap_action" value="update_service_cost">';
            $form_content .= '<input type="hidden" name="service_name" value="' . esc_attr($service_name) . '">';

            foreach ($form_fields as $field) {
                $form_content .= ASAP_Digest_Admin_UI::create_form_field($field);
            }

            $form_content .= '<p class="submit">';
            $form_content .= '<button type="submit" class="button button-primary">Update Cost</button>';
            $form_content .= '</p>';
            $form_content .= '</form>';

            echo ASAP_Digest_Admin_UI::create_card(
                ucfirst($service_name) . ' Cost Configuration',
                $form_content,
                'asap-card-service-cost'
            );
        }
        ?>
    </div>
</div>

<style>
    .asap-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .asap-card-service-cost {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    .asap-form-row {
        margin-bottom: 15px;
    }

    .asap-form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .asap-form-row .description {
        color: #666;
        font-style: italic;
        margin-top: 5px;
    }

    .asap-card-service-cost .button-primary {
        margin-top: 10px;
    }
</style> 