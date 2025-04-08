<script>
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { invalidateAll } from '$app/navigation';
  import { toasts } from '$lib/stores/toast';
  import ToastContainer from '$lib/components/ui/toast/toast-container.svelte';
  import * as Avatar from '$lib/components/ui/avatar';
  import { CircleUser, Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';

  /** @type {import('./$types').LayoutData} */
  let { data, children } = $props();

  /** @type {import('app').App.User | null} */
  const user = $derived(data.user);
  let syncInterval;
  let firstSync = true;

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

    console.debug('[Sync Layout] Setting up periodic sync interval (5 minutes)...');
    // Set up periodic sync every 5 minutes
    syncInterval = setInterval(async () => {
      console.debug('[Sync Layout] Interval: Running periodic sync...');
      try {
        const response = await fetch('/api/auth/sync', {
          credentials: 'include'
        });
        console.debug('[Sync Layout] Interval sync fetch completed. Status:', response.status);
        
        if (response.ok) {
          const result = await response.json();
           console.debug('[Sync Layout] Interval sync result:', result);
          if (result.updated) {
            console.debug('[Sync Layout] Interval sync indicates update. Invalidating...');
            // Refresh all data if user info was updated
            await invalidateAll();
            if (!firstSync) {
              console.debug('[Sync Layout] Interval sync: Showing profile update toast.');
              toasts.show('Profile information updated', 'info');
            } else {
               console.debug('[Sync Layout] Interval sync: Update detected on first sync, suppressing toast.');
            }
            firstSync = false;
          } else {
             console.debug('[Sync Layout] Interval sync: No update detected.');
          }
        } else {
           console.debug('[Sync Layout] Interval sync fetch failed or returned non-OK status.');
        }
      } catch (error) {
        console.error('[Sync Layout] Interval sync fetch error:', error);
        toasts.show('Failed to sync profile information', 'error');
      }
    }, 5 * 60 * 1000);

    return () => {
      console.debug('[Sync Layout] Component unmounting. Clearing sync interval.');
      if (syncInterval) clearInterval(syncInterval);
    };
  });
</script>

{#if user}
  <div class="protected-layout">
    <header class="user-header">
      <div class="user-info">
        {#if user.avatarUrl}
          <Avatar.Root>
            <Avatar.Image src={user.avatarUrl} alt={user.displayName} />
            <Avatar.Fallback>
              <Icon icon={CircleUser} class="w-8 h-8 text-[hsl(var(--muted-foreground))]" />
            </Avatar.Fallback>
          </Avatar.Root>
        {:else}
          <div class="avatar-placeholder">
            <Icon icon={CircleUser} class="w-8 h-8 text-[hsl(var(--muted-foreground))]" />
          </div>
        {/if}
        <div class="user-details">
          <span class="display-name">{user.displayName}</span>
          <span class="email">{user.email}</span>
        </div>
      </div>
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