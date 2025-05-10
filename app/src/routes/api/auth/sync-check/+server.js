/**
 * Simple check endpoint that serves as a fallback for /api/auth/sync
 * This endpoint will never 404 and can be used to verify API connectivity
 * 
 * @created 2025-05-09 | 10:35 AM PDT
 */

import { json } from '@sveltejs/kit';

/** @type {import('./$types').RequestHandler} */
export async function GET({ locals }) {
  const user = locals.user;
  console.log('[Sync Check API] Received GET request', user ? 'User found in locals' : 'No user in locals');
  
  // Simply return a success message with valid=true and the status of the session
  return json({
    valid: true,
    session_exists: !!locals.session,
    user_exists: !!user,
    timestamp: new Date().toISOString()
  });
}

/** @type {import('./$types').RequestHandler} */
export async function POST({ request, locals }) {
  // Get the request body
  const body = await request.json().catch(() => ({}));
  
  console.log('[Sync Check API] Received POST request', 
    locals.user ? 'User found in locals' : 'No user in locals');
  
  // Return similar response as GET but acknowledge the POST data
  return json({
    valid: true,
    session_exists: !!locals.session,
    user_exists: !!locals.user,
    received_data: !!Object.keys(body).length,
    timestamp: new Date().toISOString()
  });
}

/** @type {import('./$types').RequestHandler} */
export async function OPTIONS() {
  // Handle OPTIONS for CORS preflight
  return new Response(null, {
    status: 204,
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization'
    }
  });
} 