// REMOVED 2025-05-16: Legacy auth import - Better Auth APIs now handled by GraphQL + wp-user-sync
// import { auth } from '$lib/server/auth';
import { sequence } from '@sveltejs/kit/hooks';
import { dev } from '$app/environment';
import { redirect } from '@sveltejs/kit';
import { BETTER_AUTH_SECRET } from '$env/static/private'; // Import the shared secret
import { pool } from '$lib/server/auth'; // Import the DB pool
import { syncUserToWordPress } from '$lib/server/wp-sync';

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
 * Import database types from mysql2/promise
 * @typedef {import('mysql2/promise').PoolConnection} PoolConnection
 * @typedef {import('mysql2/promise').RowDataPacket} RowDataPacket
 * @typedef {import('mysql2/promise').OkPacket} OkPacket
 * @typedef {import('mysql2/promise').ResultSetHeader} ResultSetHeader
 */

/**
 * User model for hook processing
 * @typedef {Object} User
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
 * Extended RequestInit interface with duplex property
 * @typedef {RequestInit & {duplex: 'half'}} RequestInitWithDuplex
 */

/**
 * SvelteKit event locals extension for hooks
 * @typedef {Object} EventLocals
 * @property {User} [user] - User object if authenticated
 * @property {Object.<string, any>} [additionalProps] - Any additional properties
 */

/**
 * SvelteKit handle function for CORS
 * @param {object} params - Handle parameters
 * @param {import('@sveltejs/kit').RequestEvent & { locals: App.Locals }} params.event - The request event
 * @param {Function} params.resolve - The resolve function
 * @returns {Promise<Response>} - The response
 */
const corsHandle = async ({ event, resolve }) => {
  // Define allowed origins (adjust for production)
  const allowedOrigin = dev ? 'https://localhost:5173' : 'https://app.asapdigest.com';
  const requestOrigin = event.request.headers.get('origin');
  const isSyncPath = event.url.pathname === '/api/auth/sync';

  console.log(`[CORS Handle Entry] Path: ${event.url.pathname}, Method: ${event.request.method}, Origin: ${requestOrigin}`); // DEBUG

  // Handle OPTIONS preflight requests FIRST
  if (isSyncPath && event.request.method === 'OPTIONS') {
    if (requestOrigin === allowedOrigin) {
      console.log(`[CORS Handle] Handling OPTIONS preflight for ${event.url.pathname} from allowed origin: ${requestOrigin}`); // DEBUG
      return new Response(null, {
        status: 204, // No Content
        headers: {
          'Access-Control-Allow-Origin': allowedOrigin,
          'Access-Control-Allow-Credentials': 'true',
          'Access-Control-Allow-Methods': 'GET, POST, OPTIONS', // Allow necessary methods
          'Access-Control-Allow-Headers': 'Content-Type, Cookie, X-CSRF-Token, Authorization', // Allow necessary headers
          'Access-Control-Max-Age': '86400' // Cache preflight for 1 day
        }
      });
    } else {
      console.warn(`[CORS Handle] OPTIONS request received for ${event.url.pathname} from DISALLOWED origin: ${requestOrigin}`); // DEBUG
      // Return a generic response for disallowed origins in preflight
      return new Response('CORS Origin Not Allowed', { status: 403 });
    }
  }

  // Resolve the request for non-OPTIONS calls
  const response = await resolve(event);

  // Apply CORS headers to the actual response for the sync path if origin is allowed
  if (isSyncPath && requestOrigin === allowedOrigin) {
    console.log(`[CORS Handle] Applying CORS headers to actual response for ${event.url.pathname} from origin: ${requestOrigin}`); // DEBUG
    response.headers.set('Access-Control-Allow-Origin', allowedOrigin);
    response.headers.set('Access-Control-Allow-Credentials', 'true');
    // Optional: Add Vary header if your origin logic becomes dynamic
    // response.headers.append('Vary', 'Origin');
  } else if (isSyncPath) {
      console.warn(`[CORS Handle] Actual request received for ${event.url.pathname} from DISALLOWED origin: ${requestOrigin}. Not adding CORS headers.`); // DEBUG
  }

  return response;
};

