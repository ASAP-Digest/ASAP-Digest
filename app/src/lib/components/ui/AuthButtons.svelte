<!-- 
  AuthButtons Component
  Shows login/register or logout buttons based on authentication status
-->
<script>
  import { authStore } from '$lib/auth';
  import { goto } from '$app/navigation';
  import Icon from "$lib/components/ui/Icon.svelte";
  import { LogIn, LogOut, UserPlus } from '$lib/utils/lucide-icons.js';
  
  /**
   * Component props
   */
  let { 
    /**
     * Button size: 'sm', 'md', or 'lg'
     */
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
      await authStore.signOut();
      goto('/');
    } catch (error) {
      console.error('Logout error:', error);
    }
  }
</script>

{#if $authStore.user}
  <div class="flex items-center gap-2">
    <span class="text-sm mr-2 hidden md:inline">{$authStore.user.name || $authStore.user.email}</span>
    <button 
      on:click={logout}
      class="bg-[hsl(var(--primary))] text-white rounded-md hover:bg-opacity-90 transition-colors flex items-center gap-1 {sizeClasses[size]}"
      aria-label="Log out"
    >
      <Icon icon={LogOut} size={size === 'sm' ? 14 : size === 'md' ? 16 : 18} />
      <span>Logout</span>
    </button>
  </div>
{:else}
  <div class="flex items-center gap-2">
    <button 
      on:click={login}
      class="bg-[hsl(var(--primary))] text-white rounded-md hover:bg-opacity-90 transition-colors flex items-center gap-1 {sizeClasses[size]}"
      aria-label="Log in"
    >
      <Icon icon={LogIn} size={size === 'sm' ? 14 : size === 'md' ? 16 : 18} />
      <span>Login</span>
    </button>
    
    <button 
      on:click={register}
      class="bg-white text-[hsl(var(--primary))] border border-[hsl(var(--primary))] rounded-md hover:bg-gray-50 transition-colors flex items-center gap-1 {sizeClasses[size]}"
      aria-label="Register"
    >
      <Icon icon={UserPlus} size={size === 'sm' ? 14 : size === 'md' ? 16 : 18} />
      <span>Register</span>
    </button>
  </div>
{/if} 