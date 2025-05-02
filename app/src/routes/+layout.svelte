<script>
  /** 
   * @typedef {Object} Props
   * @property {import('svelte').Snippet} [children]
   * @property {Object} [data] - The data passed to the layout
   */

  /** @type {Props} */
  let { children, data } = $props();

  import "../app.css";
  import { onMount, onDestroy } from 'svelte';
  import Footer from "$lib/components/layout/Footer.svelte";
  import PerformanceMonitor from "$lib/components/ui/PerformanceMonitor.svelte";
  import { initPerformanceMonitoring } from "$lib/utils/performance";
  // import { handleLazyLoading, initImageOptimization } from '$lib/utils/imageOptimizer'; // TEMP: Comment out this import
  import { page } from '$app/stores';
  import InstallPrompt from '$lib/components/pwa/InstallPrompt.svelte';
  import MainSidebar from '$lib/components/layout/MainSidebar.svelte';
  // import { findProblematicClasses, fixClassString } from '$lib/utils/tailwindFixer';
  import { registerServiceWorker } from '$lib/utils/register-sw';
  import TestPwaControls from '$lib/components/pwa/TestPwaControls.svelte';
  import { browser } from '$app/environment';
  import { dev } from '$app/environment';
  import GlobalFAB from '$lib/components/layout/GlobalFAB.svelte';
  // Import the functions
  import { getInstallPrompt, getIsInstallable, getIsPWA } from '$lib/stores/pwa.svelte.js';
  // Import local toast components and store
  import Toaster from '$lib/components/ui/toast/toast-container.svelte';
  import { toasts } from '$lib/stores/toast.js'; // Correctly import the store
  // Import required icons
  import { 
    Menu, X, Search, Bell, CircleUser, LayoutDashboard, Settings, LogOut, Home, Download 
  } from '$lib/utils/lucide-compat.js';
  import { goto, invalidateAll } from '$app/navigation'; // Import invalidateAll from correct module
  // Import the new GraphQL helper
  import { fetchGraphQL } from '$lib/utils/fetchGraphQL.js';
  // Import session store/hook (assuming useSession exists or similar)
  // import { useSession } from '$lib/stores/session'; // Placeholder - Adjust if store name/structure differs

  // State management with Svelte 5 runes
  let isSidebarOpen = $state(false);
  let isSidebarCollapsed = $state(false);
  let isMobile = $state(false);
  // let isSidebarOpen = false; // TEMP: Use non-reactive fallback
  // let isSidebarCollapsed = false; // TEMP: Use non-reactive fallback
  // let isMobile = false; // TEMP: Use non-reactive fallback

  // Derived values using Svelte 5 runes
  let isAuthRoute = $derived($page.url.pathname.startsWith('/login') || $page.url.pathname.startsWith('/register'));
  let isDesignSystemRoute = $derived($page.url.pathname.startsWith('/design-system'));
  // Check if there is an active Better Auth session
  // Assuming $page.data.user existing indicates an active session for now
  let hasBetterAuthSession = $derived(!!$page.data.user); 
  // let isAuthRoute = false; // TEMP: Use non-reactive fallback
  // let isDesignSystemRoute = false; // TEMP: Use non-reactive fallback

  // Store previous user update timestamp
  let previousUserUpdatedAt = $state($page.data.user?.updatedAt);

  // Log user data from page store on mount AND whenever it changes
  $effect(() => {
    // This log runs both on the server (during SSR) and client
    console.log('[Layout $effect] $page.data.user:', JSON.stringify($page.data.user || null));
  });

  // Effect to show toast on user data update
  $effect(() => {
    const currentUser = $page.data.user;
    if (currentUser?.updatedAt && currentUser.updatedAt !== previousUserUpdatedAt) {
      console.log(`[Layout Toast Effect] User data updated. Old: ${previousUserUpdatedAt}, New: ${currentUser.updatedAt}. Showing toast.`); // DEBUG
      toasts.show(
        'Your profile details have been updated from WordPress.',
        'success'
        // Optionally pass duration if needed, defaults to 5000ms
      );
      previousUserUpdatedAt = currentUser.updatedAt;
    }
  });

  // Effects using Svelte 5 runes
  $effect(() => {
    if (isDesignSystemRoute && browser) {
      forceCSSVariableApplication();
    }
  });

  // Initialize on mount
  onMount(() => {
    // --- NEW: WP Session Check via GraphQL viewer query (Step C & D) ---
    if (browser && !hasBetterAuthSession) { // Only run if in browser AND no BA session exists
      console.log('[Layout V3 - Step C] No Better Auth session found. Checking WP session via GraphQL viewer query...'); // Added V3 prefix

      const viewerQuery = `query GetViewerDetails { viewer { databaseId email username name } }`;
      const wpGraphqlUrl = import.meta.env.VITE_PUBLIC_WP_GRAPHQL_URL || 'https://asapdigest.local/graphql';
      console.log(`[Layout V3 - Step D] Using GraphQL endpoint: ${wpGraphqlUrl}`); // Log the URL

      // Step D: Verify WP Session via GraphQL viewer query
      fetch(wpGraphqlUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: viewerQuery }),
        credentials: 'include' // ESSENTIAL: Sends WP cookies
        })
        .then(response => {
        if (!response.ok) {
          // Handle non-2xx HTTP responses (e.g., CORS errors, server errors)
          // Log the response text for more details
          response.text().then(text => {
            console.error(`[Layout V3 - Step D] GraphQL query fetch failed. Status: ${response.status}, Response: ${text}. Stopping auto-login.`);
          });
          throw new Error(`GraphQL request failed with status ${response.status}`);
        }
        return response.json();
        })
      .then(({ data, errors }) => {
        if (errors || !data?.viewer?.databaseId) {
          // Handle GraphQL errors (e.g., "unauthenticated") or null viewer
          // Log the actual errors object
          console.log(`[Layout V3 - Step D] GraphQL query logical failure or viewer data missing. ${errors ? `Errors: ${JSON.stringify(errors)}.` : 'No viewer data.'} Stopping auto-login.`);
          throw new Error(`No active WordPress session detected or GraphQL error: ${JSON.stringify(errors)}`);
    }

        // Success Case: WP Session Validated
        /** @type {WpUserSync} */
        const wpUserDetails = {
          wpUserId: data.viewer.databaseId,
          email: data.viewer.email,
          username: data.viewer.username,
          name: data.viewer.name
        };
        console.log("[Layout V3 - Step D] WP session verified via GraphQL viewer query. User details:", wpUserDetails); // Added V3 prefix
        
        // Step E: Trigger Backend Sync
        console.log("[Layout V3 - Step E] Triggering backend sync with verified WP user details..."); // Added V3 prefix
        return fetch('/api/auth/wp-user-sync', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(wpUserDetails)
            });
        })
        .then(response => {
        if (!response) {
           // This case might be less likely with fetch, but good to have
           console.error('[Layout V3 - Step E] No response received from sync endpoint.');
           throw new Error('No response received from sync endpoint');
          }
                 if (!response.ok) {
            // Log the response text for backend errors
            return response.text().then(text => {
              console.error(`[Layout V3 - Step E] Backend sync fetch failed. Status: ${response.status}, Response: ${text}`);
              throw new Error(`Backend sync failed with status ${response.status}. Response: ${text}`);
            });
                 } 
        return response.json();
            })
        .then(syncResult => {
        // Step G: Handle Sync Success
        if (syncResult?.success) {
          console.log("[Layout V3 - Step G] Backend sync successful. Invalidating data to refresh session state."); // Added V3 prefix
          
          // Show success toast
                        toasts.show(
            'Logged in via WordPress',
                          'success'
                        );
          
          // Invalidate SvelteKit data and navigate
          invalidateAll().then(() => {
            // Wait a tick to allow page store to update
            setTimeout(() => {
              const targetPath = '/dashboard';
              console.log(`[Layout V3 - Step G] Redirecting to ${targetPath}`); // Added V3 prefix
              goto(targetPath, { replaceState: true });
            }, 0);
          });
                    } else {
          // Log the actual failure result from the backend
          console.error(`[Layout V3 - Step G] Backend sync reported failure: ${JSON.stringify(syncResult)}`);
          throw new Error(`Backend sync reported failure: ${JSON.stringify(syncResult)}`);
                }
            })
            .catch(error => {
        // Log the full error object
        console.log(`[Layout V3] Auto-login process halted. Error:`, error);
            });
    } else if (browser) {
      console.log('[Layout V3] Active Better Auth session detected. Skipping WP session check.'); // Added V3 prefix
    } 
    // --- END NEW WP Session Check ---

    // --- SSE Listener Setup --- 
    let localEventSource = null;
    if (browser) {
        console.log('[Layout Sync Listener] Setting up EventSource...');
        localEventSource = new EventSource('/api/auth/sync-stream');

        localEventSource.onopen = () => {
          console.log('[Layout Sync Listener] EventSource connection opened.');
        };

        localEventSource.onmessage = (event) => {
          try {
            const data = JSON.parse(event.data);
            console.log('[Layout Sync Listener] Received message:', data); // DEBUG

            if (data.type === 'user-update') {
              const currentUserId = $page.data.user?.id; 
              // Enhanced Logging: Include received timestamp
              console.log(`[Layout Sync Listener] User update received. Target User: ${data.userId}, Current User: ${currentUserId}, Timestamp in message: ${data.updatedAt}`); // DEBUG
              
              // Check if userId matches AND the updatedAt timestamp exists in the message
              if (currentUserId && data.userId === currentUserId && data.updatedAt) { 
              console.log('[Layout Sync Listener] Relevant user update event received. Invalidating data...');
              invalidateAll(); // Invalidate data to trigger re-fetch
                } else {
              console.log('[Layout Sync Listener] Update not applicable to current user or missing timestamp. Skipping.'); // DEBUG
              }
            }
          } catch (error) {
          console.error('[Layout Sync Listener] Error handling message:', error); // DEBUG
          }
        };

        localEventSource.onerror = (error) => {
        console.error('[Layout Sync Listener] EventSource error:', error); // DEBUG
        // Handle reconnection logic if needed
      };
    }

    // Performance monitoring setup
    if (browser) {
      initPerformanceMonitoring();
      // initImageOptimization(); // TEMP: Disabled
    }

    // Register service worker
    if (browser && !dev) { // Don't register SW in dev mode
      registerServiceWorker();
    }

    // === END MOUNT FUNCTION ===

    try {
      // Initialize mobile state
      const checkMobile = () => {
        isMobile = window.innerWidth < 1024;
        // Close mobile menu if resizing to desktop
        if (!isMobile && isSidebarOpen) {
          isSidebarOpen = false;
        }
      };
      
      // Initial mobile check
      checkMobile();
      
      // Register resize listener
      window.addEventListener('resize', checkMobile);
      
      // Initialize sidebar state from localStorage
      if (typeof window !== 'undefined' && window.localStorage) {
        const storedState = localStorage.getItem('sidebar-collapsed');
        // Default to collapsed on mobile, respect storage on desktop
        isSidebarCollapsed = window.innerWidth < 1024 ? true : storedState === 'true';
        localStorage.setItem('sidebar-collapsed', isSidebarCollapsed.toString()); // Ensure storage is set initially
      }
      
      // Enable dark mode by default
      document.documentElement.classList.add('dark');
      
      // Initialize image optimization
      // const cleanupLazyLoading = handleLazyLoading(); // TEMP: Comment out this call
      // initImageOptimization(); // TEMP: Comment out this call
      
      // Initialize performance monitoring
      // initPerformanceMonitoring();
      
      // Register Service Worker
      // registerServiceWorker().catch(error => {
      //   console.debug('[SW] Registration error (non-critical):', error.message);
      // });
      
      // Find and report any problematic Tailwind classes
      // findProblematicClasses(); // Assuming this is for debugging, keep if needed
      
      // Return cleanup function
      return () => {
        if (localEventSource) {
          console.log('[Layout Sync Listener] Closing EventSource connection.');
          localEventSource.close();
        }
        window.removeEventListener('resize', checkMobile);
        // cleanupLazyLoading?.(); // TEMP: Comment out this call
      };
    } catch (error) {
      console.error('[Layout] Error initializing layout:', error);
    }
  });

  /**
   * @description Toggles the visibility of the mobile sidebar.
   * @returns {void}
   */
  function toggleMobileSidebar() {
    isSidebarOpen = !isSidebarOpen;
    console.log('Toggled mobile sidebar, isSidebarOpen:', isSidebarOpen);
  }

  /**
   * @description Toggles the collapsed state of the desktop sidebar and saves to localStorage.
   * @returns {void}
   */
  function toggleDesktopSidebarCollapsed() {
    isSidebarCollapsed = !isSidebarCollapsed;
    localStorage.setItem('sidebar-collapsed', isSidebarCollapsed.toString());
    // No need to update body class anymore, handled by class binding on app-layout
  }

  /**
   * @description Forces application of CSS custom properties (primarily for design system route).
   * @returns {void}
   */
  function forceCSSVariableApplication() {
    if (typeof window === 'undefined') return;
    
    const computedStyle = getComputedStyle(document.documentElement);
    const cssColorVars = [
      '--background', '--foreground', '--card', '--card-foreground', 
      '--popover', '--popover-foreground', '--primary', '--primary-foreground', 
      '--secondary', '--secondary-foreground', '--muted', '--muted-foreground', 
      '--accent', '--accent-foreground', '--destructive', '--destructive-foreground', 
      '--border', '--input', '--ring'
    ];
    
    cssColorVars.forEach(varName => {
      const value = computedStyle.getPropertyValue(varName).trim();
      if (value) {
        document.documentElement.style.setProperty(varName, value);
        document.body.style.setProperty(`--applied${varName}`, `hsl(${value})`);
      }
    });
  }

  /**
   * @description Checks for potential layout problems like overlapping elements.
   * @returns {void}
   */
  function checkForLayoutIssues() {
    console.log('[Layout] Checking for layout issues...'); 
    // Add placeholder logic or restore original logic if missing
    const sidebarElement = document.querySelector('[data-testid="sidebar"]');
    if (sidebarElement) {
      console.log('[Layout] Sidebar element found during layout check.');
      // Example check: Log computed style
      // const styles = window.getComputedStyle(sidebarElement);
      // console.log('[Layout] Sidebar computed display:', styles.display);
    } else {
      console.warn('[Layout] Sidebar element not found during layout check.');
    }
  }

  /**
   * @description Initializes layout checks and observers after mount.
   * @returns {Promise<void>}
   */
  async function initializeLayout() {
    if (typeof window === 'undefined') return;
    console.log('[Layout] Starting layout initialization...');
    
    // --- Temporarily comment out ALL calls inside ---
    // checkForLayoutIssues(); 
    
    // console.log('[Layout] Checking for problematic shadcn-svelte classes...');
    // await checkForProblematicClasses(); 
    
    // setupResizeObserver();
    
    // setTimeout(checkVisibilityAfterMount, 1000); 
    // --- End temporary commenting ---

    console.log('[Layout] initializeLayout finished (minimal execution).'); // Add log
  }
  
  // Initialize on mount
  onMount(() => {
    console.log("[Layout] Component mounted");
    
    // Initialize layout checks and observers
    initializeLayout();
    
    // Additional check after a little delay
    setTimeout(() => {
      console.log("[Layout] Running visibility check 1 second after mounting");
      const contentGrid = document.querySelector('.content-grid');
      console.log("[Layout] Content grid template columns:", 
        contentGrid ? window.getComputedStyle(contentGrid).gridTemplateColumns : 'not found');
        
      // Full dump of all sidebar-related elements
      console.log("[Layout] All elements with sidebar in class:", 
        document.querySelectorAll('[class*="sidebar"]').length);
    }, 1000);
  });

  import { auth, useSession } from '$lib/auth-client';
  import AuthButtons from '$lib/components/AuthButtons.svelte';
  import { navigating } from '$app/stores';
  // Remove direct import
  // import { Loader2 } from 'lucide-svelte';
  // Import wrapper and icon from compat layer
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { Loader2 } from '$lib/utils/lucide-compat.js';

  const { data: session } = useSession();

  // Store the last known updatedAt timestamp
  let lastUpdatedAt = $state(null);

  $effect(() => {
    const currentUser = $page.data.user;
    if (currentUser?.updatedAt) {
      if (lastUpdatedAt === null) {
        // Initialize on first load
        lastUpdatedAt = currentUser.updatedAt;
        console.log('[Layout Effect] Initialized lastUpdatedAt:', lastUpdatedAt); // DEBUG
      } else if (currentUser.updatedAt !== lastUpdatedAt) {
        // Timestamp has changed, trigger toast
        console.log('[Layout Effect] User data updated via $effect. Old ts:', lastUpdatedAt, 'New ts:', currentUser.updatedAt); // DEBUG
        toasts.show(
          'Your profile details have been updated from WordPress.',
          'success'
        );
        lastUpdatedAt = currentUser.updatedAt; // Update the stored timestamp
      }
    } else if (lastUpdatedAt !== null && !currentUser) {
       // User logged out, reset timestamp
       console.log('[Layout Effect] User logged out, resetting lastUpdatedAt.'); // DEBUG
       lastUpdatedAt = null;
    }
  });

  /**
   * @typedef {import('$lib/components/ui/toast/use-toast').ToastActionPayload} ToastActionPayload
   */

  /**
   * Placeholder for the actual toast function.
   * Replace with your project's toast implementation.
   * @param {ToastActionPayload} options
   */
  // Remove the placeholder function
  // function showToast(options) {
  //  console.log('Showing Toast:', options);
  // }

  /** @type {User | null | undefined} */
  let currentUser = $state(undefined); // Use $state for reactivity

  // Subscribe to page store to get user data
  page.subscribe(value => {
      // Only update if the initial state is undefined or user logs out
      if (currentUser === undefined || (currentUser && !value.data.user)) {
          currentUser = value.data.user;
      }
      // Avoid overwriting currentUser if it's already set and page data updates later without user info
  });

  onMount(async () => {
    // Only run the check if we haven't determined the user status yet or if they are logged out
    if (!currentUser) {
      console.debug('[Layout Mount] No active SK session detected. Checking WP sync...');
      try {
        // Modify the fetch call to include credentials
        const response = await fetch('/api/auth/sync', {
          credentials: 'include' // <<< ADD THIS OPTION
        }); // Browser sends cookies automatically
        if (!response.ok) {
          // Don't throw, just log. The backend handles non-200 for invalid WP sessions etc.
          console.debug(`[Layout Mount] Sync check response not OK: ${response.status}`);
          const errorData = await response.json().catch(() => ({})); // Attempt to parse error
          console.debug(`[Layout Mount] Sync error data:`, errorData);
           // No toast needed here, just means no auto-login happened
           return;
        }

        const data = await response.json();
        console.debug('[Layout Mount] Sync check response OK:', data);

        if (data.valid && data.session_created) {
          console.log('[Layout Mount] Auto-login successful via sync endpoint.');
          // Use the imported toast function
          toasts.show(
            "Auto Login Successful",
            "You've been automatically logged in based on your WordPress session.",
            'success' // Use 'success' type for consistency
          );
          // Reload the page to ensure SvelteKit picks up the new session cookie
          // and the layout/stores reflect the logged-in state correctly.
          // Use invalidateAll() + goto() for a smoother transition if preferred,
          // but location.reload() is simpler and guarantees a fresh state.
          window.location.reload();
        } else {
            console.debug('[Layout Mount] Sync check successful, but no new session created (or autosync inactive).');
        }
      } catch (error) {
        console.error('[Layout Mount] Error during automatic sync check:', error);
        // Use the imported toast function
        toasts.show(
          "Sync Check Error",
          "Could not check login status automatically.",
          'destructive'
        );
      }
    } else {
         console.debug('[Layout Mount] Active SK session detected. Skipping WP sync check.');
    }
  });

  // SSE Listener for Live Sync Updates
  /** @type {EventSource | null} */
  let eventSource = $state(/** @type {EventSource | null} */ (null));

  // Update currentUserStore when session changes
  $effect(() => {
    // Access the session store's value directly
    if ($session?.user) { 
      console.log('[Layout Session Effect] Updating currentUserStore from session:', $session.user);
      currentUser = $session.user;
    } else {
      console.log('[Layout Session Effect] Session null or no user, setting currentUserStore to null.');
      currentUser = null;
    }
  });

  /**
   * @param {Event & { target: EventTarget | null }} e - The event object.
   */
  function handleImageError(e) {
    console.warn("Image failed to load:", e);
    const target = /** @type {HTMLImageElement} */ (e.target);
    if (target) {
      target.style.display = 'none'; // Hide broken image
    }
  }

  async function handleLogout() {
    try {
      console.log('[Layout] Attempting sign out...');
      // @ts-ignore - Assuming auth.signOut exists
      // Remove callbackUrl if not supported by client-side signOut
      await auth.signOut({ redirect: true /*, callbackUrl: '/' */ }); 
      console.log('[Layout] Sign out successful.');
    } catch (error) {
      console.error('[Layout] Error during sign out:', error);
      toasts.show(
        "Logout Error",
        "Could not log out. Please try again later.",
        'destructive'
      );
    }
  }

  let wpSyncStatus = $state('idle'); // idle, checking, syncing, synced, failed, failedCheck, notLoggedIn, noTokenFound, noCheckNeeded
  let wpAuthBridgeIframe = $state(null); // Reference to the iframe element
  let wpSyncTimeout = $state(null); // Timeout handler
  const WP_ORIGIN = 'https://asapdigest.local'; // Define WP Origin (Make env-aware if needed)

  // --- WP Sync State ---
  let shouldInitializeBridge = $state(false); // Flag to trigger bridge init
  let lastCheckTimestamp = $state(0); // Timestamp of the last check attempt
  const CHECK_DEBOUNCE_MS = 15000; // Check WP max once every 15 seconds

  // Function to handle messages from the WP bridge iframe
  async function handleWpBridgeMessage(event) {
    if (event.origin !== WP_ORIGIN) {
      // console.debug('[Layout Bridge] Ignored message from unexpected origin:', event.origin); // Optional debug
      return;
    }

    // Clear the timeout since we received a message
    if (wpSyncTimeout) clearTimeout(wpSyncTimeout);

    const data = event.data;
    console.log('[Layout Bridge] Received message:', data); // DEBUG

    if (data?.type === 'wpAuthToken' && data.token) {
      wpSyncStatus = 'syncing';
      try {
        const response = await fetch('/api/auth/verify-sync-token', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token: data.token })
        });

        const result = await response.json();

        if (response.ok && result?.success) {
          wpSyncStatus = 'synced';
          toasts.show(
            'Session Synced',
            'Your WordPress session was automatically detected.',
            'success'
          );

          // --- Fetch session data explicitly after successful sync ---
          try {
            console.log('[Layout Bridge] Fetching session data after successful sync...');
            const sessionResponse = await fetch('/api/auth/session'); // Assuming this endpoint returns current session
            if (sessionResponse.ok) {
              const sessionData = await sessionResponse.json();
              if (sessionData?.user) {
                console.log('[Layout Bridge] Successfully fetched session data, updating local state:', sessionData.user);
                currentUser = sessionData.user; // Update local reactive state
                // Optionally update a dedicated store if currentUser isn't sufficient
              } else {
                 console.warn('[Layout Bridge] Fetched session data, but no user object found.');
              }
            } else {
               console.warn(`[Layout Bridge] Fetch to /api/auth/session failed with status: ${sessionResponse.status}`);
            }
          } catch (sessionError) {
             console.error('[Layout Bridge] Error fetching session data after sync:', sessionError);
          }
          // --- End explicit session fetch ---

          // Refresh page data to reflect new SK session
          invalidateAll();
          // Optionally remove the success indicator after a delay
          setTimeout(() => { if (wpSyncStatus === 'synced') wpSyncStatus = 'idle'; }, 3000);

        } else {
          throw new Error(result?.error || 'Token verification failed');
        }
      } catch (error) {
        console.error('[Layout Bridge] Error verifying sync token:', error);
        wpSyncStatus = 'failed';
        toasts.show(
          'Sync Error',
          'Could not automatically sync session.',
          'destructive'
        );
      } finally {
         cleanupWpAuthBridge(); // Cleanup after processing token
      }

    } else if (data?.type === 'wpAuthStatus') {
       wpSyncStatus = data.loggedIn ? 'failed' : 'notLoggedIn'; // Logged in but no token found is a failure state for sync
       if(wpSyncStatus === 'failed') console.warn('[Layout Bridge] WP user logged in, but no valid sync token found.');
       cleanupWpAuthBridge(); // Cleanup after receiving status
    }
  }

  // Function to cleanup iframe and listener
  function cleanupWpAuthBridge() {
     if (wpSyncTimeout) clearTimeout(wpSyncTimeout);
     window.removeEventListener('message', handleWpBridgeMessage);
     if (wpAuthBridgeIframe) {
        wpAuthBridgeIframe.remove();
        wpAuthBridgeIframe = null;
        console.log('[Layout Bridge] Cleaned up iframe and listener.'); // DEBUG
     }
     // Don't reset status here, let the indicator show final state
  }

  // --- Helper Functions --- 
  /** Checks if a check was run recently */
  function isRecentCheck() {
    return Date.now() - lastCheckTimestamp < CHECK_DEBOUNCE_MS;
  }

  /** Performs the check for WP token and sets flag */
  async function checkWpTokenAndMaybeBridge() {
    // Condition checks moved to the calling context (onMount, event listeners)
    console.log('[Layout Check Function] Initiating WP token check via SK proxy...'); // Use proxy message
    wpSyncStatus = 'checking'; 
    lastCheckTimestamp = Date.now(); 
    shouldInitializeBridge = false; 

    // Use the proxied path
    const checkTokenUrl = '/wp-api/asap/v1/check-sync-token'; 
    let attempt = 0;
    const MAX_FETCH_RETRIES = 2; 
    const FETCH_RETRY_DELAY = 500; 
    let fetchSuccess = false;

    while (attempt <= MAX_FETCH_RETRIES && !fetchSuccess) {
        attempt++;
        console.debug(`[Layout Check Function] Attempt ${attempt} to fetch ${checkTokenUrl}...`);
        try {
            console.log(`[Layout Check Function] >>> MAKING FETCH CALL to ${checkTokenUrl}`); 
            const tokenCheckResponse = await fetch(checkTokenUrl, {
                 // credentials: 'include' // REMOVED for proxy
            });

            if (!tokenCheckResponse.ok) {
                const status = tokenCheckResponse.status;
                const errorData = await tokenCheckResponse.json().catch(() => ({ message: `HTTP status ${status}` })); 
                const errorMsg = errorData?.message || `Proxy check failed with status ${status}`;
                
                // Handle specific statuses if needed (e.g., 401 from WP means not logged in)
                if (status === 401 || status === 404 || errorMsg.includes('not logged in') || errorMsg.includes('No WP auth cookies provided to proxy')) { 
                    console.info(`[Layout Check Function] WP token check via proxy indicates user not logged into WP or endpoint issue (Status: ${status}, Msg: ${errorMsg}).`);
                    wpSyncStatus = 'notLoggedIn'; 
                } else {
                     console.warn(`[Layout Check Function] WP token check via proxy failed. Status: ${status}, Response: ${JSON.stringify(errorData)}`);
                     wpSyncStatus = 'failedCheck';
                }
                fetchSuccess = true; 
                break; 
            }

            const tokenCheckData = await tokenCheckResponse.json();
            console.log('[Layout Check Function] WP token check via proxy response OK: ', tokenCheckData);
            fetchSuccess = true;

            // Check the actual boolean value from the response
            if (!tokenCheckData?.tokenExists) { 
                console.info(`[Layout Check Function] No valid sync token found on WP side via proxy (tokenExists: false). Skipping iframe bridge.`);
                wpSyncStatus = 'noTokenFound';
            } else {
                console.info('[Layout Check Function] Valid sync token detected on WP side via proxy. Flagging to proceed with iframe bridge...');
                shouldInitializeBridge = true; // Set flag to trigger bridge init via $effect
            }
            break; // Exit loop on success

        } catch (fetchError) {
            console.error(`[Layout Check Function] Attempt ${attempt} failed: Error fetching SK proxy endpoint ${checkTokenUrl}:`, fetchError); 
            if (attempt > MAX_FETCH_RETRIES) {
                 wpSyncStatus = 'failedCheck';
                 // Display a more generic error as proxy should handle CORS/SSL
                 toasts.show(
                     'Connection Error',
                     'Could not connect to the backend proxy.',
                     'destructive'
                 );
            } else {
                 await new Promise(resolve => setTimeout(resolve, FETCH_RETRY_DELAY));
            }
        }
    } // End while loop

    if (!fetchSuccess) {
         console.error('[Layout Check Function] Max retries reached or other fetch failure. Aborting check via proxy.');
         wpSyncStatus = 'failedCheck';
    }
    console.log('[Layout Check Function] Finished check via proxy. shouldInitializeBridge = ', shouldInitializeBridge);
  }

  // Effect to actually initialize the bridge if the flag is set
  $effect(() => {
    // This effect runs whenever shouldInitializeBridge changes.
    if (shouldInitializeBridge) {
      console.log('[Layout Bridge Effect] shouldInitializeBridge is true. Initializing...');
      // Setup listener first (Important to attach before setting src)
      window.addEventListener('message', handleWpBridgeMessage);

      // Create and append iframe
      const iframe = document.createElement('iframe');
      iframe.id = 'wp-auth-bridge';
      iframe.style.display = 'none'; 
      iframe.src = `${WP_ORIGIN}/`; // Load WP homepage (or any page with wp_footer)
      document.body.appendChild(iframe);
      wpAuthBridgeIframe = iframe; // Store reference for cleanup
      console.log('[Layout Bridge Effect] Iframe bridge created and listener attached.'); 

      // Set timeout for response from the iframe via postMessage
      wpSyncTimeout = setTimeout(() => {
          console.warn('[Layout Bridge] Timeout waiting for postMessage from WP bridge iframe.');
          // Only mark as failed if it was still in the initial 'checking' state 
          // (which might have been updated by the time this timeout runs)
          if (wpSyncStatus === 'checking') { 
               wpSyncStatus = 'failed'; // Timeout is a failure
               // SILENT FAIL: No toast needed for background timeout
          }
          cleanupWpAuthBridge(); // Cleanup on timeout
      }, 15000); // 15 second timeout

      // Reset the flag immediately after initiating the bridge setup
      // to prevent this effect from re-running if other state changes trigger it.
      shouldInitializeBridge = false; 
    }
  });

  // ---> Placeholder for log, handleSyncSuccess, triggerBackendSync, checkWordPressSession functions <---
  // ---> (These will be added in the next step) <---
  function log(message, ...optionalParams) { if (browser) console.log('[Layout Auth Check]', message, ...optionalParams); }
  async function handleSyncSuccess() {
    log("Backend sync successful. Invalidating data to refresh session state.");
    // Ensure toasts store is available and used correctly
    toasts?.add({ title: 'Success', description: 'Logged in via WordPress.', type: 'success' });
    await invalidateAll(); // Re-runs load functions using new cookie
    
    // Optional: Redirect after invalidation completes and $page store updates
    await new Promise(resolve => setTimeout(resolve, 50)); // Wait a tick
    // Check if still on a non-dashboard page after potential load function redirect
    if ($page.url.pathname !== '/dashboard') { 
        log("Redirecting to dashboard after successful sync.");
        goto('/dashboard', { replaceState: true });
    }
  }
  async function triggerBackendSync(wpUserDetails) {
    log("Triggering backend sync with WP user details:", wpUserDetails);
    const syncEndpoint = '/api/auth/wp-user-sync';
    try {
        const syncResponse = await fetch(syncEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(wpUserDetails)
        });

        if (!syncResponse.ok) {
            const errorText = await syncResponse.text();
            log(`Backend sync request failed with status: ${syncResponse.status}. Response: ${errorText}`);
            toasts?.add({ title: 'Sync Error', description: `Backend sync failed (Status: ${syncResponse.status})`, type: 'error' });
            return; // Stop flow here, backend handled error
        }
        
        // Success Case: Backend handled sync, cookie should be set
        const result = await syncResponse.json();
        log("Backend sync request successful. Result:", result);
        handleSyncSuccess(); // Proceed to invalidation/UI update

    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        log(`Network error during backend sync request: ${message}.`);
        toasts?.add({ title: 'Network Error', description: 'Could not reach sync endpoint.', type: 'error' });
    }
  }

  /**
   * Queries the WordPress GraphQL endpoint to check for an active WP session.
   * Uses the 'viewer' query and sends credentials.
   */
  async function checkWordPressSession() {
      log("Checking WordPress session via GraphQL viewer query...");
      const viewerQuery = `query GetViewerDetails { viewer { databaseId email username name } }`;
      const wpGraphqlUrl = import.meta.env.VITE_PUBLIC_WP_GRAPHQL_URL || 'https://asapdigest.local/graphql';

      if (!wpGraphqlUrl) {
          log("Error: PUBLIC_WP_GRAPHQL_URL environment variable is not set.");
          toasts?.add({ title: 'Config Error', description: 'GraphQL endpoint URL missing.', type: 'error' });
          return;
      }

      try {
          const response = await fetch(wpGraphqlUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ query: viewerQuery }),
              credentials: 'include' // ESSENTIAL: Sends WP cookies
          });

          if (!response.ok) {
              log(`GraphQL query failed with status: ${response.status}. Is CORS configured correctly on WP?`);
              return; // Stop the flow
          }

          const { data, errors } = await response.json();

          if (errors || !data?.viewer?.databaseId) {
              log(`GraphQL query completed but no authenticated viewer found. Errors: ${JSON.stringify(errors)}`);
              return; // Stop the flow
          }

          // Success Case: WP Session Validated
          if (!data.viewer.email) {
              log("GraphQL viewer query succeeded but email is missing.");
              toasts?.add({ title: 'Data Error', description: 'WP user email missing.', type: 'error' });
              return;
          }
          
          /** @type {WpUserSync} */
          const wpUserDetails = {
              wpUserId: data.viewer.databaseId,
              email: data.viewer.email,
              username: data.viewer.username || data.viewer.email.split('@')[0],
              name: data.viewer.name || data.viewer.username || 'WP User'
          };
          log("WP session verified via GraphQL viewer query. User details:", wpUserDetails);
          triggerBackendSync(wpUserDetails);

    } catch (error) {
          const message = error instanceof Error ? error.message : String(error);
          log(`Network error during GraphQL viewer query: ${message}. Check browser console for SSL/CORS issues.`);
      }
  }

  onMount(() => {
    if (!browser) return;
    log("Layout mounted. Checking auth state...");
    const unsubscribe = page.subscribe(currentPage => {
        if (currentPage.data.user) {
            log("Active Better Auth session found via $page store. Skipping WP check.", currentPage.data.user);
        } else {
            log("No active Better Auth session found via $page store. Initiating WP session check...");
            checkWordPressSession();
    }
    });
    return () => {
        log("Layout unmounting. Unsubscribing from page store.");
        unsubscribe();
    };
  });
