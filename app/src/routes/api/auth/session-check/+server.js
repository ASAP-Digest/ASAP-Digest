import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth';
import { log } from '$lib/utils/log';

/**
 * @typedef {import('$lib/types/better-auth').BetterAuth} BetterAuth
 * @typedef {import('$lib/types/better-auth').SessionResponse} SessionResponse
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
 * Handles GET requests to check the current session status
 * @param {import('@sveltejs/kit').RequestEvent} event The request event
 * @returns {Promise<Response>} JSON response with session data
 */
export async function GET(event) {
    try {
        log('[API /session-check] Checking current session status');
        
        // Apply local-variable-type-safety-protocol - validate auth object structure
        if (!hasSessionManager(auth)) {
            log('[API /session-check] Auth object missing sessionManager', 'error');
            return json({ 
                authenticated: false,
                error: 'auth_configuration_error',
                message: 'Auth system not properly configured'
            }, { status: 500 });
        }
        
        // Get session from request cookie
        const session = await auth.sessionManager.getSession(event.request);
        
        if (!session) {
            log('[API /session-check] No active session found');
            return json({ 
                authenticated: false,
                message: 'No active session'
            });
        }
        
        // Validate adapter exists before using it
        if (!hasAdapter(auth)) {
            log('[API /session-check] Auth object missing adapter', 'error');
            return json({ 
                authenticated: false,
                error: 'auth_configuration_error',
                message: 'Auth system not properly configured'
            }, { status: 500 });
        }
        
        // Session exists, now get the user associated with it
        const user = await auth.adapter.getUserById(session.userId);
        
        if (!user) {
            // Session exists but user doesn't - potential data inconsistency
            log('[API /session-check] Session found for non-existent user ID: ' + session.userId, 'warn');
            // Optionally delete the invalid session
            // await auth.adapter.deleteSession(session.token);
            return json({ 
                authenticated: false,
                message: 'Invalid session (user not found)'
            });
        }
        
        // Return filtered user data - avoid sending sensitive fields
        log('[API /session-check] Valid session found for user: ' + user.email);
        
        /** @type {SessionResponse} */
        const response = {
            authenticated: true,
            user: {
                id: user.id,
                email: user.email,
                displayName: user.display_name || user.username || user.email.split('@')[0],
                roles: Array.isArray(user.roles) ? user.roles : 
                      (user.metadata && Array.isArray(user.metadata.roles)) ? user.metadata.roles : 
                      ['user'],
                updatedAt: new Date().toISOString()
            }
        };
        
        return json(response);
    } catch (error) {
        // Apply local-variable-type-safety-protocol for error handling
        const errorMessage = error instanceof Error ? error.message : String(error);
        log('[API /session-check] Error checking session: ' + errorMessage, 'error');
        return json({ 
            authenticated: false, 
            error: 'session_check_failed',
            message: 'Error verifying session'
        }, { 
            status: 500 
        });
    }
} 