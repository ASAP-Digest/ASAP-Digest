/**
 * Server endpoint to handle user logout
 * This is a client-side only operation since JWT tokens can't be invalidated on server
 */

import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth';

/**
 * Handle logout POST request
 * @param {{ request: Request }} param0 Request object
 */
export async function POST({ request }) {
    try {
        await auth.logout();
        return json({ success: true });
    } catch (error) {
        console.error('Logout error:', error);
        return json(
            { error: error.message || 'An error occurred during logout' },
            { status: 400 }
        );
    }
} 