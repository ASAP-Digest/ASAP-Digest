import { betterAuth } from 'better-auth';
import { MysqlDialect } from 'kysely';
import mysql from 'mysql2/promise';

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
 * @typedef {Object} BetterAuthUserMetadata
 * @property {number} [wp_user_id] - WordPress user ID
 */

/**
 * @typedef {Object} BetterAuthUser
 * @property {string} id - User ID
 * @property {string} email - User email
 * @property {string} [username] - Optional username
 * @property {string} [name] - Optional display name
 * @property {BetterAuthUserMetadata} metadata - User metadata
 */

/**
 * @typedef {Object} BetterAuthSession
 * @property {string} id - Session ID
 * @property {string} userId - User ID
 * @property {BetterAuthUser} user - Session user
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
    host: requiredEnvVars.DB_HOST,
    port: requiredEnvVars.DB_PORT,
    user: requiredEnvVars.DB_USER,
    password: requiredEnvVars.DB_PASS,
    database: requiredEnvVars.DB_NAME,
    charset: 'utf8mb4',
    connectTimeout: 120000,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

// Create MySQL connection pool
const pool = mysql.createPool(DB_CONFIG);

// Create Kysely dialect
const dialect = new MysqlDialect({
    pool: pool
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
        const configKey = envVar.toLowerCase().replace('db_', '');
        logConfig(`${envVar} environment variable is not set, using default: ${DB_CONFIG[configKey]}`, 'warn');
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
 * @param {BetterAuthUser} user Better Auth user object
 * @returns {Promise<object|null>} WordPress API response or null on error
 */
async function createWordPressUser(user) {
    if (!user || !user.id) {
        logConfig(`Cannot create WordPress user: Invalid user data`, 'error');
        return null;
    }

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
                id: user.id,
                email: user.email,
                username: user.username || user.email.split('@')[0],
                name: user.name || user.username || user.email.split('@')[0]
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to create WordPress user');
        }
        
        const data = await response.json();
        logConfig(`WordPress user created/mapped successfully. WP User ID: ${data.wp_user_id}`, 'info');
        return data;
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        logConfig(`Error creating WordPress user: ${errorMessage}`, 'error');
        return null;
    }
}

/**
 * Create a WordPress session for the Better Auth user
 * This is called after a user signs in to Better Auth
 * 
 * @param {BetterAuthSession} session Better Auth session object with user property
 * @returns {Promise<object|null>} WordPress API response or null on error
 */
async function createWordPressSession(session) {
    if (!session || !session.user || !session.user.id) {
        logConfig(`Cannot create WordPress session: Invalid session data`, 'error');
        return null;
    }

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
                wp_user_id: session.user.wp_user_id
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to create WordPress session');
        }
        
        const data = await response.json();
        logConfig(`WordPress session created successfully`, 'info');
        return data;
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        logConfig(`Error creating WordPress session: ${errorMessage}`, 'error');
        return null;
    }
}

/**
 * Create an HMAC SHA-256 signature using the Web Crypto API
 * 
 * @param {string} timestamp Timestamp to sign
 * @param {string} secret Secret key
 * @returns {Promise<string>} HMAC signature as hex string
 */
async function createHmacSha256(timestamp, secret) {
    const encoder = new TextEncoder();
    const key = await crypto.subtle.importKey(
        'raw',
        encoder.encode(secret),
        { name: 'HMAC', hash: 'SHA-256' },
        false,
        ['sign']
    );
    
    const signature = await crypto.subtle.sign(
        'HMAC',
        key,
        encoder.encode(timestamp)
    );
    
    return Array.from(new Uint8Array(signature))
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
}

// Configure Better Auth
const auth = betterAuth({
    adapter: {
        config: {
            dialect: new MysqlDialect({
                pool: mysql.createPool(DB_CONFIG)
            }),
            tables: {
                users: 'ba_users',
                sessions: 'ba_sessions',
                accounts: 'ba_accounts',
                verifications: 'ba_verifications'
            }
        }
    },
    emailAndPassword: {
        enabled: true,
        autoSignIn: true
    },
    secret: process.env.BETTER_AUTH_SECRET,
    baseUrl: process.env.BETTER_AUTH_URL
});

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

export { auth, getNonce };