import { auth } from '$lib/server/auth';
import { toSvelteKitHandler } from "better-auth/svelte-kit";

export const handle = toSvelteKitHandler({ auth }); 