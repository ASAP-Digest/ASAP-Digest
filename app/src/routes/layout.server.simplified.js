import { auth } from '$lib/server/auth';
import { log } from '$lib/utils/log';

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
 * Simplified server load function using only Better Auth
 * @param {object} event The server load event
 * @param {App.Locals} event.locals Access to request-scoped data
 * @param {Request} event.request The request object
 * @returns {Promise<{ user: App.User | null }>} Data for all pages
 */
export async function load(event) {
    try {
        log('[Layout.server.simplified] Starting auth check with Better Auth only', 'info');
        
        // Validate auth object structure
        if (!hasSessionManager(auth)) {
            log('[Layout.server.simplified] Auth object missing sessionManager', 'error');
            return { user: null };
        }
        
        // Get session using Better Auth (with our custom session manager)
        const authSession = await auth.sessionManager.getSession(event.request);
        
        if (authSession) {
            log(`[Layout.server.simplified] Session found: ${authSession.userId}`, 'info');
            
            // Validate adapter exists
            if (!hasAdapter(auth)) {
                log('[Layout.server.simplified] Auth object missing adapter', 'error');
                return { user: null };
            }
            
            // Get user from session
            const betterAuthUser = await auth.adapter.getUserById(authSession.userId);
            
            if (betterAuthUser && typeof betterAuthUser === 'object') {
                log(`[Layout.server.simplified] User loaded: ${betterAuthUser.email}`, 'info');
                
                // Convert to App.User format
                const wpUserId = betterAuthUser.metadata?.wp_user_id;
                const normalizedWpUserId = typeof wpUserId === 'number' ? wpUserId : 
                                         (typeof wpUserId === 'string' ? parseInt(wpUserId, 10) : undefined);
                
                const user = {
                    id: betterAuthUser.id,
                    email: betterAuthUser.email,
                    displayName: betterAuthUser.displayName || betterAuthUser.name || betterAuthUser.username || betterAuthUser.email.split('@')[0],
                    avatarUrl: betterAuthUser.avatarUrl,
                    roles: betterAuthUser.roles || [],
                    plan: 'Free', // Default plan, will be enhanced later
                    metadata: betterAuthUser.metadata || {},
                    updatedAt: typeof betterAuthUser.updatedAt === 'string' 
                        ? betterAuthUser.updatedAt 
                        : (betterAuthUser.updatedAt instanceof Date 
                            ? betterAuthUser.updatedAt.toISOString() 
                            : new Date().toISOString()),
                    wp_user_id: normalizedWpUserId,
                    wpUserId: normalizedWpUserId
                };
                
                // Set in locals for server-side use
                event.locals.user = user;
                
                log(`[Layout.server.simplified] Returning user: ${user.email} (wp_user_id: ${user.wp_user_id})`, 'info');
                return { user };
            }
            
            log('[Layout.server.simplified] Session exists but user not found', 'warn');
        } else {
            log('[Layout.server.simplified] No active session found', 'debug');
        }
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        log(`[Layout.server.simplified] Error loading user: ${errorMessage}`, 'error');
        console.error('Simplified layout server load error:', error);
    }
    
    // Return null user if no session or error
    return { user: null };
} 