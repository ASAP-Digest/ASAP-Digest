<script>
  import { page } from '$app/stores';
  import { Home, User, LogIn, Menu, Search, Bell } from 'lucide-svelte';
  import { Input } from '$lib/components/ui/input';
  
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
        <button class="p-[0.5rem] rounded-full hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors">
          <Bell size={20} />
          {#if notificationCount > 0}
            <div class="absolute top-0 right-0 bg-[hsl(var(--destructive))] text-[hsl(var(--destructive-foreground))] rounded-full w-[1.25rem] h-[1.25rem] flex items-center justify-center text-[0.75rem] font-bold">
              {notificationCount}
            </div>
          {/if}
        </button>
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
          <div class="absolute right-0 mt-[0.5rem] w-[12rem] bg-[hsl(var(--background))] dark:bg-[hsl(var(--muted))] shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1),0_4px_6px_-4px_rgba(0,0,0,0.1)] rounded-[0.375rem] z-50 border border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
            <div class="p-[0.5rem] border-b border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="font-semibold">{user.name}</div>
              <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.email}</div>
            </div>
            
            <div class="py-[0.25rem]">
              <a href="/dashboard" class="block px-[1rem] py-[0.5rem] text-[0.875rem] hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]">
                Dashboard
              </a>
              <a href="/settings" class="block px-[1rem] py-[0.5rem] text-[0.875rem] hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]">
                Settings
              </a>
              <a href="/logout" class="block px-[1rem] py-[0.5rem] text-[0.875rem] hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)]">
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
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .absolute {
    animation: fadeIn 0.2s ease-out;
  }
</style> 