/**
 * @file WordPress Session Check Endpoint
 * @description Server-to-server endpoint that checks for active WordPress sessions and initiates auto-login
 * @created 2025-05-01
 * @milestone WP <-> SK Auto Login V6 - MILESTONE COMPLETED! 2025-05-03
 * 
 * This endpoint is critical to the WP <-> SK Auto Login process:
 * 1. It receives requests from the frontend when no active session is found
 * 2. It makes a server-to-server request to WordPress to check for active sessions
 * 3. If an active WP session is found, it triggers syncWordPressUserAndCreateSession
 * 4. It sets the proper session cookie for client-side authentication
 * 
 * The implementation now successfully:
 * - Creates users in ba_users
 * - Creates account records in ba_accounts
 * - Creates authenticated sessions in ba_sessions
 * - Handles CSRF validation properly with the bypass header
 * - Uses the correct server-to-server authentication with shared secret
 */

import { json } from '@sveltejs/kit';
import { syncWordPressUserAndCreateSession } from '$lib/server/auth-utils';
import { log } from '$lib/utils/log';
import crypto from 'node:crypto';
// Remove import from $lib/config that might be causing issues
// import { SYNC_SECRET, WP_API_URL } from '$lib/config';

// Get environment variables for server-to-server communication
const SYNC_SECRET = process.env.BETTER_AUTH_SECRET || 'development-sync-secret-v6'; // Must match WP's BETTER_AUTH_SECRET
const WP_API_URL = process.env.WP_API_URL || 'https://asapdigest.local/wp-json';

// Log configuration for debugging
console.log('[WordPress API Config] SYNC_SECRET length:', SYNC_SECRET.length);
console.log('[WordPress API Config] WP_API_URL:', WP_API_URL);

/**
 * @typedef {Object} WPUserData
 * @property {number} wpUserId - WordPress user ID 
 * @property {string} email - User email address
 * @property {string} username - WordPress username
 * @property {string} displayName - User display name
 * @property {string[]} roles - Array of user roles
 * @property {string} firstName - User first name
 * @property {string} lastName - User last name
 * @property {Object} metadata - Additional user metadata
 */

/**
 * @typedef {import('$lib/types/better-auth').User} User
 */

/**
 * @typedef {Object} SessionResponse
 * @property {boolean} success - Whether the operation was successful
 * @property {User} [user] - User data if success is true
 * @property {string} [error] - Error message if success is false
 * @property {string} [details] - Additional error details or diagnostic information
 * @property {boolean} [sessionCreated] - Whether a new session was created
 * @property {boolean} [created] - Whether a new user was created
 * @property {boolean} [noRefresh] - Whether to prevent page refresh
 * @property {string} [warning] - Warning message
 * @property {number} [status] - HTTP status code
 */

/**
 * Handle POST requests to check WordPress session status
 * This is a server-to-server endpoint that communicates with WordPress
 * to check for active WordPress session and sync the user if found.
 *
 * @param {import('@sveltejs/kit').RequestEvent} event The SvelteKit request event
 * @returns {Promise<Response>} JSON response with user data or error message
 */
