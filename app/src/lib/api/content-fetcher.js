/**
 * Enhanced Content Fetcher Service for the NewItemsSelector component
 * Extends basic content-service with caching, debouncing, and better error handling
 * @module api/content-fetcher
 */

import { browser } from '$app/environment';
// @ts-ignore - Import conflicts with local declaration
import { 
  fetchContentItems as fetchServiceItems, 
  searchMultipleContentTypes,
  getContentTypeDetails
} from './content-service.js';
import { getOptimalImageUrl } from '../utils/image-utils.js';
import { ARTICLE_QUERY, PODCAST_QUERY, FINANCIAL_QUERY, SOCIAL_QUERY, UNIFIED_CONTENT_QUERY } from './queries/content-queries.js';
import { getImageUrl } from '$lib/utils/image-utils.js';

// @ts-ignore - Missing type declarations for localforage
import localforage from 'localforage';

/**
 * @typedef {import('./content-service').ContentItem} BaseContentItem
 * @typedef {import('./content-service').QueryParams} QueryParams
 */

/**
 * Custom pagination info that allows null cursor
 * @typedef {Object} PaginationInfo
 * @property {boolean} hasNextPage - Whether there are more pages
 * @property {string|null} endCursor - Cursor for fetching the next page
 */

/**
 * @typedef {Object} EnhancedContentItem
 * @property {string} id - Unique identifier
 * @property {number} [databaseId] - Database identifier (WordPress specific)
 * @property {string} type - Content type (article, podcast, etc.)
 * @property {string} title - Content title
 * @property {string} [summary] - Brief excerpt or summary
 * @property {string} [excerpt] - Brief excerpt or summary (alias)
 * @property {string} [source] - Source of the content
 * @property {string} [image] - URL to content image
 * @property {string} [imageUrl] - URL to the featured image (alias)
 * @property {string} [date] - Publication date
 * @property {string} [formattedDate] - Formatted date string
 * @property {string} [optimizedImageUrl] - Optimized version of the image
 * @property {string} [thumbnailUrl] - Thumbnail version of the image
 * @property {Object} [raw] - Original raw data
 * @property {Object} [metadata] - Additional metadata
 * @property {Object} [meta] - Additional metadata (alias)
 */

/**
 * @typedef {BaseContentItem & EnhancedContentItem} ContentItem
 */

/**
 * @typedef {Object} ContentResponseData
 * @property {ContentItem[]} items - Content items
 * @property {Object} pageInfo - Pagination information
 * @property {boolean} pageInfo.hasNextPage - Whether there's another page
 * @property {string|null} pageInfo.endCursor - Cursor for the next page
 */

/**
 * @typedef {Object} CacheEntry
 * @property {ContentItem[]} items - Content items
 * @property {PaginationInfo} pagination - Pagination information
 * @property {number} timestamp - When the data was cached
 */

/**
 * @typedef {Object} DebouncedFunction
 * @property {Function} cancel - Function to cancel the debounced execution
 */

/**
 * @typedef {Object} FetchOptions
 * @property {number} [limit=10] - Number of items to fetch
 * @property {string} [cursor] - Pagination cursor
 * @property {string} [search] - Search query
 * @property {string} [dateFrom] - Start date filter (ISO format)
 * @property {string} [dateTo] - End date filter (ISO format)
 * @property {string[]} [categories] - Category IDs to filter by
 * @property {string[]} [platforms] - Social media platforms to filter by
 * @property {boolean} [useCache=true] - Whether to use cached data
 * @property {number} [cacheTime=5] - Cache duration in minutes
 */

// Cache TTL in milliseconds (10 minutes)
const CACHE_TTL = 10 * 60 * 1000;

// Cache key prefix
const CACHE_PREFIX = 'asap_content_';

/**
 * Get cache key for a specific query
 * 
 * @param {string} contentType - Content type
 * @param {QueryParams} params - Query params
 * @returns {string} - Cache key
 */
function getCacheKey(contentType, params) {
  const paramsKey = JSON.stringify(params || {});
  return `${CACHE_PREFIX}${contentType}_${paramsKey}`;
}

/**
 * Read cache data
 * 
 * @param {string} key - Cache key
 * @returns {CacheEntry|null} - Cached data or null if not found/expired
 */
