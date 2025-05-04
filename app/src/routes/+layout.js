import { browser } from '$app/environment';
import { invalidateAll } from '$app/navigation';
import { log } from '$lib/utils/log.js';

/**
 * Client-side load function that enhances the session handling
 * @param {Object} event The load event
 * @param {Object} event.data The data from the parent layout
 * @param {App.User|null} [event.data.user] The user data
 * @param {function(RequestInfo, RequestInit=): Promise<Response>} event.fetch Fetch function
 * @param {function(string): void} event.depends Dependencies function
 * @returns {Promise<Object>} Enhanced data object
 */
export async function load({ data, fetch, depends }) {
    // Register dependencies that will trigger this load function when invalidated
    depends('app:user');
    depends('app:session');

    // Prevent this from running during SSR
    if (!browser) {
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
                    
                    // Check if response contains noRefresh flag
                    const preventRefresh = wpData.noRefresh === true;
                    
                    // Calculate a short delay based on whether we're refreshing
                    // This helps ensure the UI updates properly
                    const updateDelay = preventRefresh ? 0 : 500;
                    
                    // Short wait before invalidation to ensure cookie is processed
                    if (updateDelay > 0) {
                        await new Promise(resolve => setTimeout(resolve, updateDelay));
                    }
                    
                    if (!preventRefresh) {
                        // Only trigger invalidation if not preventing refresh
                        invalidateAll();
                    }
                    
                    return {
                        ...data,
                        user: wpData.user,
                        preventRefresh: true // Always set preventRefresh flag in return data
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