import { json } from '@sveltejs/kit';
import { pool } from '$lib/server/auth'; // Assuming pool is exported from auth.js
import { randomUUID, randomBytes } from 'node:crypto';
// --- Add missing JSDoc imports for mysql2 types ---
/** 
 * @typedef {import('mysql2/promise').RowDataPacket} RowDataPacket
 * @typedef {import('mysql2/promise').OkPacket} OkPacket
 * @typedef {import('mysql2/promise').ResultSetHeader} ResultSetHeader
 */
// --- End added imports ---

// Assuming these helper functions are moved to a shared utility or redefined here if needed
// We need findUserByWpId, createUserFromWpId, createDbSession
// For simplicity, let's copy/adapt them here for now. Ideally, refactor later.

// --- Copied/Adapted Helper Functions (from /api/auth/sync/+server.js) ---

/**
 * Logs messages with context
 * @param {string} message
 * @param {'debug' | 'info' | 'warn' | 'error'} [level='info']
 */
function log(message, level = 'info') {
    const prefix = '[POST /api/auth/verify-sync-token]';
    // Basic console logging, adjust as needed
    console[level] ? console[level](`${prefix} ${message}`) : console.log(`${prefix} ${message}`);
}

/**
 * @typedef {Object} UserMetadata
 * @property {number} wp_user_id
 * @property {string[]} [roles]
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
 * @property {string[]} roles - User roles (likely from WP)
 * @property {string} [updatedAt] - Timestamp of last update
 */

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
 * Helper to fetch minimal WP user details (placeholder/requires implementation)
 * @param {number} wpUserId
 * @returns {Promise<{email: string, displayName: string} | null>}
 */
async function fetchWpUserDetails(wpUserId) {
    // --- Placeholder ---
    // In a real scenario, this would make a secure server-to-server call
    // to a dedicated WP endpoint to get email/display name for the given wpUserId.
    // The endpoint MUST be protected (e.g., require an API key or shared secret).
    log(`Placeholder: Fetching WP user details for ${wpUserId}`, 'warn');
    if (wpUserId > 0) {
        return {
            email: `wpuser${wpUserId}@example.local`, // Placeholder email
            displayName: `WP User ${wpUserId}` // Placeholder name
        };
    }
    return null;
    // --- End Placeholder ---
}


/**
 * Helper to create a user directly using the pool
 * @param {number} wpUserId
 * @param {string} email
 * @param {string} displayName
 * @returns {Promise<User | null>}
 */
async function createUserFromWpData(wpUserId, email, displayName) {
    if (!wpUserId || !email) {
        log(`Cannot create user: Missing wpUserId or email.`, 'error');
        return null;
    }
    let connection;
    try {
        connection = await pool.getConnection();
        const finalName = displayName || `WordPress User ${wpUserId}`;
        const finalUsername = email; // Use email as username
        const finalRoles = ['subscriber'];
        const metadata = { wp_user_id: wpUserId, roles: finalRoles };
        const metadataJson = JSON.stringify(metadata);
        const userId = randomUUID();

        const sql = 'INSERT INTO ba_users (id, email, username, name, metadata, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
        /** @type {[import('mysql2/promise').OkPacket | import('mysql2/promise').ResultSetHeader, any]} */
        const [result] = await connection.execute(sql, [userId, email, finalUsername, finalName, metadataJson]);

        if (result && 'affectedRows' in result && result.affectedRows === 1) {
            /** @type {User} */
            const newUser = {
                id: userId, email: email, username: finalUsername, name: finalName,
                metadata: metadata, betterAuthId: userId, displayName: finalName, roles: finalRoles,
                updatedAt: new Date().toISOString()
            };
            log(`Created new Better Auth user ${userId} linked to wpUserId ${wpUserId}`, 'info');
            return newUser;
        }
        log(`Failed to create user for wpUserId ${wpUserId}.`, 'error');
        return null;
    } catch (dbError) {
        log(`Database error during user creation for wpUserId ${wpUserId}: ${dbError instanceof Error ? dbError.message : dbError}`, 'error');
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
        /** @type {[import('mysql2/promise').OkPacket | import('mysql2/promise').ResultSetHeader, any]} */
        const [result] = await connection.execute(sql, [baUserId, sessionToken, expiresAt]);

        if (result && 'affectedRows' in result && result.affectedRows === 1) {
            return { sessionToken, expiresAt };
        }
        log(`Failed to create session in DB for baUserId ${baUserId}`, 'error');
        return null;
    } catch(dbError) {
         log(`DB Error creating session for baUserId ${baUserId}: ${dbError instanceof Error ? dbError.message : dbError}`, 'error');
         return null;
    }
     finally {
        connection?.release();
    }
}

