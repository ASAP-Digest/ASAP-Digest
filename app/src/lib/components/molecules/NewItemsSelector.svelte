<script>
  import { onMount } from 'svelte';
  import { getContent } from '$lib/api/crawler-api';
  import Button from '$lib/components/atoms/Button.svelte';
  import Tag from '$lib/components/atoms/Tag.svelte';
  import Pagination from '$lib/components/atoms/Pagination.svelte';
  import SearchInput from '$lib/components/atoms/SearchInput.svelte';
  import Loading from '$lib/components/atoms/Loading.svelte';

  export let onSelect = () => {}; // Default empty function
  export let allowMultiple = false;
  export let initiallySelected = [];
  export let excludeIds = [];
  
  let loading = true;
  let items = [];
  let sources = [];
  let selected = [...initiallySelected];
  let currentPage = 1;
  let totalPages = 1;
  let totalItems = 0;
  let searchQuery = '';
  let filterSourceId = 0;
  let itemsPerPage = 10;
  let sortBy = 'date';
  let sortOrder = 'desc';
  
  // AI enhancement indicators
  let showAiEnhanced = true;
  
  $: isItemSelected = (id) => selected.includes(id);
  
  onMount(async () => {
    await loadItems();
  });
  
  async function loadItems() {
    loading = true;
    
    try {
      const response = await getContent({
        page: currentPage,
        per_page: itemsPerPage,
        search: searchQuery,
        source_id: filterSourceId > 0 ? filterSourceId : undefined,
        orderby: sortBy,
        order: sortOrder
      });
      
      items = response.items || [];
      sources = response.sources || [];
      totalPages = response.total_pages || 1;
      totalItems = response.total_items || 0;
      
      // Filter out excluded IDs
      if (excludeIds && excludeIds.length > 0) {
        items = items.filter(item => !excludeIds.includes(item.id));
      }
      
      // Add display properties to items
      items = items.map(item => {
        // Check for AI enhancements
        const hasAiSummary = item.summary && item.meta && item.meta.ai_generated;
        const hasKeywords = item.meta && item.meta.keywords && item.meta.keywords.length > 0;
        const hasEntities = item.meta && item.meta.entities && item.meta.entities.length > 0;
        const hasClassification = item.meta && item.meta.classification;
        
        return {
          ...item,
          ai_enhanced: hasAiSummary || hasKeywords || hasEntities || hasClassification,
          display_date: new Date(item.publish_date || item.created_at).toLocaleDateString(),
          display_source: sources.find(s => s.id === item.source_id)?.name || item.source_name || 'Unknown'
        };
      });
    } catch (error) {
      console.error('Error loading content:', error);
      items = [];
    } finally {
      loading = false;
    }
  }
  
  function handleSelect(id) {
    if (allowMultiple) {
      // Toggle selection
      if (isItemSelected(id)) {
        selected = selected.filter(itemId => itemId !== id);
      } else {
        selected = [...selected, id];
      }
    } else {
      // Single select mode
      selected = [id];
    }
    
    const selectedItems = items.filter(item => selected.includes(item.id));
    onSelect(allowMultiple ? selectedItems : selectedItems[0]);
  }
  
  function handleSearch() {
    currentPage = 1;
    loadItems();
  }
  
  function handleClearSearch() {
    searchQuery = '';
    currentPage = 1;
    loadItems();
  }
  
  function handleSourceFilter(event) {
    filterSourceId = parseInt(event.target.value);
    currentPage = 1;
    loadItems();
  }
  
  function handlePageChange(event) {
    currentPage = event.detail.page;
    loadItems();
  }
  
  function handleSortChange(event) {
    const [newSortBy, newSortOrder] = event.target.value.split('-');
    sortBy = newSortBy;
    sortOrder = newSortOrder;
    currentPage = 1;
    loadItems();
  }
  
  function toggleAiEnhancedFilter() {
    showAiEnhanced = !showAiEnhanced;
    loadItems();
  }
</script>

