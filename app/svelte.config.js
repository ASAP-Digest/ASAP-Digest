import adapter from '@sveltejs/adapter-node';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	// Enable runes for Svelte 5 features
	compilerOptions: {
		runes: true
	},
	kit: {
		// Using adapter-node for Node.js server deployment
		adapter: adapter(),

		// Performance optimizations
		csrf: {
			checkOrigin: true
		},

		// Completely disable the service worker by not defining it
		// serviceWorker: false, 

		// Optimize page loading
		prerender: {
			handleHttpError: ({ path, referrer, message }) => {
				// Log prerendering errors but don't fail the build
				console.warn(`[Prerender] Error for ${path} (referred from ${referrer}): ${message}`);
				return;
			},
			handleMissingId: ({ path, id, referrer }) => {
				// Log missing IDs but don't fail the build
				console.warn(`[Prerender] Missing ID: ${id} for ${path} (referred from ${referrer})`);
				return;
			}
		},

		// Add cache headers
		version: {
			name: Date.now().toString(),
			pollInterval: 0
		},

		// Optimize asset paths
		paths: {
			assets: '',
			base: ''
		},

		// Add alias for frequently used paths
		alias: {
			$components: 'src/components',
			$lib: 'src/lib',
			$stores: 'src/stores',
			$utils: 'src/utils'
		}
	}
};

export default config;
