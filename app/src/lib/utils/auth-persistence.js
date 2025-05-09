/**
 * Auth Persistence Utility
 * Implements Local First principles for auth state management
 * 
 * @created 07.06.25 | 02:53 PM PDT
 */

import { browser } from '$app/environment';
import { writable, derived } from 'svelte/store';
import { goto } from '$app/navigation';

// Constants
const LOCAL_AUTH_KEY = 'asap_digest_auth';
const LOCAL_AUTH_VERSION = 1;
const SESSION_DURATION = 7 * 24 * 60 * 60 * 1000; // 7 days in ms
const SYNC_INTERVAL = 5 * 60 * 1000; // 5 minutes in ms

/**
 * @typedef {Object} AuthData
 * @property {string} id - User ID
 * @property {string} email - User email
 * @property {string} [displayName] - User display name
 * @property {Object} [profile] - User profile data
 * @property {Object} [security] - User security settings
 * @property {Object} [privacy] - User privacy settings
 * @property {Object} [notifications] - User notification preferences
 * @property {Object} [subscription] - User subscription details
 * @property {number} lastSynced - Timestamp of last server sync
 * @property {number} version - Schema version
 */

/**
 * Creates a persistent auth store with local-first capabilities
 * @returns {Object} Auth store and utility methods
 */
function createAuthStore() {
  // Initialize from localStorage when in browser
  let initialValue = null;
  
  if (browser) {
    try {
      const storedData = localStorage.getItem(LOCAL_AUTH_KEY);
      if (storedData) {
        const parsedData = JSON.parse(storedData);
        // Check version compatibility
        if (parsedData && parsedData.version === LOCAL_AUTH_VERSION) {
          initialValue = parsedData;
        }
      }
    } catch (error) {
      console.error('Error loading auth data from localStorage:', error);
    }
  }
  
  // Create the main store
  const { subscribe, set, update } = writable(initialValue);
  
  // Set up sync interval when in browser
  if (browser) {
    const syncInterval = setInterval(() => {
      syncWithServer(false);
    }, SYNC_INTERVAL);
    
    // Clean up interval on page unload
    window.addEventListener('beforeunload', () => {
      clearInterval(syncInterval);
    });
  }
  
  /**
   * Persist auth data to localStorage
   * @param {AuthData} data - Auth data to persist
   */
  function persistToLocalStorage(data) {
    if (browser && data) {
      try {
        localStorage.setItem(LOCAL_AUTH_KEY, JSON.stringify({
          ...data,
          lastSynced: Date.now(),
          version: LOCAL_AUTH_VERSION
        }));
      } catch (error) {
        console.error('Error saving auth data to localStorage:', error);
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
      
      // Skip sync if we've synced recently (unless force=true)
      if (!force && currentUser?.lastSynced && (now - currentUser.lastSynced < SYNC_INTERVAL)) {
        return true; // Recently synced, no need to sync again
      }
      
      // Fetch current session from server
      const response = await fetch('/api/auth/session', {
        headers: {
          'Cache-Control': 'no-cache',
          'Pragma': 'no-cache'
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch session');
      }
      
      const data = await response.json();
      
      if (data.authenticated && data.user) {
        // Server says we're authenticated
        update(current => {
          const updated = {
            ...current,
            ...data.user,
            lastSynced: now,
            version: LOCAL_AUTH_VERSION
          };
          persistToLocalStorage(updated);
          return updated;
        });
        return true;
      } else {
        // Server says we're not authenticated
        if (currentUser) {
          // We thought we were authenticated but server says no - clear local state
          clear();
        }
        return false;
      }
    } catch (error) {
      console.error('Error syncing with server:', error);
      // If offline, we keep the local data
      return false;
    }
  }
  
  /**
   * Get the current auth data
   * @returns {AuthData|null} Current auth data
   */
  function get() {
    let currentValue = null;
    subscribe(value => {
      currentValue = value;
    })();
    return currentValue;
  }
  
  /**
   * Clear auth data from store and localStorage
   */
  function clear() {
    set(null);
    if (browser) {
      try {
        localStorage.removeItem(LOCAL_AUTH_KEY);
      } catch (error) {
        console.error('Error clearing auth data from localStorage:', error);
      }
    }
  }
  
  /**
   * Login the user
   * @param {Object} credentials - Login credentials 
   * @returns {Promise<Object>} Login result
   */
  async function login(credentials) {
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(credentials)
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Login failed');
      }
      
      const result = await response.json();
      
      if (result.success && result.user) {
        // Update store with user data
        update(() => {
          const userData = {
            ...result.user,
            lastSynced: Date.now(),
            version: LOCAL_AUTH_VERSION
          };
          persistToLocalStorage(userData);
          return userData;
        });
        
        return { success: true, user: result.user };
      } else {
        throw new Error('Login failed');
      }
    } catch (error) {
      console.error('Login error:', error);
      return { 
        success: false, 
        error: error instanceof Error ? error.message : 'Login failed' 
      };
    }
  }
  
  /**
   * Logout the user
   * @returns {Promise<Object>} Logout result
   */
  async function logout() {
    try {
      // Attempt to logout on server
      const response = await fetch('/api/auth/session', {
        method: 'DELETE'
      });
      
      // Clear local state regardless of server response
      clear();
      
      if (response.ok) {
        return { success: true };
      } else {
        return { success: true, warning: 'Server logout failed, but local state cleared' };
      }
    } catch (error) {
      // Even if server logout fails, clear local state
      clear();
      return { success: true, warning: 'Offline logout' };
    }
  }
  
  return {
    subscribe,
    set: (value) => {
      set(value);
      persistToLocalStorage(value);
    },
    update: (updater) => {
      update(current => {
        const updated = updater(current);
        persistToLocalStorage(updated);
        return updated;
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
 * Force refresh of auth state (for use in layouts, +page.js files)
 * @returns {Promise<AuthData|null>} Updated auth data
 */
export async function refreshAuthState() {
  await authStore.syncWithServer(true);
  return authStore.get();
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
 * Middleware function to handle auth for protected routes in load functions
 * @param {Object} event - SvelteKit load event
 * @returns {Promise<Object>} Route data or redirect
 */
export async function authMiddleware(event) {
  if (browser) {
    const authenticated = await isAuthenticated();
    
    if (!authenticated) {
      return goto('/login');
    }
    
    // Refresh the auth state
    await refreshAuthState();
    
    // Return the current user data for the page
    return {
      user: authStore.get(),
      usingLocalAuth: !navigator.onLine
    };
  }
  
  // Server-side: rely on the server's session check
  return {
    user: null
  };
} 