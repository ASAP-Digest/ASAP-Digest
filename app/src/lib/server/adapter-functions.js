/**
 * @file Better Auth adapter functions for database operations
 * @description Provides standalone functions for user/session management
 * @version 1.0.0
 */

import crypto from 'node:crypto';
import { log } from '$lib/utils/log';

/**
 * @typedef {Object} UserMetadata
 * @property {number} [wp_user_id] - WordPress user ID
 * @property {string[]} [roles] - User roles array
 * @property {string} [wpUsername] - WordPress username
 * @property {string} [wpFirstName] - WordPress first name
 * @property {string} [wpLastName] - WordPress last name 
 * @property {string} [wpDisplayName] - WordPress display name
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
 * Find user by WordPress ID stored in metadata
 * @param {number} wpUserId - WordPress User ID
 * @returns {Promise<User|null>} User object or null if not found
 */
export async function getUserByWpIdFn(wpUserId) {
    log(`[Adapter] getUserByWpIdFn called for WP ID: ${wpUserId}`);
    // This would normally query the database
    // For now, return null to simulate not finding the user
    return Promise.resolve(null);
}

/**
 * Create a new user
 * @param {Object} userData - Data for the new user
 * @param {string} userData.email - User email
 * @param {string} [userData.name] - Optional display name
 * @param {number} [userData.wpUserId] - Optional WordPress user ID
 * @param {string[]} [userData.roles] - Optional user roles
 * @param {Object} [userData.metadata] - Optional additional metadata
 * @returns {Promise<User>} Created user object
 */
export async function createUserFn(userData) {
    log(`[Adapter] createUserFn called for email: ${userData.email}`);
    
    // Normalize the WordPress user ID to a number
    const wpUserId = userData.wpUserId ? Number(userData.wpUserId) : undefined;
    
    // Construct metadata object with WordPress-specific fields
    /** @type {UserMetadata} */
    const metadata = {
        wp_user_id: wpUserId,
        roles: userData.roles || ['subscriber'],
        ...userData.metadata
    };
    
    // Create a new user with a random UUID
    /** @type {User} */
    const user = {
        id: crypto.randomUUID(),
        email: userData.email,
        username: userData.email.split('@')[0],
        name: userData.name,
        displayName: userData.name || userData.email,
        metadata: metadata
    };
    
    log(`[Adapter] Created user with ID: ${user.id}`);
    
    // In a real implementation, this would save to database
    return Promise.resolve(user);
}

/**
 * Create a linked account record
 * @param {Object} accountData - Account linking data
 * @param {string} accountData.userId - Better Auth user ID
 * @param {string} accountData.provider - Provider name (e.g., 'wordpress')
 * @param {string} accountData.providerAccountId - Provider-specific ID
 * @returns {Promise<boolean>} Success status
 */
export async function createAccountFn(accountData) {
    log(`[Adapter] createAccountFn called for user: ${accountData.userId}, provider: ${accountData.provider}`);
    // This would normally create a record in the accounts table
    return Promise.resolve(true);
}

/**
 * Create a new session
 * @param {Object} sessionData - Session data
 * @param {string} sessionData.userId - Better Auth user ID
 * @param {Date} [sessionData.expires] - Optional expiration date
 * @param {Object} [sessionData.metadata] - Optional session metadata
 * @returns {Promise<Session>} Created session
 */
export async function createSessionFn(sessionData) {
    log(`[Adapter] createSessionFn called for user: ${sessionData.userId}`);
    
    // Generate a random session ID and token
    const sessionId = crypto.randomUUID();
    const token = crypto.randomBytes(32).toString('hex');
    const now = new Date();
    
    // Default expiration is 30 days if not provided
    const expiresAt = sessionData.expires || new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000);
    
    /** @type {Session} */
    const session = {
        id: sessionId,
        userId: sessionData.userId,
        token: token,
        expiresAt: expiresAt,
        createdAt: now,
        updatedAt: now
    };
    
    log(`[Adapter] Created session with ID: ${session.id}`);
    
    // In a real implementation, this would save to database
    return Promise.resolve(session);
}

/**
 * Get a user by their email address
 * @param {string} email - User email address
 * @returns {Promise<User|null>} User object or null if not found
 */
export async function getUserByEmailFn(email) {
    log(`[Adapter] getUserByEmailFn called for email: ${email}`);
    // This would normally query the database
    return Promise.resolve(null);
}

/**
 * Get a user by their ID
 * @param {string} id - User ID
 * @returns {Promise<User|null>} User object or null if not found
 */
export async function getUserByIdFn(id) {
    log(`[Adapter] getUserByIdFn called for ID: ${id}`);
    // This would normally query the database
    return Promise.resolve(null);
}

/**
 * Get a session by its token
 * @param {string} token - Session token
 * @returns {Promise<Session|null>} Session object or null if not found
 */
export async function getSessionByTokenFn(token) {
    log(`[Adapter] getSessionByTokenFn called`);
    // This would normally query the database
    return Promise.resolve(null);
}

/**
 * Delete a session
 * @param {string} sessionId - Session ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteSessionFn(sessionId) {
    log(`[Adapter] deleteSessionFn called for session: ${sessionId}`);
    // This would normally delete from database
    return Promise.resolve(true);
} 