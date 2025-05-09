<!--
  FilterPanel.svelte
  -----------------
  Provides advanced filtering options for content selection:
  - Date range (from/to)
  - Category (multi-select)
  - Source (text input)
  Emits a 'filter' event with the selected filter values.
-->
<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { Label } from '$lib/components/ui/label';
  import { Badge } from '$lib/components/ui/badge';
  import { Select, SelectTrigger, SelectContent, SelectItem } from '$lib/components/ui/select';
  import { Checkbox } from '$lib/components/ui/checkbox';
  import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '$lib/components/ui/accordion';
  import { Calendar } from '$lib/components/ui/calendar';
  import { Popover, PopoverContent, PopoverTrigger } from '$lib/components/ui/popover';
  import { format } from 'date-fns';
  import { Calendar as CalendarIcon, X } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { fetchContentTypes } from '$lib/api/content-fetcher.js';
  
  /**
   * @typedef {Object} FilterOptions
   * @property {string} [search=''] - Search term
   * @property {string[]} [types=[]] - Content types to include
   * @property {string[]} [categories=[]] - Categories to filter by
   * @property {string[]} [sources=[]] - Sources to filter by
   * @property {Date|null} [dateFrom=null] - Start date filter
   * @property {Date|null} [dateTo=null] - End date filter
   * @property {boolean} [onlyFresh=false] - Only show content from last 24 hours
   * @property {number} [limit=20] - Number of items per page
   */
  
  /** @type {FilterOptions} */
  const defaultFilters = {
    search: '',
    types: ['article', 'podcast', 'financial', 'social'],
    categories: [],
    sources: [],
    dateFrom: null,
    dateTo: null,
    onlyFresh: false,
    limit: 20
  };
  
  /** @type {FilterOptions} */
  let filters = $state({ ...defaultFilters });
  
  /**
   * @type {Array<{id: string, name: string}>}
   */
  let categories = $state([]);
  
  /**
   * @type {Array<{id: string, name: string}>}
   */
  let sources = $state([]);
  
  /**
   * @type {Array<{value: string, label: string}>}
   */
  const contentTypes = [
    { value: 'article', label: 'Articles' },
    { value: 'podcast', label: 'Podcasts' },
    { value: 'financial', label: 'Financial Data' },
    { value: 'social', label: 'Social Media' }
  ];
  
  /**
   * @type {Array<{value: number, label: string}>}
   */
  const limitOptions = [
    { value: 10, label: '10 items' },
    { value: 20, label: '20 items' },
    { value: 50, label: '50 items' },
    { value: 100, label: '100 items' }
  ];
  
  /** @type {string[]} */
  let selectedTypes = $state(filters.types);
  
  /** @type {string[]} */
  let selectedCategories = $state(filters.categories);
  
  /** @type {string[]} */
  let selectedSources = $state(filters.sources);
  
  /** @type {Date|null} */
  let dateFrom = $state(filters.dateFrom);
  
  /** @type {Date|null} */
  let dateTo = $state(filters.dateTo);
  
  /** @type {boolean} */
  let onlyFresh = $state(filters.onlyFresh);
  
  /** @type {number} */
  let limit = $state(filters.limit);
  
  /** @type {string} */
  let search = $state(filters.search);
  
  /** @type {boolean} */
  let isExpanded = $state(false);
  
  const dispatch = createEventDispatcher();
  
  // Debounce search for better UX
  let searchTimeout;
  $effect(() => {
    // Reference 'search' to create dependency on it
    search;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      applyFilters();
    }, 300);
  });
  
  // Auto-apply filters when selections change
  $effect(() => {
    filters.types = selectedTypes;
    filters.categories = selectedCategories;
    filters.sources = selectedSources;
    filters.dateFrom = dateFrom;
    filters.dateTo = dateTo;
    filters.onlyFresh = onlyFresh;
    filters.limit = limit;
    filters.search = search;
    
    // Don't apply immediately for types/categories/sources to allow multiple selections
    if (selectedTypes.length === 0) {
      // If no types selected, select all (prevent no results)
      selectedTypes = contentTypes.map(t => t.value);
    }
  });
  
  /**
   * @param {string} value
   * @returns {void}
   */
  function toggleType(value) {
    if (selectedTypes.includes(value)) {
      selectedTypes = selectedTypes.filter(t => t !== value);
    } else {
      selectedTypes = [...selectedTypes, value];
    }
  }
  
  /**
   * @param {string} value
   * @returns {void}
   */
  function toggleCategory(value) {
    if (selectedCategories.includes(value)) {
      selectedCategories = selectedCategories.filter(c => c !== value);
    } else {
      selectedCategories = [...selectedCategories, value];
    }
  }
  
  /**
   * @param {string} value
   * @returns {void}
   */
  function toggleSource(value) {
    if (selectedSources.includes(value)) {
      selectedSources = selectedSources.filter(s => s !== value);
    } else {
      selectedSources = [...selectedSources, value];
    }
  }
  
  /**
   * Apply all filters and dispatch the filter event
   */
  function applyFilters() {
    // Create a clean filter object with ISO format dates
    const appliedFilters = {
      search,
      types: selectedTypes,
      categories: selectedCategories,
      sources: selectedSources,
      dateFrom: dateFrom ? dateFrom.toISOString() : null,
      dateTo: dateTo ? dateTo.toISOString() : null,
      onlyFresh,
      limit
    };
    
    dispatch('filter', appliedFilters);
  }
  
  /**
   * Reset all filters to default values
   */
  function resetFilters() {
    search = defaultFilters.search;
    selectedTypes = [...defaultFilters.types];
    selectedCategories = [];
    selectedSources = [];
    dateFrom = null;
    dateTo = null;
    onlyFresh = defaultFilters.onlyFresh;
    limit = defaultFilters.limit;
    
    // Apply the reset filters
    applyFilters();
  }
  
  /**
   * Function to load categories from the backend
   * Currently uses mock data for demo
   */
  async function loadCategories() {
    // Mock data - would be replaced with actual API call
    categories = [
      { id: '1', name: 'Technology' },
      { id: '2', name: 'Business' },
      { id: '3', name: 'Health' },
      { id: '4', name: 'Science' },
      { id: '5', name: 'Politics' }
    ];
  }
  
  /**
   * Function to load sources from the backend
   * Currently uses mock data for demo
   */
  async function loadSources() {
    // Mock data - would be replaced with actual API call
    sources = [
      { id: '1', name: 'The New York Times' },
      { id: '2', name: 'The Washington Post' },
      { id: '3', name: 'The Wall Street Journal' },
      { id: '4', name: 'BBC News' },
      { id: '5', name: 'CNN' }
    ];
  }
  
  // State for available content types
  let availableContentTypes = $state([]);
  let loadingContentTypes = $state(true);
  let contentTypesError = $state('');
  
  onMount(async () => {
    loadingContentTypes = true;
    contentTypesError = '';
    try {
      const types = await fetchContentTypes();
      availableContentTypes = Array.isArray(types) ? types : [];
    } catch (err) {
      contentTypesError = 'Failed to load content types.';
      availableContentTypes = [];
    } finally {
      loadingContentTypes = false;
    }
    loadCategories();
    loadSources();
  });
