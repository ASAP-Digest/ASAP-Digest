<!-- User menu component -->
<script>
  import { page } from '$app/stores';
  import { Button } from '$lib/components/ui/button';
  import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
  } from '$lib/components/ui/dropdown-menu';
  import * as Avatar from '$lib/components/ui/avatar';
  import { UserCircle, Settings, LogOut } from '$lib/utils/lucide-compat.js';
  import Icon from '../icon/icon.svelte';

  /** @type {{ user: { displayName: string, email: string, avatarUrl: string, roles: string[] } }} */
  const { data } = $props();

  const user = $derived(data.user);
</script>

<DropdownMenu>
  <DropdownMenuTrigger asChild let:builder>
    <Button
      variant="ghost"
      class="relative h-10 w-10 rounded-full"
      builders={[builder]}
    >
      {#if user.avatarUrl}
        <Avatar.Root>
          <Avatar.Image src={user.avatarUrl} alt={user.displayName} />
          <Avatar.Fallback>
            <Icon icon={UserCircle} class="w-6 h-6 text-[hsl(var(--muted-foreground))]" />
          </Avatar.Fallback>
        </Avatar.Root>
      {:else}
        <div class="avatar-placeholder">
          <Icon icon={UserCircle} class="w-6 h-6 text-[hsl(var(--muted-foreground))]" />
        </div>
      {/if}
    </Button>
  </DropdownMenuTrigger>
  
  <DropdownMenuContent class="w-56" align="end">
    <DropdownMenuLabel>
      <div class="flex flex-col space-y-1">
        <p class="text-sm font-medium leading-none">{user.displayName}</p>
        <p class="text-xs leading-none text-muted-foreground">{user.email}</p>
      </div>
    </DropdownMenuLabel>
    
    <DropdownMenuSeparator />
    
    <DropdownMenuItem asChild let:builder>
      <a href="/settings" class="cursor-pointer" builders={[builder]}>
        <Icon icon={Settings} class="mr-2 h-4 w-4" />
        <span>Settings</span>
      </a>
    </DropdownMenuItem>
    
    <DropdownMenuItem asChild let:builder>
      <a href="/api/auth/logout" class="cursor-pointer" builders={[builder]}>
        <Icon icon={LogOut} class="mr-2 h-4 w-4" />
        <span>Log out</span>
      </a>
    </DropdownMenuItem>
  </DropdownMenuContent>
</DropdownMenu>

<style>
  .avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: hsl(var(--muted));
  }
</style> 