// --- End Helper Functions ---


/**
 * Handles POST requests to verify a WP sync token and establish an SK session.
 * @type {import('./$types').RequestHandler}
 */
export async function POST({ request }) {
    const headers = new Headers({ 'Content-Type': 'application/json' });
    let tokenToValidate;

    try {
        const payload = await request.json();
        tokenToValidate = payload?.token;

        if (!tokenToValidate || typeof tokenToValidate !== 'string') {
            log('Missing or invalid token in request payload.', 'warn');
            return json({ success: false, error: 'Invalid request: Missing token.' }, { status: 400, headers });
        }
        log(`Received token for validation: ${tokenToValidate.substring(0, 6)}...`, 'debug');

    } catch (e) {
        log(`Error parsing request body: ${e instanceof Error ? e.message : e}`, 'error');
        return json({ success: false, error: 'Invalid request body.' }, { status: 400, headers });
    }

    // Define WP validation endpoint URL (should use env var)
    const WP_VALIDATE_URL = process.env.WP_VALIDATE_SYNC_TOKEN_URL || 'https://asapdigest.local/wp-json/asap/v1/validate-sync-token'; // Fallback to local

    try {
        log(`Calling WP validation endpoint: ${WP_VALIDATE_URL}`, 'debug');
        // Server-to-server fetch, no cookies needed here.
        const wpValidationResponse = await fetch(WP_VALIDATE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: tokenToValidate })
        });

        log(`WP validation response status: ${wpValidationResponse.status}`, 'info');
        const wpValidationData = await wpValidationResponse.json();
        log(`WP validation response data: ${JSON.stringify(wpValidationData)}`, 'debug');

        if (!wpValidationResponse.ok || !wpValidationData?.valid || !wpValidationData?.wpUserId) {
            log('WP token validation failed or missing user ID.', 'warn');
            return json({ success: false, error: 'WordPress token validation failed.' }, { status: 401, headers });
        }

        const wpUserId = wpValidationData.wpUserId;
        log(`WP token validated successfully for wpUserId: ${wpUserId}`, 'info');

        // --- REVISED LOGIC: Find or Create User/Account/Map here ---
        let baUser = await findUserByWpId(wpUserId);
        let targetSkUserId;
        let isNewlyCreated = false;

        if (!baUser) {
            // User NOT found, needs creation
            log(`No existing BA user for validated wpUserId ${wpUserId}. Attempting creation flow...`, 'info');
            
            // 1. Fetch required details from WP (using placeholder for now)
            console.warn('[Verify Token] Using PLACEHOLDER fetchWpUserDetails - Replace with secure WP endpoint call!');
            const wpDetails = await fetchWpUserDetails(wpUserId); // Using the placeholder

            if (!wpDetails || !wpDetails.email) {
                log(`Cannot create BA user for wpUserId ${wpUserId}: Failed to fetch required details (email) from placeholder WP fetch.`, 'error');
                return json({ success: false, error: 'User creation failed - missing WP details' }, { status: 500, headers });
            }
            console.log(`[Verify Token] Placeholder WP details fetched: ${JSON.stringify(wpDetails)}`, 'debug');

            // 2. Create User in ba_users
            const newUser = await createUserFromWpData(wpUserId, wpDetails.email, wpDetails.displayName);
            if (!newUser) {
                 log(`Failed to insert new user into ba_users for wpUserId ${wpUserId}`, 'error');
                 return json({ success: false, error: 'User creation failed during DB insert.' }, { status: 500, headers });
            }
            baUser = newUser; // Use the newly created user object
            targetSkUserId = newUser.id;
            isNewlyCreated = true;
            log(`[Verify Token] Successfully created new ba_user: ${targetSkUserId}`, 'info');

            // 3. Create Account in ba_accounts (Needs DB connection - refactor needed or pass connection)
            // For now, call a modified helper or inline the logic
            const accountCreated = await createAccountForUser(targetSkUserId);
            if (!accountCreated) {
                 log(`Failed to insert new account into ba_accounts for user ${targetSkUserId}`, 'error');
                 // Decide if this is critical - maybe proceed without account? For now, let's error.
                 return json({ success: false, error: 'User account creation failed during DB insert.' }, { status: 500, headers });
            }
            log(`[Verify Token] Successfully created ba_account for user: ${targetSkUserId}`, 'info');
            
            // 4. Create Map in ba_wp_user_map (Needs DB connection - refactor needed or pass connection)
            const mapCreated = await createMapForUser(wpUserId, targetSkUserId);
             if (!mapCreated) {
                 log(`Failed to insert mapping for wpUserId ${wpUserId} to ${targetSkUserId}`, 'error');
                 // Decide if this is critical - error for now.
                 return json({ success: false, error: 'User map creation failed during DB insert.' }, { status: 500, headers });
            }
            log(`[Verify Token] Successfully created ba_wp_user_map entry.`, 'info');

        } else {
            // User WAS found
            targetSkUserId = baUser.id;
            isNewlyCreated = false;
            log(`Existing BA user ${targetSkUserId} found for wpUserId ${wpUserId}.`, 'info');
        }
        // --- END REVISED LOGIC ---
        
        // Ensure we have a valid targetSkUserId before proceeding
        if (!targetSkUserId) {
             log('CRITICAL: targetSkUserId is missing after find/create flow. Aborting session creation.', 'error');
             return json({ success: false, error: 'Internal error during user processing.' }, { status: 500, headers });
        }

        // We have a valid BA user (found or created)
        log(`Proceeding to create session for user: ${targetSkUserId}`, 'info');

        // Create Better Auth session
        const sessionData = await createDbSession(targetSkUserId);

        if (!sessionData) {
            log(`Failed to create Better Auth session for user ${targetSkUserId}`, 'error');
            return json({ success: false, error: 'Session creation failed.' }, { status: 500, headers });
        }

        // --- Session Created Successfully ---
        const { sessionToken, expiresAt } = sessionData;
        log(`Created Better Auth session token: ${sessionToken.substring(0, 6)}... for user ${targetSkUserId}`, 'info');

        // Set session cookie
        const cookieName = process.env.SESSION_COOKIE_NAME || 'asap_sk_session'; // Use env var or default
        const cookieOptions = [
            `Path=/`,
            `Expires=${expiresAt.toUTCString()}`,
            `HttpOnly`,
            `SameSite=Lax`, // Use Lax for standard browser navigation
            // Secure flag should be added based on environment (e.g., in production)
            // This might be handled by SvelteKit adapter depending on config.
            // process.env.NODE_ENV === 'production' ? 'Secure' : ''
            'Secure' // <-- Always add Secure since we use HTTPS locally
        ].filter(Boolean).join('; ');

        headers.append('Set-Cookie', `${cookieName}=${sessionToken}; ${cookieOptions}`);
        log(`Session cookie prepared. Cookie options: ${cookieOptions}`, 'debug');

        // Return success, cookie will be set in the browser
        return json({ success: true }, { headers });

    } catch (error) {
        log(`Error during token verification process: ${error instanceof Error ? error.message : error}`, 'error');
        return json({ success: false, error: 'Internal server error during token verification.' }, { status: 500, headers });
    }
}

