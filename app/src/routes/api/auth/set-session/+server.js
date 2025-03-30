import { dev } from '$app/environment';

/**
 * Handle POST request to set session token in secure HTTP-only cookie
 * @param {Request} request - The request object
 * @returns {Response} - The response object
 */
export async function POST({ request }) {
    try {
        const { token } = await request.json();
        
        if (!token) {
            return new Response(JSON.stringify({ error: 'No token provided' }), {
                status: 400,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // Create secure HTTP-only cookie with the session token
        const cookie = [
            `better_auth_session=${token}`,
            'HttpOnly',
            'Path=/',
            'SameSite=Lax',
            !dev && 'Secure'
        ].filter(Boolean).join('; ');

        return new Response(JSON.stringify({ success: true }), {
            status: 200,
            headers: {
                'Content-Type': 'application/json',
                'Set-Cookie': cookie
            }
        });
    } catch (error) {
        console.error('[Auth API] Error setting session:', error);
        return new Response(JSON.stringify({ error: 'Internal server error' }), {
            status: 500,
            headers: { 'Content-Type': 'application/json' }
        });
    }
} 