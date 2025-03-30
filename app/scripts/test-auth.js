/**
 * Authentication Test Runner
 * @created 03.30.25 | 05:21 AM PDT
 */

import { loadEnv } from 'vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import dotenv from 'dotenv';

// Get current directory
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Load environment variables
dotenv.config({ path: resolve(__dirname, '../.env.local') });

// Import and run tests
import('../src/lib/server/auth.test.js').catch(error => {
    console.error('Failed to run tests:', error);
    process.exit(1);
}); 