// --- ADD HELPER FUNCTIONS (potentially move later) ---

/**
 * Helper to create an account entry
 * @param {string} baUserId 
 * @returns {Promise<boolean>}
 */
async function createAccountForUser(baUserId) {
    let connection;
    try {
        connection = await pool.getConnection();
        const accountId = randomUUID();
        const insertAccountSql = `INSERT INTO ba_accounts (id, user_id, provider, provider_account_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())`;
        const accountParams = [accountId, baUserId, 'wp_sync', baUserId];
        /** @type {[OkPacket | ResultSetHeader, any]} */
        const [result] = await connection.execute(insertAccountSql, accountParams);
        return result?.affectedRows === 1;
    } catch (dbError) {
        log(`DB Error creating account for user ${baUserId}: ${dbError instanceof Error ? dbError.message : dbError}`, 'error');
        return false;
    } finally {
        connection?.release();
    }
}

/**
 * Helper to create user mapping
 * @param {number} wpUserId 
 * @param {string} baUserId 
 * @returns {Promise<boolean>}
 */
async function createMapForUser(wpUserId, baUserId) {
     let connection;
    try {
        connection = await pool.getConnection();
        const mapSql = 'INSERT INTO ba_wp_user_map (wp_user_id, ba_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE ba_user_id = VALUES(ba_user_id)';
        /** @type {[OkPacket | ResultSetHeader, any]} */
        const [result] = await connection.execute(mapSql, [wpUserId, baUserId]);
        // INSERT...ON DUPLICATE KEY UPDATE returns 1 for insert, 2 for update, 0 for no change.
        // We consider it a success if rows were affected or the mapping already existed.
        return result?.affectedRows >= 0; 
    } catch (dbError) {
        log(`DB Error creating/updating map for wpUserId ${wpUserId} <-> ${baUserId}: ${dbError instanceof Error ? dbError.message : dbError}`, 'error');
        return false;
    } finally {
        connection?.release();
    }
}
// --- END HELPER FUNCTIONS --- 