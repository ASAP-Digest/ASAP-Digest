/**
 * Digest Builder API
 * Handles all API calls for the new digest creation flow
 */

import { getApiUrl } from '$lib/utils/api-config.js';
import { getSession } from '$lib/auth-client.js';

const API_BASE = getApiUrl();

/**
 * Get the current Better Auth session token
 * @returns {Promise<string|null>} Session token or null if not found
 */
async function getSessionToken() {
  try {
    const sessionData = await getSession();
    // Better Auth handles tokens via HTTP-only cookies, so we may not have direct access
    // Check if session data has the token in the expected structure
    if (sessionData && typeof sessionData === 'object' && 'data' in sessionData) {
      // @ts-ignore - Better Auth session structure may vary
      return sessionData.data?.session?.token || null;
    }
    // If no token is directly accessible, return null and rely on credentials: 'include'
    return null;
  } catch (error) {
    console.error('Error getting session token:', error);
    return null;
  }
}

/**
 * Get headers for API requests including authentication
 * @returns {Promise<Object>} Headers object
 */
async function getApiHeaders() {
  const headers = {
    'Content-Type': 'application/json',
  };
  
  const sessionToken = await getSessionToken();
  if (sessionToken) {
    headers['Authorization'] = `Bearer ${sessionToken}`;
  }
  
  return headers;
}

/**
 * Fetch available layout templates
 */
export async function fetchLayoutTemplates() {
  try {
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/layouts`, {
      method: 'GET',
      headers: await getApiHeaders(),
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
    console.error('Error fetching layout templates:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Create a new draft digest
 * @param {number} userId - WordPress user ID
 * @param {string} layoutTemplateId - Layout template ID
 * @returns {Promise<Object>} Response object
 */
export async function createDraftDigest(userId, layoutTemplateId) {
  try {
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/create-draft`, {
      method: 'POST',
      headers: await getApiHeaders(),
      credentials: 'include',
      body: JSON.stringify({
        user_id: userId,
        layout_template_id: layoutTemplateId,
        status: 'draft'
      })
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return {
      success: true,
      data: data
    };
  } catch (error) {
    console.error('Error creating draft digest:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Add a module to a digest
 */
export async function addModuleToDigest(digestId, moduleData, gridPosition) {
  try {
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/add-module`, {
      method: 'POST',
      headers: await getApiHeaders(),
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
 */
export async function fetchDigest(digestId) {
  try {
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/${digestId}`, {
      method: 'GET',
      headers: await getApiHeaders(),
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
    console.error('Error fetching digest:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Fetch user's digests
 * @param {number} userId - WordPress user ID
 * @param {string} status - Digest status (draft, published, etc.)
 * @returns {Promise<Object>} Response object
 */
export async function fetchUserDigests(userId, status = 'draft') {
  try {
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/user/${userId}?${params}`, {
      method: 'GET',
      headers: await getApiHeaders(),
      credentials: 'include'
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return {
      success: true,
      data: data
    };
  } catch (error) {
    console.error('Error fetching user digests:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Update digest status (draft -> published, etc.)
 */
export async function updateDigestStatus(digestId, status) {
  try {
    let response;
    
    // For now, only support publishing. Other status updates can be added later.
    if (status === 'published') {
      response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/${digestId}/publish`, {
        method: 'PUT',
        headers: await getApiHeaders(),
        credentials: 'include'
      });
    } else {
      throw new Error(`Status update to '${status}' is not yet supported`);
    }

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
 */
export async function removeModuleFromDigest(digestId, placementId) {
  try {
    const response = await fetch(`${API_BASE}/wp-json/asap/v1/digest-builder/${digestId}/remove-module/${placementId}`, {
      method: 'DELETE',
      headers: await getApiHeaders(),
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
 * Save digest layout changes
 * TODO: Implement backend endpoint for layout saving
 */
export async function saveDigestLayout(digestId, layoutData) {
  // Placeholder - backend endpoint not yet implemented
  console.warn('saveDigestLayout: Backend endpoint not yet implemented');
  return {
    success: true,
    data: { message: 'Layout saving not yet implemented' }
  };
} 