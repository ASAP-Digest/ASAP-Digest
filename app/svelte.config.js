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
			},
			// Prerender more paths for better performance
			entries: ['*']
			// The filter option is not supported in SvelteKit 2
			// filter: (path) => !path.includes('[') && !path.includes(']')
		},

		// Optimize server-side rendering
		csp: {
			mode: 'auto',
			directives: {
				'script-src': ['self']
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
		},

		// Improve page loading experience
		inlineStyleThreshold: 8192 // Inline CSS under 8KB
	}
};

export default config;
