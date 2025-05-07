import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { visualizer } from 'rollup-plugin-visualizer';
import fs from 'node:fs';
import mkcert from 'vite-plugin-mkcert';
import path from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

export default defineConfig(({ mode }) => {
	// Load env file based on mode
	const env = loadEnv(mode, process.cwd(), '');

	const isAnalyze = mode === 'analyze';

	return {
		plugins: [
			tailwindcss({
				config: './tailwind.config.js',
			}),
			sveltekit(),
			mkcert(),
			isAnalyze && visualizer({
				open: true,
				gzipSize: true,
				brotliSize: true,
				filename: 'analyze/stats.html',
			})
		].filter(Boolean),
		server: {
			fs: {
				strict: false,
				allow: ['..', '../..', '../../node_modules', '.', './node_modules']
			},
			https: true,
			host: env.HOST || 'localhost',
			port: parseInt(env.PORT || '5173', 10),
			proxy: {
				'^/wp-api/': {
					target: env.WP_API_URL?.replace('/wp-json', '') || 'https://asapdigest.local',
					changeOrigin: true,
					secure: false,
					rewrite: (path) => path.replace(/^\/wp-api/, '/wp-json'),
				}
			},
			hmr: {
				protocol: env.VITE_HTTPS === 'true' || true ? 'wss' : 'ws',
				port: parseInt(env.PORT || '5173', 10),
				host: env.HOST || 'localhost',
				overlay: true,
				timeout: 120000
			},
			strictPort: true
		},
		build: {
			target: 'esnext',
			minify: 'esbuild',
			cssCodeSplit: true,
			sourcemap: process.env.NODE_ENV !== 'production',
			rollupOptions: {
				output: {
					manualChunks: {
						'svelte': ['svelte'],
						'ui-lib': ['bits-ui']
					}
				}
			},
			reportCompressedSize: false,
			chunkSizeWarningLimit: 1000
		},
		resolve: {
			preserveSymlinks: true,
			dedupe: ['svelte', '@sveltejs/kit'],
			alias: {
				'lucide-svelte': resolve(__dirname, 'src/lib/utils/lucide-compat.js'),
				'lucide-svelte/icons': resolve(__dirname, 'src/lib/utils/lucide-compat.js'),
				'svelte-chart': resolve(__dirname, 'src/lib/utils/svelte-chart-compat.js'),
				'$src': path.resolve('./src'),
			}
		},
		optimizeDeps: {
			exclude: ['@sveltejs/kit'],
			include: [
				'tailwindcss',
				'clsx',
				'tailwind-merge',
				'tailwind-variants',
				'@stripe/stripe-js'
			],
			esbuildOptions: {
				target: 'esnext'
			}
		},
		ssr: {
			timeout: 120000,
			noExternal: ['lucide-svelte', '@floating-ui/dom', 'clsx', 'class-variance-authority', 'tailwind-merge']
		},
		// Expose all environment variables to the client
		define: {
			'process.env': env
		}
	};
});
