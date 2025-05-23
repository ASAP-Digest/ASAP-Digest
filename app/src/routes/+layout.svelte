<!-- 
  @file Main Application Layout
  @description The root layout component for the entire application
  @milestone WP <-> SK Auto Login V6 - MILESTONE COMPLETED! 2025-05-03
  1. Successfully implemented server-to-server auto-login
  2. User is created in ba_users table
  3. Account is created in ba_accounts table 
  4. Session is created in ba_sessions table
  @nextSteps 
  1. Implement UI refresh with WordPress profile data (avatar, bio, etc.)
  2. Fix protected routes access without page refreshes
-->

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
    Menu, X, Search, Bell, CircleUser, LayoutDashboard, Settings, LogOut, Home, Download, User 
  } from '$lib/utils/lucide-compat.js';
  import { goto, invalidateAll } from '$app/navigation'; // Import invalidateAll from correct module
  // Import the new GraphQL helper
  import { fetchGraphQL } from '$lib/utils/fetchGraphQL.js';
  // Import session store/hook (assuming useSession exists or similar)
  // import { useSession } from '$lib/stores/session'; // Placeholder - Adjust if store name/structure differs
  import { log } from '$lib/utils/log.js'; // Assuming a logging utility
  // Remove the problematic import and use a constant instead
  // import { PUBLIC_WP_API_URL } from '$env/dynamic/public'; 
  // Import the enhanced user utils
  import { getAvatarUrl } from '$lib/stores/user.js';
  import { theme, setTheme, getAvailableThemes } from '$lib/stores/theme.js';

  // State management with Svelte 5 runes
  let isSidebarOpen = $state(false);
  let isSidebarCollapsed = $state(false);
  let isMobile = $state(false);
  // let isSidebarOpen = false; // TEMP: Use non-reactive fallback
  // let isSidebarCollapsed = false; // TEMP: Use non-reactive fallback
  // let isMobile = false; // TEMP: Use non-reactive fallback

  // Define fallback values for environment variables
  const WP_API_URL = 'https://asapdigest.local'; // Fallback value

  // Derived values using Svelte 5 runes
  let isAuthRoute = $derived($page.url.pathname.startsWith('/login') || $page.url.pathname.startsWith('/register'));
  let isDesignSystemRoute = $derived($page.url.pathname.startsWith('/design-system'));
  // Check if there is an active Better Auth session
  // $page.data contains data from the layout's load function (+layout.server.js or +layout.js)
  let hasBetterAuthSession = $derived(!!$page.data.user); 
  // let isAuthRoute = false; // TEMP: Use non-reactive fallback
  // let isDesignSystemRoute = false; // TEMP: Use non-reactive fallback

  // Store previous user update timestamp and signature to avoid unnecessary toasts
  let previousUserUpdatedAt = $state($page.data.user?.updatedAt);
  let previousUserSignature = $state($page.data.user ? JSON.stringify({
    displayName: $page.data.user.displayName,
    email: $page.data.user.email,
    avatarUrl: $page.data.user.avatarUrl,
    preferences: $page.data.user.preferences
  }) : null);

  // Log user data from page store on mount AND whenever it changes
  $effect(() => {
    // This log runs both on the server (during SSR) and client
    log('[Layout $effect] $page.data.user:', JSON.stringify($page.data.user || null));
  });

  // Effect to show toast on user data update (coming from invalidateAll or SSE)
  // But ONLY when actual profile data changes, not just timestamps
  $effect(() => {
    const currentUser = $page.data.user;
    
    // Skip if no user data
    if (!currentUser) {
      previousUserUpdatedAt = null;
      previousUserSignature = null;
      return;
    }
    
    // Create a signature of user data we care about, excluding timestamp
    const currentSignature = JSON.stringify({
      displayName: currentUser.displayName,
      email: currentUser.email,
      avatarUrl: currentUser.avatarUrl,
      preferences: currentUser.preferences
    });
    
    // Only show toast if the signature changed (actual data changed)
    // AND there was a previous user (not first load)
    if (previousUserSignature && 
        currentSignature !== previousUserSignature &&
        previousUserUpdatedAt) {
      
      log(`[Layout Toast Effect] User data updated. Old: ${previousUserUpdatedAt}, New: ${currentUser.updatedAt}. Showing toast.`); // DEBUG
      
      toasts.show(
        'Your profile details have been updated.', // Simpler message
        'success'
      );
    }
    
    // Always update both the timestamp and signature
    previousUserUpdatedAt = currentUser.updatedAt;
    previousUserSignature = currentSignature;
  });

  // Effects using Svelte 5 runes
  $effect(() => {
    if (isDesignSystemRoute && browser) {
      forceCSSVariableApplication();
    }
  });

  // Initialize on mount
  onMount(async () => {
    if (!browser) return; // Only run client-side

    // Remove the stored auto-login success check since we now handle toast directly in the login flow
    try {
      // Clean up any lingering auto-login flags from previous sessions
      sessionStorage.removeItem('auto_login_success');
      
      // Keep the fallback check for edge cases
      const fallbackAttempted = sessionStorage.getItem('fallback_auth_attempted');
      log(`[Layout] Session initialization. Fallback auth attempted: ${fallbackAttempted ? 'true' : 'false'}`);
    } catch (e) {
      // Ignore any errors with sessionStorage
      console.error('[Layout] Error with sessionStorage:', e);
    }

    // --- V6 Auto Login Logic ---
    if (browser) {
      // Skip auto-login if returning from auth providers or pages
      log('[Layout V6] Checking for existing Better Auth session...', 'info');
      
      // If we have session data already, don't trigger auto-login
      if ($page.data.user || $session) {
        log('[Layout V6] Active Better Auth session found. Auto-login flow stopped.', 'info');
        return;
      }
    
      log('[Layout V6] No active Better Auth session. Triggering server-to-server check...', 'info');
      
      // V6 uses a different endpoint than V5:
      const checkEndpoint = '/api/auth/check-wp-session';
      log(`[Layout V6] Using endpoint: ${checkEndpoint}`, 'debug');
      
      /**
       * @description Check for WordPress session and sync user if found
       * @returns {Promise<boolean>} True if successful auto-login
       */
      const checkWpSession = async () => {
        try {
          log('[Layout V6] Checking for WordPress session...', 'debug');
          
          // If we have session data already, don't trigger auto-login again
          if ($page.data.user || $session) {
            log('[Layout V6] Active Better Auth session found. Skipping auto-login check.', 'info');
            return true;
          }
          
          log('[Layout V6] No active Better Auth session. Triggering server-to-server check...', 'info');
          
          // Make request to check-wp-session endpoint
          const response = await fetch(checkEndpoint, {
            method: 'POST',
            credentials: 'include', // Include cookies
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'Cache-Control': 'no-cache, no-store, must-revalidate',
              'Origin': 'https://localhost:5173' // Add origin header to identify as browser request
            },
            body: JSON.stringify({ timestamp: Date.now() })
          });
            
          if (!response.ok) {
            const text = await response.text();
            log(`[Layout V6] Server returned ${response.status}: ${text}`, 'warn');
            return false;
          }
            
          const data = await response.json();
            
          if (data.success && data.user) {
            log(`[Layout V6] Auto-login successful! User: ${data.user.email}`, 'info');
              
            if (data.created) {
              log('[Layout V6] New Better Auth user was created', 'info');
            } else {
              log('[Layout V6] Existing Better Auth user was used', 'info');
            }
              
            // Check for warning flags
            if (data.warning) {
              log(`[Layout V6] Warning from server: ${data.warning}`, 'warn');
            }
              
            log('[Layout V6] Auto-login completed, refreshing data...', 'info');
              
            // IMPROVED IMPLEMENTATION: Use SvelteKit's invalidateAll instead of forcing page reload
            // This leverages SvelteKit's reactivity system for a seamless experience
            await invalidateAll();
              
            // Show success toast using the toasts store
            toasts.show(
              `Welcome back, ${data.user.displayName || data.user.email}!`,
              'success'
            );
              
            // Always return true to indicate success
            return true;
              
          } else {
            // If no WordPress session or auto-login failed
            log('[Layout V6] No active WordPress session or auto-login failed', 'debug');
            return false;
          }
        } catch (err) {
          // Handle unexpected errors
          log(`[Layout V6] Error during auto-login check: ${err.message}`, 'error');
          // Will retry again after retryIntervalMs
          return false;
        }
      };

      // Configurable auto-login retry settings
      const initialDelayMs = 500;
      const retryIntervalMs = 30000; // 30 seconds between retries 
      // Remove maxRetryDurationMs to allow indefinite retries
      let autoLoginIntervalId = null;
      let firstAttemptCompleted = false;
      let startTime = Date.now();
      let retryCount = 0;

      // Start the initial auto-login check with a small delay
      log(`[Layout V6] Starting auto-login check with ${initialDelayMs}ms delay...`, "debug");
      setTimeout(async () => {
          // Make the initial attempt
          const success = await checkWpSession();
          firstAttemptCompleted = true;
          
          // If first attempt successful, we're done
          if (success) {
              log(`[Layout V6] Auto-login successful on first attempt`, "info");
              sessionStorage.removeItem('fallback_auth_attempted'); // Clear this flag to ensure future retries work
              return;
          }
          
          // If unsuccessful, start the retry interval
          log(`[Layout V6] Initial auto-login attempt failed, starting retry interval (every ${retryIntervalMs/1000}s)`, "info");
          
          // Store flag to prevent rapid retry loops
          sessionStorage.setItem('auto_login_attempted', 'true');
          
          // Begin continuous retry interval without max duration limit
          autoLoginIntervalId = setInterval(async () => {
              retryCount++;
              
              // Attempt auto-login again
              log(`[Layout V6] Retrying auto-login (attempt #${retryCount})...`, "info");
              const success = await checkWpSession();
              
              // If successful, clear the interval
              if (success) {
                  log(`[Layout V6] Auto-login successful after ${retryCount} retries, stopping further attempts`, "info");
                  sessionStorage.removeItem('fallback_auth_attempted'); // Clear this flag to ensure future retries work
                  sessionStorage.removeItem('auto_login_attempted'); // Clear the attempt flag
                  if (autoLoginIntervalId) clearInterval(autoLoginIntervalId);
              }
          }, retryIntervalMs);
          
      }, initialDelayMs);

      onDestroy(() => {
          // Clean up interval on component destruction
          if (autoLoginIntervalId) clearInterval(autoLoginIntervalId);
      });
    }
    // --- End V6 Auto Login Logic ---

    // --- SSE Listener Setup (Remains relevant for live updates) --- 
    let localEventSource = null;
        console.log('[Layout Sync Listener] Setting up EventSource...');
        localEventSource = new EventSource('/api/auth/sync-stream');
        
        // Set global flag to indicate root SSE connection is active
        if (typeof window !== 'undefined') {
          window.asapDigestSseActive = true;
        }

        localEventSource.onopen = () => {
          console.log('[Layout Sync Listener] EventSource connection opened.');
        };

        localEventSource.onmessage = (event) => {
          try {
            const data = JSON.parse(event.data);
            console.log('[Layout Sync Listener] Received message:', data); // DEBUG

            if (data.type === 'user-update') {
              const currentUserId = $page.data.user?.id; 
              console.log(`[Layout Sync Listener] User update received. Target User: ${data.userId}, Current User: ${currentUserId}, Timestamp in message: ${data.updatedAt}`); // DEBUG
              
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
      };

    // Performance monitoring setup
      initPerformanceMonitoring();

    // Register service worker
    if (!dev) { // Don't register SW in dev mode
      registerServiceWorker();
    }

    // === END MOUNT FUNCTION ===

    try {
      // Initialize mobile state
      const checkMobile = () => {
        isMobile = window.innerWidth < 1024;
        if (!isMobile && isSidebarOpen) {
          isSidebarOpen = false;
        }
      };
      
      checkMobile();
      window.addEventListener('resize', checkMobile);
      
      // Initialize sidebar state from localStorage
        const storedState = localStorage.getItem('sidebar-collapsed');
        isSidebarCollapsed = window.innerWidth < 1024 ? true : storedState === 'true';
      localStorage.setItem('sidebar-collapsed', isSidebarCollapsed.toString());
      
      document.documentElement.classList.add('dark');
      initializeLayout();
      
      return () => {
        if (localEventSource) {
          console.log('[Layout Sync Listener] Closing EventSource connection.');
          localEventSource.close();
          
          // Remove global flag when connection is closed
          if (typeof window !== 'undefined') {
            window.asapDigestSseActive = false;
          }
        }
        window.removeEventListener('resize', checkMobile);
      };
    } catch (error) {
      console.error('[Layout] Error initializing layout:', error);
    }

    // Initialize theme system on mount
    console.log('🚀 App layout mounted, initializing theme system');
    
    // --- Gridstack Initialization ---
    console.log('Initializing Gridstack for main content layout...');
    // Import GridStack dynamically client-side
    let GridStack;
    let gridContainer;
    let grid;

    // Dynamically import GridStack
    const gridstackModule = await import('gridstack');
    GridStack = gridstackModule.GridStack;
    // Dynamically import Gridstack CSS client-side
    await import('gridstack/dist/gridstack.min.css');

    grid = GridStack.init({
      column: 12, // Default desktop columns
      columnOpts: {
        breakpoints: [
          { w: 1024, c: 12 }, // Desktop (optional, explicit for clarity)
          { w: 768, c: 8 },  // Tablet
          { w: 480, c: 4 }   // Mobile
        ]
      },
      margin: 'var(--spacing-md)', // Use design system spacing
      float: false,
      disableDrag: true, // Disable drag for general layout items
      disableResize: true, // Disable resize for general layout items
      // Add other options as needed for the general layout
    }, gridContainer);
    console.log('Gridstack initialized.');
    // --- End Gridstack Initialization ---
    
    // Log available themes for debugging
    console.log('Available themes in layout:', getAvailableThemes());
    
    // Debug theme availability after a small delay to allow CSS to load
    setTimeout(() => {
      try {
        const availableThemes = getAvailableThemes();
        console.log('Available themes after delay:', availableThemes);
      } catch (error) {
        console.error('Error getting available themes:', error);
      }
    }, 500);
    
    // Subscribe to theme changes for debugging
    const unsubscribe = theme.subscribe(value => {
      console.log(`📱 Theme changed to: ${value}`);
      // Force re-application of theme to ensure proper rendering
      if (value === 'default') {
        document.documentElement.removeAttribute('data-theme');
      } else {
        document.documentElement.setAttribute('data-theme', value);
      }
    });
    
    return () => {
      unsubscribe();
    };
  });

  /**
   * @description Toggles the visibility of the mobile sidebar.
   * @returns {void}
   */
  function toggleMobileSidebar() {
    isSidebarOpen = !isSidebarOpen;
    log('Toggled mobile sidebar, isSidebarOpen:', isSidebarOpen);
  }

  /**
   * @description Toggles the collapsed state of the desktop sidebar and saves to localStorage.
   * @returns {void}
   */
  function toggleDesktopSidebarCollapsed() {
    isSidebarCollapsed = !isSidebarCollapsed;
    localStorage.setItem('sidebar-collapsed', isSidebarCollapsed.toString());
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
    log('[Layout] Checking for layout issues...'); 
    const sidebarElement = document.querySelector('[data-testid="sidebar"]');
    if (sidebarElement) {
      log('[Layout] Sidebar element found during layout check.');
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
    log('[Layout] Starting layout initialization...');
    log('[Layout] initializeLayout finished (minimal execution).'); // Add log
  }

  import { auth, useSession } from '$lib/auth-client';
  import AuthButtons from '$lib/components/AuthButtons.svelte';
  import { navigating } from '$app/stores';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { Loader2 } from '$lib/utils/lucide-compat.js';

  const { data: session } = useSession();

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
      log('[Layout] Attempting sign out...');
      await auth.signOut({ redirect: true }); 
      log('[Layout] Sign out successful.');
    } catch (error) {
      console.error('[Layout] Error during sign out:', error);
      toasts.show(
        "Logout Error",
        "Could not log out. Please try again later.",
        'destructive'
      );
    }
  }
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
            <span>⚡️ </span><span class="gradient-text">ASAP</span> Digest
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
              <!-- Consistent avatar handling for hydration -->
              {#if $page.data.user}
                {#key $page.data.user.updatedAt || 'initial-render'}
                  <img
                    src={getAvatarUrl($page.data.user) || '/images/default-avatar.svg'}
                    alt={$page.data.user.displayName || 'User Avatar'}
                    class="h-full w-full object-cover"
                    onerror={handleImageError} 
                  />
                {/key}
              {:else}
                <!-- Default avatar fallback -->
                <div class="h-full w-full flex items-center justify-center text-[hsl(var(--muted-foreground))]">
                  <Icon icon={User} class="w-5 h-5" />
                </div>
              {/if}
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
                <!-- Add plan display -->
                {#if $page.data.user.plan}
                  <div class="text-xs text-[hsl(var(--primary)/0.8)] mt-1">
                    {#if typeof $page.data.user.plan === 'object' && $page.data.user.plan !== null}
                      {$page.data.user.plan.name || 'Free'}
                    {:else if typeof $page.data.user.plan === 'string'}
                      {$page.data.user.plan}
                    {:else}
                      Free
                    {/if}
                  </div>
                {/if}
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
      
      <!-- Main content - Wrapped in Gridstack container -->
      <div bind:this={gridContainer} class="grid-stack">
        {#if $navigating}
          <div class="grid-stack-item grid-stack-item-content flex justify-center items-center min-h-[50vh]">
            <!-- Use Icon wrapper -->
            <Icon icon={Loader2} class="w-8 h-8 animate-spin text-[hsl(var(--primary))]" />
          </div>
        {:else}
          <!-- Route content goes here -->
          <!-- Ensure content within here uses grid-stack-item classes for direct children that should be part of the grid -->
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
  {#if dev}
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
  
  /* Gradient text styling */
  .gradient-text {
    background: linear-gradient(5deg, rgba(255, 255, 255, 1) 0%, rgba(153, 82, 224, 1) 30%, rgba(26, 198, 255, 1) 61%, rgba(255, 255, 255, 1) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent;
    display: inline-block;
    font-weight: 700;
    padding: 0 2px; /* Small padding to ensure gradient edges are visible */
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

  :global(.theme-button-clicked) {
    transform: scale(0.9) !important;
    opacity: 0.8 !important;
    transition: transform 0.15s ease-in-out, opacity 0.15s ease-in-out !important;
  }
  
  :global(.theme-button[class*="bg-"]) {
    position: relative;
  }
  
  :global(.theme-button[style*="background-color: hsl(var(--brand) / 0.3);"]::after) {
    content: "✓";
    position: absolute;
    top: -3px;
    right: -3px;
    font-size: 0.5rem;
    background-color: hsl(var(--brand));
    color: hsl(var(--brand-fg));
    border-radius: 50%;
    width: 12px;
    height: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  /* Direct styling of theme buttons to ensure checkmarks appear properly */
  :global(.theme-button.active-theme) {
    background-color: hsl(var(--brand) / 0.3) !important;
    position: relative;
  }
  
  :global(.theme-button.active-theme::after) {
    content: "✓";
    position: absolute;
    top: -3px;
    right: -3px;
    font-size: 0.5rem;
    background-color: hsl(var(--brand));
    color: hsl(var(--brand-fg));
    border-radius: 50%;
    width: 12px;
    height: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
  }
</style>