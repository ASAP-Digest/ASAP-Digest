/// <reference types="@sveltejs/kit" />

import mysql from 'mysql2/promise';
import { 
    DB_HOST, 
    DB_USER, 
    DB_PASS,
    DB_NAME,
    DB_PORT
} from '$env/static/private';
import { randomUUID } from 'node:crypto';
import { randomBytes } from 'node:crypto';
import { pool } from '$lib/server/auth';
import { redirect } from '@sveltejs/kit';
// /** @typedef {import('../sync-stream/+server.js')} SyncStreamModule */
// /** @type {SyncStreamModule['broadcastSyncUpdate']} */
// import { broadcastSyncUpdate } from '../sync-stream/+server.js'; // <-- Old incorrect path
import { broadcastSyncUpdate } from '$lib/server/syncBroadcaster'; // <-- Correct path

/**
 * @typedef {import('mysql2/promise').RowDataPacket} RowDataPacket
 */

/**
 * @typedef {Object} SyncResponse
 * @property {boolean} valid - Whether the sync was successful
 * @property {boolean} [updated] - Whether the user data was updated
 * @property {string} [error] - Error message if sync failed
 */

/**
 * Handles GET requests for session synchronization and token-based auto-login.
 *
 * If a 'token' query parameter is present, it attempts to validate a temporary
 * login token from WordPress and establish a new SvelteKit session, redirecting
 * the user upon success.
 *
 * If no 'token' is present, it checks existing WordPress browser cookies to
 * validate the session and potentially sync user data with SvelteKit locals.
 *
 * @param {object} event The SvelteKit request event object.
 * @param {Request} event.request The incoming request object.
 * @param {URL} event.url The URL object for the request.
 * @param {App.Locals} event.locals The SvelteKit request locals object.
 * @returns {Promise<Response>} A response (JSON or Redirect).
 */
