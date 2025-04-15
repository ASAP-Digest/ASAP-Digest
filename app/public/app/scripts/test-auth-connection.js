// app/scripts/test-auth-connection.js
import { betterAuth } from 'better-auth';
import { MysqlDialect } from 'kysely';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import fs from 'fs';

// Initialize environment variables
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const envPath = join(__dirname, '..', '.env.local');

if (fs.existsSync(envPath)) {
  console.log(`Loading environment from ${envPath}`);
  dotenv.config({ path: envPath });
} else {
  console.log('No .env.local file found, using default environment');
  dotenv.config();
}

// Get database configuration from environment variables
const DB_CONFIG = {
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '10018', 10),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || 'root',
  database: process.env.DB_NAME || 'local'
};

async function testAuthConnection() {
  try {
    console.log('Testing Better Auth database connection with config:', DB_CONFIG);
    
    // Create MySQL connection pool
    const pool = mysql.createPool({
      ...DB_CONFIG,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
      enableKeepAlive: true,
      keepAliveInitialDelay: 0
    });
    
    // Create Better Auth instance
    const auth = betterAuth({
      secret: process.env.BETTER_AUTH_SECRET || 'test-secret',
      baseURL: process.env.BETTER_AUTH_URL || 'https://localhost:5173',
      database: {
        type: 'mysql',
        dialect: new MysqlDialect({ pool })
      },
      emailAndPassword: {
        enabled: true,
        autoSignIn: true
      }
    });
    
    console.log('Better Auth instance created successfully');
    
    // Try to access the users table
    console.log('Attempting to list users...');
    const result = await auth.users.list({
      limit: 1
    });
    
    console.log('✅ Connection successful!');
    console.log(`Found ${result.total} users in the database`);
    
    // Close the pool
    await pool.end();
    
  } catch (error) {
    console.error('❌ Connection failed:', error);
    
    // More detailed error information
    if (error.code === 'ECONNREFUSED') {
      console.error('Database connection refused. Check that the database server is running.');
    } else if (error.code === 'ER_NO_SUCH_TABLE') {
      console.error('Table not found. Check that Better Auth tables have been created.');
    } else if (error.code === 'ER_ACCESS_DENIED_ERROR') {
      console.error('Access denied. Check database credentials.');
    }
    
    // Log full error details for debugging
    console.error('Full error details:', {
      message: error.message,
      stack: error.stack,
      code: error.code,
      errno: error.errno,
      sql: error.sql,
      sqlState: error.sqlState
    });
    
    process.exit(1);
  }
}

testAuthConnection(); 