/**
 * SvelteKit handle function for authentication
 * @param {object} params - Handle parameters
 * @param {import('@sveltejs/kit').RequestEvent & { locals: App.Locals }} params.event - The request event
 * @param {Function} params.resolve - The resolve function
 * @returns {Promise<Response>} - The response
 */
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
        const VERIFY_TOKEN_PATH = '/api/auth/verify-sync-token';
        // Add new constant for the check-wp-session path
        const CHECK_WP_SESSION_PATH = '/api/auth/check-wp-session';
        const isCheckWpSession = event.url.pathname === CHECK_WP_SESSION_PATH;
        
        // Add new constant for the profile path
        const PROFILE_PATH = '/api/auth/profile';
        const isProfilePath = event.url.pathname === PROFILE_PATH;
        
        // Enhanced debug logging for auth requests
        if (isAuthPath) {
            console.log(`[Auth Handle] Processing ${event.request.method} request for ${event.url.pathname}`);
            console.log(`[Auth Handle] Headers:`, Object.fromEntries([...event.request.headers.entries()]));
            console.log(`[Auth Handle] Cookie header:`, event.request.headers.get('cookie'));
            console.log(`[Auth Handle] CSRF token:`, event.request.headers.get('X-CSRF-Token'));
        }
        
        // Special handling for profile endpoint - always check session
        if (isProfilePath && isMutatingMethod) {
            console.log(`[Auth Handle] Special handling for ${PROFILE_PATH}: checking for session cookie`);
            
            // Check for session token
            const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
            if (!sessionToken) {
                console.warn(`[Auth Handle] No session token found for ${PROFILE_PATH}`);
                return new Response(JSON.stringify({ 
                    error: 'Authentication required',
                    success: false 
                }), {
                    status: 401,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
            
            // Set the Authorization header from the session token cookie
            const headers = new Headers(event.request.headers);
            headers.set('Authorization', `Bearer ${sessionToken}`);
            
            /**
             * Create a new Request with modified headers
             * @type {RequestInitWithDuplex}
             */
            const requestOptions = {
                method: event.request.method,
                headers,
                body: event.request.body,
                duplex: "half", // Type assertion handled via JSDoc - Required for streaming request bodies
                mode: event.request.mode,
                credentials: event.request.credentials,
                cache: event.request.cache,
                redirect: event.request.redirect,
                referrer: event.request.referrer,
                integrity: event.request.integrity
            };
            
            // Create a new request with the updated headers
            event.request = new Request(event.request.url, requestOptions);
            
            // Profile path also requires CSRF token validation
            const isAuthorized = validateCSRFToken(event.request);
            if (!isAuthorized) {
                console.warn(`[Auth Handle] Invalid CSRF token for ${PROFILE_PATH}`);
                return new Response(JSON.stringify({ 
                    error: 'Invalid CSRF token',
                    success: false 
                }), {
                    status: 403,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
            
            console.log(`[Auth Handle] Session token and CSRF token validated for ${PROFILE_PATH}`);
        }
        
        // Special handling for check-wp-session endpoint - always allow without CSRF
        if (isCheckWpSession && event.request.method === 'POST') {
            console.log(`[Auth Handle] Special handling for ${CHECK_WP_SESSION_PATH}: bypassing CSRF check`);
            
            // Set session token from cookie if available
            const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
            if (sessionToken) {
                const headers = new Headers(event.request.headers);
                headers.set('Authorization', `Bearer ${sessionToken}`);
                
                /**
                 * Create a new Request with modified headers
                 * @type {RequestInitWithDuplex}
                 */
                const requestOptions = {
                    method: event.request.method,
                    headers,
                    body: event.request.body,
                    duplex: "half", // Type assertion handled via JSDoc - Required for streaming request bodies
                    mode: event.request.mode,
                    credentials: event.request.credentials,
                    cache: event.request.cache,
                    redirect: event.request.redirect,
                    referrer: event.request.referrer,
                    integrity: event.request.integrity
                };
                
                event.request = new Request(event.request.url, requestOptions);
            }
            
            // Skip other auth checks and proceed directly
            console.log(`[Auth Handle] Resolving request for ${event.url.pathname}`);
            return resolve(event);
        }

        if (isAuthPath && isMutatingMethod && !isProfilePath && !isCheckWpSession) {
            let isAuthorized = false;
            let expectedAuthMethod = ''; // For logging/error message

            // Check for explicit CSRF bypass header (only for specific endpoints)
            const bypassCSRF = event.request.headers.get('X-CSRF-Protection') === 'none';

            if (event.url.pathname === VERIFY_TOKEN_PATH && event.request.method === 'POST') {
                // For the token verification endpoint from the bridge, bypass standard CSRF/Secret check.
                // The security relies on the short-lived, single-use nature of the sync token itself.
                console.log(`[Auth Handle] Allowing POST to ${VERIFY_TOKEN_PATH} without CSRF/Secret check (Bridge Token Verification).`);
                isAuthorized = true; // Assume authorized here; validation happens in the endpoint
                expectedAuthMethod = 'Bridge Token Verification'; // Log this specific flow
            } else if (isSyncPath) {
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

            if (!isAuthorized && expectedAuthMethod !== 'Bridge Token Verification') { // Don't block if it's the bridge verification path
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

        // Get session token from secure cookie for all requests
        const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
        if (sessionToken) {
            console.log(`[Auth Handle] Found session token in cookie for ${event.url.pathname}, length: ${sessionToken.length}`);
            const headers = new Headers(event.request.headers);
            headers.set('Authorization', `Bearer ${sessionToken}`);
            
            /**
             * Create a new Request with modified headers
             * @type {RequestInitWithDuplex}
             */
            const requestOptions = {
                method: event.request.method,
                headers,
                body: event.request.body,
                duplex: "half", // Type assertion handled via JSDoc - Required for streaming request bodies
                mode: event.request.mode,
                credentials: event.request.credentials,
                cache: event.request.cache,
                redirect: event.request.redirect,
                referrer: event.request.referrer,
                integrity: event.request.integrity
            };
            
            event.request = new Request(event.request.url, requestOptions);
        }

        // Resolve the request (this will now correctly route all auth-related endpoints)
        console.log(`[Auth Handle] Resolving request for ${event.url.pathname}`); // DEBUG
        return resolve(event);
    } catch (error) {
        console.error('Auth handle error:', error);
        return resolve(event);
    }
};

/**
 * SvelteKit handle function for WordPress session validation
 * @param {object} params - Handle parameters 
 * @param {import('@sveltejs/kit').RequestEvent & { locals: App.Locals }} params.event - The request event
 * @param {Function} params.resolve - The resolve function
 * @returns {Promise<Response>} - The response
 */
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

        // UPDATED 2025-05-16: Now part of v3 auth flow using GraphQL viewer query + wp-user-sync
        // Check for existing Better Auth session cookie
        const sessionToken = event.request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
        if (!sessionToken) {
            console.log('[hooks.server.js | WP Session] No better_auth_session cookie found.');
            // No Better Auth session - this is now handled by frontend GraphQL viewer query
            // If WP session exists, frontend will call wp-user-sync endpoint
            return resolve(event);
        }

        console.log(`[hooks.server.js | WP Session] Found better_auth_session cookie. Token length: ${sessionToken.length}`);

        // When Better Auth session exists, check if it's valid and set user in locals
        try {
            skConnection = await pool.getConnection();
            const sessionQuery = `
                SELECT 
                    s.id as session_id, 
                    s.user_id, 
                    s.token, 
                    s.expires_at,
                    u.id,
                    u.email, 
                    u.username,
                    u.name,
                    u.metadata
                FROM 
                    ba_sessions s
                JOIN 
                    ba_users u ON s.user_id = u.id
                WHERE 
                    s.token = ?
                    AND s.expires_at > NOW()
                LIMIT 1
            `;
            
            console.log(`[hooks.server.js | WP Session] Executing session query with token: ${sessionToken.substring(0, 5)}...`);
            
            /** @type {[RowDataPacket[], import('mysql2/promise').FieldPacket[]]} */
            const [rows, fields] = await skConnection.execute(sessionQuery, [sessionToken]);
            
            console.log(`[hooks.server.js | WP Session] Query returned ${rows.length} rows.`);
            
            // Use proper type checking before accessing rows
            if (Array.isArray(rows) && rows.length > 0) {
                const sessionRow = rows[0];
                if (sessionRow && typeof sessionRow === 'object') {
                    console.log('[hooks.server.js | WP Session] Valid session found:', sessionRow.session_id);
                    
                    // Parse metadata if needed
                    let metadata = sessionRow.metadata;
                    if (typeof metadata === 'string') {
                        try {
                            metadata = JSON.parse(metadata);
                        } catch (e) {
                            console.warn('[hooks.server.js | WP Session] Error parsing metadata:', e);
                            metadata = {};
                        }
                    }
                    
                    // Apply consistent type definitions according to app.d.ts
                    // Apply Local Variable Type Safety for roles
                    const roles = Array.isArray(metadata?.roles) ? metadata.roles : ['subscriber'];
                    
                    // Ensure email is always a string to satisfy type requirements
                    const userEmail = typeof sessionRow.email === 'string' ? sessionRow.email : '';
                    
                    // Set user in locals (using App.Locals.user type)
                    locals.user = {
                        id: sessionRow.id,
                        email: userEmail,
                        displayName: sessionRow.name || sessionRow.username || userEmail,
                        roles: roles,
                        metadata: metadata || {},
                        wp_user_id: metadata?.wp_user_id || null // Add wp_user_id directly to user object
                        // Avoid adding betterAuthId directly as it's not part of App.User type
                    };
                    
                    // Ensure token is always a string to satisfy type requirements
                    const sessionTokenValue = typeof sessionRow.token === 'string' ? sessionRow.token : '';
                    
                    // Set session in locals according to App.Locals.session type
                    locals.session = {
                        userId: sessionRow.user_id,
                        token: sessionTokenValue,
                        expiresAt: new Date(sessionRow.expires_at).toISOString()
                    };
                    
                    console.log('[hooks.server.js | WP Session] User data set in locals:', locals.user?.id, locals.user?.email);
                } else {
                    console.log('[hooks.server.js | WP Session] Session row found but invalid format:', sessionRow);
                }
            } else {
                // Invalid or expired session
                console.log('[hooks.server.js | WP Session] No valid session found for token:', 
                    sessionToken.length > 5 ? `${sessionToken.substring(0, 5)}...` : 'INVALID');
                // Clear the invalid cookie
                event.cookies.delete('better_auth_session', { 
                    path: '/', 
                    httpOnly: true, 
                    secure: true,
                    sameSite: 'strict'
                });
            }
        } catch (dbError) {
            console.error('[hooks.server.js | WP Session] Database error validating session:', dbError);
        } finally {
            if (skConnection) {
                console.log('[hooks.server.js | WP Session] Releasing database connection');
                skConnection.release();
            }
        }
        
        return resolve(event);
    } catch (error) {
        console.error('[hooks.server.js | WP Session] Unhandled error:', error);
        if (skConnection) {
            console.log('[hooks.server.js | WP Session] Releasing database connection after error');
            skConnection.release();
        }
        return resolve(event);
    }
};

/** 
 * Handle protected route access control
 * 
 * @param {object} params - Handle parameters
 * @param {import('@sveltejs/kit').RequestEvent & { locals: App.Locals }} params.event - The request event
 * @param {Function} params.resolve - The resolve function
 * @returns {Promise<Response>} - The response
 */
const protectedRouteHandle = async ({ event, resolve }) => {
    /** @type {App.Locals} */
    const locals = event.locals;
    /** @type {App.User|undefined} */
    const user = locals.user; // User data populated by wordPressSessionHandle or previous hooks
    
    const protectedRoutes = [
        '/dashboard',
        '/settings',
        '/profile',
        '/billing',
        // Add other protected routes here
    ];
    
    const isAdminRoute = event.url.pathname.startsWith('/admin');
    
    /**
     * Check if user has administrator role
     * @param {App.User | undefined | null} user The user to check
     * @returns {boolean} Whether the user has admin role
     */
    const requiresAdmin = (user) => {
        // Use proper type checking before accessing roles
        if (!user || !user.roles || !Array.isArray(user.roles)) {
            return false;
        }
        return user.roles.includes('administrator');
    };

    // IMPORTANT: Allow access to root path even if logged in - prevents auto redirection
    if (event.url.pathname === '/' && user) {
        console.log(`[Protected Route] User is logged in and accessing root path. Allowing access without redirect.`);
        return resolve(event);
    }

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

    return resolve(event);
};

// Export the sequence including the new CORS handle *first*
export const handle = sequence(
  corsHandle, // Add CORS handling first
  betterAuthHandle,
  wordPressSessionHandle,
  protectedRouteHandle
); 