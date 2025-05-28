/**
 * Better Auth get-session endpoint
 * Handles session retrieval for Better Auth client
 */

import { auth } from '$lib/server/auth';
import { json } from '@sveltejs/kit';

/**
 * Handle GET requests for session data
 * @type {import('@sveltejs/kit').RequestHandler}
 */
export async function GET({ request, cookies }) {
    try {
        console.log('[get-session] Processing session request');
        
        // Try to get session using our custom session manager
        const session = await auth.sessionManager.getSession(request);
        
        if (!session) {
            console.log('[get-session] No session found');
            return json({ user: null, session: null }, { status: 200 });
        }
        
        console.log(`[get-session] Session found for user: ${session.userId}`);
        
        // Return session data in the format Better Auth client expects
        return json({
            user: session.user,
            session: {
                id: session.sessionId,
                userId: session.userId,
                expiresAt: session.expiresAt,
                createdAt: session.createdAt
            }
        }, { status: 200 });
        
    } catch (error) {
        console.error('[get-session] Error:', error);
        return json(
            { 
                error: 'Failed to get session', 
                details: error instanceof Error ? error.message : String(error) 
            },
            { status: 500 }
        );
    }
} 