import { auth } from '$lib/server/auth';
import { sequence } from '@sveltejs/kit/hooks';
import { dev } from '$app/environment';
import { redirect } from '@sveltejs/kit';

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

/**
 * Validate CSRF token
 * @param {Request} request - The request object
 * @returns {boolean} - Whether the CSRF token is valid
 */
function validateCSRFToken(request) {
    const csrfToken = request.headers.get('X-CSRF-Token');
    const storedToken = request.headers.get('cookie')?.match(/csrf_token=([^;]+)/)?.[1];
    
    if (!csrfToken || !storedToken) {
        return false;
    }
    
    return csrfToken === storedToken;
}

/**
 * @typedef {Object} User
 * @property {string} id - User ID
 * @property {string} betterAuthId - Better Auth user ID
 * @property {string} displayName - User's display name
 * @property {string} email - User's email address
 * @property {string} avatarUrl - URL to user's avatar
 * @property {Array<string>} roles - User's roles
 * @property {string} syncStatus - User sync status
 * @property {string} [updatedAt] - Timestamp of last update (optional, from sync)
 */

/**
 * @typedef {Object} EventLocals
 * @property {User} [user] - User object if authenticated
 * @property {Object.<string, any>} [additionalProps] - Any additional properties
 */

/** @type {import('@sveltejs/kit').Handle} */
const betterAuthHandle = async ({ event, resolve }) => {
    try {
        // Only ignore Vite's internal HMR websocket connection
        if (event.url.pathname === '/@vite/client' || 
            event.url.pathname.startsWith('/@fs/')) {
            return resolve(event);
        }

        // Check CSRF token for mutating requests to auth endpoints
        if (event.url.pathname.startsWith('/api/auth/') && 
            ['POST', 'PUT', 'DELETE', 'PATCH'].includes(event.request.method)) {
            if (!validateCSRFToken(event.request)) {
                return new Response(JSON.stringify({ error: 'Invalid CSRF token' }), {
                    status: 403,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        }

        // Get session token from secure cookie
        const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
        if (sessionToken) {
            const headers = new Headers(event.request.headers);
            headers.set('Authorization', `Bearer ${sessionToken}`);
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

        // Handle auth routes with Better Auth
        if (event.url.pathname.startsWith('/api/auth/')) {
            try {
                const response = await auth.handler(event.request);
                if (response) return response;
            } catch (error) {
                console.error('Better Auth handler error:', error);
                return new Response(JSON.stringify({ error: 'Authentication error' }), {
                    status: 500,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        }
        
        return resolve(event);
    } catch (error) {
        console.error('Auth handle error:', error);
        return resolve(event);
    }
};

/** @type {import('@sveltejs/kit').Handle} */
const wordPressSessionHandle = async ({ event, resolve }) => {
    /** @type {App.Locals} */
    const locals = event.locals;
    console.log(`[hooks.server.js | WP Session] Request Path: ${event.url.pathname}`); // Log request path

    try {
        // Skip WordPress session check for API and static routes
        if (event.url.pathname.startsWith('/api/') || 
            event.url.pathname.startsWith('/_app/') ||
            event.url.pathname.startsWith('/@')) {
            console.log('[hooks.server.js | WP Session] Skipping WP check for API/Static route.'); // Log skip reason
            return resolve(event);
        }

        // Check for existing Better Auth session
        const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
        if (!sessionToken) {
            console.log('[hooks.server.js | WP Session] No better_auth_session cookie found. Resolving.'); // Log no token
            // No Better Auth session, continue without validation
            return resolve(event);
        }
        console.log('[hooks.server.js | WP Session] Found better_auth_session cookie.'); // Log token found

        // Initialize retry mechanism
        const maxRetries = 3;
        const retryDelay = 1000; // 1 second
        let lastError = null;

        for (let attempt = 0; attempt < maxRetries; attempt++) {
            console.log(`[hooks.server.js | WP Session] Attempt ${attempt + 1} to check WP session.`); // Log retry attempt
            try {
                // Check WordPress session with Better Auth token
                const wpApiUrl = `${getWordPressBaseURL()}/wp-json/asap/v1/auth/check-wp-session`;
                console.log(`[hooks.server.js | WP Session] Fetching: ${wpApiUrl}`); // Log fetch URL
                const wpResponse = await fetch(wpApiUrl, {
                    headers: {
                        'X-Better-Auth-Token': sessionToken,
                        cookie: event.request.headers.get('cookie') || ''
                    }
                });
                console.log(`[hooks.server.js | WP Session] WP Response Status: ${wpResponse.status}`); // Log response status

                if (!wpResponse.ok) {
                    console.warn(`[hooks.server.js | WP Session] WP check failed. Status: ${wpResponse.status}`); // Log failure
                    // Clear invalid session on 401/403
                    if (wpResponse.status === 401 || wpResponse.status === 403) {
                        console.log('[hooks.server.js | WP Session] Clearing invalid session cookie due to 401/403.'); // Log clearing reason
                        const headers = new Headers();
                        headers.append('Set-Cookie', 'better_auth_session=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT');
                        
                        // Don't redirect on API routes
                        if (!event.url.pathname.startsWith('/api/')) {
                            console.log('[hooks.server.js | WP Session] Redirecting to /login.'); // Log redirect
                            throw redirect(303, `/login?redirect=${encodeURIComponent(event.url.pathname)}`);
                        }
                    }
                    throw new Error(`WordPress session check failed: ${wpResponse.status}`);
                }

                const data = await wpResponse.json();
                console.log('[hooks.server.js | WP Session] WP Response Data:', data); // Log received data
                
                if (!data.valid) {
                    console.log('[hooks.server.js | WP Session] WP check returned invalid. Clearing session cookie.'); // Log invalid data reason
                    // Clear invalid session
                    const headers = new Headers();
                    headers.append('Set-Cookie', 'better_auth_session=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT');
                    
                    // Don't redirect on API routes
                    if (!event.url.pathname.startsWith('/api/')) {
                        console.log('[hooks.server.js | WP Session] Redirecting to /login.'); // Log redirect
                        throw redirect(303, `/login?redirect=${encodeURIComponent(event.url.pathname)}`);
                    }
                    return resolve(event);
                }

                // Add session token to request headers
                const headers = new Headers(event.request.headers);
                headers.set('Authorization', `Bearer ${sessionToken}`);
                
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

                // Add user data and sync status to event locals
                console.log('[hooks.server.js | WP Session] Populating event.locals.user.'); // Log population step
                /** @type {EventLocals} */
                const updatedLocals = {
                    ...locals,
                    user: {
                        id: String(data.user_id),
                        betterAuthId: data.better_auth_user_id,
                        displayName: data.display_name,
                        email: data.user_email,
                        avatarUrl: data.avatar_url,
                        roles: data.user_roles,
                        syncStatus: data.sync_status || 'unknown',
                        updatedAt: data.updatedAt
                    }
                };

                event.locals = updatedLocals;
                console.log('[hooks.server.js | WP Session] event.locals updated:', event.locals); // Log updated locals

                // Handle sync failures
                if (data.sync_status === 'sync_failed') {
                    console.error('[hooks.server.js | WP Session] User data sync failed - some data may be outdated');
                    // You might want to trigger a background sync retry here
                }

                console.log('[hooks.server.js | WP Session] WP session check successful. Resolving.'); // Log success
                return resolve(event);
            } catch (error) {
                lastError = error;
                console.error(`[hooks.server.js | WP Session] Error during attempt ${attempt + 1}:`, error); // Log error during attempt
                
                // Don't retry on redirects or clear session errors
                if (error instanceof Response || (error instanceof Error && error.message?.includes('clear session'))) {
                    throw error;
                }
                
                // Wait before retry
                if (attempt < maxRetries - 1) {
                    console.log(`[hooks.server.js | WP Session] Retrying after ${retryDelay}ms...`); // Log retry wait
                    await new Promise(resolve => setTimeout(resolve, retryDelay));
                }
            }
        }

        // If we get here, all retries failed
        console.error('[hooks.server.js | WP Session] WordPress session check failed after retries:', lastError);
        return resolve(event);
    } catch (error) {
        if (error instanceof Response) {
            throw error;
        }
        console.error('[hooks.server.js | WP Session] Global handle error:', error);
        return resolve(event);
    }
};

/** @type {import('@sveltejs/kit').Handle} */
const protectedRouteHandle = async ({ event, resolve }) => {
    try {
        // Skip protection for public routes and API endpoints
        if (event.url.pathname.startsWith('/api/') || 
            event.url.pathname.startsWith('/_app/') ||
            event.url.pathname.startsWith('/@') ||
            event.url.pathname === '/' ||
            event.url.pathname === '/login' ||
            event.url.pathname === '/register' ||
            event.url.pathname === '/forgot-password') {
            return resolve(event);
        }

        // Check if route is protected
        const isProtectedRoute = event.url.pathname.startsWith('/dashboard') || 
                               event.url.pathname.startsWith('/account') ||
                               event.url.pathname.startsWith('/settings');

        if (!isProtectedRoute) {
            return resolve(event);
        }

        // Get session token
        const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
        if (!sessionToken) {
            throw redirect(303, `/login?redirect=${encodeURIComponent(event.url.pathname)}`);
        }

        // Validate session with WordPress
        const wpResponse = await fetch(`${getWordPressBaseURL()}/wp-json/asap/v1/auth/check-wp-session`, {
            headers: {
                'X-Better-Auth-Token': sessionToken,
                cookie: event.request.headers.get('cookie') || ''
            }
        });

        if (!wpResponse.ok) {
            // Clear invalid session
            const headers = new Headers();
            headers.append('Set-Cookie', 'better_auth_session=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT');
            throw redirect(303, `/login?redirect=${encodeURIComponent(event.url.pathname)}`);
        }

        const data = await wpResponse.json();
        if (!data.valid) {
            throw redirect(303, `/login?redirect=${encodeURIComponent(event.url.pathname)}`);
        }

        // Add user data to event locals for use in routes
        /** @type {EventLocals} */
        const eventLocals = event.locals;
        eventLocals.user = {
            id: String(data.user_id),
            betterAuthId: data.better_auth_user_id,
            displayName: data.display_name,
            email: data.user_email,
            avatarUrl: data.avatar_url,
            roles: data.user_roles,
            syncStatus: data.sync_status || 'unknown'
        };

        return resolve(event);
    } catch (error) {
        if (error instanceof Response) {
            throw error;
        }
        console.error('Protected route error:', error);
        throw redirect(303, '/login');
    }
};

/** @type {import('@sveltejs/kit').Handle} */
export const handle = sequence(betterAuthHandle, wordPressSessionHandle, protectedRouteHandle); 