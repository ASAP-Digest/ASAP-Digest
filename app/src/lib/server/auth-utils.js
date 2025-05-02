/**
 * @file Utility functions for server-side authentication and user synchronization.
 * @created 05.01.25 | 09:45 PM PDT // Placeholder timestamp
 */

import { 
    getUserByWpIdFn, 
    createUserFn, 
    createAccountFn,
    createSessionFn 
} from '$lib/server/auth.js'; // Import necessary adapter functions
import crypto from 'node:crypto';
import { Kysely } from 'kysely'; // Add Kysely import for JSDoc

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
 * @typedef {import('$lib/server/auth').User} User - Assuming User type is defined and exported correctly in auth.js or globally
 */

/**
 * @typedef {object} CreateUserInput - Defines the expected input structure for createUserFn
 * @property {number} wpUserId
 * @property {string} [email]
 * @property {string} [username]
 * @property {string} [name]
 * @property {string[]} [roles]
 */

/**
 * @typedef {object} Session - Defines the structure returned by our custom createSessionFn
 * @property {string} sessionId - Session ID (Primary Key, usually UUID)
 * @property {string} userId - Better Auth User ID
 * @property {string} token - Session token
 * @property {Date} expiresAt - Session expiry date
 * @property {Date} createdAt - Session creation date
 * @property {User} user - Session user object
 */

/**
 * Synchronizes WordPress user details with the Better Auth system.
 * Finds an existing BA user based on WP ID, or creates a new BA user and account link.
 * Then creates and returns a new Better Auth session for the user.
 * Does NOT handle cookie setting.
 * 
 * This is the core logic for the V4 Server-to-Server auto-login flow (Step 7).
 * It should be called by the SvelteKit backend endpoint that verifies the WP-generated token.
 * Also relevant for Roadmap Task 4 (Authentication Features).
 * 
 * @param {WpUserSync} wpUserDetails - Details of the WordPress user from validation.
 * @returns {Promise<Session | null>} The created Session object, or null if synchronization or session creation fails.
 * @created 05.01.25 | 09:45 PM PDT // Placeholder timestamp
 */
export async function syncWordPressUserAndCreateSession(wpUserDetails) {
    log(`syncWordPressUserAndCreateSession called for WP User: ${wpUserDetails?.wpUserId}`);

    // Validate input
    if (!wpUserDetails || typeof wpUserDetails !== 'object' || !wpUserDetails.wpUserId || !wpUserDetails.email) {
        log(`Invalid wpUserDetails received: ${JSON.stringify(wpUserDetails)}`, 'error');
        return null;
    }
    
    /** @type {User | null} */
    let baUser = null;
    const wpUserIdNum = Number(wpUserDetails.wpUserId); // Ensure number type
    
    if (isNaN(wpUserIdNum)) {
         log(`Invalid wpUserId provided: ${wpUserDetails.wpUserId}`, 'error');
         return null;
    }

    try {
        // 1. Look up user by WP ID
        log(`Attempting to find existing BA user for WP ID: ${wpUserIdNum}`);
        baUser = await getUserByWpIdFn(wpUserIdNum); 

        if (baUser && typeof baUser === 'object' && baUser.id) {
            log(`Existing BA user found: ${baUser.id}`);
             /** @type {User} */ // Type assertion after check
             const existingUser = baUser; 
             baUser = existingUser; // Use typed variable
        } else {
            // 2. Create new user if not found
            log(`No existing BA user found for WP ID: ${wpUserIdNum}. Creating user...`);
            
             /** @type {CreateUserInput} */ // Use the specific input type
            const newUserInput = {
                wpUserId: wpUserIdNum,
                email: wpUserDetails.email,
                username: wpUserDetails.username || wpUserDetails.email.split('@')[0],
                name: wpUserDetails.name || wpUserDetails.username || `WP User ${wpUserIdNum}`,
                // roles: wpUserDetails.roles || ['subscriber'] // Roles might come later or via WP profile sync
            };
            
            baUser = await createUserFn(newUserInput);

            if (!baUser || typeof baUser !== 'object' || !baUser.id) {
                log(`Failed to create BA user for WP ID ${wpUserIdNum}. Result from createUserFn: ${JSON.stringify(baUser)}`, 'error');
                throw new Error(`Failed to create Better Auth user for WordPress user ${wpUserIdNum}`);
            }
             /** @type {User} */ // Type assertion after check
             const createdUser = baUser;
             baUser = createdUser; // Use typed variable
             log(`BA user created successfully: ${createdUser.id}`);

            // 3. Link Account
            log(`Attempting account link for BA User ${createdUser.id} / WP ID ${wpUserIdNum}`);
            const accountLinked = await createAccountFn({
                userId: createdUser.id, 
                provider: 'wordpress', // Consistent provider name
                providerAccountId: String(wpUserIdNum) // Ensure string
            });

            if (!accountLinked) {
                // Log warning but don't fail the whole process
                log(`Failed to create account link in ba_accounts for user ${createdUser.id} and WP ID ${wpUserIdNum}. Proceeding...`, 'warn');
            } else {
                log(`Account link created successfully.`);
            }
        }

        // 4. Create Session
        if (!baUser || !baUser.id) {
             log(`Cannot create session, invalid baUser object after lookup/create.`, 'error');
             throw new Error('Invalid user object state before session creation.');
        }
        
        log(`Attempting to create BA session for BA User ID: ${baUser.id}`);
        const sessionToken = crypto.randomBytes(32).toString('hex');
        const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000); // 30 days

        const session = await createSessionFn(String(baUser.id), sessionToken, expiresAt);

        if (!session || typeof session !== 'object' || !session.token) {
            log(`Session creation failed or did not return valid session object for user ${baUser.id}. Result: ${JSON.stringify(session)}`, 'error');
            throw new Error(`Failed to create session for user ${baUser.id}`);
        }
         /** @type {Session} */ // Use local Session typedef
         const createdSession = session;
         log(`BA session created successfully. Session ID: ${createdSession.sessionId}`); // Now matches Session typedef
        
        return createdSession; // Return the created session object

    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        log(`Error in syncWordPressUserAndCreateSession for WP ID ${wpUserIdNum}: ${message}`, 'error');
        return null; // Return null on any failure
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

    if (!db || typeof db.updateTable !== 'function') { // Basic check for db instance
        log(`Failed to update user metadata: Invalid Kysely instance provided`, 'error');
        return false;
    }
    
    try {
        // Safely stringify metadata with error handling
        let metadataString;
        try {
            metadataString = JSON.stringify(metadata);
        } catch (stringifyError) {
            log(`Failed to stringify metadata: ${stringifyError instanceof Error ? stringifyError.message : String(stringifyError)}`, 'error');
            return false;
        }
        
        // Use the passed Kysely instance
        const result = await db
            .updateTable('ba_users')
            .set({ metadata: metadataString, updated_at: new Date() }) // Also update updated_at
            .where('id', '=', userId)
            .executeTakeFirst(); // Use executeTakeFirst for updates if expecting one result
            
        log(`Update result for user ${userId}: ${JSON.stringify(result)}`);
        // Kysely's executeTakeFirst for update typically returns UpdateResult containing numUpdatedRows
        // Checking if numUpdatedRows exists and is > 0 might be more robust
        return result && typeof result === 'object' && BigInt(result.numUpdatedRows || 0) > 0n;
        
    } catch (error) {
        log(`Failed to update user metadata for ${userId}: ${error instanceof Error ? error.message : String(error)}`, 'error');
        return false;
    }
} 