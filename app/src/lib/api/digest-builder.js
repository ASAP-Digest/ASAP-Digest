/**
 * Digest Builder API
 * Handles all API calls for the new digest creation flow
 * Now uses GraphQL as the primary interface, with REST fallbacks for operations not yet implemented in GraphQL
 * 
 * MIGRATION NOTE (December 2024):
 * This file was updated to use GraphQL instead of REST API proxy routes to align with the original
 * architecture vision specified in asap-digest-stack.mdc. The proxy pattern has been repurposed
 * into a reusable utility at $lib/utils/api-proxy.js for legitimate use cases like webhook
 * forwarding and external API integrations.
 */

import { getApiUrl } from '$lib/utils/api-config.js';
import { browser } from '$app/environment';
import { authStore } from '$lib/utils/auth-persistence.js';

// Import GraphQL implementations
import {
  fetchLayoutTemplates as fetchLayoutTemplatesGraphQL,
  createDraftDigest as createDraftDigestGraphQL,
  fetchUserDigests as fetchUserDigestsGraphQL,
  fetchDigest as fetchDigestGraphQL,
  updateDigestStatus as updateDigestStatusGraphQL,
  addModuleToDigest as addModuleToDigestGraphQL,
  removeModuleFromDigest as removeModuleFromDigestGraphQL,
  saveDigestLayout as saveDigestLayoutGraphQL
} from './digest-builder-graphql.js';

const API_BASE = getApiUrl();

/**
 * Get Better Auth session token using multiple fallback methods
 * @returns {Promise<string|null>} Session token or null if not found
 */
async function getBetterAuthToken() {
  if (!browser) return null;
  
  console.log('[Digest Builder API] Attempting to get session token...');
  console.log('[Digest Builder API] Available cookies:', document.cookie);
  console.log('[Digest Builder API] Current URL:', window.location.href);
  console.log('[Digest Builder API] Cookie domain:', window.location.hostname);
  
  // Method 1: Try to get from auth store first (most reliable for cross-domain)
  try {
    const authData = authStore.get();
    console.log('[Digest Builder API] Auth store data:', authData);
    if (authData && authData.sessionToken) {
      console.log('[Digest Builder API] Found session token from auth store');
      return authData.sessionToken;
    } else {
      console.log('[Digest Builder API] No session token in auth store, authData keys:', authData ? Object.keys(authData) : 'null');
    }
  } catch (error) {
    console.warn('[Digest Builder API] Failed to get session token from auth store:', error);
  }
  
  // Method 2: Try WordPress session check endpoint to get/create session token
  try {
    const response = await fetch('/api/auth/check-wp-session', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    if (response.ok) {
      const wpSessionData = await response.json();
      console.log('[Digest Builder API] WordPress session check response:', wpSessionData);
      if (wpSessionData && wpSessionData.sessionToken) {
        console.log('[Digest Builder API] Found session token from WordPress session check');
        
        // Store the session token in auth store for future use
        try {
          const currentAuthData = authStore.get();
          if (currentAuthData) {
            authStore.set({
              ...currentAuthData,
              sessionToken: wpSessionData.sessionToken
            });
            console.log('[Digest Builder API] Stored session token in auth store');
          }
        } catch (storeError) {
          console.warn('[Digest Builder API] Failed to store session token in auth store:', storeError);
        }
        
        return wpSessionData.sessionToken;
      }
    } else {
      console.log('[Digest Builder API] WordPress session check failed with status:', response.status);
    }
  } catch (error) {
    console.warn('[Digest Builder API] Failed to check WordPress session:', error);
  }
  
  // Method 3: Try to get from cookies (if accessible)
  const cookies = document.cookie.split(';');
  for (let cookie of cookies) {
    const [name, value] = cookie.trim().split('=');
    if (name === 'better_auth_session') {
      console.log('[Digest Builder API] Found session token in cookies');
      return value;
    }
  }
  
  // Method 4: Try to get from session endpoint
  try {
    const response = await fetch('/api/auth/session', {
      method: 'GET',
      credentials: 'include'
    });
    
    if (response.ok) {
      const sessionData = await response.json();
      console.log('[Digest Builder API] Session endpoint response:', sessionData);
      if (sessionData && sessionData.session && sessionData.session.sessionToken) {
        console.log('[Digest Builder API] Found session token from session endpoint');
        return sessionData.session.sessionToken;
      } else {
        console.log('[Digest Builder API] No session token in session endpoint response');
      }
    } else {
      console.log('[Digest Builder API] Session endpoint failed with status:', response.status);
    }
  } catch (error) {
    console.warn('[Digest Builder API] Failed to fetch session token from endpoint:', error);
  }
  
  console.warn('[Digest Builder API] No session token found via any method');
  return null;
}

/**
 * Get headers for API requests with Better Auth token
 * @returns {Promise<Record<string, string>>} Headers object
 */
async function getApiHeaders() {
  /** @type {Record<string, string>} */
  const headers = {
    'Content-Type': 'application/json',
  };
  
  // Add Better Auth token if available
  const token = await getBetterAuthToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    console.log('[Digest Builder API] Added Authorization header with token:', token.substring(0, 10) + '...');
  } else {
    console.warn('[Digest Builder API] No Better Auth session token found - API calls may fail');
  }
  
  return headers;
}

