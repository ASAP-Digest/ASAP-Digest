<script>
  import { Calendar, Play, Download, Share2, Clock, Bookmark } from 'lucide-svelte';
  
  const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
</script>

<div class="space-y-8">
  <section class="bg-[hsl(var(--primary)/0.1)] rounded-lg p-6">
    <div class="flex items-center gap-2 mb-2 text-sm text-gray-600 dark:text-gray-400">
      <Calendar size={16} />
      <span>{today}</span>
    </div>
    
    <h1 class="text-2xl md:text-3xl font-bold mb-4">Today's Digest</h1>
    <p class="text-lg mb-6">Your curated summary of essential updates across AI, tech, and finance.</p>
    
    <div class="flex flex-col sm:flex-row gap-4">
      <button class="bg-[hsl(var(--primary))] text-white hover:bg-[hsl(var(--primary)/0.9)] px-4 py-2 rounded-md flex items-center justify-center gap-2">
        <Play size={18} />
        <span>Listen Now</span>
      </button>
      <button class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 px-4 py-2 rounded-md flex items-center justify-center gap-2">
        <Download size={18} />
        <span>Download Audio</span>
      </button>
      <button class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 px-4 py-2 rounded-md flex items-center justify-center gap-2">
        <Share2 size={18} />
        <span>Share</span>
      </button>
    </div>
  </section>

  <section>
    <h2 class="text-xl font-semibold mb-4">Today's Highlights</h2>
    
    <div class="space-y-4">
      {#each ['Article', 'Podcast', 'Key Term', 'Financial Bite', 'X Post', 'Reddit Buzz', 'Event', 'Polymarket'] as type, i}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-start mb-3">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              {type}
            </div>
            <button class="text-gray-400 hover:text-[hsl(var(--primary))]">
              <Bookmark size={16} />
            </button>
          </div>
          
          <h3 class="font-medium mb-2">Placeholder {type} #{i + 1}</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            This is a placeholder for the {type} content that would appear in today's digest.
            {#if i % 3 === 0}
              It includes additional details that make it longer than the others.
            {/if}
          </p>
          
          <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-1">
              <Clock size={14} />
              <span>{2 + i} min read</span>
            </div>
            <span>Source Name</span>
          </div>
        </div>
      {/each}
    </div>
  </section>
</div> 