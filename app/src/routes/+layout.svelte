<script>
  import "../app.css";
  import { onMount } from 'svelte';
  import Navigation from "$lib/components/layout/Navigation.svelte";
  import Footer from "$lib/components/layout/Footer.svelte";
  import PerformanceMonitor from "$lib/components/ui/PerformanceMonitor.svelte";
  import { initPerformanceMonitoring } from "$lib/utils/performance";
  import { initImageOptimization } from "$lib/utils/imageOptimizer";
  import { page } from '$app/stores';
  import MobileNav from '$lib/components/layout/MobileNav.svelte';
  import InstallPrompt from '$lib/components/pwa/InstallPrompt.svelte';
  import MainSidebar from '$lib/components/layout/MainSidebar.svelte';
  import { SidebarProvider, SidebarTrigger, useSidebar } from '$lib/components/ui/sidebar';
  import * as Sheet from '$lib/components/ui/sheet';
  import { findProblematicClasses } from '$lib/utils/tailwindFixer';
  import { setSidebar } from '$lib/components/ui/sidebar/context.svelte.js';
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
  
  // Hook into sidebar state for mobile sheet
  let sheetOpen = $state(false);
  
  // Create a function to sync sidebar and sheet states
  function handleSidebarToggle() {
    const sidebar = useSidebar();
    sheetOpen = sidebar.isOpen;
  }
  
  // Close sidebar when sheet closes
  /**
   * Handles changes to the sheet's open state and syncs with sidebar
   * @param {boolean} open - Whether the sheet is open
   */
  function handleSheetOpenChange(open) {
    try {
      const sidebar = useSidebar();
      if (sidebar) {
        if (!open && sidebar.isOpen) {
          sidebar.close();
        } else if (open && !sidebar.isOpen) {
          sidebar.open();
        }
      }
      sheetOpen = open;
    } catch (error) {
      console.error("Error handling sheet open change:", error);
      sheetOpen = open;
    }
  }

  // Initialization function for all layout behaviors
  function initializeLayout() {
    console.log("Initializing layout...");
    
    // Initialize performance monitoring
    initPerformanceMonitoring();
    
    // Initialize image optimization
    const imageObserver = initImageOptimization();
    
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
    
    // Handle sidebar subscription
    let sidebarUnsubscribe = null;
    try {
      const sidebar = useSidebar();
      if (sidebar && sidebar.subscribe) {
        sidebarUnsubscribe = sidebar.subscribe((/** @type {any} */ state) => {
          if (state) {
            sheetOpen = state.isOpen;
          }
        });
      }
    } catch (error) {
      console.error("Error setting up sidebar subscription:", error);
    }
    
    // Cleanup function
    return () => {
      imageObserver?.disconnect();
      lazyImageObserver?.disconnect();
      linkObserver?.disconnect();
      if (sidebarUnsubscribe) sidebarUnsubscribe();
    };
  }
  
  // Use a single onMount with the initialization function
  onMount(initializeLayout);

  // Set up sidebar state (expanded by default, but can be toggled)
  let sidebarOpen = $state(true);
  /**
   * Updates the sidebar open state
   * @param {boolean} open - Whether the sidebar should be open
   */
  const setOpen = (open) => {
    sidebarOpen = open;
  };
  
  // Track sidebar initialization
  let sidebarInitialized = $state(false);
  
  // Initialize sidebar state during component mount
  onMount(() => {
    if (typeof document !== 'undefined') {
      setSidebar({
        open: () => sidebarOpen,
        setOpen
      });
      sidebarInitialized = true;
    }
    
    return () => {
      // Clean up if needed
      sidebarInitialized = false;
    };
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
    @apply fixed top-[0] left-[0] right-[0] h-[0.25rem] z-[9999];
    background-color: var(--offline-indicator);
  }
  
  /* Define sidebar width */
  :global(:root) {
    --sidebar-width: 240px;
  }
</style>

<!-- Main wrapper - corresponds to <main-wrapper> -->
<div class="flex flex-col min-h-screen">
  {#if !isAuthRoute}
    <!-- Full width header -->
    <header class="sticky top-0 z-30 w-full bg-[hsl(var(--background))] border-b border-[hsl(var(--border))]">
      <Navigation />
    </header>
    
    <!-- Main content wrap - corresponds to <main-content-wrap> -->
    <div class="flex flex-1 relative">
      <!-- Sidebar - desktop view -->
      <aside class="hidden md:block w-[var(--sidebar-width)] shrink-0 border-r border-[hsl(var(--border))] bg-[hsl(var(--background))]">
        {#if sidebarInitialized}
          <MainSidebar />
        {/if}
      </aside>
      
      <!-- Mobile sidebar trigger -->
      <div class="md:hidden fixed left-4 top-16 z-40">
        {#if sidebarInitialized}
          <button 
            class="flex h-10 w-10 items-center justify-center rounded-md border border-[hsl(var(--border))] bg-[hsl(var(--background))]"
            onclick={() => {
              const sidebar = useSidebar();
              sidebar.toggle();
            }}
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
              <path d="M4 6h16"></path>
              <path d="M4 12h16"></path>
              <path d="M4 18h16"></path>
            </svg>
            <span class="sr-only">Toggle sidebar</span>
          </button>
        {/if}
      </div>
      
      <!-- Mobile sheet for sidebar -->
      <Sheet.Root bind:open={sheetOpen} onOpenChange={handleSheetOpenChange}>
        <Sheet.Content 
          side="left" 
          class="w-[var(--sidebar-width)] p-0 border-r border-[hsl(var(--border))]"
          portalProps={{}}
        >
          {#if sidebarInitialized}
            <MainSidebar />
          {/if}
        </Sheet.Content>
      </Sheet.Root>
      
      <!-- Main content - corresponds to <main-content> -->
      <main class="flex-1 w-full overflow-x-hidden">
        {@render children?.()}
      </main>
    </div>
    
    <!-- Full width footer -->
    <footer class="w-full border-t border-[hsl(var(--border))] bg-[hsl(var(--background))]">
      <Footer />
    </footer>
  {:else}
    <!-- Auth layout (simpler) -->
    <div class="flex flex-col min-h-screen">
      <main class="flex-1">
        {@render children?.()}
      </main>
    </div>
  {/if}
</div>