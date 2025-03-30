<!-- 
  AuthGuard Component
  Used to conditionally render content based on user authentication status
  Usage:
  <AuthGuard>
    <span slot="authenticated">Content only shown to logged-in users</span>
    <span slot="unauthenticated">Content only shown to guests</span>
  </AuthGuard>
-->
<script>
  import { useSession } from '$lib/auth-client';
  import { onMount } from 'svelte';
  import { cn } from '$lib/utils';
  
  /**
   * Component props
   * @typedef {Object} Props
   * @property {boolean} [showLoading=true] - Whether to display a loading spinner while checking auth status
   * @property {boolean} [requireAuth=false] - Whether to redirect unauthenticated users to login page
   * @property {string} [redirectUrl='/login'] - URL to redirect to if user is not authenticated
   */
  
  /** @type {string} */
  export let redirectUrl = '/login';
  /** @type {boolean} */
  export let requireAuth = true;
  /** @type {string} */
  export let class_ = '';
  
  const session = useSession();
  let isLoading = true;
  
  onMount(async () => {
    if (requireAuth) {
      try {
        const isValid = $session !== null;
        if (!isValid) {
          window.location.href = redirectUrl;
        }
      } catch (error) {
        console.error('Error checking session:', error);
        window.location.href = redirectUrl;
      }
    }
    isLoading = false;
  });
</script>

{#if isLoading}
  <div class={cn('flex items-center justify-center min-h-screen', class_)}>
    <div class="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
  </div>
{:else}
  <slot />
{/if} 