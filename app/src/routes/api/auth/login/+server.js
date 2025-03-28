/**
 * Server endpoint to handle user authentication
 * Proxies request to WordPress JWT auth endpoint
 */

import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth';

/**
 * Handle login POST request
 * @param {{ request: Request }} param0 Request object
 */
export async function POST({ request }) {
    try {
        const { email, password, rememberMe } = await request.json();
        
        const result = await auth.login({
            email,
            password,
            rememberMe: !!rememberMe
        });

        return json(result);
    } catch (error) {
        console.error('Login error:', error);
        return json(
            { error: error.message || 'An error occurred during login' },
            { status: 400 }
        );
    }
} 