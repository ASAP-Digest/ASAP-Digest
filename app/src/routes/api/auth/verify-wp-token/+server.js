import { redirect, json } from '@sveltejs/kit';
import { syncWordPressUserAndCreateSession } from '$lib/server/auth-utils.js';
import { auth } from '$lib/server/auth.js';
import { ASAP_SK_SYNC_SECRET } from '$env/dynamic/private'; // Shared secret from .env
import { PUBLIC_WP_API_URL } from '$env/dynamic/public'; // Base WP URL
import { dev } from '$app/environment'; // To check environment
import { log } from '$lib/utils/log.js';

/**
 * Handles GET request to verify a WP-generated token (V4 Flow).
 * This endpoint receives the token from the browser via redirect,
 * validates it server-side with WordPress, syncs the user, creates a session,
 * sets the session cookie, and redirects the browser.
 *
 * @param {import('@sveltejs/kit').RequestEvent} event The SvelteKit request event.
 * @returns {Promise<Response>}
 * @created 07.27.24 | 03:45 PM PDT
 * @file-marker verify-wp-token
 */
export async function GET(event) {
	const token = event.url.searchParams.get('token');

	if (!token) {
		log('[Verify WP Token] No token found in request URL.');
		// Redirect to login with an error
		throw redirect(302, '/login?error=missing_token');
	}

	log(`[Verify WP Token] Received token: ${token.substring(0, 10)}...`); // Log part of token

	// 1. Determine WP validation endpoint URL
	const wpValidateUrl = `${PUBLIC_WP_API_URL}/wp-json/asap/v1/validate-sk-token`; // Assumes PUBLIC_WP_API_URL is set correctly (e.g., https://asapdigest.local)
	log(`[Verify WP Token] WP Validation URL: ${wpValidateUrl}`);

	let wpUser = null;
	let wpValidationError = null;

	// 2. Make Server-to-Server call to WP to validate token
	try {
		log(`[Verify WP Token] Sending validation request to WP...`);
		const wpResponse = await fetch(wpValidateUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-ASAP-Sync-Secret': ASAP_SK_SYNC_SECRET, // Include shared secret
			},
			body: JSON.stringify({ token: token }),
		});

		if (!wpResponse.ok) {
			const errorText = await wpResponse.text();
			log(`[Verify WP Token] WP validation failed. Status: ${wpResponse.status}, Response: ${errorText}`);
			wpValidationError = `WP validation failed (${wpResponse.status})`;
		} else {
			const wpData = await wpResponse.json();
			if (wpData.success && wpData.user) {
				log(`[Verify WP Token] WP validation successful. User ID: ${wpData.user.wpUserId}`);
				wpUser = wpData.user;
			} else {
				log(`[Verify WP Token] WP validation returned success=false or missing user data. Response: ${JSON.stringify(wpData)}`);
				wpValidationError = wpData.error || 'Invalid token or user data';
			}
		}
	} catch (error) {
		const message = error instanceof Error ? error.message : String(error);
		log(`[Verify WP Token] Network error during WP validation fetch: ${message}`);
		wpValidationError = 'Network error during validation';
	}

	// 3. Handle WP Validation Result
	if (!wpUser || wpValidationError) {
		log(`[Verify WP Token] Aborting sync due to WP validation failure: ${wpValidationError}`);
		// Redirect to login with an error
		throw redirect(302, `/login?error=wp_validation_failed&reason=${encodeURIComponent(wpValidationError)}`);
	}

	// 4. Sync User & Create BA Session (Call reusable function)
	log(`[Verify WP Token] WP validation succeeded. Calling syncWordPressUserAndCreateSession...`);
	const session = await syncWordPressUserAndCreateSession(wpUser);

	if (!session) {
		log(`[Verify WP Token] syncWordPressUserAndCreateSession failed.`);
		// Redirect to login with an error
		throw redirect(302, '/login?error=ba_sync_failed');
	}

	// 5. Set BA Session Cookie
	log(`[Verify WP Token] BA session created successfully. Setting cookie.`);
	try {
		// Type assertion needed as auth instance type doesn't expose sessionManager directly,
		// but it's expected based on better-auth-config v1.3 and needed for manual cookie setting.
		/** @type {any} */ (auth).sessionManager.setCookie(event.cookies, session);
		log(`[Verify WP Token] Cookie set.`);
	} catch (cookieError) {
		const message = cookieError instanceof Error ? cookieError.message : String(cookieError);
		log(`[Verify WP Token] Error setting session cookie: ${message}`);
		// Proceed to redirect anyway, but log the error
		// Consider redirecting to an error page if cookie setting is critical and fails
	}

	// 6. Redirect to Dashboard (Success)
	log(`[Verify WP Token] Redirecting to /dashboard.`);
	throw redirect(302, '/dashboard');
} 