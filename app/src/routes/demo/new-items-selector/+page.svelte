<script>
  // @ts-ignore - Svelte component import
  import { Card } from '$lib/components/ui/card';
  // @ts-ignore - Svelte component import
  import NewItemsSelector2 from '$lib/components/selectors/NewItemsSelector2.svelte';
  
  /**
   * @typedef {import('$lib/api/content-service').ContentItem} ContentItem
   */
  
  /** @type {ContentItem[]} */
  let selectedItems = $state([]);
  
  /**
   * Handle selection change
   * @param {ContentItem[]} items
   */
  function onSelectionChange(items) {
    selectedItems = items;
    console.log('Selected items:', items);
  }
</script>

<div class="container mx-auto px-4 py-8">
  <h1 class="text-[var(--font-size-xl)] font-[var(--font-weight-semibold)] text-[hsl(var(--text-1))] mb-8">
    New Items Selector Demo
  </h1>
  
  <div class="grid grid-cols-1 gap-8">
    <div>
      <h2 class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] text-[hsl(var(--text-1))] mb-4">
        Select Content Items
      </h2>
      
      <Card class="p-4">
        <NewItemsSelector2
          maxItems={5}
          enabledContentTypes={['article', 'podcast', 'keyterm', 'financial']}
          onSelectionChange={onSelectionChange}
        />
      </Card>
    </div>
    
    <div>
      <h2 class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] text-[hsl(var(--text-1))] mb-4">
        Selected Items Preview
      </h2>
      
      <Card class="p-4">
        {#if selectedItems.length === 0}
          <p class="text-[hsl(var(--text-2))]">No items selected. Select items from above.</p>
        {:else}
          <ul class="list-none space-y-4">
            {#each selectedItems as item}
              <li class="border-b border-[hsl(var(--border))] pb-4 last:border-0 last:pb-0">
                <h3 class="text-[var(--font-size-base)] font-[var(--font-weight-semibold)] text-[hsl(var(--text-1))]">
                  {item.title}
                </h3>
                <p class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
                  {item.excerpt || 'No description available'}
                </p>
                <div class="flex gap-2 mt-2 text-[var(--font-size-xs)] text-[hsl(var(--text-3))]">
                  <span class="capitalize">{item.type}</span>
                  {#if item.source}
                    <span>| {item.source}</span>
                  {/if}
                  {#if item.date}
                    <span>| {item.date}</span>
                  {/if}
                </div>
              </li>
            {/each}
          </ul>
        {/if}
      </Card>
    </div>
  </div>
</div> 