/**
 * Server endpoint to handle user registration
 * Proxies request to WordPress JWT auth endpoint
 */

import { env } from '$env/dynamic/private';
import { json } from '@sveltejs/kit';

/**
 * Handle registration POST request
 * @param {{ request: Request }} param0 Request object
 */
export async function POST({ request }) {
    try {
        const { email, password, name } = await request.json();

        console.log('Registration request received for:', email);

        // Validate input
        if (!email || !password) {
            console.error('Registration validation failed: missing email or password');
            return json({
                success: false,
                message: 'Email and password are required'
            }, { status: 400 });
        }

        // The AUTH_API_URL should be set in your .env file
        const AUTH_API_URL = env.AUTH_API_URL || 'https://asapdigest.local';
        console.log('Using AUTH_API_URL for registration:', AUTH_API_URL);

        // Forward the registration request to the WordPress JWT API
        const endpoint = `${AUTH_API_URL}/wp-json/asap/v1/auth/register`;
        console.log('Forwarding registration request to:', endpoint);

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: email, // WordPress accepts username as identifier
                email,
                password,
                first_name: name ? name.split(' ')[0] : '',
                last_name: name && name.split(' ').length > 1 ? name.split(' ').slice(1).join(' ') : ''
            })
        });

        // Get the response data
        const data = await response.json();
        console.log('Registration response status:', response.status);

        // Handle error response
        if (!response.ok) {
            console.error('Registration failed with status:', response.status, 'Message:', data.message);
            return json({
                success: false,
                message: data.message || 'Registration failed',
            }, { status: response.status });
        }

        console.log('Registration successful for user:', data.email);

        // Successful registration
        return json({
            success: true,
            user: {
                id: data.user_id,
                email: data.email,
                name: name || email.split('@')[0],
                roles: data.roles || []
            },
            token: data.token,
            expiresIn: data.exp
        });
    } catch (error) {
        console.error('Registration error details:', error instanceof Error ? error.message : String(error));
        return json({
            success: false,
            message: 'Internal server error: ' + (error instanceof Error ? error.message : 'Unknown error')
        }, { status: 500 });
    }
} 