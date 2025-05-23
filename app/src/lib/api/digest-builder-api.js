/**
 * Frontend API client for the ASAP Digest Builder REST API endpoints.
 * Handles interaction with the /asap/v1/digest-builder/ endpoints.
 */

// import { getAuthHeaders } from './auth-client'; // Assuming an auth helper exists

const API_BASE_URL = '/wp-json/asap/v1/digest-builder'; // Base URL for the digest builder endpoints

/**
 * Creates a new draft digest.
 *
 * @param {number} userId The ID of the user creating the digest.
 * @param {string} layoutTemplateId The identifier for the layout template.
 * @returns {Promise<object|null>} A promise resolving with the new digest data or null on error.
 */
export async function createDraftDigest(userId, layoutTemplateId) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/create-draft`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // ...headers, // Spread auth headers
            },
            body: JSON.stringify({
                user_id: userId,
                layout_template_id: layoutTemplateId,
            }),
        });

        if (!response.ok) {
            // TODO: Handle API errors (e.g., log, throw error, return specific error object)
            console.error('Failed to create draft digest:', response.status, response.statusText);
            return null;
        }

        return await response.json();
    } catch (error) {
        // TODO: Handle network or other unexpected errors
        console.error('Error creating draft digest:', error);
        return null;
    }
}

/**
 * Adds a module to an existing draft digest.
 *
 * @param {number} digestId The ID of the digest to add the module to.
 * @param {number} moduleCptId The CPT ID of the module to add.
 * @param {object} [placement] Optional placement data.
 * @param {number} [placement.grid_x] X coordinate on the grid.
 * @param {number} [placement.grid_y] Y coordinate on the grid.
 * @param {number} [placement.grid_width] Width on the grid.
 * @param {number} [placement.grid_height] Height on the grid.
 * @param {number} [placement.order_in_grid] Order within the grid cell.
 * @returns {Promise<object|null>} A promise resolving with the placement confirmation or null on error.
 */
export async function addModuleToDigest(digestId, moduleCptId, placement = {}) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/add-module`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // ...headers,
            },
            body: JSON.stringify({
                digest_id: digestId,
                module_cpt_id: moduleCptId,
                ...placement, // Spread placement data
            }),
        });

        if (!response.ok) {
            // TODO: Handle API errors
            console.error('Failed to add module to digest:', response.status, response.statusText);
            return null;
        }

        return await response.json();
    } catch (error) {
        // TODO: Handle network errors
        console.error('Error adding module to digest:', error);
        return null;
    }
}

/**
 * Updates the placement details of a module within a digest.
 *
 * @param {number} digestId The ID of the digest.
 * @param {number} placementId The ID of the placement record to update.
 * @param {object} updateData The placement data to update.
 * @param {number} [updateData.grid_x] New X coordinate.
 * @param {number} [updateData.grid_y] New Y coordinate.
 * @param {number} [updateData.grid_width] New width.
 * @param {number} [updateData.grid_height] New height.
 * @param {number} [updateData.order_in_grid] New order.
 * @returns {Promise<object|null>} A promise resolving with the update confirmation or null on error.
 */
export async function updateModulePlacement(digestId, placementId, updateData) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/${digestId}/update-placement/${placementId}`, {
            method: 'PUT', // Or POST, depending on API definition
            headers: {
                'Content-Type': 'application/json',
                // ...headers,
            },
            body: JSON.stringify(updateData),
        });

        if (!response.ok) {
            // TODO: Handle API errors
            console.error('Failed to update module placement:', response.status, response.statusText);
            return null;
        }

        // API might return success message or updated placement data
        return await response.json();
    } catch (error) {
        // TODO: Handle network errors
        console.error('Error updating module placement:', error);
        return null;
    }
}

/**
 * Removes a module placement from a digest.
 *
 * @param {number} digestId The ID of the digest.
 * @param {number} placementId The ID of the placement record to remove.
 * @returns {Promise<object|null>} A promise resolving with the deletion confirmation or null on error.
 */
export async function removeModulePlacement(digestId, placementId) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/${digestId}/remove-module/${placementId}`, {
            method: 'DELETE',
            headers: {
                // ...headers,
            },
            // DELETE typically doesn't have a body, but can depending on API design
        });

        if (!response.ok) {
            // TODO: Handle API errors
            console.error('Failed to remove module placement:', response.status, response.statusText);
            return null;
        }

        return await response.json();
    } catch (error) {
        // TODO: Handle network errors
        console.error('Error removing module placement:', error);
        return null;
    }
}

