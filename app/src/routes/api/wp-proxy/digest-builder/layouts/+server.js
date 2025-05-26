import { json } from '@sveltejs/kit';
import { getApiUrl } from '$lib/utils/api-config.js';

/**
 * Proxy for WordPress digest builder layouts endpoint
 * This allows the frontend to make requests through SvelteKit instead of direct cross-domain calls
 */
export async function GET(event) {
    try {
        const wpApiUrl = getApiUrl();
        const url = `${wpApiUrl}/wp-json/asap/v1/digest-builder/layouts`;
        
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
        
        console.log('[WP Proxy] Layouts - Forwarding request to:', url);
        console.log('[WP Proxy] Layouts - Headers:', headers);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: headers
        });
        
        console.log('[WP Proxy] Layouts - Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('[WP Proxy] Layouts - Error response:', errorText);
            return json({
                success: false,
                error: `WordPress API error: ${response.status} - ${errorText}`
            }, { status: response.status });
        }
        
        const data = await response.json();
        console.log('[WP Proxy] Layouts - Success, returning data');
        
        return json(data);
        
    } catch (error) {
        console.error('[WP Proxy] Layouts - Fetch error:', error);
        return json({
            success: false,
            error: `Network error: ${error instanceof Error ? error.message : 'Unknown error'}`
        }, { status: 500 });
    }
} 