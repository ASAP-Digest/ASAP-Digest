import { auth } from '$lib/server/auth';
import { svelteKitHandler } from 'better-auth/svelte-kit';

/** @type {import('@sveltejs/kit').RequestHandler} */
const handler = async (event) => {
    console.log(`[Auth Handler] Received ${event.request.method} request for: ${event.url.pathname}`);

    try {
        const response = await svelteKitHandler({
            event: {
                request: event.request,
                url: event.url
            },
            resolve: () => {},
            auth
        });

        console.log(`[Auth Handler] svelteKitHandler successful for ${event.url.pathname}. Status: ${response.status}`);

        return response;
    } catch (error) {
        console.error(`[Auth Handler] Error processing ${event.url.pathname}:`, error);
        
        return new Response(JSON.stringify({ error: 'Internal Server Error during auth handling' }), {
            status: 500,
            headers: { 'Content-Type': 'application/json' }
        });
    }
};

export const GET = handler;
export const POST = handler;
export const PUT = handler;
export const DELETE = handler;
export const PATCH = handler;
export const OPTIONS = handler; 