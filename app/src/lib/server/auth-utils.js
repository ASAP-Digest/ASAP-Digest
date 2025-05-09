/**
 * @file Utility functions for server-side authentication and user synchronization.
 * @created 05.01.25 | 09:45 PM PDT
 * @milestone WP <-> SK Auto Login V6 - MILESTONE COMPLETED! 2025-05-03
 * 
 * This file contains the core functions that enable successful auto-login between
 * WordPress and SvelteKit. The implementation now:
 * 
 * 1. Successfully creates users in ba_users table
 * 2. Creates account records in ba_accounts table
 * 3. Creates authenticated sessions in ba_sessions table
 * 4. Sets cookies properly for client-side authentication
 * 
 * NOTE: The main auto-login process is handled by:
 * - WordPress API endpoint that returns active sessions
 * - SvelteKit server endpoint that calls the WP API (/api/auth/check-wp-session)
 * - This utility file which performs the database operations
 * - Frontend layout component that initiates the process and handles UI updates
 */

import { auth } from '$lib/server/auth.js'; // Import the configured Better Auth instance
import crypto from 'node:crypto';
import { Kysely } from 'kysely'; // Add Kysely import for JSDoc
import { error } from '@sveltejs/kit'; // For potential error responses
import { log as appLog } from '$lib/utils/log.js'; // Rename imported log

/**
 * Logs messages with a specific prefix for auth utils.
 * @param {string} message - The message to log.
 * @param {'info'|'warn'|'error'} [level='info'] - The log level.
 */
function log(message, level = 'info') {
    const prefix = '[Auth Utils]';
    if (level === 'error') {
        console.error(`${prefix} ERROR: ${message}`);
    } else if (level === 'warn') {
        console.warn(`${prefix} WARN: ${message}`);
    } else {
        console.log(`${prefix} ${message}`);
    }
}

/**
 * @typedef {import('$lib/server/auth').User} User 
 */

/**
 * @typedef {object} CreateUserInput 
 * @property {number} wpUserId
 * @property {string} [email]
 * @property {string} [username]
 * @property {string} [name]
 * @property {string} [avatarUrl]
 * @property {string[]} [roles]
 */

// Removed local Session typedef

/**
 * Synchronizes WordPress user data with Better Auth user/account records
 * and creates a Better Auth session.
 * This function encapsulates the logic previously used in the V3 /wp-user-sync endpoint.
 *
 * @param {object} wpUserDetails - Object containing WP user details.
 * @param {number|string} wpUserDetails.wpUserId - The WordPress user ID.
 * @param {string} wpUserDetails.email - The user's email.
 * @param {string} [wpUserDetails.username] - The WordPress username (optional).
 * @param {string} [wpUserDetails.name] - The user's display name (optional).
 * @param {string} [wpUserDetails.avatarUrl] - The user's avatar URL (optional).
 * @param {string[]} [wpUserDetails.roles] - The user's roles (optional).
 * @returns {Promise<import('better-auth').Session | null>} The created Better Auth session object on success, or null on failure.
 * @created 07.27.24 | 03:40 PM PDT
 * @file-marker syncWordPressUserAndCreateSession
 */
