<script>
  /** @type {Props} */
  let { children } = $props();
  import "../app.css";
  import { onMount } from 'svelte';
  import Footer from "$lib/components/layout/Footer.svelte";
  import PerformanceMonitor from "$lib/components/ui/PerformanceMonitor.svelte";
  import { initPerformanceMonitoring } from "$lib/utils/performance";
  import { initImageOptimization } from "$lib/utils/imageOptimizer";
  import { page } from '$app/stores';
  import InstallPrompt from '$lib/components/pwa/InstallPrompt.svelte';
  import MainSidebar from '$lib/components/layout/MainSidebar.svelte';
  import { findProblematicClasses, fixClassString } from '$lib/utils/tailwindFixer';
  import { registerServiceWorker } from '$lib/utils/register-sw';
  import TestPwaControls from '$lib/components/pwa/TestPwaControls.svelte';
  import { browser } from '$app/environment';
  import { dev } from '$app/environment';
  import GlobalFAB from '$lib/components/layout/GlobalFAB.svelte';
  /**
   * @typedef {Object} Props
   * @property {import('svelte').Snippet} [children]
   */


  
  /**
   * Determines if the current route is an auth route
   */
  let isAuthRoute = $derived($page.url.pathname.startsWith('/login') || $page.url.pathname.startsWith('/register'));
  
  /**
   * Determines if the current route is a design system route
   */
  let isDesignSystemRoute = $derived($page.url.pathname.startsWith('/design-system'));
  
  // Use an effect to log state changes (creates proper closure)
  $effect(() => {
    console.debug('[DEBUG] Current path:', $page.url.pathname, 'Auth route:', isAuthRoute, 'Design system route:', isDesignSystemRoute);
    
    // If it's a design system route, immediately apply CSS var forcing
    if (isDesignSystemRoute && browser) {
      console.debug('[DEBUG] Forcing CSS variable application for design system');
      forceCSSVariableApplication();
    }
  });
  
  /**
   * Force CSS variables to be applied properly - addresses Tailwind 4 HSL processing issues
   */
  function forceCSSVariableApplication() {
    if (typeof window === 'undefined') return;
    
    console.log('[Layout] Forcing CSS variable application');
    
    // Get all CSS variables from :root
    const computedStyle = getComputedStyle(document.documentElement);
    const cssColorVars = [
      '--background', '--foreground', '--card', '--card-foreground', 
      '--popover', '--popover-foreground', '--primary', '--primary-foreground', 
      '--secondary', '--secondary-foreground', '--muted', '--muted-foreground', 
      '--accent', '--accent-foreground', '--destructive', '--destructive-foreground', 
      '--border', '--input', '--ring'
    ];
    
    // Force re-application of CSS variables with direct style manipulation
    cssColorVars.forEach(varName => {
      const value = computedStyle.getPropertyValue(varName).trim();
      if (value) {
        document.documentElement.style.setProperty(varName, value);
        
        // Also force HSL variables for Tailwind 4
        document.body.style.setProperty(`--applied${varName}`, `hsl(${value})`);
      }
    });
    
    // Also initialize direct CSS application for key elements
    setTimeout(() => {
      // Apply direct styles to key elements that might be using Tailwind classes
      document.querySelectorAll('.design-system-container button, .design-system-container a, .design-system-container h1, .design-system-container h2').forEach(el => {
        // Force reflow to ensure styles are recalculated
        void el.offsetWidth;
      });
    }, 100);
  }
  
  // Single source of truth for sidebar state
  let isSidebarCollapsed = $state(false);
  
  // Function to toggle state
  function toggleSidebar() {
    if (typeof window === 'undefined') return;
    
    isSidebarCollapsed = !isSidebarCollapsed;

    if (isSidebarCollapsed) {
      document.body.classList.add('sidebar-collapsed');
    } else {
      document.body.classList.remove('sidebar-collapsed');
    }
    
    // Save preference
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('sidebar-collapsed', String(isSidebarCollapsed));
    }
    
    console.log(`[Layout] Sidebar toggled state to: ${isSidebarCollapsed}`);
  }
  
  // Track if we're on mobile
  let isMobile = $state(false);
  
  // Setup responsive behavior
  onMount(() => {
    try {
      const checkMobile = () => {
        isMobile = window.innerWidth < 1024; // lg breakpoint
      };
      
      // Initial check
      checkMobile();
      
      // Register event listener for resize
      window.addEventListener('resize', checkMobile);
      
      // Initialize state from localStorage
      if (typeof window !== 'undefined' && window.localStorage) {
        const storedState = localStorage.getItem('sidebar-collapsed');
        if (storedState === 'true') {
          isSidebarCollapsed = true;
          // Ensure body class matches initial state
          document.body.classList.add('sidebar-collapsed');
        } else {
          // Ensure body class matches initial state
          document.body.classList.remove('sidebar-collapsed'); 
        }
        console.log('[Layout] Initialized sidebar state from localStorage:', isSidebarCollapsed);
      }
      
      // Initialize performance monitoring
      initPerformanceMonitoring();
      
      // Optimize images
      initImageOptimization();
      
      // Register Service Worker for PWA functionality
      registerServiceWorker().catch(error => {
        // Log error but don't break app functionality
        console.debug('[SW] Registration error (non-critical):', error.message);
      });
      
      // Find and report any problematic Tailwind classes
      findProblematicClasses();
      
      // Clean up on component destruction
      return () => {
        window.removeEventListener('resize', checkMobile);
      };
    } catch (error) {
      console.error('[Layout] Error initializing layout:', error);
    }
  });

  /**
   * Checks for potential layout problems like overlapping elements.
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
   * Initializes layout checks and observers after mount.
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
    
    // Add resize observer to track sidebar size changes
    setTimeout(() => {
      const sidebar = document.querySelector('.sidebar-area');
      if (sidebar && 'ResizeObserver' in window) {
        console.log("[Layout] Setting up ResizeObserver for sidebar");
        
        const resizeObserver = new ResizeObserver(entries => {
          for (const entry of entries) {
            console.log("[Layout] Sidebar size changed:", {
              width: entry.contentRect.width,
              height: entry.contentRect.height,
              time: new Date().toISOString()
            });
          }
        });
        
        resizeObserver.observe(sidebar);
        console.log("[Layout] ResizeObserver attached to sidebar");
      }
    }, 200);
    
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

    // Setup listener for sidebar toggle events from child components
    /**
     * @param {CustomEvent<{collapsed: boolean}>} event - The sidebar toggle event
     */
    const handleSidebarToggle = (event) => {
      if (isSidebarCollapsed !== event.detail.collapsed) {
        toggleSidebar();
      }
    };
    
    document.addEventListener('sidebarToggle', handleSidebarToggle);
    
    // Clean up event listener on component destruction
    return () => {
      document.removeEventListener('sidebarToggle', handleSidebarToggle);
    };
  });
