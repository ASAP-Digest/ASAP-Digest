<!--
  ModuleSelector.svelte
  ---------------------
  Specialized module selector for digest creation flow.
  
  This component provides a simplified interface for selecting individual modules
  to add to a digest layout. It integrates with the existing NewItemsSelector
  but focuses on single-selection mode for placement in specific grid positions.
-->
<script>
  import { createEventDispatcher } from 'svelte';
  import { fly, fade } from 'svelte/transition';
  import Button from '$lib/components/ui/button/button.svelte';
  import Input from '$lib/components/ui/input/input.svelte';
  import { Search, FileText, Headphones, Key, DollarSign, Twitter, MessageSquare, Calendar, LineChart, X } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { fetchCachedContent, searchContentWithDebounce } from '$lib/api/content-fetcher.js';
  
  const dispatch = createEventDispatcher();
  
  // Props
  let { 
    open = false,
    targetPosition = null, // Grid position where module will be placed
    excludeIds = []        // IDs to exclude from selection
  } = $props();
  
  // State
  let selectedType = $state('');
  let searchQuery = $state('');
  let contentItems = $state([]);
  let isLoading = $state(false);
  let error = $state('');
  let hasMore = $state(false);
  let pageCursor = $state(null);
  
  // Content types
  const contentTypes = [
    { id: 'article', label: 'Article', icon: FileText, color: 'blue' },
    { id: 'podcast', label: 'Podcast', icon: Headphones, color: 'purple' },
    { id: 'keyterm', label: 'Key Term', icon: Key, color: 'amber' },
    { id: 'financial', label: 'Financial', icon: DollarSign, color: 'green' },
    { id: 'xpost', label: 'X Post', icon: Twitter, color: 'sky' },
    { id: 'reddit', label: 'Reddit', icon: MessageSquare, color: 'orange' },
    { id: 'event', label: 'Event', icon: Calendar, color: 'rose' },
    { id: 'polymarket', label: 'Polymarket', icon: LineChart, color: 'indigo' }
  ];
  
  // Load content items for selected type
  async function loadContentItems({ reset = true, cursor = null } = {}) {
    if (!selectedType) return;
    
    if (reset) {
      isLoading = true;
      contentItems = [];
      pageCursor = null;
    }
    
    error = '';
    
    try {
      const params = {
        limit: 12,
        cursor: cursor || (reset ? null : pageCursor),
        search: searchQuery.trim() || undefined
      };
      
      const { items, pagination } = await fetchCachedContent(selectedType, params);
      
      if (reset) {
        contentItems = items;
      } else {
        contentItems = [...contentItems, ...items];
      }
      
      hasMore = pagination.hasNextPage;
      pageCursor = pagination.endCursor;
    } catch (e) {
      error = e?.message || 'Failed to load content.';
    } finally {
      isLoading = false;
    }
  }
  
  // Debounced search
  let searchCancelFn = null;
  function onSearchInput(e) {
    searchQuery = e.target.value;
    if (searchCancelFn) searchCancelFn();
    
    isLoading = true;
    error = '';
    
    searchCancelFn = searchContentWithDebounce(
      [selectedType],
      { limit: 12, search: searchQuery.trim() },
      (results, err) => {
        isLoading = false;
        if (err) {
          error = err.message || 'Search failed.';
          contentItems = [];
        } else {
          const typeResults = results[selectedType] || { items: [], pagination: { hasNextPage: false, endCursor: null } };
          contentItems = typeResults.items;
          hasMore = typeResults.pagination.hasNextPage;
          pageCursor = typeResults.pagination.endCursor;
        }
      }
    );
  }
  
  // Watch selectedType and load content
  $effect(() => {
    if (selectedType) {
      loadContentItems({ reset: true });
    }
  });
  
  // Select content type
  function selectContentType(type) {
    selectedType = type;
    searchQuery = '';
    loadContentItems({ reset: true });
  }
  
  // Select module and dispatch event
  function selectModule(item) {
    dispatch('select', {
      id: item.id,
      type: selectedType,
      title: item.title,
      excerpt: item.excerpt || item.summary || '',
      url: item.url,
      publishedAt: item.publishedAt,
      source: item.source,
      metadata: item.metadata
    });
  }
  
  // Close selector
  function close() {
    dispatch('close');
  }
  
  // Load more items
  function loadMoreItems() {
    if (hasMore && !isLoading) {
      loadContentItems({ reset: false, cursor: pageCursor });
    }
  }
  
  // Filter out excluded items
  const filteredContentItems = $derived(() => 
    contentItems.filter(item => !excludeIds.includes(item.id))
  );
</script>

