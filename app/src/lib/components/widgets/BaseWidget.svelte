<script>
  import { Card, CardContent, CardHeader, CardTitle } from "$lib/components/ui/card";
  import { Skeleton } from "$lib/components/ui/skeleton";
  import { fade } from 'svelte/transition';
  import { WIDGET_SPACING } from '$lib/styles/spacing.js';
  
  /** @type {Object} props - Component properties */
  let {
    title = "",
    icon = null,
    loading = false,
    variant = /** @type {'default' | 'compact' | 'expanded'} */ ('default'),
    default: defaultSlot,
    loadingSlot
  } = $props();
  
  let spacing = $derived(getVariantSpacing(variant));
  
  /**
   * Get spacing values based on widget variant
   * @param {'default' | 'compact' | 'expanded'} variant - Widget variant
   * @returns {{padding: string, gap: string}} Spacing values
   */
  function getVariantSpacing(variant) {
    switch(variant) {
      case 'compact':
        return {
          padding: 'p-[calc(var(--spacing-unit)*3)]',
          gap: 'gap-[calc(var(--spacing-unit)*2)]'
        };
      case 'expanded':
        return {
          padding: 'p-[calc(var(--spacing-unit)*6)]',
          gap: 'gap-[calc(var(--spacing-unit)*4)]'
        };
      case 'default':
      default:
        return {
          padding: 'p-[calc(var(--spacing-unit)*4)]',
          gap: 'gap-[calc(var(--spacing-unit)*3)]'
        };
    }
  }
</script>

<Card class="h-full rounded-[var(--radius-lg)]">
  {#if title}
    <CardHeader class="{spacing.padding} pb-0">
      <CardTitle class="flex items-center {spacing.gap}">
        {#if icon}
          <div class="text-primary">
            <!-- Hard-coded icon handling -->
            <span class="text-primary w-5 h-5"></span>
          </div>
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
      {#if loadingSlot}
        {@render loadingSlot()}
      {:else}
        <div class="space-y-[0.5rem]">
          <Skeleton class="h-[4rem] w-full" />
          <Skeleton class="h-[1rem] w-3/4" />
          <Skeleton class="h-[1rem] w-1/2" />
        </div>
      {/if}
    {:else}
      {#if defaultSlot}
        {@render defaultSlot()}
      {/if}
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