function getCache(key) {
  if (!browser) return null;
  
  try {
    const data = localStorage.getItem(key);
    if (!data) return null;
    
    const parsed = JSON.parse(data);
    
    // Check if cache is expired
    if (Date.now() - parsed.timestamp > CACHE_TTL) {
      localStorage.removeItem(key);
      return null;
    }
    
    return parsed;
  } catch (error) {
    console.error('Error reading cache:', error);
    return null;
  }
}

/**
 * Write data to cache
 * 
 * @param {string} key - Cache key
 * @param {ContentItem[]} items - Content items
 * @param {PaginationInfo} pagination - Pagination info
 */
function setCache(key, items, pagination) {
  if (!browser) return;
  
  try {
    const data = {
      items,
      pagination,
      timestamp: Date.now()
    };
    
    localStorage.setItem(key, JSON.stringify(data));
  } catch (error) {
    console.error('Error writing to cache:', error);
  }
}

/**
 * Clear all content cache or specific type
 * 
 * @param {string} [contentType] - Optional content type to clear
 */
export function clearContentCache(contentType) {
  if (!browser) return;
  
  if (contentType) {
    // Clear only specific content type
    const prefix = `${CACHE_PREFIX}${contentType}_`;
    
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith(prefix)) {
        localStorage.removeItem(key);
      }
    }
  } else {
    // Clear all content cache
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith(CACHE_PREFIX)) {
        localStorage.removeItem(key);
      }
    }
  }
}

/**
 * Enhanced fetch content function with caching
 * 
 * @param {string|string[]} contentType - Type of content to fetch
 * @param {QueryParams} params - Query parameters
 * @param {Object} options - Additional options
 * @param {boolean} [options.bypassCache=false] - Whether to bypass cache
 * @param {boolean} [options.updateCache=true] - Whether to update cache
 * @returns {Promise<{items: ContentItem[], pagination: PaginationInfo}>} - Content items and pagination
 */
export async function fetchCachedContent(contentType, params, options = {}) {
  const { bypassCache = false, updateCache = true } = options;
  
  // Convert single string to array if needed
  const contentTypeArray = Array.isArray(contentType) ? contentType : [contentType];
  
  // Use first content type for cache key
  const cacheKey = getCacheKey(Array.isArray(contentType) ? contentType[0] : contentType, params);
  
  // Try to get from cache first
  if (!bypassCache) {
    const cached = getCache(cacheKey);
    if (cached) {
      console.log(`[Content Fetcher] Cache hit for ${contentType}`, params);
      return {
        items: cached.items,
        pagination: cached.pagination
      };
    }
  }
  
  console.log(`[Content Fetcher] Fetching ${contentType} from API`, params);
  
  // If not in cache or bypassing, fetch from API
  try {
    // @ts-ignore - Incorrect type between fetchServiceItems import and usage
    const result = await fetchServiceItems(contentTypeArray, params);
    
    // Process and enhance items
    const enhancedItems = result.items.map(item => enhanceContentItem(item));
    
    // Convert pageInfo to pagination for consistent interface
    const pagination = {
      // @ts-ignore - pageInfo property access
      hasNextPage: result.pageInfo?.hasNextPage || false,
      // @ts-ignore - pageInfo property access
      endCursor: result.pageInfo?.endCursor || null
    };
    
    // Update cache if enabled
    if (updateCache) {
      setCache(cacheKey, enhancedItems, pagination);
    }
    
    return {
      items: enhancedItems,
      pagination: pagination
    };
  } catch (error) {
    console.error(`[Content Fetcher] Error fetching ${contentType}:`, error);
    
    // Return empty result on error with typed pagination that accepts null cursor
    /** @type {PaginationInfo} */
    const emptyPagination = {
      hasNextPage: false,
      endCursor: null
    };
    
    return {
      items: [],
      pagination: emptyPagination
    };
  }
}

/**
 * Enhance a content item with additional computed properties
 * 
 * @param {BaseContentItem} item - Content item to enhance
 * @returns {ContentItem} - Enhanced content item
 */
