<?php
/**
 * Controller for SvelteKit Auto-Login Token Exchange (V4)
 *
 * Handles generation and validation of single-use tokens for server-to-server
 * verification between WordPress and the SvelteKit application.
 *
 * @package    ASAPDigest_Core
 * @subpackage API
 * @since      1.0.0
 * @version    1.0
 * @created    07.27.24 | 03:30 PM PDT 
 * @file-marker SK_Token_Controller
 */

namespace ASAPDigest\Core\API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Manages the REST API endpoints for SK token exchange.
 */
class SK_Token_Controller {

	/**
	 * Namespace for the REST API routes.
	 *
	 * @var string
	 */
	private $namespace = 'asap/v1';

	/**
	 * Registers the REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/generate-sk-token',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_generate_token'],
				'permission_callback' => [$this, 'generate_token_permissions_check'],
			]
		);

		register_rest_route(
			$this->namespace,
			'/validate-sk-token',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_validate_token'],
				'permission_callback' => [$this, 'validate_token_permissions_check'],
			]
		);
	}

	/**
	 * Permission check for the token generation endpoint.
	 * Only logged-in users can generate a token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permission is granted, WP_Error otherwise.
	 */
	public function generate_token_permissions_check(WP_REST_Request $request) {
		if (!is_user_logged_in()) {
			// Option 1: Redirect to login (might be complex with REST)
			// wp_safe_redirect(wp_login_url($request->get_route())); exit;
			// Option 2: Return an error
			return new WP_Error('rest_not_logged_in', __('You are not currently logged in.', 'asapdigest-core'), ['status' => 401]);
		}
		return true;
	}

	/**
	 * Permission check for the token validation endpoint.
	 * Requires a valid shared secret header.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permission is granted, WP_Error otherwise.
	 */
	public function validate_token_permissions_check(WP_REST_Request $request) {
		$shared_secret = defined('ASAP_SK_SYNC_SECRET') ? ASAP_SK_SYNC_SECRET : '';
		$received_secret = $request->get_header('X-ASAP-Sync-Secret');

		if (empty($shared_secret) || empty($received_secret) || !hash_equals($shared_secret, $received_secret)) {
			error_log('[ASAP SK Token] Invalid or missing shared secret for validation.');
			return new WP_Error('rest_forbidden_context', __('Invalid shared secret.', 'asapdigest-core'), ['status' => 403]);
		}
		return true;
	}

	/**
	 * Handles the GET request to generate a sync token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error A response object or WP_Error on failure.
	 */
	public function handle_generate_token(WP_REST_Request $request) {
		$user_id = get_current_user_id();
		if (!$user_id) {
			// This should be caught by permission_callback, but double-check
			return new WP_Error('rest_internal_error', __('Could not get current user ID.', 'asapdigest-core'), ['status' => 500]);
		}

		// 1. Generate Token
		$token = wp_generate_password(64, false);
		// Hash the token before storing for better security
		$hashed_token = wp_hash_password($token);
		$expiry = time() + 120; // 2-minute expiry

		// 2. Store Token (Using User Meta for simplicity)
		// Store the HASHED token and expiry time
		$stored = update_user_meta($user_id, '_sk_sync_token_v4', ['token_hash' => $hashed_token, 'expires' => $expiry]);

		if (!$stored) {
			error_log("[ASAP SK Token] Failed to store token meta for user ID: $user_id");
			return new WP_Error('rest_internal_error', __('Failed to store sync token.', 'asapdigest-core'), ['status' => 500]);
		}

		error_log("[ASAP SK Token] Generated and stored token meta for user ID: $user_id");

		// 3. Construct SK URL
		$sk_verify_url_base = (wp_get_environment_type() === 'production')
			? 'https://app.asapdigest.com/api/auth/verify-wp-token' // Production URL
			: 'https://localhost:5173/api/auth/verify-wp-token'; // Development URL

		// Pass the PLAINTEXT token in the URL
		$redirect_url = add_query_arg('token', $token, $sk_verify_url_base);

		// 4. Redirect
		wp_safe_redirect($redirect_url);
		exit;
	}

	/**
	 * Handles the POST request to validate a sync token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error A response object or WP_Error on failure.
	 */
	public function handle_validate_token(WP_REST_Request $request) {
		$params = $request->get_json_params();
		$token = isset($params['token']) ? sanitize_text_field($params['token']) : '';

		if (empty($token)) {
			return new WP_Error('rest_bad_request', __('Missing token.', 'asapdigest-core'), ['status' => 400]);
		}

		error_log("[ASAP SK Token] Received validation request for token.");

		// --- Find User by Token ---
		// This is inefficient. We need to query users based on meta value.
		// A custom table would be better, but let's try user meta query first.
		$args = [
			'meta_key'     => '_sk_sync_token_v4',
			'meta_compare' => 'EXISTS', // Find users who have the meta key
			'fields'       => 'ID', // Only need user IDs
		];
		$user_query = new \WP_User_Query($args);
		$users = $user_query->get_results();

		$valid_user_id = null;
		$user_data_to_return = null;

		if (!empty($users)) {
			foreach ($users as $user_id) {
				$meta_value = get_user_meta($user_id, '_sk_sync_token_v4', true);

				if (is_array($meta_value) && isset($meta_value['token_hash']) && isset($meta_value['expires'])) {
					// Verify the hash of the received token against the stored hash
					if (wp_check_password($token, $meta_value['token_hash'])) {
						// Token matches, now check expiry
						if (time() < $meta_value['expires']) {
							// Valid token found! Delete it and store user ID
							$deleted = delete_user_meta($user_id, '_sk_sync_token_v4');
							if ($deleted) {
								error_log("[ASAP SK Token] Valid token found and deleted for user ID: $user_id");
								$valid_user_id = $user_id;
							} else {
								error_log("[ASAP SK Token] Valid token found for user ID $user_id, but FAILED TO DELETE meta.");
								// Treat as invalid if we can't delete it (prevents replay)
							}
							break; // Stop checking once a valid token is found and processed
						} else {
							error_log("[ASAP SK Token] Token found for user ID $user_id, but expired. Deleting.");
							delete_user_meta($user_id, '_sk_sync_token_v4'); // Clean up expired token
						}
					}
					// If hash doesn't match, continue checking other users
				}
			}
		}

		if ($valid_user_id) {
			$user_info = get_userdata($valid_user_id);
			if ($user_info) {
				$user_data_to_return = [
					'wpUserId' => $user_info->ID,
					'email'    => $user_info->user_email,
					'username' => $user_info->user_login,
					'name'     => $user_info->display_name,
				];
				return new WP_REST_Response(['success' => true, 'user' => $user_data_to_return], 200);
			} else {
				error_log("[ASAP SK Token] Could not retrieve userdata for valid user ID: $valid_user_id");
				return new WP_Error('rest_internal_error', __('Could not retrieve user data.', 'asapdigest-core'), ['status' => 500]);
			}
		} else {
			error_log("[ASAP SK Token] Token validation failed (not found, expired, hash mismatch, or delete failed).");
			return new WP_Error('rest_invalid_token', __('Invalid or expired token.', 'asapdigest-core'), ['status' => 401]);
		}
	}
} 