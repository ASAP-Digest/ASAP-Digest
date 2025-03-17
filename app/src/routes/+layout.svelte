<script>
  import "../app.css";
  import { onMount } from 'svelte';
  import Navigation from "$lib/components/layout/Navigation.svelte";
  import Footer from "$lib/components/layout/Footer.svelte";
  import PerformanceMonitor from "$lib/components/ui/PerformanceMonitor.svelte";
  import { initPerformanceMonitoring } from "$lib/utils/performance";
  import { initImageOptimization } from "$lib/utils/imageOptimizer";
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
    
    // Add intersection observer for lazy loading images
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    if ('IntersectionObserver' in window) {
      const lazyImageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
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
</script>

<div class="min-h-screen bg-background text-foreground flex flex-col">
  <Navigation />
  
  <main class="flex-1 container mx-auto px-4 py-4">
    {@render children?.()}
  </main>
  
  <Footer />
  
  {#if import.meta.env.DEV}
    <PerformanceMonitor />
  {/if}
</div>

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