/**
 * @typedef {Object} ApiResponse
 * @property {boolean} success - Whether the operation was successful
 * @property {any} [data] - Response data if successful
 * @property {string} [error] - Error message if unsuccessful
 */

/**
 * @typedef {Object} LayoutTemplate
 * @property {string} id - Template ID
 * @property {string} name - Template name
 * @property {string} description - Template description
 * @property {Object} config - Template configuration
 */

/**
 * @typedef {Object} ModuleData
 * @property {string|number} id - Module ID
 * @property {string} type - Module type
 * @property {string} title - Module title
 * @property {string} excerpt - Module excerpt
 * @property {string} [image] - Module image URL
 * @property {string} [source] - Module source
 * @property {string} [publishedAt] - Publication date
 */

/**
 * @typedef {Object} GridPosition
 * @property {number} x - Grid X position
 * @property {number} y - Grid Y position
 * @property {number} w - Grid width
 * @property {number} h - Grid height
 */

/**
 * Fetch available layout templates
 * @returns {Promise<ApiResponse>} Response with layout templates data
 */
export async function fetchLayoutTemplates() {
  console.log('[Digest Builder API] Using GraphQL implementation for fetchLayoutTemplates');
  return await fetchLayoutTemplatesGraphQL();
}

/**
 * Create a new draft digest
 * @param {number} userId - WordPress user ID
 * @param {string} layoutTemplateId - Layout template ID
 * @returns {Promise<ApiResponse>} Response object
 */
export async function createDraftDigest(userId, layoutTemplateId) {
  console.log('[Digest Builder API] Using GraphQL implementation for createDraftDigest');
  return await createDraftDigestGraphQL(userId, layoutTemplateId);
}

/**
 * Add a module to a digest
 * @param {string|number} digestId - Digest ID
 * @param {ModuleData} moduleData - Module data
 * @param {GridPosition} gridPosition - Grid position data
 * @returns {Promise<ApiResponse>} Response object
 */
export async function addModuleToDigest(digestId, moduleData, gridPosition) {
  try {
    const headers = await getApiHeaders();
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/add-module`, {
      method: 'POST',
      headers: headers,
      credentials: 'include',
      body: JSON.stringify({
        digest_id: digestId,
        module_cpt_id: moduleData.id,
        module_type: moduleData.type,
        grid_x: gridPosition.x,
        grid_y: gridPosition.y,
        grid_width: gridPosition.w,
        grid_height: gridPosition.h,
        module_data: {
          title: moduleData.title,
          excerpt: moduleData.excerpt,
          image: moduleData.image,
          source: moduleData.source,
          publishedAt: moduleData.publishedAt
        }
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return {
      success: true,
      data: data
    };
  } catch (error) {
    console.error('Error adding module to digest:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Fetch a specific digest with its modules
 * @param {string|number} digestId - Digest ID
 * @returns {Promise<ApiResponse>} Response object
 */
export async function fetchDigest(digestId) {
  console.log('[Digest Builder API] Using GraphQL implementation for fetchDigest');
  return await fetchDigestGraphQL(digestId);
}

/**
 * Fetch user's digests
 * @param {number} userId - WordPress user ID (optional, will use authenticated user)
 * @param {string} [status='draft'] - Digest status filter (draft, published, etc.)
 * @returns {Promise<ApiResponse>} Response object
 */
export async function fetchUserDigests(userId, status = 'draft') {
  console.log('[Digest Builder API] Using GraphQL implementation for fetchUserDigests');
  return await fetchUserDigestsGraphQL(userId, status);
}

/**
 * Update digest status (draft -> published, etc.)
 * @param {string|number} digestId - Digest ID
 * @param {string} status - New status
 * @returns {Promise<ApiResponse>} Response object
 */
export async function updateDigestStatus(digestId, status) {
  try {
    const headers = await getApiHeaders();
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/${digestId}/status`, {
        method: 'PUT',
      headers: headers,
      credentials: 'include',
      body: JSON.stringify({
        status: status
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return {
      success: true,
      data: data
    };
  } catch (error) {
    console.error('Error updating digest status:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Remove a module from a digest
 * @param {string|number} digestId - Digest ID
 * @param {string|number} placementId - Module placement ID
 * @returns {Promise<ApiResponse>} Response object
 */
export async function removeModuleFromDigest(digestId, placementId) {
  try {
    const headers = await getApiHeaders();
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/${digestId}/module/${placementId}`, {
      method: 'DELETE',
      headers: headers,
      credentials: 'include'
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return {
      success: true,
      data: data
    };
  } catch (error) {
    console.error('Error removing module from digest:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Save digest layout data
 * @param {string|number} digestId - Digest ID
 * @param {Object} layoutData - GridStack layout data
 * @returns {Promise<ApiResponse>} Response object
 */
export async function saveDigestLayout(digestId, layoutData) {
  try {
    const headers = await getApiHeaders();
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/${digestId}/layout`, {
      method: 'PUT',
      headers: headers,
      credentials: 'include',
      body: JSON.stringify({
        layout_data: layoutData
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
  return {
    success: true,
      data: data
    };
  } catch (error) {
    console.error('Error saving digest layout:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
} 