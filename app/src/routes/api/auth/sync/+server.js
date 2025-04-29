/// <reference types="@sveltejs/kit" />

import mysql from 'mysql2/promise';
import { randomUUID } from 'node:crypto';
import { randomBytes } from 'node:crypto';
import { pool } from '$lib/server/auth';
import { redirect } from '@sveltejs/kit';
import { json, error } from '@sveltejs/kit';
import { broadcastSyncUpdate } from '$lib/server/syncBroadcaster';

// Environment variable for WP endpoint
const WP_CHECK_SESSION_URL = process.env.WP_CHECK_SESSION_URL;

/**
 * @typedef {import('mysql2/promise').RowDataPacket} RowDataPacket
 * @typedef {import('mysql2/promise').OkPacket} OkPacket
 * @typedef {import('mysql2/promise').ResultSetHeader} ResultSetHeader
 * @typedef {import('mysql2/promise').QueryResult} QueryResult 
 */

/**
 * @typedef {Object} SyncResponse
 * @property {boolean} valid - Whether the sync was successful
 * @property {boolean} [session_created] - Whether a new session was created
 * @property {string} [error] - Error message if sync failed
 */

// Type definitions from auth.js (can be moved to a central types file eventually)
/**
 * @typedef {Object} UserMetadata
 * @property {number} wp_user_id
 * @property {string[]} [roles]
 * @property {string} [registered]
 * @property {string} [locale]
 */
/**
 * @typedef {Object} User
 * @property {string} id - Better Auth User ID (UUID)
 * @property {string} email - User email
 * @property {string} [username] - Optional username from WP
 * @property {string} [name] - Optional display name from WP
 * @property {UserMetadata} metadata - User metadata 
 * @property {string} betterAuthId - Better Auth User ID (should match id)
 * @property {string} displayName - Primary display name (likely from WP)
 * @property {string} [avatarUrl] - URL to user avatar
 * @property {string[]} roles - User roles (likely from WP)
 * @property {'pending' | 'synced' | 'error'} syncStatus - Status of sync with WP
 * @property {string} [updatedAt] - Timestamp of last update from ba_users table (ISO 8601 format)
 */

/**
 * @typedef {object} WordPressSessionData - Expected structure from WP session check.
 * @property {boolean} loggedIn
 * @property {boolean} autosyncActive
 * @property {{ wpUserId: number, email: string, displayName: string } | null} userData - Nested user details if logged in.
 */

/**
 * Logs messages with context
 * @param {string} message
 * @param {'debug' | 'info' | 'warn' | 'error'} [level='info']
 */
function log(message, level = 'info') {
    const prefix = '[GET /api/auth/sync]';
    switch (level) {
        case 'debug': console.debug(`${prefix} ${message}`); break;
        case 'info': console.log(`${prefix} ${message}`); break;
        case 'warn': console.warn(`${prefix} Warning: ${message}`); break;
        case 'error': console.error(`${prefix} ERROR: ${message}`); break;
    }
}

/**
 * Helper to find user by WP ID directly using the pool
 * @param {number} wpUserId
 * @returns {Promise<User | null>}
 */
async function findUserByWpId(wpUserId) {
    let connection;
    try {
        connection = await pool.getConnection();
        const sql = "SELECT * FROM ba_users WHERE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wp_user_id')) = ? LIMIT 1";
        /** @type {[RowDataPacket[], any]} */
        const [rows] = await connection.execute(sql, [String(wpUserId)]);
        if (Array.isArray(rows) && rows.length > 0) {
            const userRow = rows[0];
            if (userRow && typeof userRow === 'object' && 'id' in userRow) {
                 let metadata = userRow.metadata;
                if (typeof metadata === 'string') try { metadata = JSON.parse(metadata); } catch { metadata = {}; }
                /** @type {User} */
                const user = {
                    id: String(userRow.id),
                    email: String(userRow.email),
                    username: userRow.username ? String(userRow.username) : undefined,
                    name: userRow.name ? String(userRow.name) : undefined,
                    metadata: metadata || {},
                    betterAuthId: String(userRow.id),
                    displayName: String(userRow.name || userRow.username || userRow.email),
                    roles: metadata?.roles || [],
                    syncStatus: /** @type {'pending' | 'synced' | 'error'} */ ('pending'),
                    updatedAt: userRow.updated_at ? new Date(userRow.updated_at).toISOString() : undefined,
                };
                return user;
            }
        }
        return null;
    } finally {
        connection?.release();
    }
}

