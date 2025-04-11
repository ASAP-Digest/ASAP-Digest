import { betterAuth } from 'better-auth';
import mysql from 'mysql2/promise';
import { Kysely } from 'kysely';
import { MysqlDialect } from 'kysely';
import { 
    DB_HOST,
    DB_PORT,
    DB_USER,
    DB_PASS,
    DB_NAME,
    BETTER_AUTH_SECRET,
    BETTER_AUTH_URL
} from '$env/static/private'; // Restored import

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
 * @typedef {Object} Session
 * @property {string} id - Session ID
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

// Ensure required environment variables are available
const requiredEnvVars = {
    DB_HOST: process.env.DB_HOST || 'localhost',
    DB_PORT: parseInt(process.env.DB_PORT || '10018', 10),
    DB_USER: process.env.DB_USER || 'root',
    DB_PASS: process.env.DB_PASS || 'root',
    DB_NAME: process.env.DB_NAME || 'local',
    BETTER_AUTH_SECRET: process.env.BETTER_AUTH_SECRET,
    BETTER_AUTH_URL: process.env.BETTER_AUTH_URL || 'http://localhost:5173'
};

// Validate environment variables
Object.entries(requiredEnvVars).forEach(([key, value]) => {
    if (!value) {
        throw new Error(`${key} environment variable is required but not set`);
    }
});

