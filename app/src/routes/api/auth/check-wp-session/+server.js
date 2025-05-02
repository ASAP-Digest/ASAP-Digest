import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth'; // Import configured Better Auth instance
import { syncWordPressUserAndCreateSession } from '$lib/server/auth-utils'; // Import reusable sync function
import { log } from '$lib/utils/log'; // Import logging utility

// Get environment variables or use fallbacks for development
const SYNC_SECRET = 'shared_secret_for_server_to_server_auth'; // Fallback
const WP_API_URL = 'https://asapdigest.local'; // Fallback

/**
 * Handles POST requests to check the WP session via cookies and establish an SK session.
 * @param {import('@sveltejs/kit').RequestEvent} event The SvelteKit request event object.
 * @returns {Promise<Response>} JSON response indicating success or failure.
 */
export async function POST(event) {
	log('[API /check-wp-session] Received POST request.');

	// 1. Extract WP Auth Cookies from the incoming request header
	const requestCookies = event.request.headers.get('cookie');
	if (!requestCookies) {
		log('[API /check-wp-session] No cookies found in request header.', 'warn');
		return json({ success: false, error: 'wp_cookie_missing' }, { status: 400 });
	}

	const wpAuthCookieHeader = requestCookies; // Send all cookies to WP for validation
	log('[API /check-wp-session] Extracted WP Auth Cookie header.');

	// 2. Determine WP validation endpoint URL
	const wpValidateUrl = `${WP_API_URL}/wp-json/asap/v1/validate-session-get-user`;
	log(`[API /check-wp-session] WP Validation URL: ${wpValidateUrl}`);

	// 3. Make server-to-server call to WP
	let wpResponse;
	try {
		wpResponse = await fetch(wpValidateUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Cookie': wpAuthCookieHeader,
				'X-ASAP-Sync-Secret': SYNC_SECRET
			}
		});

		log(`[API /check-wp-session] Received response from WP: Status ${wpResponse.status}`);

		if (!wpResponse.ok) {
			const errorText = await wpResponse.text();
			log(`[API /check-wp-session] WP validation request failed with status ${wpResponse.status}: ${errorText}`, 'error');
			return json({ success: false, error: 'wp_validation_failed_http' }, { status: 502 });
		}

		const wpResult = await wpResponse.json();
		log('[API /check-wp-session] Parsed WP JSON response.');

		// 4. Process WP Response
		if (wpResult.success && wpResult.userData) {
			log('[API /check-wp-session] WP validation successful. Proceeding with user sync.');
			
			// 5. Sync User and Create SK Session
			const session = await syncWordPressUserAndCreateSession(wpResult.userData);

			if (session) {
				log('[API /check-wp-session] SK user sync/session creation successful.');
				
				// 6. Set Better Auth session cookie - using a simplified approach
				const cookieHeader = `better_auth_session=${session.token}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${30 * 24 * 60 * 60}`;

				// 7. Return success to frontend with Set-Cookie header
				log('[API /check-wp-session] Returning success to frontend with Set-Cookie header.');
				return json(
					{ 
						success: true, 
						user: {
							id: session.userId,
							email: wpResult.userData.email,
							displayName: wpResult.userData.name || wpResult.userData.username
						} 
					},
					{
						headers: {
							'Set-Cookie': cookieHeader
						}
					}
				);
			} else {
				log('[API /check-wp-session] SK syncWordPressUserAndCreateSession failed.', 'error');
				return json({ success: false, error: 'sk_sync_failed' }, { status: 500 });
			}
		} else {
			// WP validation failed (invalid cookie, expired, etc.)
			log(`[API /check-wp-session] WP validation returned failure. Reason: ${wpResult.error || 'wp_session_invalid'}`, 'warn');
			return json({ success: false, error: wpResult.error || 'wp_session_invalid' }, { status: 401 });
		}

	} catch (error) {
		const errorMessage = error instanceof Error ? error.message : String(error);
		log(`[API /check-wp-session] Error during server-to-server fetch to WP: ${errorMessage}`, 'error');
		return json({ success: false, error: 'wp_fetch_error' }, { status: 500 });
	}
}

/**
 * Default GET handler (optional, could return method not allowed).
 * @param {import('@sveltejs/kit').RequestEvent} event
 * @returns {Promise<Response>}
 */
export async function GET(event) {
    return json({ message: 'Method Not Allowed. Use POST.' }, { status: 405 });
} 