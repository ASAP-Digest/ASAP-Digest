import { betterAuth } from "better-auth";
import mysql from 'mysql2/promise';
import { Kysely, MysqlDialect } from 'kysely';

/**
 * Database configuration object for MySQL.
 * Defines connection parameters for the MySQL database.
 *
 * @typedef {object} DBConfig
 * @property {string} host - MySQL server hostname or IP address (e.g., 'localhost' or '127.0.0.1').
 * @property {number} port - MySQL server port number (e.g., 3306 or 10018).
 * @property {string} user - MySQL username for database access (e.g., 'root').
 * @property {string} password - MySQL password for the specified user (e.g., 'root').
 * @property {string} database - Name of the MySQL database to connect to (e.g., 'local').
 * @property {string} charset - Character set for the connection (e.g., 'utf8mb4').
 * @property {number} connectTimeout - Connection timeout in milliseconds (e.g., 120000 for 2 minutes).
 * @property {boolean} waitForConnections - Whether to wait for connections if the pool is full.
 * @property {number} connectionLimit - Maximum number of connections in the pool.
 * @property {number} queueLimit - Maximum number of connection requests to queue.
 * @property {boolean} debug - Enable debug logging for development (e.g., true in development, false in production).
 * @property {boolean} trace - Enable connection tracing for detailed debugging (e.g., true in development, false in production).
 */
const DB_CONFIG = {
    host: '127.0.0.1',
    port: 10018,      // Port from AdminerEvo database variables
    user: 'root',
    password: 'root',
    database: 'local',
    charset: 'utf8mb4', // Recommended charset for modern applications

    connectTimeout: 120000, // 120 seconds (2 minutes) - Increased timeout

    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,

    // Kysely dialect doesn't directly use these mysql2 pool options here
    // debug: process.env.NODE_ENV !== 'production', // Debug mode based on environment
    // trace: process.env.NODE_ENV !== 'production'  // Trace mode based on environment
};

// Create a Kysely dialect using the mysql2 pool
const dialect = new MysqlDialect({
    pool: mysql.createPool(DB_CONFIG)
});

/**
 * Initializes and exports the Better Auth configuration object.
 * Configures Better Auth with MySQL database adapter and connection pool.
 */
export const auth = betterAuth({
    // Use the Kysely dialect
    database: {
        dialect: dialect,
        type: "mysql" // Explicitly tell better-auth the database type
    },
    secret: process.env.BETTER_AUTH_SECRET,
    baseURL: process.env.BETTER_AUTH_URL,
    // Ensure email & password auth is enabled if you plan to use it
    emailAndPassword: {
        enabled: true,
        // Add other email/password settings as needed (e.g., requireEmailVerification)
    },
    // ... other Better Auth configurations from the documentation if needed ...
});