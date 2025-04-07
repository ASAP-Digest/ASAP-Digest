/// <reference types="@sveltejs/kit" />

/**
 * @typedef {Object} SyncResponse
 * @property {boolean} valid - Whether the sync was successful
 * @property {boolean} [updated] - Whether the user data was updated
 * @property {string} [error] - Error message if sync failed
 */

/** 
 * Synchronize WordPress and Better Auth sessions
 * @param {import('@sveltejs/kit').RequestEvent} event - The request event object
 * @returns {Promise<Response>} The response object
 */
/** @type {import('./$types').RequestHandler} */
export async function GET({ request, locals }) {
  console.debug('[Sync API] Received GET request for /api/auth/sync');
  try {
    // Extract cookies from request
    const cookies = request.headers.get('cookie') || '';
    console.debug('[Sync API] Extracted cookies:', cookies);

    // Find WordPress authentication cookies
    const wpCookies = cookies
      .split(';')
      .map(cookie => cookie.trim())
      .filter(cookie => cookie.startsWith('wordpress_logged_in_'));
    
    console.debug('[Sync API] Found WordPress cookies:', wpCookies);

    // Return early if no WordPress cookies found
    if (wpCookies.length === 0) {
      console.debug('[Sync API] No WordPress cookies found. Returning 401.');
      return new Response(
        JSON.stringify({ 
          valid: false, 
          error: 'No WordPress session found' 
        }), {
          status: 401,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }

    /**
     * Get WordPress base URL based on environment
     * @returns {string} The WordPress base URL
     */
    const getWordPressBaseURL = () => {
      if (process.env.NODE_ENV === 'development') {
        return 'https://asapdigest.local';
      }
      return 'https://asapdigest.com';
    };
    const wpBaseURL = getWordPressBaseURL();
    const checkSessionURL = `${wpBaseURL}/wp-json/asap/v1/auth/check-wp-session`;
    console.debug(`[Sync API] Calling WordPress endpoint: ${checkSessionURL}`);

    // Validate WordPress session
    const response = await fetch(
      checkSessionURL,
      {
        headers: {
          'Cookie': wpCookies.join('; ')
        },
        credentials: 'include'
      }
    );

    console.debug('[Sync API] WordPress check-wp-session response status:', response.status);
    const data = await response.json();
    console.debug('[Sync API] WordPress check-wp-session response data:', data);

    // Handle unsuccessful response
    if (!response.ok) {
      console.debug('[Sync API] WordPress check-wp-session call failed or returned non-OK status. Returning error.');
      return new Response(
        JSON.stringify({ 
          valid: false, 
          error: data.error || 'Failed to validate session' 
        }), {
          status: response.status,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }

    // Handle not logged in state
    if (!data.loggedIn) {
      console.debug('[Sync API] WordPress check-wp-session indicates not logged in. Returning 401.');
      return new Response(
        JSON.stringify({ 
          valid: false, 
          error: 'Not logged in to WordPress' 
        }), {
          status: 401,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }

    // Update user data if needed
    /** @type {User|null} */
    const currentUser = locals.user || null;
    console.debug('[Sync API] Current SvelteKit user data (locals.user):', currentUser);
    console.debug('[Sync API] Data from WP (data):', data);

    const userIdChanged = currentUser?.id !== data.userId;
    const updatedAtChanged = currentUser?.updatedAt !== data.updatedAt;
    console.debug(`[Sync API] Checking for updates: userIdChanged=${userIdChanged}, updatedAtChanged=${updatedAtChanged}`);
    
    const updated = userIdChanged || updatedAtChanged;

    if (updated) {
      console.debug(`[Sync API] Update detected (userId changed: ${userIdChanged}, timestamp changed: ${updatedAtChanged}). Updating locals.user...`);
      /** @type {User} */
      locals.user = {
        id: data.userId,
        sessionToken: data.sessionToken,
        betterAuthId: data.userId,
        displayName: data.displayName || '',
        email: data.email || '',
        avatarUrl: data.avatarUrl || '',
        roles: data.roles || [],
        syncStatus: 'synced',
        updatedAt: data.updatedAt
      };
      console.debug('[Sync API] Updated locals.user:', locals.user);
    } else {
      console.debug('[Sync API] No update detected.');
    }

    console.debug(`[Sync API] Returning response: { valid: true, updated: ${updated} }`);
    return new Response(
      JSON.stringify({ valid: true, updated }), {
        headers: { 'Content-Type': 'application/json' }
      }
    );

  } catch (error) {
    console.error('[Sync API] Error during sync processing:', error);
    return new Response(
      JSON.stringify({ 
        valid: false, 
        error: 'Internal server error' 
      }), {
        status: 500,
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
} 