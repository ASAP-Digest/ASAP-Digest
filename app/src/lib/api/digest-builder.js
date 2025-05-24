/**
 * Digest Builder API Client
 * 
 * Provides functions to interact with the WordPress REST API
 * for digest creation and management.
 */

const API_BASE = '/wp-json/asap/v1/digest-builder';

/**
 * Fetch available layout templates
 * @returns {Promise<Object>} Response with layout templates
 */
export async function fetchLayoutTemplates() {
  try {
    const response = await fetch(`${API_BASE}/layouts`, {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch layout templates: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching layout templates:', error);
    throw error;
  }
}

/**
 * Fetch available modules for digest building
 * @returns {Promise<Object>} Response with available modules
 */
export async function fetchAvailableModules() {
  try {
    const response = await fetch(`${API_BASE}/modules`, {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch available modules: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching available modules:', error);
    throw error;
  }
}

/**
 * Create a new draft digest
 * @param {number} userId - User ID
 * @param {string} layoutTemplateId - Layout template ID
 * @returns {Promise<Object>} Response with new digest ID
 */
export async function createDraftDigest(userId, layoutTemplateId) {
  try {
    const response = await fetch(`${API_BASE}/create-draft`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        user_id: userId,
        layout_template_id: layoutTemplateId,
      }),
    });

    if (!response.ok) {
      throw new Error(`Failed to create draft digest: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error creating draft digest:', error);
    throw error;
  }
}

/**
 * Add a module to a digest
 * @param {number} digestId - Digest ID
 * @param {number} moduleCptId - Module CPT ID
 * @param {Object} placement - Optional placement data (grid_x, grid_y, grid_width, grid_height, order_in_grid)
 * @returns {Promise<Object>} Response with placement ID
 */
export async function addModuleToDigest(digestId, moduleCptId, placement = {}) {
  try {
    const response = await fetch(`${API_BASE}/add-module`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        digest_id: digestId,
        module_cpt_id: moduleCptId,
        ...placement,
      }),
    });

    if (!response.ok) {
      throw new Error(`Failed to add module to digest: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error adding module to digest:', error);
    throw error;
  }
}

/**
 * Update module placement in a digest
 * @param {number} digestId - Digest ID
 * @param {number} placementId - Placement ID
 * @param {Object} placement - Placement data to update
 * @returns {Promise<Object>} Response with update status
 */
export async function updateModulePlacement(digestId, placementId, placement) {
  try {
    const response = await fetch(`${API_BASE}/${digestId}/update-placement/${placementId}`, {
      method: 'PUT',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(placement),
    });

    if (!response.ok) {
      throw new Error(`Failed to update module placement: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error updating module placement:', error);
    throw error;
  }
}

/**
 * Remove a module from a digest
 * @param {number} digestId - Digest ID
 * @param {number} placementId - Placement ID
 * @returns {Promise<Object>} Response with removal status
 */
export async function removeModuleFromDigest(digestId, placementId) {
  try {
    const response = await fetch(`${API_BASE}/${digestId}/remove-module/${placementId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to remove module from digest: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error removing module from digest:', error);
    throw error;
  }
}

/**
 * Fetch a specific digest with its placements
 * @param {number} digestId - Digest ID
 * @returns {Promise<Object>} Response with digest data
 */
export async function fetchDigest(digestId) {
  try {
    const response = await fetch(`${API_BASE}/${digestId}`, {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch digest: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching digest:', error);
    throw error;
  }
}

/**
 * Fetch user's digests
 * @param {number} userId - User ID
 * @param {string} status - Optional status filter ('draft', 'published', 'archived')
 * @returns {Promise<Object>} Response with user's digests
 */
export async function fetchUserDigests(userId, status = null) {
  try {
    const url = new URL(`${API_BASE}/user/${userId}`, window.location.origin);
    if (status) {
      url.searchParams.append('status', status);
    }

    const response = await fetch(url, {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch user digests: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching user digests:', error);
    throw error;
  }
}

/**
 * Publish a draft digest
 * @param {number} digestId - Digest ID
 * @returns {Promise<Object>} Response with publish status
 */
export async function publishDigest(digestId) {
  try {
    const response = await fetch(`${API_BASE}/${digestId}/publish`, {
      method: 'PUT',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to publish digest: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error publishing digest:', error);
    throw error;
  }
}

/**
 * Delete a digest
 * @param {number} digestId - Digest ID
 * @returns {Promise<Object>} Response with deletion status
 */
export async function deleteDigest(digestId) {
  try {
    const response = await fetch(`${API_BASE}/${digestId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to delete digest: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error deleting digest:', error);
    throw error;
  }
} 