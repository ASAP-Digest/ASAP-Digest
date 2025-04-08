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

    // Update user data if needed
    /** @type {User|null} */
    const currentUser = locals.user || null;
    console.debug('[Sync API] Current SvelteKit user data (locals.user):', currentUser);
    console.debug('[Sync API] Data from WP (data):', data);

    const userIdChanged = currentUser?.id !== data.userId;
    const updatedAtChanged = currentUser?.updatedAt !== data.updatedAt;
    console.debug(`[Sync API] Checking for updates: userIdChanged=${userIdChanged}, updatedAtChanged=${updatedAtChanged}`);
    
    const updated = userIdChanged || updatedAtChanged;

    if (updated) {
      console.debug(`[Sync API] Update detected (userId changed: ${userIdChanged}, timestamp changed: ${updatedAtChanged}). Updating locals.user...`);
      /** @type {User} */
      locals.user = {
        id: data.userId,
        sessionToken: data.sessionToken,
        betterAuthId: data.userId,
        displayName: data.displayName || '',
        email: data.email || '',
        avatarUrl: data.avatarUrl || '',
        roles: data.roles || [],
        syncStatus: 'synced',
        updatedAt: data.updatedAt
      };
      console.debug('[Sync API] Updated locals.user:', locals.user);
    } else {
      console.debug('[Sync API] No update detected.');
    }

    console.debug(`[Sync API] Returning response: { valid: true, updated: ${updated} }`);
    return new Response(
      JSON.stringify({ valid: true, updated }), {
        headers: { 'Content-Type': 'application/json' }
      }
    );

  } catch (error) {
    console.error('[Sync API] Error during sync processing:', error);
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
        const new_ba_user_id = randomUUID();
        console.debug(`[Sync API POST] Generated potential new BA User ID: ${new_ba_user_id}`);

        // Step 1: INSERT or UPDATE ba_users based on email (UNIQUE key)
        const usersSql = `
            INSERT INTO ba_users (
                id, email, name, image, created_at, updated_at
            )
            VALUES (?, ?, ?, ?, NOW(), NOW()) -- Added id placeholder
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                image = VALUES(image),
                updated_at = NOW();
        `;

        const avatarUrlParam = userData.avatarUrl ?? userData.metadata?.avatar_url ?? null;

        const usersParams = [
            new_ba_user_id, // Provide the generated ID for potential INSERT
            userData.email,
            userData.displayName,
            avatarUrlParam
        ];

        console.debug('[Sync API POST] Executing SQL for ba_users:', usersSql.trim());
        console.debug('[Sync API POST] With Params:', usersParams);
        const [usersResult] = await connection.execute(usersSql, usersParams);
        console.debug('[Sync API POST] ba_users execute result:', usersResult);

        // Step 2: Determine the definitive ba_user_id 
        let ba_user_id = null;
        let needsSelect = false;
        if (usersResult && typeof usersResult === 'object' && 'affectedRows' in usersResult && 'insertId' in usersResult) {
            // Now the linter knows these properties exist within this block
            const affectedRows = usersResult.affectedRows ?? 0;
            const insertId = usersResult.insertId ?? 0; 

            if (affectedRows === 1 && insertId === 0) { 
                // Most likely an INSERT where insertId isn't returned reliably, or an update that changed 1 row
                // Let's use the generated ID for INSERT or fetch for UPDATE
                console.debug('[Sync API POST] AffectedRows=1, insertId=0. Could be INSERT or UPDATE.');
                // Check if the inserted ID matches the generated one (might work on some config)
                if (insertId > 0) { // Unlikely but check
                     ba_user_id = insertId;
                     console.debug(`[Sync API POST] Using insertId: ${ba_user_id}`);
                } else { 
                     // Assume INSERT used our UUID, but verify with SELECT just in case it was an update
                     ba_user_id = new_ba_user_id; 
                     console.debug(`[Sync API POST] Assuming INSERT used generated UUID: ${ba_user_id}. Will verify with SELECT.`);
                     needsSelect = true; // Verify/fetch ID via SELECT
                }
            } else if (affectedRows >= 1) {
                // Most likely an UPDATE (affectedRows=2) or INSERT (affectedRows=1, if insertId reported)
                console.debug(`[Sync API POST] AffectedRows >= 1 (${affectedRows}). Likely UPDATE or INSERT. Fetching ID via SELECT.`);
                needsSelect = true;
            } else { // affectedRows === 0
                console.debug('[Sync API POST] AffectedRows=0. No change. Fetching ID via SELECT.');
                needsSelect = true;
            }
        } else {
            console.error('[Sync API POST] Unexpected ba_users execute result structure:', usersResult);
            needsSelect = true; // Attempt SELECT as fallback
        }

        if (needsSelect) {
            console.debug('[Sync API POST] Fetching BA ID by email...');
            const [rows] = await connection.execute(
                'SELECT id FROM ba_users WHERE email = ? LIMIT 1',
                [userData.email]
            );
            if (Array.isArray(rows) && rows.length > 0 && rows[0] && typeof rows[0] === 'object' && 'id' in rows[0]) {
                ba_user_id = rows[0].id;
                console.debug(`[Sync API POST] Found/verified BA ID via SELECT: ${ba_user_id}`);
            } else {
                console.error(`[Sync API POST] ERROR: Could not find user in ba_users by email after operation. Email: ${userData.email}`);
                ba_user_id = null; // Ensure ba_user_id is null if SELECT fails
            }
        }
        
        // Step 3: Update ba_wp_user_map if ba_user_id was determined
        if (ba_user_id) {
            const mapSql = `
                INSERT IGNORE INTO ba_wp_user_map (wp_user_id, ba_user_id, created_at)
                VALUES (?, ?, NOW());
            `;
            const mapParams = [userData.wpUserId, ba_user_id];
            
            console.debug('[Sync API POST] Executing SQL for ba_wp_user_map:', mapSql.trim());
            console.debug('[Sync API POST] With Params:', mapParams);
            const [mapResult] = await connection.execute(mapSql, mapParams);
            console.debug('[Sync API POST] ba_wp_user_map execute result:', mapResult);
        } else {
             console.error(`[Sync API POST] Skipping ba_wp_user_map update because ba_user_id could not be determined.`);
        }

        // --- End Database Logic ---

        if (!ba_user_id) {
             console.error(`[Sync API POST] Failed to determine Better Auth ID for WP User ${userData.wpUserId} after DB operations.`);
             return new Response(JSON.stringify({ error: 'Database sync error: Could not confirm user ID' }), { 
                status: 500, 
                headers: { 'Content-Type': 'application/json' } 
            });
        }

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
        if (connection) {
            await connection.end();
            console.debug('[Sync API POST] Database connection closed due to error.');
        }
        return new Response(JSON.stringify({ error: 'Internal server error during sync' }), { 
            status: 500, 
            headers: { 'Content-Type': 'application/json' } 
        });
    } finally {
        if (connection) {
            try {
                await connection.end();
                console.debug('[Sync API POST] Database connection closed in finally block.');
            } catch (closeError) {
                console.error('[Sync API POST] Error closing database connection:', closeError);
            }
        }
    }
} 