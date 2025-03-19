/**
 * WordPress API service for fetching data from the WordPress REST API
 * @module api/wordpress
 */

/**
 * Environment type from Vite environment variables
 * @type {string}
 */
const APP_ENV = import.meta.env.VITE_APP_ENV || 'development';

/**
 * Base site URL for WordPress based on environment
 * @type {string}
 */
const SITE_URL = APP_ENV === 'production' ? 'https://asapdigest.com' : 'https://asapdigest.local';

/**
 * The base URL for WordPress REST API and GraphQL requests
 * @type {string}
 */
const WP_API_URL = import.meta.env.VITE_WORDPRESS_API_URL || `${SITE_URL}/wp-json/wp/v2`;
const ASAP_API_URL = import.meta.env.VITE_ASAP_API_URL || `${SITE_URL}/wp-json/asap/v1`;
const WP_GRAPHQL_URL = import.meta.env.VITE_WORDPRESS_GRAPHQL_URL || `${SITE_URL}/graphql`;

// Log API configuration for debugging
console.debug('WordPress API Configuration:', {
    APP_ENV,
    SITE_URL,
    WP_API_URL,
    ASAP_API_URL,
    WP_GRAPHQL_URL
});

export { SITE_URL };


/**
 * @typedef {Object} ArticleProps
 * @property {string} id - Unique identifier for the article
 * @property {string} title - Title of the article
 * @property {string} excerpt - Short excerpt or summary
 * @property {string} source - Source of the article
 * @property {string} sourceUrl - URL to the source
 * @property {string} [content] - Full article content (only present for single article)
 * @property {string} [imageUrl] - Optional featured image URL
 * @property {string} [date] - Publication date
 * @property {string[]} [tags] - Array of tags
 */

/**
 * @typedef {Object} WPTerm
 * @property {string} name - Term name
 */

/**
 * @typedef {Object} GraphQLError
 * @property {string} message - Error message
 */

/**
 * @typedef {Object} GraphQLNode
 * @property {string} id - Node ID
 * @property {string} title - Node title
 * @property {string} date - Publication date
 * @property {string} excerpt - Post excerpt
 * @property {Object} [featuredImage] - Featured image data
 * @property {Object} [featuredImage.node] - Featured image node
 * @property {string} [featuredImage.node.sourceUrl] - Featured image URL
 * @property {Object} [articleFields] - Custom article fields
 * @property {string} [articleFields.source] - Article source
 * @property {string} [articleFields.sourceUrl] - Article source URL
 * @property {Object} [tags] - Tags data
 * @property {Array<{name: string}>} [tags.nodes] - Tags nodes
 */

/**
 * @typedef {Object} GraphQLEdge
 * @property {GraphQLNode} node - The node object
 */

/**
 * Fetch articles from WordPress
 * @param {Object} options - Query options
 * @param {number} [options.perPage=10] - Number of articles to fetch per page
 * @param {number} [options.page=1] - Page number
 * @param {string[]} [options.categories] - Category IDs to filter by
 * @param {string[]} [options.tags] - Tag IDs to filter by
 * @returns {Promise<ArticleProps[]>} - Array of article objects
 */
