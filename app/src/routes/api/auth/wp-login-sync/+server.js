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
 * Handles POST requests to synchronize WordPress user login with Better Auth.
 * 
 * @param {import('@sveltejs/kit').RequestEvent} event - The SvelteKit request event.
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

    // Validate required fields existence
    if (!wpUserId || !email || !username || !displayName) {
        console.error('[API /wp-login-sync] Missing required user data in request body.', requestBody);
        return json({ success: false, error: 'Missing required user data.' }, { status: 400 });
    }

    // Type guard: Ensure all required fields are strings
    if (typeof wpUserId !== 'string' || typeof email !== 'string' || typeof username !== 'string' || typeof displayName !== 'string') {
        console.error('[API /wp-login-sync] Invalid types for required fields.', { wpUserId, email, username, displayName });
        return json({ success: false, error: 'Invalid user data types.' }, { status: 400 });
    }

    /** @type {any} */
    let baUser = null;
    /** @type {any} */
    let session = null;

    try {
        console.log(`[API /wp-login-sync] Attempting to find user by wpUserId: ${wpUserId}`);
        // 1. Verify SK/BA User Existence using the imported function
        baUser = await getUserByWpIdFn(Number(wpUserId));

        if (baUser) {
            // 2. Handle Existing User
            console.log(`[API /wp-login-sync] Found existing Better Auth user: ${baUser.id} (${baUser.email})`);
        } else {
            // 3. Handle New User
            console.log(`[API /wp-login-sync] No existing Better Auth user found. Attempting to create user...`);
            baUser = await createUserFn({
                email,
                username,
                name: displayName,
                wpUserId: Number(wpUserId)
            });
            if (!baUser) {
                throw new Error('Failed to create Better Auth user in adapter.');
            }
            console.log(`[API /wp-login-sync] Successfully created new Better Auth user: ${baUser.id} (${baUser.email})`);
        }

        // 4. BA User & Account Login (Session Creation)
        if (baUser && baUser.id) { 
            console.log(`[API /wp-login-sync] Attempting to create session for user: ${baUser.id}`);
            const sessionToken = crypto.randomBytes(32).toString('hex');
            const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000); // 30 days expiry
            session = await createSessionFn(String(baUser.id), sessionToken, expiresAt);
            if (!session) {
                 throw new Error('Failed to create session via adapter function.');
            }

            console.log(`[API /wp-login-sync] Successfully created session.`);
            return json({ success: true });
        } else {
            throw new Error('Failed to obtain valid Better Auth user object after find/create.');
        }
    } catch (error) {
        console.error('[API /wp-login-sync] Error during user sync/session creation:', error);
        console.error(`[API /wp-login-sync] Error occurred after finding/creating user ${baUser?.id || '(unknown ID)'}. Session creation may have failed.`);
        return json({ success: false, error: 'Server error during user synchronization or session creation.' }, { status: 500 });
    }
} 