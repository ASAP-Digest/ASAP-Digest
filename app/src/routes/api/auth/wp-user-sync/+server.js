/**
 * @file /api/auth/wp-user-sync/+server.js
 * @description API endpoint to sync WordPress user to Better Auth via the new v3 mechanism
 * When the frontend detects a WordPress session (via GraphQL viewer query) but no Better Auth session,
 * it sends the WP user details to this endpoint, which then creates or finds the corresponding
 * Better Auth user and establishes a Better Auth session (with cookie).
 * @since 1.0.0
 */

import { json } from '@sveltejs/kit';
// Import individual adapter functions directly, as this endpoint provides
// custom logic outside the standard Better Auth flows handled by [...auth].js
import { 
  getUserByWpIdFn, 
  createUserFn, 
  createSessionFn 
} from '$lib/server/auth';
import crypto from 'node:crypto';

/**
 * Handle POST requests to sync WordPress user to Better Auth
 * 
 * @param {object} event - The SvelteKit request event object.
 * @param {Request} event.request - The request object containing JSON data.
 * @param {import('@sveltejs/kit').Cookies} event.cookies - Cookies API for the response.
 * @returns {Promise<Response>} JSON response with sync status.
 */
export async function POST(event) {
    console.log('[API /wp-user-sync] Received WordPress user sync request');
    
    /** @type {WpUserSync|null} */
    let wpUserDetails = null;
    
    try {
        const requestBody = await event.request.json();
        
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
            throw new Error('Invalid request structure');
        }
    } catch (error) {
        console.error('[API /wp-user-sync] Error parsing request body:', error instanceof Error ? error.message : String(error));
        return json({ success: false, error: 'Invalid request body' }, { status: 400 });
    }
    
    // Type guard to ensure wpUserDetails is not null before destructuring
    if (!wpUserDetails) {
        return json({ 
            success: false, 
            error: 'Invalid request data' 
        }, { status: 400 });
    }
    
    // Extract and validate required fields using type safety protocol
    const { wpUserId, email, username, name } = wpUserDetails;
    
    if (!wpUserId || !email) {
        console.error('[API /wp-user-sync] Missing required fields:', { wpUserId, email });
        return json({ 
            success: false, 
            error: 'Missing required fields: wpUserId and email are required' 
        }, { status: 400 });
    }
    
    // Type safety: ensure wpUserId is a number
    const wpUserIdNum = typeof wpUserId === 'string' ? parseInt(wpUserId, 10) : wpUserId;
    
    if (isNaN(wpUserIdNum)) {
        console.error('[API /wp-user-sync] Invalid WordPress user ID:', wpUserId);
        return json({ 
            success: false, 
            error: 'Invalid WordPress user ID' 
        }, { status: 400 });
    }
    
    try {
        // Step 1: Find existing user by WordPress ID using getUserByWpIdFn
        console.log(`[API /wp-user-sync] Looking up user by WP ID: ${wpUserIdNum}`);
        
        /**
         * @type {User|null} - The Better Auth user or null if not found
         */
        let baUser = await getUserByWpIdFn(wpUserIdNum);
        
        // Type guard to ensure baUser is valid when found
        if (baUser && typeof baUser === 'object' && 'id' in baUser) {
            // User exists in Better Auth
            console.log(`[API /wp-user-sync] Found existing user for WP ID ${wpUserIdNum}: ${baUser.id}`);
        } else {
            // User doesn't exist, create a new one using createUserFn
            console.log(`[API /wp-user-sync] No existing user found for WP ID ${wpUserIdNum}. Creating new user...`);
            
            // Create the BA user - structure matching createUserFn parameter requirements
            baUser = await createUserFn({
                wpUserId: wpUserIdNum,
                email,
                username: username || email.split('@')[0],
                name: name || username || email.split('@')[0],
                roles: ['subscriber'] // Default role
            });
            
            // Type guard to check if user creation succeeded
            if (!baUser || typeof baUser !== 'object' || !('id' in baUser)) {
                throw new Error(`Failed to create Better Auth user for WordPress user ${wpUserIdNum}`);
            }
            
            console.log(`[API /wp-user-sync] Successfully created user for WP ID ${wpUserIdNum}: ${baUser.id}`);
        }
        
        // Step 2: Create a Better Auth session for the user using createSessionFn
        // Type guard to ensure baUser exists and has id
        if (baUser && typeof baUser === 'object' && 'id' in baUser && baUser.id) {
            console.log(`[API /wp-user-sync] Creating session for user: ${baUser.id}`);
            
            /**
             * Generate required arguments for session creation
             * @type {string} - Random hex token for session
             */
            const sessionToken = crypto.randomBytes(32).toString('hex');
            
            /**
             * Session expiration date (30 days from now)
             * @type {Date}
             */
            const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000); // 30 days
            
            // The createSession method should handle cookie setting via hooks
            const session = await createSessionFn(String(baUser.id), sessionToken, expiresAt);
            
            // Type guard to check if session creation succeeded
            if (!session || typeof session !== 'object') {
                throw new Error(`Failed to create session for user ${baUser.id}`);
            }
            
            console.log(`[API /wp-user-sync] Session created successfully. Session token set in cookie.`);
            
            // Return success response
            return json(/** @type {WpUserSyncResponse} */ ({
                success: true,
                userId: baUser.id
            }));
        } else {
            throw new Error('Failed to obtain valid user after find/create operations');
        }
        
    } catch (error) {
        // Use type guard for error object for safety
        const errorMessage = error instanceof Error ? error.message : String(error);
        console.error('[API /wp-user-sync] Error during user sync:', errorMessage);
        return json(/** @type {WpUserSyncResponse} */ ({
            success: false, 
            error: `Server error: ${errorMessage}` 
        }), { status: 500 });
    }
} 