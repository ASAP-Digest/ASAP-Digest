import { json } from '@sveltejs/kit';
import { getApiUrl } from '$lib/utils/api-config.js';

/**
 * Proxy for WordPress digest builder user-digests endpoint
 * This allows the frontend to make requests through SvelteKit instead of direct cross-domain calls
 */
export async function GET(event) {
    try {
        const wpApiUrl = getApiUrl();
        const searchParams = event.url.searchParams;
        const status = searchParams.get('status') || 'draft';
        const url = `${wpApiUrl}/wp-json/asap/v1/digest-builder/user-digests?status=${status}`;
        
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
        
        console.log('[WP Proxy] User Digests - Forwarding request to:', url);
        console.log('[WP Proxy] User Digests - Headers:', headers);
        console.log('[WP Proxy] User Digests - Auth header present:', !!authHeader);
        console.log('[WP Proxy] User Digests - Auth header preview:', authHeader ? authHeader.substring(0, 20) + '...' : 'none');
        
        const response = await fetch(url, {
            method: 'GET',
            headers: headers
        });
        
        console.log('[WP Proxy] User Digests - Response status:', response.status);
        console.log('[WP Proxy] User Digests - Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('[WP Proxy] User Digests - Error response:', errorText);
            return json({
                success: false,
                error: `WordPress API error: ${response.status} - ${errorText}`
            }, { status: response.status });
        }
        
        const data = await response.json();
        console.log('[WP Proxy] User Digests - Success, returning data');
        
        return json(data);
        
    } catch (error) {
        console.error('[WP Proxy] User Digests - Fetch error:', error);
        return json({
            success: false,
            error: `Network error: ${error instanceof Error ? error.message : 'Unknown error'}`
        }, { status: 500 });
    }
} 