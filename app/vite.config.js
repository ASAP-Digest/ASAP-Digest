import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { visualizer } from 'rollup-plugin-visualizer';

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
			host: env.HOST || 'localhost',
			port: parseInt(env.PORT || '5173', 10),
			hmr: {
				clientPort: process.env.HMR_HOST ? 5173 : null,
				overlay: true,
				timeout: 60000,
				protocol: 'ws',
				host: env.HOST || 'localhost'
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
				'lucide-svelte': resolve(__dirname, 'src/lib/utils/lucide-icons.js'),
				'lucide-svelte/icons': resolve(__dirname, 'src/lib/utils/lucide-icons.js'),
				'svelte-chart': resolve(__dirname, 'src/lib/utils/svelte-chart-compat.js')
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
			noExternal: ['esm-env']
		},
		// Expose all environment variables to the client
		define: {
			'process.env': env
		}
	};
});
