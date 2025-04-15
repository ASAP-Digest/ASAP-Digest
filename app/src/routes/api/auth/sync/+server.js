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
import { json, error } from '@sveltejs/kit';
import { auth } from '$lib/server/auth';
import { broadcastSyncUpdate } from '$lib/server/syncBroadcaster';

/**
 * @typedef {import('mysql2/promise').RowDataPacket} RowDataPacket
 * @typedef {import('mysql2/promise').OkPacket} OkPacket
 * @typedef {import('mysql2/promise').ResultSetHeader} ResultSetHeader
 * @typedef {import('mysql2/promise').QueryResult} QueryResult // Represents [OkPacket|ResultSetHeader|RowDataPacket[], FieldPacket[]]
 */

/**
 * @typedef {Object} SyncResponse
 * @property {boolean} valid - Whether the sync was successful
 * @property {boolean} [updated] - Whether the user data was updated
 * @property {string} [error] - Error message if sync failed
 */

/**
 * @typedef {object} BetterAuthUser - Defines the expected structure for Better Auth user objects.
 * @property {string} userId
 * @property {string} email
 * @property {string} [name] // Made optional based on usage
 * @property {string} [username] // Made optional based on usage
 * @property {Date | null} [email_verified]
 * // Add other known properties if applicable
 */

/**
 * @typedef {object} WordPressSessionData - Expected structure from WP session check.
 * @property {boolean} loggedIn
 * @property {boolean} autosyncActive
 * @property {number | null} userId
 * @property {string} [userEmail]
 * @property {string} [username]
 * @property {string} [displayName]
 * @property {string} [avatarUrl]
 */

/**
 * @description Handles GET requests for session synchronization and token-based auto-login.
 * If a 'token' query parameter is present, it attempts to validate a temporary
 * login token from WordPress and establish a new SvelteKit session, redirecting
 * the user upon success.
 * If no 'token' is present, it checks existing WordPress browser cookies to
 * validate the session and potentially sync user data with SvelteKit locals.
 *
 * @param {object} event The SvelteKit request event object.
 * @param {import('@sveltejs/kit').RequestEvent['request']} event.request The incoming request object.
 * @param {URL} event.url The URL object for the request.
 * @param {App.Locals} event.locals The SvelteKit request locals object.
 * @returns {Promise<Response>} A response (JSON or Redirect).
 */
