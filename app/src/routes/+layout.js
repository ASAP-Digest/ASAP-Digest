/**
 * @file +layout.js
 * @description SvelteKit client-side layout loader with Better Auth user normalization and rigorous typing.
 */
import { browser } from '$app/environment';
import { invalidateAll } from '$app/navigation';
import { log } from '$lib/utils/log.js';
import { user as userStore, getUserData } from '$lib/stores/user.js';
import { authStore } from '$lib/utils/auth-persistence';

/**
 * @typedef {Object} UserMetadata
 * @property {string[]} [roles]
 * @property {string} [nickname]
 * @property {number} [wp_user_id]
 * @property {string} [description]
 */

/**
 * Note: Using the User type from user.js store for consistent typing.
 * @typedef {import('$lib/stores/user').User} NormalizedUser
 */

/**
 * @typedef {Object} LoadData
 * @property {NormalizedUser|null} [user]
 * @property {boolean} [preventRefresh]
 * @property {boolean} [fallbackAuth] - Indicates if fallback auth attempt was made
 * @property {string} [error] - Error message if authentication failed
 */

/**
 * Normalize a user object from any source (server, session, or WordPress)
 * using the comprehensive getUserData helper for consistent processing.
 * 
 * @param {any} inputUser - The user object to normalize
 * @returns {import('$lib/stores/user').User} - Normalized user object compatible with userStore
 */
function normalizeUser(inputUser) {
  if (!inputUser) {
    // Return empty user object with default values to satisfy the User type
    return {
    id: '',
    email: '', // Always provide a string value for email (empty string if not available)
    displayName: '',
    avatarUrl: '',
    roles: [],
    metadata: {},
    plan: { name: 'Free' }, // Must be an object with name property to match UserPlan type
    updatedAt: new Date().toISOString()
  };
  }
  
  // Log the input to diagnose issues
  log(`[+layout.js] Normalizing user object: ${JSON.stringify(inputUser)}`, 'debug');
  
  // Use the comprehensive getUserData helper for normalization
  const userDataHelper = getUserData(inputUser);
  const normalizedUser = userDataHelper.toJSON();
  
  // Ensure updatedAt is set for compatibility
  if (!normalizedUser.updatedAt) {
    normalizedUser.updatedAt = new Date().toISOString();
  }
  
  // Log the normalized result
  log(`[+layout.js] Normalized user result: ${JSON.stringify(normalizedUser)}`, 'debug');
  
  return normalizedUser;
}

/**
 * @param {object} loadEvent - The SvelteKit route load event
 * @param {function} loadEvent.fetch - SvelteKit fetch function
 * @param {URL} loadEvent.url - Request URL 
 * @param {any} loadEvent.data - Server provided data
 * @param {App.Locals} loadEvent.locals - Server locals (includes user if authenticated)
 * @param {boolean} loadEvent.isSubRequest - Whether this is a subrequest
 * @param {function} loadEvent.depends - Function to declare dependencies
 * @returns {Promise<LoadData>} Data for the route
 */
