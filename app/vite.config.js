import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [sveltekit()],
	build: {
		target: 'esnext',
		minify: 'esbuild',
		cssMinify: true,
		rollupOptions: {
			output: {
				manualChunks: {
					'lucide-icons': ['lucide-svelte'],
					'svelte-core': ['svelte', 'svelte/internal', 'svelte/store'],
				}
			}
		}
	},
	server: {
		fs: {
			strict: false
		},
		hmr: {
			overlay: false
		}
	},
	optimizeDeps: {
		include: ['lucide-svelte'],
		exclude: []
	}
});
