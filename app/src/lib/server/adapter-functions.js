/**
 * @file Mock/placeholder adapter functions for Better Auth
 * 
 * This file provides placeholder implementations of the adapter functions
 * required by Better Auth. In a production environment, these should be
 * replaced with real database logic, likely using Kysely or another query builder.
 * 
 * @version 1.0.0
 * @created 2025-07-29
 */

import { log } from '$lib/utils/log.js';
import crypto from 'node:crypto';

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
 * Find user by WordPress ID stored in metadata
 * @param {number} wpUserId - WordPress User ID
 * @returns {Promise<User|null>} User object or null if not found
 */
export async function getUserByWpIdFn(wpUserId) {
    log(`[Adapter] getUserByWpIdFn called for WP ID: ${wpUserId}`);
    // Placeholder: Query ba_users table based on wpUserId metadata or join with ba_accounts
    return null; 
}

/**
 * Create a new user
 * @param {Object} userData - Data for the new user
 * @param {string} userData.email - User email
 * @param {string} [userData.name] - Optional display name
 * @param {number} [userData.wpUserId] - Optional WordPress user ID
 * @param {string[]} [userData.roles] - Optional user roles
 * @returns {Promise<User|null>} Created user object or null on failure
 */
export async function createUserFn(userData) {
    log(`[Adapter] createUserFn called for email: ${userData.email}`);
    
    // Prepare properly structured metadata object
    /** @type {UserMetadata} */
    const metadata = {
        wp_user_id: userData.wpUserId,
        roles: userData.roles || ['subscriber']
    };
    
    // Return mock user with proper structure
    return {
        id: crypto.randomUUID(), // Example ID generation
        email: userData.email,
        username: userData.email.split('@')[0],
        name: userData.name,
        displayName: userData.name || userData.email,
        metadata: metadata
    };
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
    // Placeholder: Insert into ba_accounts table
    return true; // Assume success for placeholder
}

/**
 * Create a new session
 * @param {Object} sessionData - Session data
 * @param {string} sessionData.userId - Better Auth user ID
 * @returns {Promise<Session|null>} Created session or null on failure
 */
export async function createSessionFn(sessionData) {
    log(`[Adapter] createSessionFn called for user: ${sessionData.userId}`);
    
    // Generate session ID and token
    const sessionId = crypto.randomUUID();
    const token = crypto.randomBytes(32).toString('hex');
    const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000); // 30 days
    const now = new Date();

    // Return mock session with proper structure
    return {
        id: sessionId,
        userId: sessionData.userId,
        token: token,
        expiresAt: expiresAt,
        createdAt: now,
        updatedAt: now
    };
}

/**
 * Get user by email
 * @param {string} email - User email
 * @returns {Promise<User|null>} User object or null if not found
 */
export async function getUserByEmailFn(email) {
    log(`[Adapter] getUserByEmailFn called for email: ${email}`);
    // Placeholder
    return null;
}

/**
 * Get user by ID
 * @param {string} userId - Better Auth user ID
 * @returns {Promise<User|null>} User object or null if not found
 */
export async function getUserByIdFn(userId) {
    log(`[Adapter] getUserByIdFn called for ID: ${userId}`);
    // Placeholder
    return null;
}

/**
 * Get session by token
 * @param {string} token - Session token
 * @returns {Promise<Session|null>} Session object or null if not found/expired
 */
export async function getSessionByTokenFn(token) {
    log(`[Adapter] getSessionByTokenFn called`);
    // Placeholder
    return null;
}

/**
 * Delete session by token
 * @param {string} token - Session token
 * @returns {Promise<boolean>} Success status
 */
export async function deleteSessionFn(token) {
    log(`[Adapter] deleteSessionFn called`);
    // Placeholder
    return true;
} 