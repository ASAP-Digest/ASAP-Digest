<script>
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { authStore } from '$lib/auth'; // Import authStore
  
  onMount(async () => {
    console.log('Logout page mounted, attempting sign out...');
    try {
      await authStore.signOut();
      console.log('Sign out successful, redirecting to login.');
      goto('/login'); // Redirect to login page after logout
    } catch (error) {
      console.error('Logout error:', error);
      // Optionally handle error, maybe redirect anyway or show a message
      goto('/login');
    }
  });
</script>

<div class="container h-[calc(100vh-4rem)] flex flex-col items-center justify-center">
  <div class="max-w-md w-full bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm p-8 text-center">
    <h1 class="text-2xl font-bold mb-4 text-[hsl(var(--foreground))]">Logging Out</h1>
    <p class="text-[hsl(var(--muted-foreground))] mb-6">Please wait while we log you out...</p>
    
    <div class="flex justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[hsl(var(--primary))]"></div>
    </div>
    
    <p class="text-sm text-[hsl(var(--muted-foreground))] mt-6">
      You will be redirected to the login page shortly.
    </p>
  </div>
</div> 