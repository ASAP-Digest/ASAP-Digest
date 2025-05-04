<script>
  import { cn } from '$lib/utils';
  import { Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';

  /**
   * @typedef {Object} BaseWidgetProps
   * @property {string} [title] - Widget title
   * @property {Object} [icon] - Icon object for the widget header
   * @property {boolean} [loading=false] - Whether the widget is in loading state
   * @property {'default' | 'compact' | 'expanded'} [variant='default'] - Widget spacing variant
   * @property {string} [className] - Additional CSS classes
   * @property {import('svelte').Snippet} [children] - Widget content
   */

  /** @type {BaseWidgetProps} */
  let {
    title = '',
    icon = undefined,
    loading = false, 
    variant = 'default', // 'default', 'compact', 'expanded'
    className = '',
    children = undefined
  } = $props();

  const getVariantSpacing = () => {
    switch (variant) {
      case 'compact':
        // Using 8pt grid (multiples of 8)
        return 'p-3 gap-2'; // 12px padding, 8px gap
      case 'expanded':
        // Using Golden Ratio derived from 8pt grid (16px * 1.618)
        return 'p-6 gap-4'; // ~26px padding, ~16px gap 
      default:
        // Default using 8pt grid (16px)
        return 'p-4 gap-3'; // 16px padding, 12px gap
    }
  };

  const spacing = getVariantSpacing();
</script>

<div 
  class={cn(
    'group/widget relative bg-[hsl(var(--surface-1))] rounded-[var(--radius-lg)] border border-[hsl(var(--border))] shadow-[var(--shadow-sm)] mb-0 h-full',
    'transition-all duration-[var(--duration-normal)] ease-[var(--ease-out)]',
    spacing,
    className
  )}
>
  <!-- Header with title and icon -->
  {#if title}
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-[var(--font-weight-semibold)] text-[var(--font-size-base)] text-[hsl(var(--text-1))]">
        {title}
        
        {#if loading}
          <span class="inline-flex items-center ml-2 text-[hsl(var(--text-2))]">
            <Icon icon={Loader2} size={16} class="animate-spin mr-2" />
            <span class="text-[var(--font-size-xs)]">Loading...</span>
          </span>
        {/if}
      </h3>
      
      {#if icon}
        <div class="text-[hsl(var(--text-2))]">
          <Icon icon={icon} size={20} />
        </div>
      {/if}
    </div>
  {/if}
  
  <!-- Loading indicator if no title -->
  {#if loading && !title}
    <div class="flex items-center justify-center py-4 text-[hsl(var(--text-2))]">
      <Icon icon={Loader2} class="animate-spin mr-2" size={20} />
      <span class="text-[var(--font-size-sm)]">Loading widget content...</span>
    </div>
  {/if}
  
  <!-- Widget content -->
  {@render children?.()}
</div>

<style>
  /* Local styles for hover effects - Using GRDSP variables */
  :global(.group\/widget:hover) {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    border-color: hsl(var(--border)/0.8);
    transition: transform var(--duration-fast) var(--ease-out),
                box-shadow var(--duration-normal) var(--ease-out),
                border-color var(--duration-normal) var(--ease-out);
  }
</style> 