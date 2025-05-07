<!-- 
  AuthButtons Component
  Shows login/register or logout buttons based on authentication status
-->
<script>
  import { signOut, useSession } from '$lib/auth-client';
  import { goto } from '$app/navigation';
  import { auth } from '$lib/auth-client';
  import { Button } from '$lib/components/ui/button';
  import { LogIn, LogOut, Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { UserPlus } from '$lib/utils/lucide-compat.js';
  
  /**
   * @typedef {Object} AuthButtonsProps
   * @property {'sm'|'md'|'lg'} [size] - Button size
   */
  
  /** @type {AuthButtonsProps} */
  let { 
    size = 'md'
  } = $props();
  
  /**
   * Button style classes based on size
   */
  const sizeClasses = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
  };
  
  const session = useSession();
  let isLoading = $state(false);
  
  /**
   * Navigate to login page
   */
  function login() {
    goto('/login');
  }
  
  /**
   * Navigate to register page
   */
  function register() {
    goto('/register');
  }
  
  /**
   * Log the user out
   */
  async function logout() {
    try {
      isLoading = true;
      await signOut();
      goto('/');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      isLoading = false;
    }
  }
</script>

{#if $session}
  <Button
    variant="ghost"
    size="sm"
    onclick={logout}
    disabled={isLoading}
    class="flex items-center gap-2"
  >
    {#if isLoading}
      <Icon icon={Loader2} class="w-4 h-4 animate-spin" />
    {:else}
      <Icon icon={LogOut} class="w-4 h-4" />
    {/if}
    Sign Out
  </Button>
{:else}
  <div class="flex items-center gap-2">
    <Button
      variant="ghost"
      size="sm"
      onclick={() => goto('/login')}
      disabled={isLoading}
      class="flex items-center gap-2"
    >
      {#if isLoading}
        <Icon icon={Loader2} class="w-4 h-4 animate-spin" />
      {:else}
        <Icon icon={LogIn} class="w-4 h-4" />
      {/if}
      Sign In
    </Button>
    
    <button 
      onclick={register}
      class="bg-white text-[hsl(var(--brand))] border border-[hsl(var(--brand))] rounded-md hover:bg-[hsl(var(--surface-1))] transition-colors flex items-center gap-1 {sizeClasses[size]}"
      aria-label="Register"
    >
      <Icon icon={UserPlus} size={size === 'sm' ? 14 : size === 'md' ? 16 : 18} />
      <span>Register</span>
    </button>
  </div>
{/if} 