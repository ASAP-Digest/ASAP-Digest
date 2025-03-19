<script>
  import { Card, CardContent, CardHeader, CardTitle } from "$lib/components/ui/card";
  import { Skeleton } from "$lib/components/ui/skeleton";
  import { fade } from 'svelte/transition';
  import { WIDGET_SPACING } from '$lib/styles/spacing.js';
  
  /** @type {string} */
  export let title = "";
  
  /** @type {import('lucide-svelte').LucideIcon | null} */
  export let icon = null;
  
  /** @type {boolean} */
  export let loading = false;
  
  /** @type {'default' | 'compact' | 'expanded'} */
  export let variant = 'default';
  
  $: spacing = getVariantSpacing(variant);
  
  /**
   * Get spacing values based on widget variant
   * @param {'default' | 'compact' | 'expanded'} variant - Widget variant
   * @returns {{padding: string, gap: string}} Spacing values
   */
  function getVariantSpacing(variant) {
    switch(variant) {
      case 'compact':
        return {
          padding: 'p-[0.75rem]',
          gap: 'gap-[0.5rem]'
        };
      case 'expanded':
        return {
          padding: 'p-[1.5rem]',
          gap: 'gap-[1rem]'
        };
      case 'default':
      default:
        return {
          padding: 'p-[1rem]',
          gap: 'gap-[0.75rem]'
        };
    }
  }
</script>

<Card class="h-full">
  {#if title}
    <CardHeader class="{spacing.padding} pb-0">
      <CardTitle class="flex items-center {spacing.gap}">
        {#if icon}
          <svelte:component this={icon} class="text-primary" size={20} />
        {/if}
        {#if loading}
          <Skeleton class="h-[1.5rem] w-[8rem]" />
        {:else}
          {title}
        {/if}
      </CardTitle>
    </CardHeader>
  {/if}
  <CardContent class="{spacing.padding} {spacing.gap} flex flex-col">
    {#if loading}
      <slot name="loading">
        <div class="space-y-[0.5rem]">
          <Skeleton class="h-[4rem] w-full" />
          <Skeleton class="h-[1rem] w-3/4" />
          <Skeleton class="h-[1rem] w-1/2" />
        </div>
      </slot>
    {:else}
      <slot />
    {/if}
  </CardContent>
</Card>

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