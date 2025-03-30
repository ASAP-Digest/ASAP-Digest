<script>
  import { page } from '$app/stores';
  import { Loader2 } from 'lucide-svelte';
  
  /** @type {import('./$types').LayoutData} */
  const { data } = $props();
</script>

{#if $page.data.session}
  <slot />
{:else}
  <div class="flex justify-center items-center min-h-screen">
    <Loader2 class="w-8 h-8 animate-spin text-[hsl(var(--primary))]" />
  </div>
{/if} 