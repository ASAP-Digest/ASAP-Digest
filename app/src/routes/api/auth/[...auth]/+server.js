import { auth } from '$lib/server/auth';
import { svelteKitHandler } from 'better-auth/svelte-kit';

/** @type {import('@sveltejs/kit').RequestHandler} */
const handler = (event) => {
    return svelteKitHandler({
        event: {
            request: event.request,
            url: event.url
        },
        resolve: () => {},
        auth
    });
};

export const GET = handler;
export const POST = handler;
export const PUT = handler;
export const DELETE = handler;
export const PATCH = handler;
export const OPTIONS = handler; 