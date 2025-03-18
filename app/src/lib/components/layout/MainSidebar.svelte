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

<Root class="h-full border-r border-[hsl(var(--sidebar-border))]">
  <Header class="px-2">
    <div class="flex items-center justify-between p-4">
      <div class="flex items-center gap-2">
        <span class="font-semibold text-lg">ASAP Digest</span>
      </div>
    </div>
  </Header>
  
  <Content class="px-2">
    <Group class="pb-4">
      <GroupLabel class="px-2">Main</GroupLabel>
      <GroupContent class="space-y-1">
        <Menu class="space-y-1">
          {#each mainNavItems as item (item.label)}
            <MenuItem class="px-0">
              <MenuButton 
                class="w-full justify-start" 
                isActive={item.active}
                tooltipContent={item.label}
                tooltipContentProps={{}}>
                {#snippet child({ props = /** @type {Record<string, any>} */ ({}) })}
                  <a href={item.url} {...props}>
                    <item.icon size={18} />
                    <span>{item.label}</span>
                  </a>
                {/snippet}
              </MenuButton>
            </MenuItem>
          {/each}
        </Menu>
      </GroupContent>
    </Group>
    
    <Separator class="my-2" />
    
    <Group class="pb-4">
      <GroupLabel class="px-2">Account</GroupLabel>
      <GroupContent class="space-y-1">
        <Menu class="space-y-1">
          {#each secondaryNavItems as item (item.label)}
            <MenuItem class="px-0">
              <MenuButton 
                class="w-full justify-start" 
                isActive={item.active}
                tooltipContent={item.label}
                tooltipContentProps={{}}>
                {#snippet child({ props = /** @type {Record<string, any>} */ ({}) })}
                  <a href={item.url} {...props}>
                    <item.icon size={18} />
                    <span>{item.label}</span>
                  </a>
                {/snippet}
              </MenuButton>
            </MenuItem>
          {/each}
        </Menu>
      </GroupContent>
    </Group>
  </Content>
  
  <Footer class="px-4 py-2">
    <div class="p-2 text-sm">
      <p>ASAP Digest v1.0</p>
    </div>
  </Footer>
</Root> 