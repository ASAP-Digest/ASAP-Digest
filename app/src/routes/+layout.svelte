<script>
  import "../app.css";
  import { onMount } from 'svelte';
  import Footer from "$lib/components/layout/Footer.svelte";
  import PerformanceMonitor from "$lib/components/ui/PerformanceMonitor.svelte";
  import { initPerformanceMonitoring } from "$lib/utils/performance";
  import { initImageOptimization } from "$lib/utils/imageOptimizer";
  import { page } from '$app/stores';
  import InstallPrompt from '$lib/components/pwa/InstallPrompt.svelte';
  import MainSidebar from '$lib/components/layout/MainSidebar.svelte';
  import { SidebarProvider } from '$lib/components/ui/sidebar';
  import { findProblematicClasses } from '$lib/utils/tailwindFixer';
  /**
   * @typedef {Object} Props
   * @property {import('svelte').Snippet} [children]
   */

  /** @type {Props} */
  let { children } = $props();
  
  /**
   * Determines if the current route is an auth route
   */
  let isAuthRoute = $derived($page.url.pathname.startsWith('/login') || $page.url.pathname.startsWith('/register'));
  
  // Use an effect to log state changes (creates proper closure)
  $effect(() => {
    console.debug('[DEBUG] Current path:', $page.url.pathname, 'Auth route:', isAuthRoute);
  });
  
  // Track sidebar state
  let isSidebarCollapsed = $state(false);
  
  /**
   * Toggle sidebar collapsed state
   * @param {MouseEvent} e - Mouse event
   */
  function toggleSidebar(e) {
    isSidebarCollapsed = !isSidebarCollapsed;
    // Add class to body to allow for CSS transitions
    if (isSidebarCollapsed) {
      document.body.classList.add('sidebar-collapsed');
    } else {
      document.body.classList.remove('sidebar-collapsed');
    }
    
    // Open sidebar on mobile specifically
    if (isMobile) {
      if (!isSidebarCollapsed) {
        document.body.classList.add('sidebar-open');
      } else {
        document.body.classList.remove('sidebar-open');
      }
    }
    
    // Save preference
    if (localStorage) {
      localStorage.setItem('sidebar-collapsed', String(isSidebarCollapsed));
    }
    
    console.log(`[Layout] Sidebar toggled to ${isSidebarCollapsed ? 'collapsed' : 'expanded'}`);
  }

  // Track if we're on mobile
  let isMobile = $state(false);
  
  // Setup responsive behavior
  onMount(() => {
    const checkMobile = () => {
      isMobile = window.innerWidth < 1024; // lg breakpoint
    };
    
    // Initial check
    checkMobile();
    
    // Re-check on resize
    window.addEventListener('resize', checkMobile);
    
    return () => {
      window.removeEventListener('resize', checkMobile);
    };
  });

  // Initialization function for all layout behaviors
  function initializeLayout() {
    console.log("[Layout] Starting layout initialization...");
    
    // Initialize performance monitoring
    initPerformanceMonitoring();
    
    // Initialize image optimization
    const imageObserver = initImageOptimization();
    
    // Debug check for sidebar visibility right after initialization
    setTimeout(() => {
      console.log("[Layout] Checking sidebar visibility after initialization");
      const sidebar = document.querySelector('.sidebar-area');
      console.log("[Layout] Sidebar element found:", !!sidebar);
      console.log("[Layout] Sidebar parent element:", sidebar?.parentElement);
      
      if (sidebar) {
        console.log("[Layout] Sidebar display:", window.getComputedStyle(sidebar).display);
        console.log("[Layout] Sidebar visibility:", window.getComputedStyle(sidebar).visibility);
        console.log("[Layout] Sidebar width:", window.getComputedStyle(sidebar).width);
      }
    }, 500);
    
    // Debug helper for Tailwind classes
    console.debug('Checking for layout issues...');
    const checkStyles = () => {
      /** @type {Array<{element: Element, class: string, issue: string}>} */
      const problematicClasses = [];
      
      document.querySelectorAll('[class]').forEach(el => {
        const classes = Array.from(el.classList);
        
        // Check theme color syntax
        classes.forEach(cls => {
          // Check if any class has theme color but doesn't use the new HSL syntax
          if ((cls.startsWith('text-') || cls.startsWith('bg-') || cls.startsWith('border-') || 
               cls.startsWith('ring-') || cls.startsWith('outline-') || cls.startsWith('shadow-')) && 
              (cls.includes('primary') || cls.includes('secondary') || cls.includes('background') || 
               cls.includes('foreground') || cls.includes('muted') || cls.includes('accent') ||
               cls.includes('popover') || cls.includes('card') || cls.includes('destructive')) && 
              !cls.includes('[hsl(var(')) {
            problematicClasses.push({ 
              element: el, 
              class: cls, 
              issue: 'Tailwind 4 theme color syntax missing'
            });
          }
        });
        
        // Check for inconsistent spacing
        const marginClasses = classes.filter(c => c.startsWith('m-') || c.startsWith('mx-') || 
                                              c.startsWith('my-') || c.startsWith('mt-') || 
                                              c.startsWith('mb-') || c.startsWith('ml-') || 
                                              c.startsWith('mr-'));
                                              
        const paddingClasses = classes.filter(c => c.startsWith('p-') || c.startsWith('px-') || 
                                               c.startsWith('py-') || c.startsWith('pt-') || 
                                               c.startsWith('pb-') || c.startsWith('pl-') || 
                                               c.startsWith('pr-'));
        
        // Check for fractional spacing that should use modern syntax
        [...marginClasses, ...paddingClasses].forEach(cls => {
          // Check for old fraction syntax (e.g., 'p-1/2' should be 'p-[0.5]')
          if (cls.includes('/') && !cls.includes('[')) {
            problematicClasses.push({ 
              element: el, 
              class: cls, 
              issue: 'Use modern bracket notation for fractional spacing'
            });
          }
        });
        
        // Check for nested container issues
        if (classes.includes('container') && el.parentElement) {
          const parentClasses = Array.from(el.parentElement.classList || []);
          if (parentClasses.includes('container')) {
            problematicClasses.push({ 
              element: el, 
              class: 'container', 
              issue: 'Nested container detected - can cause spacing issues'
            });
          }
        }
      });
      
      if (problematicClasses.length > 0) {
        console.warn('Found problematic Tailwind classes:', problematicClasses);
        console.info('Consider updating to Tailwind 4 syntax:', { 
          'Theme colors': {
            'text-primary': 'text-[hsl(var(--primary))]',
            'bg-background': 'bg-[hsl(var(--background))]',
            'border-border': 'border-[hsl(var(--border))]'
          },
          'Fractional values': {
            'p-1/2': 'p-[0.5]',
            'm-1/4': 'm-[0.25]'
          },
          'Container issues': 'Avoid nesting container classes'
        });
      } else {
        console.debug('No problematic Tailwind classes detected');
      }
    };
    
    // Run check after a short delay to allow all components to mount
    setTimeout(checkStyles, 1000);
    
    // Add intersection observer for lazy loading images
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    /** @type {IntersectionObserver|null} */
    let lazyImageObserver = null;
    
    if ('IntersectionObserver' in window) {
      lazyImageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = /** @type {HTMLImageElement} */ (entry.target);
            if (img.dataset.src) {
              img.src = img.dataset.src;
            }
            if (img.dataset.srcset) {
              img.srcset = img.dataset.srcset;
            }
            if (img.dataset.sizes) {
              img.sizes = img.dataset.sizes;
            }
            img.classList.remove('lazy');
            lazyImageObserver?.unobserve(img);
          }
        });
      });
      
      lazyImages.forEach(img => lazyImageObserver?.observe(img));
    } else {
      // Fallback for browsers that don't support IntersectionObserver
      lazyImages.forEach(img => {
        const imgElement = /** @type {HTMLImageElement} */ (img);
        if (imgElement.dataset.src) {
          imgElement.src = imgElement.dataset.src;
        }
        if (imgElement.dataset.srcset) {
          imgElement.srcset = imgElement.dataset.srcset;
        }
      });
    }
    
    // Add preloading for links in viewport
    const linkObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const link = entry.target;
          const href = link.getAttribute('href');
          if (href && href.startsWith('/') && !link.hasAttribute('data-preloaded')) {
            const preloadLink = document.createElement('link');
            preloadLink.rel = 'prefetch';
            preloadLink.href = href;
            document.head.appendChild(preloadLink);
            link.setAttribute('data-preloaded', 'true');
          }
        }
      });
    }, { rootMargin: '200px' });
    
    document.querySelectorAll('a[href^="/"]').forEach(link => {
      linkObserver.observe(link);
    });
    
    // Handle offline/online events
    window.addEventListener('online', () => {
      document.body.classList.remove('offline');
      // Notify user they're back online
      const offlineNotification = document.getElementById('offline-notification');
      if (offlineNotification) {
        offlineNotification.style.display = 'none';
      }
    });
    
    window.addEventListener('offline', () => {
      document.body.classList.add('offline');
      // Notify user they're offline
      let offlineNotification = document.getElementById('offline-notification');
      if (!offlineNotification) {
        const notification = document.createElement('div');
        notification.id = 'offline-notification';
        notification.innerHTML = 'You are currently offline. Some features may be limited.';
        notification.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#ffcc00;color:#000;text-align:center;padding:10px;z-index:9999;';
        document.body.appendChild(notification);
      } else {
        offlineNotification.style.display = 'block';
      }
    });
    
    // Only run in development mode
    if (import.meta.env.DEV) {
      console.log('Checking for problematic shadcn-svelte classes...');
      
      document.querySelectorAll('[class]').forEach(el => {
        const classStr = el.getAttribute('class') || '';
        const problematicClasses = findProblematicClasses(classStr);
        
        if (problematicClasses.length > 0) {
          console.warn(
            'Problematic shadcn-svelte classes found:',
            problematicClasses,
            '\nElement:',
            el
          );
        }
      });
    }
  }
  
  // Initialize on mount
  onMount(() => {
    console.log("[Layout] Component mounted");
    
    // Force sidebar to be visible on initial load
    document.body.classList.remove('sidebar-collapsed');
    console.log("[Layout] Removed sidebar-collapsed class from body");
    
    // Run full layout initialization
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
  });
