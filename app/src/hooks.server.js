import { auth } from '$lib/server/auth';
import { sequence } from '@sveltejs/kit/hooks';
import { dev } from '$app/environment';
import { redirect } from '@sveltejs/kit';
import { BETTER_AUTH_SECRET } from '$env/static/private'; // Import the shared secret
import { pool } from '$lib/server/auth'; // Import the DB pool

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
        console.warn('[CSRF Validate] Missing CSRF token or cookie token.'); // DEBUG
        return false;
    }
    
    if (csrfToken !== storedToken) {
        console.warn('[CSRF Validate] CSRF token mismatch.'); // DEBUG
        return false;
    }
    
    return true;
}

/**
 * Validate the internal sync secret
 * @param {Request} request - The request object
 * @returns {boolean} - Whether the secret is valid
 */
function validateSyncSecret(request) {
    const receivedSecret = request.headers.get('X-WP-Sync-Secret');
    if (!receivedSecret) {
        console.warn('[Sync Secret Validate] Missing X-WP-Sync-Secret header.'); // DEBUG
        return false;
    }
    if (receivedSecret !== BETTER_AUTH_SECRET) {
        console.warn('[Sync Secret Validate] X-WP-Sync-Secret mismatch.'); // DEBUG
        return false;
    }
    return true;
}

/**
 * @typedef {import('mysql2/promise').PoolConnection} PoolConnection
 * @typedef {import('mysql2/promise').RowDataPacket} RowDataPacket
 * @typedef {import('mysql2/promise').OkPacket} OkPacket
 * @typedef {import('mysql2/promise').ResultSetHeader} ResultSetHeader
 */

/**
 * @typedef {Object} User - Defined locally for hook processing
 * @property {string} id - User ID (WP User ID)
 * @property {string} betterAuthId - Better Auth user ID (UUID)
 * @property {string | undefined} [displayName] - User's display name (Optional from DB)
 * @property {string | undefined} [email] - User's email address (Optional from DB)
 * @property {string | undefined} [avatarUrl] - URL to user's avatar (Optional)
 * @property {Array<string>} [roles] - User's roles
 * @property {string} [syncStatus] - User sync status (Optional)
 * @property {string} [updatedAt] - Timestamp of last update (ISO format)
 */

/**
 * @typedef {Object} EventLocals
 * @property {User} [user] - User object if authenticated
 * @property {Object.<string, any>} [additionalProps] - Any additional properties
 */

/** @type {import('@sveltejs/kit').Handle} */
const corsHandle = async ({ event, resolve }) => {
  // Define allowed origins (adjust for production)
  const allowedOrigin = dev ? 'http://localhost:5173' : 'https://app.asapdigest.com';
  const requestOrigin = event.request.headers.get('origin');

  // Default response (will be modified if it's a relevant CORS request)
  const response = await resolve(event);

  // Check if the request path is our sync endpoint and if origin matches
  if (event.url.pathname === '/api/auth/sync' && requestOrigin === allowedOrigin) {
    console.log(`[CORS Handle] Applying CORS headers for /api/auth/sync from origin: ${requestOrigin}`);
    response.headers.set('Access-Control-Allow-Origin', allowedOrigin);
    response.headers.set('Access-Control-Allow-Credentials', 'true');
    // Optionally add other headers like Allow-Methods, Allow-Headers if needed
    // response.headers.set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    // response.headers.set('Access-Control-Allow-Headers', 'Content-Type, X-CSRF-Token');
  }

  // Handle OPTIONS preflight requests for the sync endpoint
  if (event.request.method === 'OPTIONS' && event.url.pathname === '/api/auth/sync' && requestOrigin === allowedOrigin) {
    console.log(`[CORS Handle] Handling OPTIONS preflight for /api/auth/sync from origin: ${requestOrigin}`);
    return new Response(null, {
      status: 204, // No Content
      headers: {
        'Access-Control-Allow-Origin': allowedOrigin,
        'Access-Control-Allow-Credentials': 'true',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS', // Allow GET/POST if needed later
        'Access-Control-Allow-Headers': 'Content-Type, Cookie, X-CSRF-Token', // Allow necessary headers
        'Access-Control-Max-Age': '86400' // Cache preflight for 1 day
      }
    });
  }

  return response;
};

