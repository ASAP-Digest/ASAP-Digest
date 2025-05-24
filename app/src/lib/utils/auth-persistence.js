/**
 * Auth Persistence Utility
 * Implements Local First principles for auth state management
 * 
 * @created 07.06.25 | 02:53 PM PDT
 */

import { browser } from '$app/environment';
import { writable, derived } from 'svelte/store';
import { goto } from '$app/navigation';
import { user as userRootStore } from '$lib/stores/user.js';

// Constants
const LOCAL_AUTH_KEY = 'asap_digest_auth';
const LOCAL_AUTH_VERSION = 1;
const SESSION_DURATION = 7 * 24 * 60 * 60 * 1000; // 7 days in ms
const SYNC_INTERVAL = 5 * 60 * 1000; // 5 minutes in ms

/**
 * @typedef {Object} AuthDataPlan
 * @property {string} name
 */

/**
 * @typedef {Object} AuthDataMetadata
 * @property {string[]} [roles]
 * @property {Object} [preferences] - User preferences (can be nested in metadata)
 * // Add other potential metadata fields here
 */

/**
 * @typedef {Object} AuthData
 * @property {string} id - User ID
 * @property {string} email - User email
 * @property {string} [displayName] - User display name
 * @property {string} [avatarUrl] - User avatar URL
 * @property {AuthDataPlan | string} [plan] - User plan information
 * @property {AuthDataMetadata} [metadata] - Additional user metadata, including roles and preferences
 * @property {Object} [preferences] - User preferences (Better Auth standard field)
 * @property {number} [lastSynced] - Timestamp of last server sync
 * @property {number} [version] - Schema version
 * @property {string[]} [roles] - Deprecated: prefer metadata.roles, but handle for backward compatibility if necessary
 */

/**
 * Sync data between authStore and user store
 * This ensures both stores have the same data for consistency
 * @param {AuthData|null} authDataValue - User data from authStore (AuthData type)
 * @param {boolean} [forceUpdate=false] - Whether to force update userRootStore even if data seems unchanged
 */
function syncUserStores(authDataValue, forceUpdate = false) {
  if (browser) {
    /** @type {import('$lib/stores/user').User | null} */
    let currentUserRootData = null;
    userRootStore.subscribe(value => { currentUserRootData = value; })(); 

    /** @type {import('$lib/stores/user').User | null} */
    let userToSetInRoot = null;

    if (authDataValue && typeof authDataValue === 'object' && typeof authDataValue.id === 'string') {
      /** @type {import('$lib/stores/user').UserPlan | undefined} */
      let finalPlanForRootStore;
      const rawPlan = authDataValue.plan;
      if (typeof rawPlan === 'object' && rawPlan !== null && typeof rawPlan.name === 'string') {
        finalPlanForRootStore = { name: rawPlan.name }; 
      } else if (typeof rawPlan === 'string') {
        finalPlanForRootStore = { name: rawPlan }; 
      } else {
        finalPlanForRootStore = { name: 'Free' }; 
      }
      
      /** @type {string[] | undefined} */
      let finalRolesForRootStore;
      if (authDataValue.metadata && Array.isArray(authDataValue.metadata.roles)) {
        finalRolesForRootStore = authDataValue.metadata.roles;
      } else if (Array.isArray(authDataValue.roles)) { // Fallback for potential direct roles on AuthData
        finalRolesForRootStore = authDataValue.roles;
        console.warn('[Auth Store] Using direct roles from AuthData, consider moving to metadata.roles');
      } else {
        finalRolesForRootStore = undefined; 
      }

      // Extract metadata following Better Auth protocols
      /** @type {Object | undefined} */
      let finalMetadataForRootStore;
      if (authDataValue.metadata && typeof authDataValue.metadata === 'object' && authDataValue.metadata !== null) {
        // Follow Better Auth metadata structure - preserve all metadata fields
        finalMetadataForRootStore = {
          ...authDataValue.metadata,
          // Ensure roles are properly structured in metadata
          roles: finalRolesForRootStore || authDataValue.metadata.roles || []
        };
      } else {
        // Create minimal metadata structure if none exists
        finalMetadataForRootStore = {
          roles: finalRolesForRootStore || []
        };
      }

      // Extract preferences following Better Auth protocols
      /** @type {Object | undefined} */
      let finalPreferencesForRootStore;
      if (authDataValue.preferences && typeof authDataValue.preferences === 'object' && authDataValue.preferences !== null) {
        finalPreferencesForRootStore = authDataValue.preferences;
      } else if (authDataValue.metadata && authDataValue.metadata.preferences) {
        // Check if preferences are nested in metadata (some Better Auth configurations)
        finalPreferencesForRootStore = authDataValue.metadata.preferences;
      }

      userToSetInRoot = {
        id: authDataValue.id,
        email: authDataValue.email, 
        displayName: authDataValue.displayName,
        avatarUrl: authDataValue.avatarUrl,
        plan: finalPlanForRootStore,
        updatedAt: typeof authDataValue.lastSynced === 'number' ? new Date(authDataValue.lastSynced).toISOString() : new Date().toISOString(),
        roles: finalRolesForRootStore,
        metadata: finalMetadataForRootStore,
        ...(finalPreferencesForRootStore && { preferences: finalPreferencesForRootStore })
      };
    } else if (authDataValue === null) {
      userToSetInRoot = null;
    }

    if (forceUpdate || JSON.stringify(userToSetInRoot) !== JSON.stringify(currentUserRootData)) {
      console.log(`[Auth Store] Syncing user data to userRootStore (Forced: ${forceUpdate}). Data:`, JSON.stringify(userToSetInRoot));
      userRootStore.set(userToSetInRoot);
    } else {
      console.log('[Auth Store] User data unchanged for userRootStore, skipping sync');
    }
  }
}

