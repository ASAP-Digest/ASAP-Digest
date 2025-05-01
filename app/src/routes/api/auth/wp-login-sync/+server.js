import { json } from '@sveltejs/kit';
import crypto from 'node:crypto'; // Import crypto for token generation
// Remove main auth import if only using specific functions, 
// OR keep it if other parts of 'auth' (like hooks) are needed elsewhere in this file (unlikely here).
// For clarity, let's remove it and only import what's needed.
// import { auth } from '$lib/server/auth'; 

// Import the specific adapter functions we need
import { 
    getUserByWpIdFn,
    createUserFn,
    createSessionFn
} from '$lib/server/auth';

// Remove placeholder types as we are using direct functions now
// /** @typedef {import('$lib/types').AdapterUser} AdapterUser */ 
// /** @typedef {import('$lib/types').AdapterSession} AdapterSession */

/**
 * Handles POST requests to synchronize WP user login with Better Auth.
 * Expects wpUserId, email, username, displayName in the request body.
 * Attempts to find the user by wpUserId, creates the user if not found,
 * then creates a Better Auth session and sets the session cookie.
 * 
 * @param {import('@sveltejs/kit').RequestEvent} event The SvelteKit request event.
 * @returns {Promise<Response>} A JSON response indicating success or failure.
 */
export async function POST(event) {
    console.log('[API /wp-login-sync] Received POST request.');
    let requestBody;
    try {
        requestBody = await event.request.json();
    } catch (e) {
        console.error('[API /wp-login-sync] Error parsing request body:', e);
        return json({ success: false, error: 'Invalid request body.' }, { status: 400 });
    }

    const { wpUserId, email, username, displayName } = requestBody;

    // Validate required fields
    if (!wpUserId || !email || !username || !displayName) {
        console.error('[API /wp-login-sync] Missing required user data in request body.', requestBody);
        return json({ success: false, error: 'Missing required user data.' }, { status: 400 });
    }

    // Remove unused @type annotations if placeholders were removed
    // /** @type {AdapterUser | null} */
    let baUser = null;
    // /** @type {AdapterSession | null} */
    let session = null;

    try {
        console.log(`[API /wp-login-sync] Attempting to find user by wpUserId: ${wpUserId}`);
        // 1. Verify SK/BA User Existence - Use imported function directly
        baUser = await getUserByWpIdFn(wpUserId);

        if (baUser) {
            // 2. Handle Existing User
            console.log(`[API /wp-login-sync] Found existing Better Auth user: ${baUser.id} (${baUser.email})`);
        } else {
            // 3. Handle New User
            console.log(`[API /wp-login-sync] No existing Better Auth user found. Attempting to create user...`);
            // Ensure createUser handles ba_users and ba_accounts creation
            // Use imported function directly
            baUser = await createUserFn({
                email: email,
                username: username, 
                name: displayName,
                wpUserId: wpUserId, // Pass wpUserId for mapping
                // Include other necessary fields if your adapter requires them
            });

            // Add null check after creation attempt
            if (!baUser) {
                throw new Error('Failed to create Better Auth user in adapter.');
            }
            console.log(`[API /wp-login-sync] Successfully created new Better Auth user: ${baUser.id} (${baUser.email})`);
        }

        // 4. BA User & Account Login (Session Creation)
        // Ensure baUser is valid before proceeding (redundant check, but safe)
        if (baUser && baUser.id) { 
            console.log(`[API /wp-login-sync] Attempting to create session for user: ${baUser.id}`);
            
            // Generate required arguments for direct adapter call
            const sessionToken = crypto.randomBytes(32).toString('hex');
            const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000); // 30 days expiry

            // createSession should handle setting the cookie via hooks/internals
            // Use imported function directly, providing all arguments
            session = await createSessionFn(baUser.id, sessionToken, expiresAt);
            
            if (!session) {
                 throw new Error('Failed to create session via adapter function.');
            }

            console.log(`[API /wp-login-sync] Successfully created session.`);

            // 5. Return success (Cookie should be set by createSession)
            return json({ success: true });
        } else {
            // This should not happen if user was found or created successfully
            throw new Error('Failed to obtain valid Better Auth user object after find/create.');
        }

    } catch (error) {
        console.error('[API /wp-login-sync] Error during user sync/session creation:', error);
        // Log specific details if available
        // Use optional chaining for safer access in logging
        console.error(`[API /wp-login-sync] Error occurred after finding/creating user ${baUser?.id || '(unknown ID)'}. Session creation may have failed.`);
        return json({ success: false, error: 'Server error during user synchronization or session creation.' }, { status: 500 });
    }
} 