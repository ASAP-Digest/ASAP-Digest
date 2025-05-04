/**
 * @file Better Auth Configuration and Adapter Implementation
 * @created 2025-05-01
 * @milestone WP <-> SK Auto Login V6 - MILESTONE COMPLETED! 2025-05-03
 * 
 * This file contains the complete Better Auth configuration and adapter implementation
 * that enables auto-login between WordPress and SvelteKit. The implementation now:
 * 
 * 1. Creates proper users in ba_users with UUIDs
 * 2. Creates account records in ba_accounts linking users to WordPress
 * 3. Creates authenticated sessions in ba_sessions table
 * 4. Fixed all type errors with proper error handling
 * 
 * The Better Auth adapter pattern allows for custom database operations that maintain
 * the connection between WordPress users and SvelteKit authentication.
 */

import { betterAuth } from 'better-auth';
import mysql from 'mysql2/promise';
import { Kysely } from 'kysely';
import { MysqlDialect } from 'kysely';
import crypto from 'node:crypto';
import { 
    DB_HOST,
    DB_PORT,
    DB_USER,
    DB_PASS,
    DB_NAME,
    BETTER_AUTH_SECRET,
    BETTER_AUTH_URL
} from '$env/static/private'; // Restored import
import { createPool } from 'mysql2/promise';
import { log } from '$lib/utils/log';

/**
 * @typedef {Object} RequiredEnvVars
 * @property {string} DB_HOST - Database host
 * @property {number} DB_PORT - Database port
 * @property {string} DB_USER - Database user
 * @property {string} DB_PASS - Database password
 * @property {string} DB_NAME - Database name
 * @property {string} BETTER_AUTH_SECRET - Better Auth secret key
 * @property {string} BETTER_AUTH_URL - Better Auth base URL
 */

/**
 * @typedef {Object} WordPressUser
 * @property {number} wp_user_id - WordPress user ID
 */

/**
 * @typedef {Object} UserMetadata
 * @property {number} wp_user_id
 * @property {string[]} [roles]
 * @property {string} [registered]
 * @property {string} [locale]
 */

/**
 * Custom type definitions for Better Auth User and Session
 * @typedef {Object} User
 * @property {string} id - Better Auth User ID (UUID)
 * @property {string} betterAuthId - Better Auth User ID (should match id)
 * @property {string} email - User email
 * @property {string} [username] - Optional username from WP
 * @property {string} [name] - Optional display name from WP
 * @property {string} displayName - Primary display name (likely from WP)
 * @property {string} [avatarUrl] - URL to user avatar
 * @property {string[]} [roles] - User roles (likely from WP)
 * @property {string} [syncStatus] - Status of sync with WP
 * @property {string} [sessionToken] - Session token for client use
 * @property {string} [updatedAt] - Timestamp of last update from ba_users table (ISO 8601 format)
 * @property {Object} [metadata] - User metadata object
 * @property {number} [metadata.wp_user_id] - WordPress user ID in metadata
 * @property {string[]} [metadata.roles] - User roles in metadata
 */

/**
 * @typedef {Object} Session
 * @property {string} sessionId - Session ID (Primary Key, usually UUID)
 * @property {string} userId - Better Auth User ID
 * @property {string} token - Session token
 * @property {Date} expiresAt - Session expiry date
 * @property {Date} createdAt - Session creation date
 * @property {User} user - Session user
 */

/**
 * @typedef {Object} DBConfig
 * @property {string} host - Database host
 * @property {number} port - Database port
 * @property {string} user - Database user
 * @property {string} password - Database password
 * @property {string} database - Database name
 * @property {string} charset - Character set
 * @property {number} connectTimeout - Connection timeout
 * @property {boolean} waitForConnections - Wait for connections
 * @property {number} connectionLimit - Connection limit
 * @property {number} queueLimit - Queue limit
 */

