/**
 * Server endpoint to handle user logout
 * This is a client-side only operation since JWT tokens can't be invalidated on server
 */

import { auth } from '$lib/server/auth';
import { toSvelteKitHandler } from "better-auth/svelte-kit";

const handler = toSvelteKitHandler({
    handler: auth.handler,
    options: auth.options
});

/**
 * Handle logout POST request
 * @param {{ request: Request }} param0 Request object
 */
export const POST = handler; 