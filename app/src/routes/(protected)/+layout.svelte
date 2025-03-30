<script>
  import { page } from '$app/stores';
  import { Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import ProtectedRoute from '$lib/components/ProtectedRoute.svelte';
  
  /** @type {import('./$types').LayoutData} */
  const { data, children } = $props();
</script>

{#if $page.data.session}
  {@render children?.()}
{:else}
  <div class="flex justify-center items-center min-h-screen">
    <Icon icon={Loader2} class="w-8 h-8 animate-spin text-[hsl(var(--primary))]" />
  </div>
{/if} 