<script>
  import { page } from '$app/stores';
  import { Home, FileText, Headphones, Settings, User } from 'lucide-svelte';
  import * as Sidebar from '$lib/components/ui/sidebar';
  
  // Get active route
  const path = $derived($page.url.pathname);
  
  // Menu items
  const mainItems = [
    {
      title: "Home",
      url: "/",
      icon: Home,
      active: path === '/'
    },
    {
      title: "Articles",
      url: "/articles",
      icon: FileText,
      active: path.startsWith('/articles')
    },
    {
      title: "Podcasts",
      url: "/podcasts",
      icon: Headphones,
      active: path.startsWith('/podcasts')
    }
  ];
  
  const accountItems = [
    {
      title: "Profile",
      url: "/profile",
      icon: User,
      active: path.startsWith('/profile')
    },
    {
      title: "Settings",
      url: "/settings",
      icon: Settings,
      active: path.startsWith('/settings')
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
          {#each mainItems as item (item.title)}
            <Sidebar.MenuItem class="px-0">
              <Sidebar.MenuButton 
                class="w-full justify-start" 
                isActive={item.active}
                tooltipContent={item.title}
                tooltipContentProps={{}}
              >
                {#snippet child({ props })}
                  <a href={item.url} {...props}>
                    <item.icon size={18} />
                    <span>{item.title}</span>
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
          {#each accountItems as item (item.title)}
            <Sidebar.MenuItem class="px-0">
              <Sidebar.MenuButton 
                class="w-full justify-start" 
                isActive={item.active}
                tooltipContent={item.title}
                tooltipContentProps={{}}
              >
                {#snippet child({ props })}
                  <a href={item.url} {...props}>
                    <item.icon size={18} />
                    <span>{item.title}</span>
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