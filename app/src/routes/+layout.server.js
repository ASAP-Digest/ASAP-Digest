import { auth } from '$lib/server/auth';
import { serialize } from 'cookie';
import { log } from '$lib/utils/log';

/**
 * @typedef {import('$lib/types/better-auth').BetterAuth} BetterAuth
 */

/**
 * Type guard to verify if an object has a sessionManager property
 * @param {any} obj - Object to test
 * @returns {obj is { sessionManager: { getSession: Function } }} - Type predicate
 */
function hasSessionManager(obj) {
    return obj && 
           typeof obj === 'object' && 
           'sessionManager' in obj && 
           obj.sessionManager && 
           typeof obj.sessionManager === 'object' &&
           'getSession' in obj.sessionManager &&
           typeof obj.sessionManager.getSession === 'function';
}

/**
 * Type guard to verify if an object has an adapter property
 * @param {any} obj - Object to test
 * @returns {obj is { adapter: { getUserById: Function } }} - Type predicate
 */
function hasAdapter(obj) {
    return obj && 
           typeof obj === 'object' && 
           'adapter' in obj && 
           obj.adapter && 
           typeof obj.adapter === 'object' &&
           'getUserById' in obj.adapter &&
           typeof obj.adapter.getUserById === 'function';
}

/**
 * Convert BetterAuthSession to App.Locals.session format
 * 
 * @param {import('$lib/types/better-auth').BetterAuthSession} authSession - The Better Auth session
 * @returns {{ userId: string, token: string, expiresAt: string }} - Session in App.Locals format
 */
function convertSessionForLocals(authSession) {
    return {
        userId: authSession.userId,
        token: authSession.token,
        // Convert Date to string if needed
        expiresAt: typeof authSession.expiresAt === 'string' 
            ? authSession.expiresAt 
            : authSession.expiresAt.toISOString()
    };
}

/**
 * Server load function for the root layout
 * @param {object} event The server load event
 * @param {App.Locals} event.locals Access to request-scoped data
 * @param {Request} event.request The request object
 * @returns {Promise<{ user: App.User | null }>} Data for all pages
 */
