/**
 * Crawler API - Interface for the WordPress Content Crawler
 * @module api/crawler-api
 */

/**
 * @typedef {Object} CrawlerSource
 * @property {number} id - Source ID
 * @property {string} name - Source name
 * @property {string} url - Source URL
 * @property {string} type - Source type (rss, api, scraper)
 * @property {string} status - Source status
 * @property {string} created_at - Creation timestamp
 * @property {string} updated_at - Last update timestamp
 */

/**
 * @typedef {Object} CrawlerContent
 * @property {number} id - Content ID
 * @property {string} title - Content title
 * @property {string} content - Content HTML
 * @property {string} summary - Content summary
 * @property {string} url - Original content URL
 * @property {string} image - Featured image URL
 * @property {string} publish_date - Publication date
 * @property {string} status - Content status (pending, approved, rejected)
 * @property {number} source_id - Source ID
 * @property {string} source_name - Source name
 * @property {string} type - Content type
 * @property {string} created_at - Creation timestamp
 */

/**
 * API base URL
 * @type {string}
 */
const API_BASE = '/wp-json/asap/v1/crawler';

/**
 * Fetch API wrapper with error handling
 * @param {string} endpoint - API endpoint
 * @param {Object} options - Fetch options
 * @returns {Promise<any>} Response data
 */
async function fetchAPI(endpoint, options = {}) {
  try {
    const response = await fetch(`${API_BASE}${endpoint}`, options);
    
    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`API error (${response.status}): ${errorText}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error('Crawler API error:', error);
    throw error;
  }
}

/**
 * Get crawler sources
 * @param {Object} options - Request options
 * @param {number} [options.page=1] - Page number
 * @param {number} [options.per_page=20] - Items per page
 * @param {string} [options.status] - Filter by status
 * @param {string} [options.type] - Filter by source type
 * @returns {Promise<{sources: CrawlerSource[], total: number, pages: number}>} Sources and pagination
 */
export async function getSources(options = {}) {
  const params = new URLSearchParams();
  
  if (options.page) params.append('page', String(options.page));
  if (options.per_page) params.append('per_page', String(options.per_page));
  if (options.status) params.append('status', options.status);
  if (options.type) params.append('type', options.type);
  
  const query = params.toString() ? `?${params.toString()}` : '';
  
  return fetchAPI(`/sources${query}`);
}

/**
 * Get a single source by ID
 * @param {number} id - Source ID
 * @returns {Promise<CrawlerSource>} Source details
 */
export async function getSource(id) {
  return fetchAPI(`/sources/${id}`);
}

/**
 * Create a new content source
 * @param {Object} source - Source data
 * @param {string} source.name - Source name
 * @param {string} source.url - Source URL
 * @param {string} source.type - Source type (rss, api, scraper)
 * @param {Object} [source.config] - Source configuration
 * @returns {Promise<CrawlerSource>} Created source
 */
export async function createSource(source) {
  return fetchAPI('/sources', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(source)
  });
}

/**
 * Update an existing content source
 * @param {number} id - Source ID
 * @param {Object} source - Source data to update
 * @returns {Promise<CrawlerSource>} Updated source
 */
export async function updateSource(id, source) {
  return fetchAPI(`/sources/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(source)
  });
}

/**
 * Delete a content source
 * @param {number} id - Source ID
 * @returns {Promise<{success: boolean}>} Success response
 */
export async function deleteSource(id) {
  return fetchAPI(`/sources/${id}`, {
    method: 'DELETE'
  });
}

/**
 * Run the crawler for a specific source
 * @param {number} id - Source ID
 * @returns {Promise<Object>} Crawl results
 */
export async function runSourceCrawl(id) {
  return fetchAPI(`/run/${id}`, {
    method: 'POST'
  });
}

/**
 * Run the crawler for all due sources
 * @returns {Promise<Object>} Crawl results
 */
export async function runCrawler() {
  return fetchAPI('/run', {
    method: 'POST'
  });
}

/**
 * Get crawler status
 * @returns {Promise<Object>} Crawler status
 */
export async function getCrawlerStatus() {
  return fetchAPI('/status');
}

