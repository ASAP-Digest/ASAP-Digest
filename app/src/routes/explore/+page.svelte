<script>
  import { Search, Filter } from 'lucide-svelte';
</script>

<div class="space-y-8">
  <section>
    <h1 class="text-2xl font-bold mb-6">Explore Content</h1>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Search size={18} class="text-gray-400" />
        </div>
        <input 
          type="text" 
          placeholder="Search for articles, podcasts, and more..." 
          class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
        />
      </div>
      
      <div class="mt-4 flex flex-wrap gap-2">
        <button class="flex items-center gap-1 bg-primary/10 text-primary px-3 py-1 rounded-full text-sm">
          Articles
        </button>
        <button class="flex items-center gap-1 bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded-full text-sm">
          Podcasts
        </button>
        <button class="flex items-center gap-1 bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded-full text-sm">
          Key Terms
        </button>
        <button class="flex items-center gap-1 bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded-full text-sm">
          Financial
        </button>
        <button class="flex items-center gap-1 bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded-full text-sm">
          Social
        </button>
      </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each Array(6) as _, i}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">
              {['Article', 'Podcast', 'Key Term', 'Financial Bite', 'X Post', 'Reddit Buzz'][i % 6]}
            </div>
            <h3 class="font-medium mb-2">Placeholder Content #{i + 1}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
              This is a placeholder for the {['Article', 'Podcast', 'Key Term', 'Financial Bite', 'X Post', 'Reddit Buzz'][i % 6]} content type.
            </p>
            <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
              <span>{new Date().toLocaleDateString()}</span>
              <span>Content Source</span>
            </div>
          </div>
        </div>
      {/each}
    </div>
  </section>
</div> 