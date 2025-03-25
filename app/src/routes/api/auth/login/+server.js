/**
 * Server endpoint to handle user authentication
 * Proxies request to WordPress JWT auth endpoint
 */

import { env } from '$env/dynamic/private';
import { json } from '@sveltejs/kit';

/**
 * Handle login POST request
 * @param {{ request: Request }} param0 Request object
 */
export async function POST({ request }) {
    try {
        const { email, password, rememberMe } = await request.json();

        // Validate input
        if (!email || !password) {
            return json({
                success: false,
                message: 'Email and password are required'
            }, { status: 400 });
        }

        // The AUTH_API_URL should be set in your .env file
        const AUTH_API_URL = env.AUTH_API_URL || 'https://asapdigest.local';
        console.log('Using AUTH_API_URL for login:', AUTH_API_URL);

        // Forward the authentication request to the WordPress JWT endpoint
        const response = await fetch(`${AUTH_API_URL}/wp-json/asap/v1/auth/token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: email, // WordPress typically uses username
                password,
                remember: rememberMe
            })
        });

        // Get the response data
        const data = await response.json();

        // Handle error response
        if (!response.ok) {
            return json({
                success: false,
                message: data.message || 'Authentication failed',
            }, { status: response.status });
        }

        // Successful login
        return json({
            success: true,
            user: {
                id: data.user_id,
                email: data.user_email,
                name: data.user_display_name,
                roles: data.roles || []
            },
            token: data.token,
            expiresIn: data.exp
        });
    } catch (error) {
        console.error('Login error:', error);
        return json({
            success: false,
            message: 'Internal server error'
        }, { status: 500 });
    }
} 