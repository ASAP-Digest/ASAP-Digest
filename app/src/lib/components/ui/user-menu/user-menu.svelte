<!-- User menu component -->
<script>
  import { page } from '$app/stores';
  import { Button } from '$lib/components/ui/button';
  import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
  } from '$lib/components/ui/dropdown-menu';
  import * as Avatar from '$lib/components/ui/avatar';
  import { CircleUser, Settings, LogOut } from '$lib/utils/lucide-compat.js';
  import Icon from '../icon/icon.svelte';
  import { onMount } from 'svelte';

  /** @type {{ user: import('app').App.User }} */
  const { data } = $props();

  const user = $derived(data.user);

  /**
   * @description Minimal theme switcher logic for avatar dropdown
   * @created 12.10.24 | 01:00 AM PDT
   */
  let theme = 'default';
  onMount(() => {
    // Detect current theme on mount
    theme = document.documentElement.dataset.theme === 'new' ? 'new' : 'default';
  });
  /**
   * @param {string} value - 'default' or 'new'
   * Logs theme changes with previous and new value, and user info if available.
   */
  function setTheme(value) {
    const prevTheme = theme;
    theme = value;
    if (value === 'new') {
      document.documentElement.setAttribute('data-theme', 'new');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
    // Logging
    console.log(`Theme changed:`, {
      previous: prevTheme,
      next: value,
      user: user?.displayName || 'unknown',
      email: user?.email || 'unknown',
      timestamp: new Date().toISOString()
    });
  }

  /**
   * Logs click events on the theme switcher select for troubleshooting.
   * @param {MouseEvent} event
   */
  function logThemeClick(event) {
    console.log('Theme switcher <select> clicked', {
      event,
      theme,
      user: user?.displayName || 'unknown',
      email: user?.email || 'unknown',
      timestamp: new Date().toISOString()
    });
  }
</script>

<DropdownMenu>
  <DropdownMenuTrigger asChild let:builder>
    <Button
      variant="ghost"
      class="relative h-10 w-10 rounded-full"
      builders={[builder]}
    >
      {#if user.avatarUrl}
        <Avatar.Root>
          <Avatar.Image src={user.avatarUrl} alt={user.displayName} />
          <Avatar.Fallback>
            <Icon icon={CircleUser} class="w-6 h-6 text-[hsl(var(--muted-foreground))]" />
          </Avatar.Fallback>
        </Avatar.Root>
      {:else}
        <div class="avatar-placeholder">
          <Icon icon={CircleUser} class="w-6 h-6 text-[hsl(var(--muted-foreground))]" />
        </div>
      {/if}
    </Button>
  </DropdownMenuTrigger>
  
  <DropdownMenuContent class="w-56" align="end">
    <DropdownMenuLabel>
      <div class="flex flex-col space-y-1">
        <p class="text-sm font-medium leading-none">{user.displayName}</p>
        <p class="text-xs leading-none text-muted-foreground">{user.email}</p>
      </div>
    </DropdownMenuLabel>

    <!-- Theme Switcher: Minimal, fits dropdown, now direct child and fully interactive -->
    <div class="flex items-center gap-2 mt-2 px-2" style="pointer-events:auto;" >
      <label for="theme-switch" class="text-xs text-muted-foreground">Theme:</label>
      <select
        id="theme-switch"
        class="text-xs rounded px-2 py-1 bg-[hsl(var(--muted))] text-[hsl(var(--text-1))] border border-[hsl(var(--border))] focus:outline-none focus:ring-2 focus:ring-[hsl(var(--ring))]"
        style="pointer-events:auto;"
        on:click={logThemeClick}
        on:click|stopPropagation
        on:mousedown|stopPropagation
        on:change={(e) => setTheme(e.target.value)}
        bind:value={theme}
        aria-label="Theme switcher"
      >
        <option value="default">Default</option>
        <option value="new">Amethyst/Blue</option>
      </select>
    </div>

    <DropdownMenuSeparator />
    
    <DropdownMenuItem asChild let:builder>
      <a href="/settings" class="cursor-pointer" builders={[builder]}>
        <Icon icon={Settings} class="mr-2 h-4 w-4" />
        <span>Settings</span>
      </a>
    </DropdownMenuItem>
    
    <DropdownMenuItem asChild let:builder>
      <a href="/api/auth/logout" class="cursor-pointer" builders={[builder]}>
        <Icon icon={LogOut} class="mr-2 h-4 w-4" />
        <span>Log out</span>
      </a>
    </DropdownMenuItem>
  </DropdownMenuContent>
</DropdownMenu>

<style>
  .avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: hsl(var(--muted));
  }
</style> 