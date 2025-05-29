/**
 * @file Protected Routes Server Layout
 * @description Server layout for protected routes - checks authentication and redirects if not authenticated
 * @milestone WP <-> SK Auto Login V6 - Protected Routes Implementation
 * @created 2025-05-03
 */

import { redirect } from '@sveltejs/kit';
import { log } from '$lib/utils/log';
import { getUserData } from '$lib/stores/user.js';

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
    
    // Enhanced debugging for wp_user_id
    console.log('[Protected Layout Server] Full user object:', locals.user);
    console.log('[Protected Layout Server] User metadata:', locals.user.metadata);
    console.log('[Protected Layout Server] wp_user_id from user:', locals.user.wp_user_id);
    console.log('[Protected Layout Server] wp_user_id from metadata:', locals.user.metadata?.wp_user_id);
    
    // Use getUserData to properly compute all fields including avatarUrl
    const userDataHelper = getUserData(locals.user);
    const processedUser = userDataHelper.toJSON();
    
    console.log('[Protected Layout Server] Processed user with getUserData:', {
        id: processedUser.id,
        email: processedUser.email,
        avatarUrl: processedUser.avatarUrl,
        wpUserId: processedUser.wpUserId
    });
    
    return {
        user: processedUser
    };
} 