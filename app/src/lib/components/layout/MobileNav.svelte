<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { fade, fly } from 'svelte/transition';
  import { page } from '$app/stores';
  import { X, Menu, Home, User, Calendar, Bell, Settings, Sparkle } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  /**
   * @typedef {Object} MobileNavProps
   * @property {boolean} [showBottomNav=true] - Whether to show the bottom navigation
   */

  /** @type {MobileNavProps} */
  const { showBottomNav = true } = $props();
  
  // Current menu state
  let isOpen = $state(false);
  
  // Toggle menu
  function toggleMenu() {
    isOpen = !isOpen;
    
    // Toggle body class to prevent scrolling when menu is open
    if (isOpen) {
      document.body.classList.add('overflow-hidden');
    } else {
      document.body.classList.remove('overflow-hidden');
    }
  }
  
  // Event dispatcher for external use
  const dispatch = createEventDispatcher();
  
  // Close menu when user navigates
  $effect(() => {
    const currentPath = $page.url.pathname;
    if (isOpen) {
      toggleMenu();
    }
  });
</script>

<!-- Mobile hamburger button -->
<button 
  on:click={toggleMenu}
  class="fixed bottom-4 right-4 z-50 bg-[hsl(var(--brand))] text-[hsl(var(--brand-fg))] rounded-full p-3 shadow-[var(--shadow-lg)]"
  aria-label={isOpen ? 'Close Menu' : 'Open Menu'}
>
  <Icon icon={isOpen ? X : Menu} size={20} />
</button>

<!-- Mobile menu overlay (backdrop) -->
{#if isOpen}
  <div
    transition:fade={{ duration: 200 }}
    class="fixed inset-0 bg-[hsl(var(--canvas-base)/0.8)] backdrop-blur-sm z-40"
    on:click={toggleMenu}
    role="presentation"
  ></div>
{/if}

<!-- Slide-in menu -->
{#if isOpen}
  <div
    transition:fly={{ x: '100%', duration: 300 }}
    class="fixed inset-y-0 right-0 z-50 w-full max-w-xs bg-[hsl(var(--surface-1))] p-6 shadow-[var(--shadow-lg)]"
  >
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)]">ASAP Digest</h2>
      <button on:click={toggleMenu} class="p-2">
        <Icon icon={X} size={24} />
      </button>
    </div>
    
    <nav class="space-y-1">
      <a
        href="/"
        class="flex items-center space-x-4 px-2 py-2 rounded-[var(--radius-md)] hover:bg-[hsl(var(--surface-2))] {$page.url.pathname === '/' ? 'bg-[hsl(var(--surface-2))] font-[var(--font-weight-semibold)]' : ''}"
      >
        <Icon icon={Home} size={20} />
        <span>Home</span>
      </a>
      
      <a
        href="/profile"
        class="flex items-center space-x-4 px-2 py-2 rounded-[var(--radius-md)] hover:bg-[hsl(var(--surface-2))] {$page.url.pathname === '/profile' ? 'bg-[hsl(var(--surface-2))] font-[var(--font-weight-semibold)]' : ''}"
      >
        <Icon icon={User} size={20} />
        <span>Profile</span>
      </a>
      
      <a
        href="/today"
        class="flex items-center space-x-4 px-2 py-2 rounded-[var(--radius-md)] hover:bg-[hsl(var(--surface-2))] {$page.url.pathname === '/today' ? 'bg-[hsl(var(--surface-2))] font-[var(--font-weight-semibold)]' : ''}"
      >
        <Icon icon={Sparkle} size={20} />
        <span>Today</span>
      </a>
      
      <a
        href="/notifications"
        class="flex items-center space-x-4 px-2 py-2 rounded-[var(--radius-md)] hover:bg-[hsl(var(--surface-2))] {$page.url.pathname === '/notifications' ? 'bg-[hsl(var(--surface-2))] font-[var(--font-weight-semibold)]' : ''}"
      >
        <Icon icon={Bell} size={20} />
        <span>Notifications</span>
      </a>
      
      <a
        href="/settings"
        class="flex items-center space-x-4 px-2 py-2 rounded-[var(--radius-md)] hover:bg-[hsl(var(--surface-2))] {$page.url.pathname === '/settings' ? 'bg-[hsl(var(--surface-2))] font-[var(--font-weight-semibold)]' : ''}"
      >
        <Icon icon={Settings} size={20} />
        <span>Settings</span>
      </a>
    </nav>
  </div>
{/if}

<!-- Bottom navigation bar -->
{#if showBottomNav}
  <nav class="fixed bottom-0 left-0 right-0 z-50 bg-[hsl(var(--surface-1))] border-t border-[hsl(var(--border))]">
    <div class="grid grid-cols-5 h-16">
      <!-- Home -->
      <a
        href="/"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors"
        class:text-[hsl(var(--text-1))]={$page.url.pathname === '/'}
      >
        <Icon icon={Home} size={20} />
        <span class="text-[var(--font-size-xs)]">Home</span>
      </a>
      
      <!-- Profile -->
      <a
        href="/profile"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors"
        class:text-[hsl(var(--text-1))]={$page.url.pathname === '/profile'}
      >
        <Icon icon={User} size={20} />
        <span class="text-[var(--font-size-xs)]">Profile</span>
      </a>
      
      <!-- Today -->
      <a
        href="/today"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors"
        class:text-[hsl(var(--text-1))]={$page.url.pathname === '/today'}
      >
        <Icon icon={Sparkle} size={20} />
        <span class="text-[var(--font-size-xs)]">Today</span>
      </a>
      
      <!-- Notifications -->
      <a
        href="/notifications"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors"
        class:text-[hsl(var(--text-1))]={$page.url.pathname === '/notifications'}
      >
        <Icon icon={Bell} size={20} />
        <span class="text-[var(--font-size-xs)]">Alerts</span>
      </a>
      
      <!-- Settings -->
      <a
        href="/settings"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors"
        class:text-[hsl(var(--text-1))]={$page.url.pathname === '/settings'}
      >
        <Icon icon={Settings} size={20} />
        <span class="text-[var(--font-size-xs)]">Settings</span>
      </a>
    </div>
  </nav>
{/if} 