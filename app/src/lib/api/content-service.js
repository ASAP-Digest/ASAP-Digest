/**
 * Content Fetcher Service for retrieving various content types via GraphQL
 * @module api/content-service
 */

import { 
  ARTICLE_QUERY,
  PODCAST_QUERY,
  KEYTERM_QUERY,
  FINANCIAL_QUERY,
  XPOST_QUERY,
  REDDIT_QUERY,
  EVENT_QUERY,
  POLYMARKET_QUERY
} from './queries/content-queries';

import { APP_ENV, SITE_URL, WP_GRAPHQL_URL } from './wordpress';

/**
 * @typedef {Object} ContentItem
 * @property {string} id - Unique identifier
 * @property {string} type - Content type (article, podcast, etc.)
 * @property {string} title - Content title
 * @property {string} [excerpt] - Brief excerpt or summary
 * @property {string} [source] - Source of the content
 * @property {string} [imageUrl] - URL to the featured image
 * @property {string} [date] - Publication date
 * @property {Object} [meta] - Additional metadata specific to content type
 */

/**
 * @typedef {Object} PaginationInfo
 * @property {boolean} hasNextPage - Whether there are more pages
 * @property {string} endCursor - Cursor for fetching the next page
 */

/**
 * @typedef {Object} QueryParams
 * @property {number} [limit=10] - Number of items to retrieve
 * @property {string} [cursor] - Pagination cursor
 * @property {string} [search] - Search query
 * @property {string} [dateFrom] - Start date (ISO format)
 * @property {string} [dateTo] - End date (ISO format)
 * @property {string[]} [categories] - Category IDs
 * @property {string} [type] - Type filter (for financial data)
 * @property {string} [subreddit] - Subreddit filter (for reddit posts)
 */

/**
 * @typedef {Object} ContentResponse
 * @property {ContentItem[]} items - Array of content items
 * @property {PaginationInfo} pagination - Pagination information
 */

/**
 * @typedef {Object} GraphQLResponse
 * @property {Object} data - The data returned from the GraphQL query
 * @property {Object[]} [errors] - Any errors returned from the GraphQL query
 */

/**
 * Maps content types to their GraphQL queries
 */
const CONTENT_TYPE_QUERIES = {
  article: ARTICLE_QUERY,
  podcast: PODCAST_QUERY,
  keyterm: KEYTERM_QUERY,
  financial: FINANCIAL_QUERY,
  xpost: XPOST_QUERY,
  reddit: REDDIT_QUERY,
  event: EVENT_QUERY,
  polymarket: POLYMARKET_QUERY
};

/**
 * Maps content types to their GraphQL query root fields
 */
const CONTENT_TYPE_FIELDS = {
  article: 'posts',
  podcast: 'podcasts',
  keyterm: 'keyTerms',
  financial: 'financialData',
  xpost: 'xPosts',
  reddit: 'redditPosts',
  event: 'events',
  polymarket: 'polymarkets'
};

/**
 * Maps content types to their ACF field names
 */
const CONTENT_TYPE_ACF_FIELDS = {
  article: 'acfArticle',
  podcast: 'acfPodcast',
  keyterm: 'acfKeyTerm',
  financial: 'acfFinancial',
  xpost: 'acfXPost',
  reddit: 'acfReddit',
  event: 'acfEvent',
  polymarket: 'acfPolymarket'
};

/**
 * Execute a GraphQL query against the WordPress GraphQL endpoint
 * 
 * @param {string} query - GraphQL query
 * @param {Object} variables - GraphQL variables
 * @returns {Promise<GraphQLResponse>} - GraphQL response
 */
