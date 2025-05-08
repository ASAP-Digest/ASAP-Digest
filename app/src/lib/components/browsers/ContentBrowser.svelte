<script>
  import { onMount } from 'svelte';
  import FilterPanel from '$lib/components/selectors/FilterPanel.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Card, CardContent, CardFooter, CardHeader } from '$lib/components/ui/card';
  import { Skeleton } from '$lib/components/ui/skeleton';
  import { Tabs, TabsContent, TabsList, TabsTrigger } from '$lib/components/ui/tabs';
  import { Badge } from '$lib/components/ui/badge';
  import { Pagination } from '$lib/components/ui/pagination';
  import { Checkbox } from '$lib/components/ui/checkbox';
  import { fetchContent, fetchContentTypes } from '$lib/api/content-fetcher.js';
  import { selectedItems } from '$lib/stores/selected-items-store.js';
  import { formatDistance } from 'date-fns';
  import { Grid, List, Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  /**
   * @typedef {import('$lib/api/content-fetcher').ContentItem} ContentItem
   * @typedef {import('$lib/components/selectors/FilterPanel.svelte').FilterOptions} FilterOptions
   */
  
  // Tracking selected items in the current view
  let currentlySelectedItems = $state(new Set());
  
  // Subscribe to the selected items store
  onMount(() => {
    const unsubscribe = selectedItems.subscribe(items => {
      currentlySelectedItems = new Set(items.map(item => item.id));
    });
    
    // Initial content load
    loadContent();
    
    return () => {
      unsubscribe();
    };
  });
  
  /** @type {FilterOptions} */
  let currentFilters = {
    search: '',
    types: ['article', 'podcast', 'financial', 'social'],
    categories: [],
    sources: [],
    dateFrom: null,
    dateTo: null,
    onlyFresh: false,
    limit: 12
  };
  
  /** @type {'grid' | 'list'} */
  let viewMode = $state('grid');
  
  /** @type {ContentItem[]} */
  let contentItems = $state([]);
  
  /** @type {boolean} */
  let isLoading = $state(false);
  
  /** @type {boolean} */
  let hasError = $state(false);
  
  /** @type {string} */
  let errorMessage = $state('');
  
  /** @type {number} */
  let currentPage = $state(1);
  
  /** @type {number} */
  let totalPages = $state(1);
  
  /** @type {string|null} */
  let nextCursor = $state(null);
  
  /** @type {boolean} */
  let hasNextPage = $state(false);
  
  /** @type {boolean} */
  let selectAllChecked = $state(false);
  
  /** @type {string[]} */
  let availableContentTypes = $state([]);
  
  /** @type {string} */
  let activeTab = $state('all');
  
  /**
   * Load content based on current filters and pagination
   */
  async function loadContent() {
    try {
      isLoading = true;
      hasError = false;
      errorMessage = '';
      
      // For the first page, reset the cursor
      if (currentPage === 1) {
        nextCursor = null;
      }
      
      // If not already fetched, load available content types
      if (availableContentTypes.length === 0) {
        try {
          const types = await fetchContentTypes();
          availableContentTypes = types;
        } catch (error) {
          console.error('Failed to fetch content types:', error);
          // Default types if fetch fails
          availableContentTypes = ['article', 'podcast', 'financial', 'social'];
        }
      }
      
      // Prepare filters for API
      const apiFilters = {
        ...currentFilters,
        cursor: nextCursor,
        // If a specific tab is active (not 'all'), only get that content type
        types: activeTab !== 'all' ? [activeTab] : currentFilters.types
      };
      
      // Fetch content items
      const result = await fetchContent(apiFilters);
      
      // Update state with results
      contentItems = result.items;
      hasNextPage = result.hasNextPage;
      nextCursor = result.nextCursor;
      totalPages = result.totalPages || 1;
      
      // Reset selection state for the new content
      currentlySelectedItems.clear();
      selectAllChecked = false;
      
      // Update currently selected items based on the global selection
      updateCurrentSelections();
      
    } catch (error) {
      console.error('Error loading content:', error);
      hasError = true;
      errorMessage = error.message || 'Failed to load content. Please try again.';
      contentItems = [];
    } finally {
      isLoading = false;
    }
  }
  
  /**
   * Handle filter changes from FilterPanel
   * @param {CustomEvent<FilterOptions>} event
   */
  function handleFilterChange(event) {
    currentFilters = event.detail;
    currentPage = 1; // Reset to first page
    loadContent();
  }
  
  /**
   * Handle tab changes
   * @param {string} tabValue
   */
  function handleTabChange(tabValue) {
    activeTab = tabValue;
    currentPage = 1; // Reset to first page
    loadContent();
  }
  
  /**
   * Go to a specific page
   * @param {number} page
   */
  function goToPage(page) {
    if (page === currentPage) return;
    currentPage = page;
    loadContent();
  }
  
  /**
   * Go to the next page
   */
  function nextPage() {
    if (!hasNextPage) return;
    currentPage += 1;
    loadContent();
  }
  
  /**
   * Go to the previous page
   */
  function prevPage() {
    if (currentPage <= 1) return;
    currentPage = currentPage - 1;
    loadContent();
  }
  
  /**
   * Toggle selection of a content item
   * @param {ContentItem} item
   */
  function toggleItemSelection(item) {
    if (currentlySelectedItems.has(item.id)) {
      currentlySelectedItems.delete(item.id);
      selectedItems.remove(item.id);
    } else {
      currentlySelectedItems.add(item.id);
      selectedItems.add(item);
    }
    
    // Update the select all checkbox state
    updateSelectAllState();
  }
  
  /**
   * Update the selection state based on global selection
   */
  function updateCurrentSelections() {
    currentlySelectedItems.clear();
    
    // Check each content item against the global selection
    for (const item of contentItems) {
      if (selectedItems.isSelected(item.id)) {
        currentlySelectedItems.add(item.id);
      }
    }
    
    // Update the select all checkbox state
    updateSelectAllState();
  }
  
  /**
   * Update the select all checkbox state
   */
  function updateSelectAllState() {
    // If there are no items, the checkbox should be unchecked
    if (contentItems.length === 0) {
      selectAllChecked = false;
      return;
    }
    
    // If all items are selected, check the box
    selectAllChecked = contentItems.every(item => currentlySelectedItems.has(item.id));
  }
  
  /**
   * Toggle selection of all items in the current view
   */
  function toggleSelectAll() {
    if (selectAllChecked) {
      // Unselect all items
      for (const item of contentItems) {
        if (currentlySelectedItems.has(item.id)) {
          currentlySelectedItems.delete(item.id);
          selectedItems.remove(item.id);
        }
      }
    } else {
      // Select all items
      for (const item of contentItems) {
        if (!currentlySelectedItems.has(item.id)) {
          currentlySelectedItems.add(item.id);
          selectedItems.add(item);
        }
      }
    }
    
    // Toggle the checkbox state
    selectAllChecked = !selectAllChecked;
  }
  
  /**
   * Get humanized time since publication
   * @param {string} dateStr - ISO date string
   * @returns {string} Humanized time
   */
  function getTimeSince(dateStr) {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      return formatDistance(date, new Date(), { addSuffix: true });
    } catch (error) {
      return '';
    }
  }
  
  /**
   * Format the timestamp for display
   * @param {string} dateStr - ISO date string
   * @returns {string} Formatted date
   */
  function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      return date.toLocaleDateString(undefined, { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch (error) {
      return '';
    }
  }
  
  /**
   * Get the badge color for a content type
   * @param {string} type - Content type
   * @returns {string} Badge variant
   */
  function getTypeStyle(type) {
    switch (type) {
      case 'article':
        return 'bg-[hsl(var(--primary))]';
      case 'podcast':
        return 'bg-[hsl(var(--accent))]';
      case 'financial':
        return 'bg-[hsl(var(--success))]';
      case 'social':
        return 'bg-[hsl(var(--link))]';
      default:
        return 'bg-muted';
    }
  }
</script>

<div class="container mx-auto px-4 py-8">
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-[300px_1fr]">
    <!-- Left Sidebar with Filters -->
    <div class="flex flex-col gap-4">
      <FilterPanel on:filter={handleFilterChange} />
    </div>
    
    <!-- Right Content Area -->
    <div class="flex flex-col gap-4">
      <!-- Content Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-semibold">Content Browser</h2>
          <p class="text-muted-foreground">
            Browse and select content for your digest
          </p>
        </div>
        
        <div class="flex gap-2">
          <!-- View Mode Toggle -->
          <div class="flex">
            <Button 
              variant={viewMode === 'grid' ? 'default' : 'outline'} 
              size="icon" 
              class="rounded-r-none" 
              onclick={() => viewMode = 'grid'}
            >
              <Icon icon={Grid} class="h-4 w-4" />
              <span class="sr-only">Grid view</span>
            </Button>
            <Button 
              variant={viewMode === 'list' ? 'default' : 'outline'} 
              size="icon" 
              class="rounded-l-none" 
              onclick={() => viewMode = 'list'}
            >
              <Icon icon={List} class="h-4 w-4" />
              <span class="sr-only">List view</span>
            </Button>
          </div>
          
          <!-- Select All -->
          <div class="flex items-center ml-4">
            <Checkbox id="selectAll" checked={selectAllChecked} onclick={toggleSelectAll} />
            <label for="selectAll" class="ml-2 text-sm">Select All</label>
          </div>
        </div>
      </div>
      
      <!-- Content Type Tabs -->
      <Tabs value={activeTab} class="w-full">
        <TabsList>
          <TabsTrigger value="all" onclick={() => handleTabChange('all')}>All</TabsTrigger>
          {#each availableContentTypes as type}
            <TabsTrigger value={type} onclick={() => handleTabChange(type)}>
              {type.charAt(0).toUpperCase() + type.slice(1)}
            </TabsTrigger>
          {/each}
        </TabsList>
      </Tabs>
      
      <!-- Content Display -->
      {#if isLoading}
        <!-- Loading State -->
        <div class="col-span-full flex justify-center items-center h-64">
          <Icon icon={Loader2} class="h-8 w-8 animate-spin text-primary" />
        </div>
      {:else if hasError}
        <!-- Error State -->
        <div class="flex flex-col items-center justify-center py-12">
          <div class="text-destructive text-center">
            <p class="text-lg font-semibold">Something went wrong</p>
            <p class="mt-2">{errorMessage}</p>
            <Button class="mt-4" onclick={loadContent}>Try Again</Button>
          </div>
        </div>
      {:else if contentItems.length === 0}
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-12">
          <p class="text-lg font-semibold">No content found</p>
          <p class="mt-2 text-muted-foreground">Try adjusting your filters</p>
        </div>
      {:else}
        <!-- Grid View -->
        {#if viewMode === 'grid'}
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {#each contentItems as item (item.id)}
              <Card class="flex h-full flex-col">
                <div class="relative">
                  <!-- Content Type Badge -->
                  <Badge class={`absolute left-2 top-2 ${getTypeStyle(item.type)}`}>
                    {item.type.charAt(0).toUpperCase() + item.type.slice(1)}
                  </Badge>
                  
                  <!-- Selection Checkbox -->
                  <div class="absolute right-2 top-2 z-10">
                    <Checkbox 
                      checked={currentlySelectedItems.has(item.id)} 
                      onclick={() => toggleItemSelection(item)}
                    />
                  </div>
                  
                  <!-- Item Image -->
                  {#if item.image}
                    <div class="aspect-video w-full overflow-hidden">
                      <img 
                        src={item.image} 
                        alt={item.title} 
                        class="h-full w-full object-cover"
                      />
                    </div>
                  {:else}
                    <div class="aspect-video w-full bg-muted flex items-center justify-center">
                      <span class="text-muted-foreground">No image</span>
                    </div>
                  {/if}
                </div>
                
                <CardHeader class="pb-2">
                  <h3 class="font-semibold line-clamp-2">{item.title}</h3>
                </CardHeader>
                
                <CardContent class="flex-grow">
                  <p class="text-sm text-muted-foreground line-clamp-3">{item.summary || 'No summary available'}</p>
                </CardContent>
                
                <CardFooter class="flex flex-col items-start pt-2">
                  <div class="flex w-full items-center justify-between text-xs text-muted-foreground">
                    <span>{item.source || 'Unknown source'}</span>
                    <span title={formatDate(item.timestamp)}>{getTimeSince(item.timestamp)}</span>
                  </div>
                </CardFooter>
              </Card>
            {/each}
          </div>
        {:else}
          <!-- List View -->
          <div class="flex flex-col gap-2">
            {#each contentItems as item (item.id)}
              <div class="flex gap-4 border rounded-md p-3 hover:bg-accent/10">
                <!-- Selection Checkbox -->
                <div class="self-center">
                  <Checkbox 
                    checked={currentlySelectedItems.has(item.id)} 
                    onclick={() => toggleItemSelection(item)}
                  />
                </div>
                
                <!-- Item Image (smaller in list view) -->
                {#if item.image}
                  <div class="h-16 w-24 flex-shrink-0 overflow-hidden rounded-md">
                    <img 
                      src={item.image} 
                      alt={item.title} 
                      class="h-full w-full object-cover"
                    />
                  </div>
                {:else}
                  <div class="h-16 w-24 flex-shrink-0 overflow-hidden rounded-md bg-muted flex items-center justify-center">
                    <span class="text-xs text-muted-foreground">No image</span>
                  </div>
                {/if}
                
                <!-- Content Info -->
                <div class="flex-grow">
                  <div class="flex items-center gap-2">
                    <Badge class={`${getTypeStyle(item.type)}`}>
                      {item.type.charAt(0).toUpperCase() + item.type.slice(1)}
                    </Badge>
                    <span class="text-xs text-muted-foreground">{item.source || 'Unknown source'}</span>
                  </div>
                  
                  <h3 class="font-semibold mt-1">{item.title}</h3>
                  <p class="text-sm text-muted-foreground line-clamp-1">{item.summary || 'No summary available'}</p>
                  
                  <div class="text-xs text-muted-foreground mt-1">
                    <span title={formatDate(item.timestamp)}>{getTimeSince(item.timestamp)}</span>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
        
        <!-- Pagination -->
        {#if totalPages > 1 || hasNextPage}
          <div class="flex items-center justify-between pt-4">
            <div class="text-sm text-muted-foreground">
              Page {currentPage} of {totalPages || '?'}
            </div>
            <div class="flex gap-1">
              <Button 
                variant="outline" 
                size="sm" 
                disabled={currentPage <= 1} 
                onclick={prevPage}
              >
                Previous
              </Button>
              <Button 
                variant="outline" 
                size="sm" 
                disabled={!hasNextPage} 
                onclick={nextPage}
              >
                Next
              </Button>
            </div>
          </div>
        {/if}
      {/if}
      
      <!-- Selected Items Summary -->
      {#if selectedItems.length > 0}
        <div class="mt-4 bg-accent/10 p-4 rounded-md">
          <div class="flex justify-between">
            <div>
              <span class="font-semibold">{selectedItems.length} item{selectedItems.length !== 1 ? 's' : ''} selected</span>
              <p class="text-sm text-muted-foreground">Selected items will be available for your digest</p>
            </div>
            <div>
              <Button 
                onclick={() => window.location.href = '/digest/edit'}
              >
                Create Digest
              </Button>
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>
</div> 