export async function fetchArticles(options = {}) {
    const { perPage = 10, page = 1, categories = [], tags = [] } = options;

    let url = `${WP_API_URL}/article?per_page=${perPage}&page=${page}&_embed`;

    if (categories.length) {
        url += `&categories=${categories.join(',')}`;
    }

    if (tags.length) {
        url += `&tags=${tags.join(',')}`;
    }

    try {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Error fetching articles: ${response.statusText}`);
        }

        /** @type {any[]} */
        const articles = await response.json();

        // Transform the response to match our ArticleProps format
        return articles.map((article) => ({
            id: article.id.toString(),
            title: article.title.rendered,
            excerpt: article.excerpt.rendered.replace(/<\/?[^>]+(>|$)/g, ""), // Strip HTML
            source: article.acf?.source || 'Unknown Source',
            sourceUrl: article.acf?.source_url || '#',
            imageUrl: article._embedded?.['wp:featuredmedia']?.[0]?.source_url || '',
            date: new Date(article.date).toLocaleDateString(),
            tags: article._embedded?.['wp:term']?.[1]?.map(/** @param {WPTerm} tag */(tag) => tag.name) || []
        }));
    } catch (error) {
        console.error('Failed to fetch articles:', error);
        return [];
    }
}

/**
 * Fetch a single article by ID
 * @param {string|number} id - Article ID
 * @returns {Promise<ArticleProps|null>} - Article object or null if not found
 */
export async function fetchArticleById(id) {
    try {
        const response = await fetch(`${WP_API_URL}/article/${id}?_embed`);

        if (!response.ok) {
            throw new Error(`Error fetching article: ${response.statusText}`);
        }

        /** @type {any} */
        const article = await response.json();

        return {
            id: article.id.toString(),
            title: article.title.rendered,
            content: article.content.rendered,
            excerpt: article.excerpt.rendered.replace(/<\/?[^>]+(>|$)/g, ""), // Strip HTML
            source: article.acf?.source || 'Unknown Source',
            sourceUrl: article.acf?.source_url || '#',
            imageUrl: article._embedded?.['wp:featuredmedia']?.[0]?.source_url || '',
            date: new Date(article.date).toLocaleDateString(),
            tags: article._embedded?.['wp:term']?.[1]?.map(/** @param {WPTerm} tag */(tag) => tag.name) || []
        };
    } catch (error) {
        console.error(`Failed to fetch article with ID ${id}:`, error);
        return null;
    }
}

/**
 * @typedef {Object} DigestResponse
 * @property {string} content - Digest content
 * @property {string} share_link - Share link for the digest
 */

/**
 * Fetch latest digest
 * @returns {Promise<DigestResponse|null>} - Digest object or null if not found
 */
export async function fetchLatestDigest() {
    try {
        const response = await fetch(`${ASAP_API_URL}/digest`);

        if (!response.ok) {
            throw new Error(`Error fetching digest: ${response.statusText}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Failed to fetch latest digest:', error);
        return null;
    }
}

/**
 * Fetch digest by ID
 * @param {string|number} id - Digest ID
 * @returns {Promise<DigestResponse|null>} - Digest object or null if not found
 */
export async function fetchDigestById(id) {
    try {
        const response = await fetch(`${ASAP_API_URL}/digest/${id}`);

        if (!response.ok) {
            throw new Error(`Error fetching digest: ${response.statusText}`);
        }

        return await response.json();
    } catch (error) {
        console.error(`Failed to fetch digest with ID ${id}:`, error);
        return null;
    }
}

/**
 * Fetch articles using GraphQL
 * @param {Object} options - Query options
 * @param {number} [options.first=10] - Number of articles to fetch
 * @param {string} [options.after] - Cursor for pagination
 * @returns {Promise<ArticleProps[]>} - Array of article objects
 */
export async function fetchArticlesGraphQL(options = {}) {
    const { first = 10, after } = options;

    const query = `
        query GetArticles($first: Int!, $after: String) {
            articles(first: $first, after: $after) {
                edges {
                    node {
                        id
                        title
                        date
                        excerpt
                        featuredImage {
                            node {
                                sourceUrl
                            }
                        }
                        articleFields {
                            source
                            sourceUrl
                        }
                        tags {
                            nodes {
                                name
                            }
                        }
                    }
                }
            }
        }
    `;

    try {
        const response = await fetch(WP_GRAPHQL_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                query,
                variables: { first, after },
            }),
        });

        if (!response.ok) {
            throw new Error(`Error fetching articles with GraphQL: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.errors) {
            throw new Error(`GraphQL errors: ${result.errors.map(/** @param {GraphQLError} e */(e) => e.message).join(', ')}`);
        }

        // Transform the response to match our ArticleProps format
        return result.data.articles.edges.map((/** @type {GraphQLEdge} */ edge) => {
            const { node } = edge;
            return {
                id: node.id,
                title: node.title,
                excerpt: node.excerpt.replace(/<\/?[^>]+(>|$)/g, ""), // Strip HTML
                source: node.articleFields?.source || 'Unknown Source',
                sourceUrl: node.articleFields?.sourceUrl || '#',
                imageUrl: node.featuredImage?.node?.sourceUrl || '',
                date: new Date(node.date).toLocaleDateString(),
                tags: node.tags?.nodes?.map(tag => tag.name) || []
            };
        });
    } catch (error) {
        console.error('Failed to fetch articles with GraphQL:', error);
        return [];
    }
} 