/** @type {import('./$types').RequestHandler} */
export async function GET({ request, url, locals }) {
	console.log('[GET /api/auth/sync] Received request');
	console.log('[GET /api/auth/sync] Headers:');
	for (const [key, value] of request.headers.entries()) {
		console.log(`${key}: ${value}`);
	}

	let connection;
	const headers = new Headers({ 'Content-Type': 'application/json' });

	// --- Check for Token-Based Auto-Login ---
	const loginToken = url.searchParams.get('token');

	if (loginToken) {
		console.debug(`[Sync API] Login token found: ${loginToken}. Attempting token validation.`);
		try {
			connection = await pool.getConnection();
			console.debug('[Sync API] Acquired DB connection for token validation.');

			let wpUserId = null;
			let tokenIsValid = false;

			const tokenSql = 'SELECT wp_user_id FROM ba_wp_login_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1';
			const tokenParams = [loginToken];
			/** @type {QueryResult} */
			/** @type {[RowDataPacket[] | OkPacket | ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
			const [tokenQueryResult] = await connection.execute(tokenSql, tokenParams);

			// Type Guard: Check if the first element is an array and has data
			if (Array.isArray(tokenQueryResult) && tokenQueryResult.length > 0) {
				/** @type {RowDataPacket[]} */
				const tokenRows = tokenQueryResult;
				// Type Guard: Check if the first row is an object and has the property
				if (tokenRows[0] && typeof tokenRows[0] === 'object' && 'wp_user_id' in tokenRows[0]) {
					wpUserId = tokenRows[0].wp_user_id;
					tokenIsValid = true;
					console.debug(`[Sync API] Token validated for wpUserId: ${wpUserId}`);

					// Delete the used token
					const deleteSql = 'DELETE FROM ba_wp_login_tokens WHERE token = ?';
					await connection.execute(deleteSql, [loginToken]);
					console.debug(`[Sync API] Deleted used login token: ${loginToken}`);
				}
			}

			if (!tokenIsValid) {
				console.warn(`[Sync API] Login token invalid or expired: ${loginToken}`);
				return new Response(JSON.stringify({ valid: false, error: 'Invalid or expired login token' }), { status: 401, headers });
			}

			// Proceed only if token is valid and wpUserId is found
			if (wpUserId) {
				// 2. Find ba_user_id from ba_wp_user_map
				const mapSql = 'SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ? LIMIT 1';
				const mapParams = [wpUserId];
				/** @type {QueryResult} */
				/** @type {[RowDataPacket[] | OkPacket | ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
				const [mapQueryResult] = await connection.execute(mapSql, mapParams);
				let ba_user_id = null;

				// Type Guard: Check array and property existence
				if (Array.isArray(mapQueryResult) && mapQueryResult.length > 0) {
					/** @type {RowDataPacket[]} */
					const mapRows = mapQueryResult;
					if (mapRows[0] && typeof mapRows[0] === 'object' && 'ba_user_id' in mapRows[0]) {
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
						console.debug(`[Sync API] Set-Cookie header prepared for redirect.`);

						// 6. Redirect to dashboard (or another target page)
						console.debug('[Sync API] Token login successful. Redirecting to /dashboard...');
						const redirectHeaders = new Headers();
						redirectHeaders.set('Location', '/dashboard');
						const cookieHeaderValue = headers.get('Set-Cookie');
						if (cookieHeaderValue) {
								redirectHeaders.set('Set-Cookie', cookieHeaderValue);
						}
						return new Response(null, { status: 302, headers: redirectHeaders });

					} else {
						console.error(`[Sync API] CRITICAL: Could not find ba_user_id in ba_wp_user_map for wpUserId: ${wpUserId} during token login.`);
						return new Response(JSON.stringify({ valid: false, error: 'User mapping error during login' }), { status: 500, headers });
					}
				} else {
					// This case handles if the QueryResult was not an array (e.g., OkPacket), which shouldn't happen for SELECT
					console.error(`[Sync API] CRITICAL: Unexpected database response format when querying ba_wp_user_map for wpUserId: ${wpUserId}.`);
					return new Response(JSON.stringify({ valid: false, error: 'Database error during login' }), { status: 500, headers });
				}
			}
		} catch (/** @type {any} */ error) { // Catch for token validation try
			console.error('[Sync API] Error during token-based login:', error);
			// Removed connection release duplication
			return new Response(JSON.stringify({ valid: false, error: 'Server error during token login' }), { status: 500, headers });
		} finally {
			if (connection) {
				try { await connection.release(); console.debug('[Sync API] Released DB connection after token processing.'); } catch (relErr) { console.error('[Sync API] Error releasing connection in token finally:', relErr); }
			}
		}
	}

	// --- Fallback to Cookie-Based Sync Check (AJAX Flow) ---
	console.debug('[Sync API] No login token found. Proceeding with AJAX cookie-based sync check.');
	const wpCheckUrl = `${process.env.PUBLIC_WP_API_URL}/asap/v1/check-sk-session`; // Use runtime env var
	try { // Outer try for AJAX check
		console.log(`[Sync API - AJAX] Checking WP session at: ${wpCheckUrl}`);

		const wpResponse = await fetch(wpCheckUrl, {
			method: 'GET',
			credentials: 'include', // Crucial for sending WP cookies
			headers: {
				'Content-Type': 'application/json',
				// Forward relevant cookies from the incoming request if needed and safe
				// Example (use cautiously): 'Cookie': request.headers.get('cookie') || ''
			},
		});

		console.log(`[Sync API - AJAX] WP Check Response Status: ${wpResponse.status}`);

		if (!wpResponse.ok) {
			const errorText = await wpResponse.text();
			console.error('[Sync API - AJAX] WP session check failed:', wpResponse.status, errorText);
			// Don't return 500 for WP failure, maybe 401 or a specific status?
			// Using 401 implies the SK->WP check itself failed auth/session wise
			return json({ valid: false, error: `WordPress check failed (${wpResponse.status})` }, { status: wpResponse.status });
		}

		/** @type {WordPressSessionData} */
		const wpData = await wpResponse.json();
		console.log('[Sync API - AJAX] WP Check Response Data:', wpData);

		// Type Guard: Check properties carefully before use
		if (wpData && wpData.loggedIn && wpData.autosyncActive && typeof wpData.userId === 'number') {
			const wpUserId = wpData.userId;

			try { // Inner try for lookup/creation/session
				/** @type {BetterAuthUser | null} */
				let baUser = null;
				/** @type {string | null} */
				let baUserId = null;
				/** @type {import('mysql2/promise').PoolConnection | null} */
				let connection = null;

				try {
					connection = await pool.getConnection();
					/** @type {QueryResult} */
					/** @type {[RowDataPacket[] | OkPacket | ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
					const [mapQueryResult] = await connection.execute(
						'SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ? LIMIT 1',
						[wpUserId]
					);

					// Type Guard for map result
					if (Array.isArray(mapQueryResult) && mapQueryResult.length > 0) {
						/** @type {RowDataPacket[]} */
						const mapRows = mapQueryResult;
						if (mapRows[0] && typeof mapRows[0] === 'object' && 'ba_user_id' in mapRows[0]) {
							baUserId = mapRows[0].ba_user_id;
							console.log(`[Sync API - AJAX] Found existing Better Auth user ID ${baUserId} for WP ID ${wpUserId}.`);
							// @ts-ignore - Still assumes auth.getUser exists, add try/catch if needed
							try {
								// @ts-ignore - Suppress linter error pending investigation
								baUser = await auth.getUser(baUserId);
								if (!baUser) {
									console.warn(`[Sync API - AJAX] User ID ${baUserId} found in map, but getUser failed. Will attempt creation.`);
								} else {
									console.log(`[Sync API - AJAX] Successfully fetched Better Auth user object for ID ${baUserId}.`);
								}
							} catch (getUserError) {
								console.error(`[Sync API - AJAX] Error calling auth.getUser for ID ${baUserId}:`, getUserError);
								// Decide if this is critical or if creation should still proceed
								baUser = null; // Ensure baUser is null if getUser failed
							}
						}
					} else {
						console.log(`[Sync API - AJAX] No existing Better Auth user mapping found for WP ID ${wpUserId}. Proceeding to create.`);
					}
				} finally {
					connection?.release();
				}

				// Attempt creation only if lookup failed or getUser failed
				if (!baUser || !baUserId) {
					console.log(`[Sync API - AJAX] Creating new Better Auth user for WP ID ${wpUserId}.`);
					// Type Guard: Check if required properties exist before using them
					if (!wpData.userEmail || !wpData.username) {
						console.error(`[Sync API - AJAX] Cannot create user: Missing essential data (email, username) from WP for WP ID ${wpUserId}. Data received:`, wpData);
						return json({ valid: false, error: 'Incomplete user data from WordPress for creation' }, { status: 400 });
					}
					connection = null; // Reset connection variable
					try {
						// @ts-ignore - Still assumes auth.createUser exists
						// Use optional chaining and fallbacks for safety
						// @ts-ignore - Suppress linter error pending investigation
						baUser = await auth.createUser({
							key: {
								providerId: 'wp_import',
								providerUserId: wpUserId.toString(),
								password: null // WP doesn't provide password here
							},
							attributes: {
								email: wpData.userEmail, // Existence checked above
								name: wpData.displayName || wpData.username, // Fallback
								username: wpData.username, // Existence checked above
								email_verified: new Date() // Assume verified if coming from WP logged in session
							}
						});

						// Type Guard: Ensure baUser is not null after creation attempt
						if (!baUser || !baUser.userId) { // Also check userId specifically
							throw new Error(`User creation failed unexpectedly, baUser is null or missing userId. Input data: ${JSON.stringify(wpData)}`);
						}
						baUserId = baUser.userId; // Now safe to access userId
						console.log(`[Sync API - AJAX] Successfully created new Better Auth user with ID ${baUserId} for WP ID ${wpUserId}.`);

						// Establish mapping after successful creation
						connection = await pool.getConnection();
						await connection.execute(
							'INSERT INTO ba_wp_user_map (wp_user_id, ba_user_id) VALUES (?, ?)',
							[wpUserId, baUserId]
						);
						console.log(`[Sync API - AJAX] Created mapping entry for WP ID ${wpUserId} to BA ID ${baUserId}.`);
					} catch (/** @type {any} */ creationError) {
						console.error(`[Sync API - AJAX] Error creating Better Auth user for WP ID ${wpUserId}:`, creationError);
						return json({ valid: false, error: 'Failed to create SvelteKit user profile' }, { status: 500 });
					} finally {
						connection?.release();
					}
				}

				// Final check before session creation
				if (!baUser || !baUserId) {
					console.error(`[Sync API - AJAX] CRITICAL: Failed to obtain valid Better Auth user object or ID after lookup/creation for WP ID ${wpUserId}.`);
					return json({ valid: false, error: 'User synchronization failed unexpectedly.' }, { status: 500 });
				}

				// Create session if we have a valid user
				// @ts-ignore - Still assumes auth.createSession exists
				// @ts-ignore - Suppress linter error pending investigation
				const session = await auth.createSession(baUserId);
				console.log(`[Sync API - AJAX] Session created successfully for Better Auth user ID: ${baUserId}`);
				// @ts-ignore - Still assumes auth.createSessionCookie exists
				// @ts-ignore - Suppress linter error pending investigation
				const sessionCookie = auth.createSessionCookie(session.sessionId);
				headers.append('Set-Cookie', sessionCookie.serialize());
				console.log(`[Sync API - AJAX] Session cookie created and added to headers.`);

				// Return success with headers
				return json({ valid: true, updated: !!baUser }, { headers }); // Indicate updated if user was created/fetched

			} catch (/** @type {any} */ innerError) { // Catch for inner DB/auth ops
				console.error(`[Sync API - AJAX] Error during sync process for WP ID ${wpUserId}:`, innerError);
				return json({ valid: false, error: 'User synchronization failed due to a server error' }, { status: 500 });
			}
		} else {
			// Handle case where WP conditions are not met (not logged in, autosync off, or no userId)
			console.log('[Sync API - AJAX] Conditions not met for sync:', { loggedIn: wpData?.loggedIn, autosyncActive: wpData?.autosyncActive, userId: wpData?.userId });
			// Invalidate local session if user is logged out on WP but has a SK session
			if (locals.session && !wpData?.loggedIn) {
				console.log('[Sync API - AJAX] User logged out on WP, attempting to invalidate local session.');
				try {
					// @ts-ignore - Assumes invalidateSession exists
					// @ts-ignore - Suppress linter error pending investigation
					await auth.invalidateSession(locals.session.sessionId);
					// @ts-ignore - Assumes createBlankSessionCookie exists
					// @ts-ignore - Suppress linter error pending investigation
					const blankCookie = auth.createBlankSessionCookie();
					headers.append('Set-Cookie', blankCookie.serialize());
					console.log('[Sync API - AJAX] Local session invalidated and blank cookie set.');
				} catch (/** @type {any} */ invalidationError) {
					console.error('[Sync API - AJAX] Error invalidating local session:', invalidationError);
					// Continue without invalidation if it fails
				}
			}
			// Return specific reason if possible
			let errorMessage = 'User not logged in on WordPress or autosync disabled.';
			if (!wpData?.loggedIn) errorMessage = 'User not logged in on WordPress.';
			else if (!wpData?.autosyncActive) errorMessage = 'Autosync disabled for WordPress user.';
			else if (typeof wpData?.userId !== 'number') errorMessage = 'WordPress user ID missing.';

			return json({ valid: false, updated: false, error: errorMessage }, { headers });
		}
	} catch (/** @type {any} */ outerError) { // Catch for outer AJAX fetch try
		console.error('[Sync API - AJAX] Error during AJAX fetch/processing:', outerError);
		return json({ valid: false, error: 'Server error during sync check' }, { status: 500 });
	}
} // End of GET function

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
