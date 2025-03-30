import { auth } from '$lib/server/auth';
import { sequence } from '@sveltejs/kit/hooks';

/** @type {import('@sveltejs/kit').Handle} */
const betterAuthHandle = async ({ event, resolve }) => {
    try {
        const response = await auth.handler(event.request);
        if (response) return response;
        return resolve(event);
    } catch (error) {
        console.error('Auth error:', error);
        return resolve(event);
    }
};

/** @type {import('@sveltejs/kit').Handle} */
export const handle = sequence(betterAuthHandle); 