import { json } from '@sveltejs/kit';
import { dev } from '$app/environment';

/**
 * Get WordPress base URL
 * @returns {string} WordPress base URL
 */
function getWordPressBaseURL() {
    if (dev) {
        return 'https://asapdigest.local';
    }
    return 'https://asapdigest.com';
}

/** @type {import('./$types').RequestHandler} */
export async function GET({ request, locals }) {
    try {
        // Get session token
        const sessionToken = request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
        if (!sessionToken) {
            return json({ error: 'No session token found' }, { status: 401 });
        }

        // Check WordPress session and sync user data
        const wpResponse = await fetch(`${getWordPressBaseURL()}/wp-json/asap/v1/auth/check-wp-session`, {
            headers: {
                'X-Better-Auth-Token': sessionToken,
                cookie: request.headers.get('cookie') || ''
            }
        });

        if (!wpResponse.ok) {
            return json({ error: 'Failed to validate session' }, { status: 401 });
        }

        const data = await wpResponse.json();
        if (!data.valid) {
            return json({ error: 'Invalid session' }, { status: 401 });
        }

        // Check if user data has changed
        const currentUser = locals.user;
        const updated = !currentUser ||
            currentUser.displayName !== data.display_name ||
            currentUser.email !== data.email ||
            currentUser.avatarUrl !== data.avatar_url ||
            JSON.stringify(currentUser.roles) !== JSON.stringify(data.roles);

        // Update locals if needed
        if (updated) {
            locals.user = {
                id: data.user_id,
                betterAuthId: data.better_auth_user_id,
                displayName: data.display_name,
                email: data.email,
                avatarUrl: data.avatar_url,
                roles: data.roles
            };
        }

        return json({
            valid: true,
            updated,
            user: locals.user
        });
    } catch (error) {
        console.error('Sync error:', error);
        return json({ error: 'Sync failed' }, { status: 500 });
    }
} 