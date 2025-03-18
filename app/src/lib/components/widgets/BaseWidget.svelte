<script>
  import { Card } from '$lib/components/ui/card';
  import { CardHeader } from '$lib/components/ui/card';
  import { CardTitle } from '$lib/components/ui/card';
  import { CardContent } from '$lib/components/ui/card';
  import { fade } from 'svelte/transition';
  import { WIDGET_SPACING } from '$lib/styles/spacing.js';
  
  export let title = '';
  export let icon = null;
  export let loading = false;
  export let variant = 'default'; // 'default', 'compact', or 'expanded'
  
  // Get appropriate spacing based on variant
  $: spacing = getVariantSpacing(variant);
  
  /**
   * Returns specific spacing values based on widget variant
   * @param {'default'|'compact'|'expanded'} variant - Widget variant
   * @returns {Object} - Spacing values
   */
  function getVariantSpacing(variant) {
    switch(variant) {
      case 'compact':
        return {
          padding: 'p-3',
          margin: 'm-0',
          gap: 'gap-2'
        };
      case 'expanded':
        return {
          padding: 'p-6',
          margin: 'm-0',
          gap: 'gap-6'
        };
      default:
        return {
          padding: 'p-4 md:p-5',
          margin: 'm-0',
          gap: 'gap-4'
        };
    }
  }
</script>

<div in:fade={{ duration: 200 }} class="widget w-full">
  <Card class="shadow-md w-full border border-[hsl(var(--border))] bg-[hsl(var(--card))]">
    <CardHeader class={`pb-0 ${spacing.padding}`}>
      <CardTitle class="flex items-center text-lg text-[hsl(var(--card-foreground))]">
        {#if icon}
          <svelte:component this={icon} class="mr-2 w-5 h-5" />
        {/if}
        {title}
      </CardTitle>
    </CardHeader>
    <CardContent class={`pt-4 ${spacing.padding}`}>
      {#if loading}
        <div class="flex justify-center p-4">
          <div class="animate-spin h-6 w-6 border-2 border-[hsl(var(--primary))] rounded-full border-t-transparent"></div>
        </div>
      {:else}
        <div class={spacing.gap}>
          <slot />
        </div>
      {/if}
    </CardContent>
  </Card>
</div>

<style>
  /* Local styling for widgets */
  :global(.widget) {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  }
  
  :global(.widget:hover) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }
</style> 