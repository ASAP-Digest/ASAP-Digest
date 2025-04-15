<script>
  import { page } from '$app/stores';
  import { onMount, onDestroy } from 'svelte';
  import { invalidateAll } from '$app/navigation';
  import { toasts } from '$lib/stores/toast';
  import ToastContainer from '$lib/components/ui/toast/toast-container.svelte';
  import * as Avatar from '$lib/components/ui/avatar';
  import { CircleUser, Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { DropdownMenu } from '$lib/components/ui/dropdown-menu';
  import { Settings, CreditCard, LogOut } from 'lucide-icons';

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

  onMount(() => {
    console.debug('[Sync Layout] onMount: Starting initial sync...');
    // Initial sync to check for auto-login
    fetch('/api/auth/sync', {
      credentials: 'include'
    }).then(async (response) => {
      console.debug('[Sync Layout] Initial sync fetch completed. Status:', response.status);
      console.log(`["(protected) Layout"] Sync API response status: ${response.status}`); // DEBUG

      if (response.ok) {
        const result = await response.json();
        console.debug('[Sync Layout] Initial sync result:', result);
        console.debug('["(protected) Layout"] Received data from /api/auth/sync:', JSON.stringify(result)); 
        if (result.valid && result.updated) {
          console.debug('[Sync Layout] Initial sync indicates update. Invalidating and showing toast...');
          // Show auto-login notification only when auto-login occurs
          toasts.show('Successfully logged in via WordPress', 'success');
          await invalidateAll();
        } else {
          console.debug('[Sync Layout] Initial sync valid but no update detected.');
        }
      } else {
         console.debug('[Sync Layout] Initial sync fetch failed or returned non-OK status.');
      }
    }).catch((error) => {
      console.error('[Sync Layout] Initial sync fetch error:', error);
    });

    // --- Setup Server-Sent Events --- 
    console.log('[Sync Layout] Setting up SSE connection...');
    eventSource = new EventSource('/api/auth/sync-stream');

    eventSource.onopen = () => {
      console.log('[Sync Layout] SSE connection opened.');
    };

    eventSource.onerror = (error) => {
      console.error('[Sync Layout] SSE connection error:', error);
      // Optional: Implement reconnection logic here if needed
      toasts.show('Real-time sync connection lost. Retrying...', 'warning');
      eventSource?.close(); // Close the errored connection
      // Simple retry after a delay
      setTimeout(() => {
        if (!eventSource || eventSource.readyState === EventSource.CLOSED) {
           console.log('[Sync Layout] Attempting SSE reconnection...');
           eventSource = new EventSource('/api/auth/sync-stream');
           // Re-attach handlers if necessary (or manage within a class/store)
        }
      }, 5000); // Retry after 5 seconds
    };

    eventSource.onmessage = (event) => {
      try {
        const eventData = JSON.parse(event.data);
        console.log('[Sync Layout] SSE message received:', eventData);

        if (eventData.connected) {
          console.log(`[Sync Layout] SSE confirmed connection with clientId: ${eventData.clientId}`);
          return; // Ignore connection confirmation message
        }

        if (eventData.updated) {
            // Check if the update is for the current user (optional but good practice)
            if (eventData.userId === user?.id) {
                console.log('[Sync Layout] SSE update received for current user. Invalidating data and showing toast...');
                invalidateAll();
                toasts.show('Profile information updated', 'info');
            } else {
                 console.log(`[Sync Layout] SSE update received for different user (${eventData.userId}), ignoring.`);
            }
        }
      } catch (e) {
        console.error('[Sync Layout] Error parsing SSE message:', e, 'Data:', event.data);
      }
    };
  });
  
  onDestroy(() => {
      console.log('[Sync Layout] Component destroying. Closing SSE connection.');
      if (eventSource) {
          eventSource.close();
          eventSource = null;
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
          <Avatar.Image class="" src={user.avatarUrl} alt={user.displayName || 'User Avatar'} />
          <Avatar.Fallback class="">
            <Icon icon={CircleUser} class="w-8 h-8 text-[hsl(var(--muted-foreground))]" color="currentColor" />
          </Avatar.Fallback>
        </Avatar.Root>
        <div class="user-details">
          <span class="display-name">{user.displayName}</span>
          <span class="email">{user.email}</span>
        </div>
      </div>
      <DropdownMenu>
        <DropdownMenu.Button>
          <Icon icon={Settings} class="mr-2 h-4 w-4" color="currentColor" />
        </DropdownMenu.Button>
        <DropdownMenu.Content class="w-56" align="end">
          <DropdownMenu.Label>{user.displayName || user.email}</DropdownMenu.Label>
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

  main {
    flex: 1;
    padding: 2rem;
  }
</style> 