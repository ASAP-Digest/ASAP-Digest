/**
 * Test endpoint to verify authentication configuration
 */

import { env } from '$env/dynamic/private';
import { json } from '@sveltejs/kit';

/**
 * GET handler for authentication test
 * @param {{ request: Request }} param0 Request object
 */
export async function GET({ request }) {
    try {
        // Report environment configuration
        const AUTH_API_URL = env.AUTH_API_URL || 'https://asapdigest.local';
        const envData = {
            auth_api_url: AUTH_API_URL,
            node_env: env.NODE_ENV || 'development',
            app_url: env.APP_URL || 'http://localhost:5173',
            wp_url: env.WP_URL || 'https://asapdigest.local',
        };

        // Test connection to WordPress site
        /** @type {{ status?: number, ok?: boolean, statusText?: string, error?: boolean, message?: string }} */
        let wpConnectionStatus = {};
        try {
            const wpResponse = await fetch(`${AUTH_API_URL}/wp-json`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            wpConnectionStatus = {
                status: wpResponse.status,
                ok: wpResponse.ok,
                statusText: wpResponse.statusText
            };
        } catch (error) {
            wpConnectionStatus = {
                error: true,
                message: error instanceof Error ? error.message : String(error)
            };
        }

        // Test access to JWT auth endpoints
        /** @type {{ status?: number, ok?: boolean, statusText?: string, nonce?: string, error?: boolean, message?: string }} */
        let jwtEndpointStatus = {};
        try {
            const jwtResponse = await fetch(`${AUTH_API_URL}/wp-json/asap/v1/nonce?action=asap_digest_nonce`, {
                method: 'GET',
            });
            jwtEndpointStatus = {
                status: jwtResponse.status,
                ok: jwtResponse.ok,
                statusText: jwtResponse.statusText
            };

            if (jwtResponse.ok) {
                jwtEndpointStatus.nonce = await jwtResponse.text();
            }
        } catch (error) {
            jwtEndpointStatus = {
                error: true,
                message: error instanceof Error ? error.message : String(error)
            };
        }

        // Test JWT auth endpoints
        /** @type {Record<string, { status?: number, ok?: boolean, method?: string, error?: boolean, message?: string }>} */
        let jwtAuthEndpoints = {};

        try {
            // Test if the JWT auth endpoints exist
            const endpoints = [
                'auth/register',
                'auth/token',
                'auth/validate',
                'auth/refresh'
            ];

            for (const endpoint of endpoints) {
                const key = endpoint.split('/')[1];
                try {
                    const response = await fetch(`${AUTH_API_URL}/wp-json/asap/v1/${endpoint}`, {
                        method: 'OPTIONS',
                        headers: { 'Content-Type': 'application/json' }
                    });

                    jwtAuthEndpoints[key] = {
                        status: response.status,
                        ok: response.status !== 404, // Even if we get a 405 Method Not Allowed, the endpoint exists
                        method: 'OPTIONS'
                    };
                } catch (endpointError) {
                    jwtAuthEndpoints[key] = {
                        error: true,
                        message: endpointError instanceof Error ? endpointError.message : String(endpointError)
                    };
                }
            }
        } catch (error) {
            console.error('JWT endpoints test error:', error);
        }

        return json({
            success: true,
            environment: envData,
            wordpress_connection: wpConnectionStatus,
            jwt_endpoint: jwtEndpointStatus,
            jwt_auth_endpoints: jwtAuthEndpoints,
            request_headers: {
                host: request.headers.get('host'),
                origin: request.headers.get('origin'),
                referer: request.headers.get('referer')
            }
        });
    } catch (error) {
        console.error('Auth test error:', error);
        return json({
            success: false,
            message: 'Auth test failed',
            error: error instanceof Error ? error.message : String(error)
        }, { status: 500 });
    }
} 