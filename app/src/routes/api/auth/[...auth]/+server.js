import { auth } from '$lib/server/auth';
import { toSvelteKitHandler } from "better-auth/svelte-kit";

export const handler = toSvelteKitHandler({ auth });

export const GET = handler;
export const POST = handler;
export const PUT = handler;
export const DELETE = handler; 