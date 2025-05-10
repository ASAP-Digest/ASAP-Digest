/**
 * Server endpoint to validate JWT session
 * Proxies request to WordPress JWT validation endpoint
 */

// REMOVED 2025-05-16: Legacy auth import - Better Auth APIs now handled by GraphQL + wp-user-sync
// import { auth } from '$lib/server/auth';

// Updated to use the new GraphQL-based auth approach
import { json } from '@sveltejs/kit';

/**
 * Handle POST requests to check session status
 * 
 * @param {object} event The request event object
 * @param {object} event.locals Server locals containing user data if authenticated
 * @param {User|undefined} event.locals.user User object if authenticated
 * @param {object} event.cookies Cookies API
 * @returns {Promise<Response>} JSON response with session status
 */
export const POST = async (event) => {
    return json({ 
        message: 'Session endpoint now handled via GraphQL viewer query + wp-user-sync', 
        userAuthenticated: !!event.locals.user 
    });
};

/**
 * Handle GET requests to check session status
 * 
 * @param {object} event The request event object
 * @returns {Promise<Response>} JSON response with session status
 */
export const GET = async (event) => {
    // Check if we have a user in locals
    const user = event.locals?.user;
    
    // Get the session token from cookie directly using cookies API
    const sessionToken = event.cookies.get('better_auth_session');
    
    // Format consistent response
    const response = {
        authenticated: !!user,
        user: user || null
    };
    
    // Include session info if available in locals
    if (event.locals?.session) {
        response.session = event.locals.session;
    }
    
    console.log('[API /session] GET request received. Cookie present:', !!sessionToken);
    console.log('[API /session] User authenticated:', response.authenticated);
    
    // Add debug info in dev mode
    if (process.env.NODE_ENV !== 'production') {
        response.debug = {
            hasToken: !!sessionToken,
            tokenLength: sessionToken ? sessionToken.length : 0,
            hasUser: !!user,
            userId: user?.id || null
        };
    }
    
    return json(response);
}; 