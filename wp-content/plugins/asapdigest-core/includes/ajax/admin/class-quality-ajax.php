<?php
/**
 * ASAP Digest Quality AJAX Handler
 *
 * Standardized handler for content quality-related AJAX operations
 *
 * @package ASAPDigest_Core
 * @since 3.0.0
 */

namespace AsapDigest\Core\Ajax\Admin;

use AsapDigest\Core\Ajax\Base_AJAX;
use AsapDigest\Core\ErrorLogger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Quality AJAX Handler Class
 *
 * Handles all AJAX requests related to content quality management
 *
 * @since 3.0.0
 */
class Quality_Ajax extends Base_AJAX {
    
    /**
     * Required capability for this handler
     *
     * @var string
     */
    protected $capability = 'manage_options';
    
    /**
     * Nonce action for this handler
     *
     * @var string
     */
    protected $nonce_action = 'asap_admin_nonce';
    
    /**
     * Register AJAX actions
     *
     * @since 3.0.0
     * @return void
     */
    protected function register_actions() {
        add_action('wp_ajax_asap_get_quality_settings', [$this, 'handle_get_quality_settings']);
        add_action('wp_ajax_asap_save_quality_settings', [$this, 'handle_save_quality_settings']);
    }
    
    /**
     * Handle getting quality settings
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_get_quality_settings() {
        // Verify request
        $this->verify_request();
        
        try {
            // Get quality settings from options
            $settings = get_option('asap_content_quality_settings', [
                'min_quality_score' => defined('ASAP_QUALITY_SCORE_MINIMUM') ? ASAP_QUALITY_SCORE_MINIMUM : 50,
                'auto_approve_threshold' => defined('ASAP_QUALITY_SCORE_EXCELLENT') ? ASAP_QUALITY_SCORE_EXCELLENT : 90,
                'auto_reject_threshold' => defined('ASAP_QUALITY_SCORE_AUTO_REJECT') ? ASAP_QUALITY_SCORE_AUTO_REJECT : 25,
                'rules' => [
                    'completeness' => [
                        'weight' => 0.3,
                        'title_min_length' => 10,
                        'content_min_length' => 100,
                        'requires_image' => false
                    ],
                    'readability' => [
                        'weight' => 0.2,
                        'min_score' => 60
                    ],
                    'relevance' => [
                        'weight' => 0.3,
                        'keyword_match' => true
                    ],
                    'freshness' => [
                        'weight' => 0.1,
                        'max_age_days' => 30
                    ],
                    'enrichment' => [
                        'weight' => 0.1,
                        'require_metadata' => false
                    ]
                ]
            ]);
            
            $this->send_success(['settings' => $settings]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'get_quality_settings_error', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while retrieving quality settings.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle saving quality settings
     *
     * @since 3.0.0
     * @return void
     */
    public function handle_save_quality_settings() {
        // Verify request
        $this->verify_request();
        
        // Validate parameters
        $this->validate_params(['settings']);
        
        $settings = $_POST['settings'];
        
        try {
            // Validate and sanitize
            $sanitized_settings = [];
            
            // Validate thresholds
            if (isset($settings['min_quality_score'])) {
                $sanitized_settings['min_quality_score'] = max(0, min(100, intval($settings['min_quality_score'])));
            }
            
            if (isset($settings['auto_approve_threshold'])) {
                $sanitized_settings['auto_approve_threshold'] = max(0, min(100, intval($settings['auto_approve_threshold'])));
            }
            
            if (isset($settings['auto_reject_threshold'])) {
                $sanitized_settings['auto_reject_threshold'] = max(0, min(100, intval($settings['auto_reject_threshold'])));
            }
            
            // Validate rules
            if (isset($settings['rules']) && is_array($settings['rules'])) {
                $sanitized_settings['rules'] = [];
                $total_weight = 0;
                
                foreach ($settings['rules'] as $rule => $config) {
                    $sanitized_rule = sanitize_key($rule);
                    $sanitized_settings['rules'][$sanitized_rule] = [];
                    
                    if (isset($config['weight'])) {
                        $weight = floatval($config['weight']);
                        $sanitized_settings['rules'][$sanitized_rule]['weight'] = $weight;
                        $total_weight += $weight;
                    }
                    
                    // Sanitize other rule config values
                    foreach ($config as $key => $value) {
                        if ($key === 'weight') {
                            continue; // Already handled
                        }
                        
                        $sanitized_key = sanitize_key($key);
                        
                        if (is_bool($value)) {
                            $sanitized_settings['rules'][$sanitized_rule][$sanitized_key] = $value;
                        } elseif (is_numeric($value)) {
                            $sanitized_settings['rules'][$sanitized_rule][$sanitized_key] = floatval($value);
                        } else {
                            $sanitized_settings['rules'][$sanitized_rule][$sanitized_key] = sanitize_text_field($value);
                        }
                    }
                }
                
                // Normalize weights if they don't sum to 1.0
                if ($total_weight > 0 && abs($total_weight - 1.0) > 0.01) {
                    foreach ($sanitized_settings['rules'] as $rule => $config) {
                        if (isset($config['weight'])) {
                            $sanitized_settings['rules'][$rule]['weight'] = floatval($config['weight']) / $total_weight;
                        }
                    }
                }
            }
            
            // Log the settings changes
            ErrorLogger::log('ajax', 'save_quality_settings', 'Quality settings updated', [
                'old_settings' => get_option('asap_content_quality_settings', []),
                'new_settings' => $sanitized_settings
            ], 'info');
            
            // Save settings
            update_option('asap_content_quality_settings', $sanitized_settings);
            
            $this->send_success([
                'message' => __('Quality settings updated successfully', 'asapdigest-core'),
                'settings' => $sanitized_settings,
            ]);
        } catch (\Exception $e) {
            ErrorLogger::log('ajax', 'save_quality_settings_error', $e->getMessage(), [
                'settings' => $settings,
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            $this->send_error([
                'message' => __('An error occurred while saving quality settings.', 'asapdigest-core'),
                'code' => 'processing_error',
                'details' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get the default quality settings
     *
     * @since 3.0.0
     * @return array
     */
    private function get_default_quality_settings() {
        return [
            'min_quality_score' => defined('ASAP_QUALITY_SCORE_MINIMUM') ? ASAP_QUALITY_SCORE_MINIMUM : 50,
            'auto_approve_threshold' => defined('ASAP_QUALITY_SCORE_EXCELLENT') ? ASAP_QUALITY_SCORE_EXCELLENT : 90,
            'auto_reject_threshold' => defined('ASAP_QUALITY_SCORE_AUTO_REJECT') ? ASAP_QUALITY_SCORE_AUTO_REJECT : 25,
            'rules' => [
                'completeness' => [
                    'weight' => 0.3,
                    'title_min_length' => 10,
                    'content_min_length' => 100,
                    'requires_image' => false
                ],
                'readability' => [
                    'weight' => 0.2,
                    'min_score' => 60
                ],
                'relevance' => [
                    'weight' => 0.3,
                    'keyword_match' => true
                ],
                'freshness' => [
                    'weight' => 0.1,
                    'max_age_days' => 30
                ],
                'enrichment' => [
                    'weight' => 0.1,
                    'require_metadata' => false
                ]
            ]
        ];
    }
} 