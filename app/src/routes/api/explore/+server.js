import { json } from '@sveltejs/kit';

/**
 * GET handler for explore API
 * @param {Request} request - The request object
 * @returns {Promise<Response>} JSON response with explore content data
 */
export async function GET(request) {
    try {
        const url = new URL(request.url);
        const category = url.searchParams.get('category') || 'all';
        const page = parseInt(url.searchParams.get('page') || '1');
        const perPage = 6;

        // Mock data - replace with actual data fetching logic
        const allContent = [
            {
                id: 'article-1',
                type: 'Article',
                title: 'Understanding Market Volatility',
                summary: 'A deep dive into what causes market volatility and how investors can navigate turbulent times.',
                date: '2024-03-24',
                source: 'Financial Times',
                category: 'finance'
            },
            {
                id: 'podcast-1',
                type: 'Podcast',
                title: 'The Future of AI in Healthcare',
                summary: 'Medical experts discuss how artificial intelligence is transforming diagnostic procedures and patient care.',
                date: '2024-03-23',
                source: 'Health Insights Podcast',
                duration: '45 min',
                category: 'technology'
            },
            {
                id: 'keyterm-1',
                type: 'Key Term',
                term: 'Quantitative Easing',
                definition: 'A monetary policy where a central bank purchases longer-term securities to increase money supply and encourage lending and investment.',
                category: 'finance'
            },
            {
                id: 'financial-1',
                type: 'Financial Bite',
                title: 'Tech Stocks Rally',
                summary: 'Major technology companies saw significant gains as investors responded to positive earnings reports.',
                change: '+2.8%',
                category: 'finance'
            },
            {
                id: 'xpost-1',
                type: 'X Post',
                author: '@tech_analyst',
                content: 'New research shows quantum computing making significant progress in error correction. This could accelerate practical applications by years.',
                engagement: '3.2K likes',
                category: 'technology'
            },
            {
                id: 'reddit-1',
                type: 'Reddit Buzz',
                subreddit: 'r/DataScience',
                title: 'Breakthrough in ML reduces training time by 60%',
                upvotes: '2.4k',
                comments: '412',
                category: 'technology'
            },
            {
                id: 'article-2',
                type: 'Article',
                title: 'Climate Change Policy Update',
                summary: 'Recent international agreements set new targets for carbon reduction across major economies.',
                date: '2024-03-22',
                source: 'Environmental Monitor',
                category: 'environment'
            },
            {
                id: 'podcast-2',
                type: 'Podcast',
                title: 'Political Landscape Analysis',
                summary: 'Expert analysis of shifting political dynamics and their implications for global relations.',
                date: '2024-03-21',
                source: 'Global Affairs Podcast',
                duration: '38 min',
                category: 'politics'
            },
            {
                id: 'keyterm-2',
                type: 'Key Term',
                term: 'Zero-Day Vulnerability',
                definition: 'A software security flaw unknown to those who should be interested in its mitigation until it is actively exploited.',
                category: 'technology'
            },
            {
                id: 'financial-2',
                type: 'Financial Bite',
                title: 'Energy Sector Performance',
                summary: 'Renewable energy companies outperformed traditional energy stocks amid new policy announcements.',
                change: '+4.2%',
                category: 'finance'
            },
            {
                id: 'xpost-2',
                type: 'X Post',
                author: '@health_insights',
                content: 'New study suggests intermittent fasting may have significant benefits for metabolic health beyond weight loss.',
                engagement: '1.8K likes',
                category: 'health'
            },
            {
                id: 'reddit-2',
                type: 'Reddit Buzz',
                subreddit: 'r/Futurology',
                title: 'Fusion energy breakthrough achieves record efficiency',
                upvotes: '5.6k',
                comments: '876',
                category: 'technology'
            }
        ];

        // Filter by category if not 'all'
        const filteredContent = category === 'all'
            ? allContent
            : allContent.filter(item => item.category === category);

        // Paginate results
        const startIndex = (page - 1) * perPage;
        const endIndex = startIndex + perPage;
        const paginatedContent = filteredContent.slice(startIndex, endIndex);

        return json({
            items: paginatedContent,
            total: filteredContent.length,
            perPage: perPage,
            category: category
        });
    } catch (error) {
        console.error('Error in explore API:', error);
        return new Response(JSON.stringify({ error: 'Failed to fetch explore content' }), {
            status: 500,
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
} 