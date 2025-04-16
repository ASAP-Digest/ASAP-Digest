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
 * @property {number | null} userId
 * // Add other potential fields if WP endpoint is enhanced later
 * // @property {string} [userEmail]
 * // @property {string} [username]
 * // @property {string} [displayName]
 * // @property {string} [avatarUrl]
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
        const sql = "SELECT * FROM ba_users WHERE JSON_EXTRACT(metadata, '$.wp_user_id') = ? LIMIT 1";
        /** @type {[RowDataPacket[], any]} */
        const [rows] = await connection.execute(sql, [wpUserId]);
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
 * @param {{ wpUserId: number, email?: string, username?: string, name?: string, roles?: string[] }} userData
 * @returns {Promise<User | null>}
 */
async function createUserFromWpId(userData) {
    const { wpUserId } = userData;
    if (!wpUserId) return null;

    let connection;
    try {
        connection = await pool.getConnection();
        // Placeholder details - Requires enhancement (e.g., fetch from WP)
        const finalEmail = userData.email || `wp_user_${wpUserId}@asapdigest.local`;
        const finalUsername = userData.username || `wp_user_${wpUserId}`;
        const finalName = userData.name || `WordPress User ${wpUserId}`;
        const finalRoles = userData.roles || ['subscriber'];
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
            return newUser;
        }
        return null;
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

    if (!WP_CHECK_SESSION_URL) {
        log('WP_CHECK_SESSION_URL environment variable is not set.', 'error');
        return json({ valid: false, error: 'Server configuration error [WPURL]' }, { status: 500 });
    }

	try { // Outer try for AJAX check
        log(`Checking WP session at: ${WP_CHECK_SESSION_URL}`);

        const wpResponse = await fetch(WP_CHECK_SESSION_URL, {
			method: 'GET',
            credentials: 'include',
			headers: {
				'Content-Type': 'application/json',
                // Pass necessary cookies from original request
                'Cookie': request.headers.get('cookie') || '' 
			},
		});

        log(`WP Check Response Status: ${wpResponse.status}`);

		if (!wpResponse.ok) {
			const errorText = await wpResponse.text();
            log(`WP session check failed: ${wpResponse.status} ${errorText}`, 'error');
			return json({ valid: false, error: `WordPress check failed (${wpResponse.status})` }, { status: wpResponse.status });
		}

		/** @type {WordPressSessionData} */
		const wpData = await wpResponse.json();
        log(`WP Check Response Data: ${JSON.stringify(wpData)}`);

        if (wpData && wpData.loggedIn && typeof wpData.userId === 'number') {
			const wpUserId = wpData.userId;
            log(`WP user logged in. WP User ID: ${wpUserId}`);

            try { // Inner try for DB lookup/creation/session
                let baUser = await findUserByWpId(wpUserId);
                let baUserId = baUser ? baUser.id : null;

                if (!baUser) {
                    log(`Better Auth user not found for WP ID ${wpUserId}. Attempting creation.`, 'debug');
                    // Pass minimal data, assumes createUserFromWpId handles defaults
                    baUser = await createUserFromWpId({ wpUserId: wpUserId });

								if (!baUser) {
                        log(`Failed to create Better Auth user for WP ID ${wpUserId}.`, 'error');
                        return json({ valid: false, error: 'Failed to create corresponding user account.' }, { status: 500 });
                    }
                    baUserId = baUser.id;
                    log(`Created Better Auth user ${baUserId} for WP ID ${wpUserId}.`, 'debug');
					} else {
                    log(`Found existing Better Auth user ${baUserId} for WP ID ${wpUserId}.`, 'debug');
					}

                if (!baUserId) {
                    log(`CRITICAL: Could not obtain Better Auth user ID after lookup/creation for WP ID ${wpUserId}.`, 'error');
                    return json({ valid: false, error: 'User synchronization error.' }, { status: 500 });
					}

                // Create a DB session directly
                const sessionData = await createDbSession(baUserId);

                if (sessionData) {
                    const { sessionToken, expiresAt } = sessionData;
                    log(`DB session created successfully for user ${baUserId}.`, 'debug');

                    const cookieName = 'asap_sk_session'; // Match auth.js config
                    const cookieOptions = [
                        `Path=/`,
                        `Expires=${expiresAt.toUTCString()}`,
                        `HttpOnly`,
                        `SameSite=Lax`,
                        // process.env.NODE_ENV === 'production' ? 'Secure' : '' // Adapter handles this usually
                    ].filter(Boolean).join('; ');
                    headers.set('Set-Cookie', `${cookieName}=${sessionToken}; ${cookieOptions}`);
                    log('Set-Cookie header prepared.', 'debug');

                    // Return success JSON with the cookie header
                    return json({ valid: true, session_created: true }, { headers });

                } else {
                    log(`Failed to create DB session for user ${baUserId}.`, 'error');
                    return json({ valid: false, error: 'Session creation failed.' }, { status: 500 });
				}

            } catch (/** @type {any} */ dbError) {
                log(`Error during DB lookup/creation/session: ${dbError instanceof Error ? dbError.message : String(dbError)}`, 'error');
                return json({ valid: false, error: 'Server error during user sync.' }, { status: 500 });
			}
		} else {
            log('WP user not logged in or required data missing.');
            // Optional: Invalidate SK session if WP is logged out?
            return json({ valid: false });
        }
    } catch (/** @type {any} */ fetchError) {
        log(`Error fetching WP session status: ${fetchError instanceof Error ? fetchError.message : String(fetchError)}`, 'error');
        return json({ valid: false, error: 'Could not contact WordPress for session check.' }, { status: 502 }); // Bad Gateway
    }
}

