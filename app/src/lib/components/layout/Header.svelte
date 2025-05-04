<script>
  import { page } from '$app/stores';
  import { Home, User, LogIn, Menu, Search, Bell } from '$lib/utils/lucide-compat.js';
  import { Input } from '$lib/components/ui/input';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { onMount } from 'svelte';
  
  /**
   * @typedef {Object} HeaderProps
   * @property {import('app').App.User | null} user - The user object or null if not logged in.
   */

  /** @type {HeaderProps} */
  const { user = null } = $props(); // Accept user as a prop
  
  // Avatar dropdown open state
  let isAvatarDropdownOpen = $state(false);
  
  // Toggle avatar dropdown
  /**
   * @param {MouseEvent} event - The mouse event
   */
  function toggleAvatarDropdown(event) {
    isAvatarDropdownOpen = !isAvatarDropdownOpen;
  }
  
  // Use notification count from props or state if needed
  let notificationCount = $state(3); // Example state, ideally from data
  
  /**
   * Handle image error by replacing with a placeholder
   * @param {Event} e - The error event
   */
  function handleImageError(e) {
    const target = e.target;
    if (target instanceof HTMLImageElement) {
      target.src = '/favicon.png'; // Fallback to favicon
    }
  }
</script>

<header class="bg-[hsl(var(--surface-1))] shadow-[var(--shadow-sm)]">
  <div class="container mx-auto px-4 py-3 flex justify-between items-center">
    <!-- Logo -->
    <div class="flex items-center">
      <a href="/" class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] flex items-center gap-2">
        <img src="/logo.svg" alt="ASAP Digest" width="32" height="32" loading="lazy" onerror={handleImageError} />
        <span class="text-[hsl(var(--brand))]">⚡️ ASAP</span>
      </a>
    </div>
    
    <!-- Search (center) -->
    <div class="hidden md:flex flex-1 max-w-md mx-8">
      <div class="relative w-full">
        <Icon icon={Search} class="absolute left-3 top-1/2 transform -translate-y-1/2 text-[hsl(var(--text-2))]" size={16} />
        <Input type="search" placeholder="Search..." class="pl-10 w-full" />
      </div>
    </div>
    
    <!-- Right side controls -->
    <div class="flex items-center space-x-4">
      <!-- Notifications -->
      <div class="relative">
        <a
          href="/notifications"
          class="relative p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors duration-[var(--duration-fast)]"
        >
          <Icon icon={Bell} class="w-5 h-5" />
          {#if notificationCount > 0}
            <span class="absolute top-1 right-1 w-2 h-2 bg-[hsl(var(--functional-error))] rounded-full"></span>
          {/if}
        </a>
      </div>
      
      <!-- User actions -->
      {#if user}
        <!-- Logged in: Avatar -->
        <div class="relative">
          <button
            onclick={toggleAvatarDropdown}
            class="flex items-center p-2 rounded-full hover:bg-[hsl(var(--surface-2))] transition-colors duration-[var(--duration-fast)]"
            aria-expanded={isAvatarDropdownOpen}
            aria-haspopup="true"
          >
            <img
              src={user.avatar || `/images/default-avatar.png`}
              alt={user.displayName || "User"}
              class="w-8 h-8 rounded-full"
              onerror={handleImageError}
            />
          </button>
          
          <!-- Avatar dropdown menu -->
          {#if isAvatarDropdownOpen}
            <div class="absolute right-0 mt-2 w-48 bg-[hsl(var(--surface-1))] border border-[hsl(var(--border))] rounded-[var(--radius-md)] shadow-[var(--shadow-md)] z-10">
              <div class="p-3 border-b border-[hsl(var(--border))]">
                <p class="text-[var(--font-size-base)] font-[var(--font-weight-semibold)] text-[hsl(var(--text-1))]">
                  {user.displayName || "User"}
                </p>
                <p class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
                  {user.email}
                </p>
              </div>
              
              <nav class="py-1">
                <a
                  href="/profile"
                  class="block px-4 py-2 text-[var(--font-size-base)] text-[hsl(var(--text-1))] hover:bg-[hsl(var(--surface-2))]"
                >
                  Profile
                </a>
                <a
                  href="/settings"
                  class="block px-4 py-2 text-[var(--font-size-base)] text-[hsl(var(--text-1))] hover:bg-[hsl(var(--surface-2))]"
                >
                  Settings
                </a>
                <a
                  href="/logout"
                  class="block px-4 py-2 text-[var(--font-size-base)] text-[hsl(var(--functional-error))] hover:bg-[hsl(var(--surface-2))]"
                >
                  Logout
                </a>
              </nav>
            </div>
          {/if}
        </div>
      {:else}
        <!-- Not logged in: Login button -->
        <a
          href="/login"
          class="flex items-center gap-1 px-3 py-2 text-[var(--font-size-base)] text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))] transition-colors duration-[var(--duration-fast)]"
        >
          <Icon icon={LogIn} class="w-4 h-4" />
          <span>Login</span>
        </a>
      {/if}
      
      <!-- Mobile menu toggle -->
      <button
        class="md:hidden p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors duration-[var(--duration-fast)]"
        aria-label="Toggle menu"
      >
        <Icon icon={Menu} class="w-6 h-6" />
      </button>
    </div>
  </div>
</header>

<style>
  /* Add animations for dropdowns */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-0.625rem); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .absolute {
    animation: fadeIn 0.2s ease-out;
  }

  /* Avatar dropdown styles */
  .avatar-dropdown {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 0.5rem;
    width: 12rem;
    background-color: hsl(var(--surface-1));
    border-radius: var(--radius-md);
    border-width: 1px;
    border-style: solid;
    border-color: hsl(var(--border));
    box-shadow: var(--shadow-md);
    max-height: calc(100vh - 3.75rem);
    overflow-y: auto;
  }

  .avatar-dropdown-header {
    padding: 0.5rem;
    border-bottom-width: 1px;
    border-bottom-style: solid;
    border-bottom-color: hsl(var(--border));
  }

  .avatar-dropdown-link {
    display: block;
    padding: 0.5rem 1rem;
    font-size: var(--font-size-sm);
    transition: background-color var(--duration-fast) var(--ease-out);
  }

  .avatar-dropdown-link:hover {
    background-color: hsl(var(--surface-2));
  }
</style> 