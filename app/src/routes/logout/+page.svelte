<script>
  import { auth } from '$lib/auth-client';
  import { goto } from '$app/navigation';

  /** @type {import('./$types').PageLoad} */
  export async function load() {
    try {
      await auth.signOut();
      goto('/login');
    } catch (error) {
      console.error('Error signing out:', error);
    }
  }
</script>

<div class="flex items-center justify-center min-h-screen">
  <p>Signing out...</p>
</div> 