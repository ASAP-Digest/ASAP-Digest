/**
 * Server endpoint to retrieve WordPress nonce
 * Proxies request to WordPress API to get a nonce for authentication
 */

import { env } from '$env/dynamic/private';

export async function GET({ url }) {
    const action = url.searchParams.get('action') || 'wp_rest';
    const WORDPRESS_URL = env.WORDPRESS_URL || 'https://asapdigest.local';

    const response = await fetch(`${WORDPRESS_URL}/wp-json/asap/v1/nonce?action=${action}`, {
        headers: { 'X-WP-Nonce': 'retrieve' }
    });

    if (!response.ok) {
        return new Response('Nonce generation failed', { status: 502 });
    }

    return new Response(await response.text());
}
