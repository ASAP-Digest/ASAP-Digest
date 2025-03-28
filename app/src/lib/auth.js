import { browser } from '$app/environment';
import { writable, derived } from 'svelte/store';
import { createAuthClient } from "better-auth/svelte";
import { betterAuth } from "better-auth";
import { toSvelteKitHandler } from "better-auth/svelte-kit";
import mysql from 'mysql2/promise';

/** @type {string|null} */
let wpNonce = null;

/** @type {number|null} */
let nonceExpiry = null;

/**
 * @typedef {Object} AuthState
 * @property {any|null} user - The authenticated user or null
 * @property {string|null} token - Authentication token or null
 * @property {boolean} loading - Whether authentication is in progress
 * @property {string|null} error - Error message or null
 */

/** @type {import('better-auth/svelte').BetterAuthClient} */
export const betterAuthClient = createAuthClient({
    baseURL: browser ? window.location.origin : 'https://asapdigest.local'
});

// User session store
const createAuthStore = () => {
    /** @type {import('svelte/store').Writable<AuthState>} */
    const { subscribe, set, update } = writable({
        user: null,
        token: null,
        loading: true,
        error: null
    });

    // Initialize from localStorage if available
    if (browser) {
        try {
            const storedSession = localStorage.getItem('auth_session');
            if (storedSession) {
                const session = JSON.parse(storedSession);
                set({
                    user: session.user,
                    token: session.token,
                    loading: false,
                    error: null
                });
            } else {
                set({ user: null, token: null, loading: false, error: null });
            }
        } catch (error) {
            console.error('Error initializing auth store:', error);
            set({ user: null, token: null, loading: false, error: error instanceof Error ? error.message : String(error) });
        }
    }

    return {
        subscribe,
        /**
         * Sign in a user with email and password
         * @param {string} email User's email
         * @param {string} password User's password
         * @param {boolean} rememberMe Whether to remember the user
         */
        signIn: async (email, password, rememberMe = false) => {
            update(state => ({ ...state, loading: true, error: null }));
            try {
                const response = await betterAuthClient.signIn.credentials({ 
                    email, 
                    password,
                    rememberMe
                });

                const session = {
                    user: response.user,
                    token: response.token
                };

                if (rememberMe && browser) {
                    localStorage.setItem('auth_session', JSON.stringify(session));
                }

                set({
                    user: response.user,
                    token: response.token,
                    loading: false,
                    error: null
                });

                return response;
            } catch (error) {
                console.error('Sign in error:', error);
                set({ user: null, token: null, loading: false, error: error instanceof Error ? error.message : String(error) });
                throw error;
            }
        },

        /**
         * Register a new user
         * @param {string} email User's email
         * @param {string} password User's password
         * @param {string} name User's name
         */
        register: async (email, password, name) => {
            update(state => ({ ...state, loading: true, error: null }));
            try {
                const response = await betterAuthClient.signUp.credentials({
                    email,
                    password,
                    name
                });

                set({
                    user: response.user,
                    token: response.token,
                    loading: false,
                    error: null
                });

                if (browser) {
                    localStorage.setItem('auth_session', JSON.stringify({
                        user: response.user,
                        token: response.token
                    }));
                }

                return response;
            } catch (error) {
                console.error('Registration error:', error);
                set({ user: null, token: null, loading: false, error: error instanceof Error ? error.message : String(error) });
                throw error;
            }
        },

        /**
         * Sign out the current user
         */
        signOut: async () => {
            update(state => ({ ...state, loading: true }));
            try {
                await betterAuthClient.signOut();
            } catch (error) {
                console.error('Sign out error:', error);
            } finally {
                if (browser) {
                    localStorage.removeItem('auth_session');
                }
                set({ user: null, token: null, loading: false, error: null });
            }
        },

        /**
         * Check if the user session is valid
         */
        checkSession: async () => {
            update(state => ({ ...state, loading: true }));
            try {
                const session = await betterAuthClient.getSession();

                if (!session) {
                    if (browser) {
                        localStorage.removeItem('auth_session');
                    }
                    set({ user: null, token: null, loading: false, error: null });
                    return false;
                }

                update(state => ({
                    ...state,
                    user: session.user,
                    token: session.token,
                    loading: false,
                    error: null
                }));
                return true;
            } catch (error) {
                console.error('Session check error:', error);
                set({ user: null, token: null, loading: false, error: error instanceof Error ? error.message : String(error) });
                return false;
            }
        },

        /**
         * Request password reset for a user
         * @param {string} email User's email
         */
        requestPasswordReset: async (email) => {
            update(state => ({ ...state, loading: true, error: null }));
            try {
                await betterAuthClient.requestPasswordReset(email);
                update(state => ({ ...state, loading: false }));
                return true;
            } catch (error) {
                console.error('Password reset request error:', error);
                update(state => ({ ...state, loading: false, error: error instanceof Error ? error.message : String(error) }));
                throw error;
            }
        },

        /**
         * Complete password reset with token
         * @param {string} token Reset token from email
         * @param {string} password New password
         */
        resetPassword: async (token, password) => {
            update(state => ({ ...state, loading: true, error: null }));
            try {
                await betterAuthClient.resetPassword(token, password);
                update(state => ({ ...state, loading: false }));
                return true;
            } catch (error) {
                console.error('Password reset error:', error);
                update(state => ({ ...state, loading: false, error: error instanceof Error ? error.message : String(error) }));
                throw error;
            }
        }
    };
};

