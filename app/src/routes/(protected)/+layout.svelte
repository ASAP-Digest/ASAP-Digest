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
  const { data } = $props();

  const user = $derived(data.user);
  let syncInterval;
  let firstSync = true;

  onMount(() => {
    // Initial sync to check for auto-login
    fetch('/api/auth/sync', {
      credentials: 'include'
    }).then(async (response) => {
      if (response.ok) {
        const result = await response.json();
        if (result.valid && result.updated) {
          // Show auto-login notification only when auto-login occurs
          toasts.show('Successfully logged in via WordPress', 'success');
          await invalidateAll();
        }
      }
    }).catch((error) => {
      console.error('Initial sync failed:', error);
    });

    // Set up periodic sync every 5 minutes
    syncInterval = setInterval(async () => {
      try {
        const response = await fetch('/api/auth/sync', {
          credentials: 'include'
        });
        
        if (response.ok) {
          const result = await response.json();
          if (result.updated) {
            // Refresh all data if user info was updated
            await invalidateAll();
            if (!firstSync) {
              toasts.show('Profile information updated', 'info');
            }
            firstSync = false;
          }
        }
      } catch (error) {
        console.error('Auto-sync failed:', error);
        toasts.show('Failed to sync profile information', 'error');
      }
    }, 5 * 60 * 1000);

    return () => {
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
      <slot />
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