{#if open}
  <div class="module-selector-overlay" transition:fade={{ duration: 200 }}>
    <div class="module-selector" transition:fly={{ y: 50, duration: 300 }}>
      <!-- Header -->
      <div class="selector-header">
        <h2>Select Module</h2>
        {#if targetPosition}
          <p class="position-info">
            Position: {targetPosition.x}, {targetPosition.y} 
            ({targetPosition.w}×{targetPosition.h})
          </p>
        {/if}
        <Button variant="ghost" size="sm" onclick={close}>
          <Icon icon={X} size={20} />
        </Button>
      </div>
      
      <!-- Content Type Selection -->
      {#if !selectedType}
        <div class="type-selection">
          <h3>Choose Content Type</h3>
          <div class="type-grid">
            {#each contentTypes as type}
              <button 
                class="type-card"
                onclick={() => selectContentType(type.id)}
              >
                <Icon icon={type.icon} size={24} />
                <span>{type.label}</span>
              </button>
            {/each}
          </div>
        </div>
      {:else}
        <!-- Content Selection -->
        <div class="content-selection">
          <!-- Back and Search -->
          <div class="selection-header">
            <Button variant="ghost" size="sm" onclick={() => selectedType = ''}>
              ← Back to Types
            </Button>
            <div class="search-container">
              <Icon icon={Search} size={16} />
              <Input
                type="text"
                placeholder="Search {contentTypes.find(t => t.id === selectedType)?.label.toLowerCase()}..."
                value={searchQuery}
                oninput={onSearchInput}
              />
            </div>
          </div>
          
          <!-- Content Grid -->
          <div class="content-grid">
            {#if isLoading && contentItems.length === 0}
              <div class="loading-state">
                <p>Loading content...</p>
              </div>
            {:else if error}
              <div class="error-state">
                <p>Error: {error}</p>
                <Button size="sm" onclick={() => loadContentItems({ reset: true })}>
                  Retry
                </Button>
              </div>
            {:else if filteredContentItems.length === 0}
              <div class="empty-state">
                <p>No content found</p>
              </div>
            {:else}
              {#each filteredContentItems as item (item.id)}
                <div class="content-item" onclick={() => selectModule(item)}>
                  <div class="item-header">
                    <h4>{item.title}</h4>
                    <span class="item-source">{item.source?.name || 'Unknown'}</span>
                  </div>
                  <p class="item-excerpt">
                    {item.excerpt || item.summary || 'No description available'}
                  </p>
                  <div class="item-meta">
                    <span class="item-date">
                      {item.publishedAt ? new Date(item.publishedAt).toLocaleDateString() : ''}
                    </span>
                  </div>
                </div>
              {/each}
              
              {#if hasMore}
                <div class="load-more">
                  <Button 
                    variant="outline" 
                    onclick={loadMoreItems}
                    disabled={isLoading}
                  >
                    {isLoading ? 'Loading...' : 'Load More'}
                  </Button>
                </div>
              {/if}
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .module-selector-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }
  
  .module-selector {
    background-color: hsl(var(--background));
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
    width: 100%;
    max-width: 800px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  
  .selector-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
  }
  
  .selector-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
  }
  
  .position-info {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
  }
  
  .type-selection {
    padding: 1.5rem;
  }
  
  .type-selection h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 500;
  }
  
  .type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
  }
  
  .type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background-color: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .type-card:hover {
    border-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.05);
  }
  
  .content-selection {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
  }
  
  .selection-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
  }
  
  .search-container {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
  }
  
  .search-container :global(svg) {
    position: absolute;
    left: 0.75rem;
    color: hsl(var(--muted-foreground));
    z-index: 1;
  }
  
  .search-container :global(input) {
    padding-left: 2.5rem;
  }
  
  .content-grid {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  
  .content-item {
    background-color: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .content-item:hover {
    border-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.05);
  }
  
  .item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.5rem;
  }
  
  .item-header h4 {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.3;
    flex: 1;
  }
  
  .item-source {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    white-space: nowrap;
  }
  
  .item-excerpt {
    margin: 0 0 0.75rem 0;
    font-size: 0.8rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  .item-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .item-date {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
  }
  
  .loading-state,
  .error-state,
  .empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 2rem;
    color: hsl(var(--muted-foreground));
  }
  
  .load-more {
    grid-column: 1 / -1;
    text-align: center;
    padding: 1rem;
  }
  
  @media (max-width: 768px) {
    .module-selector-overlay {
      padding: 0.5rem;
    }
    
    .module-selector {
      max-height: 95vh;
      border-radius: 1rem 1rem 0 0;
    }
    
    .content-grid {
      grid-template-columns: 1fr;
      gap: 0.75rem;
    }
    
    .selection-header {
      flex-direction: column;
      align-items: stretch;
      gap: 0.75rem;
    }
    
    .type-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.75rem;
    }
    
    .type-card {
      padding: 0.75rem;
    }
    
    .content-item {
      padding: 0.75rem;
    }
    
    .item-header h4 {
      font-size: 0.8rem;
    }
    
    .item-excerpt {
      font-size: 0.75rem;
      -webkit-line-clamp: 2;
    }
  }
</style> 