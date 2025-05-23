<script>
  import { Search, Filter } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  /**
   * @typedef {import('./$types').PageData} PageData
   */
  
  /** @type {PageData} */
  let { data } = $props();
  
  let searchTerm = $state('');
  let category = $state(data.content?.category || 'all');
  let content = $state(data.content?.items || []);
  let pagination = $state(data.pagination || { currentPage: 1, totalPages: 1 });
  
  /**
   * Handle category filter change
   * @param {string} newCategory - New category to filter by
   */
  function filterByCategory(newCategory) {
    category = newCategory;
    window.location.href = `/explore?category=${newCategory}&page=1`;
  }
  
  /**
   * Format date to readable format
   * @param {string} dateString - ISO date string
   * @returns {string} Formatted date
   */
  function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString();
  }
</script>

<!-- Explore Content Header and Search/Filter Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <section>
      <h1 class="text-2xl font-bold mb-6">Explore Content</h1>
      
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Icon icon={Search} size={18} class="text-gray-400" />
          </div>
          <input 
            type="text" 
            placeholder="Search for articles, podcasts, and more..." 
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          />
        </div>
        
        <div class="mt-4 flex flex-wrap gap-2">
          <button 
            class="flex items-center gap-1 {category === 'all' ? 'bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))]' : 'bg-gray-200 dark:bg-gray-700'} px-3 py-1 rounded-full text-sm"
            onclick={() => filterByCategory('all')}
          >
            All
          </button>
          <button 
            class="flex items-center gap-1 {category === 'finance' ? 'bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))]' : 'bg-gray-200 dark:bg-gray-700'} px-3 py-1 rounded-full text-sm"
            onclick={() => filterByCategory('finance')}
          >
            Finance
          </button>
          <button 
            class="flex items-center gap-1 {category === 'technology' ? 'bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))]' : 'bg-gray-200 dark:bg-gray-700'} px-3 py-1 rounded-full text-sm"
            onclick={() => filterByCategory('technology')}
          >
            Technology
          </button>
          <button 
            class="flex items-center gap-1 {category === 'health' ? 'bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))]' : 'bg-gray-200 dark:bg-gray-700'} px-3 py-1 rounded-full text-sm"
            onclick={() => filterByCategory('health')}
          >
            Health
          </button>
          <button 
            class="flex items-center gap-1 {category === 'environment' ? 'bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))]' : 'bg-gray-200 dark:bg-gray-700'} px-3 py-1 rounded-full text-sm"
            onclick={() => filterByCategory('environment')}
          >
            Environment
          </button>
          <button 
            class="flex items-center gap-1 {category === 'politics' ? 'bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))]' : 'bg-gray-200 dark:bg-gray-700'} px-3 py-1 rounded-full text-sm"
            onclick={() => filterByCategory('politics')}
          >
            Politics
          </button>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- Content Grid Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each content as item}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">
              {item.type}
            </div>
            <h3 class="font-medium mb-2">{item.title || item.term || ''}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
              {item.summary || item.definition || item.content || ''}
            </p>
            <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
              <span>{formatDate(item.date)}</span>
              <span>{item.source || item.author || item.subreddit || ''}</span>
            </div>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

{#if pagination.totalPages > 1}
  <!-- Pagination Section - Treat as Gridstack item -->
  <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="1">
    <div class="grid-stack-item-content">
      <div class="mt-8 flex justify-center gap-2">
        {#each Array(pagination.totalPages) as _, i}
          <a 
            href={`/explore?category=${category}&page=${i+1}`}
            class="px-3 py-1 rounded {pagination.currentPage === i+1 ? 'bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))]' : 'bg-gray-200 dark:bg-gray-700'}"
          >
            {i+1}
          </a>
        {/each}
      </div>
    </div>
  </div>
{/if} 