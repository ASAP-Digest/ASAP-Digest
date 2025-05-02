/**
 * @file Utility functions for server-side authentication and user synchronization.
 * @created 2025-07-29
 * @version 1.0.0
 */

import { error } from '@sveltejs/kit';
import { log } from '$lib/utils/log';
// Import adapter functions directly from adapter-functions.js
import { 
    getUserByWpIdFn, 
    createUserFn,
    createSessionFn 
} from './adapter-functions';

/**
 * @typedef {Object} UserMetadata
 * @property {number} [wp_user_id] - WordPress user ID
 * @property {string[]} [roles] - User roles array
 */

/**
 * @typedef {Object} User
 * @property {string} id - Better Auth User ID (UUID)
 * @property {string} email - User email
 * @property {string} [username] - Optional username
 * @property {string} [name] - Optional display name
 * @property {string} displayName - Primary display name 
 * @property {UserMetadata} [metadata] - User metadata
 */

/**
 * @typedef {Object} Session
 * @property {string} id - Session ID (Primary Key, usually UUID)
 * @property {string} userId - Better Auth User ID
 * @property {string} token - Session token
 * @property {Date} expiresAt - Session expiry date
 * @property {Date} createdAt - Session creation date
 * @property {Date} updatedAt - Session update date
 */

/**
 * Synchronizes a WordPress user with the Better Auth system and creates a session.
 * @param {Object} wpUserData The WordPress user data from the validation endpoint
 * @param {number} wpUserData.wpUserId WordPress user ID
 * @param {string} wpUserData.email User email address
 * @param {string} wpUserData.username Username
 * @param {string} wpUserData.displayName Display name
 * @param {string[]} wpUserData.roles User roles array
 * @param {string} wpUserData.firstName First name
 * @param {string} wpUserData.lastName Last name
 * @param {Object} wpUserData.metadata Additional metadata
 * @returns {Promise<{token: string, userId: string}|null>} Session data if successful, null if failed
 */
export async function syncWordPressUserAndCreateSession(wpUserData) {
    try {
        log(`[auth-utils-fixed] syncWordPressUserAndCreateSession called for WP user: ${wpUserData.email}`);

        // Validate required data
        if (!wpUserData || !wpUserData.wpUserId || !wpUserData.email) {
            log('[auth-utils-fixed] Invalid WP user data', 'error');
            return null;
        }

        // Check if user already exists in Better Auth by WP user ID
        let user = null;
        try {
            user = await getUserByWpIdFn(Number(wpUserData.wpUserId));
            log(`[auth-utils-fixed] User lookup by WP ID ${wpUserData.wpUserId}: ${user ? 'Found' : 'Not found'}`);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : String(err);
            log(`[auth-utils-fixed] Error looking up user by WP ID: ${errorMessage}`, 'error');
            // Continue to try creating user
        }

        // If user doesn't exist, create a new user
        if (!user) {
            log(`[auth-utils-fixed] Creating new Better Auth user for WP user ${wpUserData.email}`);
            try {
                // Prepare user data from WP user
                const userData = {
                    email: wpUserData.email,
                    name: wpUserData.displayName || `${wpUserData.firstName} ${wpUserData.lastName}`.trim() || wpUserData.username,
                    wpUserId: Number(wpUserData.wpUserId),
                    roles: wpUserData.roles || [],
                    metadata: {
                        wpUsername: wpUserData.username,
                        wpFirstName: wpUserData.firstName || '',
                        wpLastName: wpUserData.lastName || '',
                        wpDisplayName: wpUserData.displayName || ''
                    }
                };

                // Create user in Better Auth using the adapter function
                user = await createUserFn(userData);
                if (!user) throw new Error('Failed to create user');
                log(`[auth-utils-fixed] Created new user in Better Auth: ${user.id}`);
            } catch (err) {
                const errorMessage = err instanceof Error ? err.message : String(err);
                log(`[auth-utils-fixed] Error creating user: ${errorMessage}`, 'error');
                return null;
            }
        } else {
            log(`[auth-utils-fixed] Using existing Better Auth user: ${user.id}`);
        }

        // Create session for the user using session adapter function
        if (user && user.id) {
            log(`[auth-utils-fixed] Creating session for user ID: ${user.id}`);
            try {
                const session = await createSessionFn({
                    userId: user.id,
                    expires: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days
                    metadata: {
                        wpSync: true,
                        wpUserId: wpUserData.wpUserId,
                        syncTimestamp: new Date().toISOString()
                    }
                });

                log(`[auth-utils-fixed] Session created successfully: ${session.id}`);
                return {
                    token: session.token,
                    userId: user.id
                };
            } catch (err) {
                const errorMessage = err instanceof Error ? err.message : String(err);
                log(`[auth-utils-fixed] Error creating session: ${errorMessage}`, 'error');
                return null;
            }
        }

        log('[auth-utils-fixed] Failed to sync WP user - no valid user object', 'error');
        return null;
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        log(`[auth-utils-fixed] Unexpected error in syncWordPressUserAndCreateSession: ${errorMessage}`, 'error');
        return null;
    }
} 