/**
 * Creates a persistent auth store with local-first capabilities
 * @returns {import('svelte/store').Writable<AuthData | null> & { syncWithServer: (force?: boolean) => Promise<boolean>, login: (credentials: Object) => Promise<Object>, logout: () => Promise<Object>, clear: () => void, get: () => AuthData | null }} Auth store and utility methods
 */
function createAuthStore() {
  /** @type {AuthData | null} */
  let initialValue = null;
  if (browser) {
    try {
      const storedData = localStorage.getItem(LOCAL_AUTH_KEY);
      if (storedData) {
        const parsedData = JSON.parse(storedData);
        if (parsedData && parsedData.version === LOCAL_AUTH_VERSION && typeof parsedData.id === 'string') {
          initialValue = /** @type {AuthData} */ (parsedData);
          if (initialValue) {
            console.log('[Auth Store] Initialized from localStorage:', initialValue.id);
            syncUserStores(initialValue, true);
          } else {
            initialValue = null;
            console.warn('[Auth Store] localStorage parsed data was invalid after cast.');
          }
        } else {
          initialValue = null; 
        }
      }
    } catch (error) {
      console.error('Error loading auth data from localStorage:', error);
      initialValue = null;
    }
  }
  const { subscribe, set: svelteSet, update: svelteUpdate } = writable(initialValue);
  
  if (browser) {
    const syncIntervalId = setInterval(() => { syncWithServer(false); }, SYNC_INTERVAL);
    window.addEventListener('beforeunload', () => { clearInterval(syncIntervalId); });
  }
  
  /**
   * Persist auth data to localStorage or clear it if data is null.
   * @param {AuthData | null} data - Auth data to persist or null to clear.
   * @param {boolean} [forceSyncToRoot=false] - Whether to force sync to userRootStore.
   */
  function persistToLocalStorage(data, forceSyncToRoot = false) {
    if (browser) {
      try {
        if (data === null) {
          localStorage.removeItem(LOCAL_AUTH_KEY);
          console.log('[Auth Store] Cleared localStorage because data is null');
        } else if (typeof data === 'object' && data !== null && typeof data.id === 'string' && data.id) {
          const storageData = { ...data, lastSynced: Date.now(), version: LOCAL_AUTH_VERSION };
          localStorage.setItem(LOCAL_AUTH_KEY, JSON.stringify(storageData));
          console.log('[Auth Store] Persisted to localStorage:', data.id);
        } else {
          console.warn('[Auth Store] Attempted to persist invalid data. Clearing localStorage.', data);
          localStorage.removeItem(LOCAL_AUTH_KEY);
          syncUserStores(null, forceSyncToRoot); 
          return;
        }
        syncUserStores(data, forceSyncToRoot);
      } catch (error) {
        console.error('Error interacting with localStorage:', error);
      }
    }
  }
  
  /**
   * Sync with server - fetch latest auth state and update local state
   * @param {boolean} [force=false] - Force sync even if within sync interval
   * @returns {Promise<boolean>} Success status
   */
  async function syncWithServer(force = false) {
    if (!browser) return false;
    try {
      const currentUser = get(); 
      const now = Date.now();
      if (!force && currentUser && currentUser.lastSynced && (now - currentUser.lastSynced < SYNC_INTERVAL)) {
        return true;
      }
      const response = await fetch('/api/auth/session', { headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }, credentials: 'include' });
      if (!response.ok) throw new Error('Failed to fetch session');
      const responseData = await response.json();
      if (responseData.authenticated && typeof responseData.user === 'object' && responseData.user !== null && typeof responseData.user.id === 'string') {
        const serverUser = /** @type {AuthData} */ (responseData.user);
        svelteUpdate(current => {
          const currentAuthData = (current && typeof current === 'object' && current.id) ? /** @type {AuthData} */ (current) : null;
          const updated = /** @type {AuthData} */ ({
            ...(currentAuthData || {}),
            ...serverUser,
            lastSynced: now,
            version: LOCAL_AUTH_VERSION
          });
          persistToLocalStorage(updated, true);
          return updated;
        });
        return true;
      } else {
        if (currentUser) clear();
        return false;
      }
    } catch (error) {
      console.error('Error syncing with server:', error);
      return false;
    }
  }
  
  /** @returns {AuthData | null} */
  function get() {
    let cv = null; subscribe(v => cv = v)(); return cv;
  }
  
  function clear() {
    svelteSet(null);
    persistToLocalStorage(null, true); 
  }
  
  /**
   * @param {Object} credentials - Login credentials 
   * @returns {Promise<{success: boolean, user?: AuthData, error?: string, warning?: string}>} Login result
   */
  async function login(credentials) {
    try {
      const response = await fetch('/api/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(credentials), credentials: 'include' });
      if (!response.ok) { 
        const errorData = await response.json();
        throw new Error(errorData.message || 'Login failed'); 
      }
      const result = await response.json();
      if (result.success && typeof result.user === 'object' && result.user !== null && typeof result.user.id === 'string') {
        const validUser = /** @type {AuthData} */ (result.user);
        svelteUpdate(() => { 
          const userData = /** @type {AuthData} */ ({ 
            ...validUser, 
            lastSynced: Date.now(), 
            version: LOCAL_AUTH_VERSION 
          });
          persistToLocalStorage(userData, true);
          return userData;
        });
        return { success: true, user: validUser };
      } else { 
        throw new Error('Login failed or user data missing'); 
      }
    } catch (error) { 
      console.error('Login error:', error);
      return { success: false, error: error instanceof Error ? error.message : 'Login failed' };
    }
  }
  
  /** @returns {Promise<{success: boolean, warning?: string}>} */
  async function logout() {
    try {
      await fetch('/api/auth/session', { method: 'DELETE', credentials: 'include' });
    } catch (error) {
      console.warn('[Auth Store] Server logout request failed, proceeding with local clear:', error);
    }
    clear();
    return { success: true };
  }
  
  return {
    subscribe,
    /** @param {AuthData | null} newValue */
    set: (newValue) => {
      /** @type {AuthData | null} */
      let currentValue = null;
      subscribe(v => { currentValue = v; });

      /** @type {AuthData | null} */
      let valueToSet = null;

      if (newValue === null) {
        console.log('[Auth Store SET] Explicitly clearing store (newValue is null).');
        valueToSet = null;
      } else if (typeof newValue === 'object' && newValue !== null && typeof newValue.id === 'string' && newValue.id) {
        const newAuthData = /** @type {AuthData} */ (newValue);
        valueToSet = newAuthData;

        // Check if currentValue is a valid object and has an 'id' property that is a string
        if (currentValue && 
            typeof currentValue === 'object' && 
            currentValue !== null && 
            Object.prototype.hasOwnProperty.call(currentValue, 'id') &&
            typeof (/** @type {any} */ (currentValue)).id === 'string' && 
            (/** @type {any} */ (currentValue)).id !== '') {
          
          // Now we are more certain currentValue is AuthData-like and has a valid string id.
          const currentAuthData = /** @type {AuthData} */ (currentValue);
          const currentId = currentAuthData.id; // Access id after casting currentValue to AuthData

          if (currentId === newAuthData.id) {
            console.log('[Auth Store SET] Merging. New:', JSON.stringify(newAuthData), 'Current:', JSON.stringify(currentAuthData));
            
            const mergedUser = /** @type {AuthData} */ ({
              ...currentAuthData, 
              ...newAuthData     
            });

            const newAvatarIsMissing = !Object.prototype.hasOwnProperty.call(newAuthData, 'avatarUrl') || newAuthData.avatarUrl === undefined;
            const currentAvatarExistsAndIsValid = Object.prototype.hasOwnProperty.call(currentAuthData, 'avatarUrl') && currentAuthData.avatarUrl !== undefined;
            if (newAvatarIsMissing && currentAvatarExistsAndIsValid) {
              mergedUser.avatarUrl = currentAuthData.avatarUrl;
              console.log(`[Auth Store SET] Preserved avatarUrl: ${currentAuthData.avatarUrl}`);
            }

            const newPlanIsMissing = !Object.prototype.hasOwnProperty.call(newAuthData, 'plan') || newAuthData.plan === undefined;
            const currentPlanExistsAndIsValid = Object.prototype.hasOwnProperty.call(currentAuthData, 'plan') && currentAuthData.plan !== undefined;
            if (newPlanIsMissing && currentPlanExistsAndIsValid) {
              mergedUser.plan = currentAuthData.plan;
              console.log(`[Auth Store SET] Preserved plan: ${JSON.stringify(currentAuthData.plan)}`);
            }
            valueToSet = mergedUser;
          } else {
            console.log('[Auth Store SET] Setting new user (currentValue.id invalid or mismatch). New:', JSON.stringify(newAuthData));
          }
        } else {
          console.log('[Auth Store SET] Setting new/initial user (currentValue not suitable for merge). New:', JSON.stringify(newAuthData));
        }
      } else {
        console.warn('[Auth Store SET] Invalid newValue (not null, not user object). Clearing store for safety. NewValue:', JSON.stringify(newValue));
        valueToSet = null; 
      }
      
      svelteSet(valueToSet);
      persistToLocalStorage(valueToSet, true);
    },
    /** @param {(current: AuthData | null) => AuthData | null} updater */
    update: (updater) => {
      svelteUpdate(current => {
        const updatedByUserFn = updater(current);
        
        const isValidUpdate = updatedByUserFn === null ||
                              (typeof updatedByUserFn === 'object' && 
                               updatedByUserFn !== null &&
                               Object.prototype.hasOwnProperty.call(updatedByUserFn, 'id') && 
                               typeof updatedByUserFn.id === 'string' &&
                               updatedByUserFn.id); // Check id is truthy string

        if (isValidUpdate) {
            console.log('[Auth Store UPDATE] Updating. Current:', JSON.stringify(current), 'Next:', JSON.stringify(updatedByUserFn));
            persistToLocalStorage(updatedByUserFn, false); 
            return updatedByUserFn;
        } else {
            console.warn('[Auth Store UPDATE] Updater function returned invalid data. Store not changed. Data:', JSON.stringify(updatedByUserFn));
            return current;
        }
      });
    },
    syncWithServer,
    login,
    logout,
    clear,
    get
  };
}

