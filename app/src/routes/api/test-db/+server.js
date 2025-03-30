import { json } from '@sveltejs/kit';
import mysql from 'mysql2/promise';

const DB_CONFIG = {
    socketPath: '/Volumes/Macintosh HD/Users/vsmith/Library/Application Support/Local/run/AFTH2oxzp/mysql/mysqld.sock',
    user: 'root',
    password: 'root',
    database: 'local',
    charset: 'utf8mb4',
    connectTimeout: 120000,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
};

/** @type {import('./$types').RequestHandler} */
export async function GET() {
    /** @type {import('mysql2/promise').PoolConnection | undefined} */
    let connection;
    try {
        console.log('Attempting to connect to database with config:', {
            ...DB_CONFIG,
            password: '[REDACTED]'
        });

        // Create a connection pool
        const pool = mysql.createPool(DB_CONFIG);

        // Test the connection by getting a connection from the pool
        connection = await pool.getConnection();
        console.log('Successfully connected to database');

        // Get all tables in the database
        const [tables] = await connection.query('SHOW TABLES');
        console.log('Tables in database:', tables);

        return json({
            success: true,
            message: 'Database connection successful',
            /** @type {string[]} */
            tables: Array.isArray(tables) ? tables.map(row => String(Object.values(row)[0])) : []
        });
    } catch (error) {
        console.error('Database connection error:', error);
        return json({
            success: false,
            message: 'Database connection failed',
            error: error instanceof Error ? error.message : String(error)
        }, { status: 500 });
    } finally {
        if (connection) {
            connection.release();
        }
    }
} 