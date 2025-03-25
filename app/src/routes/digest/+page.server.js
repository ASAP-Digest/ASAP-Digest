import { error } from '@sveltejs/kit';

/** @type {import('./$types').PageData} */
export const ssr = true;

/**
 * Server load function for the digest page
 * @typedef {Object} Digest
 * @property {string} id - The digest ID
 * @property {string} title - The digest title
 * @property {string} content - The digest content
 * 
 * @typedef {Object} LoadError
 * @property {number} status - HTTP status code
 * @property {string} message - Error message
 * 
 * @param {Object} params - Load function parameters
 * @param {Function} params.fetch - Fetch function for making HTTP requests
 * @param {Object} params.params - URL parameters
 * @param {Function} params.setHeaders - Function to set response headers
 * @returns {Promise<{digests: Digest[], meta: {title: string, description: string}}>}
 * @throws {Error} When digest content fails to load
 */
export const load = async ({ fetch, params, setHeaders }) => {
    try {
        // Set cache headers for SSR content
        setHeaders({
            'Cache-Control': 'public, max-age=3600'
        });

        // Fetch digest content
        const response = await fetch('/api/digests');

        if (!response.ok) {
            throw error(response.status, `Failed to load digests: ${await response.text()}`);
        }

        // Only try to parse JSON if response is ok
        const digests = await response.json();

        return {
            digests,
            meta: {
                title: 'ASAP Digest - Latest Updates',
                description: 'Stay up to date with the latest digests and summaries'
            }
        };
    } catch (err) {
        console.error('Error loading digest page:', err);
        // Type guard for error with status and message
        if (err && typeof err === 'object' &&
            'status' in err && typeof err.status === 'number' &&
            'message' in err && typeof err.message === 'string') {
            throw error(err.status, err.message);
        }
        throw error(500, 'Failed to load digest content');
    }
}; 