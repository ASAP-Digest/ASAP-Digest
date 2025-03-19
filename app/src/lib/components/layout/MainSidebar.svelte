<script>
  import { page } from '$app/stores';
  import { Home, FileText, Headphones, Settings, User } from 'lucide-svelte';
  // Import individual components directly
  import Root from '$lib/components/ui/sidebar/sidebar.svelte';
  import Header from '$lib/components/ui/sidebar/sidebar-header.svelte';
  import Content from '$lib/components/ui/sidebar/sidebar-content.svelte';
  import Group from '$lib/components/ui/sidebar/sidebar-group.svelte';
  import GroupLabel from '$lib/components/ui/sidebar/sidebar-group-label.svelte';
  import GroupContent from '$lib/components/ui/sidebar/sidebar-group-content.svelte';
  import Menu from '$lib/components/ui/sidebar/sidebar-menu.svelte';
  import MenuItem from '$lib/components/ui/sidebar/sidebar-menu-item.svelte';
  import MenuButton from '$lib/components/ui/sidebar/sidebar-menu-button.svelte';
  import Separator from '$lib/components/ui/sidebar/sidebar-separator.svelte';
  import Footer from '$lib/components/ui/sidebar/sidebar-footer.svelte';
  import { onMount } from 'svelte';
  
  // Make path a derived state that updates when page changes
  let path = $derived($page.url.pathname);
  
  // Create navigation items with reactive closures for 'active' property
  const mainNavItems = [
    {
      label: "Home",
      url: "/",
      icon: Home,
      get active() { return path === '/' }
    },
    {
      label: "Articles",
      url: "/articles",
      icon: FileText,
      get active() { return path.startsWith('/articles') }
    },
    {
      label: "Podcasts",
      url: "/podcasts",
      icon: Headphones,
      get active() { return path.startsWith('/podcasts') }
    }
  ];
  
  const secondaryNavItems = [
    {
      label: "Profile",
      url: "/profile",
      icon: User,
      get active() { return path.startsWith('/profile') }
    },
    {
      label: "Settings",
      url: "/settings",
      icon: Settings,
      get active() { return path.startsWith('/settings') }
    }
  ];
  
  onMount(() => {
    console.log('MainSidebar mounted');
    console.log('Current path:', path);
    console.log('Main nav items:', mainNavItems);
  });
</script>

<style>
  /* Enhance transitions for collapse state */
  :global(.sidebar-icon) {
    @apply transition-all duration-200;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  :global(.sidebar-label) {
    @apply transition-opacity duration-200 whitespace-nowrap overflow-hidden;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  /* Balance icon sizing when collapsed */
  .sidebar-collapsed :global(.sidebar-icon) {
    @apply mx-auto h-5 w-5;
  }
  
  /* Fix alignment in menu items */
  :global(.sidebar-menu-item a) {
    @apply flex items-center gap-3;
  }
  
  /* Enhanced visibility for active items in collapsed mode */
  .sidebar-collapsed :global(.sidebar-menu-item a.active .sidebar-icon) {
    @apply text-[hsl(var(--primary))];
  }
</style>

<Root class="h-full border-r border-[hsl(var(--sidebar-border))]">
  <Header class="px-2">
    <div class="flex items-center justify-between p-4">
      <div class="flex items-center gap-2">
        <span class="font-semibold text-lg sidebar-label">ASAP Digest</span>
      </div>
    </div>
  </Header>
  
  <Content class="px-2">
    <Group class="pb-4">
      <GroupLabel class="px-2 sidebar-label">Main</GroupLabel>
      <GroupContent class="space-y-1">
        <Menu class="space-y-1">
          {#each mainNavItems as item (item.label)}
            <MenuItem class="px-0 sidebar-menu-item">
              <a href={item.url} class="{item.active ? 'active' : ''} flex items-center gap-3 w-full justify-start p-2 rounded-md hover:bg-[hsl(var(--sidebar-accent))]">
                <div class="sidebar-icon">
                  {#if item.icon}
                    <item.icon size={18} />
                  {/if}
                </div>
                <span class="sidebar-label">{item.label}</span>
              </a>
            </MenuItem>
          {/each}
        </Menu>
      </GroupContent>
    </Group>
    
    <Separator class="my-2" />
    
    <Group class="pb-4">
      <GroupLabel class="px-2 sidebar-label">Account</GroupLabel>
      <GroupContent class="space-y-1">
        <Menu class="space-y-1">
          {#each secondaryNavItems as item (item.label)}
            <MenuItem class="px-0 sidebar-menu-item">
              <a href={item.url} class="{item.active ? 'active' : ''} flex items-center gap-3 w-full justify-start p-2 rounded-md hover:bg-[hsl(var(--sidebar-accent))]">
                <div class="sidebar-icon">
                  {#if item.icon}
                    <item.icon size={18} />
                  {/if}
                </div>
                <span class="sidebar-label">{item.label}</span>
              </a>
            </MenuItem>
          {/each}
        </Menu>
      </GroupContent>
    </Group>
  </Content>
  
  <Footer class="px-4 py-2">
    <div class="p-2 text-sm">
      <p class="sidebar-label">ASAP Digest v1.0</p>
    </div>
  </Footer>
</Root> 