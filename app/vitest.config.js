import { defineConfig } from 'vitest/config';
import { sveltekit } from '@sveltejs/kit/vite';
import { loadEnv } from 'vite';

// Load environment variables
const env = loadEnv('test', process.cwd(), '');

export default defineConfig({
    plugins: [sveltekit()],
    test: {
        include: ['src/**/*.{test,spec}.{js,ts}'],
        environment: 'jsdom',
        globals: true,
        setupFiles: ['src/setupTests.js'],
        env: env
    },
    define: {
        'process.env': env
    }
}); 