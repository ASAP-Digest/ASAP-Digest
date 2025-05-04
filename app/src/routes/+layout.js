import { browser } from '$app/environment';
import { invalidateAll } from '$app/navigation';
import { log } from '$lib/utils/log.js';

/**
 * Enhanced data object type including preventRefresh flag
 * @typedef {Object} LoadData
 * @property {App.User|null} [user] User data if available
 * @property {boolean} [preventRefresh] Flag to prevent refresh loops
 */

/**
 * Client-side load function that enhances the session handling
 * @param {Object} params The load parameters
 * @param {LoadData} params.data The data from the parent layout
 * @param {function(RequestInfo, RequestInit=): Promise<Response>} params.fetch Fetch function
 * @param {function(string): void} params.depends Dependencies registration function
 * @returns {Promise<LoadData>} Enhanced data object with user and preventRefresh flag
 */
export async function load({ data, fetch, depends }) {
    // Register dependencies that will trigger this load function when invalidated
    depends('app:user');
    depends('app:session');

    // Prevent this from running during SSR
    if (!browser) {
        return data;
    }

    // IMPORTANT: If the data already has preventRefresh flag, respect it to stop the loop
    if (data && data.preventRefresh === true) {
        return data;
    }

    // Check if we already have user data from the server
    if (data && 'user' in data && data.user) {
        log('[+layout.js] User data available from server load', 'info');
        return {
            ...data,
            preventRefresh: true // Add preventRefresh flag
        };
    }

    // Check if we recently attempted auto-login to prevent constant retries
    try {
        const autoLoginAttemptTimestamp = sessionStorage.getItem('auto_login_attempt_timestamp');
        const now = Date.now();
        
        if (autoLoginAttemptTimestamp) {
            const timestamp = parseInt(autoLoginAttemptTimestamp, 10);
            // If we've attempted auto-login in the last 5 seconds, don't try again
            if (!isNaN(timestamp) && (now - timestamp) < 5000) {
                log('[+layout.js] Recent auto-login attempt detected, skipping check', 'info');
                return {
                    ...data,
                    preventRefresh: true
                };
            }
        }
        
        // Update the timestamp for this attempt
        sessionStorage.setItem('auto_login_attempt_timestamp', now.toString());
    } catch (e) {
        // Ignore sessionStorage errors
    }

    try {
        // If no user in data but we're in browser, check session state
        log('[+layout.js] No user data from server, checking session status...', 'info');
        
        // Use a lightweight session-check endpoint instead of redirecting
        const response = await fetch('/api/auth/session-check', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            }
        });

        if (response.ok) {
            const sessionData = await response.json();
            
            if (sessionData.authenticated && sessionData.user) {
                log('[+layout.js] Session check success, user authenticated', 'info');
                
                // Return enhanced data with user and preventRefresh flag
                return {
                    ...data,
                    user: sessionData.user,
                    preventRefresh: true // Add preventRefresh flag
                };
            }
        }
        
        // No session found, try server-to-server WordPress check
        log('[+layout.js] No session found, checking WordPress session...', 'info');
        
        try {
            const wpResponse = await fetch('/api/auth/check-wp-session', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store'
                },
                body: JSON.stringify({
                    timestamp: Date.now()
                })
            });
            
            if (wpResponse.ok) {
                const wpData = await wpResponse.json();
                
                if (wpData.success && wpData.user) {
                    log('[+layout.js] WordPress auto-login successful!', 'info');
                    
                    // Always set preventRefresh to true to avoid the loop
                    const preventRefresh = true;
                    
                    // Return the data immediately with preventRefresh flag
                    return {
                        ...data,
                        user: wpData.user,
                        preventRefresh: true
                    };
                }
                
                log('[+layout.js] WordPress auto-login unsuccessful', 'info');
            }
        } catch (wpError) {
            const errorMessage = wpError instanceof Error ? wpError.message : String(wpError);
            log(`[+layout.js] Error checking WordPress session: ${errorMessage}`, 'error');
        }
        
        // Session check failed or no user - continue with original data
        log('[+layout.js] Session check completed - no authenticated user', 'debug');
        return {
            ...data,
            preventRefresh: true // Add preventRefresh flag
        };
    } catch (error) {
        // Type guard for error message access
        const errorMessage = error instanceof Error ? error.message : String(error);
        log(`[+layout.js] Error checking session: ${errorMessage}`, 'error');
        return {
            ...data,
            preventRefresh: true // Add preventRefresh flag
        };
    }
} 