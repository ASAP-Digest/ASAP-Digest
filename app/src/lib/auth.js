import { $state } from 'svelte';

// Reactive nonce state management
let wpNonce = $state(null);
let nonceExpiry = $state(null);

/** 
 * Get WordPress nonce with caching
 * @param {string} [action='asap_digest_nonce']
 * @param {number} [ttl=3600] Seconds
 */
export async function getNonce(action = 'asap_digest_nonce', ttl = 3600) {
    if (wpNonce && Date.now() < nonceExpiry) return wpNonce;

    try {
        const response = await fetch(`/api/nonce?action=${action}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        wpNonce = await response.text();
        nonceExpiry = Date.now() + (ttl * 1000);
        sessionStorage.setItem('wp_nonce', JSON.stringify({
            value: wpNonce,
            expiry: nonceExpiry
        }));

        return wpNonce;
    } catch (error) {
        console.error('Nonce Error:', error);
        throw error;
    }
}

// Hydrate from session storage
const stored = sessionStorage.getItem('wp_nonce');
if (stored) try { ({ value: wpNonce, expiry: nonceExpiry } = JSON.parse(stored)) }
    catch { } 