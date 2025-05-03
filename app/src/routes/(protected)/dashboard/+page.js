import { browser } from '$app/environment';
import { redirect } from '@sveltejs/kit';
import { goto } from '$app/navigation';
import { log } from '$lib/utils/log';

// Export config flags as separate properties
export const ssr = false;
export const csr = true;

/**
 * @typedef {Object} User
 * @property {string} id - User ID
 * @property {string} email - User email
 * @property {string} [displayName] - User display name
 * @property {string} [avatarUrl] - User avatar URL
 * @property {string[]} [roles] - User roles
 * @property {string} [plan] - User subscription plan
 * @property {string} [updatedAt] - Last update timestamp
 * @property {boolean} [_noRefresh] - Internal flag to prevent refreshes
 */

/**
 * Load function for the dashboard page with enhanced session validation
 * @param {{fetch: Function, parent: Function, depends: Function}} params - Load function parameters
 * @returns {Promise<{streamed: {dashboardData: Promise<Record<string, any>|null>}, user: User|null, preventRefresh: boolean}>}
 */
export const load = async ({ fetch, parent, depends }) => {
    // Add dependency on user data
    depends('app:user');
    
    // Always prevent multiple refresh cycles
    const preventRefresh = true;
    
    // Get parent data which includes auth state
    const layoutData = await parent();
    
    // Extract user and preventRefresh flag from layout data with type safety
    /** @type {User|null} */
    const user = layoutData?.user || null;
    
    // Check if parent already has preventRefresh flag set
    const parentPreventRefresh = layoutData?.preventRefresh === true;
    
    // Check for _noRefresh flag in user data
    const userNoRefresh = user?._noRefresh === true;
    
    // Combine all prevention flags
    const skipAuthCheck = preventRefresh && (parentPreventRefresh || userNoRefresh);

    log(`[Dashboard] Page load - user: ${user ? user.email : 'null'}, skipAuthCheck: ${skipAuthCheck}`, 'info');

    if (!browser) {
        // For server-side rendering, just return placeholder
        return {
            ...layoutData,
            preventRefresh,
            streamed: {
                dashboardData: Promise.resolve(null)
            }
        };
    }

    // Client-side execution
    log('[Dashboard] Client-side load executing', 'info');
    
    // If user data is already loaded, return dashboard data
    if (user && user.id) {
        log('[Dashboard] Using existing user data:', 'info');
        return {
            ...layoutData,
            preventRefresh,
            streamed: {
                dashboardData: fetchDashboardData(fetch, user)
            }
        };
    }
    
    // Skip authentication check if we have flags telling us to avoid refresh
    if (skipAuthCheck) {
        log('[Dashboard] Skipping auth check due to preventRefresh flags', 'info');
        return {
            ...layoutData,
            preventRefresh, 
            streamed: {
                dashboardData: Promise.resolve(null)
            }
        };
    }
    
    // User data is not yet loaded, trigger a session check
    log('[Dashboard] User data not available. Verifying session...', 'info');
    
    try {
        // Try to verify session from server (single attempt only)
        const response = await fetch('/api/auth/session-check', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache, no-store',
                'X-No-Refresh': 'true'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.authenticated && data.user) {
                log('[Dashboard] Session check successful, received user data', 'info');
                
                // Convert user data to our defined User type
                /** @type {User} */
                const typedUser = {
                    id: data.user.id || '',
                    email: data.user.email || '',
                    displayName: data.user.displayName || data.user.email?.split('@')[0] || '',
                    avatarUrl: data.user.avatarUrl || null,
                    roles: Array.isArray(data.user.roles) ? data.user.roles : ['user'],
                    plan: data.user.plan || 'Free',
                    updatedAt: data.user.updatedAt || new Date().toISOString(),
                    _noRefresh: true // Add noRefresh flag
                };
                
                // Return data with user information but don't invalidate dependencies
                return {
                    ...layoutData,
                    user: typedUser,
                    preventRefresh,
                    streamed: {
                        dashboardData: fetchDashboardData(fetch, typedUser)
                    }
                };
            }
        }
        
        // If we reach this point, session check failed or user is not authenticated
        log('[Dashboard] Session verification failed, redirecting to login', 'warn');
        
        // Use SvelteKit goto instead of redirect for client navigation
        if (browser) {
            goto('/login', { replaceState: true });
            return {
                ...layoutData,
                preventRefresh,
                streamed: {
                    dashboardData: Promise.resolve(null)
                }
            };
        }
        
        // Fallback to redirect if needed
        throw redirect(307, '/login');
        
    } catch (error) {
        // If error is not a redirect, log and continue with redirect
        if (!(error instanceof Response)) {
            // Type guard for error message access
            const errorMessage = error instanceof Error ? error.message : String(error);
            log(`[Dashboard] Error during session check: ${errorMessage}`, 'error');
            
            if (browser) {
                goto('/login', { replaceState: true });
                return {
                    ...layoutData,
                    preventRefresh,
                    streamed: {
                        dashboardData: Promise.resolve(null)
                    }
                };
            }
        }
        throw error; // Re-throw if it's a redirect
    }
};

/**
 * Fetch dashboard data from API
 * @param {Function} fetch - Fetch function
 * @param {User} user - User data
 * @returns {Promise<Record<string, any>|null>} Dashboard data
 */
async function fetchDashboardData(fetch, user) {
    if (!user || !user.id) {
        log('[Dashboard] Cannot fetch dashboard data - missing user ID', 'error');
        return null;
    }
    
    try {
        const response = await fetch('/api/dashboard', {
            headers: {
                'Authorization': `Bearer ${user.id}`,
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch dashboard data: ${response.status}`);
        }

        return response.json();
    } catch (error) {
        // Type guard for error message access
        const errorMessage = error instanceof Error ? error.message : String(error);
        log(`[Dashboard] Error fetching dashboard data: ${errorMessage}`, 'error');
        return null;
    }
} 