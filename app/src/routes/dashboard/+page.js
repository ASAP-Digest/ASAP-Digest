import { browser } from '$app/environment';
import { redirect } from '@sveltejs/kit';

/** @type {import('./$types').PageData} */
export const ssr = false;
export const csr = true;

/**
 * Load function for the dashboard page
 * @param {Object} params - Load function parameters
 * @param {Function} params.fetch - Fetch function for making HTTP requests
 * @param {Function} params.parent - Function to access parent data
 * @returns {Promise<{streamed: {dashboardData: Promise<Record<string, any>|null>}}>}
 */
export const load = async ({ fetch, parent }) => {
    // Get parent data which includes auth state
    const { session } = await parent();

    // Redirect if not authenticated
    if (!session?.user) {
        throw redirect(307, '/login');
    }

    // Only fetch dashboard data in the browser
    if (!browser) {
        return {
            streamed: {
                dashboardData: Promise.resolve(null)
            }
        };
    }

    return {
        streamed: {
            dashboardData: fetchDashboardData(fetch, session)
        }
    };
};

/**
 * Fetch dashboard data from API
 * @param {Function} fetch - Fetch function
 * @param {Object} session - Session data
 * @param {string} session.token - Session token
 * @returns {Promise<Record<string, any>|null>} Dashboard data
 */
async function fetchDashboardData(fetch, session) {
    try {
        const response = await fetch('/api/dashboard', {
            headers: {
                'Authorization': `Bearer ${session.token}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch dashboard data');
        }

        return response.json();
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
        return null;
    }
} 