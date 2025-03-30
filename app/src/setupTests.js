/**
 * Test Setup Configuration
 * @created 03.30.25 | 05:21 AM PDT
 */

import { loadEnv } from 'vite';
import { vi } from 'vitest';

// Load environment variables
const env = loadEnv('test', process.cwd(), '');

// Set up environment variables
Object.keys(env).forEach(key => {
    process.env[key] = env[key];
});

// Mock fetch
global.fetch = vi.fn();

// Mock console methods for cleaner output
global.console.log = vi.fn();
global.console.error = vi.fn();
global.console.warn = vi.fn();
global.console.info = vi.fn();

// Reset mocks before each test
beforeEach(() => {
    vi.clearAllMocks();
    fetch.mockClear();
}); 