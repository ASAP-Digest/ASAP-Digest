import { betterAuth } from "better-auth";
import mysql from 'mysql2/promise';
import { Kysely, MysqlDialect } from 'kysely';

/**
 * Database configuration object for MySQL.
 * Reads connection parameters from environment variables.
 *
 * @typedef {object} DBConfig
 * @property {string} socketPath - MySQL Unix socket path
 * @property {string} user - MySQL username for database access.
 * @property {string} password - MySQL password for the specified user.
 * @property {string} database - Name of the MySQL database to connect to.
 * @property {string} [charset] - Character set for the connection.
 * @property {number} [connectTimeout] - Connection timeout in milliseconds.
 * @property {boolean} [waitForConnections] - Whether to wait for connections if the pool is full.
 * @property {number} [connectionLimit] - Maximum number of connections in the pool.
 * @property {number} [queueLimit] - Maximum number of connection requests to queue.
 */
const DB_CONFIG = {
    socketPath: '/Volumes/Macintosh HD/Users/vsmith/Library/Application Support/Local/run/AFTH2oxzp/mysql/mysqld.sock',
    user: 'root',
    password: 'root',
    database: 'local',
    charset: 'utf8mb4',

    // You might want to make these configurable via env vars too, or keep defaults
    connectTimeout: 120000,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
};

// Create a Kysely dialect using the mysql2 pool
const dialect = new MysqlDialect({
    pool: mysql.createPool(DB_CONFIG)
});

/**
 * Initializes and exports the Better Auth configuration object.
 * Configures Better Auth with MySQL database adapter using environment variables.
 */
const auth = betterAuth({
    // Use the Kysely dialect
    database: {
        dialect: dialect,
        type: "mysql" // Explicitly tell better-auth the database type
    },
    secret: process.env.BETTER_AUTH_SECRET, // Make sure this is set in your .env file
    baseURL: process.env.BETTER_AUTH_URL,   // Make sure this is set in your .env file
    // Ensure email & password auth is enabled if you plan to use it
    emailAndPassword: {
        enabled: true,
        // Add other email/password settings as needed (e.g., requireEmailVerification)
    },
    // ... other Better Auth configurations from the documentation if needed ...
});

// Add a check for essential Better Auth env vars
if (!process.env.BETTER_AUTH_SECRET) {
    console.error('[Auth Setup] CRITICAL: BETTER_AUTH_SECRET environment variable is not set!');
}
if (!process.env.BETTER_AUTH_URL) {
    console.error('[Auth Setup] CRITICAL: BETTER_AUTH_URL environment variable is not set!');
}

/**
 * Get a WordPress nonce for authenticated requests
 * @returns {Promise<string>} - WordPress nonce
 */
export async function getNonce() {
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

export { auth };
export default auth;