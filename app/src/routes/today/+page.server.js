import { error } from '@sveltejs/kit';

/**
 * Server load function for the today page
 * @typedef {Object} Highlight
 * @property {string} type - Content type (Article, Podcast, etc.)
 * @property {string} [title] - Content title
 * @property {string} [summary] - Content summary
 * @property {string} [term] - Key term
 * @property {string} [definition] - Term definition
 * @property {string} [source] - Content source
 * 
 * @param {Object} params - Load function parameters
 * @param {Function} params.fetch - Fetch function for making HTTP requests
 * @param {Function} params.setHeaders - Function to set response headers
 * @returns {Promise<{
 *   highlights: Highlight[],
 *   meta: {title: string, description: string}
 * }>}
 * @throws {Error} When today's content fails to load
 */
export const load = async ({ fetch, setHeaders }) => {
    try {
        // Set cache headers for SSR content
        setHeaders({
            'Cache-Control': 'public, max-age=1800'
        });

        // Fetch today's digest content
        const response = await fetch('/api/today');

        if (!response.ok) {
            throw error(response.status, 'Failed to load today\'s content');
        }

        const todayData = await response.json();

        return {
            highlights: todayData.highlights,
            meta: {
                title: 'ASAP Digest - Today\'s Updates',
                description: 'Your daily curated summary of essential updates'
            }
        };
    } catch (err) {
        console.error('Error loading today page:', err);
        throw error(500, 'Failed to load today\'s content');
    }
}; 