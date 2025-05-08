/**
 * Enhanced Content Fetcher Service for the NewItemsSelector component
 * Extends basic content-service with caching, debouncing, and better error handling
 * @module api/content-fetcher
 */

import { browser } from '$app/environment';
import { 
  fetchContentItems, 
  searchMultipleContentTypes,
  getContentTypeDetails
} from './content-service.js';
import { getOptimalImageUrl } from '../utils/image-utils.js';

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
 * @property {string} type - Content type (article, podcast, etc.)
 * @property {string} title - Content title
 * @property {string} [excerpt] - Brief excerpt or summary
 * @property {string} [source] - Source of the content
 * @property {string} [imageUrl] - URL to the featured image
 * @property {string} [date] - Publication date
 * @property {string} [formattedDate] - Formatted date string
 * @property {string} [optimizedImageUrl] - Optimized version of the image
 * @property {string} [thumbnailUrl] - Thumbnail version of the image
 * @property {Object} [meta] - Additional metadata specific to content type
 */

/**
 * @typedef {BaseContentItem & EnhancedContentItem} ContentItem
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
 * @param {string} contentType - Type of content to fetch
 * @param {QueryParams} params - Query parameters
 * @param {Object} options - Additional options
 * @param {boolean} [options.bypassCache=false] - Whether to bypass cache
 * @param {boolean} [options.updateCache=true] - Whether to update cache
 * @returns {Promise<{items: ContentItem[], pagination: PaginationInfo}>} - Content items and pagination
 */
export async function fetchCachedContent(contentType, params, options = {}) {
  const { bypassCache = false, updateCache = true } = options;
  
  // Generate cache key
  const cacheKey = getCacheKey(contentType, params);
  
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
    const result = await fetchContentItems(contentType, params);
    
    // Process and enhance items
    const enhancedItems = result.items.map(item => enhanceContentItem(item));
    
    // Update cache if enabled
    if (updateCache) {
      setCache(cacheKey, enhancedItems, result.pagination);
    }
    
    return {
      items: enhancedItems,
      pagination: result.pagination
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
  const enhanced = { 
    ...item,
    optimizedImageUrl: item.imageUrl || '',
    thumbnailUrl: item.imageUrl || '',
    formattedDate: item.date || ''
  };
  
  // Optimize image URL if present
  if (enhanced.imageUrl) {
    enhanced.optimizedImageUrl = getOptimalImageUrl(
      { sourceUrl: enhanced.imageUrl },
      { width: 300, height: 200 }
    );
    
    enhanced.thumbnailUrl = getOptimalImageUrl(
      { sourceUrl: enhanced.imageUrl },
      { width: 80, height: 80 }
    );
  }
  
  // Format date for display if present
  if (enhanced.date) {
    try {
      const dateObj = new Date(enhanced.date);
      enhanced.formattedDate = dateObj.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    } catch (e) {
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
    // Fetch from API (this is a simplified approach - ideally we'd have a dedicated endpoint)
    const result = await fetchContentItems(contentType, { limit: 100 });
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
 * Default export - creates a content manager with both fetching and selection capabilities
 */
export function createContentManager() {
  const selectionManager = createSelectedItemsManager();
  
  return {
    ...selectionManager,
    fetchContent: fetchCachedContent,
    searchContent: searchContentWithDebounce,
    getItemById: getContentItemById,
    clearCache: clearContentCache
  };
} 