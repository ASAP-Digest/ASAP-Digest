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
 * @param {object} event.locals Server locals containing user data if authenticated
 * @param {User|undefined} event.locals.user User object if authenticated
 * @param {object} event.cookies Cookies API
 * @returns {Promise<Response>} JSON response with session status
 */
export const GET = async (event) => {
    return json({ 
        message: 'Session endpoint now handled via GraphQL viewer query + wp-user-sync', 
        userAuthenticated: !!event.locals.user 
    });
}; 