function enhanceContentItem(item) {
  // Make a copy to avoid modifying the original
  /** @type {ContentItem} */
  // @ts-ignore - Property assignment is handled dynamically
  const enhanced = { 
    ...item,
    optimizedImageUrl: item.imageUrl || '',
    thumbnailUrl: item.imageUrl || '',
    formattedDate: item.date || ''
  };
  
  // Optimize image URL if present
  // @ts-ignore - Property access is handled dynamically
  if (enhanced.imageUrl) {
    // @ts-ignore - Property assignment is handled dynamically
    enhanced.optimizedImageUrl = getOptimalImageUrl(
      // @ts-ignore - Property access is handled dynamically
      { sourceUrl: enhanced.imageUrl },
      { width: 300, height: 200 }
    );
    
    // @ts-ignore - Property assignment is handled dynamically
    enhanced.thumbnailUrl = getOptimalImageUrl(
      // @ts-ignore - Property access is handled dynamically
      { sourceUrl: enhanced.imageUrl },
      { width: 80, height: 80 }
    );
  }
  
  // Format date for display if present
  if (enhanced.date) {
    try {
      const dateObj = new Date(enhanced.date);
      // @ts-ignore - Property assignment is handled dynamically
      enhanced.formattedDate = dateObj.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    } catch (e) {
      // @ts-ignore - Property assignment is handled dynamically
      enhanced.formattedDate = enhanced.date;
    }
  }
  
  return enhanced;
}

/**
 * Debounce helper function
 * 
 * @template T
 * @param {function(...any): T} func - Function to debounce
 * @param {number} wait - Wait time in ms
 * @returns {Function & {cancel: Function}} - Debounced function
 */
function debounce(func, wait) {
  let timeout;
  
  /** @type {Function & {cancel: Function}} */
  const debouncedFunc = function(...args) {
    clearTimeout(timeout);
    
    timeout = setTimeout(() => {
      func.apply(undefined, args);
    }, wait);
  };
  
  debouncedFunc.cancel = function() {
    clearTimeout(timeout);
  };
  
  return debouncedFunc;
}

// Create a debounced version of fetchCachedContent with 300ms delay
export const debouncedFetchContent = debounce(fetchCachedContent, 300);

/**
 * Search across multiple content types with debouncing
 * 
 * @param {string[]} contentTypes - Types to search
 * @param {QueryParams} params - Search parameters
 * @param {function(Object, Error|null): void} callback - Callback function for results
 * @returns {Function} - Function to cancel the debounced search
 */
export function searchContentWithDebounce(contentTypes, params, callback) {
  const debouncedSearch = debounce(async () => {
    try {
      const results = await searchMultipleContentTypes(contentTypes, params);
      
      // Enhance all items in the results
      const enhancedResults = {};
      
      for (const type in results) {
        if (Object.prototype.hasOwnProperty.call(results, type)) {
          enhancedResults[type] = {
            items: results[type].items.map(item => enhanceContentItem(item)),
            pagination: results[type].pagination
          };
        }
      }
      
      callback(enhancedResults, null);
    } catch (error) {
      console.error('[Content Fetcher] Search error:', error);
      // Cast the error to Error type
      callback({}, /** @type {Error} */(error) || new Error('Unknown error'));
    }
  }, 500);
  
  debouncedSearch();
  
  return function cancelSearch() { 
    if (typeof debouncedSearch.cancel === 'function') {
      debouncedSearch.cancel();
    }
  };
}

/**
 * Get a single content item by ID with caching
 * 
 * @param {string} contentType - Content type
 * @param {string} id - Item ID
 * @returns {Promise<ContentItem|null>} - Content item or null if not found
 */
export async function getContentItemById(contentType, id) {
  // Try to find in cache first
  if (browser) {
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith(`${CACHE_PREFIX}${contentType}_`)) {
        try {
          const data = localStorage.getItem(key);
          if (!data) continue;
          
          const parsed = JSON.parse(data);
          const found = parsed.items.find(item => item.id === id);
          
          if (found) {
            return enhanceContentItem(found);
          }
        } catch (e) {
          continue;
        }
      }
    }
  }
  
  // Not found in cache, fetch from API
  try {
    // Fetch from API with proper type handling
    // @ts-ignore - Incorrect type between fetchServiceItems import and usage
    const result = await fetchServiceItems([contentType], { limit: 100 });
    const found = result.items.find(item => item.id === id);
    
    if (found) {
      return enhanceContentItem(found);
    }
    
    return null;
  } catch (error) {
    console.error(`[Content Fetcher] Error getting ${contentType} item ${id}:`, error);
    return null;
  }
}

/**
 * Create a selected items manager for persisting selection state
 * 
 * @param {string} storageKey - Local storage key
 * @returns {Object} - Selected items manager
 */
