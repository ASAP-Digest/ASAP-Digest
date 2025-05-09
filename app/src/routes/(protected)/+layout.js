/**
 * Protected routes layout
 * Ensures authentication for all protected routes with local-first capabilities
 * 
 * @created 07.06.25 | 03:04 PM PDT
 * @fileoverview Layout load function for protected routes with Local First integration
 */

import { authMiddleware, refreshAuthState } from '$lib/utils/auth-persistence';
import { error, redirect } from '@sveltejs/kit';

/**
 * Layout load function for protected routes
 * 
 * @type {import('./$types').LayoutLoad}
 */
export async function load(event) {
  // Add dependency for auth state changes
  event.depends('auth:state');
  
  try {
    // Use the auth middleware to handle authentication
    const authData = await authMiddleware(event);
    
    // If authMiddleware didn't redirect, we're authenticated
    return {
      user: authData.user,
      usingLocalAuth: authData.usingLocalAuth || false
    };
  } catch (err) {
    // Handle authentication errors by redirecting to login
    console.error('Authentication error in protected route:', err);
    throw redirect(302, '/login');
  }
} 