</script>

<Card class="w-full">
  <CardHeader>
    <CardTitle>Content Filters</CardTitle>
  </CardHeader>
  <CardContent>
    <div class="space-y-4">
      <!-- Search Field -->
      <div class="w-full">
        <Label for="search">Search</Label>
        <div class="flex gap-2">
          <Input 
            id="search" 
            type="text" 
            placeholder="Search content..." 
            bind:value={search}
          />
          {#if search}
            <Button variant="ghost" size="icon" on:click={() => search = ''}>
              <Icon icon={X} class="h-4 w-4" />
              <span class="sr-only">Clear search</span>
            </Button>
          {/if}
        </div>
      </div>
      
      <!-- Advanced Filters -->
      <Accordion type="single" collapsible>
        <AccordionItem value="advanced-filters">
          <AccordionTrigger on:click={() => isExpanded = !isExpanded}>
            Advanced Filters
          </AccordionTrigger>
          <AccordionContent>
            <div class="space-y-4 pt-2">
              <!-- Content Types -->
              <div>
                <Label class="mb-2 block">Content Types</Label>
                <div class="flex flex-wrap gap-2">
                  {#if loadingContentTypes}
                    <span>Loading content types...</span>
                  {:else if contentTypesError}
                    <span class="text-[hsl(var(--functional-error))]">{contentTypesError}</span>
                  {:else}
                    <Select>
                      <SelectTrigger>
                        <span>Select content type</span>
                      </SelectTrigger>
                      <SelectContent>
                        {#each availableContentTypes as type}
                          <SelectItem value={type}>{type}</SelectItem>
                        {/each}
                      </SelectContent>
                    </Select>
                  {/if}
                </div>
              </div>
              
              <!-- Date Range -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="date-from">From Date</Label>
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button variant="outline" class="w-full justify-start text-left font-normal">
                        {#if dateFrom}
                          {format(dateFrom, 'PPP')}
                        {:else}
                          <span class="text-muted-foreground">Select date</span>
                        {/if}
                        <Icon icon={CalendarIcon} class="ml-auto h-4 w-4 opacity-50" />
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0" align="start">
                      <Calendar mode="single" bind:selected={dateFrom} />
                    </PopoverContent>
                  </Popover>
                </div>
                <div>
                  <Label for="date-to">To Date</Label>
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button variant="outline" class="w-full justify-start text-left font-normal">
                        {#if dateTo}
                          {format(dateTo, 'PPP')}
                        {:else}
                          <span class="text-muted-foreground">Select date</span>
                        {/if}
                        <Icon icon={CalendarIcon} class="ml-auto h-4 w-4 opacity-50" />
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0" align="start">
                      <Calendar mode="single" bind:selected={dateTo} />
                    </PopoverContent>
                  </Popover>
                </div>
              </div>
              
              <!-- Fresh Content Toggle -->
              <div class="flex items-center space-x-2">
                <Checkbox id="fresh" bind:checked={onlyFresh} />
                <Label for="fresh" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                  Only show fresh content (last 24 hours)
                </Label>
              </div>
              
              <!-- Categories -->
              <div>
                <Label class="mb-2 block">Categories</Label>
                <div class="flex flex-wrap gap-2">
                  {#each categories as category}
                    <Badge 
                      class="cursor-pointer"
                      variant={selectedCategories.includes(category.id) ? "default" : "outline"} 
                      on:click={() => toggleCategory(category.id)}
                    >
                      {category.name}
                    </Badge>
                  {/each}
                </div>
              </div>
              
              <!-- Sources -->
              <div>
                <Label class="mb-2 block">Sources</Label>
                <div class="flex flex-wrap gap-2">
                  {#each sources as source}
                    <Badge 
                      class="cursor-pointer"
                      variant={selectedSources.includes(source.id) ? "default" : "outline"} 
                      on:click={() => toggleSource(source.id)}
                    >
                      {source.name}
                    </Badge>
                  {/each}
                </div>
              </div>
              
              <!-- Items per Page -->
              <div>
                <Label for="limit">Items Per Page</Label>
                <Select bind:value={limit}>
                  <SelectTrigger>
                  </SelectTrigger>
                  <SelectContent>
                    {#each limitOptions as option}
                      <SelectItem value={option.value}>{option.label}</SelectItem>
                    {/each}
                  </SelectContent>
                </Select>
              </div>
            </div>
          </AccordionContent>
        </AccordionItem>
      </Accordion>
    </div>
  </CardContent>
  <CardFooter class="flex justify-between">
    <Button variant="outline" on:click={resetFilters}>Reset Filters</Button>
    <Button on:click={applyFilters}>Apply Filters</Button>
  </CardFooter>
</Card>

<style>
.filter-panel {
  min-width: 250px;
}
.input.input-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.9rem;
  border: 1px solid hsl(var(--border));
  border-radius: 0.25rem;
}
.btn {
  padding: 0.25rem 0.75rem;
  border-radius: 0.25rem;
  font-size: 0.95rem;
  cursor: pointer;
}
.btn-primary {
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border: none;
}
.btn-outline {
  background: transparent;
  border: 1px solid hsl(var(--border));
  color: hsl(var(--foreground));
}
</style> 