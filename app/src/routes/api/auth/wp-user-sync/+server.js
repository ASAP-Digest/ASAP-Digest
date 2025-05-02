/**
 * @file /api/auth/wp-user-sync/+server.js
 * @description API endpoint to sync WordPress user to Better Auth via the new v3 mechanism
 * When the frontend detects a WordPress session (via GraphQL viewer query) but no Better Auth session,
 * it sends the WP user details to this endpoint, which then creates or finds the corresponding
 * Better Auth user and establishes a Better Auth session (with cookie).
 * @since 1.0.0
 */

import { json } from '@sveltejs/kit';
import { dev } from '$app/environment'; // <-- Import dev
// Import individual adapter functions directly, as this endpoint provides
// custom logic outside the standard Better Auth flows handled by [...auth].js
// Also import the main 'auth' instance for session manager access.
import {
  auth,
  getUserByWpIdFn,
  createUserFn,
  createAccountFn,
  createSessionFn
} from '$lib/server/auth';

import crypto from 'node:crypto';

/**
 * @typedef {import('./$types').RequestEvent} RequestEvent - Rely on generated types if available for RequestEvent
 */

/**
 * @typedef {import('cookie').CookieSerializeOptions} CookieSerializeOptions - Import from 'cookie' package if needed, otherwise define structurally.
 */

/**
 * @typedef {object} SvelteKitCookies
 * @property {(name: string, options?: import('cookie').CookieSerializeOptions) => string | undefined} get
 * @property {(name: string) => Array<{name: string, value: string}>} getAll
 * @property {(name: string, value: string, options?: import('cookie').CookieSerializeOptions) => void} set
 * @property {(name: string, options?: import('cookie').CookieSerializeOptions) => void} delete
 * @property {(name: string, value: string, options?: import('cookie').CookieSerializeOptions) => string} serialize
 */

/**
 * Handle POST requests to sync WordPress user to Better Auth
 * 
 * @param {object} event - The SvelteKit request event object.
 * @param {Request} event.request - The request object containing JSON data.
 * @param {SvelteKitCookies} event.cookies - Cookies API for the response, structurally typed.
 * @returns {Promise<Response>} JSON response with sync status.
 */
