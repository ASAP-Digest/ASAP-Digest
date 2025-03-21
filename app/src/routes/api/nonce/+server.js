import { WORDPRESS_URL } from '$env/static/private';

export async function GET({ url }) {
    const action = url.searchParams.get('action') || 'wp_rest';

    const response = await fetch(`${WORDPRESS_URL}/wp-json/asap/v1/nonce?action=${action}`, {
        headers: { 'X-WP-Nonce': 'retrieve' }
    });

    if (!response.ok) {
        return new Response('Nonce generation failed', { status: 502 });
    }

    return new Response(await response.text());
}