/**
 * Create derived user store for easy access to the current user
 * @param {Object} authStore - The auth store
 * @returns {Object} User store
 */
function createUserStore(authStore) {
  return {
    subscribe: derived(authStore, $auth => $auth).subscribe
  };
}

// Create and export stores
export const authStore = createAuthStore();
export const user = createUserStore(authStore);

/**
 * Check if user is authenticated
 * @returns {Promise<boolean>} Authentication status
 */
export async function isAuthenticated() {
  const currentUser = authStore.get();
  
  // If we have local auth data, consider authenticated
  if (currentUser) {
    // Try to sync with server when online
    if (browser && navigator.onLine) {
      try {
        const synced = await authStore.syncWithServer(true);
        return synced;
      } catch (error) {
        console.error('Auth sync error:', error);
        // Use local data as fallback (Local First)
        return !!currentUser;
      }
    }
    
    // Offline mode - trust local data
    return true;
  }
  
  return false;
}

/**
 * Refresh auth state from server
 * @returns {Promise<boolean>} Whether refresh was successful
 */
export async function refreshAuthState() {
  if (!browser) return false;
  
  try {
    console.log(`[Auth Refresh] Attempting to refresh auth state from server`);
    const response = await fetch('/api/auth/session', {
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      },
      credentials: 'include' // This is critical for sending cookies
    });
    
    if (!response.ok) {
      console.log(`[Auth Refresh] Server returned error:`, response.status);
      return false;
    }
    
    const data = await response.json();
    
    if (data.authenticated && data.user) {
      console.log(`[Auth Refresh] Valid session found on server:`, data.user.id);
      
      // Process user data
      const userData = {
        ...data.user,
        lastSynced: Date.now(),
        version: LOCAL_AUTH_VERSION
      };
      
      // Update local store with user data from server
      authStore.set(userData);
      
      // Explicitly sync to userRootStore
      syncUserStores(userData, true);
      
      // Also save to last auth cache
      try {
        localStorage.setItem('asap_digest_last_auth', JSON.stringify(userData));
        console.log('[Auth Refresh] Saved user data to last_auth cache');
      } catch (storageError) {
        console.warn('[Auth Refresh] Error saving to last_auth cache:', storageError);
      }
      
      return true;
    }
    
    console.log(`[Auth Refresh] No valid session found on server`);
    // No valid session - clear local auth data
    authStore.clear();
    
    // Ensure user stores are in sync
    syncUserStores(null);
    
    return false;
  } catch (error) {
    console.error(`[Auth Refresh] Error refreshing auth state:`, error);
    return false;
  }
}

