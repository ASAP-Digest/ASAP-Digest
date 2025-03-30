import { dev } from '$app/environment';

/**
 * Generate a random CSRF token
 * @returns {string} - The generated CSRF token
 */
function generateCSRFToken() {
    const array = new Uint8Array(32);
    crypto.getRandomValues(array);
    return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}

/**
 * Handle GET request to generate and set CSRF token
 * @param {Request} request - The request object
 * @returns {Response} - The response object with CSRF token
 */
export async function GET() {
    try {
        const token = generateCSRFToken();
        
        // Create secure cookie with the CSRF token
        const cookie = [
            `csrf_token=${token}`,
            'Path=/',
            'SameSite=Lax',
            !dev && 'Secure'
        ].filter(Boolean).join('; ');

        return new Response(JSON.stringify({ token }), {
            status: 200,
            headers: {
                'Content-Type': 'application/json',
                'Set-Cookie': cookie
            }
        });
    } catch (error) {
        console.error('[Auth API] Error generating CSRF token:', error);
        return new Response(JSON.stringify({ error: 'Internal server error' }), {
            status: 500,
            headers: { 'Content-Type': 'application/json' }
        });
    }
} 