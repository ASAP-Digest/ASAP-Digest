<script lang="ts">
  import { page } from '$app/stores';
  import { 
    Home, 
    Calendar, 
    Compass, 
    Clock, 
    CreditCard, 
    Settings, 
    User, 
    HelpCircle, 
    ChevronLeft, 
    ChevronRight,
    LogOut,
    ChevronDown,
    Languages,
    CreditCard as CreditCardIcon
  } from 'lucide-svelte';
  // Import individual components directly
  import Root from '$lib/components/ui/sidebar/sidebar.svelte';
  import Header from '$lib/components/ui/sidebar/sidebar-header.svelte';
  import Content from '$lib/components/ui/sidebar/sidebar-content.svelte';
  import Group from '$lib/components/ui/sidebar/sidebar-group.svelte';
  import GroupLabel from '$lib/components/ui/sidebar/sidebar-group-label.svelte';
  import GroupContent from '$lib/components/ui/sidebar/sidebar-group-content.svelte';
  import Menu from '$lib/components/ui/sidebar/sidebar-menu.svelte';
  import MenuItem from '$lib/components/ui/sidebar/sidebar-menu-item.svelte';
  import Separator from '$lib/components/ui/sidebar/sidebar-separator.svelte';
  import Footer from '$lib/components/ui/sidebar/sidebar-footer.svelte';
  import { onMount } from 'svelte';
  import { Button } from '$lib/components/ui/button';
  
  // Make path a derived state that updates when page changes
  let path = $derived($page.url.pathname);
  
  // Add state for sidebar collapsed
  let collapsed = $state(false);
  
  // Toggle sidebar collapsed state
  function toggleSidebar() {
    collapsed = !collapsed;
    console.log('[MainSidebar] Toggle state:', collapsed ? 'collapsed' : 'expanded');
    
    // Dispatch custom event for parent components
    const event = new CustomEvent('sidebarToggle', { detail: { collapsed } });
    document.dispatchEvent(event);
    
    // Add class to document body for layout adjustments
    if (collapsed) {
      document.body.classList.add('sidebar-collapsed');
    } else {
      document.body.classList.remove('sidebar-collapsed');
    }
  }
  
  // Initialize collapsed state from localStorage if available
  onMount(() => {
    // Check if there's a stored preference in localStorage
    if (typeof window !== 'undefined' && window.localStorage) {
      const storedState = localStorage.getItem('sidebar-collapsed');
      if (storedState === 'true') {
        collapsed = true;
        document.body.classList.add('sidebar-collapsed');
      }
    }
    
    // Check if body already has sidebar-collapsed class from parent
    if (typeof document !== 'undefined' && document.body.classList.contains('sidebar-collapsed') && !collapsed) {
      collapsed = true;
    }
    
    console.log('[MainSidebar] Component mounted');
    console.log('[MainSidebar] Current path:', path);
    console.log('[MainSidebar] Initial collapsed state:', collapsed);
    
    // Add DOM visibility check
    setTimeout(() => {
      const rootElement = document.querySelector('.sidebar-collapsed');
      console.log('[MainSidebar] Sidebar collapsed element exists:', !!rootElement);
      
      const sidebarRoot = document.querySelector('.h-full.border-r');
      if (sidebarRoot) {
        console.log('[MainSidebar] Root element visible in DOM');
        console.log('[MainSidebar] Classes:', sidebarRoot.className);
        
        const styles = window.getComputedStyle(sidebarRoot);
        console.log('[MainSidebar] Background:', styles.backgroundColor);
        console.log('[MainSidebar] Border:', styles.borderRight);
        console.log('[MainSidebar] Display:', styles.display);
        console.log('[MainSidebar] Width:', styles.width);
        
        // Log parent elements to check for visibility issues
        let parent = sidebarRoot.parentElement;
        console.log('[MainSidebar] Parent element:', parent);
        if (parent) {
          console.log('[MainSidebar] Parent display:', window.getComputedStyle(parent).display);
          console.log('[MainSidebar] Parent visibility:', window.getComputedStyle(parent).visibility);
          console.log('[MainSidebar] Parent width:', window.getComputedStyle(parent).width);
          
          // Check grandparent
          const grandparent = parent.parentElement;
          if (grandparent) {
            console.log('[MainSidebar] Grandparent element:', grandparent);
            console.log('[MainSidebar] Grandparent display:', window.getComputedStyle(grandparent).display);
            console.log('[MainSidebar] Grandparent width:', window.getComputedStyle(grandparent).width);
          }
        }
      } else {
        console.warn('[MainSidebar] Root element NOT found in DOM!');
      }
    }, 100);
    
    // Listen for sidebar toggle events from the layout
    /**
     * @param {CustomEvent<{collapsed: boolean}>} event - The sidebar toggle event
     */
    const handleSidebarToggle = (event: CustomEvent<{collapsed: boolean}>) => {
      // Only update if the value is different to prevent infinite loops
      if (collapsed !== event.detail.collapsed) {
        collapsed = event.detail.collapsed;
        console.log('[MainSidebar] State updated from parent:', collapsed ? 'collapsed' : 'expanded');
      }
    };
    
    document.addEventListener('sidebarToggle', handleSidebarToggle as EventListener);
    
    return () => {
      document.removeEventListener('sidebarToggle', handleSidebarToggle as EventListener);
    };
  });
  
  // Main navigation items with reactive closures for 'active' property
  const mainNavItems = [
    {
      label: "Home",
      url: "/",
      icon: Home,
      get active() { return path === '/' }
    },
    {
      label: "Today",
      url: "/today",
      icon: Calendar,
      get active() { return path.startsWith('/today') }
    },
    {
      label: "Explore",
      url: "/explore",
      icon: Compass,
      get active() { return path.startsWith('/explore') }
    },
    {
      label: "Time Machine",
      url: "/digest",
      icon: Clock,
      get active() { return path.startsWith('/digest') }
    },
    {
      label: "Plans",
      url: "/plans",
      icon: CreditCard,
      get active() { return path.startsWith('/plans') }
    }
  ];
  
  // User data mock - would come from authentication in a real app
  const user = {
    name: "John Doe",
    email: "john.doe@example.com",
    avatar: "/images/avatar.png",
    plan: "Free" // Free, Spark, Pulse, Bolt
  };
  
  // Avatar dropdown open state
  let isAvatarDropdownOpen = $state(false);
  
  // Toggle avatar dropdown
  /**
   * @param {MouseEvent} event - The mouse event
   */
  function toggleAvatarDropdown(event: MouseEvent) {
    event.stopPropagation();
    isAvatarDropdownOpen = !isAvatarDropdownOpen;
  }
  
  // Error handler for avatar image
  /**
   * @param {Event} event - The error event from the image
   */
  function handleImageError(event: Event) {
    // Type assertion for event.target as HTMLImageElement
    const imgElement = /** @type {HTMLImageElement} */ (event.target);
    if (imgElement instanceof HTMLImageElement) {
      imgElement.onerror = null;
      imgElement.src = 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E';
    }
  }
