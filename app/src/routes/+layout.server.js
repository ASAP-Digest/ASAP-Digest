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
        // Apply local-variable-type-safety-protocol - validate auth object structure
        if (!hasSessionManager(auth)) {
            log('[Layout.server] Auth object missing sessionManager', 'error');
            return { user: null };
        }
        
        // Try to get session from request
        const authSession = await auth.sessionManager.getSession(event.request);
        
        if (authSession) {
            // Validate adapter exists before using it
            if (!hasAdapter(auth)) {
                log('[Layout.server] Auth object missing adapter', 'error');
                return { user: null };
            }
            
            // Get user from session
            const betterAuthUser = await auth.adapter.getUserById(authSession.userId);
            
            // Log the raw betterAuthUser object for debugging
            log('[Layout.server] betterAuthUser: ' + JSON.stringify(betterAuthUser));
            
            if (betterAuthUser) {
                log('[Layout.server] User loaded from session', 'info');
                
                // Always include roles as an array
                const userForLocals = {
                    id: betterAuthUser.id,
                    email: betterAuthUser.email,
                    displayName: betterAuthUser.display_name || betterAuthUser.username || betterAuthUser.email.split('@')[0],
                    avatarUrl: betterAuthUser.avatarUrl,
                    roles: Array.isArray(betterAuthUser.roles)
                        ? betterAuthUser.roles
                        : Array.isArray(betterAuthUser.metadata?.roles)
                            ? betterAuthUser.metadata.roles
                            : [],
                    // Add any other properties you need
                };
                log('[Layout.server] userForLocals: ' + JSON.stringify(userForLocals));
                
                // Prepare the object to return
                const loaderReturn = {
                    user: {
                        ...userForLocals,
                        testProp: 'should-be-visible',
                        rolesTest: 'should-be-visible'
                    }
                };
                const loaderReturnCloned = JSON.parse(JSON.stringify(loaderReturn));
                log('[Layout.server] Loader return value (final, deep clone): ' + JSON.stringify(loaderReturnCloned));
                
                event.locals.user = userForLocals;
                
                // Convert session to the format expected by App.Locals.session
                event.locals.session = convertSessionForLocals(authSession);
                
                return loaderReturnCloned;
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