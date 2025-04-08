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
  import { fade } from 'svelte/transition';
  // Import the functions
  import { getInstallPrompt, getIsInstallable, getIsPWA } from '$lib/stores/pwa.svelte.js';
  // Import local toast components and store
  import ToastContainer from '$lib/components/ui/toast/toast-container.svelte';
  import { toasts } from '$lib/stores/toast.js';
  // Import required icons
  import { 
    Menu, X, Search, Bell, CircleUser, LayoutDashboard, Settings, LogOut 
  } from '$lib/utils/lucide-compat.js';

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
  // let isAuthRoute = false; // TEMP: Use non-reactive fallback
  // let isDesignSystemRoute = false; // TEMP: Use non-reactive fallback

  // Log user data from page store on mount AND whenever it changes
  $effect(() => {
    // This log runs both on the server (during SSR) and client
    console.log('[Layout $effect] $page.data.user:', JSON.stringify($page.data.user || null));
  });

  // Effects using Svelte 5 runes
  $effect(() => {
    if (isDesignSystemRoute && browser) {
      forceCSSVariableApplication();
    }
  });

  // Initialize on mount
  onMount(() => {
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
      initPerformanceMonitoring();
      
      // Register Service Worker
      registerServiceWorker().catch(error => {
        console.debug('[SW] Registration error (non-critical):', error.message);
      });
      
      // Find and report any problematic Tailwind classes
      // findProblematicClasses(); // Assuming this is for debugging, keep if needed
      
      // Return cleanup function
      return () => {
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
        console.log('[Layout Effect] User data updated. Old ts:', lastUpdatedAt, 'New ts:', currentUser.updatedAt); // DEBUG
        // Use the local toast store's show method
        toasts.show('Profile synchronized.', 'success');
        lastUpdatedAt = currentUser.updatedAt; // Update the stored timestamp
      }
    } else if (lastUpdatedAt !== null && !currentUser) {
       // User logged out, reset timestamp
       console.log('[Layout Effect] User logged out, resetting lastUpdatedAt.'); // DEBUG
       lastUpdatedAt = null;
    }
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
          
          <!-- Notifications -->
          <div class="relative">
            <button
              class="focus-visible:ring-ring rounded-full p-2 transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] focus-visible:outline-none focus-visible:ring-2 dark:hover:bg-[hsl(var(--muted)/0.2)]"
            >
              <!-- Use Icon component -->
              <Icon icon={Bell} class="w-5 h-5" />
            </button>
            <!-- Use 0 as placeholder, ideally fetch real count later -->
            <!-- <div
              class="absolute right-0 top-0 flex h-5 w-5 items-center justify-center rounded-full bg-[hsl(var(--destructive))] text-xs font-bold text-[hsl(var(--destructive-foreground))]"
            >
              0 
            </div> -->
          </div>
        </div>
        
        <!-- User avatar with dropdown -->
        <div class="relative">
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
            <div
              id="user-dropdown"
              class="absolute right-0 mt-2 hidden w-48 rounded-md border border-[hsl(var(--border))] bg-[hsl(var(--background))] shadow-lg dark:border-[hsl(var(--muted-foreground)/0.2)] dark:bg-[hsl(var(--muted))] z-[var(--z-dropdown)]"
            >
              <div
                class="border-b border-[hsl(var(--border))] p-2 dark:border-[hsl(var(--muted-foreground)/0.2)]"
              >
                <!-- Use real user data -->
                <div class="font-semibold">{$page.data.user.displayName || 'User Name'}</div>
                <div
                  class="text-xs text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]"
                >
                  {$page.data.user.email || 'user@example.com'}
                </div>
              </div>
              
              <div class="py-1">
                <a
                  href="/dashboard"
                  class="flex items-center gap-2 px-4 py-2 text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]"
                >
                  <Icon icon={LayoutDashboard} class="w-4 h-4" /> 
                  <span>Dashboard</span>
                </a>
                <a
                  href="/settings"
                  class="flex items-center gap-2 px-4 py-2 text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]"
                >
                  <Icon icon={Settings} class="w-4 h-4" /> 
                  <span>Settings</span>
                </a>
                <!-- Use auth client signout -->
                <button
                  onclick={() => auth.signOut({ callbackUrl: '/' })}
                  class="flex items-center gap-2 w-full px-4 py-2 text-left text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]"
                >
                  <Icon icon={LogOut} class="w-4 h-4" /> 
                  <span>Logout</span>
                </button>
              </div>
            </div>
          {:else}
             <!-- Optionally show login button if no user -->
             <div 
               id="user-dropdown" 
               class="absolute right-0 mt-2 hidden w-48 rounded-md border border-[hsl(var(--border))] bg-[hsl(var(--background))] shadow-lg dark:border-[hsl(var(--muted-foreground)/0.2)] dark:bg-[hsl(var(--muted))] z-[var(--z-dropdown)]"
              >
                <div class="py-1">
                  <a 
                    href="/login" 
                    class="block px-4 py-2 text-sm transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]"
                  >
                    Login
                  </a>
                 </div>
             </div>
          {/if}
        </div>
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

  <GlobalFAB {isSidebarCollapsed} />

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
<ToastContainer />

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