</script>

<style>
  /* All sidebar elements should transition smoothly */
  .sidebar-wrapper *,
  .sidebar-wrapper *::before,
  .sidebar-wrapper *::after {
    transition-property: width, height, margin, padding, transform, border-radius;
    transition-duration: 0.3s;
    transition-timing-function: ease-in-out;
  }

  /* Sidebar collapse/expand styling */
  :global(body.sidebar-collapsed) .sidebar-content-collapsible {
    display: none;
  }
  
  /* Logo centering when collapsed */
  :global(body.sidebar-collapsed) .sidebar-label:not(.sidebar-content-collapsible) {
    width: 100%;
    display: flex;
    justify-content: center;
    padding: 0;
    margin: 0;
    font-size: 1rem;
  }
  
  /* Fix icon visibility and centering in collapsed state */
  .sidebar-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    margin-right: 0.75rem;
    flex-shrink: 0; /* Prevent icon from shrinking */
    transition: all 0.3s ease-in-out;
  }
  
  :global(body.sidebar-collapsed) .sidebar-wrapper .sidebar-icon {
    @apply flex justify-center items-center w-full h-full;
    margin: 0;
    padding: 0.375rem;
    min-width: 1.5rem;
    min-height: 1.5rem;
    max-width: none !important;
  }
  
  /* Adjust avatar to square when collapsed with higher specificity */
  .avatar {
    @apply relative overflow-hidden border border-[hsl(var(--border))];
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 9999px; /* Full rounded */
    transition: all 0.3s ease-in-out;
  }
  
  :global(body.sidebar-collapsed) .avatar {
    width: 2rem !important;
    height: 2rem !important;
    border-radius: 0.375rem !important; /* Square with slightly rounded corners */
    transform: scale(0.9);
    transition: all 0.3s ease-in-out;
    margin: 0 auto;
  }
  
  /* Menu hover effect */
  .menu-item-hover {
    border-radius: 0.375rem;
    transition: background-color 0.2s ease-in-out;
  }
  
  .menu-item-hover:hover {
    background-color: hsl(var(--muted)/0.3);
  }
  
  .menu-item-hover.active {
    background-color: hsl(var(--primary)/0.1);
    color: hsl(var(--primary));
    font-weight: 600;
  }
  
  /* Avatar styling */
  .avatar-dropdown {
    position: absolute;
    z-index: 50;
    /* Position 7px above avatar as requested */
    bottom: calc(100% + 7px);
    right: 0;
    width: 16rem;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    @apply bg-[hsl(var(--background))] border border-[hsl(var(--border))] rounded-[0.375rem] shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1),0_4px_6px_-4px_rgba(0,0,0,0.1)] p-[0.5rem];
    @apply dark:bg-[hsl(var(--muted))] dark:border-[hsl(var(--muted-foreground)/0.5)];
  }
  
  /* When sidebar is collapsed, place dropdown next to the sidebar */
  :global(body.sidebar-collapsed) .avatar-dropdown {
    right: auto;
    left: 100%;
    bottom: auto;
    top: 0;
    margin-left: 7px;
  }
  
  .avatar-container {
    @apply relative flex items-center cursor-pointer select-none p-[0.5rem] rounded-[0.375rem];
    @apply hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200;
  }
  
  /* Avatar container is centered when collapsed */
  :global(body.sidebar-collapsed) .avatar-container {
    justify-content: center;
    width: 100%;
    padding: 0.5rem 0;
  }
  
  .avatar-text {
    @apply text-[0.875rem] font-[500] text-center text-[hsl(var(--foreground))] dark:text-[hsl(var(--foreground)/0.8)];
  }
  
  .dropdown-item {
    @apply flex items-center gap-[0.5rem] p-[0.5rem] rounded-[0.375rem] text-[0.875rem] text-[hsl(var(--foreground))] dark:text-[hsl(var(--foreground)/0.8)];
    @apply hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200;
  }
  
  .upgrade-button {
    @apply mt-[0.5rem] w-full bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] py-[0.5rem] px-[1rem] rounded-[0.375rem];
    @apply hover:bg-[hsl(var(--primary)/0.9)] transition-colors duration-200;
  }

  /* Sidebar responsive sizing */
  .sidebar-wrapper {
    width: 240px;
    min-width: 240px;
    transition: width 0.3s ease-in-out, min-width 0.3s ease-in-out, padding 0.3s ease-in-out;
  }
  
  :global(body.sidebar-collapsed) .sidebar-wrapper {
    width: 64px !important;
    min-width: 64px !important;
  }
  
  /* Sidebar label transition */
  .sidebar-label {
    transition: opacity 0.3s ease-in-out;
  }
  
  /* Keep trigger button visible with absolute positioning */
  .sidebar-toggle {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 30;
    background-color: hsl(var(--sidebar-background));
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-radius: 0.25rem;
    @apply p-[0.25rem] hover:bg-[hsl(var(--muted)/0.2)] transition-colors;
  }
  
  /* When collapsed, position button on the edge */
  :global(body.sidebar-collapsed) .sidebar-toggle {
    right: -12px;
    background-color: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: 50%;
  }
  
  /* Ensure sufficient padding in sidebar items when collapsed */
  :global(body.sidebar-collapsed) .sidebar-menu-item a {
    justify-content: center !important;
    padding: 0.5rem 0 !important;
    width: 100%;
  }
  
  /* Fix for header structure when collapsed */
  :global(body.sidebar-collapsed) .sidebar-header-content {
    justify-content: center;
    padding: 0 !important;
  }
  
  /* Fix for Recent Digests section when collapsed */
  :global(body.sidebar-collapsed) .sidebar-group-label {
    display: none;
  }
