/**
 * LiveStore Sync API Endpoint
 * Handles synchronization of business objects with WordPress
 * Integrates seamlessly with V6 Auto-Login system
 * 
 * @fileoverview Business object sync (NOT user data - handled by auto-login)
 * @created 2025-05-28
 */

import { json } from '@sveltejs/kit';
import { log } from '$lib/utils/log.js';
import { getUserData } from '$lib/stores/user.js'; // Existing auto-login function

/**
 * @typedef {Object} LiveStoreSyncRequest
 * @property {string} table - Table name (digests, content, sources, etc.)
 * @property {string} operation - Operation type (create, update, delete)
 * @property {string} id - Record ID
 * @property {Object} data - Record data
 * @property {number} timestamp - Client timestamp
 */

/**
 * @typedef {Object} LiveStoreSyncResponse
 * @property {boolean} success - Sync success status
 * @property {Object} [data] - Synced data
 * @property {string} [error] - Error message if failed
 * @property {number} [wpPostId] - WordPress post ID if applicable
 * @property {string} [syncedAt] - Sync timestamp
 */

// Business objects we sync (NOT user data)
const SYNCABLE_TABLES = ['digests', 'content', 'sources', 'analytics', 'workflows'];

/**
 * Handle LiveStore sync POST request
 * Uses existing V6 auto-login for authentication
 * @param {Object} event - SvelteKit request event
 * @returns {Promise<Response>} JSON response
 */
