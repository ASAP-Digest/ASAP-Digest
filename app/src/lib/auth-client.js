/**
 * Better Auth Client Configuration for ASAP Digest
 * @see https://www.better-auth.com/docs/installation#create-client-instance
 * 
 * This client configuration handles authentication on the client side,
 * automatically detecting the environment and using the appropriate base URL.
 * 
 * Key Documentation References:
 * - Better Auth Client Setup: https://www.better-auth.com/docs/installation#create-client-instance
 * - SvelteKit Integration: https://www.better-auth.com/docs/installation#svelte-kit
 */

import { createAuthClient } from "better-auth/svelte";
import { dev } from '$app/environment';

/**
 * Get the appropriate base URL for the Better Auth client based on environment
 * @returns {string} - Base URL for Better Auth client
 */
function getBaseURL() {
    if (dev) {
        // Local development
        return 'http://localhost:5173';
    } else {
        // Production environment
        return 'https://app.asapdigest.com';
    }
}

/**
 * Better Auth Client Instance
 * Uses environment-specific base URL:
 * - Development: http://localhost:5173
 * - Production: https://app.asapdigest.com
 */
export const authClient = createAuthClient({
    baseURL: getBaseURL(),
});

/**
 * Export commonly used authentication methods
 * These can be imported individually in components:
 * import { signIn, signOut } from '$lib/auth-client';
 */
export const {
    signIn,     // Sign in with credentials
    signUp,     // Register new user
    signOut,    // Sign out current user
    useSession, // Svelte store for session state
    getSession, // Get current session data
    resetPassword, // Reset user password
    verifyEmail,   // Verify user email
    getUser       // Get user data for the current session
} = authClient;

/**
 * Check if user is authenticated
 * @returns {Promise<boolean>} - True if user is authenticated
 */
export async function isAuthenticated() {
    const session = await getSession();
    return !!session;
}

/**
 * Get current user data
 * @returns {Promise<object|null>} - User data or null if not authenticated
 */
export async function getCurrentUser() {
    try {
        const session = await getSession();
        if (!session) return null;
        return session.user;
    } catch (error) {
        console.error('[Auth Client] Error getting current user:', error);
        return null;
    }
} 