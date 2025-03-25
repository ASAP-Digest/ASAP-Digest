import { json } from '@sveltejs/kit';

/**
 * GET handler for digests API
 * @param {Request} request - The request object
 * @returns {Response} JSON response with digests data
 */
export async function GET(request) {
    try {
        // Mock data - replace with actual data fetching logic
        const digests = [
            {
                id: 'finance-mar20',
                title: 'Finance Weekly Roundup',
                date: '2024-03-20',
                articleCount: 12,
                category: 'Finance',
                description: 'Key market trends and financial news from the past week.'
            },
            {
                id: 'tech-mar19',
                title: 'Tech Innovation Digest',
                date: '2024-03-19',
                articleCount: 8,
                category: 'Technology',
                description: 'Latest breakthroughs in AI, robotics, and consumer tech.'
            },
            {
                id: 'health-mar18',
                title: 'Healthcare Updates',
                date: '2024-03-18',
                articleCount: 10,
                category: 'Healthcare',
                description: 'Medical research highlights and healthcare industry news.'
            },
            {
                id: 'politics-mar18',
                title: 'Political Analysis',
                date: '2024-03-18',
                articleCount: 14,
                category: 'Politics',
                description: 'In-depth coverage of global political developments.'
            },
            {
                id: 'crypto-mar17',
                title: 'Cryptocurrency Special Report',
                date: '2024-03-17',
                articleCount: 7,
                category: 'Finance',
                description: 'Analysis of recent cryptocurrency market movements.'
            },
            {
                id: 'climate-mar16',
                title: 'Climate Change Update',
                date: '2024-03-16',
                articleCount: 9,
                category: 'Environment',
                description: 'Latest research and policy changes regarding climate change.'
            }
        ];

        return json(digests);
    } catch (error) {
        console.error('Error in digests API:', error);
        return new Response(JSON.stringify({ error: 'Failed to fetch digests' }), {
            status: 500,
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
} 