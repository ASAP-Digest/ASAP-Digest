import { auth } from '../src/lib/server/auth.js';
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

async function testAuthConnection() {
  try {
    console.log('Testing Better Auth database connection...');
    
    // Try to access the users table
    const result = await auth.users.list({
      limit: 1
    });
    
    console.log('✅ Connection successful!');
    console.log(`Found ${result.total} users in the database`);
    
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
    
    console.error('Full error:', error);
  }
}

testAuthConnection(); 