<div class="item-selector">
  <div class="selector-header">
    <h3>Content Items</h3>
    <div class="filter-controls">
      <SearchInput 
        value={searchQuery} 
        on:input={(e) => searchQuery = e.target.value}
        on:search={handleSearch}
        on:clear={handleClearSearch}
        placeholder="Search content..." 
      />
      
      <select on:change={handleSourceFilter} class="source-filter">
        <option value="0">All Sources</option>
        {#each sources as source}
          <option value={source.id}>{source.name}</option>
        {/each}
      </select>
      
      <select on:change={handleSortChange} class="sort-selector">
        <option value="date-desc">Newest First</option>
        <option value="date-asc">Oldest First</option>
        <option value="title-asc">Title A-Z</option>
        <option value="title-desc">Title Z-A</option>
      </select>
      
      <label class="ai-filter">
        <input 
          type="checkbox" 
          checked={showAiEnhanced}
          on:change={toggleAiEnhancedFilter}
        />
        Show AI Enhanced
      </label>
    </div>
  </div>
  
  {#if loading}
    <div class="loading-container">
      <Loading />
    </div>
  {:else if items.length === 0}
    <div class="empty-state">
      <p>No content items found.</p>
    </div>
  {:else}
    <div class="items-grid">
      {#each items as item (item.id)}
        <div 
          class="item-card {isItemSelected(item.id) ? 'selected' : ''}"
          on:click={() => handleSelect(item.id)}
          on:keypress={(e) => e.key === 'Enter' && handleSelect(item.id)}
          tabindex="0"
        >
          <div class="item-header">
            <h4 class="item-title">{item.title}</h4>
            {#if item.ai_enhanced}
              <span class="ai-badge">AI Enhanced</span>
            {/if}
          </div>
          
          <div class="item-meta">
            <span class="item-source">{item.display_source}</span>
            <span class="item-date">{item.display_date}</span>
          </div>
          
          {#if item.image}
            <img src={item.image} alt={item.title} class="item-image" />
          {/if}
          
          {#if item.summary}
            <p class="item-summary">
              {item.summary}
            </p>
          {/if}
          
          {#if item.meta && item.meta.keywords && item.meta.keywords.length > 0}
            <div class="item-keywords">
              {#each item.meta.keywords.slice(0, 5) as keyword}
                <Tag small>{keyword.keyword || keyword}</Tag>
              {/each}
            </div>
          {/if}
          
          {#if item.meta && item.meta.classification}
            <div class="item-classification">
              <strong>Category:</strong> {item.meta.classification.category || 'Unknown'}
              {#if item.meta.classification.confidence}
                ({Math.round(item.meta.classification.confidence * 100)}%)
              {/if}
            </div>
          {/if}
          
          <div class="item-actions">
            <Button 
              small 
              variant={isItemSelected(item.id) ? 'primary' : 'outline'}
              on:click={(e) => {
                e.stopPropagation();
                handleSelect(item.id);
              }}
            >
              {isItemSelected(item.id) ? 'Selected' : 'Select'}
            </Button>
            
            <a 
              href={item.url} 
              target="_blank" 
              rel="noopener noreferrer"
              class="view-link"
              on:click={(e) => e.stopPropagation()}
            >
              View Original
            </a>
          </div>
        </div>
      {/each}
    </div>
    
    <div class="pagination-container">
      <Pagination 
        currentPage={currentPage}
        totalPages={totalPages}
        on:pageChange={handlePageChange}
      />
      <span class="items-count">
        Showing {(currentPage - 1) * itemsPerPage + 1}-{Math.min(currentPage * itemsPerPage, totalItems)} of {totalItems} items
      </span>
    </div>
  {/if}
</div>

<style>
  .item-selector {
    width: 100%;
    padding: 1rem;
    background-color: #f9f9f9;
    border-radius: 0.5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  }
  
  .selector-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .selector-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
  }
  
  .filter-controls {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
  }
  
  .source-filter,
  .sort-selector {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 0.25rem;
    background-color: white;
    font-size: 0.875rem;
  }
  
  .ai-filter {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    cursor: pointer;
  }
  
  .items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
  }
  
  .item-card {
    display: flex;
    flex-direction: column;
    background-color: white;
    border: 1px solid #eee;
    border-radius: 0.5rem;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .item-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
  }
  
  .item-card.selected {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3);
  }
  
  .item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
  }
  
  .item-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.4;
    flex: 1;
  }
  
  .ai-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    background-color: #4f46e5;
    color: white;
    border-radius: 0.25rem;
    font-weight: 500;
    white-space: nowrap;
    margin-left: 0.5rem;
  }
  
  .item-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
  }
  
  .item-image {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;
  }
  
  .item-summary {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #4b5563;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  .item-keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
  }
  
  .item-classification {
    font-size: 0.875rem;
    color: #4b5563;
    margin-bottom: 0.5rem;
  }
  
  .item-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
  }
  
  .view-link {
    font-size: 0.875rem;
    color: #4f46e5;
    text-decoration: none;
  }
  
  .view-link:hover {
    text-decoration: underline;
  }
  
  .loading-container {
    display: flex;
    justify-content: center;
    padding: 2rem;
  }
  
  .empty-state {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
  }
  
  .pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .items-count {
    font-size: 0.875rem;
    color: #6b7280;
  }
  
  @media (max-width: 768px) {
    .selector-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .filter-controls {
      width: 100%;
    }
    
    .items-grid {
      grid-template-columns: 1fr;
    }
    
    .pagination-container {
      flex-direction: column;
      align-items: center;
    }
  }
</style> 