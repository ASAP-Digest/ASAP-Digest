<script>
  import { page } from '$app/stores';
  import { Home, User, LogIn, Menu, Search } from '$lib/utils/lucide-compat.js';
  import { Input } from '$lib/components/ui/input';
  import { onMount } from 'svelte';
  import { Bell } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
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
  
  // Error handler for images
  /**
   * @param {Event} event - The error event from the image
   */
  function handleImageError(event) {
    // Type assertion for event.target as HTMLImageElement
    const imgElement = /** @type {HTMLImageElement} */ (event.target);
    if (imgElement instanceof HTMLImageElement) {
      imgElement.onerror = null;
      imgElement.src = 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E';
    }
  }

  // Function to check viewport position and adjust dropdown
  function adjustDropdownPosition() {
    if (isAvatarDropdownOpen) {
      setTimeout(() => {
        const dropdown = document.querySelector('.avatar-dropdown');
        if (dropdown && dropdown instanceof HTMLElement) {
          const rect = dropdown.getBoundingClientRect();
          const viewportHeight = window.innerHeight;
          const viewportWidth = window.innerWidth;
          
          // Check if dropdown is off the right edge
          if (rect.right > viewportWidth) {
            dropdown.style.right = '0px';
            dropdown.style.left = 'auto';
          }
          
          // Check if dropdown is off the bottom edge
          if (rect.bottom > viewportHeight) {
            dropdown.style.bottom = '100%';
            dropdown.style.top = 'auto';
            dropdown.style.marginBottom = '5px';
          }
        }
      }, 0);
    }
  }
  
  // Add listener for window resize
  let resizeObserver;
  onMount(() => {
    adjustDropdownPosition();
    window.addEventListener('resize', adjustDropdownPosition);
    return () => {
      window.removeEventListener('resize', adjustDropdownPosition);
    };
  });
  
  // Call adjust function when dropdown state changes
  $effect(() => {
    if (isAvatarDropdownOpen) {
      adjustDropdownPosition();
    }
  });
</script>

<header class="bg-[hsl(var(--canvas-base))] shadow-[var(--shadow-sm)]">
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
          class="relative p-2 text-[hsl(var(--text-2))] hover:text-[hsl(var(--text-1))] transition-colors"
        >
          <Icon icon={Bell} class="w-5 h-5" />
          {#if notificationCount > 0}
            <span class="absolute top-1 right-1 w-2 h-2 bg-[hsl(var(--functional-error))] rounded-full"></span>
          {/if}
        </a>
      </div>
      
      <!-- Avatar with dropdown -->
      <div class="relative">
        <button 
          class="flex items-center space-x-2 rounded-full hover:bg-[hsl(var(--surface-2))] p-1 transition-colors"
          onclick={toggleAvatarDropdown}
          aria-haspopup="true"
          aria-expanded={isAvatarDropdownOpen}
        >
          <div class="w-8 h-8 rounded-full bg-[hsl(var(--surface-2))] overflow-hidden">
            {#if user}
              <img 
                src={user.avatarUrl}
                alt={user.displayName}
                class="w-full h-full object-cover"
                onerror={handleImageError}
              />
            {:else}
              <Icon icon={User} class="w-full h-full p-1 text-[hsl(var(--text-2))]" />
            {/if}
          </div>
        </button>
        
        {#if isAvatarDropdownOpen && user}
          <div class="avatar-dropdown">
            <div class="avatar-dropdown-header">
              <div class="font-[var(--font-weight-semibold)]">{user.displayName}</div>
              <div class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">{user.email}</div>
            </div>
            
            <div class="py-1">
              <a href="/dashboard" class="avatar-dropdown-link">
                Dashboard
              </a>
              <a href="/settings" class="avatar-dropdown-link">
                Settings
              </a>
              <a href="/logout" class="avatar-dropdown-link">
                Logout
              </a>
            </div>
          </div>
        {/if}
      </div>
      
      <!-- Mobile menu button -->
      <button class="md:hidden text-[hsl(var(--text-2))]">
        <Icon icon={Menu} size={24} />
      </button>
    </div>
  </div>
  
  <!-- Mobile search (only visible on small screens) -->
  <div class="md:hidden px-4 pb-3">
    <div class="relative w-full">
      <Icon icon={Search} class="absolute left-3 top-1/2 transform -translate-y-1/2 text-[hsl(var(--text-2))]" size={16} />
      <Input type="search" placeholder="Search..." class="pl-10 w-full" />
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