export function createSelectedItemsManager(storageKey = 'asap_selected_items') {
  // Get initial items from storage
  const getInitialItems = () => {
    if (!browser) return [];
    
    try {
      const stored = localStorage.getItem(storageKey);
      return stored ? JSON.parse(stored) : [];
    } catch (e) {
      return [];
    }
  };
  
  // Internal state
  let selectedItems = getInitialItems();
  let listeners = [];
  
  // Save to storage
  const saveToStorage = () => {
    if (!browser) return;
    
    try {
      localStorage.setItem(storageKey, JSON.stringify(selectedItems));
    } catch (e) {
      console.error('[Content Fetcher] Error saving selected items:', e);
    }
  };
  
  // Notify listeners
  const notifyListeners = () => {
    listeners.forEach(listener => listener(selectedItems));
  };
  
  return {
    /**
     * Get all selected items
     * @returns {ContentItem[]} - Selected items
     */
    getItems: () => [...selectedItems],
    
    /**
     * Add an item to selection
     * @param {ContentItem} item - Item to add
     * @returns {boolean} - Whether the item was added
     */
    addItem: (item) => {
      // Check if already selected
      if (selectedItems.some(i => i.id === item.id && i.type === item.type)) {
        return false;
      }
      
      selectedItems = [...selectedItems, item];
      saveToStorage();
      notifyListeners();
      return true;
    },
    
    /**
     * Remove an item from selection
     * @param {ContentItem} item - Item to remove
     * @returns {boolean} - Whether the item was removed
     */
    removeItem: (item) => {
      const initialLength = selectedItems.length;
      selectedItems = selectedItems.filter(
        i => !(i.id === item.id && i.type === item.type)
      );
      
      if (selectedItems.length !== initialLength) {
        saveToStorage();
        notifyListeners();
        return true;
      }
      
      return false;
    },
    
    /**
     * Toggle selection state of an item
     * @param {ContentItem} item - Item to toggle
     * @returns {boolean} - New selection state (true=selected)
     */
    toggleItem: (item) => {
      const isSelected = selectedItems.some(
        i => i.id === item.id && i.type === item.type
      );
      
      if (isSelected) {
        selectedItems = selectedItems.filter(
          i => !(i.id === item.id && i.type === item.type)
        );
      } else {
        selectedItems = [...selectedItems, item];
      }
      
      saveToStorage();
      notifyListeners();
      return !isSelected;
    },
    
    /**
     * Check if an item is selected
     * @param {ContentItem} item - Item to check
     * @returns {boolean} - Whether the item is selected
     */
    isSelected: (item) => {
      return selectedItems.some(
        i => i.id === item.id && i.type === item.type
      );
    },
    
    /**
     * Clear all selected items
     */
    clearItems: () => {
      selectedItems = [];
      saveToStorage();
      notifyListeners();
    },
    
    /**
     * Subscribe to changes
     * @param {Function} listener - Callback function
     * @returns {Function} - Unsubscribe function
     */
    subscribe: (listener) => {
      listeners.push(listener);
      return () => {
        listeners = listeners.filter(l => l !== listener);
      };
    }
  };
}

/**
 * Ingest selected items into the user's digest via the backend REST API
 *
 * @param {ContentItem[]} items - Array of selected content items
 * @param {string|number} userId - User ID
 * @param {string|number|null} digestId - Digest ID (optional)
 * @returns {Promise<{success: boolean, results: Array, errors: Array}>} API response
 * @example
 *   const result = await ingestDigestItems(selectedItems, userId, digestId);
 */
export async function ingestDigestItems(items, userId, digestId = null) {
  try {
    const response = await fetch('/asap/v1/ingest-digest-items', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items, userId, digestId })
    });
    if (!response.ok) {
      throw new Error(`Ingestion API error: ${response.status} ${response.statusText}`);
    }
    const result = await response.json();
    if (!result.success) {
      throw new Error('Ingestion failed: ' + (result.errors?.[0]?.error || 'Unknown error'));
    }
    return result;
  } catch (error) {
    console.error('[Content Fetcher] Ingestion error:', error);
    return { success: false, results: [], errors: [{ error: error instanceof Error ? error.message : String(error) }] };
  }
}

/**
 * Default export - creates a content manager with both fetching and selection capabilities
 */