export async function load({ data, fetch, depends }) {
  depends('app:user');
  depends('app:session');

  if (!browser) {
    // On the server, data is passed directly from +layout.server.js
    // We might still want to ensure event.locals.user is set for server-side rendering of components using it.
    // However, userStore and authStore are client-side stores primarily.
    return data;
  }

  // Client-side logic from here
  if (data && data.preventRefresh === true) {
    if (data.user) {
      const normalizedUser = normalizeUser(data.user);
      userStore.set(normalizedUser);
      authStore.set(normalizedUser);
    }
    return data;
  }

  if (data && 'user' in data && data.user) {
    log('[+layout.js] User data available from server load', 'info');
    const normalizedUser = normalizeUser(data.user);
    userStore.set(normalizedUser);
    authStore.set(normalizedUser);
    
    // Persist to localStorage immediately
    try {
      localStorage.setItem('asap_digest_last_auth', JSON.stringify(normalizedUser));
      console.log('[+layout.js] Persisted server user to localStorage');
    } catch (storageError) {
      console.warn('[+layout.js] Error saving server user to localStorage:', storageError);
    }
    
    return data;
  }

  try {
    log('[+layout.js] No user data from server, checking session status...', 'info');
    
    // Request session check from our session endpoint
    const res = await fetch('/api/auth/session', {
      method: 'GET',
      headers: {
        'x-better-auth-client': 'app-client'
      }
    });
    
    const sessionData = await res.json();
    
    if (sessionData && sessionData.authenticated === true && sessionData.user) {
      log('[+layout.js] Found active session', 'info');
      const normalizedUser = normalizeUser(sessionData.user);
      userStore.set(normalizedUser);
      authStore.set(normalizedUser);
      
      // Save to localStorage for protected routes to access
      try {
        localStorage.setItem('asap_digest_last_auth', JSON.stringify(normalizedUser));
        console.log('[+layout.js] Saved user to localStorage for protected routes');
      } catch (storageError) {
        console.warn('[+layout.js] Error saving to localStorage:', storageError);
      }
      
      // Return the normalized user session for page loads
      return {
        user: normalizedUser,
        fallbackAuth: false
      };
    } else {
      log('[+layout.js] No session found, checking WordPress session...', 'info');
      
      // Try WordPress auto-login (just a GET request, no credentials)
      const wpRes = await fetch('/api/auth/check-wp-session', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ clientRequest: true })
      });
      
      const wpData = await wpRes.json();
      
      if (wpData && wpData.success && wpData.user) {
        log('[+layout.js] WordPress auto-login successful!', 'info');
        
        // CRITICAL FIX: Ensure wp_user_id is preserved from WordPress response
        console.log('[+layout.js] Raw WordPress user data:', wpData.user);
        
        // Extract wp_user_id from various possible locations in the WordPress response
        const wpUserId = wpData.user.wp_user_id || 
                         wpData.user.wpUserId || 
                         wpData.user.id || 
                         (wpData.user.metadata && wpData.user.metadata.wp_user_id) ||
                         null;
        
        console.log('[+layout.js] Extracted wp_user_id:', wpUserId);
        
        // Ensure the user object has wp_user_id before normalization
        const userWithWpId = {
          ...wpData.user,
          wp_user_id: wpUserId,
          wpUserId: wpUserId
        };
        
        const normalizedUser = normalizeUser(userWithWpId);
        console.log('[+layout.js] Normalized user with wp_user_id:', normalizedUser.wp_user_id);
        
        userStore.set(normalizedUser);
        authStore.set(normalizedUser);
        
        // Save to localStorage for protected routes to access
        try {
          localStorage.setItem('asap_digest_last_auth', JSON.stringify(normalizedUser));
          console.log('[+layout.js] Saved WordPress user to localStorage for protected routes');
        } catch (storageError) {
          console.warn('[+layout.js] Error saving to localStorage:', storageError);
        }
        
        // Return the normalized WordPress user for page loads
        return {
          user: normalizedUser,
          fallbackAuth: true
        };
      } else {
        log('[+layout.js] No WordPress session found, user is not logged in', 'info');
        // Don't clear existing user data if there was an error - maintain current state
        // Only clear if we're certain there's no session
        if (!data?.user) {
          userStore.set(null);
          authStore.set(null);
        }
        return { user: data?.user || null, fallbackAuth: false };
      }
    }
  } catch (error) {
    // Apply type guard as per type-definition-management-protocol section 6.2
    const errorMessage = error instanceof Error ? error.message : String(error);
    log(`[+layout.js] Error checking session or WordPress auto-login: ${errorMessage}`, 'error');
    
    // Don't set user to null on error, maintain previous state
    // Only return error state for this page load
    return {
      user: null,
      fallbackAuth: false,
      error: errorMessage
    };
  }
} 