import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

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
		dedupe: ['svelte', '@sveltejs/kit'],
		alias: {
			// Alias lucide-svelte to our compatibility module
			'lucide-svelte': resolve(__dirname, 'src/lib/utils/lucide-icons.ts'),
			'lucide-svelte/icons': resolve(__dirname, 'src/lib/utils/lucide-icons.ts')
		}
	},
	optimizeDeps: {
		exclude: ['@sveltejs/kit'],
		include: ['tailwindcss']
	},
	ssr: {
		noExternal: ['esm-env']
	}
});