/**
 * @typedef {object} WordPressUserData - Expected structure from fetchWordPressUserData
 * @property {number} [wp_user_id]
 * @property {string} [display_name]
 * @property {string} [user_email]
 * @property {string} [username]
 * @property {string} [avatar_url]
 * @property {string[]} [user_roles]
 * @property {string} [updatedAt] // ISO 8601 string expected
 */

/**
 * @typedef {object} PostSyncPayload - Expected JSON payload for POST request.
 * @property {number} wpUserId
 * @property {string} skUserId
 * @property {string} email
 * @property {string} [displayName]
 * @property {string} [username]
 * @property {string} [avatarUrl]
 * @property {object} [metadata] // Optional metadata object
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
			// Type Guard: Check required fields after parsing
			if (!userData || typeof userData.wpUserId !== 'number' || typeof userData.skUserId !== 'string' || typeof userData.email !== 'string') {
				throw new Error('Missing required fields (wpUserId, skUserId, email) in sync data');
			}
		} catch (/** @type {any} */ parseError) {
			console.error('[Sync API POST] Error parsing request body:', parseError);
			return new Response(JSON.stringify({ success: false, message: 'Invalid request body: ' + parseError.message }), { status: 400, headers });
		}

		// 3. Upsert User Data in ba_users and Update ba_wp_user_map
		try {
			connection = await pool.getConnection();
			console.debug('[Sync API POST] Acquired DB connection.');
			await connection.beginTransaction();
			console.debug('[Sync API POST] Started transaction.');

			// Prepare data for ba_users upsert
			// Use optional chaining and nullish coalescing for safer access
			const userDataForDb = {
				id: userData.skUserId,
				email: userData.email,
				name: userData.displayName || userData.username || userData.email.split('@')[0], // Fallback logic
				username: userData.username || userData.email.split('@')[0], // Fallback logic
				updated_at: new Date(),
				image: userData.avatarUrl ?? null // Use nullish coalescing for optional image
			};

			/** @type {{ id: string; email: string; name: string; username: string; updated_at: Date; image?: string | null }} */
			const typedUserDataForDb = userDataForDb;

			console.debug('[Sync API POST] Prepared data for ba_users upsert:', typedUserDataForDb);

			// --- Refined Upsert Logic ---
			const columns = ['id', 'email', 'name', 'username', 'updated_at'];
			const valuesToInsert = [
				typedUserDataForDb.id,
				typedUserDataForDb.email,
				typedUserDataForDb.name,
				typedUserDataForDb.username,
				typedUserDataForDb.updated_at
			];

			// Conditionally add image if it's provided and not null
			if (typedUserDataForDb.image !== null && typedUserDataForDb.image !== undefined) {
				columns.push('image');
				valuesToInsert.push(typedUserDataForDb.image);
			}

			const placeholders = columns.map(() => '?').join(', ');
			const columnNames = columns.join(', ');

			// Build the UPDATE part dynamically based on provided fields (excluding id)
			const updateSet = Object.entries(typedUserDataForDb)
				.filter(([key, value]) => key !== 'id' && value !== undefined) // Exclude id and undefined values
				.map(([key]) => `${key} = VALUES(${key})`)
				.join(', ');

			const upsertUserSql = `
				INSERT INTO ba_users (${columnNames})
				VALUES (${placeholders})
				ON DUPLICATE KEY UPDATE
					${updateSet ? updateSet + ',' : ''} updated_at = NOW()`; // Always update updated_at

			console.debug('[Sync API POST] Executing upsert for ba_users. SQL:', upsertUserSql.trim(), 'Params:', valuesToInsert);
			/** @type {QueryResult} */
			/** @type {[OkPacket | ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
			const [upsertResult] = await connection.execute(upsertUserSql, valuesToInsert);
			console.debug('[Sync API POST] ba_users upsert result:', upsertResult);
			// --- End Refined Upsert Logic ---

			// Optional: Update metadata if provided (Type Guard)
			if (userData.metadata && typeof userData.metadata === 'object') {
				const updateMetaSql = 'UPDATE ba_users SET metadata = ? WHERE id = ?';
				const metaParams = [JSON.stringify(userData.metadata), userData.skUserId];
				console.debug('[Sync API POST] Executing metadata update. SQL:', updateMetaSql, 'Params:', metaParams);
				await connection.execute(updateMetaSql, metaParams);
				console.debug('[Sync API POST] Metadata updated.');
			}

			// Upsert ba_wp_user_map (Safer than checking first)
			try {
				const mapSql = 'INSERT INTO ba_wp_user_map (wp_user_id, ba_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE ba_user_id = VALUES(ba_user_id)';
				await connection.execute(mapSql, [userData.wpUserId, userData.skUserId]);
				console.log(`[Sync API POST] Ensured mapping exists for wpUserId ${userData.wpUserId} to baUserId ${userData.skUserId}.`);
			} catch (/** @type {any} */ mapInsertError) {
				// This might happen due to constraints if data is inconsistent, log and possibly rollback
				console.error(`[Sync API POST] Error upserting into ba_wp_user_map: `, mapInsertError);
				await connection.rollback();
				console.debug('[Sync API POST] Transaction rolled back due to map upsert error.');
				return new Response(JSON.stringify({ success: false, message: 'Database error during user mapping sync' }), { status: 500, headers });
			}

			await connection.commit();
			console.debug('[Sync API POST] Transaction committed.');

			console.debug(`[Sync API POST] Broadcasting sync update for user ${userData.skUserId}`);
			const broadcastPayload = { userId: userData.skUserId, updatedAt: typedUserDataForDb.updated_at.toISOString() };
			broadcastSyncUpdate(JSON.stringify(broadcastPayload));

			return new Response(JSON.stringify({ success: true, message: 'User data synchronized' }), { status: 200, headers });

		} catch (/** @type {any} */ dbError) { // Catch for inner DB transaction block
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
 * @description Placeholder function to simulate fetching user data from WordPress.
 * **MUST return email and preferably username/display_name.**
 * @param {number|string} wpUserId The WordPress user ID.
 * @returns {Promise<WordPressUserData|null>} User data or null if not found.
 */
async function fetchWordPressUserData(wpUserId) {
	// Simulate fetching essential data needed for user creation
	// In a real implementation, this would call a secure WP REST endpoint
	// that returns user details based on the validated session/wpUserId.
	console.warn(`[Sync API - fetchWordPressUserData] Using placeholder data for wpUserId: ${wpUserId}. Replace with actual API call.`);
	/** @type {WordPressUserData} */
	const placeholderData = {
			wp_user_id: Number(wpUserId),
			display_name: `WP User ${wpUserId}`,
			user_email: `wpuser${wpUserId}@example.local`,
			username: `wpuser${wpUserId}`,
			avatar_url: '',
			user_roles: ['subscriber'],
			updatedAt: new Date().toISOString()
	};
	await new Promise(resolve => setTimeout(resolve, 150));
	return placeholderData;
}

/**
 * @description Creates a standard error response.
 * @param {any} error The error object.
 * @returns {Promise<Response>} A Response object.
 */
async function postError(error) { // JSDoc added in previous step
	// Ensure error.message exists, provide fallback
	const message = (error && typeof error === 'object' && 'message' in error) ? String(error.message) : 'An unknown error occurred';
	return new Response(JSON.stringify({ error: message }), {
		status: 500,
		headers: { 'Content-Type': 'application/json' }
	});
}
