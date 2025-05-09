<!--
  NewItemsSelector2.svelte
  -----------------------
  TEST/DEMO CONTENT SELECTOR COMPONENT

  This file is a test/demo version of the content selector, used for development, prototyping, and integration testing.
  - NOT used in production or by the main app (see NewItemsSelector.svelte for the production version).
  - Used in demo/test pages (e.g., /routes/demo/new-items-selector/test/+page.svelte).
  - Implements advanced UI/UX patterns, tabbed content types, and detailed item views.
  - May contain experimental features or alternate approaches for selection, search, and layout.
  - Useful for reference, prototyping, or future migration, but not currently active in the app.
-->
<script>
  // @ts-ignore - Svelte component import
  import Button from '$lib/components/ui/button/button.svelte';
  // @ts-ignore - Svelte component import
  import { Input } from '$lib/components/ui/input';
  // @ts-ignore - Svelte component import
  import { Checkbox } from '$lib/components/ui/checkbox';
  // @ts-ignore - Svelte component import
  import { TabGroup, TabList, Tab, TabPanel } from '$lib/components/ui/tabs/index.js';
  // @ts-ignore - Svelte component import
  import Card from '$lib/components/ui/card/card.svelte';
  // @ts-ignore - Svelte component import
  import CardHeader from '$lib/components/ui/card/card-header.svelte';
  // @ts-ignore - Svelte component import
  import CardTitle from '$lib/components/ui/card/card-title.svelte';
  // @ts-ignore - Svelte component import
  import CardDescription from '$lib/components/ui/card/card-description.svelte';
  // @ts-ignore - Svelte component import
  import CardContent from '$lib/components/ui/card/card-content.svelte';
  // @ts-ignore - Svelte component import
  import CardFooter from '$lib/components/ui/card/card-footer.svelte';
  // @ts-ignore - Svelte component import
  import { Badge } from '$lib/components/ui/badge';
  // @ts-ignore - Svelte component import
  import { Skeleton } from '$lib/components/ui/skeleton';
  // @ts-ignore - Svelte component import
  import Icon from '$lib/components/ui/icon/icon.svelte';
  // @ts-ignore - Svelte component import
  import Dialog from '$lib/components/ui/dialog/dialog.svelte';
  // @ts-ignore - Svelte component import
  import DialogContent from '$lib/components/ui/dialog/dialog-content.svelte';
  // @ts-ignore - Svelte component import
  import DialogHeader from '$lib/components/ui/dialog/dialog-header.svelte';
  // @ts-ignore - Svelte component import
  import DialogTitle from '$lib/components/ui/dialog/dialog-title.svelte';
  
  import { browser } from '$app/environment';
  import { onMount } from 'svelte';
  import { fly, fade } from 'svelte/transition';
  
  import { 
    Search,
    Filter,
    Calendar,
    Plus,
    X,
    ChevronRight,
    ChevronDown,
    Loader2,
    FileText,
    Headphones,
    Key,
    DollarSign,
    Twitter,
    MessageSquare,
    LineChart,
    Check,
    ArrowLeft,
    Globe,
    ExternalLink,
    ArrowsLeftRight
  } from '$lib/utils/lucide-compat.js';
  
  // Import the new content fetcher service
  import { 
    fetchCachedContent, 
    searchContentWithDebounce,
    getContentItemById,
    createSelectedItemsManager
  } from '$lib/api/content-fetcher.js';
  
  // Import the content service for type details
  import { 
    getContentTypeDetails
  } from '$lib/api/content-service.js';
  
  // Import image optimization utils
  import { getOptimalImageUrl } from '$lib/utils/image-utils.js';
  
  // Import the selectedItems store
  import { 
    selectedItems as sharedSelectedItems, 
    isMaxItemsReached 
  } from '$lib/stores/selected-items-store.js';
  
  // Import crawler API
  import { getContent as getCrawlerContent, getSources as getCrawlerSources } from '$lib/api/crawler-api.js';
  
  /**
   * @typedef {import('$lib/api/content-fetcher').ContentItem} ContentItem
   * @typedef {import('$lib/api/content-service').PaginationInfo} PaginationInfo
   * @typedef {import('$lib/api/content-service').QueryParams} QueryParams
   */
  
  /**
   * @typedef {Object} NewItemsSelectorProps
   * @property {string} [className] - Additional CSS classes
   * @property {number} [maxItems=10] - Maximum number of items to select
   * @property {ContentItem[]} [initialSelectedItems=[]] - Initial selected items
   * @property {string[]} [enabledContentTypes=['article', 'podcast', 'keyterm']] - Content types to enable
   * @property {function(ContentItem[]): void} [onSelectionChange] - Callback when selection changes
   * @property {boolean} [showFab=true] - Whether to show the floating action button
   * @property {boolean} [startOpen=false] - Whether to open directly in content selection mode
   * @property {string} [initialType=''] - Initial content type to select
   */
  
  /** @type {NewItemsSelectorProps} */
  const { 
    className = '',
    maxItems = 10,
    initialSelectedItems = [],
    enabledContentTypes = ['article', 'podcast', 'keyterm'],
    onSelectionChange = undefined,
    showFab = true,
    startOpen = false,
    initialType = ''
  } = $props();
  
  // Initial state setup
  let isInitialized = $state(false);
  
  // State management
  let searchQuery = $state('');
  let activeTab = $state(initialType || enabledContentTypes[0] || 'article');
  let selectedDateRange = $state({ from: null, to: null });
  let showFlyout = $state(false);
  let fabPosition = $state('corner'); // 'corner' or 'center'
  let isSidebarCollapsed = $state(false);
  // CRITICAL: Force dialog to ALWAYS initialize as false
  let dialogOpen = $state(false);
  let flyoutPosition = $state('top'); // 'top', 'left', 'right'
  // Add flag to prevent immediate dialog opening
  let preventDialogOpen = $state(false);
  
  // State for detailed item view
  let detailView = $state(false);
  let currentDetailItem = $state(null);
  
  // Create a search debounce function cancellation tracker
  let cancelSearch = $state(null);
  
  // Available content types
  const contentTypeDetails = getContentTypeDetails().filter(type => 
    enabledContentTypes.includes(type.id)
  );
  
  // Define content type icons
  const contentTypeIcons = {
    article: FileText,
    podcast: Headphones,
    keyterm: Key,
    financial: DollarSign,
    xpost: Twitter,
    reddit: MessageSquare,
    event: Calendar,
    polymarket: LineChart
  };
  
  // Content items and loading states
  /** @type {Record<string, ContentItem[]>} */
  let contentItems = $state({}); 
  
  /** @type {Record<string, PaginationInfo>} */
  let paginationByType = $state({});
  
  /** @type {Record<string, boolean>} */
  let loadingByType = $state({});
  
  /** @type {Record<string, string>} */
  let errorByType = $state({});
  
  // Crawler sources state variables
  let crawlerSources = $state([]);
  let isCrawlerLoading = $state(false);
  let selectedSource = $state(''); // Added missing state variable
  let isLoading = $state(false);
  let error = $state(null);
  let pagination = $state({ totalItems: 0, totalPages: 1, currentPage: 1 });
  
  // Source options for filter
  let sourceOptions = $state([
    {
      id: 'wp',
      name: 'WordPress',
      description: 'Content from WordPress',
      icon: '/icons/wordpress.svg'
    }
  ]);
  
  // WordPress categories for source filter
  let wpCategories = $state([]);
  
  // Initialize loadingByType for each content type
  $effect(() => {
    enabledContentTypes.forEach(type => {
      loadingByType[type] = false;
      contentItems[type] = [];
      paginationByType[type] = { hasNextPage: false, endCursor: null };
    });
  });
  
  // Load stored FAB position preference and sidebar state
  $effect(() => {
    if (browser) {
      const storedPosition = localStorage.getItem('fab-position');
      if (storedPosition === 'center' || storedPosition === 'corner') {
        fabPosition = storedPosition;
      }
      
      // Check sidebar state
      const sidebarState = localStorage.getItem('sidebar-collapsed');
      isSidebarCollapsed = sidebarState === 'true';
      
      // Set up sidebar state observer
      const observer = new MutationObserver(() => {
        isSidebarCollapsed = document.body.classList.contains('sidebar-collapsed');
      });
      
      if (document.body) {
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
      }
      
      return () => {
        observer.disconnect();
      };
    }
  });
  
  // Initialization effect - run only once
  $effect(() => {
    if (!isInitialized) {
      console.log('Initializing component, startOpen:', startOpen);
      isInitialized = true;
      
      // Override startOpen behavior - NEVER auto-open dialog anymore
      // We want the radial menu first, then dialog
      console.log('Overriding startOpen behavior to favor radial menu');
      
      // If startOpen is requested, show the flyout instead of dialog
      if (startOpen) {
        setTimeout(() => {
          console.log('Auto-showing flyout instead of dialog due to startOpen');
          toggleFlyout();
        }, 500);
      }
    }
  });
  
  // Debug effect to log dialog state changes
  $effect(() => {
    console.log('Dialog state:', dialogOpen ? 'open' : 'closed');
  });
  
  // Debug effect to log flyout state changes
  $effect(() => {
    console.log('Flyout state:', showFlyout ? 'open' : 'closed');
  });
  
  // Effect to clean up search cancellation
  onMount(() => {
    return () => {
      if (cancelSearch && typeof cancelSearch === 'function') {
        cancelSearch();
      }
    };
  });
  
  // Replace local selectedItems usage with the shared store
  $effect(() => {
    // Initialize with the initial items or use the existing items in the store
    if (initialSelectedItems && initialSelectedItems.length > 0) {
      // Only add initial items if they're not already in the store
      const currentSelected = $sharedSelectedItems;
      const newItems = initialSelectedItems.filter(
        initialItem => !currentSelected.some(
          item => item.id === initialItem.id && item.type === initialItem.type
        )
      );
      
      // Add any new items to the store
      newItems.forEach(item => {
        sharedSelectedItems.add(item);
      });
    }
  });
  
  // Add to onMount or wherever you initialize your component
  onMount(async () => {
    // Load crawler sources
    try {
      isCrawlerLoading = true;
      const result = await getCrawlerSources();
      crawlerSources = result.sources || [];
      
      // Add crawler sources to the filter options
      if (crawlerSources.length > 0) {
        // Add a general crawler source group
        sourceOptions = [
          ...sourceOptions,
          {
            id: 'crawler',
            name: 'Crawler Sources',
            description: 'Content from external websites',
            icon: '/icons/rss.svg'
          }
        ];
        
        // Add individual crawler sources
        crawlerSources.forEach(source => {
          sourceOptions.push({
            id: `crawler_${source.id}`,
            name: source.name,
            description: source.url,
            icon: '/icons/rss.svg',
            parentId: 'crawler'
          });
        });
      }
    } catch (error) {
      console.error('Error loading crawler sources:', error);
    } finally {
      isCrawlerLoading = false;
    }
  });
  
  /**
   * Fetch content for a specific type
   * @param {string} contentType
   * @param {boolean} [reset=false] Reset pagination and content
   */
  async function fetchContent(contentType, reset = false) {
    try {
      loadingByType[contentType] = true;
      errorByType[contentType] = '';
      
      /** @type {QueryParams} */
      const params = {
        limit: 10,
        search: searchQuery || undefined,
        cursor: reset ? null : paginationByType[contentType]?.endCursor,
        dateFrom: selectedDateRange.from,
        dateTo: selectedDateRange.to
      };
      
      // Use the enhanced cached fetcher instead of direct API calls
      const result = await fetchCachedContent(contentType, params, {
        bypassCache: reset && !!searchQuery, // Bypass cache on new searches
      });
      
      // Update content items - either replace or append based on reset flag
      if (reset) {
        contentItems[contentType] = result.items;
      } else {
        contentItems[contentType] = [...(contentItems[contentType] || []), ...result.items];
      }
      
      // Update pagination info
      paginationByType[contentType] = result.pagination;
    } catch (err) {
      console.error(`Error fetching ${contentType}:`, err);
      errorByType[contentType] = err instanceof Error ? err.message : 'Unknown error';
    } finally {
      loadingByType[contentType] = false;
    }
  }
  
  /**
   * Handle search
   */
  function handleSearch() {
    // Cancel any existing search
    if (cancelSearch && typeof cancelSearch === 'function') {
      cancelSearch();
    }
    
    // Set loading state
    loadingByType[activeTab] = true;
    errorByType[activeTab] = '';
    
    // Create params for search
    /** @type {QueryParams} */
    const params = {
      limit: 10,
      search: searchQuery || undefined,
      dateFrom: selectedDateRange.from,
      dateTo: selectedDateRange.to
    };
    
    // Only search the active tab for now
    const searchTypes = [activeTab];
    
    // Use debounced search
    cancelSearch = searchContentWithDebounce(
      searchTypes,
      params,
      (results, error) => {
        // Handle results
        if (error) {
          errorByType[activeTab] = error.message;
          contentItems[activeTab] = [];
          paginationByType[activeTab] = { hasNextPage: false, endCursor: null };
        } else if (results[activeTab]) {
          contentItems[activeTab] = results[activeTab].items;
          paginationByType[activeTab] = results[activeTab].pagination;
        }
        
        // Clear loading state
        loadingByType[activeTab] = false;
      }
    );
  }
  
  /**
   * Clear search
   */
  function clearSearch() {
    if (searchQuery) {
      searchQuery = '';
      handleSearch();
    }
  }
  
  /**
   * Load more items for the current active tab
   */
  function loadMore() {
    if (paginationByType[activeTab]?.hasNextPage && !loadingByType[activeTab]) {
      fetchContent(activeTab, false); // Don't reset, just append
    }
  }
  
  /**
   * Toggle item selection
   * @param {ContentItem} item
   */
  function toggleSelectItem(item) {
    // Check if we've reached the max limit before adding
    if (!sharedSelectedItems.isSelected(item) && 
        isMaxItemsReached(maxItems)) {
      return; // Don't add more if at limit
      }
      
    // Toggle the item in the shared store
    sharedSelectedItems.toggle(item);
    
    // Notify parent of selection change if callback provided
    if (onSelectionChange) {
      onSelectionChange($sharedSelectedItems);
    }
  }
  
  /**
   * Remove a selected item
   * @param {ContentItem} item
   */
  function removeSelectedItem(item) {
    sharedSelectedItems.remove(item);
    
    // Notify parent of selection change
    if (onSelectionChange) {
      onSelectionChange($sharedSelectedItems);
    }
  }
  
  /**
   * Check if an item is selected
   * @param {ContentItem} item
   * @returns {boolean}
   */
  function isItemSelected(item) {
    return sharedSelectedItems.isSelected(item);
  }
  
  /**
   * Switch to a different content type tab
   * @param {string} contentType
   */
  function switchTab(contentType) {
    activeTab = contentType;
    
    // If we haven't loaded content for this type yet, load it
    if (!contentItems[contentType] || contentItems[contentType].length === 0) {
      fetchContent(contentType, true);
    }
  }
  
  /**
   * Format the timestamp to a readable format
   * @param {string} timestamp
   * @returns {string}
   */
  function formatTimestamp(timestamp) {
    if (!timestamp) return '';
    
    try {
      const date = new Date(timestamp);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch (e) {
      return timestamp; // Return the original if parsing fails
    }
  }
  
  /**
   * Toggle FAB position and save preference
   */
  function toggleFabPosition() {
    fabPosition = fabPosition === 'corner' ? 'center' : 'corner';
    if (browser) {
      localStorage.setItem('fab-position', fabPosition);
    }
  }
  
  /**
   * Calculate the appropriate position for the flyout based on screen edges
   * @returns {string} The position for the flyout
   */
  function calculateFlyoutPosition() {
    if (!browser) return 'top';
    
    // Get viewport dimensions
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    // Get FAB position
    const fabElement = document.querySelector('.selector-fab');
    if (!fabElement) return 'top';
    
    const fabRect = fabElement.getBoundingClientRect();
    
    // Calculate position - default to 'top' unless close to top edge
    if (fabRect.top < 300) {
      return 'bottom';
    } else if (fabRect.left < 200) {
      return 'right';
    } else if (viewportWidth - fabRect.right < 200) {
      return 'left';
    } else {
      return 'top';
    }
  }
  
  /**
   * Toggle the flyout visibility with edge detection
   */
  function toggleFlyout() {
    console.log('Toggling flyout, current showFlyout:', showFlyout);
    
    // CRITICAL: Always close dialog when opening flyout
    dialogOpen = false;
    console.log('Closing dialog to show flyout');
    
    // Set preventDialogOpen flag to block any attempts to open dialog
    preventDialogOpen = true;
    
    // Clear the flag after a reasonable delay
    setTimeout(() => {
      preventDialogOpen = false;
      console.log('Dialog prevention released');
    }, 500);
    
    if (!showFlyout) {
      // Calculate position before showing
      flyoutPosition = calculateFlyoutPosition();
    }
    
    showFlyout = !showFlyout;
    console.log('New showFlyout state:', showFlyout);
  }
  
  /**
   * Open the dialog
   */
  function openDialog() {
    // CRITICAL: Don't open if prevention is active
    if (preventDialogOpen) {
      console.log('Dialog open prevented by flag');
      return;
    }
    
    console.log('Opening dialog explicitly');
    dialogOpen = true;
    showFlyout = false;
    if (!contentItems[activeTab] || contentItems[activeTab].length === 0) {
      fetchContent(activeTab, true);
    }
  }
  
  /**
   * Close the dialog
   */
  function closeDialog() {
    console.log('Closing dialog explicitly');
    dialogOpen = false;
  }
  
  /**
   * Select a content type and open the dialog
   * @param {string} type
   */
  function selectContentType(type) {
    console.log('Selecting content type:', type);
    activeTab = type;
    showFlyout = false;
    
    // Add a small delay before opening dialog to ensure proper rendering
    setTimeout(() => {
      console.log('Opening dialog after content type selection');
      dialogOpen = true;
      if (!contentItems[type] || contentItems[type].length === 0) {
        fetchContent(type, true);
      }
    }, 50);
  }
  
  /**
   * Add selected items and close the dialog
   */
  function addSelectedItems() {
    if (onSelectionChange) {
      onSelectionChange($sharedSelectedItems);
    }
    closeDialog();
  }
  
  /**
   * View detailed info for an item
   * @param {ContentItem} item
   */
  function viewItemDetail(item) {
    currentDetailItem = item;
    detailView = true;
  }
  
  /**
   * Close the detail view
   */
  function closeDetailView() {
    detailView = false;
    currentDetailItem = null;
  }
  
  /**
   * Add current detail item to digest
   */
  function addDetailItemToDigest() {
    if (currentDetailItem) {
      if (!isItemSelected(currentDetailItem)) {
        toggleSelectItem(currentDetailItem);
      }
      closeDetailView();
      // Optionally close dialog after adding
      // closeDialog();
    }
  }
  
  // Update the dialog content when needed
  $effect(() => {
    if (dialogOpen && (!contentItems[activeTab] || contentItems[activeTab].length === 0)) {
      fetchContent(activeTab, true);
    }
  });
  
  // Update the fetchItems function to handle crawler sources
  async function fetchItems(params = {}) {
    isLoading = true;
    error = null;
    let items = []; // Declare items locally to avoid conflicts
    
    try {
      // Determine if we need to fetch from crawler
      const sourceType = params.source ? getSourceType(params.source) : 'all';
      
      let result = { items: [], pagination: { totalItems: 0, totalPages: 1, currentPage: 1 }};
      
      if (sourceType === 'wp' || sourceType === 'all') {
        // Fetch WordPress content
        console.log('Fetching WordPress content...');
        // Would implement WordPress fetch here
      }
      
      if (sourceType === 'crawler' || sourceType === 'crawler_source' || sourceType === 'all') {
        // Fetch crawler content
        const crawlerParams = {
          page: params.page || 1,
          per_page: params.perPage || 20,
          search: params.search || '',
          orderby: params.orderBy || 'date',
          order: params.order || 'desc'
        };
        
        // Add source filter if selecting a specific crawler source
        if (sourceType === 'crawler_source') {
          crawlerParams.source_id = params.source.replace('crawler_', '');
        }
        
        try {
          const crawlerResult = await getCrawlerContent(crawlerParams);
          
          // Transform crawler items to match our component's expected format
          const crawlerItems = (crawlerResult.items || []).map(item => ({
            id: `crawler_${item.id}`,
            title: item.title,
            content: item.content,
            excerpt: item.summary || '',
            date: item.publish_date,
            url: item.url,
            thumbnail: item.image || '',
            source: 'crawler',
            sourceId: `crawler_${item.source_id}`,
            sourceName: item.source_name,
            sourceIcon: '/icons/rss.svg'
          }));
          
          // Combine with WordPress items if fetching all
          if (sourceType === 'all' && result.items.length > 0) {
            result.items = [...result.items, ...crawlerItems];
            // Sort by date
            result.items.sort((a, b) => new Date(b.date) - new Date(a.date));
            // Apply pagination
            result.items = result.items.slice(0, params.perPage || 20);
          } else {
            result = {
              items: crawlerItems,
              pagination: {
                totalItems: crawlerResult.total_items || 0,
                totalPages: crawlerResult.total_pages || 1,
                currentPage: params.page || 1
              }
            };
          }
        } catch (err) {
          console.error('Error fetching crawler content:', err);
        }
      }
      
      // Return the results instead of updating component state directly
      return result;
      
    } catch (err) {
      console.error('Error fetching items:', err);
      error = 'Failed to load items. Please try again.';
      return { items: [], pagination: { totalItems: 0, totalPages: 1, currentPage: 1 } };
    } finally {
      isLoading = false;
    }
  }
  
  // Helper function to determine source type
  function getSourceType(source) {
    if (!source) return 'all';
    if (source === 'wp') return 'wp';
    if (source === 'crawler') return 'crawler';
    if (source.startsWith('crawler_')) return 'crawler_source';
    return 'wp'; // Default to WordPress
  }
</script>

<!-- Floating Action Button -->
{#if showFab && !dialogOpen}
  <div 
    class="selector-fab {fabPosition === 'center' ? 'center' : 'corner'} {isSidebarCollapsed ? 'sidebar-collapsed' : ''}"
    style={fabPosition === 'corner' && !isSidebarCollapsed ? 'right: calc(1.5rem + 240px);' : ''}
  >
    <!-- Main FAB Button -->
        <button 
      class="selector-fab-button {showFlyout ? 'active' : ''}"
      onclick={toggleFlyout}
      aria-label="Add new content"
    >
      <Icon icon={showFlyout ? X : Plus} size={24} />
        </button>
    
    <!-- Position Toggle (small button) -->
    <button 
      class="selector-fab-position-toggle"
      onclick={toggleFabPosition}
      title="Toggle position"
    >
      <Icon icon={ArrowsLeftRight} size={14} />
    </button>
    
    <!-- Radial Flyout Menu with Arc Pattern -->
    {#if showFlyout}
      <div class="radial-menu {fabPosition === 'center' ? 'center' : 'corner'}">
        {#each contentTypeDetails as type, i}
          <button 
            transition:fly|local={{
              delay: i * 80, // Increased delay for more pronounced staggered effect
              duration: 400,
              x: i * 15, // Add some horizontal offset
              y: 30,
              opacity: 0,
              easing: 'cubic-bezier(0.16, 1, 0.3, 1)' // More bounce/spring effect
            }}
            class="radial-menu-item" 
            style="--index: {i}; --total: {contentTypeDetails.length};"
            onclick={() => selectContentType(type.id)}
            aria-label="Add {type.label}"
          >
            <div class="radial-menu-icon">
              <Icon icon={contentTypeIcons[type.id] || FileText} size={20} />
            </div>
            <span class="radial-menu-label">{type.label}</span>
          </button>
        {/each}
      </div>
    {/if}
  </div>
{/if}

<!-- Selection Dialog -->
<Dialog open={dialogOpen} onClose={closeDialog}>
  <DialogContent class="selector-dialog-content">
    <DialogHeader>
      <DialogTitle class="text-[1.25rem] font-medium flex items-center gap-[0.5rem]">
        {#if contentTypeDetails.find(t => t.id === activeTab)}
          {@const type = contentTypeDetails.find(t => t.id === activeTab)}
          {#if contentTypeIcons[type.id]}
            <div class="w-[1.5rem] h-[1.5rem] bg-[hsl(var(--muted))] rounded-full flex items-center justify-center">
              <Icon icon={contentTypeIcons[type.id]} size={14} class="text-[hsl(var(--primary))]" />
            </div>
          {/if}
          <span>Select {type.label} Content</span>
        {:else}
          <span>Select Content</span>
        {/if}
      </DialogTitle>
    </DialogHeader>
  
  <!-- Content Type Tabs -->
  <TabGroup value={activeTab} onValueChange={(value) => switchTab(value)}>
    <TabList class="grid grid-flow-col auto-cols-fr">
      {#each contentTypeDetails as type}
        <Tab value={type.id} class="flex items-center justify-center">
          {type.label}
          {#if loadingByType[type.id]}
            <Icon icon={Loader2} class="ml-1 animate-spin" size={16} />
          {/if}
        </Tab>
      {/each}
    </TabList>
    
    <!-- Selected Items Preview -->
    <div class="pt-4">
      <div class="flex flex-wrap items-center mb-2 gap-2">
        <span class="font-[var(--font-weight-semibold)] text-[hsl(var(--text-1))]">
          Selected ({sharedSelectedItems.length}/{maxItems}):
        </span>
        {#if sharedSelectedItems.length === 0}
          <span class="text-[hsl(var(--text-3))]">No items selected</span>
        {:else}
          {#each sharedSelectedItems as item}
            <Badge variant="outline" class="flex items-center gap-1">
              {item.title.length > 20 ? item.title.slice(0, 20) + '...' : item.title}
              <button 
                type="button" 
                class="text-[hsl(var(--text-3))] hover:text-[hsl(var(--text-1))]"
                onclick={() => removeSelectedItem(item)}
              >
                <Icon icon={X} size={12} />
              </button>
            </Badge>
          {/each}
        {/if}
      </div>
    </div>
      
  <!-- Search & Filters -->
      <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-2 mt-2">
    <div class="relative flex-1">
      <Icon 
        icon={Search} 
        class="absolute left-2 top-1/2 transform -translate-y-1/2 text-[hsl(var(--text-3))]" 
        size={18} 
      />
      <Input
        placeholder="Search content..." 
        class="pl-8"
        value={searchQuery}
        onkeydown={(e) => e.key === 'Enter' && handleSearch()}
        onchange={(e) => {
          searchQuery = e.target.value;
        }}
      />
      {#if searchQuery}
        <button 
          type="button" 
          class="absolute right-2 top-1/2 transform -translate-y-1/2 text-[hsl(var(--text-3))] hover:text-[hsl(var(--text-1))]"
          onclick={clearSearch}
        >
          <Icon icon={X} size={16} />
        </button>
      {/if}
    </div>
    <Button 
      variant="outline" 
      onclick={handleSearch}
    >
      <Icon icon={Filter} class="mr-2" size={16} />
      Filter
    </Button>
    </div>
    
    <!-- Content Panels -->
    {#each contentTypeDetails as type}
        <TabPanel value={type.id} class="pt-4">
          {#if detailView && currentDetailItem && currentDetailItem.type === type.id}
            <!-- Item Detail View -->
            <div class="bg-[hsl(var(--surface-1))] rounded-lg p-4">
              <div class="flex justify-between items-center mb-4">
                <Button variant="ghost" size="sm" onclick={closeDetailView}>
                  <Icon icon={ArrowLeft} class="mr-2" size={16} />
                  Back
                </Button>
                <div class="flex gap-2">
                  <Button 
                    variant={isItemSelected(currentDetailItem) ? "outline" : "default"} 
                    size="sm"
                    onclick={addDetailItemToDigest}
                  >
                    {isItemSelected(currentDetailItem) ? 'Added to Digest' : 'Add to Digest'}
                    {#if isItemSelected(currentDetailItem)}
                      <Icon icon={Check} class="ml-2" size={14} />
                    {/if}
                  </Button>
                </div>
              </div>
              
              <h2 class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] mb-3">
                {currentDetailItem.title}
              </h2>
              
              <div class="flex gap-3 mb-4 text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
                <Badge variant="outline" class="capitalize">{currentDetailItem.type}</Badge>
                {#if currentDetailItem.source}
                  <span class="flex items-center gap-1">
                    <Icon icon={Globe} size={14} />
                    {currentDetailItem.source}
                  </span>
                {/if}
                {#if currentDetailItem.formattedDate}
                  <span class="flex items-center gap-1">
                    <Icon icon={Calendar} size={14} />
                    {currentDetailItem.formattedDate}
                  </span>
                {/if}
              </div>
              
              <div class="prose dark:prose-invert max-w-none mb-6">
                {#if currentDetailItem.content}
                  <p>{@html currentDetailItem.content}</p>
                {:else if currentDetailItem.excerpt}
                  <p>{currentDetailItem.excerpt}</p>
                {:else}
                  <p>No content available for this item.</p>
                {/if}
                
                {#if currentDetailItem.meta?.url || currentDetailItem.meta?.sourceUrl}
                  <div class="mt-4">
                    <a 
                      href={currentDetailItem.meta?.url || currentDetailItem.meta?.sourceUrl} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      class="text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))] inline-flex items-center"
                    >
                      View Original
                      <Icon icon={ExternalLink} class="ml-1" size={14} />
                    </a>
                  </div>
                {/if}
              </div>
            </div>
          {:else if errorByType[type.id]}
          <div class="p-4 text-[hsl(var(--functional-error-fg))] bg-[hsl(var(--functional-error)/0.1)] rounded-md">
            Error loading {type.label}: {errorByType[type.id]}
          </div>
        {:else if contentItems[type.id]?.length === 0 && !loadingByType[type.id]}
          <div class="p-4 text-[hsl(var(--text-2))] bg-[hsl(var(--surface-2))] rounded-md">
            No {type.label} items found.
          </div>
        {:else}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto p-1">
            {#each contentItems[type.id] || [] as item}
                <div class={`overflow-hidden transition-all border rounded-lg ${isItemSelected(item) ? 'border-[hsl(var(--brand))]' : 'border-[hsl(var(--border))]'}`}>
                  <div class="flex flex-row p-3 gap-4 w-full text-left relative">
                  <div class="flex-shrink-0 mt-1">
                      <Checkbox 
                        checked={isItemSelected(item)} 
                        onclick={(e) => {
                          e.stopPropagation();
                          toggleSelectItem(item);
                        }}
                      />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="text-[var(--font-size-base)] font-[var(--font-weight-semibold)] text-[hsl(var(--text-1))] mb-1">
                        <button 
                          type="button" 
                          class="hover:underline focus:outline-none focus:underline"
                          onclick={() => viewItemDetail(item)}
                        >
                      {item.title}
                        </button>
                    </h3>
                    <p class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))] line-clamp-2">
                      {item.excerpt || 'No description available'}
                    </p>
                    <div class="flex gap-2 mt-2 text-[var(--font-size-xs)] text-[hsl(var(--text-3))]">
                      <Badge variant="outline" class="capitalize">{item.type}</Badge>
                      {#if item.source}
                        <span>{item.source}</span>
                      {/if}
                      {#if item.formattedDate}
                        <span class="flex items-center gap-1">
                          <Icon icon={Calendar} size={12} />
                          {item.formattedDate}
                        </span>
                      {/if}
                      </div>
                      <div class="flex justify-between mt-2">
                        <Button 
                          variant="ghost" 
                          size="sm"
                          onclick={() => viewItemDetail(item)}
                        >
                          View Details
                        </Button>
                        <Button 
                          variant={isItemSelected(item) ? "outline" : "default"} 
                          size="sm"
                          onclick={(e) => {
                            e.stopPropagation();
                            toggleSelectItem(item);
                          }}
                        >
                          {isItemSelected(item) ? 'Added' : 'Add'}
                          {#if isItemSelected(item)}
                            <Icon icon={Check} class="ml-1" size={14} />
                          {/if}
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
            {/each}
            
            {#if loadingByType[type.id]}
              {#each Array(2) as _, i}
                <Card>
                  <div class="flex flex-row p-3 gap-4">
                    <div class="flex-shrink-0 mt-1">
                      <Skeleton class="h-4 w-4 rounded-sm" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <Skeleton class="h-5 w-3/4 mb-2" />
                      <Skeleton class="h-4 w-full mb-1" />
                      <Skeleton class="h-4 w-5/6 mb-2" />
                      <div class="flex gap-2">
                        <Skeleton class="h-4 w-16 rounded-full" />
                        <Skeleton class="h-4 w-20 rounded-sm" />
                      </div>
                    </div>
                  </div>
                </Card>
              {/each}
            {/if}
          </div>
          
          {#if paginationByType[type.id]?.hasNextPage}
            <div class="flex justify-center mt-4">
              <Button 
                variant="outline" 
                onclick={loadMore}
                disabled={loadingByType[type.id]}
              >
                {#if loadingByType[type.id]}
                  <Icon icon={Loader2} class="mr-2 animate-spin" size={16} />
                  Loading...
                {:else}
                  Load More
                  <Icon icon={ChevronDown} class="ml-2" size={16} />
                {/if}
              </Button>
            </div>
          {/if}
        {/if}
      </TabPanel>
    {/each}
  </TabGroup>
    
    <!-- Action Buttons -->
    <div class="flex justify-between mt-4">
      <Button variant="outline" onclick={closeDialog}>
        Cancel
      </Button>
      <Button 
        variant="default" 
        onclick={addSelectedItems} 
        disabled={sharedSelectedItems.length === 0}
      >
        Add {sharedSelectedItems.length} {sharedSelectedItems.length === 1 ? 'Item' : 'Items'}
      </Button>
</div> 
  </DialogContent>
</Dialog>

<!-- Replace the select element with the correct event syntax -->
<div class="source-filter">
  <label for="sourceFilter">Source:</label>
  <select
    id="sourceFilter"
    value={selectedSource}
    onchange={(e) => {
      selectedSource = e.target.value;
      fetchItems({ source: selectedSource, page: 1 });
    }}
  >
    <option value="">All Sources</option>
    
    <!-- WordPress sources -->
    <option value="wp">WordPress</option>
    {#each wpCategories as category}
      <option value={category.id}>&nbsp;&nbsp;{category.name}</option>
    {/each}
    
    <!-- Crawler sources -->
    {#if crawlerSources.length > 0}
      <option value="crawler">Crawler Sources</option>
      {#each crawlerSources as source}
        <option value={`crawler_${source.id}`}>&nbsp;&nbsp;{source.name}</option>
      {/each}
    {/if}
  </select>
</div>

<!-- Display loading state for crawler sources -->
{#if isCrawlerLoading}
  <div class="loading-indicator">
    <span class="spinner"></span>
    <span>Loading crawler sources...</span>
  </div>
{/if}

<style>
  /* Base FAB styling */
  .selector-fab {
    position: fixed;
    bottom: 1.5rem;
    z-index: 50;
    transition: all 0.3s ease-out;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
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
  }

  .selector-fab-button {
    background: linear-gradient(135deg, hsl(var(--brand)), hsl(var(--brand-hover)));
    color: hsl(var(--brand-fg));
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-lg);
    transition: transform 0.2s, background 0.2s;
    border: none;
    cursor: pointer;
    position: relative;
    z-index: 10;
  }
  
  .selector-fab-button.active {
    background: hsl(var(--surface-2));
    color: hsl(var(--text-1));
    box-shadow: var(--shadow-sm);
  }
  
  .selector-fab-button.active svg {
    transform: rotate(135deg);
  }

  .selector-fab-button:hover {
    transform: scale(1.05);
  }
  
  .selector-fab-button.active:hover {
    transform: rotate(45deg) scale(1.05);
  }

  .selector-fab-position-toggle {
    position: absolute;
    right: 0.25rem;
    bottom: -0.5rem;
    background: hsl(var(--surface-2));
    border: 1px solid hsl(var(--border));
    color: hsl(var(--text-1));
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s;
    z-index: 11;
  }

  .selector-fab-position-toggle:hover {
    transform: scale(1.1);
  }
  
  /* Radial Menu Styling */
  .radial-menu {
    position: absolute;
    bottom: 4rem;
    right: 0;
    z-index: 100; /* Increased z-index */
    background: rgba(0, 0, 0, 0.1); /* Slightly visible background */
    border-radius: 2rem;
    padding: 1rem;
    pointer-events: all;
    width: 30rem; /* Explicit width */
    height: 20rem; /* Explicit height */
  }
  
  .radial-menu.center {
    right: -15rem; /* Center */
  }
  
  .radial-menu-item {
    position: absolute;
    display: flex;
    align-items: center;
    background: hsl(var(--surface-2));
    border: 2px solid hsl(var(--brand)); /* More visible border */
    border-radius: 2rem;
    padding: 0.5rem 1rem;
    padding-left: 0.75rem;
    box-shadow: var(--shadow-lg); /* Heavier shadow */
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); /* More dramatic animation */
    transform: translate(calc(-60px - var(--index) * 70px), calc(-40px - var(--index) * 50px)); /* More pronounced arc */
  }
  
  .radial-menu-item:hover {
    background: hsl(var(--accent));
    transform: translate(calc(-60px - var(--index) * 70px), calc(-40px - var(--index) * 50px)) scale(1.05);
    box-shadow: 0 0 15px hsl(var(--brand) / 0.6);
  }
  
  .radial-menu-icon {
    margin-right: 0.5rem;
    color: hsl(var(--brand));
  }
  
  .radial-menu-label {
    font-weight: 500;
    color: hsl(var(--text-1));
  }

  /* Preserve all other styles (selector-dialog-content, etc.) */
  .selector-dialog-content {
    width: 90vw;
    max-width: 800px;
  }

  /* Enhance animation */
  :global(.flyout-enter), :global(.flyout-exit) {
    transition: opacity 200ms, transform 200ms;
  }
  :global(.flyout-enter-start), :global(.flyout-exit-end) {
    opacity: 0;
    transform: translateY(20px);
  }
  :global(.flyout-enter-end), :global(.flyout-exit-start) {
    opacity: 1;
    transform: translateY(0);
  }
</style> 