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
  }
  
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

  onMount(() => {
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
</script>

<style>
  /* Enhance transitions for collapse state */
  :global(.sidebar-icon) {
    @apply transition-all duration-200;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    @apply text-[hsl(var(--primary))] flex items-center justify-center;
    @apply min-w-[24px] min-h-[24px];
  }
  
  :global(.sidebar-label) {
    @apply transition-opacity duration-200 whitespace-nowrap overflow-hidden;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    @apply text-[hsl(var(--foreground))];
  }
  
  /* Balance icon sizing when collapsed */
  :global(.sidebar-collapsed .sidebar-icon) {
    @apply mx-auto h-[1.25rem] w-[1.25rem] text-[hsl(var(--primary))];
  }
  
  /* Fix alignment in menu items */
  :global(.sidebar-menu-item a) {
    @apply flex items-center gap-[0.75rem];
  }
  
  /* Enhanced visibility for active items in collapsed mode */
  :global(.sidebar-collapsed .sidebar-menu-item a.active .sidebar-icon) {
    @apply text-[hsl(var(--primary))] font-[700];
  }
  
  /* Add hover effects to menu items */
  :global(.menu-item-hover) {
    @apply rounded-[0.375rem] transition-colors duration-200;
    @apply hover:bg-[hsl(var(--primary)/0.1)] hover:text-[hsl(var(--primary))];
    @apply active:bg-[hsl(var(--primary)/0.2)];
  }
  
  /* Style active menu items */
  :global(.sidebar-menu-item a.active) {
    @apply bg-[hsl(var(--primary)/0.15)] font-[600];
    @apply text-[hsl(var(--primary))];
  }
  
  /* Collapse toggle button */
  .sidebar-toggle {
    @apply p-[0.5rem] rounded-[0.375rem] transition-colors;
    @apply hover:bg-[hsl(var(--primary)/0.15)] hover:text-[hsl(var(--primary))];
    @apply focus:outline-none border border-[hsl(var(--border)/0.5)];
    @apply text-[hsl(var(--foreground))];
    @apply shadow-[0_1px_2px_0_rgba(0,0,0,0.05)];
  }
  
  /* Hide content in collapsed state */
  :global(.sidebar-collapsed .sidebar-content-collapsible) {
    @apply hidden;
  }
  
  /* Avatar dropdown */
  .avatar-dropdown {
    @apply fixed z-50 bg-[hsl(var(--background))] border border-[hsl(var(--border))] rounded-[0.375rem] shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1),0_4px_6px_-4px_rgba(0,0,0,0.1)] p-[0.5rem] w-[16rem];
    @apply dark:bg-[hsl(var(--muted))] dark:border-[hsl(var(--muted-foreground)/0.5)];
  }
  
  .avatar-container {
    @apply relative flex items-center cursor-pointer select-none p-[0.5rem] rounded-[0.375rem];
    @apply hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200;
  }
  
  .avatar {
    @apply relative w-[2.5rem] h-[2.5rem] rounded-full overflow-hidden border border-[hsl(var(--border))];
  }
  
  .avatar-text {
    @apply text-[0.875rem] font-medium text-center text-[hsl(var(--foreground))] dark:text-[hsl(var(--foreground)/0.8)];
  }
  
  .dropdown-item {
    @apply flex items-center gap-[0.5rem] p-[0.5rem] rounded-[0.375rem] text-[0.875rem] text-[hsl(var(--foreground))] dark:text-[hsl(var(--foreground)/0.8)];
    @apply hover:bg-[hsl(var(--muted)/0.1)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200;
  }
  
  .upgrade-button {
    @apply mt-[0.5rem] w-full bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] py-[0.5rem] px-[1rem] rounded-[0.375rem];
    @apply hover:bg-[hsl(var(--primary)/0.9)] transition-colors duration-200;
  }

  .sidebar {
    @apply fixed top-0 left-0 h-screen w-[16rem] bg-[hsl(var(--background))] border-r border-[hsl(var(--border))] z-50 transition-all duration-300 ease-in-out flex flex-col;
  }

  .sidebar.collapsed {
    @apply w-[5rem];
  }

  .menu-item {
    @apply flex items-center gap-[0.5rem] p-[0.5rem] rounded-[0.375rem] text-[0.875rem] text-[hsl(var(--foreground))] dark:text-[hsl(var(--foreground)/0.8)];
    @apply hover:bg-[hsl(var(--muted)/0.5)] dark:hover:bg-[hsl(var(--muted)/0.2)] transition-colors duration-200;
  }
</style>

<div class={collapsed ? 'sidebar-collapsed' : ''} data-testid="sidebar" style="width: 240px; min-width: 240px;">
  <Root class="h-full border-r border-[hsl(var(--sidebar-border)/0.8)] bg-[hsl(var(--sidebar-background))] text-[hsl(var(--sidebar-foreground))] shadow-[1px_0_5px_rgba(0,0,0,0.05)]">
    <Header class="py-[1rem] px-[0.75rem] border-b border-[hsl(var(--sidebar-border)/0.8)]">
      <div class="flex items-center justify-between px-[0.5rem]">
        <a href="/" class="flex items-center gap-[0.75rem]">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[hsl(var(--primary))]"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          <span class="font-[600] text-[1.125rem] sidebar-label sidebar-content-collapsible">⚡️ ASAP</span>
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
        <GroupLabel class="px-[0.75rem] py-[0.5rem] text-[0.75rem] uppercase font-[700] text-[hsl(var(--sidebar-foreground)/0.7)] sidebar-label sidebar-content-collapsible" child={() => "Recent Digests"}>
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
            <img src={user.avatar} alt={user.name} onerror={handleImageError} />
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
              <div class="text-[0.75rem] font-medium mt-[0.25rem] text-[hsl(var(--primary))]">{user.plan}</div>
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