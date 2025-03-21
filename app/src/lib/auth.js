import { browser } from '$app/environment';

/** @type {string|null} */
let wpNonce = null;

/** @type {number|null} */
let nonceExpiry = null;

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

/** 
 * Get WordPress nonce with caching
 * @param {string} [action='asap_digest_nonce'] - The nonce action
 * @param {number} [ttl=3600] - Time to live in seconds
 * @returns {Promise<string>} The nonce value
 */
export async function getNonce(action = 'asap_digest_nonce', ttl = 3600) {
    if (wpNonce && nonceExpiry && Date.now() < nonceExpiry) return wpNonce;

    try {
        const response = await fetch(`/api/nonce?action=${action}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const nonceValue = await response.text();
        wpNonce = nonceValue;
        nonceExpiry = Date.now() + (ttl * 1000);

        if (browser) {
            try {
                sessionStorage.setItem('wp_nonce', JSON.stringify({
                    value: nonceValue,
                    expiry: nonceExpiry
                }));
            } catch (e) {
                console.error('Failed to store nonce in sessionStorage:', e);
            }
        }

        return nonceValue;
    } catch (error) {
        console.error('Nonce Error:', error);
        throw error;
    }
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