async function executeGraphQLQuery(query, variables) {
  try {
    // Add logging in development
    if (APP_ENV === 'development') {
      console.debug('GraphQL Query:', query);
      console.debug('GraphQL Variables:', variables);
    }

    const response = await fetch(WP_GRAPHQL_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        query,
        variables,
      }),
    });

    if (!response.ok) {
      throw new Error(`GraphQL request failed: ${response.statusText}`);
    }

    const result = await response.json();

    // Check for GraphQL errors
    if (result.errors) {
      const errorMessages = result.errors.map(e => e.message).join(', ');
      console.error('GraphQL errors:', errorMessages);
      throw new Error(`GraphQL errors: ${errorMessages}`);
    }

    return result;
  } catch (error) {
    console.error('GraphQL query execution failed:', error);
    throw error;
  }
}

/**
 * Normalize content item data based on its type
 * 
 * @param {Object} node - GraphQL node from response
 * @param {string} contentType - Content type
 * @returns {ContentItem} - Normalized content item
 */
function normalizeContentItem(node, contentType) {
  // Get the ACF field name for this content type
  const acfField = CONTENT_TYPE_ACF_FIELDS[contentType];
  
  // Base content item
  /** @type {ContentItem} */
  const baseItem = {
    id: node.id || node.databaseId?.toString() || '',
    type: contentType,
    title: node.title || '',
    imageUrl: node.featuredImage?.node?.sourceUrl || '',
    date: node.date ? new Date(node.date).toLocaleDateString() : '',
    meta: {}
  };

  // Common fields in ACF
  if (node[acfField]) {
    baseItem.source = node[acfField].source || '';
    
    // Handle different excerpt/summary fields
    if (contentType === 'article') {
      baseItem.excerpt = node.excerpt?.replace(/<\/?[^>]+(>|$)/g, "") || node[acfField].summary || '';
    } else if (contentType === 'keyterm') {
      baseItem.excerpt = node[acfField].definition || '';
    } else {
      baseItem.excerpt = node[acfField].summary || node[acfField].description || '';
    }
  }

  // Add type-specific metadata
  switch (contentType) {
    case 'article':
      if (node[acfField]) {
        baseItem.meta = {
          timestamp: node[acfField].timestamp || '',
        };
      }
      break;
    
    case 'podcast':
      if (node[acfField]) {
        baseItem.meta = {
          duration: node[acfField].duration || '',
          audioUrl: node[acfField].audioUrl || '',
        };
      }
      break;
    
    case 'keyterm':
      if (node[acfField]) {
        baseItem.meta = {
          relatedTerms: node[acfField].relatedTerms || [],
        };
      }
      break;
    
    case 'financial':
      if (node[acfField]) {
        baseItem.meta = {
          type: node[acfField].type || '',
          value: node[acfField].value || '',
          change: node[acfField].change || '',
          period: node[acfField].period || '',
        };
      }
      break;
    
    case 'xpost':
      if (node[acfField]) {
        baseItem.meta = {
          content: node[acfField].content || '',
          author: node[acfField].author || '',
          username: node[acfField].username || '',
          postUrl: node[acfField].postUrl || '',
          timestamp: node[acfField].timestamp || '',
          likes: node[acfField].likes || 0,
          reposts: node[acfField].reposts || 0,
        };
      }
      break;
    
    case 'reddit':
      if (node[acfField]) {
        baseItem.meta = {
          content: node[acfField].content || '',
          subreddit: node[acfField].subreddit || '',
          author: node[acfField].author || '',
          postUrl: node[acfField].postUrl || '',
          timestamp: node[acfField].timestamp || '',
          upvotes: node[acfField].upvotes || 0,
          downvotes: node[acfField].downvotes || 0,
          comments: node[acfField].comments || 0,
        };
      }
      break;
    
    case 'event':
      if (node[acfField]) {
        baseItem.meta = {
          location: node[acfField].location || '',
          startTime: node[acfField].startTime || '',
          endTime: node[acfField].endTime || '',
          organizer: node[acfField].organizer || '',
          eventUrl: node[acfField].eventUrl || '',
        };
      }
      break;
    
    case 'polymarket':
      if (node[acfField]) {
        baseItem.meta = {
          question: node[acfField].question || '',
          closeTime: node[acfField].closeTime || '',
          totalVolume: node[acfField].totalVolume || '',
          outcomes: node[acfField].outcomes || [],
        };
      }
      break;
  }

  return baseItem;
}

