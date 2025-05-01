// REMOVED 2025-05-16: Legacy auth import - Better Auth APIs now handled by GraphQL + wp-user-sync
// import { auth } from '$lib/server/auth';
// import { toSvelteKitHandler } from 'better-auth/svelte-kit';

// Updated to use the new GraphQL-based auth approach
import { json } from '@sveltejs/kit';

/**
 * Handle login requests - now redirected to GraphQL-based auth
 * 
 * @param {object} event The request event object
 * @param {object} event.locals Server locals containing user data if authenticated
 * @param {User|undefined} event.locals.user User object if authenticated
 * @param {object} event.cookies Cookies API
 * @returns {Promise<Response>} JSON response with message
 */
export const POST = async (event) => {
    return json({ 
        message: 'Login endpoint now handled via GraphQL viewer query + wp-user-sync'
    });
}; 