export async function load(event) {
    try {
        // PRIORITY 1: Check if hooks have already set user data
        if (event.locals.user) {
            log('[Layout.server] User already set by hooks, using existing data', 'info');
            log(`[Layout.server] User from hooks: ${event.locals.user.email} (ID: ${event.locals.user.id})`, 'info');
            return {
                user: event.locals.user
            };
        }
        
        // PRIORITY 2: Fall back to Better Auth session manager if hooks didn't set user
        log('[Layout.server] No user from hooks, trying Better Auth session manager', 'debug');
        
        // Apply local-variable-type-safety-protocol - validate auth object structure
        if (!hasSessionManager(auth)) {
            log('[Layout.server] Auth object missing sessionManager', 'error');
            return { user: null };
        }
        
        // Debug: Check what cookies are available
        const cookieHeader = event.request.headers.get('cookie');
        log(`[Layout.server] Cookie header: ${cookieHeader}`, 'debug');
        
        // Extract session token manually to compare with hooks
        const sessionToken = cookieHeader?.match(/better_auth_session=([^;]+)/)?.[1];
        log(`[Layout.server] Extracted session token: ${sessionToken ? `present (${sessionToken.substring(0, 8)}...)` : 'missing'}`, 'debug');
        
        // Try to get session from request
        log('[Layout.server] Calling Better Auth sessionManager.getSession...', 'debug');
        const authSession = await auth.sessionManager.getSession(event.request);
        log(`[Layout.server] Better Auth session result: ${authSession ? `found (userId: ${authSession.userId})` : 'not found'}`, 'debug');
        
        if (authSession) {
            // Validate adapter exists before using it
            if (!hasAdapter(auth)) {
                log('[Layout.server] Auth object missing adapter', 'error');
                return { user: null };
            }
            
            // Get user from session
            const betterAuthUser = await auth.adapter.getUserById(authSession.userId);
            
            log('[Layout.server] Raw betterAuthUser object: ' + JSON.stringify(betterAuthUser));
            
            if (betterAuthUser && typeof betterAuthUser === 'object') {
                log('[Layout.server] User loaded from session. Checking fields...', 'info');
                log(`[Layout.server] betterAuthUser.avatarUrl: ${betterAuthUser.avatarUrl}`);
                
                if (Object.prototype.hasOwnProperty.call(betterAuthUser, 'plan')) {
                    log(`[Layout.server] betterAuthUser.plan: ${JSON.stringify((/** @type {any} */ (betterAuthUser)).plan)}`);
                } else {
                    log('[Layout.server] betterAuthUser.plan property does not exist.');
                }
                if (Object.prototype.hasOwnProperty.call(betterAuthUser, 'metadata') && typeof betterAuthUser.metadata === 'object' && betterAuthUser.metadata !== null && Object.prototype.hasOwnProperty.call(betterAuthUser.metadata, 'plan')) {
                    log(`[Layout.server] betterAuthUser.metadata.plan: ${JSON.stringify((/** @type {any} */ (betterAuthUser.metadata)).plan)}`);
                } else {
                    log('[Layout.server] betterAuthUser.metadata.plan property does not exist or metadata is not an object.');
                }

                let updatedAtString;
                if (betterAuthUser.updatedAt instanceof Date) {
                    updatedAtString = betterAuthUser.updatedAt.toISOString();
                } else if (typeof betterAuthUser.updatedAt === 'string') {
                    updatedAtString = betterAuthUser.updatedAt;
                } else {
                    updatedAtString = new Date().toISOString();
                }

                let finalPlanString = 'Free'; 
                let rawPlanValue = null;
                
                if (Object.prototype.hasOwnProperty.call(betterAuthUser, 'plan') && (/** @type {any} */ (betterAuthUser)).plan !== undefined) {
                    rawPlanValue = (/** @type {any} */ (betterAuthUser)).plan;
                } else if (Object.prototype.hasOwnProperty.call(betterAuthUser, 'metadata') && 
                           typeof betterAuthUser.metadata === 'object' && 
                           betterAuthUser.metadata !== null && 
                           Object.prototype.hasOwnProperty.call(betterAuthUser.metadata, 'plan') && 
                           (/** @type {any} */ (betterAuthUser.metadata)).plan !== undefined) {
                    rawPlanValue = (/** @type {any} */ (betterAuthUser.metadata)).plan;
                }
                
                if (rawPlanValue && typeof rawPlanValue === 'object' && rawPlanValue !== null && Object.prototype.hasOwnProperty.call(rawPlanValue, 'name') && typeof (/** @type {any} */ (rawPlanValue)).name === 'string') {
                    finalPlanString = (/** @type {any} */ (rawPlanValue)).name;
                } else if (typeof rawPlanValue === 'string') {
                    finalPlanString = rawPlanValue;
                }

                const userForLocalsAndReturn = {
                    id: betterAuthUser.id,
                    email: betterAuthUser.email,
                    displayName: betterAuthUser.display_name || betterAuthUser.username || betterAuthUser.email.split('@')[0],
                    avatarUrl: betterAuthUser.avatarUrl, 
                    roles: Array.isArray(betterAuthUser.roles)
                        ? betterAuthUser.roles
                        : (betterAuthUser.metadata && Array.isArray(betterAuthUser.metadata.roles))
                            ? betterAuthUser.metadata.roles
                            : [],
                    plan: finalPlanString,
                    metadata: (typeof betterAuthUser.metadata === 'object' && betterAuthUser.metadata !== null) ? betterAuthUser.metadata : {}, 
                    updatedAt: updatedAtString 
                };
                log('[Layout.server] Constructed userForLocalsAndReturn: ' + JSON.stringify(userForLocalsAndReturn));
                
                // Set event.locals.user (this is for server-side use within hooks/endpoints for this request)
                event.locals.user = userForLocalsAndReturn;
                
                // Convert session to the format expected by App.Locals.session and set it
                event.locals.session = convertSessionForLocals(authSession);
                
                // Return the user object for the client-side SvelteKit data prop
                // Ensure the returned structure matches what $page.data.user expects
                return {
                    user: userForLocalsAndReturn
                };
            }
            
            log('[Layout.server] Session exists but user not found', 'warn');
        } else {
            log('[Layout.server] No active session', 'debug');
        }
    } catch (error) {
        // Apply local-variable-type-safety-protocol for error handling
        const errorMessage = error instanceof Error ? error.message : String(error);
        log(`[Layout.server] Error loading user: ${errorMessage}`, 'error');
        console.error('Layout server load error:', error);
    }
    
    // Return null user if no session or error
    return {
        user: null
    };
} 