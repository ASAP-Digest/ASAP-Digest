import { json } from '@sveltejs/kit';
import { getApiUrl } from '$lib/utils/api-config.js';

/**
 * Proxy for WordPress digest builder create-draft endpoint
 * This allows the frontend to make requests through SvelteKit instead of direct cross-domain calls
 */
export async function POST(event) {
    try {
        const wpApiUrl = getApiUrl();
        const url = `${wpApiUrl}/wp-json/asap/v1/digest-builder/create-draft`;
        
        // Get the request body
        const requestBody = await event.request.json();
        
        // Forward the Authorization header from the original request
        const authHeader = event.request.headers.get('authorization');
        
        // Get environment variables for server-to-server communication
        const SYNC_SECRET = process.env.BETTER_AUTH_SECRET || 'development-sync-secret-v6';
        
        const headers = {
            'Content-Type': 'application/json',
            'X-ASAP-Sync-Secret': SYNC_SECRET, // Add server-to-server auth
            'X-ASAP-Request-Source': 'sveltekit-server'
        };
        
        if (authHeader) {
            headers['Authorization'] = authHeader;
        }
        
        console.log('[WP Proxy] Create Draft - Forwarding request to:', url);
        console.log('[WP Proxy] Create Draft - Headers:', headers);
        console.log('[WP Proxy] Create Draft - Body:', requestBody);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(requestBody)
        });
        
        console.log('[WP Proxy] Create Draft - Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('[WP Proxy] Create Draft - Error response:', errorText);
            return json({
                success: false,
                error: `WordPress API error: ${response.status} - ${errorText}`
            }, { status: response.status });
        }
        
        const data = await response.json();
        console.log('[WP Proxy] Create Draft - Success, returning data');
        
        return json(data);
        
    } catch (error) {
        console.error('[WP Proxy] Create Draft - Fetch error:', error);
        return json({
            success: false,
            error: `Network error: ${error instanceof Error ? error.message : 'Unknown error'}`
        }, { status: 500 });
    }
} 