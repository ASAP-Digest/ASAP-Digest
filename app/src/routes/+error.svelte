<script>
  import { page } from '$app/stores';
  import { Button } from '$lib/components/ui/button';
  import { ArrowLeft } from 'lucide-svelte';
  import { goto } from '$app/navigation';

  /** @type {string} */
  let message = $derived($page.error?.message || 'Something went wrong');
  
  /** @type {number} */
  let status = $derived($page.status || 500);
  
  /** @type {string} */
  let title = $derived(() => {
    switch (status) {
      case 404:
        return 'Page not found';
      case 403:
        return 'Access denied';
      case 401:
        return 'Unauthorized';
      default:
        return 'Error occurred';
    }
  });

  function goBack() {
    if (window.history.length > 2) {
      window.history.back();
    } else {
      goto('/');
    }
  }
</script>

<div class="flex flex-col items-center justify-center min-h-[80vh] p-4 text-center">
  <h1 class="text-4xl font-bold mb-4 text-[hsl(var(--foreground))]">{status}</h1>
  <h2 class="text-2xl font-semibold mb-2 text-[hsl(var(--foreground))]">{title}</h2>
  <p class="text-[hsl(var(--muted-foreground))] mb-8">{message}</p>
  <Button onclick={goBack} variant="outline" class="gap-2">
    <ArrowLeft class="w-4 h-4" />
    Go Back
  </Button>
</div> 