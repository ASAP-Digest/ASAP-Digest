import { auth } from '$lib/server/auth';
import { toSvelteKitHandler } from "better-auth/svelte-kit";

const handler = toSvelteKitHandler({
    handler: auth.handler,
    options: auth.options
});

export const POST = handler; 