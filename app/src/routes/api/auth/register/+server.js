/**
 * Server endpoint to handle user registration
 * Proxies request to WordPress JWT auth endpoint
 */

import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth';

/**
 * Handle registration POST request
 * @param {{ request: Request }} param0 Request object
 */
export async function POST({ request }) {
    try {
        const { email, password, name } = await request.json();
        
        const result = await auth.register({
            email,
            password,
            name
        });

        return json(result);
    } catch (error) {
        console.error('Registration error:', error);
        return json(
            { error: error.message || 'An error occurred during registration' },
            { status: 400 }
        );
    }
} 