export function createContentManager() {
  const selectionManager = createSelectedItemsManager();
  
  return {
    ...selectionManager,
    fetchContent: fetchCachedContent,
    searchContent: searchContentWithDebounce,
    getItemById: getContentItemById,
    clearCache: clearContentCache,
    ingestDigestItems
  };
}

// GraphQL endpoint
const API_ENDPOINT = '/api/graphql';

// Cache config
const DEFAULT_CACHE_TIME = 5 * 60 * 1000; // 5 minutes

/**
 * Execute a GraphQL query against the WordPress API
 * @param {string} query - GraphQL query string
 * @param {Object} variables - Query variables
 * @param {boolean} [useCache=true] - Whether to use cached results
 * @param {number} [cacheTime=5] - Cache duration in minutes
 * @returns {Promise<Object>} Query result
 */
async function executeQuery(query, variables, useCache = true, cacheTime = 5) {
  if (!browser) return { data: null };
  
  const cacheKey = `${CACHE_PREFIX}${JSON.stringify({ query, variables })}`;
  
  // Try to get from cache if enabled
  if (useCache) {
    try {
      const cached = await localforage.getItem(cacheKey);
      if (cached && cached.timestamp > Date.now() - (cacheTime * 60 * 1000)) {
        console.log('[Content Fetcher] Using cached data:', cacheKey);
        return cached.data;
      }
    } catch (error) {
      console.warn('[Content Fetcher] Cache error:', error);
    }
  }
  
  // Execute query
  try {
    const response = await fetch(API_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        query,
        variables
      })
    });
    
    if (!response.ok) {
      throw new Error(`GraphQL request failed: ${response.status} ${response.statusText}`);
    }
    
    const result = await response.json();
    
    // Store in cache if enabled
    if (useCache && browser) {
      try {
        await localforage.setItem(cacheKey, {
          timestamp: Date.now(),
          data: result
        });
      } catch (error) {
        console.warn('[Content Fetcher] Cache storage error:', error);
      }
    }
    
    return result;
  } catch (error) {
    console.error('[Content Fetcher] Query error:', error);
    throw error;
  }
}

/**
 * Normalize article data into a consistent format
 * @param {Object} article - Raw article data from GraphQL
 * @returns {ContentItem} Normalized content item
 */
function normalizeArticle(article) {
  return {
    id: article.id,
    databaseId: article.databaseId,
    type: 'article',
    title: article.title,
    summary: article.acfArticle?.summary || '',
    source: article.acfArticle?.source || '',
    image: article.acfArticle?.image?.sourceUrl || '',
    date: article.date,
    raw: article,
    metadata: {
      timestamp: article.acfArticle?.timestamp || '',
    }
  };
}

/**
 * Normalize podcast data into a consistent format
 * @param {Object} podcast - Raw podcast data from GraphQL
 * @returns {ContentItem} Normalized content item
 */
function normalizePodcast(podcast) {
  return {
    id: podcast.id,
    databaseId: podcast.databaseId,
    type: 'podcast',
    title: podcast.title,
    summary: podcast.acfPodcast?.summary || '',
    source: podcast.acfPodcast?.host || '',
    image: podcast.acfPodcast?.coverImage?.sourceUrl || '',
    date: podcast.date,
    raw: podcast,
    metadata: {
      audioUrl: podcast.acfPodcast?.audioUrl || '',
      duration: podcast.acfPodcast?.duration || '',
    }
  };
}

/**
 * Normalize financial data into a consistent format
 * @param {Object} financial - Raw financial data from GraphQL
 * @returns {ContentItem} Normalized content item
 */
function normalizeFinancial(financial) {
  return {
    id: financial.id,
    databaseId: financial.databaseId,
    type: 'financial',
    title: financial.title,
    summary: financial.acfFinancial?.summary || '',
    source: financial.acfFinancial?.source || '',
    image: financial.acfFinancial?.chartImage?.sourceUrl || '',
    date: financial.date,
    raw: financial,
    metadata: {
      dataPoints: financial.acfFinancial?.dataPoints || [],
    }
  };
}

/**
 * Normalize social media post data into a consistent format
 * @param {Object} social - Raw social post data from GraphQL
 * @returns {ContentItem} Normalized content item
 */