/**
 * Retrieves a specific digest with its module placements.
 *
 * @param {number} digestId The ID of the digest to retrieve.
 * @returns {Promise<object|null>} A promise resolving with the digest data or null if not found/error.
 */
export async function getDigest(digestId) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/${digestId}`, {
            method: 'GET',
            headers: {
                // ...headers,
            },
        });

        if (!response.ok) {
            // TODO: Handle API errors (e.g., 404 not found)
            console.error('Failed to get digest:', response.status, response.statusText);
            return null;
        }

        return await response.json();
    } catch (error) {
        // TODO: Handle network errors
        console.error('Error getting digest:', error);
        return null;
    }
}

/**
 * Retrieves a list of available layout templates.
 *
 * @returns {Promise<Array<object>>} A promise resolving with an array of layout templates or an empty array on error.
 */
export async function getLayoutTemplates() {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/layouts`, {
            method: 'GET',
            headers: {
                // ...headers,
            },
        });

        if (!response.ok) {
            console.error('Failed to get layout templates:', response.status, response.statusText);
            return [];
        }

        const result = await response.json();

        if (result.success && Array.isArray(result.data)) {
            return result.data;
        } else {
            console.error('API response for layout templates was not successful or data is not an array:', result);
            return [];
        }
    } catch (error) {
        console.error('Error getting layout templates:', error);
        return [];
    }
}

/**
 * Retrieves a list of available module CPTs.
 *
 * @returns {Promise<Array<object>>} A promise resolving with an array of module objects or an empty array on error.
 */
export async function getAvailableModules() {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/modules`, {
            method: 'GET',
            headers: {
                // ...headers,
            },
        });

        if (!response.ok) {
            console.error('Failed to get available modules:', response.status, response.statusText);
            return [];
        }

        const result = await response.json();

        if (result.success && Array.isArray(result.data)) {
            return result.data;
        } else {
            console.error('API response for available modules was not successful or data is not an array:', result);
            return [];
        }
    } catch (error) {
        console.error('Error getting available modules:', error);
        return [];
    }
}

/**
 * Retrieves a list of digests for a specific user.
 *
 * @param {number} userId The ID of the user.
 * @param {string|null} [status] Optional status to filter digests by.
 * @returns {Promise<Array<object>>} A promise resolving with an array of digest summaries.
 */
export async function getUsersDigests(userId, status = null) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const url = new URL(`${API_BASE_URL}/user/${userId}`);
        if (status) {
            url.searchParams.append('status', status);
        }

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                // ...headers,
            },
        });

        if (!response.ok) {
            // TODO: Handle API errors
            console.error('Failed to get user digests:', response.status, response.statusText);
            return []; // Return empty array on error for collections
        }

        return await response.json();
    } catch (error) {
        // TODO: Handle network errors
        console.error('Error getting user digests:', error);
        return []; // Return empty array on error
    }
}

/**
 * Publishes a draft digest.
 *
 * @param {number} digestId The ID of the digest to publish.
 * @returns {Promise<object|null>} A promise resolving with the publication confirmation or null on error.
 */
export async function publishDigest(digestId) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/${digestId}/publish`, {
            method: 'PUT', // Or POST
            headers: {
                // ...headers,
            },
            // PUT/POST typically has a body, but this might be a simple status update
        });

        if (!response.ok) {
            // TODO: Handle API errors
            console.error('Failed to publish digest:', response.status, response.statusText);
            return null;
        }

        return await response.json();
    } catch (error) {
        // TODO: Handle network errors
        console.error('Error publishing digest:', error);
        return null;
    }
}

/**
 * Deletes a digest.
 *
 * @param {number} digestId The ID of the digest to delete.
 * @returns {Promise<object|null>} A promise resolving with the deletion confirmation or null on error.
 */
export async function deleteDigest(digestId) {
    try {
        // const headers = getAuthHeaders(); // Include auth headers
        const response = await fetch(`${API_BASE_URL}/${digestId}`, {
            method: 'DELETE',
            headers: {
                // ...headers,
            },
        });

        if (!response.ok) {
            // TODO: Handle API errors
            console.error('Failed to delete digest:', response.status, response.statusText);
            return null;
        }

        return await response.json();
    } catch (error) {
        // TODO: Handle network errors
        console.error('Error deleting digest:', error);
        return null;
    }
}

// TODO: Add client functions for managing predefined layout templates if backend endpoints are added (CRUD for layouts) 