import { redirect } from '@sveltejs/kit';

/** @type {import('./$types').LayoutLoad} */
export async function load({ parent }) {
    const data = await parent();
    
    if (!data.user) {
        throw redirect(303, '/login');
    }

    return {
        user: data.user
    };
} 