/**
 * Get content from the moderation queue
 * @param {Object} options - Request options
 * @param {number} [options.page=1] - Page number
 * @param {number} [options.per_page=20] - Items per page
 * @param {string} [options.status] - Filter by status (pending, approved, rejected)
 * @param {number} [options.source_id] - Filter by source ID
 * @param {string} [options.search] - Search term
 * @returns {Promise<{items: CrawlerContent[], total: number, pages: number}>} Content and pagination
 */
export async function getQueuedContent(options = {}) {
  const params = new URLSearchParams();
  
  if (options.page) params.append('page', String(options.page));
  if (options.per_page) params.append('per_page', String(options.per_page));
  if (options.status) params.append('status', options.status);
  if (options.source_id) params.append('source_id', String(options.source_id));
  if (options.search) params.append('search', options.search);
  
  const query = params.toString() ? `?${params.toString()}` : '';
  
  return fetchAPI(`/queue${query}`);
}

/**
 * Get approved content for the selector interface
 * @param {Object} options - Request options
 * @param {number} [options.page=1] - Page number
 * @param {number} [options.per_page=20] - Items per page
 * @param {number} [options.source_id] - Filter by source ID
 * @param {string} [options.search] - Search term
 * @param {string} [options.orderby='date'] - Order by field
 * @param {string} [options.order='desc'] - Order direction
 * @returns {Promise<{items: CrawlerContent[], total_items: number, total_pages: number, sources: Array}>} Content and pagination
 */
export async function getContent(options = {}) {
  const params = new URLSearchParams();
  
  if (options.page) params.append('page', String(options.page));
  if (options.per_page) params.append('per_page', String(options.per_page));
  if (options.source_id) params.append('source_id', String(options.source_id));
  if (options.search) params.append('search', options.search);
  if (options.orderby) params.append('orderby', options.orderby);
  if (options.order) params.append('order', options.order);
  
  // Only get approved content
  params.append('status', 'approved');
  
  const query = params.toString() ? `?${params.toString()}` : '';
  
  return fetchAPI(`/content${query}`);
}

/**
 * Approve content in the moderation queue
 * @param {number} id - Content ID
 * @param {Object} [options] - Approval options
 * @param {string} [options.reviewer_note] - Reviewer note
 * @returns {Promise<{success: boolean}>} Success response
 */
export async function approveContent(id, options = {}) {
  return fetchAPI(`/queue/approve/${id}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(options)
  });
}

/**
 * Reject content in the moderation queue
 * @param {number} id - Content ID
 * @param {Object} [options] - Rejection options
 * @param {string} [options.reason] - Rejection reason
 * @param {string} [options.reviewer_note] - Reviewer note
 * @returns {Promise<{success: boolean}>} Success response
 */
export async function rejectContent(id, options = {}) {
  return fetchAPI(`/queue/reject/${id}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(options)
  });
}

/**
 * Get moderation log for a content item
 * @param {number} id - Content ID
 * @returns {Promise<Array>} Moderation log entries
 */
export async function getModerationLog(id) {
  return fetchAPI(`/moderation-log/${id}`);
}

/**
 * Get moderation metrics and analytics
 * @returns {Promise<Object>} Moderation metrics
 */
export async function getModerationMetrics() {
  return fetchAPI('/moderation-metrics');
}

/**
 * Get all metrics (source, storage, moderation)
 * @returns {Promise<Object>} All metrics
 */
export async function getAllMetrics() {
  return fetchAPI('/metrics');
}

/**
 * Integrate crawled content with the digest
 * @param {number|string} contentId - Crawler content ID
 * @param {number|string} digestId - Digest post ID
 * @returns {Promise<Object>} Integration result
 */
export async function addContentToDigest(contentId, digestId) {
  return fetchAPI(`/content/${contentId}/add-to-digest/${digestId}`, {
    method: 'POST'
  });
}

/**
 * Default export with all API functions
 */
export default {
  getSources,
  getSource,
  createSource,
  updateSource,
  deleteSource,
  runSourceCrawl,
  runCrawler,
  getCrawlerStatus,
  getQueuedContent,
  getContent,
  approveContent,
  rejectContent,
  getModerationLog,
  getModerationMetrics,
  getAllMetrics,
  addContentToDigest
}; 