export async function syncWordPressUserAndCreateSession(wpUserDetails) {
	try {
		const { wpUserId, email, username, name, avatarUrl, roles = [] } = wpUserDetails;

		if (!wpUserId) {
			log('Error: Missing wpUserId in syncWordPressUserAndCreateSession', 'error');
			return null;
		}
		const wpUserIdNum = Number(wpUserId);
		if (isNaN(wpUserIdNum)) {
			log(`Invalid wpUserId provided: ${wpUserId}`, 'error');
			return null;
		}

		log(`Syncing WP User ID: ${wpUserIdNum}`);

		// F.2 - BA User Lookup
		log(`Attempting to find existing BA user for WP User ID: ${wpUserIdNum}`);
		// Type assertion needed as Better Auth type is not exported and structural typing fails inference
		let baUser = await /** @type {any} */ (auth).adapter.getUserByWpId(wpUserIdNum); // Use Better Auth adapter

		if (baUser && typeof baUser === 'object' && baUser.id) {
			// F.3 - Handle Existing User
			log(`Existing BA user found: ${baUser.id}`);
			/** @type {User} */ 
			const existingUser = baUser;
			baUser = existingUser;

			// Update avatar URL if provided
			if (avatarUrl && avatarUrl !== baUser.avatarUrl) {
				log(`Updating avatar URL for user ${baUser.id}`);
				try {
					// Update the avatar URL in the database
					await /** @type {any} */ (auth).database
						.updateTable('ba_users')
						.set({ 
							avatarUrl: avatarUrl,
							updated_at: new Date()
						})
						.where('id', '=', baUser.id)
						.executeTakeFirst();
					
					// Update the local baUser object
					baUser.avatarUrl = avatarUrl;
					log(`Successfully updated avatar URL for user ${baUser.id}`);
				} catch (updateError) {
					const message = updateError instanceof Error ? updateError.message : String(updateError);
					log(`Warning: Failed to update avatar URL: ${message}`, 'warn');
					// Continue with login even if avatar update fails
				}
			}
		} else {
			// F.4 - Handle New User
			log(`No existing BA user found for WP User ID: ${wpUserIdNum}. Initiating creation.`);
			try {
				// F.4.a - BA User Creation
				log(`Attempting to create BA user record (ba_users) for email: ${email}`);
				/** @type {CreateUserInput} */
				const newUserInput = {
					wpUserId: wpUserIdNum,
					email: email,
					username: username || email.split('@')[0], 
					name: name || username || `WP User ${wpUserIdNum}`,
					avatarUrl: avatarUrl || '', // Include avatar URL in user creation
					roles: roles || [] // Include roles in user creation
				};
				// Type assertion needed as Better Auth type is not exported and structural typing fails inference
				const newUser = await /** @type {any} */ (auth).adapter.createUser(newUserInput); // Use Better Auth adapter
				if (!newUser?.id) {
					log(`User creation failed or did not return ID. Input: ${JSON.stringify(newUserInput)}`, 'error');
					throw new Error('User creation failed or did not return ID.');
				}
				log(`BA user created successfully: ${newUser.id}`);

				// F.4.b - BA Account Creation (Crucial!)
				log(`Attempting to create BA account record (ba_accounts) linking ${newUser.id} to provider 'wordpress' with ID ${wpUserIdNum}`);
				// Type assertion needed as Better Auth type is not exported and structural typing fails inference
				await /** @type {any} */ (auth).adapter.createAccount({ // Use Better Auth adapter
					userId: newUser.id,
					provider: 'wordpress', 
					providerAccountId: String(wpUserIdNum) 
				});
				log(`BA account record created successfully.`);
				
				// F.4.c - Create User Mapping in ba_wp_user_map
				log(`Creating mapping in ba_wp_user_map table between WordPress user ${wpUserIdNum} and Better Auth user ${newUser.id}`);
				try {
					// Use the database from auth to create the mapping
					await /** @type {any} */ (auth).database
						.insertInto('ba_wp_user_map')
						.values({
							wp_user_id: wpUserIdNum,
							ba_user_id: newUser.id
						})
						.execute();
					log(`Successfully created mapping in ba_wp_user_map table`);
				} catch (mappingError) {
					// Log error but continue - the account record is already created
					const message = mappingError instanceof Error ? mappingError.message : String(mappingError);
					log(`Warning: Could not create ba_wp_user_map record: ${message}`, 'warn');
					// Check if it's a duplicate key error, which is fine
					if (message.includes('Duplicate entry')) {
						log(`Mapping already exists, continuing with login process`, 'info');
					}
				}
				
				/** @type {User} */ 
				const createdUser = newUser;
				baUser = createdUser; 

			} catch (creationError) {
				const message = creationError instanceof Error ? creationError.message : String(creationError);
				log(`Error during BA user/account creation: ${message}`, 'error');
				return null; // Indicate failure
			}
		}

		// F.5 - BA Session Creation
		if (!baUser || !baUser.id) {
			log('Cannot create session, invalid baUser object after lookup/create.', 'error');
			return null;
		}
		log(`Attempting to create BA session (ba_sessions) for BA User ID: ${baUser.id}`);
		
		// Fix: Pass the userId directly as a string, not wrapped in an object
		// Type assertion needed as Better Auth type is not exported and structural typing fails inference
		const session = await /** @type {any} */ (auth).adapter.createSession(String(baUser.id));

		if (!session || typeof session !== 'object' || !session.token) {
			 log(`Session creation failed or did not return valid session object. Result: ${JSON.stringify(session)}`, 'error');
			 return null; // Indicate failure
		}
		/** @type {import('better-auth').Session} */ // Use imported Session type
		const createdSession = session;
		log(`BA session created successfully. Session ID: ${createdSession.id}`);

		return createdSession; // Return the created session object

	} catch (error) {
		const message = error instanceof Error ? error.message : String(error);
		log(`Error in syncWordPressUserAndCreateSession: ${message}`, 'error');
		return null; // Indicate failure
	}
}

/**
 * Update user metadata in Better Auth database.
 * Useful for syncing profile updates (Roadmap Task 3.2: Implement Profile Page) 
 * or other metadata changes triggered from SvelteKit.
 * 
 * @param {Kysely<any>} db - The Kysely database instance (pass from calling context, e.g., imported from auth.js or created in endpoint).
 * @param {string} userId - Better Auth User ID.
 * @param {object} metadata - The complete metadata object to save (will be JSON stringified).
 * @returns {Promise<boolean>} Success status.
 * @created 05.01.25 | 10:15 PM PDT // Placeholder timestamp
 */
export async function updateUserMetadata(db, userId, metadata) {
    log(`updateUserMetadata called for user ${userId}`);
    // Apply Local Variable Type Safety Protocol - verify parameters
    if (!userId || typeof userId !== 'string') {
        log(`Failed to update user metadata: Invalid user ID`, 'error');
        return false;
    }
    
    if (!metadata || typeof metadata !== 'object') {
        log(`Failed to update user metadata: Invalid metadata`, 'error');
        return false;
    }

    if (!db || typeof db.updateTable !== 'function') {
        log(`Failed to update user metadata: Invalid Kysely instance provided`, 'error');
        return false;
    }
    
    try {
        let metadataString;
        try {
            metadataString = JSON.stringify(metadata);
        } catch (stringifyError) {
            const message = stringifyError instanceof Error ? stringifyError.message : String(stringifyError);
            log(`Failed to stringify metadata: ${message}`, 'error');
            return false;
        }
        
        const result = await db
            .updateTable('ba_users')
            .set({ metadata: metadataString, updated_at: new Date() })
            .where('id', '=', userId)
            .executeTakeFirst();
            
        log(`Update result for user ${userId}: ${JSON.stringify(result)}`);
        return result && typeof result === 'object' && BigInt(result.numUpdatedRows || 0) > 0n;
        
    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        log(`Failed to update user metadata for ${userId}: ${message}`, 'error');
        return false;
    }
} 