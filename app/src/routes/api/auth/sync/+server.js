/// <reference types="@sveltejs/kit" />

/**
 * @typedef {Object} User
 * @property {string} id - The user's ID
 * @property {string} [sessionToken] - The user's session token
 */

/**
 * @typedef {import('@sveltejs/kit').Locals} SvelteKitLocals
 */

/**
 * Extends the RequestEvent.Locals interface from SvelteKit
 * @typedef {Object} CustomLocals
 * @property {User} [user] - The user object stored in locals
 */

declare global {
  namespace App {
    interface Locals extends CustomLocals {}
  }
}

/**
 * @typedef {Object} RequestEvent
 * @property {Request} request - The request object
 * @property {Object} locals - The locals object containing user data
 * @property {User} [locals.user] - The user object stored in locals
 */

/**
 * @typedef {Object} Locals
 * @property {User} [user] - The user object stored in locals
 */

/** 
 * Synchronize WordPress and Better Auth sessions
 * @param {RequestEvent} event - The request event object
 * @returns {Promise<Response>} The response object
 */
/** @type {import('./$types').RequestHandler} */
export async function GET({ request, locals }) {
  try {
    const cookies = request.headers.get('cookie') || '';
    console.debug('All cookies:', cookies);

    // Extract WordPress cookies
    const wpCookies = cookies
      .split(';')
      .map(cookie => cookie.trim())
      .filter(cookie => cookie.startsWith('wordpress_logged_in_'));
    
    console.debug('WordPress cookies:', wpCookies);

    if (wpCookies.length === 0) {
      console.debug('No WordPress cookies found');
      return new Response(JSON.stringify({ valid: false, error: 'No WordPress session found' }), {
        status: 401,
        headers: { 'Content-Type': 'application/json' }
      });
    }

    // Get WordPress base URL based on environment
    const getWordPressBaseURL = () => {
      if (process.env.NODE_ENV === 'development') {
        return 'https://asapdigest.local';
      }
      return 'https://asapdigest.com';
    };

    // Call WordPress to validate session
    const response = await fetch(`${getWordPressBaseURL()}/wp-json/asap/v1/auth/check-wp-session`, {
      headers: {
        'Cookie': wpCookies.join('; ')
      },
      credentials: 'include'
    });

    console.debug('WordPress response status:', response.status);
    const data = await response.json();
    console.debug('WordPress response data:', data);

    if (!response.ok) {
      return new Response(JSON.stringify({ valid: false, error: data.error || 'Failed to validate session' }), {
        status: response.status,
        headers: { 'Content-Type': 'application/json' }
      });
    }

    if (!data.loggedIn) {
      return new Response(JSON.stringify({ valid: false, error: 'Not logged in to WordPress' }), {
        status: 401,
        headers: { 'Content-Type': 'application/json' }
      });
    }

    // Update user data if needed
    const currentUser = locals.user || null;
    const updated = currentUser?.id !== data.userId;

    if (updated) {
      locals.user = {
        id: data.userId,
        sessionToken: data.sessionToken
      };
    }

    return new Response(JSON.stringify({ valid: true, updated }), {
      headers: { 'Content-Type': 'application/json' }
    });

  } catch (error) {
    console.error('Sync error:', error);
    return new Response(JSON.stringify({ valid: false, error: 'Internal server error' }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    });
  }
} 