export async function POST({ request }) {
  try {
    // Use existing auto-login authentication
    const currentUser = await getUserData();
    
    if (!currentUser) {
      return json({
        success: false,
        error: 'Authentication required - please log in via WordPress'
      }, { status: 401 });
    }
    
    log(`[LiveStore Sync] Processing sync request for user: ${currentUser.email}`, 'info');
    
    /** @type {LiveStoreSyncRequest} */
    const syncRequest = await request.json();
    
    const { table, operation, id, data, timestamp } = syncRequest;
    
    if (!table || !operation || !id) {
      return json({
        success: false,
        error: 'Invalid sync request: missing table, operation, or id'
      }, { status: 400 });
    }
    
    // Validate table
    if (!SYNCABLE_TABLES.includes(table)) {
      return json({
        success: false,
        error: `Table '${table}' not supported. User data is synced via auto-login.`
      }, { status: 400 });
    }
    
    // Validate user permissions
    if (!canUserModifyRecord(table, data, currentUser)) {
      return json({
        success: false,
        error: 'Insufficient permissions to modify this record'
      }, { status: 403 });
    }
    
    // Process sync operation
    let result;
    switch (operation) {
      case 'create':
        result = await createRecord(table, id, data, currentUser);
        break;
      case 'update':
        result = await updateRecord(table, id, data, currentUser);
        break;
      case 'delete':
        result = await deleteRecord(table, id, currentUser);
        break;
      default:
        return json({
          success: false,
          error: `Operation '${operation}' not supported`
        }, { status: 400 });
    }
    
    log(`[LiveStore Sync] ${operation} operation completed for ${table}:${id}`, 'info');
    
    /** @type {LiveStoreSyncResponse} */
    const response = {
      success: true,
      data: result.data,
      wpPostId: result.wpPostId,
      syncedAt: new Date().toISOString()
    };
    
    return json(response);
    
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[LiveStore Sync] Error processing sync request: ${errorMessage}`, 'error');
    console.error('[LiveStore Sync] Full error:', error);
    
    return json({
      success: false,
      error: 'Internal server error during sync'
    }, { status: 500 });
  }
}

/**
 * Check if user can modify a record
 * Uses auto-login user roles and ownership
 * @param {string} table - Table name
 * @param {Object} data - Record data
 * @param {Object} currentUser - Current user from auto-login
 * @returns {boolean} Whether user can modify
 */
function canUserModifyRecord(table, data, currentUser) {
  // Admins can modify anything
  if (currentUser.hasRole && currentUser.hasRole('administrator')) {
    return true;
  }
  
  // Editors can modify most content
  if (currentUser.hasRole && currentUser.hasRole('editor')) {
    return true;
  }
  
  // Users can modify their own records
  if (data.authorId === currentUser.id || data.userId === currentUser.id) {
    return true;
  }
  
  // Check WordPress user ID as well
  if (data.wpUserId === currentUser.wp_user_id) {
    return true;
  }
  
  // Table-specific permissions
  switch (table) {
    case 'analytics':
      // Analytics can be created by anyone for their own actions
      return data.userId === currentUser.id;
    case 'content':
      // Content can be moderated by editors
      return currentUser.hasRole && (
        currentUser.hasRole('editor') || 
        currentUser.hasRole('content_moderator')
      );
    default:
      return false;
  }
}

/**
 * Create a new record and sync to WordPress if applicable
 * @param {string} table - Table name
 * @param {string} id - Record ID
 * @param {Object} data - Record data
 * @param {Object} currentUser - Current user
 * @returns {Promise<Object>} Creation result
 */
async function createRecord(table, id, data, currentUser) {
  // Add metadata
  const recordData = {
    ...data,
    id,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    createdBy: currentUser.id,
    lastModifiedBy: currentUser.id
  };
  
  // Sync to WordPress if applicable
  let wpPostId = null;
  if (shouldSyncToWordPress(table)) {
    wpPostId = await syncToWordPress(table, recordData, 'create', currentUser);
    if (wpPostId) {
      recordData.wpPostId = wpPostId;
      recordData.wpSynced = true;
      recordData.lastWpSync = new Date().toISOString();
    }
  }
  
  return {
    data: recordData,
    wpPostId
  };
}

/**
 * Update an existing record and sync to WordPress if applicable
 * @param {string} table - Table name
 * @param {string} id - Record ID
 * @param {Object} data - Updated data
 * @param {Object} currentUser - Current user
 * @returns {Promise<Object>} Update result
 */
async function updateRecord(table, id, data, currentUser) {
  // Add metadata
  const recordData = {
    ...data,
    id,
    updatedAt: new Date().toISOString(),
    lastModifiedBy: currentUser.id
  };
  
  // Sync to WordPress if applicable
  let wpPostId = data.wpPostId;
  if (shouldSyncToWordPress(table)) {
    wpPostId = await syncToWordPress(table, recordData, 'update', currentUser);
    if (wpPostId) {
      recordData.wpPostId = wpPostId;
      recordData.wpSynced = true;
      recordData.lastWpSync = new Date().toISOString();
    }
  }
  
  return {
    data: recordData,
    wpPostId
  };
}

/**
 * Delete a record and sync to WordPress if applicable
 * @param {string} table - Table name
 * @param {string} id - Record ID
 * @param {Object} currentUser - Current user
 * @returns {Promise<Object>} Deletion result
 */
async function deleteRecord(table, id, currentUser) {
  // For WordPress-synced records, we might want to trash instead of delete
  if (shouldSyncToWordPress(table)) {
    await syncToWordPress(table, { id }, 'delete', currentUser);
  }
  
  return {
    data: { id, deletedAt: new Date().toISOString() }
  };
}

/**
 * Check if table should sync to WordPress
 * @param {string} table - Table name
 * @returns {boolean} Whether to sync
 */
function shouldSyncToWordPress(table) {
  return ['digests', 'content', 'sources'].includes(table);
}

/**
 * Sync record to WordPress using existing auto-login infrastructure
 * @param {string} table - Table name
 * @param {Object} data - Record data
 * @param {string} operation - Operation type
 * @param {Object} currentUser - Current user
 * @returns {Promise<number|null>} WordPress post ID
 */
async function syncToWordPress(table, data, operation, currentUser) {
  try {
    // Use existing WordPress REST API with auto-login authentication
    const wpApiUrl = process.env.WP_API_URL || 'https://asapdigest.local';
    const endpoint = `${wpApiUrl}/wp-json/asap/v1/sync-business-object`;
    
    // Ensure headers are properly typed
    const headers = {
      'Content-Type': 'application/json',
      'X-ASAP-Sync-Secret': process.env.BETTER_AUTH_SECRET || '',
      'X-ASAP-User-ID': currentUser.wp_user_id ? currentUser.wp_user_id.toString() : ''
    };
    
    const response = await fetch(endpoint, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        table,
        operation,
        data,
        userId: currentUser.wp_user_id
      })
    });
    
    if (!response.ok) {
      log(`[LiveStore Sync] WordPress sync failed: ${response.statusText}`, 'error');
      return null;
    }
    
    const result = await response.json();
    
    if (result.success && result.wpPostId) {
      log(`[LiveStore Sync] Successfully synced to WordPress: ${result.wpPostId}`, 'debug');
      return result.wpPostId;
    }
    
    return null;
    
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[LiveStore Sync] Error syncing to WordPress: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Handle LiveStore sync GET request - get sync status
 * @param {Object} event - SvelteKit request event
 * @returns {Promise<Response>} JSON response
 */
export async function GET({ url }) {
  try {
    // Use existing auto-login authentication
    const currentUser = await getUserData();
    
    if (!currentUser) {
      return json({
        success: false,
        error: 'Authentication required - please log in via WordPress'
      }, { status: 401 });
    }
    
    const table = url.searchParams.get('table');
    const since = parseInt(url.searchParams.get('since') || '0');
    
    return json({
      success: true,
      syncStatus: {
        authenticated: true,
        user: currentUser.email,
        supportedTables: SYNCABLE_TABLES,
        lastSync: new Date().toISOString()
      }
    });
    
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[LiveStore Sync] Error getting sync status: ${errorMessage}`, 'error');
    
    return json({
      success: false,
      error: 'Internal server error'
    }, { status: 500 });
  }
} 