/**
 * Check if user is authenticated and redirect if not
 * @param {string} [redirectTo='/login'] - Where to redirect unauthenticated users
 * @returns {Promise<boolean>} Authentication status
 */
export async function requireAuth(redirectTo = '/login') {
  const authenticated = await isAuthenticated();
  
  if (!authenticated && browser) {
    goto(redirectTo);
  }
  
  return authenticated;
}

/**
 * Normalize a user object from any source to ensure consistent properties
 * @param {Object} inputUser - Raw user object
 * @returns {Object} Normalized user object
 */
function normalizeUserData(inputUser) {
  if (!inputUser) return null;
  
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
  
  // Add timestamp for session tracking
  const now = Date.now();
  
  // Construct normalized user
  return {
    id: props.id || inputUser.ID || inputUser.Id || '',
    email: props.email || inputUser.EMAIL || inputUser.Email || '',
    displayName: props.displayname || inputUser.DISPLAYNAME || inputUser.DisplayName || props.name || '',
    avatarUrl: props.avatarurl || inputUser.AVATARURL || inputUser.AvatarUrl || '',
    roles: combinedRoles,
    metadata: props.metadata || {},
    plan: props.plan || 'Free',
    updatedAt: props.updatedat || inputUser.UPDATEDAT || new Date().toISOString(),
    lastSynced: now
  };
}