export const authStore = createAuthStore();

// Derived stores for common auth state
export const isAuthenticated = derived(authStore, $auth => !!$auth.user);
export const isLoading = derived(authStore, $auth => $auth.loading);
export const currentUser = derived(authStore, $auth => $auth.user);
export const authError = derived(authStore, $auth => $auth.error);

/**
 * Get a nonce for WordPress REST API requests
 * @param {string} action The action for which to get a nonce
 * @param {number} ttl Time-to-live in seconds
 * @returns {Promise<string>} The nonce
 */
export async function getNonce(action = 'asap_digest_nonce', ttl = 3600) {
    // If we already have a valid nonce, return it
    const now = Math.floor(Date.now() / 1000);
    if (wpNonce && nonceExpiry && nonceExpiry > now) {
        return wpNonce;
    }

    try {
        // Determine the correct API endpoint based on hostname
        let apiBase;

        if (typeof window !== 'undefined') {
            // Browser-side
            const hostname = window.location.hostname;

            if (hostname === 'localhost' || hostname === '127.0.0.1') {
                apiBase = 'https://asapdigest.local'; // Local development WordPress
            } else if (hostname === 'app.asapdigest.com') {
                apiBase = 'https://asapdigest.com'; // Production WordPress
            } else if (hostname === 'asapdigest.local') {
                apiBase = 'https://asapdigest.local'; // Local WordPress
            } else {
                apiBase = window.location.origin;
            }

            console.log('Nonce API Base:', apiBase, 'for hostname:', hostname);
        } else {
            // Server-side - default to local for development
            apiBase = 'https://asapdigest.local';
        }

        const response = await fetch(`${apiBase}/wp-json/asap/v1/nonce?action=${action}`);
        const nonce = await response.text();
        wpNonce = nonce;
        nonceExpiry = now + ttl;
        return nonce;
    } catch (error) {
        console.error('Error fetching nonce:', error);
        throw error;
    }
}

// Only use runes on the client side
if (browser) {
    // We can't use the standard import for $state because it would break SSR
    // Instead, use a safe dynamic import with proper type checking
    import('svelte').then(module => {
        // Check if we're in a Svelte environment with runes support
        // @ts-ignore - We know this might exist in Svelte 5
        const stateFunction = module.$state;

        if (typeof stateFunction === 'function') {
            // Re-assign variables using $state in client context
            const initialNonce = wpNonce;
            const initialExpiry = nonceExpiry;
            // @ts-ignore - Using the runes API
            wpNonce = stateFunction(initialNonce);
            // @ts-ignore - Using the runes API
            nonceExpiry = stateFunction(initialExpiry);
        }
    }).catch(err => console.error('Failed to import Svelte runes:', err));
}

// Hydrate from session storage (client-side only)
if (browser) {
    try {
        const stored = sessionStorage.getItem('wp_nonce');
        if (stored) {
            /** @type {{ value: string, expiry: number }} */
            const parsed = JSON.parse(stored);
            wpNonce = parsed.value;
            nonceExpiry = parsed.expiry;
        }
    } catch (e) {
        console.error('Failed to read nonce from sessionStorage:', e);
    }
}

// Export Better Auth client for direct access if needed
export { betterAuthClient };

// Configure Better Auth
export const auth = betterAuth({
    database: mysql.createPool({
        host: 'localhost',
        port: 10040,
        user: 'root',
        password: 'root',
        database: 'local',
        charset: 'utf8',
        ssl: undefined
    }),
    session: {
        modelName: 'wp_better_auth_sessions',
        expiresIn: 30 * 24 * 60 * 60,
        updateAge: 24 * 60 * 60
    },
    cookies: {
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax',
        domain: process.env.NODE_ENV === 'production' ? '.asapdigest.com' : '.asapdigest.local'
    },
    providers: ['credentials'],
    security: {
        trackIp: process.env.NODE_ENV === 'production'
    }
});

// Export handler for SvelteKit endpoint
export const handler = toSvelteKitHandler(auth); 