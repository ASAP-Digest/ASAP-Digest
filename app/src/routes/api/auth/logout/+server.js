/**
 * Server endpoint to handle user logout
 * This is a client-side only operation since JWT tokens can't be invalidated on server
 */

import { json } from '@sveltejs/kit';

/**
 * Handle logout POST request
 * @param {{ request: Request }} param0 Request object
 */
export async function POST({ request }) {
    // JWT tokens can't be invalidated on the server since they're stateless
    // The client will handle clearing the token from storage

    // We return a success response to acknowledge the logout request
    return json({
        success: true,
        message: 'Logged out successfully'
    });
} 