</script>

<svelte:head>
  <!-- Set meta theme color -->
  <meta name="theme-color" content="#00ffff">
</svelte:head>

<!-- Skip layout for auth routes -->
{#if isAuthRoute}
  {@render children?.()}
{:else}
  <div class={`app-layout ${isSidebarCollapsed ? 'sidebar-collapsed' : ''}`}>
    <!-- Header -->
    <header class="app-header h-[64px] border-b border-[hsl(var(--border)/0.8)]">
      <div class="container mx-auto flex h-full items-center justify-between px-3">
        <div class="flex items-center gap-3">
          <!-- Mobile sidebar toggle button - visible on small screens -->
          {#if isMobile}
            <button
              class="mobile-menu-trigger z-[var(--z-sidebar-trigger)] rounded-md p-2"
              onclick={toggleMobileSidebar}
              aria-label="Toggle menu"
            >
              {#if isSidebarOpen}
                <!-- Use Icon component -->
                <Icon icon={X} class="w-6 h-6" />
              {:else}
                <!-- Use Icon component -->
                <Icon icon={Menu} class="w-6 h-6" />
              {/if}
            </button>
          {/if}
          <!-- Logo -->
          <span class="text-xl font-semibold">
            <span class="text-[hsl(var(--primary))]">⚡️ ASAP</span> Digest
          </span>
        </div>
        
        <!-- Header actions (search, notifications, user avatar) -->
        <div class="flex items-center gap-4">
          <!-- Search input -->
          <button
            class="focus-visible:ring-ring rounded-md bg-[hsl(var(--muted)/0.1)] p-2 transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.2)] focus-visible:outline-none focus-visible:ring-2"
            aria-label="Search"
          >
            <!-- Use Icon component -->
            <Icon icon={Search} class="w-5 h-5" />
          </button>
          
          
        </div>
        
        <div class="flex avatar-notifications-wrapper"><!--! start of avatar-notifications wrapper -->

        <div class="relative"><!--! start of avatar wrapper -->

          <!-- User avatar with dropdown -->
          <button
            class="focus-visible:ring-ring flex items-center space-x-2 rounded-full p-1 transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] focus-visible:outline-none focus-visible:ring-2 dark:hover:bg-[hsl(var(--muted)/0.2)]"
            onclick={() => {
              if (typeof window === 'undefined') return;
              const dropdown = document.getElementById('user-dropdown');
              if (dropdown) {
                dropdown.classList.toggle('hidden');
              }
            }}
            aria-haspopup="true"
          >
            <div class="h-8 w-8 overflow-hidden rounded-full bg-[hsl(var(--muted)/0.2)]">
              <!-- Use real user data for avatar -->
              <img
                src={$page.data.user?.avatarUrl || 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E'}
                alt={$page.data.user?.displayName || 'User Avatar'}
                class="h-full w-full object-cover"
                onerror={(e) => {
                  if (typeof window === 'undefined') return;
                  /** @type {HTMLImageElement} */
                  const target = e.target; // Type assertion replaced with JSDoc
                  target.onerror = null;
                  // Use Icon component for fallback INSIDE the img tag's logic is complex,
                  // Using a simple SVG data URI fallback is often cleaner here.
                  // Keep the SVG data URI for onerror fallback.
                  target.src =
                    'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E';
                }}
              />
            </div>
          </button>
          
          <!-- User dropdown menu -->
          {#if $page.data.user}
            <div id="user-dropdown" class="absolute right-0 mt-2 hidden w-48 rounded-md border border-[hsl(var(--border))] bg-[hsl(var(--background))] shadow-lg dark:border-[hsl(var(--muted-foreground)/0.2)] dark:bg-[hsl(var(--muted))] z-[var(--z-dropdown)]">
              
              <!-- User & Email -->
              <div class="border-b border-[hsl(var(--border))] p-2 dark:border-[hsl(var(--muted-foreground)/0.2)]">
                <!-- Use real user data -->
                <div class="font-semibold">
                  {$page.data.user.displayName || 'User Name'}
                </div>
                <div class="text-xs text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">
                  {$page.data.user.email || 'user@example.com'}
                </div>
              </div>

              <!-- Dashboard, Settings, Logout -->
              <div class="py-1">
                <a href="/dashboard" class="flex items-center gap-2 px-4 py-2 text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]">
                  <Icon icon={LayoutDashboard} class="w-4 h-4" /> 
                  <span>Dashboard</span>
                </a>
                <a href="/settings" class="flex items-center gap-2 px-4 py-2 text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]">
                  <Icon icon={Settings} class="w-4 h-4" /> 
                  <span>Settings</span>
                </a>
                <!-- Use auth client signout -->
                <button onclick={handleLogout} class="flex items-center gap-2 w-full px-4 py-2 text-left text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]">
                  <Icon icon={LogOut} class="w-4 h-4" /> 
                  <span>Logout</span>
                </button>
              </div>
            </div><!--! end of user dropdown -->
          {:else}
            <!-- Optionally show login button if no user -->
            <div id="user-dropdown" class="absolute right-0 mt-2 hidden w-48 rounded-md border border-[hsl(var(--border))] bg-[hsl(var(--background))] shadow-lg dark:border-[hsl(var(--muted-foreground)/0.2)] dark:bg-[hsl(var(--muted))] z-[var(--z-dropdown)]">
              <div class="py-1">
                <a href="/login" class="block px-4 py-2 text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]">
                  Login
                </a>
              </div>
            </div>
          {/if}
          <!-- END of User avatar with dropdown -->

        </div> <!-- end of avatar wrapper -->

        <div class="relative"><!--! start of notifications wrapper -->
          <button class="focus-visible:ring-ring rounded-full p-2 transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] focus-visible:outline-none focus-visible:ring-2 dark:hover:bg-[hsl(var(--muted)/0.2)]">
            <!-- Use Icon component -->
            <Icon icon={Bell} class="w-5 h-5" />
          </button>
          <!-- Use 0 as placeholder, ideally fetch real count later -->
          <div class="absolute right-0 top-0 flex h-5 w-5 items-center justify-center rounded-full bg-[hsl(var(--destructive))] text-xs font-bold text-[hsl(var(--destructive-foreground))]">
            0 
          </div>
        </div><!--! end of notifications wrapper -->

      </div><!--! end of avatar-notifications wrapper -->

      </div>
    </header>
    
    <!-- Sidebar / Mobile Menu -->
    <aside
      class={`app-sidebar ${isMobile ? 'mobile-menu' : ''} ${isMobile && isSidebarOpen ? 'open' : ''}`}
      data-mobile={isMobile}
      data-open={isMobile && isSidebarOpen}
    >
      <MainSidebar
        collapsed={isSidebarCollapsed}
        toggleSidebar={toggleDesktopSidebarCollapsed}
        isMobile={isMobile}
        closeMobileMenu={() => (isSidebarOpen = false)}
        user={$page.data.user}
      />
    </aside>
    
    <!-- NEW: Mobile Menu Backdrop -->
    {#if isMobile && isSidebarOpen}
      <button 
        type="button"
        class="mobile-menu-backdrop" 
        onclick={() => (isSidebarOpen = false)}
        onkeydown={(e) => e.key === 'Escape' && (isSidebarOpen = false)}
        aria-label="Close mobile menu"
      ></button>
    {/if}
    
    <!-- Main content area -->
    <main class="app-content">
      <!-- PWA install prompt -->
      {#if getIsInstallable() && !getIsPWA()} 
        <InstallPrompt />
      {/if}
      
      <!-- Main content -->
      <div>
        {#if $navigating}
          <div class="flex justify-center items-center min-h-[50vh]">
            <!-- Use Icon wrapper -->
            <Icon icon={Loader2} class="w-8 h-8 animate-spin text-[hsl(var(--primary))]" />
          </div>
        {:else}
          {@render children?.()}
        {/if}
      </div>
    </main>
    
    <!-- Footer -->
    <footer class="app-footer border-t border-[hsl(var(--border)/0.8)]">
      <div class="container mx-auto px-[1rem] py-[1rem]">
        <Footer />
      </div>
    </footer>
    
    <!-- PWA Testing Controls -->
    <TestPwaControls />
  </div>
  
  <!-- Performance monitor (dev only) - Positioned outside app-layout -->
  {#if import.meta.env.DEV}
    <PerformanceMonitor />
  {/if}

  <GlobalFAB />

  {#if $session}
    <div class="fixed bottom-4 right-4 p-2 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-full shadow-lg">
      <span class="flex items-center gap-2">
        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
        Connected
      </span>
    </div>
  {/if}

  <!-- Sync Status Indicator -->
  <div class="fixed bottom-4 right-4 text-xl z-[var(--z-fab)] pointer-events-none transition-opacity duration-500"
       class:opacity-0={wpSyncStatus === 'idle' || wpSyncStatus === 'noCheckNeeded'} >
    {#if wpSyncStatus === 'checking'}
      <span class="animate-pulse text-yellow-400" title="Checking WP Session...">⚡️</span>
    {:else if wpSyncStatus === 'syncing'}
      <span class="animate-spin text-blue-400" title="Syncing Session...">⚙️</span>
    {:else if wpSyncStatus === 'synced'}
      <span class="text-green-500" title="Session Synced!">✅</span>
    {:else if wpSyncStatus === 'failed'}
      <span class="text-red-500" title="Sync Failed">❌</span>
     {:else if wpSyncStatus === 'notLoggedIn'}
       <!-- Optional: Show greyed out? Or keep hidden via opacity-0 -->
       <!-- <span class="text-gray-500" title="Not logged into WP">🔌</span> -->
    {/if}
  </div>
{/if}

<!-- Add local ToastContainer component -->
<Toaster />

<style>
  /* Add CSS for lazy-loaded images */
  :global(img.lazy) {
    opacity: 0;
    transition-property: opacity;
    transition-duration: 300ms;
  }
  
  :global(img:not(.lazy)) {
    opacity: 100;
  }
  
  /* Add CSS for offline mode - use CSS variables from Tailwind */
  :global(body.offline) {
    --offline-indicator: hsl(var(--warning, 48 96% 53%));
  }
  
  :global(body.offline::before) {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 0.25rem;
    background-color: var(--offline-indicator);
    z-index: var(--z-max); /* Ensure it's above everything */
  }
  
  /* Prevent horizontal overflow */
  :global(html, body) {
    overflow-x: hidden;
    width: 100%;
    max-width: 100vw;
  }
  
  /* Ensure mobile trigger is visible only on mobile */
  .mobile-menu-trigger {
    display: none; /* Hidden by default */
  }
  @media (max-width: 1023px) {
    .mobile-menu-trigger {
      display: block; /* Shown on mobile */
    }
  }
</style>