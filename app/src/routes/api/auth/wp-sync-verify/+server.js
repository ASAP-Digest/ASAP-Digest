import { json, error } from '@sveltejs/kit';
import { WP_API_URL } from '$env/static/private'; // Assuming WP URL is in env
// Import the initialized auth instance and adapter functions if needed
import { auth } from '$lib/server/auth.js'; 
// Assuming adapter functions are exported or accessible via auth.adapter
// If not exported directly, we might need to modify auth.js to export them, 
// or rely on auth internal methods if appropriate (less ideal).
// For now, assume they are available via the imported auth object's adapter property IF configured.
// OR better, import them directly if auth.js exports them individually.
// import { 
//     getUserByWpIdFn,
//     createUserFn
// } from '$lib/server/auth.js'; // Attempt direct import (assuming they are exported)

/**
 * Handles POST requests to verify a WP sync token and potentially create a BA session.
 * Expects { token: string } in the request body.
 *
 * @param {import('@sveltejs/kit').RequestEvent} event The request event.
 * @returns {Promise<Response>} JSON response indicating success or failure.
 */
export async function POST({ request, cookies }) { // Added cookies to event
  try {
    const body = await request.json();
    const { token } = body;

    if (!token || typeof token !== 'string') {
      throw error(400, { message: 'Missing or invalid token in request body.' });
    }

    console.log(`[API /wp-sync-verify] Received token: ${token.substring(0, 5)}...`); 

    // --- Step C: Call WP validation endpoint ---
    // Use the proxied path
    const wpValidateUrl = `/wp-api/asap/v1/validate-sync-token`; 
    console.log(`[API /wp-sync-verify] Calling WP endpoint via proxy: ${wpValidateUrl}`);

    const wpResponse = await fetch(wpValidateUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        // No need for cookies here - this is server-to-server routed via Vite proxy
      },
      body: JSON.stringify({ token: token })
    });

    const wpResult = await wpResponse.json();
    console.log(`[API /wp-sync-verify] Received response from WP: Status ${wpResponse.status}, Body:`, wpResult);

    if (!wpResponse.ok || !wpResult?.valid) {
        const wpErrorReason = wpResult?.message || (wpResponse.status === 401 ? 'Invalid/expired token' : 'Unknown WP validation error');
        console.warn(`[API /wp-sync-verify] WP token validation failed. Reason: ${wpErrorReason}`);
        throw error(401, { message: `WordPress token validation failed: ${wpErrorReason}` });
    }

    const wpUserId = wpResult.wpUserId;
    console.log(`[API /wp-sync-verify] WP token validated successfully for wpUserId: ${wpUserId}`);

    // --- Step D: BA User Lookup/Create & Session ---
    let baUser = null;
    let sessionCreated = false;

    // 1. User Lookup using adapter function
    console.log(`[API /wp-sync-verify] Looking up BA user for wpUserId: ${wpUserId}`);
    try {
        // @ts-ignore - Assuming adapter exists due to better-auth-config.mdc rule
        baUser = await auth.adapter.getUserByWpId(wpUserId); 
    } catch (lookupError) {
        console.error(`[API /wp-sync-verify] Error during getUserByIdFn: ${lookupError}`); // Keep original fn name in log msg
        throw error(500, { message: 'Error looking up user.'});
    }

    // 2. User Creation if not found
    if (!baUser) {
        console.log(`[API /wp-sync-verify] BA user not found for wpUserId: ${wpUserId}. Attempting creation...`);
        
        // --- Fetch WP User Details --- 
        let fetchedWpDetails = null;
        try {
            const userDetailsUrl = `${WP_API_URL}/asap/v1/user-details/${wpUserId}`;
            console.log(`[API /wp-sync-verify] Fetching WP user details from: ${userDetailsUrl}`);
            // IMPORTANT: This fetch needs appropriate authentication to succeed.
            // Using internal fetch from SK backend to WP backend. Needs secure method.
            // Placeholder: Assume internal network allows or use pre-shared key/application password.
            // TODO: Implement secure backend-to-backend authentication if needed.
            const wpUserDetailsResponse = await fetch(userDetailsUrl, {
                method: 'GET',
                headers: {
                    // Add auth headers if required by the WP endpoint's permission_callback
                    // 'Authorization': `Basic ${Buffer.from('user:app_password').toString('base64')}` 
                }
            });

            if (!wpUserDetailsResponse.ok) {
                throw new Error(`Failed to fetch WP user details. Status: ${wpUserDetailsResponse.status}`);
            }
            fetchedWpDetails = await wpUserDetailsResponse.json();
            console.log(`[API /wp-sync-verify] Successfully fetched WP user details:`, fetchedWpDetails);
        
        } catch (fetchWpError) {
            console.error(`[API /wp-sync-verify] Error fetching WP user details for ${wpUserId}: ${fetchWpError}`);
            // Decide if failure to fetch details should prevent user creation
            // For now, let's proceed with placeholders but log the error.
            // throw error(500, { message: 'Failed to retrieve WordPress user data for account creation.'}); 
        }
        // --- End Fetch WP User Details ---

        // Use fetched details if available, otherwise use placeholders
        const userDetailsForCreation = {
            wpUserId: wpUserId,
            email: fetchedWpDetails?.email || `wp_user_${wpUserId}@asapdigest.local`,
            username: fetchedWpDetails?.username || `wp_user_${wpUserId}`,
            name: fetchedWpDetails?.displayName || `WordPress User ${wpUserId}`,
            roles: fetchedWpDetails?.roles || ['subscriber']
        };
        
        try {
            // @ts-ignore - Assuming adapter exists due to better-auth-config.mdc rule
            baUser = await auth.adapter.createUser(userDetailsForCreation); // Pass potentially fetched details
            if (!baUser) {
                throw new Error('User creation function returned null.');
            }
            console.log(`[API /wp-sync-verify] Successfully created BA user: ${baUser.id}`);
        } catch (createError) {
            console.error(`[API /wp-sync-verify] Error during createUserFn: ${createError}`); // Keep original fn name in log msg
            throw error(500, { message: 'Error creating user.'});
        }
    } else {
        console.log(`[API /wp-sync-verify] Found existing BA user: ${baUser.id}`);
    }

    // 3. Session Creation
    if (baUser && baUser.id) {
        console.log(`[API /wp-sync-verify] Attempting to create session for BA user: ${baUser.id}`);
        try {
            const sessionToken = crypto.randomUUID(); 
            // @ts-ignore
            const sessionExpiresInMs = auth.options.sessionExpiresIn || (30 * 24 * 60 * 60 * 1000); 
            const expiresAt = new Date(Date.now() + sessionExpiresInMs);
            
            // @ts-ignore - Assuming adapter exists due to better-auth-config.mdc rule
            const session = await auth.adapter.createSession(baUser.id, sessionToken, expiresAt);

            if (session && session.token) {
                console.log(`[API /wp-sync-verify] BA Session created successfully: ${session.id}`);
                
                // @ts-ignore 
                const cookieName = auth.options.sessionCookieName || 'better_auth_session';
                
                cookies.set(cookieName, session.token, {
                    path: '/',
                    httpOnly: true,
                    secure: process.env.NODE_ENV === 'production',
                    sameSite: 'lax',
                    maxAge: sessionExpiresInMs / 1000 
                });
                console.log(`[API /wp-sync-verify] Session cookie set.`);
                sessionCreated = true;
            } else {
                throw new Error('Failed to create session using adapter.');
            }

        } catch (sessionError) {
            console.error(`[API /wp-sync-verify] Error creating session: ${sessionError}`);
            throw error(500, { message: 'Error creating session.'});
        }
    } else {
        console.error(`[API /wp-sync-verify] Cannot create session: No valid BA user object available.`);
        throw error(500, { message: 'Cannot create session without user.'});
    }
    
    // Final success response
    return json({ 
        message: 'Token validated and session established.', 
        wpUserId: wpUserId,
        baUserId: baUser.id,
        session_created: sessionCreated 
    }, { status: 200 });

  } catch (err) {
    console.error('[API /wp-sync-verify] Error processing request:', err);
    if (err && typeof err === 'object' && 'status' in err) {
        throw err; 
    } 
    else if (err instanceof Error) {
        throw error(500, { message: `Internal server error during token verification: ${err.message}` });
    }
    else {
        throw error(500, { message: 'Internal server error during token verification.' });
    }
  }
} 