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
  import { authStore, isLoading } from '$lib/auth';
  import { onMount } from 'svelte';
  
  /**
   * Component props
   */
  let { 
    /**
     * When true, displays a loading spinner while checking auth status
     */
    showLoading = true,
    
    /**
     * Whether to redirect unauthenticated users to login page
     */
    requireAuth = false,
    
    /**
     * URL to redirect to if user is not authenticated (when requireAuth is true)
     */
    redirectUrl = '/login'
  } = $props();
  
  onMount(async () => {
    // Check session validity on component mount
    if (requireAuth) {
      try {
        const isValid = await authStore.checkSession();
        if (!isValid) {
          window.location.href = redirectUrl;
        }
      } catch (error) {
        console.error('Auth check error:', error);
        window.location.href = redirectUrl;
      }
    }
  });
</script>

{#if $isLoading && showLoading}
  <div class="flex justify-center items-center p-4">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[hsl(var(--primary))]"></div>
  </div>
{:else if $authStore.user}
  <slot name="authenticated"></slot>
{:else}
  <slot name="unauthenticated"></slot>
{/if} 