/**
 * Helper to create a user directly using the pool
 * @param {{ wpUserId: number, email: string, displayName: string }} userData - Expecting actual data now
 * @returns {Promise<User | null>}
 */
async function createUserFromWpId(userData) {
    const { wpUserId, email, displayName } = userData; 
    if (!wpUserId || !email) {
        log(`Cannot create user: Missing wpUserId or email. Provided: ${JSON.stringify(userData)}`, 'error');
        return null; // Cannot create user without essential info
    }

    let connection;
    try {
        connection = await pool.getConnection();
        
        const finalEmail = email;
        const finalName = displayName || `WordPress User ${wpUserId}`; // Use display name, fallback if empty
        const finalUsername = finalEmail; // Use email as username for simplicity, or derive differently if needed
        const finalRoles = ['subscriber']; // Default role for new syncs
        const metadata = { wp_user_id: wpUserId, roles: finalRoles };
        const metadataJson = JSON.stringify(metadata);
        const userId = randomUUID(); // Use crypto

        const sql = 'INSERT INTO ba_users (id, email, username, name, metadata, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
        /** @type {[OkPacket | ResultSetHeader, any]} */
        const [result] = await connection.execute(sql, [userId, finalEmail, finalUsername, finalName, metadataJson]);

        if (result && 'affectedRows' in result && result.affectedRows === 1) {
             /** @type {User} */
            const newUser = {
                id: userId,
                email: finalEmail,
                username: finalUsername,
                name: finalName,
                metadata: metadata,
                betterAuthId: userId,
                displayName: finalName,
                roles: finalRoles,
                syncStatus: /** @type {'pending' | 'synced' | 'error'} */ ('pending'),
                updatedAt: new Date().toISOString()
            };
            log(`Created new Better Auth user ${userId} linked to wpUserId ${wpUserId}`, 'info');
            return newUser;
        }
        log(`Failed to create user for wpUserId ${wpUserId}. DB Result: ${JSON.stringify(result)}`, 'error');
        return null;
    } catch (dbError) {
        log(`Database error during user creation for wpUserId ${wpUserId}: ${dbError instanceof Error ? dbError.message : dbError}`, 'error');
        return null; // Return null on error
    } finally {
        connection?.release();
    }
}

/**
 * Helper to create a session directly using the pool
 * @param {string} baUserId 
 * @returns {Promise<{sessionToken: string, expiresAt: Date} | null>}
 */
async function createDbSession(baUserId) {
    let connection;
    try {
        connection = await pool.getConnection();
        const sessionToken = randomBytes(32).toString('hex');
        const expiresAt = new Date();
        expiresAt.setDate(expiresAt.getDate() + 30); // 30-day expiry

        const sql = 'INSERT INTO ba_sessions (user_id, token, expires_at) VALUES (?, ?, ?)';
         /** @type {[OkPacket | ResultSetHeader, any]} */
        const [result] = await connection.execute(sql, [baUserId, sessionToken, expiresAt]);

         if (result && 'affectedRows' in result && result.affectedRows === 1) {
            return { sessionToken, expiresAt };
        }
        return null;
    } finally {
        connection?.release();
    }
}

