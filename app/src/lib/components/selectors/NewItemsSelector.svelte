<!--
  NewItemsSelector.svelte
  ----------------------
  PRODUCTION CONTENT SELECTOR COMPONENT

  PROTOCOL COMPLIANCE HEADER
  - roadmap-syntax-validation-protocol v1.2
  - work-session-management-protocol
  - testing-verification-protocol
  - update-memory
  - server-memory-rules
  - All props, state, and logic are documented and validated per protocol.

  This is the main content selection component used by the app (imported in GlobalFAB.svelte).
  - Provides a floating action button (FAB) for launching content selection.
  - Allows users to select content type (article, podcast, etc.) and pick items from a grid.
  - Integrates with the persistent selectedItems store for local-first selection.
  - Fetches real data from the GraphQL API via content-fetcher.js (with caching, debounced search, pagination).
  - Handles loading, error, and empty states.
  - UI/UX: Responsive, accessible, and protocol-compliant.

  NOTE: NewItemsSelector2.svelte is a test/demo version and is NOT used in production.
-->
<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { fly, slide, fade } from 'svelte/transition';
  import Dialog from '$lib/components/ui/dialog/dialog.svelte';
  import DialogContent from '$lib/components/ui/dialog/dialog-content.svelte';
  import DialogHeader from '$lib/components/ui/dialog/dialog-header.svelte';
  import DialogTitle from '$lib/components/ui/dialog/dialog-title.svelte';
  import Button from '$lib/components/ui/button/button.svelte';
  import Input from '$lib/components/ui/input/input.svelte';
  import { Check, Plus, X, Search, FileText, Headphones, Key, DollarSign, Twitter, MessageSquare, Calendar, LineChart } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { getContentTypeColor } from '$lib/utils/color-utils.js';
  import { fetchCachedContent, searchContentWithDebounce, createContentManager } from '$lib/api/content-fetcher.js';
  import { selectedItems } from '$lib/stores/selected-items-store.js';
  import { get } from 'svelte/store';
  import FilterPanel from './FilterPanel.svelte';
  import { useSession } from '$lib/auth-client.js';
  
  // Create content manager instance
  const contentManager = createContentManager();
  
  const dispatch = createEventDispatcher();
  
  // Props
  let { 
    startOpen = false,           // Whether to open directly in content selection mode
    initialType = '',            // Initial content type to select
    showFab = true,              // Whether to show the FAB (false when used with GlobalFAB)
    excludeIds = [],              // List of IDs to exclude from selection
    allowMultiple = true,         // Allow multiple selections
    onSelect = undefined,         // Optional callback for selection
    initiallySelected = [],       // List of items to initially select
    // NEW: Digest creation mode props
    mode = 'standard',           // 'standard' | 'digest-builder' | 'module-selector' | 'digest-creation'
    targetPosition = null,       // Grid position for digest building (x, y, w, h)
    onModuleSelect = undefined,  // Callback for module selection in digest mode
    singleSelect = false,        // Force single selection mode
    showPositionInfo = false,    // Show grid position info in header
    compactMode = false,         // Use compact layout for mobile/modal
  } = $props();
  
  // State variables using Svelte 5 runes
  let selectedType = $state(initialType);
  let searchQuery = $state('');
  let contentItems = $state([]);
  let isLoading = $state(false);
  let error = $state('');
  let hasMore = $state(false);
  let pageCursor = $state(null);
  let showFlyout = $state(false);
  let fabPosition = $state('corner'); // 'corner' or 'center'
  let isSidebarCollapsed = $state(false);
  let isLoadingMore = $state(false);
  let detailView = $state(false);
  let detailItem = $state(null);
  let filterState = $state({ dateFrom: '', dateTo: '', categories: [], source: '' });
  let addLoading = $state(false);
  let addError = $state('');
  let addSuccess = $state(false);
  let addResults = $state([]);
  
  // Define content type icons and colors
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
  
  // Load stored FAB position preference and sidebar state
  onMount(() => {
    const storedPosition = localStorage.getItem('fab-position');
    if (storedPosition === 'center' || storedPosition === 'corner') {
      fabPosition = storedPosition;
    }
    
    // If startOpen is true, set the initial content type
    if (startOpen && initialType) {
      selectedType = initialType;
    }

    // Check sidebar state
    if (typeof window !== 'undefined' && window.localStorage) {
      const sidebarState = localStorage.getItem('sidebar-collapsed');
      isSidebarCollapsed = sidebarState === 'true';
    }

    // Add listener for sidebar state changes
    const handleSidebarChange = () => {
      isSidebarCollapsed = document.body.classList.contains('sidebar-collapsed');
    };

    const observer = new MutationObserver(handleSidebarChange);
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    if (initiallySelected && initiallySelected.length > 0) {
      initiallySelected.forEach(sel => {
        if (!get(selectedItems).some(i => i.id === sel.id && i.type === sel.type)) {
          selectedItems.add(sel);
        }
      });
    }

    return () => {
      observer.disconnect();
    };
  });
  
  // Fetch content items for the selected type
  async function loadContentItems({ reset = true, cursor = null } = {}) {
    if (!selectedType) return;
    if (reset) {
      isLoading = true;
      isLoadingMore = false;
    } else {
      isLoadingMore = true;
    }
    error = '';
    if (reset) {
      contentItems = [];
      pageCursor = null;
    }
    try {
      const params = {
        limit: 12,
        cursor: cursor || (reset ? null : pageCursor),
        search: searchQuery.trim() || undefined,
        dateFrom: filterState.dateFrom || undefined,
        dateTo: filterState.dateTo || undefined,
        categories: filterState.categories && filterState.categories.length > 0 ? filterState.categories : undefined,
        source: filterState.source || undefined
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
      isLoadingMore = false;
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
  
  // Selection logic using persistent store
  function toggleItemSelection(item) {
    // Handle digest builder mode differently
    if (mode === 'digest-builder' || mode === 'module-selector') {
      handleModuleSelection(item);
      return;
    }
    
    if (allowMultiple && !singleSelect) {
    selectedItems.toggle(item);
    } else {
      // Single select mode: clear all, then add
      selectedItems.clear();
      selectedItems.add(item);
    }
    if (onSelect) {
      onSelect((allowMultiple && !singleSelect) ? get(selectedItems) : get(selectedItems)[0]);
    }
  }

  // Handle module selection for digest creation
  function handleModuleSelection(item) {
    const moduleData = {
      id: item.id,
      type: selectedType,
      title: item.title,
      excerpt: item.excerpt || item.summary || '',
      url: item.url,
      publishedAt: item.publishedAt,
      source: item.source,
      image: item.image,
      metadata: item.metadata || {},
      targetPosition: targetPosition
    };

    if (onModuleSelect) {
      onModuleSelect(moduleData);
    }
    
    // Close the selector after module selection
    if (mode === 'module-selector') {
      resetAndClose();
    }
  }
  
  // Check if an item is selected
  function isSelected(item) {
    return get(selectedItems).some(i => i.id === item.id && i.type === item.type);
  }
  
  // Add selected items to digest and close selector
  async function addSelectedItems() {
    addLoading = true;
    addError = '';
    addSuccess = false;
    addResults = [];
    try {
      const session = useSession();
      const userId = session?.user?.id;
      const digestId = session?.activeDigestId || null;
      if (!userId) {
        addError = 'User not authenticated.';
        addLoading = false;
        return;
      }
      const items = get(selectedItems);
      const result = await contentManager.ingestDigestItems(items, userId, digestId);
      if (result.success) {
        addSuccess = true;
        addResults = result.results;
        selectedItems.clear();
        setTimeout(() => {
          addSuccess = false;
          showFlyout = false;
        }, 1200);
      } else {
        addError = result.errors?.[0]?.error || 'Unknown error';
      }
    } catch (e) {
      addError = e.message || 'Unknown error';
    } finally {
      addLoading = false;
    }
  }

  // Start digest creation with selected item - Phase 2 Enhanced
  function startDigestWithSelected() {
    const items = get(selectedItems);
    if (items.length === 1) {
      const selectedItem = items[0];
      startDigestCreationFlow(selectedItem);
    }
  }

  // Start digest creation with specific item - Phase 2 Enhanced
  function startDigestWithItem(item) {
    startDigestCreationFlow(item);
  }

  // Phase 2: Enhanced digest creation flow
  function startDigestCreationFlow(preSelectedItem) {
    // Store the pre-selected item in sessionStorage for the digest creation page
    if (preSelectedItem) {
      sessionStorage.setItem('digest-preselected-module', JSON.stringify({
        id: preSelectedItem.id,
        type: selectedType,
        title: preSelectedItem.title,
        excerpt: preSelectedItem.excerpt || preSelectedItem.summary || '',
        url: preSelectedItem.url,
        publishedAt: preSelectedItem.publishedAt,
        source: preSelectedItem.source,
        image: preSelectedItem.image,
        metadata: preSelectedItem.metadata || {}
      }));
    }
    
    // Close the current selector
    resetAndClose();
    
    // Navigate to the enhanced digest creation page
    window.location.href = '/digest/create';
  }
  
  // Close the content type selection flyout
  function closeFlyout() {
    showFlyout = false;
  }
  
  // Select a content type and close the flyout
  function selectContentType(type) {
    selectedType = type;
    showFlyout = false;
    searchQuery = '';
    loadContentItems({ reset: true });
  }
  
  // Reset selection and close component
  function resetAndClose() {
    selectedType = '';
    showFlyout = false;
    dispatch('close');
  }
  
  // Toggle FAB position and save preference
  function toggleFabPosition() {
    fabPosition = fabPosition === 'corner' ? 'center' : 'corner';
    localStorage.setItem('fab-position', fabPosition);
  }
  
  // Get the appropriate color for a content type
  function getTypeColor(typeId) {
    const type = contentTypes.find(t => t.id === typeId);
    return type ? type.color : 'gray';
  }

  function loadMoreItems() {
    if (hasMore && !isLoadingMore) {
      loadContentItems({ reset: false, cursor: pageCursor });
    }
  }

  function onFilterChange(e) {
    filterState = e.detail;
    // Trigger a new fetch with filters
    loadContentItems({ reset: true });
  }

  // --- [ Consolidation: Exclude items by excludeIds ] ---
  // Svelte 5 runes: $derived must be called with a single function argument, dependencies are auto-tracked.
  const filteredContentItems = $derived(() => contentItems.filter(item => !excludeIds.includes(item.id)));
</script>

<!-- Content Type Selection Floating Action Button -->
{#if !selectedType && showFab}
  <div class="selector-fab {fabPosition === 'center' ? 'center' : 'corner'} {isSidebarCollapsed ? 'sidebar-collapsed' : ''}" style={fabPosition === 'corner' && !isSidebarCollapsed ? 'right: calc(1.5rem + 240px);' : ''}>
    <!-- Main FAB Button -->
    <button 
      class="selector-fab-button"
      onclick={() => showFlyout = !showFlyout}
    >
      <Icon icon={Plus} size={24} />
    </button>
    
    <!-- Position Toggle (small button) -->
    <button 
      class="selector-fab-position-toggle"
      onclick={toggleFabPosition}
      title="Toggle position"
    >
      <Icon icon={fabPosition === 'corner' ? Calendar : LineChart} size={12} />
    </button>
    
    <!-- Flyout Menu for Content Type Selection -->
    {#if showFlyout}
      <div transition:fly={{y: 20, duration: 200}} class="selector-flyout">
        {#each contentTypes as type}
          <button 
            class="selector-flyout-button"
            onclick={() => selectContentType(type.id)}
          >
            <div class="selector-flyout-icon">
              <Icon icon={type.icon} size={20} color="currentColor" class="text-[hsl(var(--primary))]" />
            </div>
            <span class="selector-flyout-label">{type.label}</span>
          </button>
        {/each}
        
        <button onclick={closeFlyout} class="selector-flyout-close">
          <Icon icon={X} size={16} />
        </button>
      </div>
    {/if}
  </div>
{/if}

{#if selectedType || startOpen}
  <!-- Visual Grid Selection Dialog -->
  <Dialog open={true} onClose={resetAndClose}>
    <DialogContent class="selector-dialog-content">
      <DialogHeader>
        <div class="selector-type-header flex items-center gap-3 mb-2">
          {#if selectedType}
            {#if contentTypes.find(t => t.id === selectedType)}
              {@const type = contentTypes.find(t => t.id === selectedType)}
              <div class="selector-type-badge flex items-center gap-2 px-3 py-1 rounded-full text-white font-semibold text-sm" style="background-color: var(--type-color, hsl(var(--primary))); --type-color: var(--color-{type.color}, hsl(var(--primary)));">
                <Icon icon={type.icon} size={18} />
                <span>{type.label}</span>
              </div>
            {/if}
          {/if}
        </div>
        <DialogTitle class="text-[1.25rem] font-medium flex items-center gap-[0.5rem]">
          {#if selectedType}
            {#if contentTypes.find(t => t.id === selectedType)}
              {@const type = contentTypes.find(t => t.id === selectedType)}
              <span>
                {#if mode === 'module-selector'}
                  Select Module for Grid Position
                {:else if mode === 'digest-builder'}
                  Add {type.label} to Digest
                {:else}
                  Select {type.label} Content
                {/if}
              </span>
            {/if}
          {:else}
            <span>
              {#if mode === 'module-selector' || mode === 'digest-builder'}
                Choose Content Type
              {:else}
                Select Content
              {/if}
            </span>
          {/if}
          {#if showPositionInfo && targetPosition}
            <span class="text-sm text-muted-foreground ml-2">
              (Position: {targetPosition.x}, {targetPosition.y} - {targetPosition.w}×{targetPosition.h})
            </span>
          {/if}
        </DialogTitle>
      </DialogHeader>
      <!-- Type Selection (when opened directly without a type) -->
      {#if !selectedType}
        <div class="selector-grid">
          {#each contentTypes as type}
            <button 
              class="selector-grid-button"
              onclick={() => selectContentType(type.id)}
            >
              <div class="selector-grid-icon">
                <Icon icon={type.icon} size={24} color="currentColor" class="text-[hsl(var(--primary))]" />
              </div>
              <span class="selector-grid-label">{type.label}</span>
            </button>
          {/each}
        </div>
      {:else}
        <!-- Filter Panel -->
        <FilterPanel
          categories={[...new Set(contentItems.map(i => i.category).filter(Boolean))]}
          sources={[...new Set(contentItems.map(i => i.source).filter(Boolean))]}
          initialFilters={filterState}
          on:filter={onFilterChange}
        />
        
        <!-- Search Bar -->
        <div class="selector-search">
          <div class="selector-search-wrapper">
            <div class="selector-search-icon">
              <Icon icon={Search} size={16} />
            </div>
            <Input 
              value={searchQuery}
              oninput={onSearchInput} 
              placeholder="Search {contentTypes.find(t => t.id === selectedType)?.label || ''}..." 
              class="selector-search-input"
            />
          </div>
        </div>
        
        <!-- Content Grid -->
        <div class="selector-content-grid">
          {#each filteredContentItems as item (item.id)}
            <button 
              type="button"
              class="selector-content-item {isSelected(item) ? 'selected' : ''}"
              onclick={() => toggleItemSelection(item)}
            >
              <div class="selector-content-image-wrapper">
                <img src={item.image} alt={item.title} class="selector-content-image" />
                {#if isSelected(item)}
                  <div class="selector-content-selected-badge">
                    <Icon icon={Check} size={16} />
                  </div>
                {/if}
              </div>
              <div class="selector-content-details">
                <h3 class="selector-content-title">
                  <span 
                    style="cursor:pointer;text-decoration:underline;" 
                    role="button"
                    tabindex="0"
                    onclick={(e) => { e.stopPropagation(); detailItem = item; detailView = true; }}
                    onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); e.stopPropagation(); detailItem = item; detailView = true; }}}
                  >
                    {item.title}
                  </span>
                </h3>
                <p class="selector-content-excerpt">
                  {item.excerpt || (item.duration ? `Duration: ${item.duration}` : '')}
                </p>
                <div class="selector-content-source">
                  <span>{item.source}</span>
                </div>
                {#if item.ai_enhanced}
                  <div class="ai-badge">AI Enhanced</div>
                {/if}
                {#if item.classification}
                  <div class="item-classification">
                    <strong>Category:</strong> {item.classification.category || 'Unknown'}
                    {#if item.classification.confidence}
                      ({Math.round(item.classification.confidence * 100)}%)
                    {/if}
                  </div>
                {/if}
                <Button size="sm" variant="outline" style="margin-top:0.5rem;" onclick={(e) => { e.stopPropagation(); detailItem = item; detailView = true; }}>
                  View Details
                </Button>
              </div>
            </button>
          {/each}
          
          {#if filteredContentItems.length === 0}
            <div class="selector-content-empty">
              No content found. Try adjusting your search.
            </div>
          {/if}
          
          {#if hasMore}
            <div class="selector-content-loadmore">
              <Button variant="outline" onclick={loadMoreItems} disabled={isLoadingMore}>
                {#if isLoadingMore}
                  Loading...
                {:else}
                  Load More
                {/if}
              </Button>
            </div>
          {/if}
        </div>
        
        <!-- Action Buttons -->
        <div class="selector-actions-row">
          <div class="selector-actions-left">
            <Button variant="outline" onclick={() => selectedType = ''}>
              ← Back to Content Types
            </Button>
          </div>
          <div class="selector-actions-right">
            {#if mode === 'module-selector'}
              <!-- Module selector mode: just show instruction -->
              <span class="selector-count-text">Click a module to add to grid position</span>
            {:else if mode === 'digest-builder'}
              <!-- Digest builder mode: show selection count and add button -->
            <span class="selector-count-text">{get(selectedItems).length} selected</span>
              <Button 
                variant="default" 
                onclick={addSelectedItems} 
                disabled={get(selectedItems).length === 0 || addLoading}
              >
                {addLoading ? 'Adding...' : 'Add to Digest'}
              </Button>
            {:else}
              <!-- Standard mode: show all options -->
              <span class="selector-count-text">{get(selectedItems).length} selected</span>
              {#if get(selectedItems).length === 1}
                <Button 
                  variant="outline" 
                  onclick={startDigestWithSelected}
                  disabled={addLoading}
                >
                  Start Digest
                </Button>
              {/if}
            <Button 
              variant="default" 
              onclick={addSelectedItems} 
              disabled={get(selectedItems).length === 0 || addLoading}
            >
              {addLoading ? 'Adding...' : 'Add Selected'}
            </Button>
            {/if}
          </div>
        </div>
        
        <!-- Add Selected Feedback -->
        {#if addSuccess}
          <div class="selector-success-message">Items added to your digest!</div>
        {/if}
        {#if addError}
          <div class="selector-error-message">{addError}</div>
        {/if}
      {/if}
    </DialogContent>
  </Dialog>
{/if}

<!-- Detail Preview Modal -->
{#if detailView && detailItem}
  <Dialog open={true} onClose={() => { detailView = false; detailItem = null; }}>
    <DialogContent class="selector-detail-dialog-content">
      <DialogHeader>
        <DialogTitle class="text-lg font-semibold flex items-center gap-2">
          {#if contentTypes.find(t => t.id === detailItem.type)}
            {@const type = contentTypes.find(t => t.id === detailItem.type)}
            <Icon icon={type.icon} size={18} />
            <span>{detailItem.title}</span>
          {:else}
            {detailItem.title}
          {/if}
        </DialogTitle>
      </DialogHeader>
      <div class="selector-detail-meta mb-4 text-sm text-gray-500 flex gap-4">
        {#if detailItem.source}
          <span>Source: {detailItem.source}</span>
        {/if}
        {#if detailItem.formattedDate}
          <span>Date: {detailItem.formattedDate}</span>
        {/if}
      </div>
      <div class="selector-detail-body mb-4">
        {#if detailItem.excerpt}
          <p>{detailItem.excerpt}</p>
        {/if}
        {#if detailItem.summary && detailItem.summary !== detailItem.excerpt}
          <p class="mt-2">{detailItem.summary}</p>
        {/if}
        {#if detailItem.image}
          <img src={detailItem.image} alt={detailItem.title} class="selector-detail-image mt-4" style="max-width:100%;border-radius:0.5rem;" />
        {/if}
      </div>
      <div class="selector-detail-actions flex justify-between mt-6">
        <Button variant="outline" onclick={() => { detailView = false; detailItem = null; }}>
          ← Back
        </Button>
        <div class="flex gap-2">
          {#if mode === 'module-selector'}
            <Button 
              variant="default" 
              onclick={() => { toggleItemSelection(detailItem); detailView = false; detailItem = null; }}
            >
              Select This Module
            </Button>
          {:else if mode === 'digest-builder'}
        <Button 
          variant={isSelected(detailItem) ? 'outline' : 'default'} 
          onclick={() => { if (!isSelected(detailItem)) toggleItemSelection(detailItem); detailView = false; detailItem = null; }}
          disabled={isSelected(detailItem)}
        >
          {isSelected(detailItem) ? 'Added to Digest' : 'Add to Digest'}
        </Button>
          {:else}
            <Button 
              variant="outline" 
              onclick={() => startDigestWithItem(detailItem)}
            >
              Start Digest
            </Button>
            <Button 
              variant={isSelected(detailItem) ? 'outline' : 'default'} 
              onclick={() => { if (!isSelected(detailItem)) toggleItemSelection(detailItem); detailView = false; detailItem = null; }}
              disabled={isSelected(detailItem)}
            >
              {isSelected(detailItem) ? 'Added to Digest' : 'Add to Digest'}
            </Button>
          {/if}
        </div>
      </div>
    </DialogContent>
  </Dialog>
{/if}

<style>
  /* Base FAB styling */
  .selector-fab {
    position: fixed;
    bottom: 1.5rem;
    z-index: var(--z-fab);
    transition: all 0.3s var(--ease-out);
  }

  /* Corner positioning */
  .selector-fab.corner {
    right: 1.5rem;
  }

  /* Center positioning */
  .selector-fab.center {
    left: 50%;
    transform: translateX(-50%);
  }

  /* Adjust for sidebar on desktop */
  @media (min-width: 1024px) {
    /* When sidebar is expanded, shift FAB position to account for sidebar width */
    .selector-fab.corner:not(.sidebar-collapsed) {
      right: calc(1.5rem + 240px);
    }
  
    /* When sidebar is collapsed, only account for collapsed sidebar width */
    .selector-fab.corner.sidebar-collapsed {
      right: calc(1.5rem + 64px);
    }
  }

  /* Main FAB button */
  .selector-fab-button {
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 9999px;
    width: 3.5rem;
    height: 3.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transition: all 0.2s var(--ease-out);
  }

  .selector-fab-button:hover {
    background-color: hsl(var(--primary)/0.9);
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }

  /* Position toggle button */
  .selector-fab-position-toggle {
    position: absolute;
    left: -1.5rem;
    bottom: 0.25rem;
    background-color: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    border-radius: 9999px;
    width: 1.25rem;
    height: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.2s var(--ease-out);
  }

  .selector-fab-position-toggle:hover {
    background-color: hsl(var(--muted)/0.9);
  }

  /* Flyout menu */
  .selector-flyout {
    position: absolute;
    background-color: hsl(var(--background));
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    border: 1px solid hsl(var(--border));
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    min-width: 20rem;
    z-index: var(--z-fab-flyout);
  }

  /* Position flyout based on FAB position */
  .selector-fab.corner .selector-flyout {
    right: 0;
    bottom: 5rem;
  }

  .selector-fab.center .selector-flyout {
    bottom: 5rem;
    left: 50%;
    transform: translateX(-50%);
  }

  /* Flyout buttons */
  .selector-flyout-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    border-radius: var(--radius-lg);
    transition: background-color 0.2s var(--ease-out);
  }

  .selector-flyout-button:hover {
    background-color: hsl(var(--muted));
  }

  .selector-flyout-icon {
    width: 2.5rem;
    height: 2.5rem;
    background-color: hsl(var(--muted));
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.25rem;
  }

  .selector-flyout-label {
    font-size: 0.75rem;
  }

  .selector-flyout-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: hsl(var(--muted-foreground));
  }

  /* Dialog content styling */
  .selector-dialog-content {
    max-width: 64rem;
    padding: 1.5rem;
    z-index: var(--z-modal);
  }

  /* Type selection grid */
  .selector-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    padding: 1rem 0;
  }

  @media (min-width: 640px) {
    .selector-grid {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  .selector-grid-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border-radius: var(--radius-lg);
    border: 1px solid hsl(var(--border));
    transition: background-color 0.2s var(--ease-out);
  }

  .selector-grid-button:hover {
    background-color: hsl(var(--muted));
  }

  .selector-grid-icon {
    width: 3rem;
    height: 3rem;
    background-color: hsl(var(--muted));
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
  }

  .selector-grid-label {
    font-size: 0.875rem;
    font-weight: 500;
  }

  /* Search bar */
  .selector-search {
    margin-bottom: 1rem;
    display: flex;
  }

  .selector-search-wrapper {
    position: relative;
    flex-grow: 1;
  }

  .selector-search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: hsl(var(--muted-foreground));
  }

  .selector-search-input {
    width: 100%;
    padding-left: 2.25rem;
  }

  /* Content grid */
  .selector-content-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1rem;
    max-height: 60vh;
    overflow-y: auto;
    padding: 0.25rem;
  }

  @media (min-width: 640px) {
    .selector-content-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (min-width: 1024px) {
    .selector-content-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  .selector-content-item {
    background-color: hsl(var(--card));
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid hsl(var(--border));
    transition: all 0.2s var(--ease-out);
    cursor: pointer;
  }

  .selector-content-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }

  .selector-content-item.selected {
    box-shadow: 0 0 0 2px hsl(var(--primary));
  }

  .selector-content-image-wrapper {
    position: relative;
    background-color: hsl(var(--muted));
    aspect-ratio: 16 / 9;
  }

  .selector-content-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .selector-content-selected-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 9999px;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .selector-content-details {
    padding: 0.75rem;
  }

  .selector-content-title {
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .selector-content-excerpt {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-top: 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .selector-content-source {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
  }

  .selector-content-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem 0;
    color: hsl(var(--muted-foreground));
  }

  /* Action buttons */
  .selector-actions-row {
    margin-top: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .selector-actions-left {
    flex: 1 1 0%;
    display: flex;
    justify-content: flex-start;
  }

  .selector-actions-right {
    flex: 1 1 0%;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.75rem;
  }

  /* Mobile responsiveness */
  @media (max-width: 640px) {
    .selector-flyout {
      min-width: calc(100vw - 3rem);
      left: 50%;
      transform: translateX(-50%);
      right: auto;
    }

    .selector-dialog-content {
      width: 100vw;
      max-width: 100vw;
      height: 100vh;
      max-height: 100vh;
      border-radius: 0;
      padding: 1rem;
    }
  }

  /* Mobile fixes */
  @media (max-width: 1023px) {
    .selector-fab.corner, 
    .selector-fab.corner.sidebar-collapsed,
    .selector-fab.corner:not(.sidebar-collapsed) {
      right: 1.5rem;
    }
  }

  .selector-content-loadmore {
    grid-column: 1 / -1;
    text-align: center;
    margin: 1.5rem 0 0.5rem 0;
  }

  .selector-detail-dialog-content {
    max-width: 32rem;
    padding: 2rem;
  }
  .selector-detail-image {
    margin-top: 1rem;
    border-radius: 0.5rem;
    max-width: 100%;
    height: auto;
  }
  .selector-detail-actions {
    margin-top: 2rem;
  }
</style> 