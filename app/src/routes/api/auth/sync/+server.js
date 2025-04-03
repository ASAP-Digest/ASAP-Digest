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
  try {
    // Extract cookies from request
    const cookies = request.headers.get('cookie') || '';
    console.debug('All cookies:', cookies);

    // Find WordPress authentication cookies
    const wpCookies = cookies
      .split(';')
      .map(cookie => cookie.trim())
      .filter(cookie => cookie.startsWith('wordpress_logged_in_'));
    
    console.debug('WordPress cookies:', wpCookies);

    // Return early if no WordPress cookies found
    if (wpCookies.length === 0) {
      console.debug('No WordPress cookies found');
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

    // Validate WordPress session
    const response = await fetch(
      `${getWordPressBaseURL()}/wp-json/asap/v1/auth/check-wp-session`, 
      {
        headers: {
          'Cookie': wpCookies.join('; ')
        },
        credentials: 'include'
      }
    );

    console.debug('WordPress response status:', response.status);
    const data = await response.json();
    console.debug('WordPress response data:', data);

    // Handle unsuccessful response
    if (!response.ok) {
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
    const updated = currentUser?.id !== data.userId;

    if (updated) {
      /** @type {User} */
      locals.user = {
        id: data.userId,
        sessionToken: data.sessionToken
      };
    }

    return new Response(
      JSON.stringify({ valid: true, updated }), {
        headers: { 'Content-Type': 'application/json' }
      }
    );

  } catch (error) {
    console.error('Sync error:', error);
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