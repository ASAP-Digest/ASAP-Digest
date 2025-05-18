<?php

namespace ASAPDigest\Core;

class ASAPDigestCore {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if (defined('ASAP_DIGEST_VERSION')) {
			$this->version = ASAP_DIGEST_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'asapdigest-core';

		// Add Hugging Face model verification handler
		add_action('wp_ajax_asap_update_hf_model_verification', array($this, 'handle_update_hf_model_verification'));

		// Initialize the plugin
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// To be implemented
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		// To be implemented
	}

	/**
	 * Define the public-facing hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// To be implemented
	}

	/**
	 * Get the plugin name.
	 *
	 * @since    1.0.0
	 * @return   string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the plugin version.
	 *
	 * @since    1.0.0
	 * @return   string    The version of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		// Get admin instance - use the correct class name and namespace
		$plugin_admin = \ASAPDigest\Admin\ASAP_Digest_Admin::get_instance();

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_admin_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');

		// Add AJAX handlers
		$this->loader->add_action('wp_ajax_asap_test_ai_connection', $this, 'handle_test_ai_connection');
		$this->loader->add_action('wp_ajax_asap_save_custom_hf_models', $this, 'handle_save_custom_hf_models');
		$this->loader->add_action('wp_ajax_asap_update_hf_model_verification', $this, 'handle_update_hf_model_verification');

		// Add settings
		// Check if the method exists before adding the action
		if (method_exists($plugin_admin, 'register_settings')) {
			$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
		}
	}

	/**
	 * Handler for updating Hugging Face model verification status
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_update_hf_model_verification() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asap_ai_admin_nonce')) {
			wp_send_json_error([
				'message' => 'Invalid security token.',
				'code' => 'invalid_nonce'
			], 403);
			exit;
		}

		// Check for model_id parameter
		if (!isset($_POST['model_id']) || empty($_POST['model_id'])) {
			wp_send_json_error([
				'message' => 'Model ID is required.',
				'code' => 'missing_parameter'
			], 400);
			exit;
		}

		$model_id = sanitize_text_field($_POST['model_id']);
		$is_verified = isset($_POST['is_verified']) ? (bool) $_POST['is_verified'] : false;

		// Get current verified and failed models lists
		$verified_models = get_option('asap_ai_verified_huggingface_models', array());
		$failed_models = get_option('asap_ai_failed_huggingface_models', array());

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
			
			wp_send_json_success([
				'message' => 'Model marked as verified.',
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
			
			wp_send_json_success([
				'message' => 'Model marked as failed.',
				'model_id' => $model_id,
				'is_verified' => false
			]);
		}
	}
} 