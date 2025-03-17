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
  import { LAYOUT_SPACING } from '$lib/styles/spacing.js';
  import MainSidebar from '$lib/components/layout/MainSidebar.svelte';
  import { SidebarProvider, SidebarTrigger, useSidebar } from '$lib/components/ui/sidebar';
  import * as Sheet from '$lib/components/ui/sheet';
  /**
   * @typedef {Object} Props
   * @property {import('svelte').Snippet} [children]
   */

  /** @type {Props} */
  let { children } = $props();
  
  // Initialize performance monitoring and optimizations on mount
  onMount(() => {
    // Initialize performance monitoring in production and development
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
    if ('IntersectionObserver' in window) {
      const lazyImageObserver = new IntersectionObserver((entries) => {
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
            lazyImageObserver.unobserve(img);
          }
        });
      });
      
      lazyImages.forEach(img => lazyImageObserver.observe(img));
    } else {
      // Fallback for browsers that don't support IntersectionObserver
      lazyImages.forEach(img => {
        if (img.dataset.src) {
          img.src = img.dataset.src;
        }
        if (img.dataset.srcset) {
          img.srcset = img.dataset.srcset;
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
      if (document.getElementById('offline-notification')) {
        document.getElementById('offline-notification').style.display = 'none';
      }
    });
    
    window.addEventListener('offline', () => {
      document.body.classList.add('offline');
      // Notify user they're offline
      if (!document.getElementById('offline-notification')) {
        const notification = document.createElement('div');
        notification.id = 'offline-notification';
        notification.innerHTML = 'You are currently offline. Some features may be limited.';
        notification.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#ffcc00;color:#000;text-align:center;padding:10px;z-index:9999;';
        document.body.appendChild(notification);
      } else {
        document.getElementById('offline-notification').style.display = 'block';
      }
    });
    
    return () => {
      // Clean up observers when component unmounts
      imageObserver?.disconnect();
      lazyImageObserver?.disconnect();
      linkObserver?.disconnect();
    };
  });

  /**
   * Determines if the current route is an auth route
   */
  let isAuthRoute = $derived($page.url.pathname.startsWith('/login') || $page.url.pathname.startsWith('/register'));
  
  // Hook into sidebar state for mobile sheet
  let sheetOpen = $state(false);
  
  // Create a function to sync sidebar and sheet states
  function handleSidebarToggle() {
    const sidebar = useSidebar();
    sheetOpen = sidebar.isOpen;
  }
  
  // Update sheet when sidebar changes
  onMount(() => {
    try {
      const sidebar = useSidebar();
      // Watch for changes
      if (sidebar && sidebar.subscribe) {
        const unsubscribe = sidebar.subscribe((state) => {
          if (state) {
            sheetOpen = state.isOpen;
          }
        });
        
        return unsubscribe;
      }
    } catch (error) {
      console.error("Error setting up sidebar subscription:", error);
    }
    
    return () => {};
  });
  
  // Close sidebar when sheet closes
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
</script>

<SidebarProvider>
  <div class="grid md:grid-cols-[250px_1fr] grid-cols-[0_1fr] min-h-screen bg-[hsl(var(--background))] text-[hsl(var(--foreground))]">
    <!-- Desktop Sidebar -->
    <aside class="hidden md:block h-screen">
      <MainSidebar />
    </aside>

    <!-- Main content -->
    <div class="flex flex-col">
      <!-- Header navigation with proper spacing -->
      <header class="sticky top-0 z-50 w-full border-b bg-[hsl(var(--background))] backdrop-blur supports-[backdrop-filter]:bg-[hsl(var(--background))]/60">
        <div class="flex items-center">
          <div class="md:hidden ml-4">
            <SidebarTrigger class="h-10 w-10" />
          </div>
          <Navigation />
        </div>
      </header>

      <!-- Main content with minimum viewport height and proper spacing from header/footer -->
      <main class="flex-grow pt-4 pb-16 md:pb-8 px-4 md:px-6 lg:px-8">
        {@render children?.()}
      </main>

      <!-- Sticky mobile navigation for small screens only with proper spacing -->
      <div class="md:hidden fixed bottom-0 left-0 right-0 z-50">
        <MobileNav />
      </div>

      <!-- Footer with proper top spacing -->
      <footer class="border-t mt-auto py-8 {LAYOUT_SPACING.container}">
        <Footer />
      </footer>
    </div>

    <!-- Mobile Sidebar Sheet -->
    <Sheet.Root open={sheetOpen} onOpenChange={handleSheetOpenChange}>
      <Sheet.Content side="left" class="p-0 max-w-[280px]">
        <MainSidebar />
      </Sheet.Content>
    </Sheet.Root>

    <!-- Install prompt with proper spacing -->
    <div class="fixed bottom-20 md:bottom-8 right-4 z-40">
      <InstallPrompt />
    </div>

    <!-- Performance monitor - only shown in dev mode -->
    {#if import.meta.env.DEV}
      <div class="fixed bottom-4 left-4 z-50 bg-[hsl(var(--card))] p-4 rounded shadow-lg text-xs opacity-80 hover:opacity-100">
        <PerformanceMonitor />
      </div>
    {/if}
  </div>
</SidebarProvider>

<style>
  /* Add CSS for lazy-loaded images */
  :global(img.lazy) {
    opacity: 0;
    transition: opacity 0.3s;
  }
  
  :global(img:not(.lazy)) {
    opacity: 1;
  }
  
  /* Add CSS for offline mode */
  :global(body.offline) {
    --offline-indicator: #ffcc00;
  }
  
  :global(body.offline::before) {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background-color: var(--offline-indicator);
    z-index: 9999;
  }
</style>