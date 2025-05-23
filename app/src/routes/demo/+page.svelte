<script>
  import { onMount } from 'svelte';
  import Button from '$lib/components/ui/button/button.svelte';
  import { Plus } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  let selectedItems = $state([]);
  
  // Listen for the global content-added event
  onMount(() => {
    const handleContentAdded = (event) => {
      const { items, type } = event.detail;
      console.log('Content added from global FAB:', items);
      selectedItems = [...selectedItems, ...items];
    };
    
    // Add event listener
    window.addEventListener('app:content-added', handleContentAdded);
    
    // Clean up event listener
    return () => {
      window.removeEventListener('app:content-added', handleContentAdded);
    };
  });
</script>

<!-- The outermost div with grid-stack-item is already added by a previous edit -->
<!-- Remove the inner wrapper and make sections direct grid-stack-items -->

<!-- Header Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="2">
  <div class="grid-stack-item-content">
    <h1 class="text-3xl font-bold mb-4">Content Selector Demo (Global FAB)</h1>
    <div class="mb-6">
      <p class="text-[hsl(var(--muted-foreground))]">
        Click the floating action button in the bottom corner to add content.
        Selected content will appear below.
      </p>
    </div>
  </div>
</div>

{#if selectedItems.length > 0}
  <!-- Selected Items Section - Treat as Gridstack item -->
  <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
    <div class="grid-stack-item-content">
      <div>
        <h2 class="text-xl font-semibold mb-4">Selected Items</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {#each selectedItems as item}
            <div class="bg-[hsl(var(--card))] border border-[hsl(var(--border))] rounded-lg shadow-sm overflow-hidden">
              <div class="aspect-video relative">
                <img src={item.image} alt={item.title} class="w-full h-full object-cover" />
              </div>
              <div class="p-4">
                <h3 class="font-medium">{item.title}</h3>
                <p class="text-sm text-[hsl(var(--muted-foreground))] mt-1">
                  {item.excerpt || (item.duration ? `Duration: ${item.duration}` : '')}
                </p>
                <div class="mt-2 text-xs text-[hsl(var(--muted-foreground))]">
                  <span>{item.source}</span>
                </div>
              </div>
            </div>
          {/each}
        </div>
      </div>
    </div>
  </div>
{:else}
  <!-- Empty State Section - Treat as Gridstack item -->
    <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="3">
      <div class="grid-stack-item-content">
        <div class="bg-[hsl(var(--muted))] rounded-lg p-8 text-center">
          <p class="text-[hsl(var(--muted-foreground))]">No items selected yet. Use the global floating action button to add content.</p>
        </div>
      </div>
    </div>
{/if}