/**
 * Middleware function to handle auth for protected routes in load functions
 * @param {Object} event - SvelteKit load event
 * @returns {Promise<Object>} Route data or redirect
 */
export async function authMiddleware(event) {
  // Create path string for logging
  const path = event?.url?.pathname || 'unknown';

  // On server, just return a placeholder structure
  // The client will handle proper authentication after hydration
  if (!browser) {
    console.log(`[Auth Middleware] Server-side call for ${path} - deferring to client`);
    return {
      user: null,
      usingLocalAuth: false
    };
  }
  
  console.log(`[Auth Middleware] Client-side call for ${path}`);
  
  // On client, first check local auth store AND localStorage directly
  const currentUser = authStore.get();
  
  if (currentUser) {
    console.log(`[Auth Middleware] User found in store for ${path}:`, currentUser.id);
    
    // Make sure user stores are in sync
    syncUserStores(currentUser);
    
    // Try to sync with server when online to keep session fresh
    if (navigator.onLine) {
      try {
        const synced = await refreshAuthState();
        if (!synced) {
          console.log(`[Auth Middleware] Session expired or invalid during refresh`);
          // Don't redirect right away, give the next localStorage check a chance
        }
      } catch (error) {
        console.error(`[Auth Middleware] Error refreshing auth state:`, error);
      }
    }
    
    // Use the (potentially refreshed) user data
    const updatedUser = authStore.get();
    if (updatedUser) {
      // Ensure user stores are in sync after refresh
      syncUserStores(updatedUser);
      
      return {
        user: updatedUser,
        usingLocalAuth: !navigator.onLine
      };
    }
  }
  
  // No user in store, check localStorage directly (extra safety for SPA navigation)
  try {
    const LOCAL_AUTH_KEY = 'asap_digest_auth';
    const storedData = localStorage.getItem(LOCAL_AUTH_KEY);
    if (storedData) {
      try {
        const parsedData = JSON.parse(storedData);
        if (parsedData && parsedData.id) {
          console.log(`[Auth Middleware] Found user in localStorage but not in store:`, parsedData.id);
          // Update the store with the localStorage data
          authStore.set(parsedData);
          
          // Ensure user stores are in sync
          syncUserStores(parsedData);
          
          return {
            user: parsedData,
            usingLocalAuth: true,
            recoveredFromStorage: true
          };
        }
      } catch (e) {
        console.error(`[Auth Middleware] Error parsing localStorage data:`, e);
      }
    }
  } catch (error) {
    console.error(`[Auth Middleware] Error accessing localStorage:`, error);
  }
  
  console.log(`[Auth Middleware] No user found in local store`);
  
  // No user in store or localStorage, now try to get session from server
  try {
    console.log(`[Auth Middleware] Attempting server session check`);
    
    // Use the fetch from the event if available (recommended by SvelteKit)
    const fetch_fn = event?.fetch || fetch;
    
    const response = await fetch_fn('/api/auth/session', {
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      },
      credentials: 'include'  // Critical for sending cookies with the request
    });
    
    if (!response.ok) {
      console.log(`[Auth Middleware] Server returned error for session check:`, response.status);
      throw new Error('Failed to fetch session');
    }
    
    const data = await response.json();
    
    if (data.authenticated && data.user) {
      console.log(`[Auth Middleware] Valid session found on server:`, data.user.id);
      
      // Process user data
      const userData = {
        ...data.user,
        lastSynced: Date.now(),
        version: LOCAL_AUTH_VERSION
      };
      
      // Update local store with user data from server
      authStore.set(userData);
      
      // Explicitly sync to userRootStore
      syncUserStores(userData, true);
      
      // Also save to last auth
      try {
        localStorage.setItem('asap_digest_last_auth', JSON.stringify(userData));
        console.log('[Auth Middleware] Saved user data to last_auth cache');
      } catch (storageError) {
        console.warn('[Auth Middleware] Error saving to last_auth cache:', storageError);
      }
      
      return {
        user: userData,
        usingLocalAuth: false
      };
    }
    
    console.log(`[Auth Middleware] No valid session found on server`);
    // No valid session - need to redirect to login
  } catch (error) {
    console.error(`[Auth Middleware] Error checking server session:`, error);
    // Server error - if we're online, we can't authenticate; if offline, we can't check
    if (navigator.onLine) {
      console.log(`[Auth Middleware] Online but session check failed`);
    } else {
      console.log(`[Auth Middleware] Offline, can't check server session`);
      // When offline, we might want a different behavior
      // For now, still redirect to login where offline status can be shown
    }
  }
  
  // If we got here, authentication failed
  console.log(`[Auth Middleware] Authentication failed - redirect to login required`);
  return {
    user: null,
    usingLocalAuth: false,
    redirectTo: '/login'
  };
} 