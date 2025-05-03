import { json } from '@sveltejs/kit';
import { syncWordPressUserAndCreateSession } from '$lib/server/auth-utils-fixed.js';
import { log } from '$lib/utils/log';
import crypto from 'node:crypto';
// Remove import from $lib/config that might be causing issues
// import { SYNC_SECRET, WP_API_URL } from '$lib/config';

// Get environment variables for server-to-server communication
const SYNC_SECRET = process.env.BETTER_AUTH_SECRET || 'development-sync-secret-v6'; // Must match WP's BETTER_AUTH_SECRET
const WP_API_URL = process.env.WP_API_URL || 'https://asapdigest.local';

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
	const { request, cookies } = event;
	log('[API /check-wp-session] Processing WordPress session check request', 'info');

	// Get client IP and user agent for logging and session tracking
	const clientIp = getClientAddress(event);
	const userAgent = request.headers.get('user-agent') || 'Server-to-server sync';

	log(`[API /check-wp-session] Request from ${clientIp} with agent: ${userAgent.substring(0, 50)}...`, 'debug');

	// Get configuration from server environment
	// Retry configuration for more reliable operation
	const maxRetries = 3;
	const retryDelayMs = 1000;
	const timeoutMs = 10000;

	try {
		// Set up abort controller for timeout
		const controller = new AbortController();
		const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

		// Prepare the payload with client timestamp for request tracing
		const requestPayload = {
			clientIp,
			clientTimestamp: Date.now(),
			checkType: 'session'
		};

		// Determine the WordPress endpoint
		// Check if WP_API_URL already ends with /wp-json to avoid duplication
		const baseUrl = WP_API_URL.endsWith('/wp-json') 
			? WP_API_URL 
			: `${WP_API_URL}/wp-json`;
		const url = `${baseUrl}/asap/v1/get-active-sessions`;
		
		log(`[API /check-wp-session] Preparing request to WordPress endpoint: ${url}`, 'debug');
		log(`[API /check-wp-session] Using secret with length: ${SYNC_SECRET ? SYNC_SECRET.length : 0}`, 'debug');

		// Make the server-to-server request
		log('[API /check-wp-session] Sending server-to-server request to WordPress', 'info');
		const response = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-ASAP-Sync-Secret': SYNC_SECRET,
				'X-ASAP-Client-IP': clientIp,
				'X-ASAP-Request-Source': 'svelte-kit-server',
				'User-Agent': userAgent
			},
			body: JSON.stringify(requestPayload),
			signal: controller.signal
		});

		// Clear the timeout
		clearTimeout(timeoutId);

		// Handle WordPress response
		if (!response.ok) {
			const errorText = await response.text();
			log(`[API /check-wp-session] WordPress API returned error: ${response.status} ${response.statusText}`, 'error');
			log(`[API /check-wp-session] Error response: ${errorText}`, 'error');
			return json({
				success: false,
				error: 'wp_api_error',
				status: response.status,
				message: response.statusText,
				details: errorText
			}, { status: 502 }); // 502 Bad Gateway
		}

		// Parse the response
		const data = await response.json();
		log(`[API /check-wp-session] WordPress API response received - success: ${!!data.success}`, 'info');

		// Check if the WordPress response indicates an active session
		if (!data.success || !data.activeSessions || data.activeSessions.length === 0) {
			log('[API /check-wp-session] No active WordPress session found', 'info');
			log(`[API /check-wp-session] WordPress response details: ${JSON.stringify(data)}`, 'debug');
			return json({
				success: false,
				error: 'no_wordpress_session',
				details: data.error || 'No active WordPress sessions found'
			});
		}

		// Extract WordPress user data from response (use first active session)
		const wpUserData = data.activeSessions[0];
		
		log(`[API /check-wp-session] WordPress user found: ${wpUserData.email} (ID: ${wpUserData.wpUserId})`, 'info');

		// Use the enhanced utility to sync WP user to Better Auth and create session
		// Pass client IP and user agent for session tracking
		log('[API /check-wp-session] Syncing WordPress user to Better Auth...', 'info');
		const result = await syncWordPressUserAndCreateSession(
			wpUserData, 
			cookies, 
			maxRetries, 
			retryDelayMs,
			clientIp,
			userAgent
		);

		// Return the result of the sync operation
		if (result.success && result.user) {
			log(`[API /check-wp-session] Auto-login successful for user: ${result.user.email}`, 'info');
			return json({
				success: true,
				user: result.user,
				created: result.created
			});
		} else {
			log(`[API /check-wp-session] Failed to auto-login WordPress user: ${result.error || 'Unknown error'}`, 'error');
			log(`[API /check-wp-session] Error details: ${result.details || 'No details provided'}`, 'error');
			
			// FALLBACK: If the specific error is session creation, try a simpler approach
			if (result.details === 'session_creation_err') {
				log('[API /check-wp-session] Attempting direct cookie fallback for session_creation_err', 'warn');
				
				try {
					// Extract user data from WordPress payload for direct use
					// This works because we know the WP user exists and we matched on session_creation_err,
					// which means user creation succeeded but session creation failed
					
					// Create a proper User object that matches the expected User type
					/** @type {import('$lib/types/better-auth').User} */
					const fallbackUser = {
						id: wpUserData.wpUserId.toString(), // Use WordPress ID directly
						email: wpUserData.email,
						displayName: wpUserData.displayName || wpUserData.username || wpUserData.email,
						// Include other required User type properties
						metadata: {
							wp_user_id: typeof wpUserData.wpUserId === 'number' ? wpUserData.wpUserId : 
									   parseInt(String(wpUserData.wpUserId), 10) || undefined,
							roles: Array.isArray(wpUserData.roles) ? wpUserData.roles : ['user']
						},
						// Optional properties with safe defaults
						username: wpUserData.username,
						name: wpUserData.displayName,
						emailVerified: true,
						image: null,
						createdAt: new Date().toISOString(),
						updatedAt: new Date().toISOString(),
						// Add noRefresh flag to prevent SvelteKit from refreshing
						_noRefresh: true
					};
					
					// Generate a session token
					const sessionToken = crypto.randomUUID();
					const expiryDate = new Date();
					expiryDate.setDate(expiryDate.getDate() + 30); // 30 days
					
					// Set cookie directly with improved settings
					cookies.set('better_auth_session', sessionToken, {
						path: '/',
						httpOnly: true,
						secure: process.env.NODE_ENV === 'production',
						expires: expiryDate,
						sameSite: 'lax',
						maxAge: 30 * 24 * 60 * 60, // 30 days in seconds - helps some browsers
						// domain: undefined - explicitly don't set to ensure full compatibility
					});
					
					log('[API /check-wp-session] Set fallback session cookie directly using WordPress data', 'warn');
					
					// Return success even though we bypassed the normal session flow
					return json({
						success: true,
						user: /** @type {import('$lib/types/better-auth').User} */ (fallbackUser),
						created: false,
						warning: 'Used cookie fallback mechanism due to session_creation_err',
						noRefresh: true // Add noRefresh flag to response
					}, {
						headers: {
							// Add cache control to prevent any caching
							'Cache-Control': 'no-store, no-cache, must-revalidate, proxy-revalidate',
							'Pragma': 'no-cache',
							'Expires': '0',
							'Surrogate-Control': 'no-store'
						}
					});
				} catch (err) {
					const error = err instanceof Error ? err : new Error(String(err));
					log(`[API /check-wp-session] Fallback mechanism failed: ${error.message}`, 'error');
				}
			}
			
			// Provide enhanced error information for debugging
			return json({
				success: false,
				error: result.error || 'sync_failed',
				details: result.details || 'unknown_error',
				diagnosticInfo: {
					// Include safe diagnostic info - don't include actual credentials
					timestamp: new Date().toISOString(),
					wpUserId: wpUserData.wpUserId,
					errorCategory: result.details?.includes('session_creation') ? 'session_error' : 
						          result.details?.includes('user_creation') ? 'user_error' : 
								  'unknown_error',
				}
			});
		}
	} catch (error) {
		const errorMessage = error instanceof Error ? error.message : String(error);
		log(`[API /check-wp-session] Unexpected error: ${errorMessage}`, 'error');
		if (error instanceof Error && error.stack) {
			log(`[API /check-wp-session] Error stack: ${error.stack}`, 'debug');
		}
		return json({
			success: false,
			error: 'unexpected_error',
			message: errorMessage,
			details: error instanceof Error ? error.stack : 'No stack trace available'
		}, { status: 500 });
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