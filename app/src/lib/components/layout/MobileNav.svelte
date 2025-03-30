<script>
  import { Menu, X, Home, User, Calendar, Bell, Settings } from '$lib/utils/lucide-icons.js';
  import { page } from '$app/stores';
  import { Home, User, Calendar, Bell, Settings } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  /** @type {boolean} */
  let isOpen = $state(false);
  
  function toggleMenu() {
    isOpen = !isOpen;
  }
  
  function closeMenu() {
    isOpen = false;
  }
</script>

<div class="lg:hidden">
  <!-- Mobile menu button -->
  <button 
    type="button"
    aria-label={isOpen ? "Close menu" : "Open menu"}
    class="fixed bottom-[1rem] right-[1rem] z-50 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-full p-[0.75rem] shadow-lg"
    onclick={toggleMenu}
  >
    {#if isOpen}
      <X class="w-[1.5rem] h-[1.5rem]" />
    {:else}
      <Menu class="w-[1.5rem] h-[1.5rem]" />
    {/if}
  </button>
  
  <!-- Mobile menu overlay -->
  {#if isOpen}
    <div 
      class="fixed inset-0 bg-[hsl(var(--background)/0.8)] backdrop-blur-sm z-40"
      onclick={closeMenu}
      onkeydown={(e) => e.key === 'Escape' && closeMenu()}
      role="dialog"
      aria-modal="true"
      aria-label="Mobile navigation menu"
      tabindex="-1"
    ></div>
    
    <!-- Mobile menu panel -->
    <div class="fixed inset-y-0 right-0 z-50 w-full max-w-xs bg-[hsl(var(--background))] p-[1.5rem] shadow-xl">
      <div class="flex items-center justify-between mb-[2rem]">
        <h2 class="text-[1.25rem] font-semibold">ASAP Digest</h2>
        <button type="button" aria-label="Close menu" onclick={closeMenu}>
          <X class="w-[1.5rem] h-[1.5rem]" />
        </button>
      </div>
      
      <nav class="space-y-[1.5rem]">
        <a 
          href="/" 
          class="flex items-center space-x-[1rem] px-[0.5rem] py-[0.5rem] rounded-md hover:bg-[hsl(var(--muted))] {$page.url.pathname === '/' ? 'bg-[hsl(var(--muted))] font-medium' : ''}"
          onclick={closeMenu}
        >
          <Home class="w-[1.25rem] h-[1.25rem]" />
          <span>Home</span>
        </a>
        
        <a 
          href="/profile" 
          class="flex items-center space-x-[1rem] px-[0.5rem] py-[0.5rem] rounded-md hover:bg-[hsl(var(--muted))] {$page.url.pathname === '/profile' ? 'bg-[hsl(var(--muted))] font-medium' : ''}"
          onclick={closeMenu}
        >
          <User class="w-[1.25rem] h-[1.25rem]" />
          <span>Profile</span>
        </a>
        
        <a 
          href="/calendar" 
          class="flex items-center space-x-[1rem] px-[0.5rem] py-[0.5rem] rounded-md hover:bg-[hsl(var(--muted))] {$page.url.pathname === '/calendar' ? 'bg-[hsl(var(--muted))] font-medium' : ''}"
          onclick={closeMenu}
        >
          <Calendar class="w-[1.25rem] h-[1.25rem]" />
          <span>History</span>
        </a>
        
        <a 
          href="/notifications" 
          class="flex items-center space-x-[1rem] px-[0.5rem] py-[0.5rem] rounded-md hover:bg-[hsl(var(--muted))] {$page.url.pathname === '/notifications' ? 'bg-[hsl(var(--muted))] font-medium' : ''}"
          onclick={closeMenu}
        >
          <Bell class="w-[1.25rem] h-[1.25rem]" />
          <span>Notifications</span>
        </a>
        
        <a 
          href="/settings" 
          class="flex items-center space-x-[1rem] px-[0.5rem] py-[0.5rem] rounded-md hover:bg-[hsl(var(--muted))] {$page.url.pathname === '/settings' ? 'bg-[hsl(var(--muted))] font-medium' : ''}"
          onclick={closeMenu}
        >
          <Settings class="w-[1.25rem] h-[1.25rem]" />
          <span>Settings</span>
        </a>
      </nav>
    </div>
  {/if}
</div>

<nav class="fixed bottom-0 left-0 right-0 z-50 bg-[hsl(var(--background))] border-t border-[hsl(var(--border))]">
  <ul class="flex justify-around items-center p-2">
    <li>
      <a
        href="/"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
        class:text-[hsl(var(--foreground))]={$page.url.pathname === '/'}
      >
        <Icon icon={Home} class="w-[1.25rem] h-[1.25rem]" />
        <span class="text-xs">Home</span>
      </a>
    </li>
    <li>
      <a
        href="/profile"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
        class:text-[hsl(var(--foreground))]={$page.url.pathname === '/profile'}
      >
        <Icon icon={User} class="w-[1.25rem] h-[1.25rem]" />
        <span class="text-xs">Profile</span>
      </a>
    </li>
    <li>
      <a
        href="/today"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
        class:text-[hsl(var(--foreground))]={$page.url.pathname === '/today'}
      >
        <Icon icon={Calendar} class="w-[1.25rem] h-[1.25rem]" />
        <span class="text-xs">Today</span>
      </a>
    </li>
    <li>
      <a
        href="/notifications"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
        class:text-[hsl(var(--foreground))]={$page.url.pathname === '/notifications'}
      >
        <Icon icon={Bell} class="w-[1.25rem] h-[1.25rem]" />
        <span class="text-xs">Alerts</span>
      </a>
    </li>
    <li>
      <a
        href="/settings"
        class="flex flex-col items-center gap-1 p-2 text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
        class:text-[hsl(var(--foreground))]={$page.url.pathname === '/settings'}
      >
        <Icon icon={Settings} class="w-[1.25rem] h-[1.25rem]" />
        <span class="text-xs">Settings</span>
      </a>
    </li>
  </ul>
</nav> 