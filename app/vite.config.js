import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
	plugins: [
		tailwindcss(),
		sveltekit()
	],
	server: {
		fs: {
			strict: false,
			allow: ['..', '../..', '../../node_modules', '.', './node_modules']
		},
		hmr: {
			clientPort: 5173,
			overlay: false
		}
	},
	build: {
		target: 'esnext',
		rollupOptions: {
			output: {
				manualChunks: {
					'svelte': ['svelte']
				}
			}
		}
	},
	resolve: {
		preserveSymlinks: true,
		dedupe: ['svelte', '@sveltejs/kit']
	},
	optimizeDeps: {
		exclude: ['@sveltejs/kit'],
		include: ['tailwindcss']
	},
	ssr: {
		noExternal: ['esm-env']
	}
});
