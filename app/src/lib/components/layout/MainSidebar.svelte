<script>
  import { page } from '$app/stores';
  import { Home, FileText, Headphones, Settings, User } from 'lucide-svelte';
  import * as Sidebar from '$lib/components/ui/sidebar';
  
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
</script>

<Sidebar.Root class="h-full">
  <Sidebar.Header class="px-2">
    <div class="flex items-center justify-between p-4">
      <div class="flex items-center gap-2">
        <span class="font-semibold text-lg">ASAP Digest</span>
      </div>
    </div>
  </Sidebar.Header>
  
  <Sidebar.Content class="px-2">
    <Sidebar.Group class="pb-4">
      <Sidebar.GroupLabel class="px-2" child={{props: {}}}>Main</Sidebar.GroupLabel>
      <Sidebar.GroupContent class="space-y-1">
        <Sidebar.Menu class="space-y-1">
          {#each mainNavItems as item (item.label)}
            <Sidebar.MenuItem class="px-0">
              <Sidebar.MenuButton 
                class="w-full justify-start" 
                isActive={item.active}
                tooltipContent={item.label}
                tooltipContentProps={{}}
              >
                {#snippet child({ props })}
                  <a href={item.url} {...props}>
                    <item.icon size={18} />
                    <span>{item.label}</span>
                  </a>
                {/snippet}
              </Sidebar.MenuButton>
            </Sidebar.MenuItem>
          {/each}
        </Sidebar.Menu>
      </Sidebar.GroupContent>
    </Sidebar.Group>
    
    <Sidebar.Separator class="my-2" />
    
    <Sidebar.Group class="pb-4">
      <Sidebar.GroupLabel class="px-2" child={{props: {}}}>Account</Sidebar.GroupLabel>
      <Sidebar.GroupContent class="space-y-1">
        <Sidebar.Menu class="space-y-1">
          {#each secondaryNavItems as item (item.label)}
            <Sidebar.MenuItem class="px-0">
              <Sidebar.MenuButton 
                class="w-full justify-start" 
                isActive={item.active}
                tooltipContent={item.label}
                tooltipContentProps={{}}
              >
                {#snippet child({ props })}
                  <a href={item.url} {...props}>
                    <item.icon size={18} />
                    <span>{item.label}</span>
                  </a>
                {/snippet}
              </Sidebar.MenuButton>
            </Sidebar.MenuItem>
          {/each}
        </Sidebar.Menu>
      </Sidebar.GroupContent>
    </Sidebar.Group>
  </Sidebar.Content>
  
  <Sidebar.Footer class="px-4 py-2">
    <div class="p-2 text-sm">
      <p>ASAP Digest v1.0</p>
    </div>
  </Sidebar.Footer>
</Sidebar.Root> 