<script>
  import { page } from '$app/stores';
  import { onMount, onDestroy } from 'svelte';
  import { invalidateAll } from '$app/navigation';
  import { toasts } from '$lib/stores/toast';
  import ToastContainer from '$lib/components/ui/toast/toast-container.svelte';
  import * as Avatar from '$lib/components/ui/avatar';
  import { CircleUser, Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import * as DropdownMenu from '$lib/components/ui/dropdown-menu';
  import { Settings, CreditCard, LogOut } from '$lib/utils/lucide-compat.js';
  import { getAvatarUrl } from '$lib/stores/user.js';

  /**
   * @typedef {Object} LayoutData
   * @property {import('svelte').Snippet | undefined} children
   * @property {{ user?: User | null }} data - From server load function
   */
  
  // Use standard $props() destructuring
  /** @type {LayoutData} */
  let { children, data } = $props();
  
  // Access user data reactively from the passed data prop
  let user = $derived(data?.user || null);
  
  // State for loading indicator (remove direct use of useSession here)
  let isLoading = $state(false); // Manage loading state locally if needed

  // Remove useSession import and usage if user comes from server load data
  // import { useSession } from '$lib/auth-client';
  // const { data: session, error: sessionError, isPending: isSessionPending } = useSession();
  // $effect(() => { isLoading = isSessionPending }); // Remove effect tied to useSession

  let eventSource = $state(/** @type {EventSource | null} */ (null));

  /**
   * Helper function to try both sync endpoints
   * @param {string} endpoint - The API endpoint to call
   * @returns {Promise<Object>} The API response
   */
  async function trySyncEndpoint(endpoint) {
    console.debug(`[Sync Layout] Calling sync endpoint: ${endpoint}`);
    const response = await fetch(endpoint, {
      credentials: 'include',
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      }
    });
    
    if (!response.ok) {
      console.warn(`[Sync Layout] Sync endpoint ${endpoint} returned ${response.status}`);
      throw new Error(`Sync failed with status: ${response.status}`);
    }
    
    console.debug(`[Sync Layout] ${endpoint} call completed successfully`);
    return await response.json();
  }

  onMount(() => {
    console.debug('[Sync Layout] onMount: Starting initial sync...');
    
    // Skip sync if we already have user data
    if (user) {
      console.debug('[Sync Layout] User already authenticated, skipping initial sync');
      return;
    }
    
    // Add a timeout to prevent indefinite loading
    const syncTimeout = setTimeout(() => {
      console.warn('[Sync Layout] Initial sync timed out, proceeding with local data');
    }, 5000);
    
    // Try main sync endpoint first
    trySyncEndpoint('/api/auth/sync')
      .catch(error => {
        console.warn('[Sync Layout] Primary sync endpoint failed:', error.message);
        // Try fallback endpoint if primary fails
        return trySyncEndpoint('/api/auth/sync-check');
      })
      .then(async (response) => {
        clearTimeout(syncTimeout);
        console.debug('[Sync Layout] Initial sync result:', response);
        
        if (response.ok) {
          console.debug('[Sync Layout] Sync successful');
          const result = await response.json();
          // Handle successful sync
        }
      })
      .catch(error => {
        clearTimeout(syncTimeout);
        console.error('[Sync Layout] All sync attempts failed:', error);
      });

    console.debug('[Sync Layout] Setting up SSE connection...');
    
    // Check if root layout already has an SSE connection
    if (typeof window !== 'undefined' && window.asapDigestSseActive) {
      console.debug('[Sync Layout] Root layout SSE connection already active, skipping duplicate connection');
      return;
    }
    
    // Only set up SSE if not already connected at root layout
    let eventSource = null;
    try {
      eventSource = new EventSource('/api/auth/sync-stream');
      
      eventSource.onopen = () => {
        console.log('[Protected Layout] SSE connection opened');
      };
      
      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          // Process the data as needed
          console.log('[Protected Layout] SSE message received:', data);
          
          // Handle user updates, etc.
        } catch (error) {
          console.error('[Protected Layout] Error processing SSE message:', error);
        }
      };
      
      eventSource.onerror = (error) => {
        console.error('[Protected Layout] SSE connection error:', error);
      };
      
      return () => {
        if (eventSource) {
          console.log('[Protected Layout] Closing SSE connection');
          eventSource.close();
        }
      };
    } catch (error) {
      console.error('[Protected Layout] Error setting up SSE connection:', error);
    }
  });
  
  onDestroy(() => {
      console.log('[Sync Layout] Component destroying. Closing SSE connection.');
      if (eventSource) {
          eventSource.close();
          eventSource = null;
          
          // Reset the global flag when this component is destroyed
          if (typeof window !== 'undefined') {
            window.asapDigestSseActive = false;
          }
      }
      // Clear any pending retry timeouts if implemented more robustly
  });

  async function handleLogout() {
    // ... existing code ...
  }
