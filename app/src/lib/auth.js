import { browser } from '$app/environment';
import { writable, derived } from 'svelte/store';

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
                // Use relative URL for API calls
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, rememberMe })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Authentication failed');
                }

                const data = await response.json();
                const session = {
                    user: data.user,
                    token: data.token
                };

                // Save to localStorage if rememberMe is enabled
                if (rememberMe && browser) {
                    localStorage.setItem('auth_session', JSON.stringify(session));
                }

                set({
                    user: data.user,
                    token: data.token,
                    loading: false,
                    error: null
                });

                return data;
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
                console.log('Initiating registration for:', email);

                // Use relative URL to ensure requests go to the correct domain
                const response = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, name })
                });

                const data = await response.json();

                if (!response.ok) {
                    console.error('Registration API error:', data);
                    const errorMessage = data.message || 'Registration failed';
                    update(state => ({ ...state, loading: false, error: errorMessage }));
                    throw new Error(errorMessage);
                }

                console.log('Registration successful, setting user data');

                set({
                    user: data.user,
                    token: data.token,
                    loading: false,
                    error: null
                });

                // Save session to localStorage
                if (browser) {
                    localStorage.setItem('auth_session', JSON.stringify({
                        user: data.user,
                        token: data.token
                    }));
                }

                return data;
            } catch (error) {
                console.error('Registration error:', error);
                // Only update the error if it hasn't been set already
                if (!(error instanceof Error) || !error.message?.includes('Registration failed')) {
                    set({ user: null, token: null, loading: false, error: error instanceof Error ? error.message : String(error) });
                }
                throw error;
            }
        },

        /**
         * Sign out the current user
         */
        signOut: async () => {
            update(state => ({ ...state, loading: true }));
            try {
                // Call the sign-out endpoint
                await fetch('/api/auth/logout', {
                    method: 'POST'
                });
            } catch (error) {
                console.error('Sign out error:', error);
            } finally {
                // Clear the session regardless of API response
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
                // Get the current token from the store
                let token = null;
                subscribe(state => {
                    token = state.token;
                })();

                if (!token) {
                    set({ user: null, token: null, loading: false, error: null });
                    return false;
                }

                const response = await fetch('/api/auth/session', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!response.ok) {
                    // Session invalid, clear local storage
                    if (browser) {
                        localStorage.removeItem('auth_session');
                    }
                    set({ user: null, token: null, loading: false, error: null });
                    return false;
                }

                const data = await response.json();

                // If token was refreshed, update it
                if (data.refreshed) {
                    const updatedSession = {
                        user: data.user,
                        token: data.token
                    };

                    // Save to localStorage
                    if (browser) {
                        localStorage.setItem('auth_session', JSON.stringify(updatedSession));
                    }
                }

                update(state => ({
                    ...state,
                    user: data.user,
                    token: data.token || state.token,
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
                // This endpoint hasn't been implemented in our JWT auth system yet
                // Will need to be added to the WordPress plugin later
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
                // This endpoint hasn't been implemented in our JWT auth system yet
                // Will need to be added to the WordPress plugin later
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