/** @type {import('@sveltejs/kit').Handle} */
const betterAuthHandle = async ({ event, resolve }) => {
    try {
        // Only ignore Vite's internal HMR websocket connection
        if (event.url.pathname === '/@vite/client' || 
            event.url.pathname.startsWith('/@fs/')) {
            return resolve(event);
        }

        // Check CSRF token OR Sync Secret for mutating requests to auth endpoints
        const isAuthPath = event.url.pathname.startsWith('/api/auth/');
        const isMutatingMethod = ['POST', 'PUT', 'DELETE', 'PATCH'].includes(event.request.method);
        const CORRECT_SYNC_PATH = '/api/auth/sync'; // Define constant for clarity
        const isSyncPath = event.url.pathname === CORRECT_SYNC_PATH; // Correct path check

        if (isAuthPath && isMutatingMethod) {
            let isAuthorized = false;
            let expectedAuthMethod = ''; // For logging/error message

            if (isSyncPath) {
                // For the specific internal sync path, check the shared secret
                console.log(`[Auth Handle] Checking sync secret for ${CORRECT_SYNC_PATH}`); // Correct log
                expectedAuthMethod = 'Sync Secret';
                isAuthorized = validateSyncSecret(event.request);
            } else {
                // For all other mutating auth paths, check the standard CSRF token
                console.log(`[Auth Handle] Checking CSRF token for ${event.url.pathname}`);
                expectedAuthMethod = 'CSRF Token';
                isAuthorized = validateCSRFToken(event.request);
            }

            if (!isAuthorized) {
                 // Use expectedAuthMethod for clearer error message
                 const errorMsg = `Invalid ${expectedAuthMethod}`;
                 console.warn(`[Auth Handle] ${errorMsg} for ${event.url.pathname}`);
                 return new Response(JSON.stringify({ error: errorMsg }), {
                    status: 403,
                    headers: { 'Content-Type': 'application/json' }
                 });
            } else {
                 console.log(`[Auth Handle] Authorization successful for ${event.url.pathname} using ${expectedAuthMethod}`);
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

        // Handle auth routes with Better Auth, *except* our custom sync route
        // which is handled by its own +server.js after secret validation.
        const isStandardAuthPath = event.url.pathname.startsWith('/api/auth/') && !isSyncPath;

        if (isStandardAuthPath) {
            console.log(`[Auth Handle] Passing standard auth path ${event.url.pathname} to auth.handler`); // DEBUG
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
        
        // Resolve the request (this will now correctly route /api/auth/sync)
        console.log(`[Auth Handle] Resolving request for ${event.url.pathname}`); // DEBUG
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
    /** @type {PoolConnection | undefined} */
    let skConnection;
    console.log(`[hooks.server.js | WP Session] Request Path: ${event.url.pathname}`);

    try {
        // Skip check for API, static, internal routes
        if (event.url.pathname.startsWith('/api/') || 
            event.url.pathname.startsWith('/_app/') ||
            event.url.pathname.startsWith('/@')) {
            console.log('[hooks.server.js | WP Session] Skipping check for API/Static/Internal route.');
            return resolve(event);
        }

        // Check for existing Better Auth session cookie
        const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
        if (!sessionToken) {
            console.log('[hooks.server.js | WP Session] No better_auth_session cookie found.');
            return resolve(event);
        }
        console.log('[hooks.server.js | WP Session] Found better_auth_session cookie.');

        // 1. Validate session token and get ba_user_id from SK database
        skConnection = await pool.getConnection();
        console.log('[hooks.server.js | SK Session] Acquired SK DB connection.');
        
        const sessionSql = `
            SELECT user_id 
            FROM ba_sessions 
            WHERE session_token = ? AND expires_at > NOW()
        `;
        /** @type {[ (RowDataPacket[] | OkPacket | ResultSetHeader), any ]} */
        const [sessionResult] = await skConnection.execute(sessionSql, [sessionToken]);
        
        let baUserId = null;
        // Type guard for sessionResult
        if (Array.isArray(sessionResult) && sessionResult.length > 0) {
             // Check the first element - assumes SELECT returns RowDataPacket[]
             const firstRow = sessionResult[0];
             if (firstRow && typeof firstRow === 'object' && 'user_id' in firstRow) {
                // @ts-ignore - Linter might still complain, but logic ensures it's RowDataPacket
                baUserId = firstRow.user_id;
                console.log(`[hooks.server.js | SK Session] Valid SK session found for ba_user_id: ${baUserId}`);
             } else {
                 // This case shouldn't happen if DB schema is correct & SELECT returns rows
                 console.warn('[hooks.server.js | SK Session] Session query returned array, but first element invalid.');
                 // Fall through to invalid session logic
             }
        }
        
        // If baUserId wasn't found (either query empty/invalid or first element check failed)
        if (!baUserId) {
            console.log('[hooks.server.js | SK Session] Invalid or expired SK session token (or unexpected query result). Clearing cookie.');
            const headers = new Headers();
            headers.append('Set-Cookie', 'better_auth_session=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT');
            if (skConnection) await skConnection.release();
            if (!event.url.pathname.startsWith('/api/')) {
                 console.log('[hooks.server.js | SK Session] Redirecting to /login due to invalid SK session.');
                 // Throw redirect needs headers attached to the response, not directly in the throw
                 const response = new Response(null, { status: 303, headers: { Location: `/login?redirect=${encodeURIComponent(event.url.pathname)}` } });
                 response.headers.append('Set-Cookie', 'better_auth_session=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT');
                 return response; // Return the response to execute redirect with cookie clearing
                 // Original: throw redirect(303, `/login?redirect=${encodeURIComponent(event.url.pathname)}`);
            }
            return resolve(event); // Resolve if API route
        }
        
        // 2. Fetch user data directly from ba_users using ba_user_id
        if (baUserId) {
            const userSql = `
                SELECT 
                    u.id as betterAuthId, 
                    u.display_name as displayName, 
                    u.email, 
                    u.avatar_url as avatarUrl, 
                    u.roles, 
                    u.sync_status as syncStatus, 
                    u.updated_at as updatedAt 
                FROM ba_users u 
                WHERE u.id = ?
            `;
            /** @type {[ (RowDataPacket[] | OkPacket | ResultSetHeader), any ]} */
            const [userResult] = await skConnection.execute(userSql, [baUserId]);
            
            if (Array.isArray(userResult) && userResult.length > 0) {
                const userData = userResult[0];
                if (userData && typeof userData === 'object') {
                    // @ts-ignore - Type assertion for userData
                    locals.user = {
                        id: locals.user?.id || baUserId, // Keep existing ID if somehow present, otherwise use baUserId
                        betterAuthId: baUserId,
                        // @ts-ignore
                        displayName: userData.displayName || undefined,
                        // @ts-ignore
                        email: userData.email || undefined,
                        // @ts-ignore
                        avatarUrl: userData.avatarUrl || undefined,
                        // @ts-ignore
                        roles: userData.roles ? JSON.parse(userData.roles) : [], // Assuming roles are stored as JSON string
                        // @ts-ignore
                        syncStatus: userData.syncStatus || 'unknown',
                        // @ts-ignore
                        updatedAt: userData.updatedAt ? new Date(userData.updatedAt).toISOString() : undefined, // Ensure ISO string
                        sessionToken: sessionToken // Include the session token
                    };
                     console.log('[hooks.server.js | SK User] Populated locals.user from SK DB:', locals.user); // DEBUG
                } else {
                    console.warn(`[hooks.server.js | SK User] User data query for ba_user_id ${baUserId} returned invalid data.`);
                }
            } else {
                 console.warn(`[hooks.server.js | SK User] No user found in ba_users for ba_user_id: ${baUserId}`);
            }
        } else {
            // This case should not be reachable if session validation logic is correct
            console.warn('[hooks.server.js | SK User] baUserId was null after session validation, cannot fetch user.');
        }

    } catch (error) {
        console.error('[hooks.server.js | WP Session] Error in session handle:', error);
    } finally {
        if (skConnection) {
            await skConnection.release();
            console.log('[hooks.server.js | SK Session] Released SK DB connection.');
        }
    }

    return resolve(event);
};

/** @type {import('@sveltejs/kit').Handle} */
const protectedRouteHandle = async ({ event, resolve }) => {
    /** @type {App.Locals} */
    const locals = event.locals;
    const user = locals.user; // User data populated by wordPressSessionHandle or previous hooks
    
    const protectedRoutes = [
        '/dashboard',
        '/settings',
        '/profile',
        '/billing',
        // Add other protected routes here
    ];
    
    const isAdminRoute = event.url.pathname.startsWith('/admin');
    /** @param {User | undefined | null} user */
    const requiresAdmin = (user) => user?.roles?.includes('administrator');

    // Check if the current path starts with any protected route
    const isProtectedRoute = protectedRoutes.some(route => event.url.pathname.startsWith(route));

    if ((isProtectedRoute || isAdminRoute) && !user) {
        // Redirect to login if trying to access protected/admin route without being logged in
        const redirectUrl = `/login?redirect=${encodeURIComponent(event.url.pathname)}`;
        console.log(`[Protected Route] No user found for ${event.url.pathname}. Redirecting to ${redirectUrl}`);
        throw redirect(303, redirectUrl);
    }
    
    if (isAdminRoute && user && !requiresAdmin(user)) {
        // Redirect to dashboard if trying to access admin route without admin role
        const redirectUrl = '/dashboard?error=forbidden';
        console.log(`[Protected Route] User lacks admin role for ${event.url.pathname}. Redirecting to ${redirectUrl}`);
        throw redirect(303, redirectUrl);
    }

    // If logged in and accessing root or login page, redirect to dashboard
    if (user && (event.url.pathname === '/' || event.url.pathname === '/login')) {
        const redirectUrl = '/dashboard';
        console.log(`[Protected Route] User logged in, redirecting from ${event.url.pathname} to ${redirectUrl}`);
        throw redirect(303, redirectUrl);
    }

    return resolve(event);
};

// Export the sequence including the new CORS handle *first*
export const handle = sequence(
  corsHandle, // Add CORS handling first
  betterAuthHandle,
  wordPressSessionHandle,
  protectedRouteHandle
); 