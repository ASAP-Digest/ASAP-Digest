<script>
  import { cn } from '$lib/utils';
  import { Icon } from '@steeze-ui/svelte-icon';
  import { Loader2 } from '@steeze-ui/heroicons';
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let {
    title = '',
    icon = undefined,
    loading = false, 
    variant = 'default', // 'default', 'compact', 'expanded'
    className = ''
  } = $props();

  const getVariantSpacing = () => {
    switch (variant) {
      case 'compact':
        return 'p-3 gap-2';
      case 'expanded':
        return 'p-6 gap-4';
      default:
        return 'p-4 gap-3';
    }
  };

  const spacing = getVariantSpacing();
</script>

<div 
  class={cn(
    'group/widget relative bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm hover:shadow-md transition-all duration-200',
    spacing,
    className
  )}
>
  <!-- Header with title and icon -->
  {#if title}
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-medium text-base">
        {title}
        
        {#if loading}
          <span class="inline-flex items-center ml-2 text-[hsl(var(--muted-foreground))]">
            <Icon icon={Loader2} size={14} class="animate-spin mr-1" color="currentColor" />
            <span class="text-xs">Loading...</span>
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
    <div class="flex items-center justify-center py-4 text-[hsl(var(--muted-foreground))]">
      <Icon icon={Loader2} class="animate-spin mr-2" color="currentColor" />
      <span>Loading widget content...</span>
    </div>
  {/if}
  
  <!-- Slot for widget content -->
  <slot />
</div>

<style>
  /* Local styles for hover effects */
  :global(.group\/widget:hover) {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }
</style> 