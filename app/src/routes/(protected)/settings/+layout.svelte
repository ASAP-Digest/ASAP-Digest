<!--
  Settings Layout
  --------------
  Provides consistent layout and state management for all settings pages.
  Handles offline detection and local-first data display.
  
  @file-marker settings-layout
  @implementation-context: SvelteKit, Better Auth, Local First
-->
<script>
  import { page } from '$app/stores';
  import { browser } from '$app/environment';
  import { Alert, AlertDescription } from '$lib/components/ui/alert';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { Wifi, WifiOff } from '$lib/utils/lucide-compat.js';
  import { onMount } from 'svelte';
  import { user as userStore, authStore } from '$lib/utils/auth-persistence';
  import { goto } from '$app/navigation';
  
  /**
   * @typedef {Object} LayoutData
   * @property {Object|null} user - User data (may be null during SSR or loading)
   * @property {boolean} [usingLocalAuth] - Whether using cached local auth
   */
  
  /** @type {LayoutData} */
  let { data, children } = $props();
  
  // Local state
  let isOffline = $state(false);
  let isLoading = $state(true);
  let userValue = null;
  
  // Listen for offline/online events
  function handleConnectionChange() {
    isOffline = !navigator.onLine;
  }
  
  onMount(() => {
    console.log('[Settings Layout] Mounted with data.user:', data?.user?.id || 'null');
    
    if (browser) {
      // Set initial offline state
      isOffline = !navigator.onLine;
      window.addEventListener('online', handleConnectionChange);
      window.addEventListener('offline', handleConnectionChange);
      
      // Subscribe to user store changes
      const unsubscribe = userStore.subscribe(value => {
        userValue = value;
        console.log('[Settings Layout] userStore updated:', value?.id || 'null');
        isLoading = false;
      });
      
      // Try to recover from localStorage if needed
      if (!data?.user && !userValue) {
        try {
          // Check localStorage directly as a last resort
          const LOCAL_AUTH_KEY = 'asap_digest_auth';
          const storedData = localStorage.getItem(LOCAL_AUTH_KEY);
          if (storedData) {
            const parsedData = JSON.parse(storedData);
            if (parsedData && parsedData.id) {
              console.log('[Settings Layout] Recovered auth from localStorage');
              // Update the store
              userValue = parsedData;
              authStore.set(parsedData);
              isLoading = false;
            }
          }
        } catch (error) {
          console.error('[Settings Layout] Error checking localStorage:', error);
        }
      }
      
      return () => {
        window.removeEventListener('online', handleConnectionChange);
        window.removeEventListener('offline', handleConnectionChange);
        unsubscribe();
      };
    }
  });
  
  // Effect to react to data changes
  $effect(() => {
    console.log('[Settings Layout] data.user updated:', data?.user?.id || 'null');
    
    // Check if we have auth data from parent layout
    if (browser && !data?.user && !userValue) {
      // No auth data in data or store, redirect to home for authentication
      console.log('[Settings Layout] No auth data available, redirecting to home');
      goto('/');
    }
  });
  
  // Derived values - fix syntax to match Svelte 5 runes
  let effectiveUser = $derived(data?.user || userValue);
  let pageTitle = $derived($page.route.id?.split('/').pop() || 'Settings');
  let formattedTitle = $derived(pageTitle.charAt(0).toUpperCase() + pageTitle.slice(1));
</script>

<div class="container mx-auto p-4 lg:p-6">
  <!-- Offline Mode Alert -->
  {#if isOffline}
    <Alert class="mb-6">
      <Icon icon={WifiOff} class="h-4 w-4 mr-2 stroke-orange-500 dark:stroke-orange-400" />
      <AlertDescription>
        You're in offline mode. Changes will sync when you're back online.
      </AlertDescription>
    </Alert>
  {/if}

  <!-- Using Local Auth Alert -->
  {#if !isOffline && data?.usingLocalAuth}
    <Alert class="mb-6">
      <Icon icon={Wifi} class="h-4 w-4 mr-2 stroke-blue-500 dark:stroke-blue-400" />
      <AlertDescription>
        Using cached authentication. Session will sync when server is available.
      </AlertDescription>
    </Alert>
  {/if}
  
  <!-- Loading or No User State -->
  {#if isLoading}
    <div class="animate-pulse">
      <div class="h-8 w-64 bg-gray-200 dark:bg-gray-700 rounded mb-6"></div>
      <div class="h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
    </div>
  {:else if !effectiveUser && browser}
    <div class="text-center py-10">
      <p>Authentication required. Redirecting...</p>
    </div>
  {:else}
    <!-- Settings Title -->
    <h1 class="text-2xl font-semibold mb-6 border-b border-gray-200 dark:border-gray-800 pb-2">
      {formattedTitle} Settings
    </h1>
    
    <!-- Settings Content -->
    <div class="grid grid-cols-1 md:grid-cols-[250px_1fr] gap-6">
      <!-- Settings Navigation -->
      <div class="space-y-2">
        <a 
          href="/settings/account" 
          class="block p-2 rounded {$page.route.id?.includes('account') ? 'bg-primary/10 font-medium' : 'hover:bg-gray-100 dark:hover:bg-gray-800'}">
          Account
        </a>
        <a 
          href="/settings/security" 
          class="block p-2 rounded {$page.route.id?.includes('security') ? 'bg-primary/10 font-medium' : 'hover:bg-gray-100 dark:hover:bg-gray-800'}">
          Security
        </a>
        <a 
          href="/settings/privacy" 
          class="block p-2 rounded {$page.route.id?.includes('privacy') ? 'bg-primary/10 font-medium' : 'hover:bg-gray-100 dark:hover:bg-gray-800'}">
          Privacy
        </a>
        <a 
          href="/settings/notifications" 
          class="block p-2 rounded {$page.route.id?.includes('notifications') ? 'bg-primary/10 font-medium' : 'hover:bg-gray-100 dark:hover:bg-gray-800'}">
          Notifications
        </a>
      </div>
      
      <!-- Main Content Area -->
      <div>
        {@render children()}
      </div>
    </div>
  {/if}
</div> 