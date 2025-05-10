/**
 * Profile API endpoint for updating user profile data
 * 
 * @created 06.01.25 | 10:35 AM PDT
 */

import { json } from '@sveltejs/kit';
import { auth } from '$lib/server/auth.js';
import { log } from '$lib/utils/log.js';
import { broadcastSyncUpdate } from '$lib/server/syncBroadcaster.js';

/**
 * Get the user profile data
 * 
 * @param {object} requestEvent - The SvelteKit request event
 * @param {object} requestEvent.locals - Local request-scoped data
 * @param {App.User} [requestEvent.locals.user] - Authenticated user from locals
 * @returns {Promise<Response>} JSON response with user profile data
 */
export async function GET({ locals }) {
  try {
    // Get the authenticated user
    const user = locals.user;
    
    if (!user) {
      return json({
        success: false,
        error: 'User not authenticated'
      }, { status: 401 });
    }
    
    return json({
      success: true,
      user: sanitizeUser(user)
    });
  } catch (error) {
    console.error('Error fetching profile:', error);
    return json({
      success: false,
      error: 'An error occurred while fetching profile data'
    }, { status: 500 });
  }
}

/**
 * Update user profile data
 * 
 * @param {object} requestEvent - The SvelteKit request event
 * @param {Request} requestEvent.request - The request object
 * @param {object} requestEvent.locals - Local request-scoped data
 * @param {App.User} [requestEvent.locals.user] - Authenticated user from locals
 * @returns {Promise<Response>} JSON response indicating success or failure
 */
export async function POST({ request, locals }) {
  try {
    console.log('[Profile API] Received profile update request');
    
    // Enhanced debug logging
    console.log('[Profile API] Request headers:', Object.fromEntries([...request.headers.entries()]));
    console.log('[Profile API] Cookie header:', request.headers.get('cookie'));
    console.log('[Profile API] CSRF token:', request.headers.get('X-CSRF-Token'));
    console.log('[Profile API] Authenticated user in locals:', locals.user?.id);
    
    // Add CORS headers for local development
    const headers = {
      'Access-Control-Allow-Origin': request.headers.get('origin') || '*',
      'Access-Control-Allow-Methods': 'POST, GET, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, X-CSRF-Token, Authorization, Cookie',
      'Access-Control-Allow-Credentials': 'true'
    };
    
    // Handle preflight
    if (request.method === 'OPTIONS') {
      return new Response(null, { 
        status: 204,
        headers
      });
    }
    
    // Get the authenticated user
    const user = locals.user;
    
    if (!user) {
      // Enhanced error logging for authentication failures
      console.warn('[Profile API] No authenticated user found in request locals');
      console.warn('[Profile API] Authorization header:', request.headers.get('Authorization'));
      console.warn('[Profile API] Session cookie exists:', !!request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1]);
      
      // Check if session exists in the cookie
      const sessionToken = request.headers.get('cookie')?.match(/better_auth_session=([^;]+)/)?.[1];
      if (sessionToken) {
        console.warn('[Profile API] Session token exists but user not authenticated. Length:', sessionToken.length);
        console.warn('[Profile API] Session token start:', sessionToken.substring(0, 10) + '...');
      }
      
      return json({
        success: false,
        error: 'User not authenticated'
      }, { 
        status: 401,
        headers
      });
    }
    
    // Get the data from the request
    let data;
    try {
      data = await request.json();
      console.log('[Profile API] Request data:', data);
    } catch (parseError) {
      console.error('[Profile API] Error parsing request JSON:', parseError);
      return json({
        success: false,
        error: 'Invalid request data'
      }, { 
        status: 400,
        headers
      });
    }
    
    // Ensure we have the user ID
    if (!data.id || data.id !== user.id) {
      console.warn('[Profile API] ID mismatch or missing. Request ID:', data.id, 'User ID:', user.id);
      return json({
        success: false,
        error: 'Invalid user ID'
      }, { 
        status: 400,
        headers
      });
    }
    
    log('[Profile API] Updating profile', 'info');
    
    // For now, we'll just merge the data with the existing user
    const updatedUser = {
      ...user,
      ...data,
      // Don't allow updating these fields
      roles: user.roles, // Keep original roles
      id: user.id, // Keep original ID
    };
    
    console.log('[Profile API] Updated user data:', updatedUser);
    
    // Broadcast the update to all connected clients
    try {
      console.log(`[Profile API] Broadcasting sync update for user ${updatedUser.id}`);
      broadcastSyncUpdate(updatedUser.id, updatedUser.updatedAt);
    } catch (broadcastError) {
      console.error('[Profile API] Error broadcasting update:', broadcastError);
      // Continue without failing the update
    }
    
    return json({
      success: true,
      user: sanitizeUser(updatedUser)
    }, { headers });
  } catch (error) {
    console.error('[Profile API] Error updating profile:', error);
    return json({
      success: false,
      error: 'An error occurred while updating profile'
    }, { status: 500 });
  }
}

/**
 * Sanitize user object for public API responses
 * 
 * @param {App.User|null} user The user object to sanitize
 * @returns {Partial<App.User>|null} Sanitized user object or null
 */
function sanitizeUser(user) {
  if (!user) return null;
  
  // Include only the fields that should be exposed
  return {
    id: user.id,
    email: user.email,
    displayName: user.displayName,
    avatarUrl: user.avatarUrl,
    gravatarUrl: user.gravatarUrl,
    preferences: user.preferences,
    roles: user.roles,
    plan: user.plan,
    updatedAt: user.updatedAt
  };
} 