</style>

<div class="sidebar-wrapper" data-testid="sidebar" class:collapsed={collapsed}>
  <Root class="h-full border-r border-[hsl(var(--sidebar-border)/0.8)] bg-[hsl(var(--sidebar-background))] text-[hsl(var(--sidebar-foreground))] shadow-[1px_0_5px_rgba(0,0,0,0.05)]">
    <Header class="py-[1rem] px-[0.75rem] border-b border-[hsl(var(--sidebar-border)/0.8)] relative">
      <div class="flex items-center sidebar-header-content justify-between px-[0.5rem]">
        <a href="/" class="flex items-center gap-[0.75rem]">
          <!-- Removed SVG heartbeat icon, keeping only the text -->
          <span class="font-[600] text-[1.125rem] sidebar-label">⚡️ ASAP</span>
        </a>
        <!-- Collapse toggle button -->
        <button 
          type="button" 
          onclick={toggleSidebar} 
          class="sidebar-toggle"
          aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
        >
          {#if collapsed}
            <ChevronRight size={18} />
          {:else}
            <ChevronLeft size={18} />
          {/if}
        </button>
      </div>
    </Header>
    
    <Content class="px-[0.5rem] overflow-y-auto">
      <Group class="pb-[1rem] pt-[1rem]">
        <Menu class="space-y-[0.75rem]">
          {#each mainNavItems as item (item.label)}
            <MenuItem class="px-0 sidebar-menu-item">
              <a 
                href={item.url} 
                class="{item.active ? 'active' : ''} menu-item-hover flex items-center gap-[0.75rem] w-full justify-start py-[0.625rem] px-[0.75rem]"
                data-sveltekit-preload-data="hover"
              >
                <div class="sidebar-icon">
                  {#if item.icon}
                    <item.icon size={20} />
                  {/if}
                </div>
                <span class="sidebar-label sidebar-content-collapsible font-[600]">{item.label}</span>
              </a>
            </MenuItem>
          {/each}
        </Menu>
      </Group>
      
      <Separator class="my-[0.75rem] bg-[hsl(var(--sidebar-border)/0.8)] h-[1px]" />

      <Group class="pb-[1rem]">
        <GroupLabel class="sidebar-group-label px-[0.75rem] py-[0.5rem] text-[0.75rem] uppercase font-[700] text-[hsl(var(--sidebar-foreground)/0.7)] sidebar-label sidebar-content-collapsible" child={() => "Recent Digests"}>
          Recent Digests
        </GroupLabel>
        <GroupContent class="space-y-[0.75rem] sidebar-content-collapsible">
          <Menu class="space-y-[0.75rem]">
            {#each ['Tech Digest', 'Finance Update', 'Health News'] as digest}
              <MenuItem class="px-0">
                <a 
                  href={`/digest/${digest.toLowerCase().replace(/\s+/g, '-')}`} 
                  class="menu-item-hover flex items-center w-full justify-start py-[0.625rem] px-[0.75rem] text-[0.875rem]"
                  data-sveltekit-preload-data="hover"
                >
                  <span class="font-[600]">{digest}</span>
                </a>
              </MenuItem>
            {/each}
          </Menu>
        </GroupContent>
      </Group>
    </Content>
    
    <Footer class="mt-auto py-[1rem] px-[1rem] border-t border-[hsl(var(--sidebar-border)/0.8)]">
      <!-- User profile area with dropdown -->
      <div class="relative">
        <button class="avatar-container w-full text-left" onclick={toggleAvatarDropdown} aria-haspopup="true" aria-expanded={isAvatarDropdownOpen}>
          <div class="avatar">
            <img src={user.avatar} alt={user.name} onerror={handleImageError} class="w-full h-full object-cover" />
          </div>
          <div class="ml-[0.5rem] sidebar-content-collapsible">
            <div class="font-semibold">{user.name}</div>
            <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.plan}</div>
          </div>
          <ChevronRight size={16} class="ml-auto transition-transform duration-200 {isAvatarDropdownOpen ? 'rotate-90' : ''} sidebar-content-collapsible" />
        </button>
        
        {#if isAvatarDropdownOpen}
          <div class="avatar-dropdown">
            <div class="p-[0.5rem] border-b border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="font-semibold">{user.name}</div>
              <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.email}</div>
              <div class="text-[0.75rem] font-[500] mt-[0.25rem] text-[hsl(var(--primary))]">{user.plan}</div>
            </div>
            
            <div class="py-[0.25rem]">
              <a href="/billing" class="dropdown-item">
                <CreditCard size={16} />
                <span>Billing</span>
              </a>
              <a href="/settings" class="dropdown-item">
                <Settings size={16} />
                <span>Settings</span>
              </a>
              <a href="/logout" class="dropdown-item">
                <LogOut size={16} />
                <span>Sign Out</span>
              </a>
            </div>
            
            <div class="p-[0.5rem] border-t border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="text-[0.75rem] font-semibold mb-[0.5rem]">Preferences</div>
              <div class="flex justify-between items-center mb-[0.5rem]">
                <span class="text-[0.75rem]">Theme</span>
                <div class="flex space-x-[0.25rem]">
                  <button class="p-[0.25rem] rounded-[0.375rem] bg-[hsl(var(--background))] border border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]" aria-label="Light theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                  </button>
                  <button class="p-[0.25rem] rounded-[0.375rem] bg-[hsl(var(--muted))] border border-[hsl(var(--muted-foreground)/0.2)]" aria-label="Dark theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[hsl(var(--background))]"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                  </button>
                  <button class="p-[0.25rem] rounded-[0.375rem] bg-[hsl(var(--muted)/0.2)] border border-[hsl(var(--border))] dark:bg-[hsl(var(--muted)/0.2)] dark:border-[hsl(var(--muted-foreground)/0.2)]" aria-label="System theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </button>
                </div>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[0.75rem]">Language</span>
                <select class="text-[0.75rem] p-[0.25rem] rounded-[0.375rem] bg-transparent border border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
                  <option>English</option>
                  <option>Spanish</option>
                  <option>French</option>
                </select>
              </div>
            </div>
            
            {#if user.plan !== 'Bolt'}
              <button class="upgrade-button">
                Upgrade Plan
              </button>
            {/if}
          </div>
        {/if}
      </div>
    </Footer>
  </Root>
</div> 