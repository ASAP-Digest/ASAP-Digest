/**
 * Server endpoint to handle user logout
 * This is a client-side only operation since JWT tokens can't be invalidated on server
 */

// REMOVED 2025-05-16: Legacy auth import - Better Auth APIs now handled by GraphQL + wp-user-sync
// import { auth } from '$lib/server/auth';
// import { toSvelteKitHandler } from 'better-auth/svelte-kit';

// Updated to use the new GraphQL-based auth approach
import { deleteSessionFn } from '$lib/server/auth';
import { json } from '@sveltejs/kit';

/**
 * Handle logout requests by removing session
 * 
 * @param {object} event The request event object
 * @param {object} event.locals Server locals containing user data if authenticated
 * @param {User|undefined} event.locals.user User object if authenticated
 * @param {object} event.cookies Cookies API for clearing session cookie
 * @param {Function} event.cookies.delete Function to delete a cookie
 * @param {Request} event.request The SvelteKit Request object with headers containing cookies
 * @returns {Promise<Response>} JSON response with success status
 */
export const POST = async (event) => {
    // Get session token from cookie with type safety
    const cookieHeader = event.request.headers.get('cookie');
    const sessionToken = cookieHeader ? 
        cookieHeader.match(/better_auth_session=([^;]+)/)?.[1] : 
        undefined;
    
    // Only attempt to delete session if token exists
    if (sessionToken && typeof sessionToken === 'string') {
        // Delete the session
        await deleteSessionFn(sessionToken);
        
        // Clear cookie
        event.cookies.delete('better_auth_session', { 
            path: '/',
            secure: true,
            httpOnly: true,
            sameSite: 'strict'
        });
    }
    
    return json({ success: true });
}; 