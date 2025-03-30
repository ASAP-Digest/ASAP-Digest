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
import { PUBLIC_BETTER_AUTH_URL } from '$env/static/public';
import { dev } from '$app/environment';

/**
 * Get the appropriate base URL for the Better Auth client based on environment
 * @returns {string} - Base URL for Better Auth client
 */
function getBaseURL() {
    if (typeof window !== 'undefined') {
        return PUBLIC_BETTER_AUTH_URL || window.location.origin;
    }
    return PUBLIC_BETTER_AUTH_URL || 'http://localhost:5173';
}

/**
 * Get WordPress base URL
 * @returns {string} WordPress base URL
 */
function getWordPressBaseURL() {
    if (dev) {
        return 'https://asapdigest.local';
    }
    return 'https://asapdigest.com';
}

/**
 * Check for existing WordPress session
 * @returns {Promise<void>}
 */
async function checkWordPressSession() {
    try {
        const response = await fetch(`${getWordPressBaseURL()}/wp-json/asap/v1/auth/check-wp-session`, {
            credentials: 'include'
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (data.sessionToken) {
            // Store the session token in localStorage for now
            // Better Auth will pick it up on the next request
            localStorage.setItem('better_auth_token', data.sessionToken);
        }
    } catch (error) {
        console.error('[Auth Client] Error checking WordPress session:', error);
    }
}

/**
 * Better Auth Client Instance
 * Uses environment-specific base URL from PUBLIC_BETTER_AUTH_URL env var
 * Falls back to window.location.origin on client-side
 * Falls back to http://localhost:5173 on server-side
 */
export const authClient = createAuthClient({
    baseURL: getBaseURL(),
    endpoints: {
        login: '/api/auth/login',
        register: '/api/auth/register',
        logout: '/api/auth/logout',
        session: '/api/auth/session'
    }
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
    verifyEmail   // Verify user email
} = authClient;

/**
 * Check if user is authenticated
 * @returns {Promise<boolean>} - True if user is authenticated
 */
export async function isAuthenticated() {
    const session = await getSession();
    if (!session) {
        // Check for WordPress session if no Better Auth session exists
        await checkWordPressSession();
        return !!(await getSession());
    }
    return true;
}

/**
 * Get current user data
 * @returns {Promise<{id: string, email: string} | null>} - User data or null if not authenticated
 */
export async function getCurrentUser() {
    try {
        const session = await getSession();
        if (!session || typeof session !== 'object' || !('user' in session)) {
            // Check for WordPress session if no Better Auth session exists
            await checkWordPressSession();
            const newSession = await getSession();
            if (!newSession || typeof newSession !== 'object' || !('user' in newSession)) {
                return null;
            }
            const user = newSession.user;
            if (!user || typeof user !== 'object' || !('id' in user) || !('email' in user)) {
                return null;
            }
            return {
                id: String(user.id),
                email: String(user.email)
            };
        }
        const user = session.user;
        if (!user || typeof user !== 'object' || !('id' in user) || !('email' in user)) {
            return null;
        }
        return {
            id: String(user.id),
            email: String(user.email)
        };
    } catch (error) {
        console.error('[Auth Client] Error getting current user:', error);
        return null;
    }
} 