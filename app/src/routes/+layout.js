/**
 * @file +layout.js
 * @description SvelteKit client-side layout loader with Better Auth user normalization and rigorous typing.
 */
import { browser } from '$app/environment';
import { invalidateAll } from '$app/navigation';
import { log } from '$lib/utils/log.js';
import { user as userStore } from '$lib/stores/user.js';

/**
 * @typedef {Object} UserMetadata
 * @property {string[]} [roles]
 * @property {string} [nickname]
 * @property {number} [wp_user_id]
 * @property {string} [description]
 */

/**
 * @typedef {Object} BetterAuthUser
 * @property {string} id
 * @property {string=} displayName
 * @property {string=} email
 * @property {string=} avatarUrl
 * @property {string[]=} roles
 * @property {UserMetadata=} metadata
 * @property {string=} plan
 * @property {string=} updatedAt
 */

/**
 * @typedef {Object} LoadData
 * @property {BetterAuthUser|null} [user]
 * @property {boolean} [preventRefresh]
 */

/**
 * Normalize a user object from any source (server, session, or WordPress)
 * to ensure it has consistent structure and properties.
 * 
 * @param {any} inputUser - The user object to normalize
 * @returns {BetterAuthUser} - Normalized user object
 */
function normalizeUser(inputUser) {
  // Create an empty user object with default values to satisfy the BetterAuthUser type
  // This ensures we never return null which would violate our type contract
  const emptyUser = {
    id: '',
    email: '',
    displayName: '',
    avatarUrl: '',
    roles: [],
    metadata: {},
    plan: 'Free',
    updatedAt: new Date().toISOString()
  };
  
  if (!inputUser) return emptyUser;
  
  // Log the input to diagnose issues
  log(`[+layout.js] Normalizing user object: ${JSON.stringify(inputUser)}`, 'debug');
  
  // Create a case-normalized properties map
  const props = {};
  
  // Map all properties to lowercase for case-insensitive normalization
  Object.keys(inputUser).forEach(key => {
    const lowerKey = key.toLowerCase();
    props[lowerKey] = inputUser[key];
  });
  
  // Get potential nested roles
  const metadataRoles = props.metadata?.roles || [];
  const rolesFromMeta = Array.isArray(metadataRoles) ? metadataRoles : [];
  
  // Get direct roles property (could be uppercase)
  const directRoles = props.roles || inputUser.ROLES || inputUser.Roles || [];
  const normalizedRoles = Array.isArray(directRoles) ? directRoles : [];
  
  // Combine roles from both sources (unique values only)
  const combinedRoles = [...new Set([...normalizedRoles, ...rolesFromMeta])];
  
  // Construct final normalized user with defaults for optional properties
  const normalizedUser = {
    id: props.id || inputUser.ID || inputUser.Id || '',
    email: props.email || inputUser.EMAIL || inputUser.Email || '',
    displayName: props.displayname || inputUser.DISPLAYNAME || inputUser.DisplayName || props.name || '',
    avatarUrl: props.avatarurl || inputUser.AVATARURL || inputUser.AvatarUrl || '',
    roles: combinedRoles,
    metadata: props.metadata || {},
    plan: props.plan || 'Free', // Default value for plan
    updatedAt: props.updatedat || inputUser.UPDATEDAT || new Date().toISOString() // Default current date for updatedAt
  };
  
  // Log the normalized result
  log(`[+layout.js] Normalized user result: ${JSON.stringify(normalizedUser)}`, 'debug');
  
  return normalizedUser;
}

/**
 * Client-side layout load function.
 * Fetches user data from session, and refreshes if needed.
 * 
 * @param {Object} event - SvelteKit event object.
 * @param {Object} event.data - Server-provided data.
 * @param {Function} event.fetch - Fetch function.
 * @param {Function} event.depends - Dependency function.
 * @returns {Promise<Object>} - Layout data.
 */
export async function load({ data, fetch, depends }) {
  depends('app:user');
  depends('app:session');

  if (!browser) {
    return data;
  }

  if (data && data.preventRefresh === true) {
    if (data.user) {
      const normalizedUser = normalizeUser(data.user);
      userStore.set(normalizedUser);
    }
    return data;
  }

  if (data && 'user' in data && data.user) {
    log('[+layout.js] User data available from server load', 'info');
    const normalizedUser = normalizeUser(data.user);
    userStore.set(normalizedUser);
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
    
    if (sessionData && sessionData.success === true && sessionData.user) {
      log('[+layout.js] Found active session', 'info');
      const normalizedUser = normalizeUser(sessionData.user);
      userStore.set(normalizedUser);
      
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
        const normalizedUser = normalizeUser(wpData.user);
        userStore.set(normalizedUser);
        
        // Return the normalized WordPress user for page loads
        return {
          user: normalizedUser,
          fallbackAuth: true
        };
      } else {
        log('[+layout.js] No WordPress session found, user is not logged in', 'info');
        userStore.set(null);
        return { user: null, fallbackAuth: false };
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