</script>

<style>
  /* Add CSS for lazy-loaded images */
  :global(img.lazy) {
    @apply opacity-0 transition-opacity duration-300;
  }
  
  :global(img:not(.lazy)) {
    @apply opacity-100;
  }
  
  /* Add CSS for offline mode - use CSS variables from Tailwind */
  :global(body.offline) {
    --offline-indicator: hsl(var(--warning, 48 96% 53%));
  }
  
  :global(body.offline::before) {
    content: '';
    @apply fixed top-[0] left-[0] right-[0] h-[0.25rem];
    background-color: var(--offline-indicator);
  }
  
  /* Prevent horizontal overflow */
  :global(html, body) {
    @apply overflow-x-hidden;
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
    grid-template-rows: auto 1fr auto;
    grid-template-areas: 
      "header"
      "content"
      "footer";
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
  
  /* Inner content grid for sidebar and main */
  .content-grid {
    display: grid;
    grid-template-columns: 240px 1fr; /* Fixed width for sidebar */
    min-height: 100%;
    width: 100%;
  }
  
  /* When sidebar is collapsed, adjust the grid */
  .sidebar-collapsed .content-grid {
    grid-template-columns: 64px 1fr;
  }
  
  /* Sidebar area */
  .sidebar-area {
    width: 240px; /* Expanded state */
    transition: width 0.3s ease;
    background-color: hsl(var(--sidebar-background));
    border-right: 1px solid hsl(var(--sidebar-border)/0.8);
    z-index: 10;
    display: flex; /* Ensure the sidebar fills its container */
    flex-direction: column;
    overflow: hidden; /* Prevent content from overflowing during transition */
    position: relative; /* Ensure proper stacking */
    box-shadow: 1px 0 5px rgba(0, 0, 0, 0.05); /* Add subtle shadow for depth */
  }
  
  /* Collapsed sidebar */
  .sidebar-collapsed .sidebar-area {
    width: 64px;
  }
  
  /* Main content area */
  .main-area {
    overflow-y: auto;
    padding: 1rem;
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
      z-index: 50;
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
      z-index: 60;
      display: block;
      padding: 0.5rem;
      border-radius: 0.375rem;
      background-color: hsl(var(--background));
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
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
    <header class="header-area">
      <div class="container mx-auto h-full flex items-center justify-between px-[2rem]">
        <div class="flex items-center gap-[0.75rem]">
          <!-- Logo -->
          <span class="text-[1.25rem] font-[600]">
            <span class="text-[hsl(var(--primary))]">ASAP</span>Digest
          </span>
        </div>
        
        <!-- Header actions (search, etc) -->
        <div class="flex items-center gap-[1rem]">
          <!-- Search input could go here -->
          <button class="p-[0.5rem] rounded-[0.375rem] bg-[hsl(var(--muted)/0.1)]" aria-label="Search">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </button>
        </div>
      </div>
    </header>
    
    <!-- Content wrapper with max-width -->
    <div class="content-wrapper">
      <!-- Content grid for sidebar and main -->
      <div class="content-grid">
        <!-- Sidebar -->
        <aside class="sidebar-area">
          <SidebarProvider class="w-full h-full flex" style="--sidebar-width: 240px; --sidebar-width-icon: 64px;">
            <MainSidebar />
          </SidebarProvider>
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
        onclick={(e) => toggleSidebar(e)}
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
  </div>
  
  <!-- Performance monitor (dev only) - Positioned outside app-shell -->
  {#if import.meta.env.DEV}
    <PerformanceMonitor />
  {/if}
{/if}