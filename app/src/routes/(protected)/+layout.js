/**
 * Protected routes layout
 * Ensures authentication for all protected routes with local-first capabilities
 * 
 * @created 07.06.25 | 03:04 PM PDT
 * @fileoverview Layout load function for protected routes with Local First integration
 */

import { authMiddleware, refreshAuthState, isAuthenticated, authStore } from '$lib/utils/auth-persistence';
import { error, redirect } from '@sveltejs/kit';
import { browser } from '$app/environment';

/**
 * Layout load function for protected routes
 * 
 * @type {import('./$types').LayoutLoad}
 */
export async function load(event) {
  // Add dependency for auth state changes
  event.depends('auth:state');
  
  try {
    console.log(`[Protected Layout] Starting auth check for: ${event.url.pathname}`);
    
    let userToUse = event.data?.user;

    if (browser && userToUse && (userToUse.avatarUrl === undefined || userToUse.plan === undefined)) {
      console.warn(`[Protected Layout] event.data.user seems incomplete. Trying authStore.get(). Event.data.user:`, JSON.stringify(userToUse));
      const storeUser = authStore.get();
      if (storeUser && storeUser.id === userToUse.id) {
        console.log(`[Protected Layout] Found user in authStore.get() with more complete data:`, JSON.stringify(storeUser));
        // Prefer storeUser if it seems more complete for essential fields
        if (storeUser.avatarUrl !== undefined && storeUser.plan !== undefined) {
          userToUse = storeUser;
        }
      }
    }
    
    if (userToUse) {
      console.log(`[Protected Layout] Using user data (final decision): ${userToUse.email}, Avatar: ${userToUse.avatarUrl}, Plan: ${JSON.stringify(userToUse.plan)}`);
      if (browser) {
        authStore.set(userToUse);
        console.log(`[Protected Layout] Auth store updated with user data.`);
      }
      return {
        user: userToUse,
        usingLocalAuth: false, // Assuming if it came via event.data or authStore, it's not purely local anymore
        sourceData: 'root-layout-or-authStore'
      };
    }
    
    // Handle server-side rendering case
    if (!browser) {
      // On server, check if we have a user in locals or data (from hooks.server.js)
      const serverUser = event.data?.user;
      if (serverUser) {
        console.log(`[Protected Layout Server] User authenticated: ${serverUser.email}`);
        // We have a server-authenticated user, so return it
        return {
          user: serverUser,
          usingLocalAuth: false
        };
      } else {
        console.log(`[Protected Layout Server] No user found in data`);
        // Allow client to handle authentication
        return {
          user: null,
          usingLocalAuth: false
        };
      }
    }
    
    // SPA APPROACH: First check for direct auth from localStorage 
    // This helps maintain auth state during client-side navigation
    const directStoreCheck = authStore.get();
    console.log(`[Protected Layout] Direct store check: ${Boolean(directStoreCheck)}`);
    
    // If we have auth in the store, use it immediately
    if (directStoreCheck) {
      console.log(`[Protected Layout] Using direct store authentication for ${event.url.pathname}`);
      
      // Even if we have local store data, still try a background refresh
      // but don't wait for it to finish or block navigation
      if (navigator.onLine) {
        refreshAuthState().catch(err => 
          console.warn('[Protected Layout] Background auth refresh failed:', err)
        );
      }
      
      return {
        user: directStoreCheck,
        usingLocalAuth: true
      };
    }
    
    // NEW: Check the page store for user data (might be there from previous navigation)
    // This helps with SPA navigation between protected routes
    try {
      // Try to use localStorage as a bridge for authentication data
      // This is more reliable than trying to access internal SvelteKit stores
      if (browser && localStorage) {
        // Try to get auth data from localStorage
        const LAST_AUTH_KEY = 'asap_digest_last_auth';
        const lastAuthData = localStorage.getItem(LAST_AUTH_KEY);
        
        if (lastAuthData) {
          try {
            const parsedAuth = JSON.parse(lastAuthData);
            if (parsedAuth && parsedAuth.id) {
              console.log(`[Protected Layout] Found user in last auth cache: ${parsedAuth.email || parsedAuth.id}`);
              // Update auth store with the cached data
              authStore.set(parsedAuth);
              return {
                user: parsedAuth,
                usingLocalAuth: false,
                sourceData: 'last-auth-cache'
              };
            }
          } catch (parseErr) {
            console.warn('[Protected Layout] Error parsing last auth data:', parseErr);
          }
        }
      }
    } catch (err) {
      console.warn('[Protected Layout] Error accessing last auth cache:', err);
    }
    
    // IMPORTANT: Use the fetch from the event
    // This ensures cookies are properly handled
    const customAuthMiddleware = async () => {
      return await authMiddleware({
        ...event,
        fetch: event.fetch // Explicitly pass the fetch from the event
      });
    };
    
    // Fallback to standard auth middleware if no direct store auth
    const authData = await customAuthMiddleware();
    
    // Check the result of auth middleware
    console.log(`[Protected Layout] Auth middleware result: ${authData?.user ? 'Has user' : 'No user'} ${authData?.redirectTo ? 'Has redirect' : 'No redirect'}`);
    
    // If middleware wants to redirect, we need to throw a redirect
    if (authData.redirectTo) {
      console.log(`[Protected Layout] Auth middleware requested redirect to: ${authData.redirectTo}`);
      
      // SPA Enhancement: Before redirecting, try one more check for localStorage data
      // This can recover from situations where the store lost data during navigation
      if (browser) {
        try {
          const LOCAL_AUTH_KEY = 'asap_digest_auth';
          const storedData = localStorage.getItem(LOCAL_AUTH_KEY);
          if (storedData) {
            const parsedData = JSON.parse(storedData);
            if (parsedData && parsedData.id) {
              console.log('[Protected Layout] Found valid auth in localStorage, bypassing redirect');
              return {
                user: parsedData,
                usingLocalAuth: true,
                recoveredAuth: true
              };
            }
          }
        } catch (error) {
          console.error('[Protected Layout] Error checking localStorage:', error);
        }
      }
      
      // If no recovery possible, proceed with redirect
      throw redirect(302, authData.redirectTo);
    }
    
    // Return the user data from auth middleware
    return {
      user: authData.user,
      usingLocalAuth: authData.usingLocalAuth || false
    };
  } catch (err) {
    console.error('Authentication error in protected route:', err);
    
    // For safety, don't expose error details in production
    if (err instanceof redirect) {
      throw err;
    }
    
    // Instead of throwing a generic 500 error, redirect to login
    // This prevents the 500 error page and gives a better user experience
    console.log('[Protected Layout] Encountered error, redirecting to login');
    throw redirect(302, '/login');
  }
} 