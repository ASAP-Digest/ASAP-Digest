/**
 * WordPress User Synchronization Module
 * Handles synchronization of user data from SvelteKit to WordPress
 * @module wp-sync
 * @created 04.28.25 | 07:45 PM PDT
 */

import { env } from '$env/dynamic/private';
import { dev } from '$app/environment';

/**
 * @typedef {object} SyncOptions
 * @property {number} [maxRetries=3] Maximum number of retry attempts
 * @property {number} [baseDelay=1000] Base delay in milliseconds for exponential backoff
 * @property {boolean} [throwOnError=false] Whether to throw errors or return error object
 * @property {string[]} [fieldsToSync] Specific fields to sync (undefined means all)
 */

/**
 * @typedef {object} SyncResult
 * @property {boolean} success Whether the sync was successful
 * @property {string} message Status message
 * @property {object} [data] Additional data if successful
 * @property {Error} [error] Error object if unsuccessful
 * @property {number} [retryCount] Number of retries attempted
 * @property {string} [timestamp] ISO timestamp of the sync attempt
 */

/**
 * @typedef {object} UserData
 * @property {string} id SvelteKit/Better Auth user ID
 * @property {string} email User's email address
 * @property {string} [displayName] User's display name
 * @property {string[]} [roles] User's roles
 * @property {object} [metadata] Additional user metadata
 * @property {Date} [updatedAt] Last update timestamp
 */

// Constants
const DEFAULT_MAX_RETRIES = 3;
const DEFAULT_BASE_DELAY = 1000; // 1 second
const WP_SYNC_ENDPOINT = dev 
    ? 'https://asapdigest.local/wp-json/asap/v1/user-sync'
    : 'https://asapdigest.com/wp-json/asap/v1/user-sync';

/**
 * Calculate delay for exponential backoff
 * @param {number} retryCount Current retry attempt number
 * @param {number} baseDelay Base delay in milliseconds
 * @returns {number} Delay in milliseconds
 */
function calculateBackoffDelay(retryCount, baseDelay) {
    return Math.min(
        baseDelay * Math.pow(2, retryCount) + Math.random() * 1000,
        30000 // Max 30 seconds
    );
}

/**
 * Validate user data before sync
 * @param {UserData} userData User data to validate
 * @returns {{ valid: boolean, errors: string[] }} Validation result
 */
function validateUserData(userData) {
    const errors = [];
    
    if (!userData.id) errors.push('User ID is required');
    if (!userData.email) errors.push('Email is required');
    if (userData.roles && !Array.isArray(userData.roles)) {
        errors.push('Roles must be an array');
    }
    
    return {
        valid: errors.length === 0,
        errors
    };
}

/**
 * Prepare headers for WordPress API request
 * @returns {Headers} Request headers
 */
function prepareHeaders() {
    const headers = new Headers({
        'Content-Type': 'application/json',
        'X-WP-Sync-Source': 'sveltekit',
        'X-WP-Sync-Version': '2.0'
    });

    // Add sync secret if configured
    const syncSecret = env.BETTER_AUTH_SECRET;
    if (syncSecret) {
        headers.set('X-WP-Sync-Secret', syncSecret);
    }

    return headers;
}

/**
 * Log sync activity with timestamp and details
 * @param {string} message Log message
 * @param {'info' | 'error' | 'warn' | 'debug'} [level='info'] Log level
 * @param {object} [details] Additional details to log
 */
function logSync(message, level = 'info', details = {}) {
    const timestamp = new Date().toISOString();
    const logData = {
        timestamp,
        message,
        ...details
    };

    switch (level) {
        case 'error':
            console.error('[WP Sync]', logData);
            break;
        case 'warn':
            console.warn('[WP Sync]', logData);
            break;
        case 'debug':
            console.debug('[WP Sync]', logData);
            break;
        default:
            console.log('[WP Sync]', logData);
    }
}

/**
 * Synchronize user data to WordPress with retry mechanism
 * @param {UserData} userData User data to synchronize
 * @param {SyncOptions} [options] Sync options
 * @returns {Promise<SyncResult>} Sync result
 */
export async function syncUserToWordPress(userData, options = {}) {
    const {
        maxRetries = DEFAULT_MAX_RETRIES,
        baseDelay = DEFAULT_BASE_DELAY,
        throwOnError = false,
        fieldsToSync
    } = options;

    // Validate user data
    const validation = validateUserData(userData);
    if (!validation.valid) {
        const error = new Error(`Invalid user data: ${validation.errors.join(', ')}`);
        logSync('User data validation failed', 'error', { errors: validation.errors });
        if (throwOnError) throw error;
        return {
            success: false,
            message: 'Validation failed',
            error,
            timestamp: new Date().toISOString()
        };
    }

    let retryCount = 0;
    let lastError = null;

    while (retryCount <= maxRetries) {
        try {
            // Prepare sync payload
            const payload = {
                skUserId: userData.id,
                email: userData.email,
                displayName: userData.displayName,
                roles: userData.roles,
                metadata: userData.metadata,
                updatedAt: userData.updatedAt?.toISOString(),
                fieldsToSync
            };

            logSync('Attempting sync', 'debug', { 
                retryCount, 
                userId: userData.id 
            });

            // Make request to WordPress
            const response = await fetch(WP_SYNC_ENDPOINT, {
                method: 'POST',
                headers: prepareHeaders(),
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            logSync('Sync successful', 'info', {
                userId: userData.id,
                retryCount
            });

            return {
                success: true,
                message: 'Sync successful',
                data: result,
                retryCount,
                timestamp: new Date().toISOString()
            };

        } catch (/** @type {any} */ error) {
            const errorMessage = error instanceof Error ? error.message : String(error);
            lastError = error instanceof Error ? error : new Error(errorMessage);
            logSync('Sync attempt failed', 'error', {
                error: errorMessage,
                retryCount,
                userId: userData.id
            });

            if (retryCount < maxRetries) {
                const delay = calculateBackoffDelay(retryCount, baseDelay);
                await new Promise(resolve => setTimeout(resolve, delay));
                retryCount++;
            } else {
                break;
            }
        }
    }

    const finalErrorMessage = lastError instanceof Error ? lastError.message : 'Unknown error during sync';
    const finalError = new Error(`Sync failed after ${retryCount} retries: ${finalErrorMessage}`);
    if (throwOnError) throw finalError;

    return {
        success: false,
        message: `Failed after ${retryCount} retries`,
        error: finalError,
        retryCount,
        timestamp: new Date().toISOString()
    };
}

/**
 * Batch sync multiple users to WordPress
 * @param {UserData[]} users Array of user data to sync
 * @param {SyncOptions} [options] Sync options
 * @returns {Promise<Array<SyncResult>>} Array of sync results
 */
export async function batchSyncUsersToWordPress(users, options = {}) {
    const results = await Promise.all(
        users.map(user => syncUserToWordPress(user, {
            ...options,
            throwOnError: false // Never throw in batch operations
        }))
    );

    const summary = {
        total: results.length,
        successful: results.filter(r => r.success).length,
        failed: results.filter(r => !r.success).length
    };

    logSync('Batch sync completed', 'info', summary);

    return results;
}

/**
 * Check if WordPress sync is configured and available
 * @returns {Promise<boolean>} Whether sync is available
 */
export async function isWordPressSyncAvailable() {
    try {
        const response = await fetch(`${WP_SYNC_ENDPOINT}/status`, {
            method: 'GET',
            headers: prepareHeaders()
        });

        return response.ok;
    } catch (/** @type {any} */ error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        logSync('Sync availability check failed', 'error', { error: errorMessage });
        return false;
    }
} 