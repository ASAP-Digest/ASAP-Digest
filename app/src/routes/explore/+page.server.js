import { error } from '@sveltejs/kit';

/** @type {import('./$types').PageServerLoad} */
export const ssr = true;

/**
 * Server load function for the explore page
 * @param {Object} params - Load function parameters
 * @param {Function} params.fetch - Fetch function for making HTTP requests
 * @param {URL} params.url - URL object containing search parameters
 * @param {Function} params.setHeaders - Function to set response headers
 * @returns {Promise<{
 *   content: Object,
 *   meta: {title: string, description: string},
 *   pagination: {currentPage: number, totalPages: number}
 * }>}
 * @throws {Error} When explore content fails to load
 */
export const load = async ({ fetch, url, setHeaders }) => {
    try {
        // Get search params
        const searchParams = url.searchParams;
        const category = searchParams.get('category') || 'all';
        const page = parseInt(searchParams.get('page') || '1');

        // Set cache headers for SSR content
        setHeaders({
            'Cache-Control': 'public, max-age=1800'
        });

        // Fetch explore content
        const response = await fetch(`/api/explore?category=${category}&page=${page}`);
        const exploreData = await response.json();

        if (!response.ok) {
            throw error(response.status, 'Failed to load explore content');
        }

        return {
            content: exploreData,
            meta: {
                title: `Explore ASAP Digest - ${category.charAt(0).toUpperCase() + category.slice(1)}`,
                description: 'Explore our curated collection of digests and summaries'
            },
            pagination: {
                currentPage: page,
                totalPages: Math.ceil(exploreData.total / exploreData.perPage)
            }
        };
    } catch (err) {
        console.error('Error loading explore page:', err);
        throw error(500, 'Failed to load explore content');
    }
}; 