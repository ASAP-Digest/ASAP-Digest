// REMOVED 2025-05-16: Legacy auth import - Better Auth APIs now handled by GraphQL + wp-user-sync
// import { auth } from '$lib/server/auth';
// import { toSvelteKitHandler } from 'better-auth/svelte-kit';

// Updated to use the new GraphQL-based auth approach
import { json } from '@sveltejs/kit';

/**
 * Handle GET requests to fallback auth endpoints
 * 
 * @param {object} event The request event object
 * @param {Record<string, string>} event.params Route parameters including auth path
 * @returns {Promise<Response>} JSON response with message
 */
export const GET = async (event) => {
    // Access params safely
    const authPath = event.params?.auth || 'unknown';
    return json({ 
        message: 'Authentication fallback endpoints now handled via GraphQL viewer query + wp-user-sync',
        path: authPath
    });
};

/**
 * Handle POST requests to fallback auth endpoints
 * 
 * @param {object} event The request event object
 * @param {Record<string, string>} event.params Route parameters including auth path
 * @returns {Promise<Response>} JSON response with message
 */
export const POST = async (event) => {
    // Access params safely
    const authPath = event.params?.auth || 'unknown';
    return json({ 
        message: 'Authentication fallback endpoints now handled via GraphQL viewer query + wp-user-sync',
        path: authPath
    });
};

/**
 * Handle PUT requests to fallback auth endpoints
 * 
 * @param {object} event The request event object
 * @param {Record<string, string>} event.params Route parameters including auth path
 * @returns {Promise<Response>} JSON response with message
 */
export const PUT = async (event) => {
    // Access params safely
    const authPath = event.params?.auth || 'unknown';
    return json({ 
        message: 'Authentication fallback endpoints now handled via GraphQL viewer query + wp-user-sync',
        path: authPath
    });
};

/**
 * Handle DELETE requests to fallback auth endpoints
 * 
 * @param {object} event The request event object
 * @param {Record<string, string>} event.params Route parameters including auth path
 * @returns {Promise<Response>} JSON response with message
 */
export const DELETE = async (event) => {
    // Access params safely
    const authPath = event.params?.auth || 'unknown';
    return json({ 
        message: 'Authentication fallback endpoints now handled via GraphQL viewer query + wp-user-sync',
        path: authPath
    });
};

/**
 * Handle PATCH requests to fallback auth endpoints
 * 
 * @param {object} event The request event object
 * @param {Record<string, string>} event.params Route parameters including auth path
 * @returns {Promise<Response>} JSON response with message
 */
export const PATCH = async (event) => {
    // Access params safely
    const authPath = event.params?.auth || 'unknown';
    return json({ 
        message: 'Authentication fallback endpoints now handled via GraphQL viewer query + wp-user-sync',
        path: authPath
    });
};

/**
 * Handle OPTIONS requests to fallback auth endpoints
 * 
 * @param {object} event The request event object
 * @param {Record<string, string>} event.params Route parameters including auth path
 * @returns {Promise<Response>} JSON response with message
 */
export const OPTIONS = async (event) => {
    // Access params safely
    const authPath = event.params?.auth || 'unknown';
    return json({ 
        message: 'Authentication fallback endpoints now handled via GraphQL viewer query + wp-user-sync',
        path: authPath
    });
}; 