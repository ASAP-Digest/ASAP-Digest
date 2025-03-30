/**
 * Server endpoint to handle user registration
 * Proxies request to WordPress JWT auth endpoint
 */

import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth';
import { toSvelteKitHandler } from "better-auth/svelte-kit";

const handler = toSvelteKitHandler({
    handler: auth.handler,
    options: auth.options
});

/**
 * Handle registration POST request
 * @param {{ request: Request }} param0 Request object
 */
export const POST = handler; 