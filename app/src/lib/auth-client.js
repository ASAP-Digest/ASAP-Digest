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
 * Better Auth Client Instance
 * Uses environment-specific base URL:
 * - Development: https://asapdigest.local
 * - Production: https://asapdigest.com
 */
export const authClient = createAuthClient({
    baseURL: dev ? 'https://asapdigest.local' : 'https://asapdigest.com'
});

/**
 * Export commonly used authentication methods
 * These can be imported individually in components:
 * import { signIn, signOut } from '$lib/auth-client';
 */
export const {
    signIn,    // Sign in with credentials
    signUp,    // Register new user
    signOut,   // Sign out current user
    useSession, // Svelte store for session state
    getSession  // Get current session data
} = authClient; 