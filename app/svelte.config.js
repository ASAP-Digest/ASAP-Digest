import adapter from '@sveltejs/adapter-node';
import { preprocessMeltUI } from '@melt-ui/pp';
import sequence from 'svelte-sequential-preprocessor';
import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

/** @type {import('@sveltejs/kit').Config} */
const config = {
    preprocess: sequence([
        vitePreprocess(),
        preprocessMeltUI()
    ]),

    kit: {
        // Use Node adapter with optimized settings
        adapter: adapter({
            out: 'build',
            precompress: true,
            envPrefix: 'APP_',
            polyfill: true
        }),

        // CSP settings for security
        csp: {
            mode: 'auto',
            directives: {
                'default-src': ['self'],
                'script-src': ['self', 'unsafe-inline'],
                'style-src': ['self', 'unsafe-inline'],
                'img-src': ['self', 'data:', 'https:'],
                'connect-src': ['self', 'https:'],
                'frame-src': ['self', 'https://asapdigest.local']
            }
        },

        // Alias configuration
        alias: {
            $components: 'src/lib/components',
            $stores: 'src/lib/stores',
            $utils: 'src/lib/utils'
        }
    }
};

export default config; 