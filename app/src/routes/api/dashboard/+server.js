/**
 * @file Dashboard API Endpoint
 * @description Provides dashboard data including user statistics and recent activity
 * @created 2025-05-03
 */

import { json } from '@sveltejs/kit';
import { log } from '$lib/utils/log';

/**
 * Handles GET requests to fetch dashboard data
 * @param {import('@sveltejs/kit').RequestEvent} event
 * @returns {Promise<Response>} JSON response with dashboard data
 */
export async function GET(event) {
    try {
        // Extract the user ID from the Authorization header
        const authHeader = event.request.headers.get('Authorization') || '';
        const userId = authHeader.replace('Bearer ', '');
        
        if (!userId) {
            log('[API /dashboard] Missing user ID in Authorization header', 'error');
            return json({ error: 'Unauthorized' }, { status: 401 });
        }
        
        // In a real implementation, you would fetch the user's dashboard data
        // from a database or other data source based on the userId.
        // For now, return sample data
        
        log(`[API /dashboard] Returning dashboard data for user: ${userId}`, 'info');
        
        return json({
            digests: {
                total: 25,
                thisWeek: 5,
                lastWeek: 7
            },
            recentActivity: [
                {
                    id: '1',
                    type: 'digest',
                    title: 'Technology Digest',
                    date: new Date().toISOString(),
                    status: 'completed'
                },
                {
                    id: '2',
                    type: 'digest',
                    title: 'Finance Digest',
                    date: new Date(Date.now() - 86400000).toISOString(), // Yesterday
                    status: 'completed'
                },
                {
                    id: '3',
                    type: 'digest',
                    title: 'Science Digest',
                    date: new Date(Date.now() - 86400000 * 2).toISOString(), // 2 days ago
                    status: 'completed'
                }
            ],
            usage: {
                digests: {
                    used: 25,
                    limit: 100
                },
                searches: {
                    used: 15,
                    limit: 50
                }
            },
            preferences: {
                topics: ['technology', 'finance', 'science'],
                notificationsEnabled: true,
                emailFrequency: 'daily'
            }
        });
        
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        log(`[API /dashboard] Error processing request: ${errorMessage}`, 'error');
        return json({ error: 'Internal Server Error' }, { status: 500 });
    }
} 