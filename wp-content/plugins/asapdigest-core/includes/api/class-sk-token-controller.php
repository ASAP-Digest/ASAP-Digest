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
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function add_query_arg;
use function constant;
use function defined;
use function delete_user_meta;
use function get_current_user_id;
use function get_user_meta;
use function get_userdata;
use function is_user_logged_in;
use function update_user_meta;
use function wp_generate_password;
use function wp_safe_redirect;
use function wp_validate_auth_cookie;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Manages the REST API endpoints for SK session validation (V5).
 * V4 Token Exchange code has been removed.
 */
class SK_Token_Controller extends WP_REST_Controller {

	/**
	 * Namespace for the REST API routes.
	 *
	 * @var string
	 */
	protected $namespace = 'asap/v1';

	/**
	 * Registers the REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/validate-session-get-user',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'validate_session_and_get_user'],
				'permission_callback' => [$this, 'validate_session_permissions_check'],
			]
		);
	}

	/**
	 * Permission check for validating a session via cookie. Requires shared secret. (V5)
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|WP_Error
	 */
	public function validate_session_permissions_check(WP_REST_Request $request) {
		$secret_header = $request->get_header('X-ASAP-Sync-Secret');
		$defined_secret = defined('ASAP_SK_SYNC_SECRET') ? constant('ASAP_SK_SYNC_SECRET') : null;

		if (empty($defined_secret) || empty($secret_header) || !hash_equals($defined_secret, $secret_header)) {
			error_log('[ASAP Digest Core] V5 Session Validation: Invalid or missing secret header.');
			return new WP_Error('rest_forbidden_secret', 'Invalid secret.', ['status' => 403]);
		}
		return true;
	}

	/**
	 * Validates the WP auth cookie provided in the header and returns user data if valid. (V5)
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error on failure.
	 */
	public function validate_session_and_get_user(WP_REST_Request $request) {
		// Get the full cookie header sent by SvelteKit backend
		$cookie_header = $request->get_header('Cookie');

		if (empty($cookie_header)) {
			error_log('[ASAP Digest Core] V5 Session Validation: Missing Cookie header.');
			return new WP_Error('rest_missing_cookie_header', 'Cookie header is missing.', ['status' => 400]);
		}

		// Parse header to find the relevant WP auth cookie.
		$wp_auth_cookie = '';
		$cookies = explode(';', $cookie_header);
		foreach ($cookies as $cookie) {
			if (strpos(trim($cookie), LOGGED_IN_COOKIE) === 0) {
				$parts = explode('=', $cookie, 2);
				if (count($parts) == 2) {
					$wp_auth_cookie = trim($parts[1]);
					break;
				}
			} elseif (defined('SECURE_AUTH_COOKIE') && strpos(trim($cookie), SECURE_AUTH_COOKIE) === 0) {
				$parts = explode('=', $cookie, 2);
				if (count($parts) == 2) {
					$wp_auth_cookie = trim($parts[1]);
					break;
	}
			} elseif (defined('AUTH_COOKIE') && strpos(trim($cookie), AUTH_COOKIE) === 0) {
				$parts = explode('=', $cookie, 2);
				if (count($parts) == 2) {
					$wp_auth_cookie = trim($parts[1]);
					break;
				}
			}
		}

		if (empty($wp_auth_cookie)) {
			error_log('[ASAP Digest Core] V5 Session Validation: WP auth cookie not found in header: ' . $cookie_header);
			return new WP_Error('rest_wp_cookie_not_found', 'WordPress auth cookie not found in provided header.', ['status' => 401]);
		}

		$user_id = wp_validate_auth_cookie($wp_auth_cookie, 'logged_in');

		if (!$user_id) {
			error_log('[ASAP Digest Core] V5 Session Validation: wp_validate_auth_cookie failed for cookie value extracted.');
			return new WP_Error('rest_invalid_session', 'Invalid WordPress session.', ['status' => 401]);
		}

		// Cookie is valid, get user data
		$user_data = get_userdata($user_id);
		if (!$user_data) {
			error_log('[ASAP Digest Core] V5 Session Validation: Could not get user data for valid user ID: ' . $user_id);
			return new WP_Error('rest_user_not_found', 'User data could not be retrieved.', ['status' => 500]);
		}

		// Prepare response data
		$response_data = [
			'success' => true,
			'userData' => [
				'wpUserId'   => $user_data->ID,
				'email'      => $user_data->user_email,
				'username'   => $user_data->user_login,
				'name'       => $user_data->display_name,
			],
		];

		error_log('[ASAP Digest Core] V5 Session Validation: Success for user ' . $user_id);
		return new WP_REST_Response($response_data, 200);
	}
} 