import { createAuthClient } from "better-auth/svelte";
import { dev } from '$app/environment';

export const authClient = createAuthClient({
    // Base URL is optional if using the same domain
    baseURL: dev ? 'https://asapdigest.local' : 'https://asapdigest.com',
    // Use the same base path as server
    basePath: '/api/auth'
});

// Export specific methods for convenience
export const {
    signIn,
    signUp,
    signOut,
    useSession,
    getSession
} = authClient; 