// Database configuration - Directly use imported variables
const DB_CONFIG = {
    host: '127.0.0.1', // Force IPv4 loopback
    port: parseInt(DB_PORT || '3306', 10), // Use directly, ensure parsing
    user: DB_USER, // Use directly
    password: DB_PASS, // Use directly
    database: DB_NAME, // Use directly
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

// Create MySQL connection pool with proven settings
const pool = mysql.createPool({
    ...DB_CONFIG,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    enableKeepAlive: true,
    keepAliveInitialDelay: 0
});

// Create Kysely dialect
const dialect = new MysqlDialect({
    pool
});

// DEBUGGING: Log the dialect object before passing to betterAuth
// console.log('[auth.js DEBUG] Dialect object:', dialect);

/**
 * Log configuration issues with appropriate severity levels
 * @param {string} message - Message to log
 * @param {string} level - Severity level (info, warn, error, critical)
 */
function logConfig(message, level = 'info') {
    const prefix = '[Better Auth Config]';
    
    switch(level) {
        case 'warn':
            console.warn(`${prefix} Warning: ${message}`);
            break;
        case 'error':
            console.error(`${prefix} ERROR: ${message}`);
            break;
        case 'critical':
            console.error(`${prefix} CRITICAL: ${message}`);
            break;
        default:
            console.log(`${prefix} ${message}`);
    }
}

// --- Adapter Functions Definition START ---
// These functions provide the low-level database interactions for Better Auth.
// They are used by both the main 'auth' instance below AND the specific
// /api/auth/wp-user-sync endpoint.

/**
 * Get user by email (Adapter Implementation)
 * @param {string} email - User email
 * @returns {Promise<User|null>} User object or null if not found
 */
async function getUserByEmailFn(email) {
    logConfig(`Adapter: getUserByEmail called for ${email}`);
    let connection;
    try {
        connection = await pool.getConnection();
        const sql = 'SELECT * FROM ba_users WHERE email = ? LIMIT 1';
        const params = [email];
        /** @type {[import('mysql2/promise').RowDataPacket[], import('mysql2/promise').FieldPacket[]]} */
        const [rows, fields] = await connection.execute(sql, params);

        if (Array.isArray(rows) && rows.length > 0) {
            const userRow = rows[0];
            if (userRow && typeof userRow === 'object' && 'id' in userRow && 'email' in userRow) {
                logConfig(`Adapter: getUserByEmail found user for ${email}`);
                let metadata = userRow.metadata;
                if (typeof metadata === 'string') {
                    try {
                        metadata = JSON.parse(metadata);
                    } catch (e) {
                        logConfig(`Adapter: Failed to parse metadata for user ${userRow.id}`, 'warn');
                        metadata = {};
                    }
                }
                const user = {
                    id: String(userRow.id),
                    email: String(userRow.email),
                    username: userRow.username ? String(userRow.username) : undefined,
                    name: userRow.name ? String(userRow.name) : undefined,
                    metadata: metadata || {},
                    betterAuthId: String(userRow.id),
                    displayName: String(userRow.name || userRow.username || userRow.email),
                    roles: metadata?.roles || [],
                    syncStatus: /** @type {'pending' | 'synced' | 'error'} */ ('pending'), // <-- Keep type cast
                    updatedAt: userRow.updated_at ? new Date(userRow.updated_at).toISOString() : undefined,
                };
                logConfig(`Adapter: getUserByEmail returning user object for ${email}: ${JSON.stringify(user)}`);
                return user;
            } else {
                logConfig(`Adapter: getUserByEmail found row but missing expected properties for ${email}`, 'warn');
                return null;
            }
        } else {
            logConfig(`Adapter: getUserByEmail did not find user for ${email}`);
            return null;
        }
    } catch (error) {
        logConfig(`Adapter: Error in getUserByEmail for ${email}: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return null;
    } finally {
        if (connection) {
            connection.release();
        }
    }
}

/**
 * Get user by ID (Adapter Implementation)
 * @param {string} userId Better Auth user ID
 * @returns {Promise<User|null>} User object or null if not found
 */
async function getUserByIdFn(userId) {
    logConfig(`Adapter: getUserById called for ${userId}`);
    let connection;
    try {
        connection = await pool.getConnection();
        const sql = 'SELECT * FROM ba_users WHERE id = ? LIMIT 1';
        const params = [userId];
        /** @type {[import('mysql2/promise').RowDataPacket[], import('mysql2/promise').FieldPacket[]]} */
        const [rows, fields] = await connection.execute(sql, params);

        if (Array.isArray(rows) && rows.length > 0) {
            const userRow = rows[0];
            if (userRow && typeof userRow === 'object' && 'id' in userRow && 'email' in userRow) {
                logConfig(`Adapter: getUserById found user for ${userId}`);
                let metadata = userRow.metadata;
                if (typeof metadata === 'string') {
                    try {
                        metadata = JSON.parse(metadata);
                    } catch (e) {
                        logConfig(`Adapter: Failed to parse metadata for user ${userRow.id}`, 'warn');
                        metadata = {};
                    }
                }
                const user = {
                    id: String(userRow.id),
                    email: String(userRow.email),
                    username: userRow.username ? String(userRow.username) : undefined,
                    name: userRow.name ? String(userRow.name) : undefined,
                    metadata: metadata || {},
                    betterAuthId: String(userRow.id),
                    displayName: String(userRow.name || userRow.username || userRow.email),
                    roles: metadata?.roles || [],
                    syncStatus: /** @type {'pending' | 'synced' | 'error'} */ ('pending'), // <-- Keep type cast
                    updatedAt: userRow.updated_at ? new Date(userRow.updated_at).toISOString() : undefined,
                };
                logConfig(`Adapter: getUserById returning user object for ${userId}: ${JSON.stringify(user)}`);
                return user;
            } else {
                logConfig(`Adapter: getUserById found row but missing expected properties for ${userId}`, 'warn');
                return null;
            }
        } else {
            logConfig(`Adapter: getUserById did not find user for ${userId}`);
            return null;
        }
    } catch (error) {
        logConfig(`Adapter: Error in getUserById for ${userId}: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return null;
    } finally {
        if (connection) {
            connection.release();
        }
    }
}

/**
 * Get session by token (Adapter Implementation)
 * @param {string} sessionToken - Session token
 * @returns {Promise<Session|null>} Session object or null if not found/expired
 */
async function getSessionByTokenFn(sessionToken) {
    logConfig(`Adapter: getSessionByToken called for token: ${sessionToken ? 'present' : 'missing'}`);
    if (!sessionToken) return null;
    let connection;
    try {
        connection = await pool.getConnection();
        const sql = 'SELECT * FROM ba_sessions WHERE token = ? AND expires_at > NOW() LIMIT 1';
        const params = [sessionToken];
        /** @type {[import('mysql2/promise').RowDataPacket[], import('mysql2/promise').FieldPacket[]]} */
        const [rows, fields] = await connection.execute(sql, params);

        if (Array.isArray(rows) && rows.length > 0) {
            const sessionData = rows[0];
            if (sessionData && typeof sessionData === 'object' && 'user_id' in sessionData && 'token' in sessionData && 'expires_at' in sessionData && 'created_at' in sessionData) {
                logConfig(`Adapter: getSessionByToken found valid session for token.`);
                const user = await getUserByIdFn(String(sessionData.user_id)); // <-- Use existing getUserByIdFn

                if (user) {
                    logConfig(`Adapter: getSessionByToken found user ${user.id} for session.`);
                    return {
                        sessionId: String(sessionData.id),
                        userId: String(sessionData.user_id),
                        token: String(sessionData.token),
                        expiresAt: new Date(sessionData.expires_at),
                        createdAt: new Date(sessionData.created_at),
                        user: user
                    };
                } else {
                    logConfig(`Adapter: getSessionByToken could NOT find user ${sessionData.user_id} for valid session. Invalid state?`, 'warn');
                    return null;
                }
            } else {
                logConfig(`Adapter: getSessionByToken found row but missing expected properties for token`, 'warn');
                return null;
            }
        } else {
            logConfig(`Adapter: getSessionByToken did not find valid session for token.`);
            return null;
        }
    } catch (error) {
        logConfig(`Adapter: Error in getSessionByToken: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return null;
    } finally {
        if (connection) {
            connection.release();
        }
    }
}

/**
 * Create session (Adapter Implementation)
 * @param {string|Object} userIdOrObj - Better Auth user ID or object with userId property
 * @param {string} [sessionToken] - Session token
 * @param {Date} [expiresAt] - Expiry date
 * @returns {Promise<Session|null>} Created session object or null on error
 */
async function createSessionFn(userIdOrObj, sessionToken, expiresAt) {
    // Handle case where first parameter is an object with userId
    let userId;
    if (typeof userIdOrObj === 'object' && userIdOrObj !== null && 'userId' in userIdOrObj) {
        userId = String(userIdOrObj.userId);
        logConfig(`Adapter: createSession called with object containing userId: ${userId}`);
    } else {
        userId = String(userIdOrObj);
    }
    
    logConfig(`Adapter: createSession called for user ${userId}`);
    
    // Generate token and expiry date if not provided
    if (!sessionToken) {
        sessionToken = crypto.randomUUID();
        logConfig(`Adapter: createSession generated new token for user ${userId}`);
    }
    
    if (!expiresAt) {
        expiresAt = new Date(Date.now() + (30 * 24 * 60 * 60 * 1000)); // 30 days default
        logConfig(`Adapter: createSession using default expiry date for user ${userId}`);
    }
    
    let connection;
    try {
        connection = await pool.getConnection();
        const createdAt = new Date();

        // Delete existing sessions for this user first
        try {
            const deleteSql = 'DELETE FROM ba_sessions WHERE user_id = ?';
            await connection.execute(deleteSql, [userId]);
            logConfig(`Adapter: createSession deleted existing sessions for user ${userId}`);
        } catch (deleteError) {
            logConfig(`Adapter: Error deleting existing sessions for user ${userId}: ${deleteError instanceof Error ? deleteError.message : String(deleteError)}`, 'warn');
        }
        
        const sessionId = crypto.randomUUID(); // Generate UUID for the session ID

        // Check the actual schema of the ba_sessions table
        // The error suggests 'session_token' column may not exist, it might be named 'token' instead
        const sql = 'INSERT INTO ba_sessions (id, user_id, token, expires_at, created_at) VALUES (?, ?, ?, ?, ?)'; 
        const params = [sessionId, userId, sessionToken, expiresAt, createdAt];
        
        /** @type {[import('mysql2/promise').ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
        const [result, fields] = await connection.execute(sql, params);

        if (result && typeof result === 'object' && 'affectedRows' in result && result.affectedRows > 0) {
            logConfig(`Adapter: createSession successfully inserted session ${sessionId} for user ${userId}`);
            const user = await getUserByIdFn(userId);
            if (user) {
                // Return object matching the updated Session typedef
                return {
                    sessionId: sessionId, // Use sessionId field
                    userId: userId,
                    token: sessionToken,
                    expiresAt: expiresAt,
                    createdAt: createdAt,
                    user: user
                };
            } else {
                logConfig(`Adapter: createSession could not fetch user ${userId} after inserting session.`, 'warn');
                return null;
            }
        } else {
            logConfig(`Adapter: createSession failed to insert session for user ${userId}. DB Result: ${JSON.stringify(result)}`, 'error');
            return null;
        }
    } catch (error) {
        logConfig(`Adapter: Error in createSession for user ${userId}: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return null;
    } finally {
        if (connection) {
            connection.release();
        }
    }
}

/**
 * Delete session by token (Adapter Implementation)
 * @param {string} sessionToken - Session token
 * @returns {Promise<void>}
 */
async function deleteSessionFn(sessionToken) {
    logConfig(`Adapter: deleteSession called for token: ${sessionToken ? 'present' : 'missing'}`);
    if (!sessionToken) return;
    let connection;
    try {
        connection = await pool.getConnection();
        const sql = 'DELETE FROM ba_sessions WHERE token = ?';
        const params = [sessionToken];
        await connection.execute(sql, params);
        logConfig(`Adapter: deleteSession executed for token.`);
    } catch (error) {
        logConfig(`Adapter: Error in deleteSession: ${error instanceof Error ? error.message : String(error)}`, 'error');
    } finally {
        if (connection) {
            connection.release();
        }
    }
}

/**
 * Find user by WordPress ID stored in metadata (Adapter Implementation)
 * @param {number} wpUserId - WordPress User ID
 * @returns {Promise<User|null>} User object or null if not found
 */
async function getUserByWpIdFn(wpUserId) {
    logConfig(`Adapter: getUserByWpId called for WP ID ${wpUserId}`);
    let connection;
    try {
        connection = await pool.getConnection();
        const sql = "SELECT * FROM ba_users WHERE JSON_EXTRACT(metadata, '$.wp_user_id') = ? LIMIT 1";
        const params = [wpUserId];
        /** @type {[import('mysql2/promise').RowDataPacket[], import('mysql2/promise').FieldPacket[]]} */
        const [rows, fields] = await connection.execute(sql, params);

        if (Array.isArray(rows) && rows.length > 0) {
            const userRow = rows[0];
            if (userRow && typeof userRow === 'object' && 'id' in userRow && 'email' in userRow) {
                logConfig(`Adapter: getUserByWpId found user ${userRow.id} for WP ID ${wpUserId}`);
                let metadata = userRow.metadata;
                if (typeof metadata === 'string') {
                    try {
                        metadata = JSON.parse(metadata);
                    } catch (e) {
                        logConfig(`Adapter: Failed to parse metadata for user ${userRow.id}`, 'warn');
                        metadata = {};
                    }
                }
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
            } else {
                 logConfig(`Adapter: getUserByWpId found row but missing expected properties for WP ID ${wpUserId}`, 'warn');
                return null;
            }
        } else {
            logConfig(`Adapter: getUserByWpId did not find user for WP ID ${wpUserId}`);
            return null;
        }
    } catch (error) {
        logConfig(`Adapter: Error in getUserByWpId for WP ID ${wpUserId}: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return null;
    } finally {
        if (connection) {
            connection.release();
        }
    }
}

/**
 * Create a new user (Adapter Implementation)
 * @param {{ wpUserId: number, email?: string, username?: string, name?: string, roles?: string[] }} userData - Data for the new user, wpUserId is mandatory.
 * @returns {Promise<User|null>} The created user object or null on failure
 */
async function createUserFn(userData) {
    const { wpUserId, email, username, name, roles } = userData;
    logConfig(`Adapter: createUser called for WP ID ${wpUserId}`);
    if (!wpUserId) {
        logConfig(`Adapter: createUser failed - wpUserId is required.`, 'error');
        return null;
    }

    let connection;
    try {
        connection = await pool.getConnection();
        const finalEmail = email || `wp_user_${wpUserId}@asapdigest.local`; 
        const finalUsername = username || `wp_user_${wpUserId}`;
        const finalName = name || `WordPress User ${wpUserId}`;
        const finalRoles = roles || ['subscriber'];
        const metadata = { wp_user_id: wpUserId, roles: finalRoles };
        const metadataJson = JSON.stringify(metadata);
        const userId = crypto.randomUUID(); 
        const sql = 'INSERT INTO ba_users (id, email, username, name, metadata, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
        const params = [userId, finalEmail, finalUsername, finalName, metadataJson];
        /** @type {[import('mysql2/promise').OkPacket|import('mysql2/promise').ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
        const [result, fields] = await connection.execute(sql, params);

        if (result && 'affectedRows' in result && result.affectedRows === 1) {
            logConfig(`Adapter: createUser successfully created user ${userId} for WP ID ${wpUserId}`);
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
        } else {
             logConfig(`Adapter: createUser failed to insert user for WP ID ${wpUserId}. Result: ${JSON.stringify(result)}`, 'error');
            return null;
        }
    } catch (error) {
        logConfig(`Adapter: Error in createUser for WP ID ${wpUserId}: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return null;
    } finally {
        if (connection) {
            connection.release();
        }
    }
}

/**
 * Create a linked account record (Adapter Implementation)
 * Associates a Better Auth user ID with a specific provider and provider ID.
 * 
 * @param {{ userId: string, provider: string, providerAccountId: string }} accountData - Account linking data.
 * @returns {Promise<boolean>} Success status.
 */
async function createAccountFn(accountData) {
    const { userId, provider, providerAccountId } = accountData;
    logConfig(`Adapter: createAccount called for user ${userId}, provider ${provider}, providerId ${providerAccountId}`);
    if (!userId || !provider || !providerAccountId) {
        logConfig(`Adapter: createAccount failed - userId, provider, and providerAccountId are required.`, 'error');
        return false;
    }

    try {
        // Generate a UUID for the account record
        const accountId = crypto.randomUUID();
        
        await pool.execute('INSERT INTO ba_accounts (id, user_id, provider, provider_account_id, created_at) VALUES (?, ?, ?, ?, NOW())', [accountId, userId, provider, providerAccountId]);

        logConfig(`Adapter: createAccount successfully linked user ${userId} to ${provider}:${providerAccountId}`);
        return true;
    } catch (error) {
        logConfig(`Adapter: Error in createAccount for user ${userId} to ${provider}:${providerAccountId}: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return false;
    }
}

// --- RESTORED Adapter Functions Definition END ---

// Define the adapter object used for initializing the main auth instance.
// This uses the functions defined above.
const adapter = {
    getUserByEmail: getUserByEmailFn,
    getUserById: getUserByIdFn,
    getSessionByToken: getSessionByTokenFn,
    createSession: createSessionFn,
    deleteSession: deleteSessionFn,
    getUserByWpId: getUserByWpIdFn,
    createUser: createUserFn,
    createAccount: createAccountFn
};

// --- Hook Functions Definition ---
// These hooks are kept for potential future use but are currently empty
// as the legacy WP sync logic they contained has been removed.

/**
 * Called after user creation
 * @param {User} user - Created user object
 * @returns {Promise<void>}
 * @deprecated REMOVED: 2025-05-16 - WordPress user creation now handled via GraphQL viewer query + wp-user-sync endpoint
 */
async function onUserCreationHook(user) {
    logConfig(`User creation hook triggered for ${user.id}`);
    // Legacy WordPress user creation removed - now handled via GraphQL + wp-user-sync endpoint
}

/**
 * Called after session creation
 * @param {Session} session - Created session object
 * @returns {Promise<void>}
 * @deprecated REMOVED: 2025-05-16 - WordPress session creation now handled via GraphQL viewer query + wp-user-sync endpoint
 */
async function onSessionCreationHook(session) {
    logConfig(`Session creation hook triggered for ${session.sessionId}`);
    // Legacy WordPress session creation removed - now handled via GraphQL + wp-user-sync endpoint
}

/**
 * Get a WordPress nonce for authenticated requests
 * @returns {Promise<string>} - WordPress nonce
 */
async function getNonce() {
    // Check if we have a cached nonce
    const cachedNonce = sessionStorage.getItem('wp_nonce');
    if (cachedNonce) {
        return cachedNonce;
    }

    try {
        const response = await fetch('/wp-json/wp/v2/nonce');
        if (!response.ok) {
            throw new Error('Failed to fetch nonce');
        }
        const nonce = await response.text();
        sessionStorage.setItem('wp_nonce', nonce);
        return nonce;
    } catch (error) {
        console.error('Error fetching nonce:', error);
        return '';
    }
}

// --- Better Auth Instance Initialization ---
// Initialize the main Better Auth instance (`auth`).
// This instance handles standard authentication flows (email/pass, social, OTP etc.)
// via the generic [...auth] endpoint and the svelteKitHandler.
// It uses the Kysely dialect and the custom adapter defined above.

/**
 * Better Auth instance configured for this application.
 * 
 * Note: We're using a fallback/type assertion pattern here because the betterAuth() function 
 * returns a type that doesn't match our BetterAuth interface exactly, but has the properties 
 * we need at runtime. This follows section 5.1 of the type-definition-management-protocol.
 * 
 * @type {any}
 */
const authResult = betterAuth({
    secret: BETTER_AUTH_SECRET, 
    sessionCookieName: 'better_auth_session',
    sessionExpiresIn: 30 * 24 * 60 * 60 * 1000,
    database: { dialect: dialect, type: "mysql" },
    adapter: adapter,
    after: { onUserCreation: onUserCreationHook, onSessionCreation: onSessionCreationHook }
});

/**
 * Type guard to verify if an object has the expected Better Auth structure
 * @param {any} obj Object to test
 * @returns {boolean} Whether the object has the expected structure
 */
function isBetterAuth(obj) {
    return obj && 
           typeof obj === 'object' &&
           'adapter' in obj && 
           'sessionManager' in obj &&
           obj.adapter && 
           obj.sessionManager;
}

/**
 * Enhanced Better Auth instance with type assertion for compatibility
 * Type assertion needed as direct typing fails due to structure differences
 * @type {import('$lib/types/better-auth').BetterAuth}
 */
const auth = /** @type {import('$lib/types/better-auth').BetterAuth} */ (
    isBetterAuth(authResult) ? authResult : {
        // Fallback to avoid runtime errors if the structure is unexpected
        sessionManager: {
            getSession: async () => null,
            createSession: async () => { throw new Error('Auth not properly initialized'); },
            refreshSession: async () => null,
            deleteSession: async () => false
        },
        adapter: adapter,
        options: {
            secret: BETTER_AUTH_SECRET,
            sessionCookieName: 'better_auth_session'
        },
        handler: async (request) => new Response('Auth system not properly initialized', { status: 500 })
    }
);

// --- Exports ---
export {
    auth, 
    getUserByEmailFn,
    getUserByIdFn,
    getUserByWpIdFn,
    createUserFn,
    createAccountFn,
    createSessionFn,
    getSessionByTokenFn,
    deleteSessionFn,
    onUserCreationHook,
    onSessionCreationHook,
    pool,
    getNonce
};