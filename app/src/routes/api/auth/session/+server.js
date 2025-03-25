/**
 * Server endpoint to validate JWT session
 * Proxies request to WordPress JWT validation endpoint
 */

import { env } from '$env/dynamic/private';
import { json } from '@sveltejs/kit';

/**
 * Handle session GET request
 * @param {{ request: Request }} param0 Request object
 */
export async function GET({ request }) {
    try {
        // Extract the Bearer token from Authorization header
        const authHeader = request.headers.get('Authorization');
        if (!authHeader || !authHeader.startsWith('Bearer ')) {
            return json({
                success: false,
                message: 'Missing or invalid token'
            }, { status: 401 });
        }

        const token = authHeader.substring(7); // Remove 'Bearer ' prefix

        // The AUTH_API_URL should be set in your .env file
        const AUTH_API_URL = env.AUTH_API_URL || 'https://asapdigest.local';
        console.log('Using AUTH_API_URL for session validation:', AUTH_API_URL);

        // Forward the token validation request to the WordPress JWT endpoint
        const response = await fetch(`${AUTH_API_URL}/wp-json/asap/v1/auth/validate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ token })
        });

        // Get the response data
        const data = await response.json();

        // Handle error response
        if (!response.ok) {
            return json({
                success: false,
                message: data.message || 'Invalid session',
            }, { status: response.status });
        }

        // If token is valid but expired, redirect to refresh endpoint
        if (data.expired) {
            // Try to refresh the token
            const refreshResponse = await fetch(`${AUTH_API_URL}/wp-json/asap/v1/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ token })
            });

            if (!refreshResponse.ok) {
                return json({
                    success: false,
                    message: 'Session expired',
                }, { status: 401 });
            }

            const refreshData = await refreshResponse.json();
            return json({
                success: true,
                user: {
                    id: refreshData.user_id,
                    // Additional user data might need to be fetched separately
                },
                token: refreshData.token,
                refreshed: true
            });
        }

        // Session is valid
        return json({
            success: true,
            valid: data.valid,
            user: data.data?.user || { id: data.data?.user?.id }
        });
    } catch (error) {
        console.error('Session validation error:', error instanceof Error ? error.message : String(error));
        return json({
            success: false,
            message: 'Internal server error'
        }, { status: 500 });
    }
} 