export async function POST(event) {
    console.log('[API /wp-user-sync V3] Received WordPress user sync request');
    
    /** @type {WpUserSync|null} */
    let wpUserDetails = null;
    
    try {
        const requestBody = await event.request.json();
        console.log('[API /wp-user-sync V3] Request Body:', requestBody);
        
        // Validate structure with type guard
        if (
            typeof requestBody === 'object' && 
            requestBody !== null && 
            'wpUserId' in requestBody && 
            'email' in requestBody
        ) {
            // Safe to cast after validation
            wpUserDetails = /** @type {WpUserSync} */ (requestBody);
        } else {
            console.error('[API /wp-user-sync V3] Invalid request structure:', requestBody);
            throw new Error('Invalid request structure');
        }
    } catch (error) {
        console.error('[API /wp-user-sync V3] Error parsing request body:', error);
        return json({ success: false, error: 'Invalid request body' }, { status: 400 });
    }
    
    // Type guard to ensure wpUserDetails is not null before destructuring
    if (!wpUserDetails) {
        return json({ 
            success: false, 
            error: 'Invalid request data' 
        }, { status: 400 });
    }
    // Add inline type annotation after guard
    /** @type {WpUserSync} */
    const checkedWpUserDetails = wpUserDetails;
    
    // Extract and validate required fields using type safety protocol
    const { wpUserId, email, username, name } = checkedWpUserDetails;
    
    if (!wpUserId || !email) {
        console.error('[API /wp-user-sync V3] Missing required fields:', { wpUserId, email });
        return json({ 
            success: false, 
            error: 'Missing required fields: wpUserId and email are required' 
        }, { status: 400 });
    }
    
    // Type safety: ensure wpUserId is a number
    const wpUserIdNum = typeof wpUserId === 'string' ? parseInt(wpUserId, 10) : wpUserId;
    
    if (isNaN(wpUserIdNum)) {
        console.error('[API /wp-user-sync V3] Invalid WordPress user ID type or value:', wpUserId);
        return json({ 
            success: false, 
            error: 'Invalid WordPress user ID' 
        }, { status: 400 });
    }
    
    try {
        // Step 1: Find existing user by WordPress ID using getUserByWpIdFn
        console.log(`[API /wp-user-sync V3] Looking up user by WP ID: ${wpUserIdNum}`);
        
        /** @type {User|null} - The Better Auth user or null if not found */
        let baUser = await getUserByWpIdFn(wpUserIdNum);
        console.log(`[API /wp-user-sync V3] Result from getUserByWpIdFn:`, baUser);
        let isNewUser = false; // Flag to track if user was just created
        
        // Type guard to ensure baUser is valid when found
        if (baUser && typeof baUser === 'object' && 'id' in baUser) {
            // User exists in Better Auth
            console.log(`[API /wp-user-sync V3] Found existing user for WP ID ${wpUserIdNum}: ${baUser.id}`);
        } else {
            // User doesn't exist, create a new one using createUserFn
            console.log(`[API /wp-user-sync V3] No existing user found for WP ID ${wpUserIdNum}. Creating new user...`);
            isNewUser = true;
            
            try {
                // Create the BA user - structure matching createUserFn parameter requirements
                baUser = await createUserFn({
                    wpUserId: wpUserIdNum,
                    email,
                    username: username || email.split('@')[0],
                    name: name || username || email.split('@')[0],
                    roles: ['subscriber'] // Default role
                });
                console.log(`[API /wp-user-sync V3] Result from createUserFn:`, baUser);
                
                // Type guard to check if user creation succeeded
                if (!baUser || typeof baUser !== 'object' || !('id' in baUser)) {
                    console.error(`[API /wp-user-sync V3] createUserFn failed or returned invalid data for WP ID ${wpUserIdNum}. Result:`, baUser);
                    throw new Error(`Failed to create Better Auth user for WordPress user ${wpUserIdNum}`);
                }
                // Add inline type after successful creation
                /** @type {User} */
                const createdBaUser = baUser; 
                console.log(`[API /wp-user-sync V3] Successfully created user for WP ID ${wpUserIdNum}: ${createdBaUser.id}`);

                // ---> ADD: Link the new BA user to the WP provider account
                console.log(`[API /wp-user-sync V3] Attempting account link for BA User ${createdBaUser.id} / WP ID ${wpUserIdNum}`);
                const accountLinked = await createAccountFn({
                    userId: createdBaUser.id, // Use typed variable
                    provider: 'wordpress', // Use consistent provider name
                    providerAccountId: String(wpUserIdNum) // Store WP ID
                });
                if (!accountLinked) {
                    // Log warning but don't necessarily fail the whole process
                    console.warn(`[API /wp-user-sync V3] Failed to create account link in ba_accounts for user ${createdBaUser.id} and WP ID ${wpUserIdNum}. Proceeding with session creation.`);
                } else {
                    console.log(`[API /wp-user-sync V3] Account link created successfully.`);
                }
                baUser = createdBaUser; // Ensure baUser holds the typed created user
            } catch (creationError) {
                console.error(`[API /wp-user-sync V3] Error during user/account creation step:`, creationError);
                throw creationError; // Re-throw to be caught by outer handler
            }
        }
        
        // Step 2: Create a Better Auth session for the user using createSessionFn
        // Type guard to ensure baUser exists and has id
        if (baUser && typeof baUser === 'object' && 'id' in baUser && baUser.id) {
             // Add inline type annotation after guard
            /** @type {User} */
            const finalBaUser = baUser;
            console.log(`[API /wp-user-sync V3] Creating session for user: ${finalBaUser.id}`);
            
            /** @type {string} - Random hex token for session */
            const sessionToken = crypto.randomBytes(32).toString('hex');
            
            /** @type {Date} */
            const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000); // 30 days
            
            const session = await createSessionFn(String(finalBaUser.id), sessionToken, expiresAt);
            console.log(`[API /wp-user-sync V3] Result from createSessionFn:`, session);
            
            // Type guard to check if session creation succeeded
            if (!session || typeof session !== 'object') {
                console.error(`[API /wp-user-sync V3] createSessionFn failed or returned invalid data for user ${finalBaUser.id}. Result:`, session);
                throw new Error(`Failed to create session for user ${finalBaUser.id}`);
            }
            // Add inline type annotation after guard
            /** @type {Session} */ 
            const createdSession = session;

            // ---> ADD Type Guard for session token before setting cookie
            if (!createdSession.token || typeof createdSession.token !== 'string') {
                console.error(`[API /wp-user-sync V3] Session created for user ${finalBaUser.id}, but session token is missing or invalid. Session:`, createdSession);
                throw new Error(`Session created for user ${finalBaUser.id}, but session token is invalid.`);
            }
            // ---> END Type Guard

            console.log(`[API /wp-user-sync V3] Session details before setting cookie:`, createdSession);

            // --- REPLACE WITH: Use Better Auth's session manager to set the cookie ---
            // Use type assertion to inform the linter about sessionManager
            /** @type {any} */ (auth).sessionManager.setCookie(event.cookies, createdSession);
            console.log(`[API /wp-user-sync V3] Session cookie set via auth.sessionManager.setCookie.`);
            // --- END REPLACEMENT ---
            
            // Return success response
            console.log(`[API /wp-user-sync V3] Sync successful. Returning 200.`);
            return json(/** @type {WpUserSyncResponse} */ ({
                success: true,
                userId: finalBaUser.id
            }));
        } else {
            console.error('[API /wp-user-sync V3] Failed to obtain valid user object after find/create operations. User object:', baUser);
            throw new Error('Failed to obtain valid user after find/create operations');
        }
        
    } catch (error) {
        // Log the full error object
        console.error('[API /wp-user-sync V3] Error during user sync process:', error); 
        const errorMessage = error instanceof Error ? error.message : String(error);
        return json(/** @type {WpUserSyncResponse} */ ({
            success: false, 
            error: `Server error: ${errorMessage}` 
        }), { status: 500 });
    }
} 