import { auth } from '$lib/server/auth';
import { svelteKitHandler } from 'better-auth/svelte-kit';

/** @type {import('@sveltejs/kit').Handle} */
export const handle = svelteKitHandler({ auth }); 