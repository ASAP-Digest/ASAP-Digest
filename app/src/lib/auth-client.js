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

import { createAuthClient } from 'better-auth/svelte';
import { dev } from '$app/environment';

/**
 * @typedef {Object} RequestConfig
 * @property {string} [method] - HTTP method
 * @property {Record<string, string>} [headers] - Request headers
 */

/**
 * @typedef {Object} ResponseObject
 * @property {Headers} headers - Response headers
 */

/**
 * Get CSRF token from cookie and request a new one if not present
 * @returns {Promise<string>} - The CSRF token
 */
export async function getCSRFToken() {
    const token = document.cookie.match(/csrf_token=([^;]+)/)?.[1];
    if (token) return token;

    const response = await fetch('/api/auth/csrf-token');
    if (!response.ok) {
        throw new Error('Failed to get CSRF token');
    }
    
    const { token: newToken } = await response.json();
    return newToken;
}

/**
 * Set session token in secure HTTP-only cookie
 * @param {string} token - The session token to store
 */
async function setSessionToken(token) {
    const response = await fetch('/api/auth/set-session', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': await getCSRFToken()
        },
        body: JSON.stringify({ token })
    });

    if (!response.ok) {
        throw new Error('Failed to set session token');
    }
}

/**
 * Check for existing WordPress session and convert to Better Auth session
 * @param {number} [retries=3] - Number of retry attempts
 * @returns {Promise<void>}
 */
export async function checkWordPressSession(retries = 3) {
    let lastError;
    
    for (let attempt = 0; attempt < retries; attempt++) {
        try {
            const response = await fetch('/wp-json/asap/v1/auth/check-wp-session', {
                credentials: 'include',
                headers: {
                    'X-CSRF-Token': await getCSRFToken()
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.sessionToken) {
                await setSessionToken(data.sessionToken);
                return; // Success - exit function
            }
            
            // If we get here with no session token, no WordPress session exists
            return;
            
        } catch (error) {
            lastError = error;
            console.warn(`[Auth] Attempt ${attempt + 1} failed checking WordPress session:`, error);
            
            // Wait before retrying (exponential backoff)
            if (attempt < retries - 1) {
                await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
            }
        }
    }

    // All retries failed
    console.error('[Auth] Failed to check WordPress session after', retries, 'attempts:', lastError);
}

// Initialize Better Auth client with secure cookie configuration
export const auth = createAuthClient({
    baseURL: dev ? 'https://localhost:5173/api/auth' : 'https://asapdigest.com/api/auth',
    onRequest: async (/** @type {RequestConfig} */ config) => {
        // Add CSRF token to mutating requests
        if (config.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(config.method.toUpperCase())) {
            config.headers = {
                ...config.headers,
                'X-CSRF-Token': await getCSRFToken()
            };
        }
        return config;
    },
    onResponse: async (/** @type {ResponseObject} */ response) => {
        // Store session token in secure cookie if present in response
        const token = response.headers.get('Authorization')?.replace('Bearer ', '');
        if (token) {
            await setSessionToken(token);
        }
        return response;
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
    getSession  // Get current session data
} = auth;

/**
 * Get current user data with WordPress session check
 * @param {number} [retries=3] - Number of retry attempts for WordPress session check
 * @returns {Promise<{id: string, email: string} | null>} - User data or null if not authenticated
 */
export async function getCurrentUser(retries = 3) {
    try {
        const session = await getSession();
        if (!session || typeof session !== 'object' || !('user' in session)) {
            // Check for WordPress session if no Better Auth session exists
            await checkWordPressSession(retries);
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