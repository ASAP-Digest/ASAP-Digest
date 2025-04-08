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

/**
 * @typedef {Object} SyncResponse
 * @property {boolean} valid - Whether the sync was successful
 * @property {boolean} [updated] - Whether the user data was updated
 * @property {string} [error] - Error message if sync failed
 */

/** 
 * Synchronize WordPress session status with the SvelteKit session.
 * Primarily checks if the WP user associated with browser cookies is still valid 
 * and potentially updates SvelteKit's locals.user if changes are detected.
 * 
 * @param {object} event The SvelteKit request event object.
 * @param {Request} event.request The incoming request object, used to access cookies.
 * @param {App.Locals} event.locals The SvelteKit request locals object, containing the current session user data.
 * @returns {Promise<Response>} A response indicating if the session is valid and if user data was updated.
 */
/** @type {import('./$types').RequestHandler} */
export async function GET({ request, locals }) {
  console.debug('[Sync API] Received GET request for /api/auth/sync');
  let connection;
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
          headers: { 'Content-Type': 'application/json' }
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
        credentials: 'include'
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
          headers: { 'Content-Type': 'application/json' }
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
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }

    // --- Start: Auto-Login Session Creation Logic ---
    let ba_user_id = null;
    let session_token = null;
    let session_created = false;
    const headers = new Headers({ 'Content-Type': 'application/json' });

    if (data.userId) {
        const wpUserId = data.userId;
        console.debug(`[Sync API] WP Session Valid for wpUserId: ${wpUserId}. Attempting to create BA session.`);

        try {
            connection = await pool.getConnection();
            console.debug('[Sync API] Acquired DB connection from pool.');

            // 1. Find ba_user_id from ba_wp_user_map
            const mapSql = 'SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ? LIMIT 1';
            const mapParams = [wpUserId];
            const [mapRows] = await connection.execute(mapSql, mapParams);

            if (Array.isArray(mapRows) && mapRows.length > 0 && mapRows[0] && typeof mapRows[0] === 'object' && 'ba_user_id' in mapRows[0]) {
                ba_user_id = mapRows[0].ba_user_id;
                console.debug(`[Sync API] Found ba_user_id: ${ba_user_id} for wpUserId: ${wpUserId}`);

                // 2. Generate secure session token
                session_token = randomBytes(32).toString('hex'); // 64 hex characters

                // 3. Calculate expiry (e.g., 30 days)
                const expires_at = new Date();
                expires_at.setDate(expires_at.getDate() + 30);

                // 4. Insert into ba_sessions
                const sessionSql = `
                    INSERT INTO ba_sessions (user_id, session_token, expires_at) 
                    VALUES (?, ?, ?)
                `;
                const sessionParams = [ba_user_id, session_token, expires_at];
                await connection.execute(sessionSql, sessionParams); 
                console.debug(`[Sync API] Inserted new session into ba_sessions for user: ${ba_user_id}`);
                session_created = true;

                const cookieName = 'asap_session';
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
                console.debug(`[Sync API] Set-Cookie header prepared: ${cookieName}=${session_token}; ${cookieOptions.join('; ')}`);

            } else {
                console.error(`[Sync API] CRITICAL: Could not find ba_user_id in ba_wp_user_map for wpUserId: ${wpUserId}. Cannot create session.`);
            }
        } catch (dbError) {
            console.error('[Sync API] Database error during session creation:', dbError);
            if (connection) await connection.release();
            return new Response(JSON.stringify({ valid: false, error: 'Database error during session creation' }), { status: 500, headers: { 'Content-Type': 'application/json' }});
        } finally {
             if (connection) {
                await connection.release();
                console.debug('[Sync API] Released DB connection back to pool.');
             }
        }
    } else {
        console.warn('[Sync API] WP check response missing userId. Cannot attempt session creation.');
    }
    // --- End: Auto-Login Session Creation Logic ---

    // Update user data in locals (still useful even if session creation failed, for this request)
    /** @type {User|null} */
    const currentUser = locals.user || null;
    console.debug('[Sync API] Current SvelteKit user data (locals.user):', currentUser);
    console.debug('[Sync API] Data from WP (data):', data);

    const userIdChanged = currentUser?.id !== data.better_auth_user_id;
    const updatedAtChanged = currentUser?.updatedAt !== data.updatedAt;
    console.debug(`[Sync API] Checking for updates: userIdChanged=${userIdChanged}, updatedAtChanged=${updatedAtChanged}`);
    
    const updated = userIdChanged || updatedAtChanged;

    if (updated) {
      console.debug(`[Sync API] Update detected (userId changed: ${userIdChanged}, timestamp changed: ${updatedAtChanged}). Updating locals.user...`);
      /** @type {User} */
      locals.user = {
        id: data.better_auth_user_id || ba_user_id || 'UNKNOWN',
        sessionToken: session_token || undefined,
        betterAuthId: data.better_auth_user_id || ba_user_id || 'UNKNOWN',
        displayName: data.display_name || '',
        email: data.user_email || '',
        avatarUrl: data.avatar_url || '',
        roles: data.user_roles || [],
        syncStatus: data.sync_status || (session_created ? 'synced' : 'error'),
        updatedAt: data.updatedAt
      };
      console.debug('[Sync API] Updated locals.user:', locals.user);
    } else {
      console.debug('[Sync API] No update detected.');
       if (session_created && ba_user_id && !locals.user) {
           console.debug('[Sync API] Session created, populating locals.user with basic info.');
           locals.user = {
                id: ba_user_id,
                sessionToken: session_token || undefined,
                betterAuthId: ba_user_id, 
                displayName: data.display_name || '',
                email: data.user_email || '',
                avatarUrl: data.avatar_url || '',
                roles: data.user_roles || [],
                syncStatus: 'synced',
                updatedAt: data.updatedAt 
           };
           console.debug('[Sync API] Updated locals.user:', locals.user);
       }
    }

    console.debug(`[Sync API] Returning response: { valid: true, updated: ${updated}, session_created: ${session_created} }`);
    return new Response(
      JSON.stringify({ valid: true, updated, session_created }), {
        headers: headers
      }
    );

  } catch (error) {
    console.error('[Sync API] Error during sync processing:', error);
    if (connection) { 
        try { await connection.release(); } catch (relErr) { console.error('[Sync API] Error releasing connection in main catch:', relErr); }
    }
    return new Response(
      JSON.stringify({ 
        valid: false, 
        error: 'Internal server error' 
      }), {
        status: 500,
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

/** 
 * Handle incoming user data sync requests from WordPress.
 * Validates the request using a shared secret and then inserts/updates
 * the user data in the ba_users table and the mapping in ba_wp_user_map.
 * 
 * @param {object} event The SvelteKit request event object.
 * @param {Request} event.request The incoming request object, used to access headers and the body payload.
 * @returns {Promise<Response>} A response indicating sync success or failure, including the Better Auth user ID.
 */
/** @type {import('./$types').RequestHandler} */
export async function POST({ request }) {
    console.debug('[Sync API POST] Received POST request');
    const expectedSecret = process.env.BETTER_AUTH_SECRET;
    const providedSecret = request.headers.get('X-WP-Sync-Secret');

    if (!expectedSecret) {
        console.error('[Sync API POST] ERROR: BETTER_AUTH_SECRET environment variable is not set.');
        return new Response(JSON.stringify({ error: 'Server configuration error' }), { 
            status: 500, 
            headers: { 'Content-Type': 'application/json' } 
        });
    }

    if (!providedSecret || providedSecret !== expectedSecret) {
        console.warn('[Sync API POST] Invalid or missing X-WP-Sync-Secret header.');
        return new Response(JSON.stringify({ error: 'Unauthorized' }), { 
            status: 403, 
            headers: { 'Content-Type': 'application/json' } 
        });
    }

    let connection;
    try {
        // --- Debugging Request Body ---
        const contentType = request.headers.get('content-type');
        console.debug('[Sync API POST] Content-Type Header:', contentType);
        
        // Read the body ONCE as text
        const rawBody = await request.text();
        console.debug('[Sync API POST] Raw Request Body:', rawBody);
        // --- End Debugging ---
        
        // Parse the stored raw text
        let userData;
        try {
            userData = JSON.parse(rawBody);
            console.debug('[Sync API POST] Parsed User Data (JSON.parse):', userData); 
        } catch (parseError) {
            console.error('[Sync API POST] Failed to parse request body as JSON:', parseError);
            return new Response(JSON.stringify({ error: 'Invalid JSON payload' }), {
                status: 400,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        if (!userData || !userData.wpUserId || !userData.email) {
            console.warn('[Sync API POST] Invalid user data received after parsing.');
            return new Response(JSON.stringify({ error: 'Invalid user data content' }), { 
                status: 400, 
                headers: { 'Content-Type': 'application/json' } 
            });
        }

        // --- Database Logic --- 
        console.debug('[Sync API POST] Connecting to database...');
        connection = await mysql.createConnection({
            host: '127.0.0.1',
            port: DB_PORT ? parseInt(DB_PORT, 10) : 3306,
            user: DB_USER,
            password: DB_PASS,
            database: DB_NAME,
        });
        console.debug('[Sync API POST] Database connection successful.');

        // Generate a UUID for potential new user insertion
        // This is kept in case the SELECT after INSERT fails, but SELECT is now the primary method
        const new_ba_user_id = randomUUID(); 
        console.debug(`[Sync API POST] Generated potential new BA User ID (fallback): ${new_ba_user_id}`);

        // ---- BEGIN TRANSACTION ----
        await connection.beginTransaction();
        console.debug('[Sync API POST] Transaction started.');

        let ba_user_id = null; // Initialize ba_user_id

        // Step 1: Ensure user exists in ba_users based on email (INSERT or UPDATE)
        const usersSql = `
            INSERT INTO ba_users (
                id, email, name, image, created_at, updated_at
            )
            VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                image = VALUES(image),
                updated_at = NOW();
        `;
        const avatarUrlParam = userData.avatarUrl ?? userData.metadata?.avatar_url ?? null;
        const usersParams = [
            new_ba_user_id, // Still provide for INSERT case
            userData.email,
            userData.displayName,
            avatarUrlParam
        ];

        console.debug('[Sync API POST] Executing SQL for ba_users:', usersSql.trim());
        console.debug('[Sync API POST] With Params:', usersParams);
        // Execute but don't rely heavily on the result for ID determination anymore
        await connection.execute(usersSql, usersParams); 
        console.debug('[Sync API POST] ba_users INSERT/UPDATE executed.');

        // Step 2: Reliably fetch the ba_user_id by email
        console.debug('[Sync API POST] Fetching BA ID by email...');
        const [rows] = await connection.execute(
            'SELECT id FROM ba_users WHERE email = ? LIMIT 1',
            [userData.email]
        );

        if (Array.isArray(rows) && rows.length > 0 && rows[0] && typeof rows[0] === 'object' && 'id' in rows[0]) {
            ba_user_id = rows[0].id;
            console.debug(`[Sync API POST] Found BA ID via SELECT: ${ba_user_id}`);
        } else {
            // This should ideally not happen if the INSERT/UPDATE worked, but handle it
            console.error(`[Sync API POST] CRITICAL ERROR: Could not find user in ba_users by email after INSERT/UPDATE. Email: ${userData.email}`);
            await connection.rollback(); // Rollback transaction
            console.debug('[Sync API POST] Transaction rolled back due to user ID fetch failure.');
            return new Response(JSON.stringify({ error: 'Database sync error: Could not confirm user ID after operation.' }), { 
                status: 500, 
                headers: { 'Content-Type': 'application/json' } 
            });
        }
        
        // Step 3: Update ba_wp_user_map (still within the transaction)
        const mapSql = `
            INSERT IGNORE INTO ba_wp_user_map (wp_user_id, ba_user_id, created_at)
            VALUES (?, ?, NOW());
        `;
        const mapParams = [userData.wpUserId, ba_user_id];
        
        console.debug('[Sync API POST] Executing SQL for ba_wp_user_map:', mapSql.trim());
        console.debug('[Sync API POST] With Params:', mapParams);
        await connection.execute(mapSql, mapParams); // Execute within transaction
        console.debug('[Sync API POST] ba_wp_user_map INSERT IGNORE executed.');

        // ---- COMMIT TRANSACTION ----
        await connection.commit();
        console.debug('[Sync API POST] Transaction committed successfully.');

        // --- End Database Logic ---

        // Return success response (ba_user_id is guaranteed to be set if we reached here)
        console.debug(`[Sync API POST] User ${userData.wpUserId} synced successfully. BA ID: ${ba_user_id}`);
        return new Response(JSON.stringify({ 
            status: 'synced', 
            data: { id: ba_user_id, wpUserId: userData.wpUserId }
        }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' }
        });

    } catch (error) {
        console.error('[Sync API POST] Error during database operation or processing:', error);
        // Attempt to rollback transaction if connection exists
        if (connection) {
            try {
                await connection.rollback();
                console.debug('[Sync API POST] Transaction rolled back due to error in catch block.');
            } catch (rollbackError) {
                console.error('[Sync API POST] Failed to rollback transaction:', rollbackError);
            } finally {
                 // Ensure connection is closed even if rollback fails
                await connection.end();
                console.debug('[Sync API POST] Database connection closed in catch block after error.');
            }
        }
        return new Response(JSON.stringify({ error: 'Internal server error during sync' }), { 
            status: 500, 
            headers: { 'Content-Type': 'application/json' } 
        });
    } finally {
        // Ensure connection is closed if it wasn't closed in the catch block (e.g., non-DB error)
        if (connection) {
            try {
                // Check if connection is still open before trying to end it
                // Note: mysql2 doesn't have a standard 'isClosed'/'isOpen'. 
                // Ending an already closed connection might throw an error, but it's often handled gracefully.
                // A more robust check might involve connection state tracking if needed.
                await connection.end();
                console.debug('[Sync API POST] Database connection closed in finally block.');
            } catch (closeError) {
                // Log error if closing fails, but don't throw (already handling primary error)
                console.error('[Sync API POST] Error closing database connection in finally block:', closeError);
            }
        }
    }
} 