</script>

{#if user}
  <div class="protected-layout">
    <header class="user-header">
      <div class="user-info">
        <Avatar.Root class="h-9 w-9">
          <Avatar.Image 
            class="" 
            src={getAvatarUrl(user) || '/images/default-avatar.svg'} 
            alt={user?.displayName || 'User Avatar'} 
          />
          <Avatar.Fallback class="">
            <Icon icon={CircleUser} class="w-8 h-8 text-[hsl(var(--muted-foreground))]" color="currentColor" />
          </Avatar.Fallback>
        </Avatar.Root>
        <div class="user-details">
          <span class="display-name">{user?.displayName || 'User'}</span>
          <span class="email">{user?.email || ''}</span>
          {#if user?.plan}
            <span class="plan">
              {#if typeof user.plan === 'object' && user.plan !== null}
                {user.plan.name || 'Free'}
              {:else if typeof user.plan === 'string'}
                {user.plan}
              {:else}
                Free
              {/if}
            </span>
          {/if}
        </div>
      </div>
      <DropdownMenu.Root>
        <DropdownMenu.Trigger asChild>
          <button class="flex items-center p-1 rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            <Icon icon={Settings} class="h-5 w-5" color="currentColor" />
          </button>
        </DropdownMenu.Trigger>
        <DropdownMenu.Content class="w-56" align="end">
          <DropdownMenu.Label>
            {user.displayName || user.email}
            {#if user?.plan}
              <span class="block text-xs text-[hsl(var(--primary)/0.8)] mt-1">
                {#if typeof user.plan === 'object' && user.plan !== null}
                  {user.plan.name || 'Free Plan'}
                {:else if typeof user.plan === 'string'}
                  {user.plan}
                {:else}
                  Free Plan
                {/if}
              </span>
            {/if}
          </DropdownMenu.Label>
          <DropdownMenu.Separator />
          <DropdownMenu.Item href="/profile">
            <Icon icon={Settings} class="mr-2 h-4 w-4" color="currentColor" />
            <span>Profile</span>
          </DropdownMenu.Item>
          <DropdownMenu.Item href="/billing">
            <Icon icon={CreditCard} class="mr-2 h-4 w-4" color="currentColor" />
            <span>Billing</span>
          </DropdownMenu.Item>
          <DropdownMenu.Separator />
          <DropdownMenu.Item on:click={handleLogout}>
            <Icon icon={LogOut} class="mr-2 h-4 w-4" color="currentColor" />
            <span>Logout</span>
          </DropdownMenu.Item>
        </DropdownMenu.Content>
      </DropdownMenu.Root>
    </header>
    
    <main>
      {@render children()}
    </main>
  </div>
{/if}

<ToastContainer />

<style>
  .protected-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  .user-header {
    background: hsl(var(--background));
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
  }

  .user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: hsl(var(--muted));
  }

  .user-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .display-name {
    font-weight: 500;
    color: hsl(var(--foreground));
  }

  .email {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
  }

  .plan {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
  }

  main {
    flex: 1;
    padding: 2rem;
  }
</style> 