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
  import { cn } from '$lib/utils';
  
  /**
   * Component props
   * @typedef {Object} Props
   * @property {boolean} [showLoading=true] - Whether to display a loading spinner while checking auth status
   * @property {boolean} [requireAuth=false] - Whether to redirect unauthenticated users to login page
   * @property {string} [redirectUrl='/login'] - URL to redirect to if user is not authenticated
   */
  
  const { showLoading = true, requireAuth = false, redirectUrl = '/login' } = $props();
  
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
  <div class={cn(
    "flex justify-center items-center",
    "p-[calc(var(--spacing-unit)*4)]"
  )}>
    <div class={cn(
      "animate-spin rounded-full",
      "h-[calc(var(--spacing-unit)*8)] w-[calc(var(--spacing-unit)*8)]",
      "border-[2px] border-[hsl(var(--muted))]",
      "border-t-[hsl(var(--primary))]",
      "transition-colors duration-[var(--duration-normal)]"
    )}></div>
  </div>
{:else if $authStore.user}
  <slot name="authenticated"></slot>
{:else}
  <slot name="unauthenticated"></slot>
{/if} 