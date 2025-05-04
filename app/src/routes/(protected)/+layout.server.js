/**
 * @file Protected Routes Server Layout
 * @description Server layout for protected routes - checks authentication and redirects if not authenticated
 * @milestone WP <-> SK Auto Login V6 - Protected Routes Implementation
 * @created 2025-05-03
 */

import { redirect } from '@sveltejs/kit';
import { log } from '$lib/utils/log';

/**
 * Server-side load function for protected routes
 * @param {import('@sveltejs/kit').ServerLoadEvent} event The SvelteKit load event
 * @returns {Promise<Object>} Data object with user information
 */
export async function load({ locals, url }) {
    // Log the request for debugging
    log(`[Protected Layout Server] Authentication check for ${url.pathname}`, 'info');
    
    // Check if user is authenticated
    if (!locals.user) {
        log(`[Protected Layout Server] No authenticated user found, redirecting to login`, 'warn');
        // Redirect to login with return URL
        throw redirect(303, `/login?redirect=${encodeURIComponent(url.pathname)}`);
    }
    
    // User is authenticated, provide user data to the client
    log(`[Protected Layout Server] User authenticated: ${locals.user.email}`, 'info');
    
    return {
        user: {
            id: locals.user.id,
            email: locals.user.email,
            displayName: locals.user.displayName || locals.user.email.split('@')[0],
            roles: locals.user.roles || ['subscriber'],
            avatarUrl: locals.user.avatarUrl,
            updatedAt: locals.user.updatedAt || new Date().toISOString()
        }
    };
} 