<script>
  import { cn } from '$lib/utils';
  import { Loader2 } from '$lib/utils/lucide-icons.js';
  import Icon from '$lib/components/ui/Icon.svelte';

  /** @typedef {{ default?: () => void }} SlotsType */

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
        return 'p-[calc(var(--spacing-unit)*3)] gap-[calc(var(--spacing-unit)*2)]';
      case 'expanded':
        return 'p-[calc(var(--spacing-unit)*6)] gap-[calc(var(--spacing-unit)*4)]';
      default:
        return 'p-[calc(var(--spacing-unit)*4)] gap-[calc(var(--spacing-unit)*3)]';
    }
  };

  const spacing = getVariantSpacing();
</script>

<div 
  class={cn(
    'group/widget relative bg-[hsl(var(--card))] rounded-[var(--radius-md)] border border-[hsl(var(--border))] shadow-sm mb-0 h-full',
    'transition-all duration-[var(--duration-normal)] ease-[var(--ease-out)]',
    spacing,
    className
  )}
>
  <!-- Header with title and icon -->
  {#if title}
    <div class="flex items-center justify-between mb-[calc(var(--spacing-unit)*3)]">
      <h3 class="font-[var(--font-weight-medium)] text-[var(--font-size-base)] text-[hsl(var(--foreground))]">
        {title}
        
        {#if loading}
          <span class="inline-flex items-center ml-2 text-[hsl(var(--muted-foreground))]">
            <Icon icon={Loader2} size={14} class="animate-spin mr-1" color="currentColor" />
            <span class="text-[var(--font-size-xs)]">Loading...</span>
          </span>
        {/if}
      </h3>
      
      {#if icon}
        <div class="text-[hsl(var(--muted-foreground))]">
          <Icon icon={icon} size={18} color="currentColor" />
        </div>
      {/if}
    </div>
  {/if}
  
  <!-- Loading indicator if no title -->
  {#if loading && !title}
    <div class="flex items-center justify-center py-[calc(var(--spacing-unit)*4)] text-[hsl(var(--muted-foreground))]">
      <Icon icon={Loader2} class="animate-spin mr-2" color="currentColor" />
      <span>Loading widget content...</span>
    </div>
  {/if}
  
  <!-- Widget content -->
  {@render children?.()}
</div>

<style>
  /* Local styles for hover effects */
  :global(.group\/widget:hover) {
    box-shadow: 0 4px 12px hsl(var(--card-foreground) / 0.05);
    transform: translateY(-2px);
  }
</style> 