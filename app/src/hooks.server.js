import { auth } from '$lib/server/auth';
import { sequence } from '@sveltejs/kit/hooks';
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

/** @type {import('@sveltejs/kit').Handle} */
const betterAuthHandle = async ({ event, resolve }) => {
    try {
        // Ignore HMR routes and non-auth routes
        if (event.url.pathname.startsWith('/@') || 
            !event.url.pathname.startsWith('/api/auth/')) {
            return resolve(event);
        }
        
        const response = await auth.handler(event.request);
        if (response) return response;
        return resolve(event);
    } catch (error) {
        console.error('Auth error:', error);
        return resolve(event);
    }
};

/** @type {import('@sveltejs/kit').Handle} */
const wordPressSessionHandle = async ({ event, resolve }) => {
    try {
        // Skip WordPress session check for API and static routes
        if (event.url.pathname.startsWith('/api/') || 
            event.url.pathname.startsWith('/_app/') ||
            event.url.pathname.startsWith('/@')) {
            return resolve(event);
        }

        // Check for existing Better Auth session
        const authHeader = event.request.headers.get('Authorization');
        if (authHeader?.startsWith('Bearer ')) {
            // We have a Better Auth token, no need to check WordPress
            return resolve(event);
        }

        // Check for WordPress session
        const wpResponse = await fetch(`${getWordPressBaseURL()}/wp-json/asap/v1/auth/check-wp-session`, {
            headers: {
                cookie: event.request.headers.get('cookie') || ''
            }
        });

        if (wpResponse.ok) {
            const data = await wpResponse.json();
            if (data.sessionToken) {
                // Add the session token to the request headers
                const headers = new Headers(event.request.headers);
                headers.set('Authorization', `Bearer ${data.sessionToken}`);
                
                // Create a new request with the updated headers
                event.request = new Request(event.request.url, {
                    method: event.request.method,
                    headers,
                    body: event.request.body,
                    mode: event.request.mode,
                    credentials: event.request.credentials,
                    cache: event.request.cache,
                    redirect: event.request.redirect,
                    referrer: event.request.referrer,
                    integrity: event.request.integrity
                });
            }
        }

        return resolve(event);
    } catch (error) {
        console.error('WordPress session check error:', error);
        return resolve(event);
    }
};

/** @type {import('@sveltejs/kit').Handle} */
export const handle = sequence(betterAuthHandle, wordPressSessionHandle); 