/** @type {import('./$types').RequestHandler} */
export async function GET({ request, url, locals }) {
    log('Received request');
	const headers = new Headers({ 'Content-Type': 'application/json' });
    const incomingCookieHeader = request.headers.get('cookie') || ''; // Get cookies from the request that hit this endpoint

    // --- Check for Token-Based Auto-Login (Existing Logic - Unchanged) ---
	const loginToken = url.searchParams.get('token');
	if (loginToken) {
        log(`Login token found: ${loginToken}. Attempting token validation.`, 'debug');
        let connection;
		try {
			connection = await pool.getConnection();
            log('Acquired DB connection for token validation.', 'debug');

			let wpUserId = null;
			let tokenIsValid = false;

			const tokenSql = 'SELECT wp_user_id FROM ba_wp_login_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1';
            /** @type {[RowDataPacket[], any]} */
            const [tokenQueryResult] = await connection.execute(tokenSql, [loginToken]);

			if (Array.isArray(tokenQueryResult) && tokenQueryResult.length > 0) {
				const tokenRows = tokenQueryResult;
				if (tokenRows[0] && typeof tokenRows[0] === 'object' && 'wp_user_id' in tokenRows[0]) {
					wpUserId = tokenRows[0].wp_user_id;
					tokenIsValid = true;
                    log(`Token validated for wpUserId: ${wpUserId}`, 'debug');
					const deleteSql = 'DELETE FROM ba_wp_login_tokens WHERE token = ?';
					await connection.execute(deleteSql, [loginToken]);
                    log(`Deleted used login token: ${loginToken}`, 'debug');
				}
			}

			if (!tokenIsValid) {
                log(`Login token invalid or expired: ${loginToken}`, 'warn');
                return json({ valid: false, error: 'Invalid or expired login token' }, { status: 401 });
			}

			if (wpUserId) {
				const mapSql = 'SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ? LIMIT 1';
                 /** @type {[RowDataPacket[], any]} */
                const [mapQueryResult] = await connection.execute(mapSql, [wpUserId]);
                let baUserId = null;

				if (Array.isArray(mapQueryResult) && mapQueryResult.length > 0) {
					const mapRows = mapQueryResult;
					if (mapRows[0] && typeof mapRows[0] === 'object' && 'ba_user_id' in mapRows[0]) {
                        baUserId = mapRows[0].ba_user_id;
                        log(`Found ba_user_id: ${baUserId} for wpUserId: ${wpUserId}`, 'debug');

                        const sessionData = await createDbSession(baUserId);
                        if (sessionData) {
                            const { sessionToken, expiresAt } = sessionData;
                            log(`Inserted new session into ba_sessions for user: ${baUserId}`, 'debug');

                            const cookieName = 'asap_sk_session'; // Match auth.js config
						const cookieOptions = [
								`Path=/`,
                                `Expires=${expiresAt.toUTCString()}`,
								`HttpOnly`,
                                `SameSite=Lax`,
                                // process.env.NODE_ENV === 'production' ? 'Secure' : '' // Handled by SvelteKit adapter usually
                            ].filter(Boolean).join('; ');
                            headers.set('Set-Cookie', `${cookieName}=${sessionToken}; ${cookieOptions}`);
                            log('Set-Cookie header prepared for redirect.', 'debug');

                            log('Token login successful. Redirecting to /dashboard...', 'debug');
                            throw redirect(302, '/dashboard'); // Use SvelteKit redirect
                        } else {
                            log(`Failed to create DB session for user ${baUserId}`, 'error');
                            return json({ valid: false, error: 'Session creation error during login' }, { status: 500 });
                        }
					} else {
                        log(`CRITICAL: Could not find ba_user_id in ba_wp_user_map for wpUserId: ${wpUserId} during token login.`, 'error');
                        return json({ valid: false, error: 'User mapping error during login' }, { status: 500 });
					}
				} else {
                    log(`CRITICAL: Unexpected database response format when querying ba_wp_user_map for wpUserId: ${wpUserId}.`, 'error');
                    return json({ valid: false, error: 'Database error during login' }, { status: 500 });
				}
			}
        } catch (/** @type {any} */ error) {
            log(`Error during token-based login: ${error instanceof Error ? error.message : String(error)}`, 'error');
            return json({ valid: false, error: 'Server error during token login' }, { status: 500 });
		} finally {
            connection?.release();
            log('Released DB connection after token processing.', 'debug');
		}
	}

    // --- Fallback to Cookie-Based Sync Check (AJAX Flow) - REVISED ---
    log('No login token found. Proceeding with AJAX cookie-based sync check.', 'debug');

    // Check if SvelteKit session already exists
    if (!locals.session) { 
        log('No active SvelteKit session found. Attempting WP sync.', 'debug');
        // Proceed only if WP_CHECK_SESSION_URL is configured
        if (WP_CHECK_SESSION_URL) {
            try {
                log(`Fetching WP session status from: ${WP_CHECK_SESSION_URL}`, 'debug');
                
                // Prepare headers for the fetch call, including forwarded cookies
                const fetchHeaders = new Headers();
                // Always include the 'Cookie' header, even if empty, 
                // to potentially signal to WP that this is a browser-like request.
                fetchHeaders.append('Cookie', incomingCookieHeader);

                if (incomingCookieHeader) {
                    log(`Forwarding incoming Cookie header to WP fetch. Header Content: [${incomingCookieHeader}]`, 'debug'); // Log cookie content
                } else {
                    log('No incoming Cookie header found to forward.', 'debug');
                }

                // Fetch from WP endpoint, explicitly passing headers
                const wpResponse = await fetch(WP_CHECK_SESSION_URL, { 
                    // **IMPORTANT**: Do NOT use `credentials: 'include'` here.
                    // We are manually forwarding the browser's cookies via the 'Cookie' header.
                    // Using `credentials: 'include'` on a server-side fetch can lead to 
                    // unexpected behavior or errors as it implies the *server itself* 
                    // has credentials to include, which is not the case here.
                    headers: fetchHeaders 
                });
                
                // Log response status and headers from WP
                log(`WP Response Status: ${wpResponse.status}`, 'info');
                /** @type {Record<string, string>} */
                const responseHeaders = {};
                wpResponse.headers.forEach((value, key) => { responseHeaders[key] = value; });
                log(`WP Response Headers: ${JSON.stringify(responseHeaders)}`, 'debug');
                
                // Clone the response to read the body, as it can only be read once
                const wpResponseClone = wpResponse.clone();
                const wpResponseBody = await wpResponseClone.text();
                log(`WP Response Body: ${wpResponseBody}`, 'debug'); // Log the raw body

                if (!wpResponse.ok) {
                    log(`WordPress session check failed with status ${wpResponse.status}. Body: ${wpResponseBody}`, 'error');
                    // Try parsing error from body if possible, otherwise use generic message
                    let errorMsg = 'WordPress session check failed';
                    try {
                        const errorJson = JSON.parse(wpResponseBody);
                        errorMsg = errorJson.message || errorMsg;
                    } catch (e) { /* Ignore parsing error */ }
                    return json({ valid: false, error: errorMsg }, { status: wpResponse.status }); // Return actual WP status
                }

                /** @type {WordPressSessionData} */
                // Use the original response object here, as clone was used for logging body
                const wpData = await wpResponse.json(); 
                log(`Parsed WP session data: ${JSON.stringify(wpData)}`, 'debug'); // Log parsed data

                // --- Process WP Response ---
                // Use optional chaining and nullish coalescing for safety
                if (wpData.loggedIn && wpData.userData?.wpUserId) {
                    const wpUserData = wpData.userData; // wpUserData is guaranteed non-null here
                    const wpUserId = wpUserData.wpUserId;
                    
                    log(`WP user logged in (wpUserId: ${wpUserId}). Checking Better Auth...`, 'info');

                    // Check if Better Auth user exists linked to this WP User ID
                    let baUser = await findUserByWpId(wpUserId);

                    if (!baUser) {
                        log(`No existing Better Auth user found for wpUserId ${wpUserId}. Attempting creation...`, 'info');
                        
                        // Ensure we have the necessary email to create a user
                        if (!wpUserData.email) { // Email is required
                            log(`Cannot create Better Auth user for wpUserId ${wpUserId}: Missing email address from WP response.`, 'error');
                            return json({ valid: false, error: 'User sync failed - missing email' }, { status: 500 });
                        }

                        // Create Better Auth user if not found
                        baUser = await createUserFromWpId({
                            wpUserId: wpUserId,
                            email: wpUserData.email, // Now guaranteed to be a string
                            displayName: wpUserData.displayName || '' // Pass displayName or empty string if null/undefined
                        }); 
                    }

                    if (baUser) {
                        log(`Found/Created Better Auth user: ${baUser.id}`, 'info');
                        // Create Better Auth session
                        const sessionData = await createDbSession(baUser.id);

                        if (sessionData) {
                            const { sessionToken, expiresAt } = sessionData;
                            log(`Created Better Auth session for user ${baUser.id}`, 'info');
                            // Set session cookie
                            const cookieName = 'asap_sk_session'; // Ensure matches auth.js config
                            const cookieOptions = [
                                `Path=/`,
                                `Expires=${expiresAt.toUTCString()}`,
                                `HttpOnly`,
                                `SameSite=Lax`, // Consider Strict if appropriate
                                // Secure // Add Secure in production (HTTPS)
                            ];
                            headers.append('Set-Cookie', `${cookieName}=${sessionToken}; ${cookieOptions.join('; ')}`);
                            log(`Session cookie set`, 'debug');

                            // Broadcast update (optional) - Pass a simple string message
                            broadcastSyncUpdate(`User ${baUser.id} synced successfully.`);
                            
                            // Return success
                            return json({ valid: true, session_created: true }, { headers });

                        } else {
                            log(`Failed to create Better Auth session for user ${baUser.id}`, 'error');
                            return json({ valid: false, error: 'Session creation failed' }, { status: 500 });
                        }
                    } else {
                        log(`Failed to find or create Better Auth user for wpUserId ${wpUserId}`, 'error');
                        return json({ valid: false, error: 'User sync failed' }, { status: 500 });
                    }

                } else {
                    // WP user not logged in
                    log('WP user not logged in, no sync performed.', 'info');
                    return json({ valid: false, error: 'User not logged in on WordPress' }, { status: 401 });
                }
            } catch (syncError) {
                log(`Error during AJAX sync process: ${syncError instanceof Error ? syncError.message : syncError}`, 'error');
                return json({ valid: false, error: 'Internal server error during sync' }, { status: 500 });
            } finally {
                // Release connection if it exists (handled in helpers now)
                log('AJAX sync attempt finished.', 'debug');
            }
        } else {
             log('WP_CHECK_SESSION_URL not configured.', 'error');
             return json({ valid: false, error: 'Server configuration error' }, { status: 500 });
        }
    } else {
        // Existing SvelteKit session found, no sync needed via this endpoint.
        log('Existing SvelteKit session found, sync not required via GET.', 'info');
        return json({ valid: true, session_created: false });
    }
}

