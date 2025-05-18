<?php
/**
 * Model Verification AJAX Handler
 *
 * @package ASAPDigest_Core
 * @subpackage AJAX
 * @since 3.1.0
 * @created 07.22.25 | 09:45 PM PDT
 */

namespace ASAPDigest\Core\AJAX\Admin;

use ASAPDigest\Core\ErrorLogger;
use AsapDigest\Core\Ajax\Base_AJAX;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Model_Verification_AJAX
 * Handles AJAX requests related to model verification
 */
class Model_Verification_AJAX extends Base_AJAX {

    /**
     * Register AJAX actions
     *
     * @return void
     */
    protected function register_actions() {
        add_action('wp_ajax_asap_update_hf_model_verification', array($this, 'handle_update_verification_status'));
        add_action('wp_ajax_asap_remove_failed_models', array($this, 'handle_remove_failed_models'));
        add_action('wp_ajax_asap_get_model_status', array($this, 'handle_get_model_status'));
    }

    /**
     * Handle updating model verification status
     *
     * @return void
     */
    public function handle_update_verification_status() {
        // Verify request
        $this->verify_request();
        
        // Validate required parameters
        $this->validate_params(['model_id', 'is_verified']);
        
        // Get parameters
        $model_id = sanitize_text_field($_POST['model_id'] ?? '');
        $is_verified = (bool)($_POST['is_verified'] ?? false);
        
        // Get current model lists
        $verified_models = get_option('asap_ai_verified_huggingface_models', array());
        $failed_models = get_option('asap_ai_failed_huggingface_models', array());
        
        // Log the action
        ErrorLogger::log('ajax', 'model_verification', 'Updating model verification status', [
            'model_id' => $model_id,
            'is_verified' => $is_verified,
        ], 'info');
        
        if ($is_verified) {
            // Add to verified list if not already there
            if (!in_array($model_id, $verified_models)) {
                $verified_models[] = $model_id;
                update_option('asap_ai_verified_huggingface_models', $verified_models);
            }
            
            // Remove from failed list if present
            if (in_array($model_id, $failed_models)) {
                $failed_models = array_diff($failed_models, array($model_id));
                update_option('asap_ai_failed_huggingface_models', $failed_models);
            }
            
            // Send success response
            $this->send_success([
                'message' => __('Model marked as verified.', 'asapdigest-core'),
                'model_id' => $model_id,
                'is_verified' => true
            ]);
        } else {
            // Add to failed list if not already there
            if (!in_array($model_id, $failed_models)) {
                $failed_models[] = $model_id;
                update_option('asap_ai_failed_huggingface_models', $failed_models);
            }
            
            // Remove from verified list if present
            if (in_array($model_id, $verified_models)) {
                $verified_models = array_diff($verified_models, array($model_id));
                update_option('asap_ai_verified_huggingface_models', $verified_models);
            }
            
            // Send success response
            $this->send_success([
                'message' => __('Model marked as failed.', 'asapdigest-core'),
                'model_id' => $model_id,
                'is_verified' => false
            ]);
        }
    }

    /**
     * Handle removing all failed models
     *
     * @return void
     */
    public function handle_remove_failed_models() {
        // Verify request
        $this->verify_request();
        
        // Get failed models
        $failed_models = get_option('asap_ai_failed_huggingface_models', array());
        
        if (empty($failed_models)) {
            $this->send_success([
                'message' => __('No failed models to remove.', 'asapdigest-core'),
                'removed_count' => 0
            ]);
            return;
        }
        
        // Get custom models
        $custom_models = get_option('asap_ai_custom_huggingface_models', array());
        
        // Remove failed models from custom models
        $removed_count = 0;
        foreach ($failed_models as $model_id) {
            if (isset($custom_models[$model_id])) {
                unset($custom_models[$model_id]);
                $removed_count++;
            }
        }
        
        // Update custom models
        update_option('asap_ai_custom_huggingface_models', $custom_models);
        
        // Clear failed models list
        update_option('asap_ai_failed_huggingface_models', array());
        
        // Log the action
        ErrorLogger::log('ajax', 'model_verification', 'Removed failed models', [
            'removed_count' => $removed_count,
            'model_ids' => $failed_models,
        ], 'info');
        
        // Send success response
        $this->send_success([
            'message' => sprintf(
                _n(
                    '%d failed model removed successfully.',
                    '%d failed models removed successfully.',
                    $removed_count,
                    'asapdigest-core'
                ),
                $removed_count
            ),
            'removed_count' => $removed_count,
            'removed_models' => $failed_models
        ]);
    }

    /**
     * Handle getting model verification status
     *
     * @return void
     */
    public function handle_get_model_status() {
        // Verify request
        $this->verify_request();
        
        // Validate required parameters
        $this->validate_params(['model_id']);
        
        // Get parameters
        $model_id = sanitize_text_field($_POST['model_id'] ?? '');
        
        // Get current model lists
        $verified_models = get_option('asap_ai_verified_huggingface_models', array());
        $failed_models = get_option('asap_ai_failed_huggingface_models', array());
        
        // Determine status
        $is_verified = in_array($model_id, $verified_models);
        $is_failed = in_array($model_id, $failed_models);
        
        $status = 'unverified';
        if ($is_verified) {
            $status = 'verified';
        } elseif ($is_failed) {
            $status = 'failed';
        }
        
        // Send response
        $this->send_success([
            'model_id' => $model_id,
            'is_verified' => $is_verified,
            'is_failed' => $is_failed,
            'status' => $status
        ]);
    }
}

// Initialize the handler
new Model_Verification_AJAX(); 