export async function POST(event) {
	// Skip during SSR
	if (event.request.headers.get('x-sveltekit-load') === 'true') {
		return json({ success: false, error: 'ssr_context' });
	}

	log('[API /check-wp-session] Initiating WordPress session check');

	try {
		// Determine if this is a browser request or direct server call
		// Check for specific headers set by our browser-side fetch
		const originHeader = event.request.headers.get('origin');
		const xRequestedWith = event.request.headers.get('x-requested-with');
		
		// Consider it a browser request if coming from a known UI origin or has XMLHttpRequest header
		const isBrowserRequest = 
			originHeader === 'https://localhost:5173' || 
			originHeader === 'https://app.asapdigest.com' || 
			xRequestedWith === 'XMLHttpRequest';
		
		// If this is a browser request without CSRF, we'll initiate our own server-to-server call
		// This effectively creates a proxy for browser-initiated requests
		if (isBrowserRequest) {
			log('[API /check-wp-session] Browser-initiated request detected, initiating server-to-server call');

			// Set a timeout to prevent hanging
			const controller = new AbortController();
			const timeoutId = setTimeout(() => controller.abort(), 8000);

			// Make server-to-server request to WordPress
			// Ensure the URL ends correctly with wp-json if needed and has proper path for get-active-sessions
			const baseApiUrl = WP_API_URL.endsWith('/wp-json') 
				? WP_API_URL 
				: WP_API_URL.endsWith('/') 
					? `${WP_API_URL}wp-json` 
					: `${WP_API_URL}/wp-json`;
			
			const wpEndpointUrl = `${baseApiUrl}/asap/v1/get-active-sessions`;
			
			// Log the full request details for debugging
			log(`[API /check-wp-session] Request details:`, 'debug');
			log(`[API /check-wp-session] - URL: ${wpEndpointUrl}`, 'debug');
			log(`[API /check-wp-session] - Secret length: ${SYNC_SECRET.length}`, 'debug');
			log(`[API /check-wp-session] - Headers: X-ASAP-Sync-Secret, X-ASAP-Request-Source`, 'debug');
		
			log(`[API /check-wp-session] Making server request to: ${wpEndpointUrl}`);

			try {
				const wpResponse = await fetch(wpEndpointUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-ASAP-Sync-Secret': SYNC_SECRET, // Use environment variable
						'X-ASAP-Request-Source': 'sveltekit-server'
					},
					body: JSON.stringify({
						requestSource: 'sk-server',
						timestamp: Date.now()
					}),
					signal: controller.signal
				});

				clearTimeout(timeoutId);

				// Basic check for HTML responses (which would indicate an error)
				const contentType = wpResponse.headers.get('content-type');
				if (contentType && contentType.includes('text/html')) {
					const errorHtml = await wpResponse.text();
					log(`[API /check-wp-session] Error: WP returned HTML instead of JSON. Content-Type: ${contentType}`, 'error');
					log(`[API /check-wp-session] HTML response start: ${errorHtml.substring(0, 200)}...`, 'error');
					return json({ success: false, error: 'wp_returned_html', htmlStart: errorHtml.substring(0, 100) });
				}

				if (!wpResponse.ok) {
					const errorText = await wpResponse.text();
					log(`[API /check-wp-session] WP request failed with status ${wpResponse.status}: ${errorText}`, 'error');
					
					// Parse the error if it's JSON
					try {
						const errorData = JSON.parse(errorText);
						if (errorData.code === 'internal_server_error') {
							log(`[API /check-wp-session] WordPress internal server error detected. This usually means the plugin is not properly configured or there's a PHP error.`, 'error');
							return json({ 
								success: false, 
								error: 'wp_plugin_error', 
								details: 'WordPress plugin may not be configured properly. Check WordPress error logs.',
								status: wpResponse.status 
							});
						}
					} catch (parseError) {
						// Not JSON, continue with generic error
					}
					
					return json({ 
						success: false, 
						error: 'wp_request_failed', 
						details: errorText.substring(0, 200),
						status: wpResponse.status 
					});
				}

				const wpResult = await wpResponse.json();

				// Process WP Response
				if (wpResult.success && wpResult.activeSessions?.length > 0) {
					// Get first active session user data
					const wpUserData = wpResult.activeSessions[0];
					log(`[API /check-wp-session] Processing WP user ${wpUserData.username}`);

					// Verify user exists or create new user
					const session = await syncWordPressUserAndCreateSession(wpUserData);

					if (session) {
						// Set Better Auth session cookie using event.cookies API instead of headers
						// This provides more consistent behavior across environments
						event.cookies.set('better_auth_session', session.token, {
							path: '/',
							httpOnly: true,
							sameSite: 'lax',
							secure: true,
							maxAge: 30 * 24 * 60 * 60 // 30 days
						});

						// Log successful session creation with session details
						log(`[API /check-wp-session] Session created successfully with token length: ${session.token.length}. Cookie set using cookies API.`, 'info');
						
						// Log avatar URL for debugging
						if (wpUserData.avatarUrl) {
							log(`[API /check-wp-session] User has avatarUrl: ${wpUserData.avatarUrl}`);
						} else {
							log(`[API /check-wp-session] No avatarUrl found for user ${wpUserData.username}`);
						}
						
						return json({
							success: true,
							user: {
								id: session.userId,
								email: wpUserData.email,
								displayName: wpUserData.displayName || wpUserData.username,
								avatarUrl: wpUserData.avatarUrl || '',
								roles: wpUserData.roles || [],
								wp_user_id: wpUserData.wpUserId, // CRITICAL FIX: Include WordPress user ID
								wpUserId: wpUserData.wpUserId    // Also include normalized version
							}
						});
					} else {
						log('[API /check-wp-session] SK session creation failed', 'error');
						return json({ success: false, error: 'sk_sync_failed' });
					}
				} else {
					const errorReason = wpResult.error || 'no_active_wp_sessions';
					log(`[API /check-wp-session] No active WP sessions found: ${errorReason}`, 'warn');
					return json({ success: false, error: errorReason });
				}
			} catch (fetchError) {
				// Handle fetch-specific errors
				const errorMessage = fetchError instanceof Error ? fetchError.message : String(fetchError);
				log(`[API /check-wp-session] Fetch error: ${errorMessage}`, 'error');
				return json({ success: false, error: 'wp_fetch_error', message: errorMessage });
			}
		} else {
			// This is for requests coming directly from WordPress
			// This branch would validate the request using the shared secret (if needed)
			// Currently, it seems WordPress is using the /api/auth/sync endpoint instead
			log('[API /check-wp-session] Non-browser request received, expected shared secret auth');
			return json({ success: false, error: 'direct_access_not_supported' });
		}
	} catch (error) {
		// Handle all other errors
		const errorMessage = error instanceof Error ? error.message : String(error);
		log(`[API /check-wp-session] Error: ${errorMessage}`, 'error');
		return json({ success: false, error: 'wp_request_error', message: errorMessage });
	}
}

/**
 * Get the client IP address from the request event
 * @param {import('@sveltejs/kit').RequestEvent} event - The SvelteKit request event
 * @returns {string} The client IP address
 */
function getClientAddress(event) {
	// Try to get IP from various headers that might be set by proxies
	const forwardedFor = event.request.headers.get('x-forwarded-for');
	if (forwardedFor) {
		// The header can contain multiple IPs, get the first one which is the client
		const ips = forwardedFor.split(',');
		return ips[0].trim();
	}
	
	const realIp = event.request.headers.get('x-real-ip');
	if (realIp) {
		return realIp.trim();
	}
	
	// Default fallback
	return '127.0.0.1';
}

/**
 * Default GET handler (optional, could return method not allowed).
 * @param {Object} event - The SvelteKit request event
 * @param {Request} event.request - The request object
 * @returns {Promise<Response>} JSON response with method not allowed message
 */
export async function GET(event) {
    return json({ message: 'Method Not Allowed. Use POST.' }, { status: 405 });
} 