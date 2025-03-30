import { auth } from '$lib/server/auth';
import { svelteKitHandler } from 'better-auth/svelte-kit';

/** @type {import('@sveltejs/kit').Handle} */
export async function handle({ event, resolve }) {
    return svelteKitHandler({ event, resolve, auth });
} 