/**
 * @typedef {object} PostSyncPayload - Expected JSON payload for POST request.
 * @property {number} wpUserId
 * @property {string} [skUserId] - SvelteKit/Better Auth ID (UUID), may be null/missing on initial syncs
 * @property {string} email
 * @property {string} [displayName]
 * @property {string} [username]
 * @property {string[]} [roles] // Roles sent at top level from WP
 * @property {string} [firstName]
 * @property {string} [lastName]
 * @property {string} [avatarUrl] // Note: WP payload might not include this, SK side uses metadata
 * @property {{ description?: string, nickname?: string }} [metadata] // Other WP metadata
 */

/**
 * @description Handles POST requests to synchronize user data from WordPress.
 * Expected payload: { wpUserId: number, skUserId: string, email: string, displayName?: string, username?: string, avatarUrl?: string, metadata?: object }
 * @param {object} event The SvelteKit request event object.
 * @param {import('@sveltejs/kit').RequestEvent['request']} event.request The incoming request object.
 * @returns {Promise<Response>} JSON response indicating success or failure.
 */
/** @type {import('./$types').RequestHandler} */
export async function POST({ request }) {
	let connection;
	// Define headers at the start of the function scope
	const headers = new Headers({ 'Content-Type': 'application/json' });
	try { // Start try block for POST function
		console.debug('[Sync API POST] Received request');

		// 1. Verify Secret Header
		const secret = request.headers.get('X-WP-Sync-Secret');
		const expectedSecret = process.env.BETTER_AUTH_SECRET; // Use the correct env var

		if (!expectedSecret) {
			console.error('[Sync API POST] CRITICAL: BETTER_AUTH_SECRET is not set in environment.');
			return new Response(JSON.stringify({ success: false, message: 'Server configuration error: Missing secret.' }), { status: 500, headers });
		}

		if (!secret || secret !== expectedSecret) {
			console.warn('[Sync API POST] Invalid or missing sync secret received.');
			return new Response(JSON.stringify({ success: false, message: 'Invalid Sync Secret' }), { status: 403, headers });
		}
		console.debug('[Sync API POST] Sync secret verified.');

		// 2. Parse Request Body
		/** @type {PostSyncPayload} */
		let userData;
		try {
			userData = await request.json();
			console.debug('[Sync API POST] Parsed user data:', userData);
			// Validation: Only require wpUserId and email. skUserId check removed.
			if (!userData || typeof userData.wpUserId !== 'number' || typeof userData.email !== 'string') {
				throw new Error('Missing required fields (wpUserId, email) in sync data');
			}
		} catch (/** @type {any} */ parseError) {
			console.error('[Sync API POST] Error parsing request body:', parseError);
			return new Response(JSON.stringify({ success: false, message: 'Invalid request body: ' + parseError.message }), { status: 400, headers });
		}

		// 3. Database Operations within Transaction
		try {
			connection = await pool.getConnection();
			console.debug('[Sync API POST] Acquired DB connection.');
			await connection.beginTransaction();
			console.debug('[Sync API POST] Started transaction.');

            // --- REVISED LOGIC: Find existing user, ONLY UPDATE --- 
            console.log(`[Sync API POST] Received sync request for wpUserId: ${userData.wpUserId}. Attempting to find existing skUser.`);
            
            // Find the corresponding SvelteKit user using the wpUserId
            const existingSkUser = await findUserByWpId(userData.wpUserId);

            if (existingSkUser) {
                console.log(`[Sync API POST] Found existing user ${existingSkUser.id}. Proceeding with UPDATE.`);
                const targetSkUserId = existingSkUser.id;

                // Prepare data ONLY for updating the existing ba_user record
                const userDataForDbUpdate = {
                    id: targetSkUserId,
                    email: userData.email,
                    name: userData.displayName || userData.username || userData.email.split('@')[0],
                    username: userData.username || userData.email.split('@')[0],
                    updated_at: new Date(), // Use current time for update
                    // Image handling might need adjustment if WP sends avatarUrl
                    // image: userData.avatarUrl ?? existingSkUser.avatarUrl ?? null 
                    image: existingSkUser.avatarUrl ?? null // Preserve existing image for now
                };
                 /** @type {{ id: string; email: string; name: string; username: string; updated_at: Date; image?: string | null }} */
                const typedUserDataForDbUpdate = userDataForDbUpdate;
                console.debug('[Sync API POST] Prepared data for DB UPDATE:', typedUserDataForDbUpdate);
                
                // Build the UPDATE statement dynamically
                const updateSet = Object.entries(typedUserDataForDbUpdate)
                    .filter(([key, value]) => key !== 'id' && value !== undefined)
                    .map(([key]) => `${key} = ?`)
                    .join(', ');
                const valuesToUpdate = Object.entries(typedUserDataForDbUpdate)
                    .filter(([key, value]) => key !== 'id' && value !== undefined)
                    .map(([key, value]) => value);
                valuesToUpdate.push(targetSkUserId); // Add ID for WHERE clause

                if (updateSet) { // Only run update if there are fields to update besides updated_at
                    const updateUserSql = `UPDATE ba_users SET ${updateSet}, updated_at = NOW() WHERE id = ?`;
                    console.debug('[Sync API POST] Executing UPDATE for existing ba_user. SQL:', updateUserSql.trim(), 'Params:', valuesToUpdate);
                    console.time('DB_UPDATE_USER');
                    /** @type {[OkPacket | ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
                    const [updateResult] = await connection.execute(updateUserSql, valuesToUpdate);
                    console.timeEnd('DB_UPDATE_USER');
                    console.debug('[Sync API POST] ba_users update result:', updateResult);
                } else {
                     console.log('[Sync API POST] No changed fields detected for UPDATE, skipping ba_users update.');
                }

                // Update metadata (including roles) separately, overwrite existing
                // Construct metadata using correct roles from TOP-LEVEL payload
                const baseMetadata = {
                    wp_user_id: userData.wpUserId,
                    roles: userData.roles || ['subscriber'], // Use top-level roles
                    description: userData.metadata?.description, // Example of other metadata
                    nickname: userData.metadata?.nickname      // Example of other metadata
                };
                const metadataJson = JSON.stringify(baseMetadata);
                console.time('DB_UPDATE_METADATA');
                const updateMetaSql = 'UPDATE ba_users SET metadata = ? WHERE id = ?';
                const metaParams = [metadataJson, targetSkUserId];
                console.debug('[Sync API POST] Executing metadata update. SQL:', updateMetaSql, 'Params:', metaParams);
                await connection.execute(updateMetaSql, metaParams);
                console.timeEnd('DB_UPDATE_METADATA');
                console.debug('[Sync API POST] Metadata updated for existing user.');

                // Ensure mapping exists (UPSERT is safe here)
                try {
                    console.time('DB_UPSERT_MAP');
                    const mapSql = 'INSERT INTO ba_wp_user_map (wp_user_id, ba_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE ba_user_id = VALUES(ba_user_id)';
                    await connection.execute(mapSql, [userData.wpUserId, targetSkUserId]);
                    console.timeEnd('DB_UPSERT_MAP');
                    console.log(`[Sync API POST] Ensured mapping exists for wpUserId ${userData.wpUserId} to baUserId ${targetSkUserId}.`);
                } catch (/** @type {any} */ mapInsertError) {
                    // ... (map error handling) ...
                }

            } else {
                // User NOT found in SvelteKit DB via wpUserId - DO NOTHING in this endpoint.
                // Creation will be handled by the verify-sync-token endpoint if the user presents a valid token.
                console.log(`[Sync API POST] No existing SvelteKit user found for wpUserId ${userData.wpUserId}. No action taken by sync endpoint.`);
            }
            // --- END REVISED LOGIC ---

            console.time('DB_COMMIT');
            await connection.commit();
            console.timeEnd('DB_COMMIT');
            // Remove DB_TRANSACTION timers as they are less relevant now
            // console.timeEnd('DB_TRANSACTION'); 

            // Broadcast update only if user existed and was potentially updated
            if (existingSkUser) {
                 console.debug(`[Sync API POST] Broadcasting sync update for user ${existingSkUser.id}`);
                 const broadcastPayload = { userId: existingSkUser.id, updatedAt: new Date().toISOString() }; // Use current time
                 broadcastSyncUpdate(JSON.stringify(broadcastPayload));
            }

            // Return success regardless of whether an update occurred (request was processed)
            return new Response(JSON.stringify({ success: true, message: existingSkUser ? 'User data synchronized' : 'No existing SK user found to update' }), { status: 200, headers });

		} catch (/** @type {any} */ dbError) { // Catch for inner DB transaction block
            console.timeEnd('DB_TRANSACTION'); // Ensure timer ends even on error
			console.error('[Sync API POST] Error during database operation:', dbError);
			if (connection) {
				try { await connection.rollback(); console.debug('[Sync API POST] Transaction rolled back due to DB error.'); } catch (/** @type {any} */ rbErr) { console.error('[Sync API POST] Error rolling back transaction:', rbErr); }
			}
			return new Response(JSON.stringify({ success: false, message: 'Database error during sync' }), { status: 500, headers });
		}
	} catch (/** @type {any} */ postError) { // Catch for outer POST function block
		// This catches errors before DB connection or after commit/rollback failure
		console.error('[Sync API POST] Unexpected error:', postError);
		// Use the headers defined at the function scope
		return new Response(JSON.stringify({ success: false, message: 'Server error during POST sync' }), { status: 500, headers }); // FIXED: Use defined headers
	} finally {
		if (connection) {
			try { await connection.release(); console.debug('[Sync API POST] Released DB connection.'); } catch (/** @type {any} */ relErr) { console.error('[Sync API POST] Error releasing connection in POST finally:', relErr); }
		}
	} // End outer try...finally
} // End POST function

/**
 * @description Creates a standard error response.
 * @param {any} error The error object.
 * @returns {Promise<Response>} A Response object.
 */
async function postError(error) { 
	// Ensure error.message exists, provide fallback
	const message = (error && typeof error === 'object' && 'message' in error) ? String(error.message) : 'An unknown error occurred';
	return new Response(JSON.stringify({ error: message }), {
		status: 500,
		headers: { 'Content-Type': 'application/json' }
	});
}