/** @type {import('./$types').RequestHandler} */
export async function GET({ request, url, locals }) {
  console.debug('[Sync API] Received GET request for /api/auth/sync');
  let connection;
  const headers = new Headers({ 'Content-Type': 'application/json' });

  // --- Check for Token-Based Auto-Login ---
  const loginToken = url.searchParams.get('token');

  if (loginToken) {
    console.debug(`[Sync API] Login token found: ${loginToken}. Attempting token validation.`);
    try {
      connection = await pool.getConnection();
      console.debug('[Sync API] Acquired DB connection for token validation.');

      // 1. Validate token against ba_wp_login_tokens table
      // TODO: Implement database interaction for token validation
      //    - Query `ba_wp_login_tokens` for the token.
      //    - Check if it exists and hasn't expired.
      //    - Retrieve `wp_user_id`.
      //    - Delete the token after successful validation (single-use).
      // Placeholder validation logic:
      let wpUserId = null;
      let tokenIsValid = false;

      const tokenSql = 'SELECT wp_user_id FROM ba_wp_login_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1';
      const tokenParams = [loginToken];
      const [tokenRows] = await connection.execute(tokenSql, tokenParams);

      if (Array.isArray(tokenRows) && tokenRows.length > 0 && tokenRows[0] && typeof tokenRows[0] === 'object' && 'wp_user_id' in tokenRows[0]) {
          wpUserId = tokenRows[0].wp_user_id;
          tokenIsValid = true;
          console.debug(`[Sync API] Token validated for wpUserId: ${wpUserId}`);

          // Delete the used token
          const deleteSql = 'DELETE FROM ba_wp_login_tokens WHERE token = ?';
          await connection.execute(deleteSql, [loginToken]);
          console.debug(`[Sync API] Deleted used login token: ${loginToken}`);
      } else {
          console.warn(`[Sync API] Login token invalid or expired: ${loginToken}`);
          // Optionally redirect to a login error page?
          return new Response(JSON.stringify({ valid: false, error: 'Invalid or expired login token' }), { status: 401, headers });
      }

      if (tokenIsValid && wpUserId) {
        // 2. Find ba_user_id from ba_wp_user_map
        const mapSql = 'SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ? LIMIT 1';
        const mapParams = [wpUserId];
        const [mapRows] = await connection.execute(mapSql, mapParams);
        let ba_user_id = null;

        if (Array.isArray(mapRows) && mapRows.length > 0 && mapRows[0] && typeof mapRows[0] === 'object' && 'ba_user_id' in mapRows[0]) {
            ba_user_id = mapRows[0].ba_user_id;
            console.debug(`[Sync API] Found ba_user_id: ${ba_user_id} for wpUserId: ${wpUserId}`);

            // 3. Generate secure session token
            const session_token = randomBytes(32).toString('hex');
            const expires_at = new Date();
            expires_at.setDate(expires_at.getDate() + 30); // 30-day expiry

            // 4. Insert into ba_sessions
            const sessionSql = 'INSERT INTO ba_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)';
            const sessionParams = [ba_user_id, session_token, expires_at];
            await connection.execute(sessionSql, sessionParams);
            console.debug(`[Sync API] Inserted new session into ba_sessions for user: ${ba_user_id}`);

            // 5. Prepare Set-Cookie header
            const cookieName = 'better_auth_session';
            const cookieOptions = [
                `Path=/`,
                `Expires=${expires_at.toUTCString()}`,
                `HttpOnly`,
                `SameSite=Lax`
            ];
            if (process.env.NODE_ENV === 'production') {
                cookieOptions.push('Secure');
            }
            headers.set('Set-Cookie', `${cookieName}=${session_token}; ${cookieOptions.join('; ')}`);
            console.debug(`[Sync API] Set-Cookie header prepared for redirect: ${cookieName}=${session_token}; ${cookieOptions.join('; ')}`);

            // 6. Redirect to dashboard (or another target page)
            console.debug('[Sync API] Token login successful. Redirecting to /dashboard...');
            // Correct way to redirect with headers: return a Response
            const redirectHeaders = new Headers();
            redirectHeaders.set('Location', '/dashboard');
            const cookieHeaderValue = headers.get('Set-Cookie');
            if (cookieHeaderValue) {
                redirectHeaders.set('Set-Cookie', cookieHeaderValue);
            }
            return new Response(null, { status: 302, headers: redirectHeaders });
            // Original incorrect line: throw redirect(302, '/dashboard', { headers });

        } else {
          console.error(`[Sync API] CRITICAL: Could not find ba_user_id in ba_wp_user_map for wpUserId: ${wpUserId} during token login. Cannot create session.`);
          return new Response(JSON.stringify({ valid: false, error: 'User mapping error during login' }), { status: 500, headers });
        }
      }

    } catch (error) {
      console.error('[Sync API] Error during token-based login:', error);
      if (connection) {
         try { await connection.release(); } catch (relErr) { console.error('[Sync API] Error releasing connection in token catch:', relErr); }
      }
      // Don't redirect on error, return an error response
      return new Response(JSON.stringify({ valid: false, error: 'Server error during token login' }), { status: 500, headers });
    } finally {
      if (connection) {
          await connection.release();
          console.debug('[Sync API] Released DB connection after token processing.');
      }
    }
  }

  // --- Fallback to Cookie-Based Sync Check (Original Logic) ---
  console.debug('[Sync API] No login token found. Proceeding with cookie-based sync check.');
  try {
    // Extract cookies from request
    const cookies = request.headers.get('cookie') || '';
    console.debug('[Sync API] Extracted cookies:', cookies);

    // Find WordPress authentication cookies
    const wpCookies = cookies
      .split(';')
      .map(cookie => cookie.trim())
      .filter(cookie => cookie.startsWith('wordpress_logged_in_'));

    console.debug('[Sync API] Found WordPress cookies:', wpCookies);

    // Return early if no WordPress cookies found
    if (wpCookies.length === 0) {
      console.debug('[Sync API] No WordPress cookies found. Returning 401.');
      return new Response(
        JSON.stringify({
          valid: false,
          error: 'No WordPress session found'
        }), {
          status: 401,
          headers: { 'Content-Type': 'application/json' } // Keep original header for this path
        }
      );
    }

    /**
     * Get WordPress base URL based on environment
     * @returns {string} The WordPress base URL
     */
    const getWordPressBaseURL = () => {
      if (process.env.NODE_ENV === 'development') {
        return 'https://asapdigest.local';
      }
      return 'https://asapdigest.com';
    };
    const wpBaseURL = getWordPressBaseURL();
    const checkSessionURL = `${wpBaseURL}/wp-json/asap/v1/auth/check-wp-session`;
    console.debug(`[Sync API] Calling WordPress endpoint: ${checkSessionURL}`);

    // Validate WordPress session
    const response = await fetch(
      checkSessionURL,
      {
        headers: {
          'Cookie': wpCookies.join('; ')
        },
        credentials: 'include' // Important for sending cookies cross-origin
      }
    );

    console.debug('[Sync API] WordPress check-wp-session response status:', response.status);
    const data = await response.json();
    console.debug('[Sync API] WordPress check-wp-session response data:', data);

    // Handle unsuccessful response
    if (!response.ok) {
      console.debug('[Sync API] WordPress check-wp-session call failed or returned non-OK status. Returning error.');
      return new Response(
        JSON.stringify({
          valid: false,
          error: data.error || 'Failed to validate session'
        }), {
          status: response.status,
          headers: { 'Content-Type': 'application/json' } // Keep original header
        }
      );
    }

    // Handle not logged in state
    if (!data.loggedIn) {
      console.debug('[Sync API] WordPress check-wp-session indicates not logged in. Returning 401.');
      return new Response(
        JSON.stringify({
          valid: false,
          error: 'Not logged in to WordPress'
        }), {
          status: 401,
          headers: { 'Content-Type': 'application/json' } // Keep original header
        }
      );
    }

    // --- Start: [DEPRECATED/REVIEW] Auto-Login Session Creation Logic within Cookie Check ---
    // This logic here duplicates the token flow partially and might be redundant
    // or lead to unexpected session creation during simple sync checks.
    // Consider removing or refining this section if cookie check should *only* sync data.
    let ba_user_id_cookie = null;
    let session_token_cookie = null;
    let session_created_cookie = false;
    // Re-use headers defined at the top for JSON response
    // const headers = new Headers({ 'Content-Type': 'application/json' });

    if (data.userId) {
        const wpUserId_cookie = data.userId;
        // console.debug(`[Sync API - Cookie Check] WP Session Valid for wpUserId: ${wpUserId_cookie}. Potentially creating BA session if needed.`);

        // Check if SK session already exists (using locals)
        if (!locals.user) {
            console.debug('[Sync API - Cookie Check] No active SK session found (locals.user is null). Attempting session creation based on WP cookie.');
             try {
                connection = await pool.getConnection();
                console.debug('[Sync API - Cookie Check] Acquired DB connection from pool.');

                // 1. Find ba_user_id from ba_wp_user_map
                const mapSql_cookie = 'SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ? LIMIT 1';
                const mapParams_cookie = [wpUserId_cookie];
                const [mapRows_cookie] = await connection.execute(mapSql_cookie, mapParams_cookie);

                if (Array.isArray(mapRows_cookie) && mapRows_cookie.length > 0 && mapRows_cookie[0] && typeof mapRows_cookie[0] === 'object' && 'ba_user_id' in mapRows_cookie[0]) {
                    ba_user_id_cookie = mapRows_cookie[0].ba_user_id;
                    console.debug(`[Sync API - Cookie Check] Found ba_user_id: ${ba_user_id_cookie} for wpUserId: ${wpUserId_cookie}`);

                    // 2. Generate secure session token
                    session_token_cookie = randomBytes(32).toString('hex'); // 64 hex characters

                    // 3. Calculate expiry (e.g., 30 days)
                    const expires_at_cookie = new Date();
                    expires_at_cookie.setDate(expires_at_cookie.getDate() + 30);

                    // 4. Insert into ba_sessions
                    const sessionSql_cookie = `
                        INSERT INTO ba_sessions (user_id, session_token, expires_at)
                        VALUES (?, ?, ?)\n                    `;
                    const sessionParams_cookie = [ba_user_id_cookie, session_token_cookie, expires_at_cookie];
                    await connection.execute(sessionSql_cookie, sessionParams_cookie);
                    console.debug(`[Sync API - Cookie Check] Inserted new session into ba_sessions for user: ${ba_user_id_cookie}`);
                    session_created_cookie = true;

                    const cookieName_cookie = 'better_auth_session';
                    const cookieOptions_cookie = [
                        `Path=/`,
                        `Expires=${expires_at_cookie.toUTCString()}`,
                        `HttpOnly`,
                        `SameSite=Lax`
                    ];
                    if (process.env.NODE_ENV === 'production') {
                        cookieOptions_cookie.push('Secure');
                    }
                    // Add cookie to the JSON response headers
                    headers.set('Set-Cookie', `${cookieName_cookie}=${session_token_cookie}; ${cookieOptions_cookie.join('; ')}`);
                    console.debug(`[Sync API - Cookie Check] Set-Cookie header prepared: ${cookieName_cookie}=${session_token_cookie}; ${cookieOptions_cookie.join('; ')}`);

                } else {
                    console.error(`[Sync API - Cookie Check] CRITICAL: Could not find ba_user_id in ba_wp_user_map for wpUserId: ${wpUserId_cookie}. Cannot create session.`);
                }
            } catch (dbError) {
                console.error('[Sync API - Cookie Check] Database error during session creation:', dbError);
                // Ensure connection is released even on error
                if (connection) {
                    try { await connection.release(); } catch (relErr) { console.error('[Sync API - Cookie Check] Error releasing connection in DB catch:', relErr); }
                    connection = null; // Avoid double release in finally
                }
                // Return error for the sync check, don't create session
                return new Response(JSON.stringify({ valid: false, error: 'Database error during session creation check' }), { status: 500, headers });
            } finally {
                 if (connection) {
                    await connection.release();
                    console.debug('[Sync API - Cookie Check] Released DB connection back to pool.');
                 }
            }
        } else {
             console.debug('[Sync API - Cookie Check] Active SK session found (locals.user exists). Skipping session creation.');
        }


    } else {
        console.warn('[Sync API - Cookie Check] WP check response missing userId. Cannot attempt session creation.');
    }
    // --- End: [DEPRECATED/REVIEW] Auto-Login Session Creation Logic within Cookie Check ---

    // Update user data in locals (still useful even if session creation failed, for this request)
    /** @type {User|null} */
    const currentUser = locals.user || null;
    console.debug('[Sync API - Cookie Check] Current SvelteKit user data (locals.user):', currentUser);
    console.debug('[Sync API - Cookie Check] Data from WP (data):', data);

    // Check if data needs updating in SvelteKit locals for this request
    const userIdChanged = currentUser?.id !== data.better_auth_user_id;
    const updatedAtChanged = currentUser?.updatedAt !== data.updatedAt;
    console.debug(`[Sync API - Cookie Check] Checking for updates: userIdChanged=${userIdChanged}, updatedAtChanged=${updatedAtChanged}`);

    const updated = userIdChanged || updatedAtChanged;

    if (updated) {
      console.debug(`[Sync API - Cookie Check] Update detected (userId changed: ${userIdChanged}, timestamp changed: ${updatedAtChanged}). Updating locals.user...`);
      /** @type {User} */
      locals.user = {
        id: data.better_auth_user_id || ba_user_id_cookie || currentUser?.id || 'UNKNOWN', // Prioritize WP data, then newly created id, fallback to existing
        sessionToken: session_token_cookie || currentUser?.sessionToken, // Use new token if created
        betterAuthId: data.better_auth_user_id || ba_user_id_cookie || currentUser?.betterAuthId || 'UNKNOWN',
        displayName: data.display_name || '',
        email: data.user_email || '',
        avatarUrl: data.avatar_url || '',
        roles: data.user_roles || [],
        syncStatus: data.sync_status || (session_created_cookie ? 'synced' : currentUser?.syncStatus || 'unknown'),
        updatedAt: data.updatedAt
      };
      console.debug('[Sync API - Cookie Check] Updated locals.user:', locals.user);
    } else {
      console.debug('[Sync API - Cookie Check] No update detected.');
       // If session was just created but no other data changed, still populate locals
       if (session_created_cookie && ba_user_id_cookie && !currentUser) {
           console.debug('[Sync API - Cookie Check] Session created, populating locals.user with basic info.');
           locals.user = {
                id: ba_user_id_cookie,
                sessionToken: session_token_cookie || undefined,
                betterAuthId: ba_user_id_cookie,
                displayName: data.display_name || '',
                email: data.user_email || '',
                avatarUrl: data.avatar_url || '',
                roles: data.user_roles || [],
                syncStatus: 'synced',
                updatedAt: data.updatedAt
           };
           console.debug('[Sync API - Cookie Check] Updated locals.user:', locals.user);
       } else if (locals.user && session_token_cookie) {
           // If session was created but locals already existed (edge case?), update token
           locals.user.sessionToken = session_token_cookie;
           console.debug('[Sync API - Cookie Check] Updated existing locals.user with new session token.');
       }
    }

    console.debug(`[Sync API - Cookie Check] Returning response: { valid: true, updated: ${updated}, session_created: ${session_created_cookie} }`);
    // Return JSON response for cookie check, potentially with Set-Cookie if session was created
    return new Response(
      JSON.stringify({ valid: true, updated, session_created: session_created_cookie }), {
        headers: headers // Contains Content-Type and potentially Set-Cookie
      }
    );

  } catch (error) {
    console.error('[Sync API - Cookie Check] Error during sync processing:', error);
    // Ensure connection is released if acquired and error occurred before finally
    if (connection) {
        try { await connection.release(); } catch (relErr) { console.error('[Sync API - Cookie Check] Error releasing connection in main catch:', relErr); }
    }
    return new Response(
      JSON.stringify({
        valid: false,
        error: 'Server error during cookie sync'
      }), {
        status: 500,
        headers: { 'Content-Type': 'application/json' } // Keep original header
      }
    );
  } finally {
      // Final check for connection release in cookie path
      if (connection) {
          try { await connection.release(); } catch (relErr) { console.error('[Sync API - Cookie Check] Error releasing connection in finally:', relErr); }
      }
  }
}

