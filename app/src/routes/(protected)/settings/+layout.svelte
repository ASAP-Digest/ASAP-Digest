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
  import { Icon } from '$lib/components/ui/icon/icon.svelte';
  import { Wifi, WifiOff } from '$lib/utils/lucide-compat.js';
  
  /**
   * @typedef {Object} LayoutData
   * @property {Object} user - User data
   * @property {boolean} [usingLocalAuth] - Whether using cached local auth
   */
  
  /** @type {LayoutData} */
  let { data } = $props();
  
  // Create reactive derived state for user data to ensure updates during navigation
  let user = $derived(data.user);
  
  // Network status
  let isOnline = $state(browser ? navigator.onLine : true);
  let wasOffline = $state(false);
  
  // Update network status when data changes
  $effect(() => {
    if (data.usingLocalAuth && browser && navigator.onLine) {
      wasOffline = true;
    }
  });
  
  // Listen for online/offline events when in browser
  if (browser) {
    window.addEventListener('online', () => {
      isOnline = true;
    });
    
    window.addEventListener('offline', () => {
      isOnline = false;
    });
  }
</script>

<div class="container mx-auto py-6 px-4 md:px-6">
  {#if data.usingLocalAuth && isOnline && wasOffline}
    <div class="fixed bottom-4 right-4 z-50">
      <Alert variant="info" class="border border-blue-200 bg-blue-50 text-blue-800 max-w-md shadow-md">
        <Icon icon={Wifi} class="h-4 w-4 mr-2" />
        <AlertDescription>
          You're back online! Some changes may need to sync with the server.
          <button class="ml-2 text-blue-600 underline" onclick={() => window.location.reload()}>
            Refresh
          </button>
        </AlertDescription>
      </Alert>
    </div>
  {:else if !isOnline}
    <div class="fixed bottom-4 right-4 z-50">
      <Alert variant="warning" class="border border-amber-200 bg-amber-50 text-amber-800 max-w-md shadow-md">
        <Icon icon={WifiOff} class="h-4 w-4 mr-2" />
        <AlertDescription>
          You're currently offline. Changes will be saved locally.
        </AlertDescription>
      </Alert>
    </div>
  {/if}
  
  <slot />
</div> 