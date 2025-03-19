<script>
  import { page } from '$app/stores';
  import { Home, Search, Calendar, User, Bell, Menu, X } from 'lucide-svelte';
  import { slide } from 'svelte/transition';
  import { LAYOUT_SPACING } from '$lib/styles/spacing.js';
  
  let mobileMenuOpen = $state(false);
  
  // Toggle mobile menu
  function toggleMobileMenu() {
    mobileMenuOpen = !mobileMenuOpen;
  }
  
  // Close mobile menu when a link is clicked
  function closeMenu() {
    mobileMenuOpen = false;
  }
  
  // Navigation items with their respective icons and paths
  const navItems = [
    { name: 'Home', icon: Home, path: '/' },
    { name: 'Explore', icon: Search, path: '/explore' },
    { name: 'Daily Digest', icon: Calendar, path: '/today' },
    { name: 'Notifications', icon: Bell, path: '/notifications' },
    { name: 'Profile', icon: User, path: '/profile' }
  ];
  
  // Check if a nav item is active
  /**
   * @param {string} path
   * @returns {boolean}
   */
  function isActive(path) {
    if (path === '/') {
      return $page.url.pathname === '/';
    }
    return $page.url.pathname.startsWith(path);
  }
</script>

<!-- Desktop Navigation - Top bar -->
<nav class="container mx-auto {LAYOUT_SPACING.container} py-3 md:py-4">
  <div class="flex justify-between items-center">
    <!-- Logo -->
    <a href="/" class="text-xl font-bold">
      <span class="text-[hsl(var(--primary))]">⚡️ ASAP</span>Digest
    </a>
    
    <!-- Desktop navigation -->
    <div class="hidden md:flex items-center space-x-6">
      <a href="/" class="text-[hsl(var(--foreground))] hover:text-[hsl(var(--primary))] transition-colors">Home</a>
      <a href="/explore" class="text-[hsl(var(--foreground))] hover:text-[hsl(var(--primary))] transition-colors">Explore</a>
      <a href="/today" class="text-[hsl(var(--foreground))] hover:text-[hsl(var(--primary))] transition-colors">Today</a>
      <a href="/notifications" class="text-[hsl(var(--foreground))] hover:text-[hsl(var(--primary))] transition-colors">Notifications</a>
      <a href="/profile" class="text-[hsl(var(--foreground))] hover:text-[hsl(var(--primary))] transition-colors">Profile</a>
    </div>
    
    <!-- Search button -->
    <div class="hidden md:block">
      <button 
        class="p-2 rounded-full bg-[hsl(var(--muted))] hover:bg-[hsl(var(--muted-foreground/10))] transition-colors"
        aria-label="Search"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile Navigation - Top header bar with menu button -->
<div class="lg:hidden fixed top-0 left-0 right-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 z-20">
  <div class="container mx-auto px-4 py-3 flex justify-between items-center">
    <a href="/" class="text-xl font-bold flex items-center gap-2">
      <span class="text-[hsl(var(--primary))]">ASAP</span>Digest
    </a>
    
    <button onclick={toggleMobileMenu} class="text-gray-600 dark:text-gray-300">
      {#if mobileMenuOpen}
        <X size={24} />
      {:else}
        <Menu size={24} />
      {/if}
    </button>
  </div>
</div>

<!-- Mobile menu overlay -->
{#if mobileMenuOpen}
  <div 
    transition:slide={{ duration: 300 }}
    class="fixed inset-0 bg-white dark:bg-gray-800 z-10 pt-16 lg:hidden"
  >
    <div class="container mx-auto px-4 py-6">
      <ul class="space-y-6">
        {#each navItems as item}
          <li>
            <a 
              href={item.path} 
              data-sveltekit-preload-data="hover"
              onclick={closeMenu}
              class="flex items-center gap-4 text-lg {isActive(item.path) ? 'text-[hsl(var(--primary))]' : 'text-gray-600 dark:text-gray-300'}"
            >
              <item.icon size={24} />
              <span>{item.name}</span>
            </a>
          </li>
        {/each}
      </ul>
    </div>
  </div>
{/if}

<!-- Mobile Navigation - Bottom bar -->
<!-- 
DISABLED: This mobile navigation has been replaced by MainSidebar.svelte to prevent overlapping issues
<div class="mobile-navigation lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-20">
  <div class="grid grid-cols-5 h-16">
    {#each navItems as item}
      <a 
        href={item.path} 
        data-sveltekit-preload-data="hover"
        class="flex flex-col items-center justify-center {isActive(item.path) ? 'text-[hsl(var(--primary))]' : 'text-gray-600 dark:text-gray-300'}"
      >
        <item.icon size={20} />
        <span class="text-xs mt-1">{item.name}</span>
      </a>
    {/each}
  </div>
</div>
-->

<!-- Spacer for fixed navigation bars -->
<div class="h-14 lg:h-14"></div>
<!-- Bottom spacer for mobile - Disabled as it's no longer needed with the sidebar -->
<!-- <div class="h-16 lg:h-0"></div> --> 