/**
 * @typedef {object} WordPressUserData - Expected structure from fetchWordPressUserData
 * @property {string} [display_name]
 * @property {string} [user_email]
 * @property {string} [avatar_url]
 * @property {string[]} [user_roles]
 * @property {string} [updatedAt] // ISO 8601 string expected
 */

/**
 * Handles POST requests to synchronize user data from WordPress.
 * Expected payload: { wpUserId: number, userData: { displayName: string, email: string, avatarUrl?: string, ... } }
 * @param {object} event The SvelteKit request event object.
 * @param {Request} event.request The incoming request object.
 * @returns {Promise<Response>} JSON response indicating success or failure.
 */
/** @type {import('./$types').RequestHandler} */
export async function POST({ request }) {
  console.debug('[Sync API] Received POST request for /api/auth/sync');
  let connection;
  const headers = { 'Content-Type': 'application/json' };

  try {
    const body = await request.json();
    console.debug('[Sync API] POST request body:', body);

    const { wpUserId, userData } = body;

    if (!wpUserId || !userData) {
      console.warn('[Sync API] POST request missing wpUserId or userData.');
      return new Response(JSON.stringify({ success: false, error: 'Missing required data' }), { status: 400, headers });
    }

    connection = await pool.getConnection();
    console.debug('[Sync API] Acquired DB connection for POST processing.');

    // 1. Find ba_user_id from ba_wp_user_map
    const mapSql = 'SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ? LIMIT 1';
    const [mapRows] = await connection.execute(mapSql, [wpUserId]);

    /** @type {string | null} */
    let baUserId = null;
    if (Array.isArray(mapRows) && mapRows.length > 0 && mapRows[0] && typeof mapRows[0] === 'object' && 'ba_user_id' in mapRows[0]) {
        baUserId = mapRows[0].ba_user_id;
        console.debug(`[Sync API] Found ba_user_id: ${baUserId} for wpUserId: ${wpUserId}`);
    } else {
        console.error(`[Sync API] CRITICAL: Could not find ba_user_id in ba_wp_user_map for wpUserId: ${wpUserId}. Cannot sync user data.`);
        if (connection) await connection.release();
        return new Response(JSON.stringify({ success: false, error: 'User mapping not found' }), { status: 404, headers });
    }

    // 2. Update ba_users table
    const updateSql = 'UPDATE ba_users SET display_name = ?, email = ?, avatar_url = ?, updated_at = NOW() WHERE id = ?';
    const updateParams = [
        userData.displayName || null,
        userData.email || null,
        userData.avatarUrl || null,
        baUserId
    ];

    console.debug('[Sync API] Executing SQL to update ba_users:', updateSql, updateParams);
    const [updateResult] = await connection.execute(updateSql, updateParams);
    console.debug('[Sync API] Update result:', updateResult);

    // Type guard for updateResult
    if (updateResult && typeof updateResult === 'object' && 'affectedRows' in updateResult && updateResult.affectedRows > 0) {
        console.info(`[Sync API] Successfully updated ba_users for ba_user_id: ${baUserId}`);

        // ---> Call the broadcast function after successful update <---    
        broadcastSyncUpdate(baUserId);
        
        if (connection) await connection.release();
        return new Response(JSON.stringify({ success: true, message: 'User data synchronized' }), { status: 200, headers });
    } else if (updateResult && typeof updateResult === 'object' && 'affectedRows' in updateResult && updateResult.affectedRows === 0) {
        console.warn(`[Sync API] Update affected 0 rows for ba_user_id: ${baUserId}. User might not exist or data was identical.`);
        // Still broadcast even if no rows changed, as the intent was to sync
        broadcastSyncUpdate(baUserId); 
        if (connection) await connection.release();
        // Consider if 0 affected rows is truly success or requires a different response
        return new Response(JSON.stringify({ success: true, message: 'Sync completed, no data changed' }), { status: 200, headers });
    } else {
        console.error(`[Sync API] Failed to update ba_users for ba_user_id: ${baUserId}. Unexpected update result:`, updateResult);
        if (connection) await connection.release();
        return new Response(JSON.stringify({ success: false, error: 'Failed to update user data' }), { status: 500, headers });
    }

  } catch (error) {
    console.error('[Sync API] Error during POST sync:', error);
    if (connection) {
        try { await connection.release(); } catch (relErr) { console.error('[Sync API] Error releasing connection in POST catch:', relErr); }
    }
    return new Response(JSON.stringify({ success: false, error: 'Server error during sync' }), { status: 500, headers });
  }
}

/**
 * Placeholder function to simulate fetching user data from WordPress.
 * Replace with actual fetch call to a WP REST endpoint.
 * @param {number|string} wpUserId
 * @returns {Promise<WordPressUserData|null>} // Updated return type
 */
async function fetchWordPressUserData(wpUserId) {
    // ... existing placeholder implementation ...
    // Return placeholder data matching WordPressUserData type
    /** @type {WordPressUserData} */
    const placeholderData = {
        display_name: `WP User ${wpUserId}`,
        user_email: `wpuser${wpUserId}@example.com`,
        avatar_url: '',
        user_roles: ['subscriber'],
        updatedAt: new Date().toISOString() // Simulate fresh data
    };
    return placeholderData;
}
