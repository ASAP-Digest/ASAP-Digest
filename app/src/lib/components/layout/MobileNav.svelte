<script>
  import { Menu, X, Home, User, Calendar, Bell, Settings } from 'lucide-svelte';
  import { page } from '$app/stores';
  
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
    class="fixed bottom-4 right-4 z-50 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-full p-3 shadow-lg"
    onclick={toggleMenu}
  >
    {#if isOpen}
      <X class="w-6 h-6" />
    {:else}
      <Menu class="w-6 h-6" />
    {/if}
  </button>
  
  <!-- Mobile menu overlay -->
  {#if isOpen}
    <div 
      class="fixed inset-0 bg-background/80 backdrop-blur-sm z-40"
      onclick={closeMenu}
      onkeydown={(e) => e.key === 'Escape' && closeMenu()}
      role="dialog"
      aria-modal="true"
      aria-label="Mobile navigation menu"
      tabindex="-1"
    ></div>
    
    <!-- Mobile menu panel -->
    <div class="fixed inset-y-0 right-0 z-50 w-full max-w-xs bg-background p-6 shadow-xl">
      <div class="flex items-center justify-between mb-8">
        <h2 class="text-xl font-semibold">ASAP Digest</h2>
        <button type="button" aria-label="Close menu" onclick={closeMenu}>
          <X class="w-6 h-6" />
        </button>
      </div>
      
      <nav class="space-y-6">
        <a 
          href="/" 
          class="flex items-center space-x-4 px-2 py-2 rounded-md hover:bg-muted {$page.url.pathname === '/' ? 'bg-muted font-medium' : ''}"
          onclick={closeMenu}
        >
          <Home class="w-5 h-5" />
          <span>Home</span>
        </a>
        
        <a 
          href="/profile" 
          class="flex items-center space-x-4 px-2 py-2 rounded-md hover:bg-muted {$page.url.pathname === '/profile' ? 'bg-muted font-medium' : ''}"
          onclick={closeMenu}
        >
          <User class="w-5 h-5" />
          <span>Profile</span>
        </a>
        
        <a 
          href="/calendar" 
          class="flex items-center space-x-4 px-2 py-2 rounded-md hover:bg-muted {$page.url.pathname === '/calendar' ? 'bg-muted font-medium' : ''}"
          onclick={closeMenu}
        >
          <Calendar class="w-5 h-5" />
          <span>History</span>
        </a>
        
        <a 
          href="/notifications" 
          class="flex items-center space-x-4 px-2 py-2 rounded-md hover:bg-muted {$page.url.pathname === '/notifications' ? 'bg-muted font-medium' : ''}"
          onclick={closeMenu}
        >
          <Bell class="w-5 h-5" />
          <span>Notifications</span>
        </a>
        
        <a 
          href="/settings" 
          class="flex items-center space-x-4 px-2 py-2 rounded-md hover:bg-muted {$page.url.pathname === '/settings' ? 'bg-muted font-medium' : ''}"
          onclick={closeMenu}
        >
          <Settings class="w-5 h-5" />
          <span>Settings</span>
        </a>
      </nav>
    </div>
  {/if}
</div> 