/**
 * Fetch content items of a specific type
 * 
 * @param {string} contentType - Type of content to fetch
 * @param {QueryParams} [params={}] - Query parameters
 * @returns {Promise<ContentResponse>} - Content items and pagination info
 */
export async function fetchContentItems(contentType, params = {}) {
  try {
    // Get the query for this content type
    const query = CONTENT_TYPE_QUERIES[contentType];
    if (!query) {
      throw new Error(`Unsupported content type: ${contentType}`);
    }

    // Get the root field for this content type
    const rootField = CONTENT_TYPE_FIELDS[contentType];
    if (!rootField) {
      throw new Error(`Unknown root field for content type: ${contentType}`);
    }

    // Prepare the variables
    const variables = {
      limit: params.limit || 10,
      cursor: params.cursor || null,
      search: params.search || null,
      dateFrom: params.dateFrom || null,
      dateTo: params.dateTo || null
    };

    // Add type-specific variables
    if (contentType === 'article' && params.categories) {
      variables.categories = params.categories;
    }
    if (contentType === 'financial' && params.type) {
      variables.type = params.type;
    }
    if (contentType === 'reddit' && params.subreddit) {
      variables.subreddit = params.subreddit;
    }

    // Execute the query
    const result = await executeGraphQLQuery(query, variables);

    // Extract the data
    const data = result.data[rootField];
    
    // If no data or nodes, return empty array
    if (!data || !data.nodes) {
      return {
        items: [],
        pagination: {
          hasNextPage: false,
          endCursor: null
        }
      };
    }

    // Normalize the items
    const items = data.nodes.map(node => normalizeContentItem(node, contentType));

    // Extract pagination info
    const pagination = {
      hasNextPage: data.pageInfo?.hasNextPage || false,
      endCursor: data.pageInfo?.endCursor || null
    };

    return {
      items,
      pagination
    };
  } catch (error) {
    console.error(`Error fetching ${contentType} items:`, error);
    
    // Return empty data on error
    return {
      items: [],
      pagination: {
        hasNextPage: false,
        endCursor: null
      }
    };
  }
}

/**
 * Search across multiple content types
 * 
 * @param {string[]} contentTypes - Types of content to search
 * @param {QueryParams} [params={}] - Query parameters
 * @returns {Promise<Record<string, ContentResponse>>} - Content items by type
 */
export async function searchMultipleContentTypes(contentTypes, params = {}) {
  try {
    // Execute all queries in parallel
    const promises = contentTypes.map(type => fetchContentItems(type, params));
    const results = await Promise.all(promises);

    // Create a map of results by content type
    const resultsByType = {};
    contentTypes.forEach((type, index) => {
      resultsByType[type] = results[index];
    });

    return resultsByType;
  } catch (error) {
    console.error('Error searching multiple content types:', error);
    
    // Return empty results for each type
    const emptyResults = {};
    contentTypes.forEach(type => {
      emptyResults[type] = {
        items: [],
        pagination: {
          hasNextPage: false,
          endCursor: null
        }
      };
    });
    
    return emptyResults;
  }
}

/**
 * Get all available content types
 * 
 * @returns {string[]} - Array of content type identifiers
 */
export function getAvailableContentTypes() {
  return Object.keys(CONTENT_TYPE_QUERIES);
}

/**
 * Get more details about content types (for UI display)
 * 
 * @returns {Array<{id: string, label: string}>} - Array of content type details
 */
export function getContentTypeDetails() {
  return [
    { id: 'article', label: 'Article' },
    { id: 'podcast', label: 'Podcast' },
    { id: 'keyterm', label: 'Key Term' },
    { id: 'financial', label: 'Financial' },
    { id: 'xpost', label: 'X Post' },
    { id: 'reddit', label: 'Reddit' },
    { id: 'event', label: 'Event' },
    { id: 'polymarket', label: 'Polymarket' }
  ];
} 