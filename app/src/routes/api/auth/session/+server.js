/**
 * Server endpoint to validate JWT session
 * Proxies request to WordPress JWT validation endpoint
 */

import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth';

/** @type {import('./$types').RequestHandler} */
export async function GET({ request }) {
    try {
        const session = await auth.getSession();
        return json(session);
    } catch (error) {
        console.error('Session check error:', error);
        return json(
            { error: error.message || 'An error occurred while checking session' },
            { status: 400 }
        );
    }
} 