</script>



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
  }
  
  /* Prevent horizontal overflow */
  :global(html, body) {
    overflow-x: hidden;
    width: 100%;
    max-width: 100vw;
  }
  
  /* App Shell Layout */
  .app-shell {
    display: grid;
    min-height: 100vh;
    width: 100%;
    max-width: 100vw;
    overflow-x: hidden;
    grid-template-columns: 1fr;
    grid-template-rows: auto 1fr auto;
    grid-template-areas: 
      "header"
      "content"
      "footer";
    transition: all 0.3s ease-in-out;
  }
  
  @media (min-width: 768px) {
    .app-shell {
      grid-template-columns: 240px 1fr;
      grid-template-areas: 
        "header header"
        "content content"
        "footer footer";
    }
    
    :global(body.sidebar-collapsed) .app-shell {
      grid-template-columns: 64px 1fr;
    }
  }
  
  /* Header area */
  .header-area {
    grid-area: header;
    border-bottom: 1px solid hsl(var(--border)/0.8);
    background-color: hsl(var(--background));
    height: 64px; /* Fixed header height */
    width: 100%;
  }
  
  /* Content wrapper for max-width control */
  .content-wrapper {
    grid-area: content;
    width: 100%;
    max-width: 1440px; /* Match our largest breakpoint */
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    min-height: 100%;
    overflow-x: hidden;
  }
  
  /* Content grid for sidebar and main */
  .content-grid {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 6;
    width: 100%;
    min-height: 100%;
    transition: grid-template-columns 0.3s ease-in-out;
  }
  
  /* When sidebar is collapsed, adjust grid properly */
  :global(body.sidebar-collapsed) .content-grid {
    grid-template-columns: 64px 1fr;
  }
  
  /* Sidebar area */
  .sidebar-area {
    width: 240px;
    min-width: 240px;
    max-width: 240px;
    position: relative;
    transition: all 0.3s ease-in-out;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background-color: hsl(var(--sidebar-background));
    border-right: 1px solid hsl(var(--sidebar-border)/0.8);
  }
  
  /* Collapsed sidebar */
  :global(body.sidebar-collapsed) .sidebar-area {
    width: 64px !important;
    min-width: 64px !important;
    max-width: 64px !important;
  }
  
  /* Main content area */
  .main-area {
    padding: 6;
    overflow-y: auto;
    overflow-x: hidden;
    width: 100%;
    max-width: 100%;
  }
  
  /* Footer area */
  .footer-area {
    grid-area: footer;
    border-top: 1px solid hsl(var(--border)/0.8);
    background-color: hsl(var(--background));
  }
  
  /* Mobile layout adjustments */
  @media (max-width: 1023px) {
    /* Hide sidebar on mobile */
    .sidebar-area {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      transform: translateX(-100%);
      transition: transform 0.3s ease;
      width: 240px !important; /* Override any other width settings */
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    
    /* Show sidebar when open */
    .sidebar-open .sidebar-area {
      transform: translateX(0);
    }
    
    /* Mobile trigger button */
    .mobile-menu-trigger {
      position: fixed;
      top: 1rem;
      left: 1rem;
      display: block;
      padding: 0.5rem;
      border-radius: 0.375rem;
      background-color: hsl(var(--background));
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    
    :global(body.sidebar-collapsed) .main-area {
      margin-left: 0; /* Don't adjust margin on mobile */
      width: 100%; /* Full width on mobile */
    }
  }
  
  /* Desktop specific styles */
  @media (min-width: 1024px) {
    .sidebar-area {
      position: relative;
      transform: none !important; /* Ensure sidebar is always visible on desktop */
      display: flex !important;
    }
    
    .mobile-menu-trigger {
      display: none;
    }
  }
</style>

<!-- Skip layout for auth routes -->
{#if isAuthRoute}
  {@render children?.()}
{:else}
  <div class={`app-shell ${isMobile && !isSidebarCollapsed ? 'sidebar-open' : ''}`}>
    <!-- Header -->
    <header class="header-area px-3">
      <div class="container flex items-center justify-between h-full">
        <div class="flex items-center gap-3">
          <!-- Logo -->
          <span class="text-xl font-semibold">
            <span class="text-[hsl(var(--primary))]">⚡️ ASAP</span> Digest
          </span>
        </div>
        
        <!-- Header actions (search, notifications, user avatar) -->
        <div class="flex items-center gap-4">
          <!-- Search input -->
          <button class="p-2 rounded-md bg-[hsl(var(--muted)/0.1)] hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]" aria-label="Search">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </button>
          
          <!-- Notifications -->
          <div class="relative">
            <button class="p-2 rounded-full hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              <div class="absolute top-0 right-0 bg-[hsl(var(--destructive))] text-[hsl(var(--destructive-foreground))] rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold">
                3
              </div>
            </button>
          </div>
          
          <!-- User avatar with dropdown -->
          <div class="relative">
            <button 
              class="flex items-center space-x-2 rounded-full hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] p-1 transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]"
              onclick={() => {
                if (typeof window === 'undefined') return;
                const dropdown = document.getElementById('user-dropdown');
                if (dropdown) {
                  dropdown.classList.toggle('hidden');
                }
              }}
              aria-haspopup="true"
            >
              <div class="w-8 h-8 rounded-full bg-[hsl(var(--muted)/0.2)] overflow-hidden">
                <img 
                  src="/images/avatar.png" 
                  alt="User" 
                  class="object-cover w-full h-full"
                  onerror={(e) => {
                    if (typeof window === 'undefined') return;
                    e.target.onerror = null;
                    e.target.src = 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E';
                  }}
                />
              </div>
            </button>
            
            <!-- User dropdown menu -->
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-[hsl(var(--background))] dark:bg-[hsl(var(--muted))] shadow-lg rounded-md z-50 border border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="p-2 border-b border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
                <div class="font-semibold">John Doe</div>
                <div class="text-xs text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">john.doe@example.com</div>
              </div>
              
              <div class="py-1">
                <a href="/dashboard" class="block px-4 py-2 text-sm hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200">
                  Dashboard
                </a>
                <a href="/settings" class="block px-4 py-2 text-sm hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200">
                  Settings
                </a>
                <a href="/logout" class="block px-4 py-2 text-sm hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200">
                  Logout
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>
    
    <!-- Content wrapper with max-width -->
    <div class="content-wrapper">
      <!-- Content grid for sidebar and main -->
      <div class="content-grid">
        <!-- Sidebar -->
        <aside class="sidebar-area">
          <MainSidebar
            collapsed={isSidebarCollapsed}
            toggleSidebar={toggleSidebar}
          />
        </aside>
        
        <!-- Main content area -->
        <main class="main-area">
          <!-- PWA install prompt -->
          <InstallPrompt />
          
          <!-- Main content -->
          <div class="min-h-screen">
            {@render children?.()}
          </div>
        </main>
      </div>
    </div>
    
    <!-- Mobile sidebar trigger -->
    {#if isMobile}
      <button 
        type="button"
        class="mobile-menu-trigger"
        aria-label="Toggle sidebar"
        onclick={(e) => toggleSidebar()}
      >
        {#if isSidebarCollapsed}
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        {:else}
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        {/if}
      </button>
    {/if}
    
    <!-- Footer -->
    <footer class="footer-area">
      <div class="container mx-auto py-[1rem] px-[1rem]">
        <Footer />
      </div>
    </footer>
    
    <!-- PWA Testing Controls -->
    <TestPwaControls />
  </div>
  
  <!-- Performance monitor (dev only) - Positioned outside app-shell -->
  {#if import.meta.env.DEV}
    <PerformanceMonitor />
  {/if}

  <!-- Add GlobalFAB at the end of the layout -->
  <GlobalFAB />
{/if}