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
  import { log } from '$lib/utils/log.js'; // Assuming a logging utility
  // Remove the problematic import and use a constant instead
  // import { PUBLIC_WP_API_URL } from '$env/dynamic/public'; 

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

  // Store previous user update timestamp
  let previousUserUpdatedAt = $state($page.data.user?.updatedAt);

  // Log user data from page store on mount AND whenever it changes
  $effect(() => {
    // This log runs both on the server (during SSR) and client
    log('[Layout $effect] $page.data.user:', JSON.stringify($page.data.user || null));
  });

  // Effect to show toast on user data update (coming from invalidateAll or SSE)
  $effect(() => {
    const currentUser = $page.data.user;
    if (currentUser?.updatedAt && currentUser.updatedAt !== previousUserUpdatedAt) {
      log(`[Layout Toast Effect] User data updated. Old: ${previousUserUpdatedAt}, New: ${currentUser.updatedAt}. Showing toast.`); // DEBUG
      toasts.show(
        'Your profile details have been updated.', // Simpler message
        'success'
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
    if (!browser) return; // Only run client-side

    // Check if we have a stored auto-login success flag
    try {
      const storedAutoLoginSuccess = sessionStorage.getItem('auto_login_success');
      const fallbackAttempted = sessionStorage.getItem('fallback_auth_attempted');
      
      if (storedAutoLoginSuccess) {
        const loginData = JSON.parse(storedAutoLoginSuccess);
        // Only use if it's recent (within the last 5 seconds)
        if (loginData && (Date.now() - loginData.timestamp) < 5000) {
          toasts.show(
            `Welcome back, ${loginData.displayName || loginData.email}!`,
            'success'
          );
          // Also invalidate all to ensure UI is updated with user data
          invalidateAll();
        }
        // Clear the flag so it doesn't show again
        sessionStorage.removeItem('auto_login_success');
      }
    } catch (e) {
      // Ignore any errors with sessionStorage
      console.error('[Layout] Error checking sessionStorage:', e);
    }

    // --- V6 Auto Login Logic ---
    const startTime = Date.now();
    log("[Layout V6] Checking for existing Better Auth session...", "info"); 
    if (hasBetterAuthSession) {
        log("[Layout V6] Active Better Auth session found. Auto-login flow stopped.", "info");
        log(`[Layout V6] User already logged in as: ${$page.data.user?.email || 'Unknown'}`, "info");
    } else {
        // Only check if we're in a potential reload loop for page refreshes
        // but always allow server-to-server retry logic to continue
        const lastReload = sessionStorage.getItem('last_reload_time');
        const currentTime = Date.now();
        const recentlyReloaded = lastReload && (currentTime - parseInt(lastReload) < 3000);
        
        // Check if we've already attempted fallback authentication recently AND we recently reloaded
        const fallbackAttempted = sessionStorage.getItem('fallback_auth_attempted');
        if (fallbackAttempted && recentlyReloaded) {
          // We've already tried the fallback mechanism after a recent page reload
          // Just avoid triggering another page reload but still allow S2S retries
          log("[Layout V6] Fallback auth was recently attempted after page reload. Will allow S2S retries but prevent additional page reloads.", "warn");
          // Do NOT return here - the return statement would block the server-to-server retries
          // We only want to prevent page refreshes, not server-to-server communication
        }
        
        log("[Layout V6] No active Better Auth session. Triggering server-to-server check...", "info");
        
        // Define the SK backend endpoint URL
        const checkWpSessionUrl = '/api/auth/check-wp-session'; 
        log(`[Layout V6] Using endpoint: ${checkWpSessionUrl}`, "debug");

        // Asynchronous function to perform the background server-to-server check
        // This will continuously retry in the background until successful
        const checkWpSession = async () => {
          try {
            log('[Layout V6] Checking for WordPress session...', 'debug');
            
            // Try to get existing session from Better Auth first
            // This is a fast check and doesn't require a server request
            if ($page.data.user) {
              log('[Layout V6] Existing Better Auth session found, using that', 'info');
              return true;
            }
            
            log('[Layout V6] No active Better Auth session. Triggering server-to-server check...', 'info');
            log('[Layout V6] Using endpoint: /api/auth/check-wp-session', 'debug');
            
            // Make the API call to check for WordPress sessions & auto-login
            const response = await fetch('/api/auth/check-wp-session', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Origin': 'https://localhost:5173', // Add origin header to identify as browser request
                'X-CSRF-Protection': 'none' // Add header to bypass CSRF check in hooks.server.js
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
              
              // Check for noRefresh flag
              const preventRefresh = data.noRefresh === true;
              
              if (preventRefresh) {
                log('[Layout V6] Server indicated no refresh needed, setting auth data for UI update.', 'warn');
                // Use invalidateAll in browser only - don't use goto which causes refresh
                if (browser) {
                  setTimeout(() => {
                    invalidateAll();
                  }, 100); // Small delay to ensure cookie is processed
                }
              } else {
                // If server didn't specify noRefresh, use invalidateAll
                log('[Layout V6] Auto-login completed, refreshing data...', 'info');
                if (browser) {
                  invalidateAll();
                }
              }
              
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
        }
        window.removeEventListener('resize', checkMobile);
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
                onerror={handleImageError} 
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