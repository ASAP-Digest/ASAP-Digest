import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { visualizer } from 'rollup-plugin-visualizer';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

export default defineConfig(({ mode }) => {
	const isAnalyze = mode === 'analyze';

	return {
		plugins: [
			tailwindcss(),
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
			hmr: {
				clientPort: 5173,
				overlay: false,
				timeout: 120000,
				protocol: 'ws'
			}
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
						'ui-lib': ['bits-ui'],
						'chart': ['svelte-chart'],
						'stripe': ['@stripe/stripe-js']
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
				'lucide-svelte/icons': resolve(__dirname, 'src/lib/utils/lucide-icons.js')
			}
		},
		optimizeDeps: {
			exclude: ['@sveltejs/kit'],
			include: [
				'tailwindcss',
				'bits-ui',
				'clsx',
				'tailwind-merge',
				'tailwind-variants'
			],
			esbuildOptions: {
				target: 'esnext'
			}
		},
		ssr: {
			noExternal: ['esm-env']
		}
	};
});