function normalizeSocial(social) {
  return {
    id: social.id,
    databaseId: social.databaseId,
    type: 'social',
    title: social.title || `${social.acfSocial?.author} on ${social.acfSocial?.platform}`,
    summary: social.acfSocial?.content || '',
    source: social.acfSocial?.platform || '',
    image: social.acfSocial?.mediaUrl || '',
    date: social.date,
    raw: social,
    metadata: {
      author: social.acfSocial?.author || '',
      platform: social.acfSocial?.platform || '',
      engagementStats: social.acfSocial?.engagementStats || {},
      link: social.acfSocial?.link || '',
    }
  };
}

/**
 * Fetch articles based on provided options
 * @param {FetchOptions} options - Fetch options
 * @returns {Promise<{items: ContentItem[], pageInfo: Object}>} Normalized articles and pagination info
 */
export async function fetchArticles(options = {}) {
  const variables = {
    limit: options.limit || 10,
    cursor: options.cursor || null,
    search: options.search || null,
    dateFrom: options.dateFrom || null,
    dateTo: options.dateTo || null,
    categories: options.categories || null
  };
  
  const result = await executeQuery(
    ARTICLE_QUERY, 
    variables, 
    options.useCache !== undefined ? options.useCache : true,
    options.cacheTime || 5
  );
  
  if (result.data?.posts?.nodes) {
    return {
      items: result.data.posts.nodes.map(normalizeArticle),
      pageInfo: result.data.posts.pageInfo
    };
  }
  
  return { items: [], pageInfo: { hasNextPage: false, endCursor: null } };
}

/**
 * Fetch podcasts based on provided options
 * @param {FetchOptions} options - Fetch options
 * @returns {Promise<{items: ContentItem[], pageInfo: Object}>} Normalized podcasts and pagination info
 */
export async function fetchPodcasts(options = {}) {
  const variables = {
    limit: options.limit || 10,
    cursor: options.cursor || null,
    search: options.search || null,
    dateFrom: options.dateFrom || null,
    dateTo: options.dateTo || null,
  };
  
  const result = await executeQuery(
    PODCAST_QUERY, 
    variables, 
    options.useCache !== undefined ? options.useCache : true,
    options.cacheTime || 5
  );
  
  if (result.data?.podcasts?.nodes) {
    return {
      items: result.data.podcasts.nodes.map(normalizePodcast),
      pageInfo: result.data.podcasts.pageInfo
    };
  }
  
  return { items: [], pageInfo: { hasNextPage: false, endCursor: null } };
}

/**
 * Fetch financial data based on provided options
 * @param {FetchOptions} options - Fetch options
 * @returns {Promise<{items: ContentItem[], pageInfo: Object}>} Normalized financial data and pagination info
 */
export async function fetchFinancialData(options = {}) {
  const variables = {
    limit: options.limit || 10,
    cursor: options.cursor || null,
    search: options.search || null,
    dateFrom: options.dateFrom || null,
    dateTo: options.dateTo || null,
  };
  
  const result = await executeQuery(
    FINANCIAL_QUERY, 
    variables, 
    options.useCache !== undefined ? options.useCache : true,
    options.cacheTime || 5
  );
  
  if (result.data?.financialData?.nodes) {
    return {
      items: result.data.financialData.nodes.map(normalizeFinancial),
      pageInfo: result.data.financialData.pageInfo
    };
  }
  
  return { items: [], pageInfo: { hasNextPage: false, endCursor: null } };
}

/**
 * Fetch social media posts based on provided options
 * @param {FetchOptions} options - Fetch options
 * @returns {Promise<{items: ContentItem[], pageInfo: Object}>} Normalized social posts and pagination info
 */
export async function fetchSocialPosts(options = {}) {
  const variables = {
    limit: options.limit || 10,
    cursor: options.cursor || null,
    search: options.search || null,
    dateFrom: options.dateFrom || null,
    dateTo: options.dateTo || null,
    platforms: options.platforms || null,
  };
  
  const result = await executeQuery(
    SOCIAL_QUERY, 
    variables, 
    options.useCache !== undefined ? options.useCache : true,
    options.cacheTime || 5
  );
  
  if (result.data?.socialPosts?.nodes) {
    return {
      items: result.data.socialPosts.nodes.map(normalizeSocial),
      pageInfo: result.data.socialPosts.pageInfo
    };
  }
  
  return { items: [], pageInfo: { hasNextPage: false, endCursor: null } };
}

