// REMOVED 2025-05-16: Legacy auth import - Better Auth APIs now handled by GraphQL + wp-user-sync
// RESTORED: 2025-05-16 - Core Better Auth handling restored
import { auth } from '$lib/server/auth';
import { svelteKitHandler } from 'better-auth/svelte-kit';

// NOTE: This file handles standard Better Auth API routes like /api/auth/signin/email, 
// /api/auth/signout, /api/auth/session, etc., using the main 'auth' instance.
// It intentionally DOES NOT handle the custom /api/auth/wp-user-sync route,
// which has its own dedicated +server.js file.

// Updated to use the new GraphQL-based auth approach
// REMOVED: Placeholder message logic
// import { json } from '@sveltejs/kit';

/**
 * Handle GET, POST, PUT, DELETE, PATCH, OPTIONS requests for standard Better Auth routes.
 * Uses svelteKitHandler to integrate Better Auth with SvelteKit.
 * 
 * @type {import('@sveltejs/kit').RequestHandler}
 */
const handler = async (event) => {
    console.log(`[Auth Fallback Handler] Received ${event.request.method} request for: ${event.url.pathname}`);
    try {
        // Pass the event object correctly as per better-auth-route-handling.mdc
        const response = await svelteKitHandler({
            event: { 
                request: event.request,
                url: event.url
                // Ensure all required event properties are included if necessary
            },
            resolve: () => { 
                // Provide a basic resolve function. For dedicated auth routes,
                // returning an empty response or specific error might be appropriate
                // if the handler itself doesn't return.
                return new Response('Not Found', { status: 404 });
             },
            auth // Pass the initialized auth instance
        });

        // The handler might return null/undefined if it doesn't handle the route,
        // or if resolve is expected to produce the final response.
        if (response) {
            console.log(`[Auth Fallback Handler] svelteKitHandler processed ${event.url.pathname}. Status: ${response.status}`);
            return response;
        } else {
            // If svelteKitHandler didn't produce a response, return a standard 404 or appropriate error.
            console.log(`[Auth Fallback Handler] svelteKitHandler did not return a response for ${event.url.pathname}. Assuming unhandled.`);
            return new Response('Auth route not handled', { status: 404 });
        }

    } catch (error) {
        console.error(`[Auth Fallback Handler] Error processing ${event.url.pathname}:`, error);
        return new Response(JSON.stringify({ error: 'Internal Server Error during auth handling' }), {
            status: 500,
            headers: { 'Content-Type': 'application/json' }
        });
    }
};

// Export all methods pointing to the restored handler
export const GET = handler;
export const POST = handler;
export const PUT = handler;
export const DELETE = handler;
export const PATCH = handler;
export const OPTIONS = handler; 