// Database configuration
const DB_CONFIG = {
    host: DB_HOST || 'localhost',
    port: parseInt(DB_PORT || '10018', 10),
    user: DB_USER || 'root',
    password: DB_PASS || 'root',
    database: DB_NAME || 'local',
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

// Validate database environment variables
const dbEnvVars = ['DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASS', 'DB_NAME'];
dbEnvVars.forEach(envVar => {
    if (!process.env[envVar]) {
        // Use a type-safe approach to handle config keys
        let defaultValue = '';
        switch(envVar) {
            case 'DB_HOST':
                defaultValue = DB_CONFIG.host;
                break;
            case 'DB_PORT':
                defaultValue = DB_CONFIG.port.toString();
                break;
            case 'DB_USER':
                defaultValue = DB_CONFIG.user;
                break;
            case 'DB_PASS':
                defaultValue = DB_CONFIG.password;
                break;
            case 'DB_NAME':
                defaultValue = DB_CONFIG.database;
                break;
        }
        logConfig(`${envVar} environment variable is not set, using default: ${defaultValue}`, 'warn');
    }
});

// Validate Better Auth environment variables
const authSecret = process.env.BETTER_AUTH_SECRET;
const authBaseURL = process.env.BETTER_AUTH_URL || 'http://localhost:5173';

if (!authSecret) {
    logConfig('BETTER_AUTH_SECRET environment variable is not set! Auth will not work correctly.', 'critical');
    // We'll throw an error later to prevent app startup
}

if (!process.env.BETTER_AUTH_URL) {
    logConfig(`BETTER_AUTH_URL environment variable is not set, using default: ${authBaseURL}`, 'warn');
}

/**
 * Create a WordPress user for the Better Auth user
 * This is called after a user signs up in Better Auth
 * 
 * @param {User} user Better Auth user object
 * @param {number} [retries=3] Number of retry attempts
 * @returns {Promise<{success: boolean, wp_user_id?: number, message?: string} | null>} WordPress API response or null on error
 */
async function createWordPressUser(user, retries = 3) {
    if (!user || !user.id) {
        logConfig(`Cannot create WordPress user: Invalid user data`, 'error');
        return null;
    }

    let lastError;
    for (let attempt = 0; attempt < retries; attempt++) {
        try {
            const baseURL = process.env.WORDPRESS_API_URL || 'http://localhost/wp-json';
            const endpoint = `${baseURL}/asap/v1/auth/create-wp-user`;
            
            // Create timestamp and signature for security
            const timestamp = Math.floor(Date.now() / 1000).toString();
            const sharedSecret = process.env.BETTER_AUTH_SHARED_SECRET || process.env.BETTER_AUTH_SECRET || '';
            const signature = await createHmacSha256(timestamp, sharedSecret);
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Better-Auth-Timestamp': timestamp,
                    'X-Better-Auth-Signature': signature
                },
                body: JSON.stringify({
                    ba_user_id: user.id,
                    email: user.email,
                    username: user.username || user.email.split('@')[0],
                    name: user.name || user.username || user.email.split('@')[0]
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to create WordPress user');
            }

            // Store WordPress user ID in Better Auth user metadata
            if (data.wp_user_id) {
                await updateUserMetadata(user.id, { wp_user_id: data.wp_user_id });
            }

            return data;

        } catch (error) {
            lastError = error;
            logConfig(`Attempt ${attempt + 1} failed creating WordPress user: ${error instanceof Error ? error.message : String(error)}`, 'warn');
            
            // Wait before retrying (exponential backoff)
            if (attempt < retries - 1) {
                await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
            }
        }
    }

    logConfig(`Failed to create WordPress user after ${retries} attempts: ${lastError instanceof Error ? lastError.message : String(lastError)}`, 'error');
    return null;
}

/**
 * Create a WordPress session for the Better Auth session
 * 
 * @param {Session} session Better Auth session object
 * @param {number} [retries=3] Number of retry attempts
 * @returns {Promise<boolean>} Success status
 */
async function createWordPressSession(session, retries = 3) {
    if (!session?.user?.metadata?.wp_user_id) {
        logConfig('Cannot create WordPress session: No WordPress user ID in metadata', 'error');
        return false;
    }

    let lastError;
    for (let attempt = 0; attempt < retries; attempt++) {
        try {
            const baseURL = process.env.WORDPRESS_API_URL || 'http://localhost/wp-json';
            const endpoint = `${baseURL}/asap/v1/auth/create-wp-session`;
            
            // Create timestamp and signature for security
            const timestamp = Math.floor(Date.now() / 1000).toString();
            const sharedSecret = process.env.BETTER_AUTH_SHARED_SECRET || process.env.BETTER_AUTH_SECRET || '';
            const signature = await createHmacSha256(timestamp, sharedSecret);
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Better-Auth-Timestamp': timestamp,
                    'X-Better-Auth-Signature': signature
                },
                body: JSON.stringify({
                    wp_user_id: session.user.metadata.wp_user_id
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to create WordPress session');
            }

            return true;

        } catch (error) {
            lastError = error;
            logConfig(`Attempt ${attempt + 1} failed creating WordPress session: ${error instanceof Error ? error.message : String(error)}`, 'warn');
            
            // Wait before retrying (exponential backoff)
            if (attempt < retries - 1) {
                await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
            }
        }
    }

    logConfig(`Failed to create WordPress session after ${retries} attempts: ${lastError instanceof Error ? lastError.message : String(lastError)}`, 'error');
    return false;
}

/**
 * Update user metadata in Better Auth
 * 
 * @param {string} userId User ID
 * @param {UserMetadata} metadata Metadata to update
 * @returns {Promise<boolean>} Success status
 */
async function updateUserMetadata(userId, metadata) {
    try {
        const db = new Kysely({ dialect });
        
        await db
            .updateTable('ba_users')
            .set({ metadata: JSON.stringify(metadata) })
            .where('id', '=', userId)
            .execute();
            
        return true;
    } catch (error) {
        logConfig(`Failed to update user metadata: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return false;
    }
}

/**
 * Create an HMAC SHA-256 signature using node:crypto
 * 
 * @param {string} message Message to sign
 * @param {string} secret Secret key
 * @returns {Promise<string>} HMAC signature as hex string
 */
async function createHmacSha256(message, secret) {
    const crypto = await import('node:crypto');
    const hmac = crypto.createHmac('sha256', secret);
    hmac.update(message);
    return hmac.digest('hex');
}

// --- Adapter Functions Definition ---

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
        const sql = 'SELECT * FROM ba_sessions WHERE session_token = ? AND expires_at > NOW() LIMIT 1';
        const params = [sessionToken];
        /** @type {[import('mysql2/promise').RowDataPacket[], import('mysql2/promise').FieldPacket[]]} */
        const [rows, fields] = await connection.execute(sql, params);

        if (Array.isArray(rows) && rows.length > 0) {
            const sessionData = rows[0];
            if (sessionData && typeof sessionData === 'object' && 'user_id' in sessionData && 'session_token' in sessionData && 'expires_at' in sessionData && 'created_at' in sessionData) {
                logConfig(`Adapter: getSessionByToken found valid session for token.`);
                const user = await getUserByIdFn(String(sessionData.user_id)); // <-- Call renamed function

                if (user) {
                    logConfig(`Adapter: getSessionByToken found user ${user.id} for session.`);
                    return {
                        id: String(sessionData.id),
                        userId: String(sessionData.user_id),
                        token: String(sessionData.session_token),
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
 * @param {string} userId - Better Auth user ID
 * @param {string} sessionToken - Session token
 * @param {Date} expiresAt - Expiry date
 * @returns {Promise<Session|null>} Created session object or null on error
 */
async function createSessionFn(userId, sessionToken, expiresAt) {
    logConfig(`Adapter: createSession called for user ${userId}`);
    let connection;
    try {
        connection = await pool.getConnection();
        const createdAt = new Date();
        const sql = 'INSERT INTO ba_sessions (user_id, session_token, expires_at, created_at) VALUES (?, ?, ?, ?)';
        const params = [userId, sessionToken, expiresAt, createdAt];
        /** @type {[import('mysql2/promise').ResultSetHeader, import('mysql2/promise').FieldPacket[]]} */
        const [result, fields] = await connection.execute(sql, params);

        if (result && typeof result === 'object' && 'affectedRows' in result && result.affectedRows > 0 && 'insertId' in result) {
            logConfig(`Adapter: createSession successfully inserted session for user ${userId}`);
            const user = await getUserByIdFn(userId); // <-- Call renamed function
            if (user) {
                return {
                    id: String(result.insertId) || sessionToken,
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
        const sql = 'DELETE FROM ba_sessions WHERE session_token = ?';
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

// --- Hook Functions Definition ---

/** 
 * Hook executed after user creation 
 * @param {User} user 
 */
async function onUserCreationHook(user) {
    logConfig(`Hook: onUserCreation triggered for user ${user.id}. Attempting to create WordPress user.`);
    const wpResult = await createWordPressUser(user);
    if (!wpResult || !wpResult.success) {
        logConfig(`Hook: Failed to create WordPress user for Better Auth user: ${user.id}`, 'error');
    } else {
        if (wpResult.wp_user_id) {
            logConfig(`Hook: Successfully created WordPress user ID: ${wpResult.wp_user_id} for Better Auth user: ${user.id}`);
        } else {
            logConfig(`Hook: WordPress user creation endpoint succeeded but did not return wp_user_id for BA user ${user.id}`, 'warn');
        }
    }
}

/** 
 * Hook executed after session creation 
 * @param {Session} session 
 */
async function onSessionCreationHook(session) {
    logConfig(`Hook: onSessionCreation triggered for user ${session.userId}. Attempting to create WordPress session.`);
    await createWordPressSession(session);
}


// Better Auth configuration
export const auth = betterAuth({
    secret: authSecret,
    sessionCookieName: 'better_auth_session',
    sessionExpiresIn: 30 * 24 * 60 * 60 * 1000,
    adapter: {
        dialect: dialect,
        getUserByEmail: getUserByEmailFn,
        getUserById: getUserByIdFn,
        getSessionByToken: getSessionByTokenFn,
        createSession: createSessionFn,
        deleteSession: deleteSessionFn,
    },
    after: {
        /** @param {User} user */
        onUserCreation: async (user) => {
            logConfig(`User created in Better Auth: ${user.id}. Attempting to create WordPress user.`);
            const wpResult = await createWordPressUser(user);
            if (!wpResult || !wpResult.success) {
                logConfig(`Failed to create WordPress user for Better Auth user: ${user.id}`, 'error');
            } else {
                if (wpResult.wp_user_id) {
                    logConfig(`Successfully created WordPress user ID: ${wpResult.wp_user_id} for Better Auth user: ${user.id}`);
                } else {
                    logConfig(`WordPress user creation endpoint succeeded but did not return wp_user_id for BA user ${user.id}`, 'warn');
                }
            }
        },
        /** @param {Session} session */
        onSessionCreation: async (session) => {
            logConfig(`Session created for user: ${session.userId}. Attempting to create WordPress session.`);
            await createWordPressSession(session);
        },
    }
});

export { pool };

// Keep the default export for auth
export default auth;

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

export { getNonce };