/**
 * Fetch content of various types in a unified format using GraphQL
 * @param {string[]} types - Content types to fetch ('article', 'podcast', etc.)
 * @param {FetchOptions} options - Fetch options
 * @returns {Promise<{items: ContentItem[], pageInfo: PaginationInfo}>} Normalized content items and pagination info
 */
export async function fetchContent(types = [], options = {}) {
  const variables = {
    types: types.length > 0 ? types : null, // Pass null if no specific types requested, assuming query handles this
    limit: options.limit || 10,
    after: options.cursor || null, // Use 'after' as per standard GraphQL pagination
    search: options.search || null,
    dateFrom: options.dateFrom || null,
    dateTo: options.dateTo || null,
    // Pass other filters like categories, platforms if the UNIFIED_CONTENT_QUERY supports them
    // categories: options.categories || null, 
    // platforms: options.platforms || null,
  };

  // Use executeQuery with UNIFIED_CONTENT_QUERY
  const result = await executeQuery(
    UNIFIED_CONTENT_QUERY, 
    variables, 
    options.useCache !== undefined ? options.useCache : true,
    options.cacheTime || 5
  );

  // Updated normalization logic based on UNIFIED_CONTENT_QUERY structure
  if (result?.data?.contentItems?.nodes) {
    const normalizedItems = result.data.contentItems.nodes.map(item => {
      // Assume UNIFIED_CONTENT_QUERY returns a structure compatible with ContentItem
      // Or perform normalization here if needed
      /** @type {ContentItem} */
      const normalizedItem = {
        id: item.id,
        databaseId: item.databaseId,
        type: item.type, // Assuming 'type' field exists directly
        title: item.title,
        summary: item.summary || item.excerpt || '', // Use summary or excerpt
        source: item.source || '', // Assuming 'source' field exists
        imageUrl: item.imageUrl || '', // Assuming 'imageUrl' field exists
        date: item.date,
        raw: item, // Store raw data
        metadata: item.metadata ? Object.fromEntries(item.metadata.map(meta => [meta.key, meta.value])) : {},
        // Add computed fields
        formattedDate: '', 
        optimizedImageUrl: '',
        thumbnailUrl: '',
      };
      // Enhance with computed fields (formattedDate, optimizedImageUrl, thumbnailUrl)
      return enhanceContentItem(normalizedItem); // Use existing enhance function
    });

    /** @type {PaginationInfo} */
    const pageInfo = {
      hasNextPage: result.data.contentItems.pageInfo?.hasNextPage || false,
      endCursor: result.data.contentItems.pageInfo?.endCursor || null
    };

    return {
      items: normalizedItems,
      pageInfo: pageInfo // Return the renamed structure
    };
  }
  
  // Return empty result on error or no data
  /** @type {PaginationInfo} */
  const emptyPageInfo = { hasNextPage: false, endCursor: null };
  return { items: [], pageInfo: emptyPageInfo }; 
}

/**
 * Fetch available content types dynamically from the WP backend.
 * TODO: Implement dynamic fetching from WP backend using a dedicated GraphQL query (Task 3.4.5)
 * @returns {Promise<string[]>} Array of content type names
 */
export async function fetchContentTypes() {
  console.warn('[Content Fetcher] Using fallback content types. TODO: Implement GraphQL query.');
  // No API call for now due to missing query
  // try {
  //   // Assume GET_CONTENT_TYPES_QUERY fetches nodes with a 'name' field
  //   const result = await executeQuery(
  //     GET_CONTENT_TYPES_QUERY, 
  //     {}, // No variables needed for this query usually
  //     true, // Use cache
  //     60 // Cache for 60 minutes
  //   );

  //   if (result?.data?.contentTypes?.nodes) {
  //     return result.data.contentTypes.nodes.map(node => node.name);
  //   }
    
  //   console.warn('[Content Fetcher] No content types returned from API. Falling back to defaults.');
  //   // Fallback to default types if API fails or returns no data
  //   return ['article', 'podcast', 'financial', 'social'];

  // } catch (error) {
  //   console.error('[Content Fetcher] Error fetching content types:', error);
  //   // Fallback to default types on error
  //   return ['article', 'podcast', 'financial', 'social'];
  // }
  
  // Return default types as fallback
  await new Promise(resolve => setTimeout(resolve, 10)); // Simulate tiny delay
  return ['article', 'podcast', 'financial', 'social'];
} 