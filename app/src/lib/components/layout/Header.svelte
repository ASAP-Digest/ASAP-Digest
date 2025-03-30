<script lang="ts">
  import { page } from '$app/stores';
  import { Home, User, LogIn, Menu, Search } from '$lib/utils/lucide-icons.js';
  import { Input } from '$lib/components/ui/input';
  import { onMount } from 'svelte';
  import { Bell } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  // Avatar dropdown open state
  let isAvatarDropdownOpen = $state(false);
  
  // Toggle avatar dropdown
  /**
   * @param {MouseEvent} event - The mouse event
   */
  function toggleAvatarDropdown(event) {
    isAvatarDropdownOpen = !isAvatarDropdownOpen;
  }
  
  // Mock user data - would come from authentication in real app
  const user = {
    name: "John Doe",
    email: "john.doe@example.com",
    avatar: "/images/avatar.png",
    plan: "Free" // Free, Spark, Pulse, Bolt
  };
  
  // Mock notification count
  const notificationCount = 3;
  
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

<header class="bg-[hsl(var(--background))] dark:bg-[hsl(var(--muted))] shadow-[0_1px_3px_0_rgba(0,0,0,0.1)]">
  <div class="container mx-auto px-[1rem] py-[0.75rem] flex justify-between items-center">
    <!-- Logo -->
    <div class="flex items-center">
      <a href="/" class="text-[1.25rem] font-bold flex items-center gap-[0.5rem]">
        <img src="/logo.svg" alt="ASAP Digest" width="32" height="32" loading="lazy" onerror={handleImageError} />
        <span class="text-[hsl(var(--primary))]">⚡️ ASAP</span>
      </a>
    </div>
    
    <!-- Search (center) -->
    <div class="hidden md:flex flex-1 max-w-md mx-[2rem]">
      <div class="relative w-full">
        <Search class="absolute left-[0.75rem] top-1/2 transform -translate-y-1/2 text-[hsl(var(--muted-foreground))]" size={16} />
        <Input type="search" placeholder="Search..." class="pl-[2.5rem] w-full" />
      </div>
    </div>
    
    <!-- Right side controls -->
    <div class="flex items-center space-x-[1rem]">
      <!-- Notifications -->
      <div class="relative">
        <a
          href="/notifications"
          class="relative p-2 text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
        >
          <Icon icon={Bell} class="w-5 h-5" />
          {#if notificationCount > 0}
            <span class="absolute top-1 right-1 w-2 h-2 bg-[hsl(var(--destructive))] rounded-full"></span>
          {/if}
        </a>
      </div>
      
      <!-- Avatar with dropdown -->
      <div class="relative">
        <button 
          class="flex items-center space-x-[0.5rem] rounded-full hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] p-[0.25rem] transition-colors"
          onclick={toggleAvatarDropdown}
          aria-haspopup="true"
          aria-expanded={isAvatarDropdownOpen}
        >
          <div class="w-[2rem] h-[2rem] rounded-full bg-[hsl(var(--muted)/0.2)] overflow-hidden">
            <img 
              src={user.avatar} 
              alt={user.name} 
              class="w-full h-full object-cover"
              onerror={handleImageError}
            />
          </div>
        </button>
        
        {#if isAvatarDropdownOpen}
          <div class="avatar-dropdown">
            <div class="avatar-dropdown-header">
              <div class="font-semibold">{user.name}</div>
              <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.email}</div>
            </div>
            
            <div class="py-[0.25rem]">
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
      <button class="md:hidden text-[hsl(var(--foreground)/0.7)] dark:text-[hsl(var(--foreground)/0.8)]">
        <Menu size={24} />
      </button>
    </div>
  </div>
  
  <!-- Mobile search (only visible on small screens) -->
  <div class="md:hidden px-[1rem] pb-[0.75rem]">
    <div class="relative w-full">
      <Search class="absolute left-[0.75rem] top-1/2 transform -translate-y-1/2 text-[hsl(var(--muted-foreground))]" size={16} />
      <Input type="search" placeholder="Search..." class="pl-[2.5rem] w-full" />
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
    background-color: hsl(var(--background));
    border-radius: 0.375rem;
    border-width: 1px;
    border-style: solid;
    border-color: hsl(var(--border));
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
    max-height: calc(100vh - 3.75rem);
    overflow-y: auto;
  }

  /* Dark mode styles */
  :global(.dark) .avatar-dropdown {
    background-color: hsl(var(--muted));
    border-color: hsl(var(--muted-foreground)/0.2);
  }

  .avatar-dropdown-header {
    padding: 0.5rem;
    border-bottom-width: 1px;
    border-bottom-style: solid;
    border-bottom-color: hsl(var(--border));
  }

  :global(.dark) .avatar-dropdown-header {
    border-bottom-color: hsl(var(--muted-foreground)/0.2);
  }

  .avatar-dropdown-link {
    display: block;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: background-color 0.2s;
  }

  .avatar-dropdown-link:hover {
    background-color: hsl(var(--muted)/0.1);
  }

  :global(.dark) .avatar-dropdown-link:hover {
    background-color: hsl(var(--muted)/0.2);
  }
</style> 