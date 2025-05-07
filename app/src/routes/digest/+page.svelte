<script>
  import { onMount } from 'svelte';
  import { Calendar, Clock } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  /** @typedef {Object} Digest 
   * @property {string} id - Digest ID
   * @property {string} title - Digest title
   * @property {string} date - Publication date
   * @property {number} articleCount - Number of articles
   * @property {string} category - Digest category
   * @property {string} description - Digest description
   */
  
  /** @type {import('./$types').PageData} */
  const { data } = $props();
  
  // Make a copy of the server data for local use
  let recentDigests = $state(data.digests.slice(0, 4));
  let trendingDigests = $state(data.digests.slice(4));
  
  /**
   * Format date to a more readable format
   * @param {string} dateString - ISO date string
   * @returns {string} - Formatted date
   */
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }
  
  /**
   * Navigate to digest detail page
   * @param {string} id - Digest ID
   */
  function viewDigest(id) {
    console.log(`Navigating to digest/${id}`);
    window.location.href = `/digest/${id}`;
  }
</script>

<div class="container py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold mb-2 text-[hsl(var(--foreground))]">Your Digests</h1>
    <p class="text-[hsl(var(--muted-foreground))]">
      Personalized content collections based on your interests and reading history.
    </p>
  </div>
  
  <!-- Recent Digests -->
  <section class="mb-10">
    <h2 class="text-xl font-semibold mb-4 text-[hsl(var(--foreground))]">Recent Digests</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {#each recentDigests as digest}
        <button 
          type="button"
          class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer text-left"
          onclick={() => viewDigest(digest.id)}
          onkeydown={(e) => e.key === 'Enter' && viewDigest(digest.id)}
        >
          <div class="p-5">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium px-2 py-1 rounded-full bg-[hsl(var(--muted))] text-[hsl(var(--muted-foreground))]">
                {digest.category}
              </span>
              <span class="text-sm text-[hsl(var(--muted-foreground))] flex items-center">
                <Icon icon={Calendar} size={14} class="mr-1" color="currentColor" />
                {formatDate(digest.date)}
              </span>
            </div>
            
            <h3 class="text-lg font-semibold mb-2 text-[hsl(var(--foreground))]">{digest.title}</h3>
            <p class="text-sm text-[hsl(var(--muted-foreground))] mb-4">{digest.description}</p>
            
            <div class="flex items-center text-xs text-[hsl(var(--muted-foreground))]">
              <Icon icon={Clock} size={14} class="mr-1" color="currentColor" />
              <span>{digest.articleCount} articles</span>
            </div>
          </div>
          
          <div class="p-3 bg-[hsl(var(--muted)/0.3)] border-t border-[hsl(var(--border))] text-center">
            <span class="text-sm font-medium text-[hsl(var(--primary))]">Read Digest</span>
          </div>
        </button>
      {/each}
    </div>
  </section>
  
  <!-- Trending Digests -->
  <section>
    <h2 class="text-xl font-semibold mb-4 text-[hsl(var(--foreground))]">Trending Digests</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {#each trendingDigests as digest}
        <button 
          type="button"
          class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer text-left"
          onclick={() => viewDigest(digest.id)}
          onkeydown={(e) => e.key === 'Enter' && viewDigest(digest.id)}
        >
          <div class="p-5">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium px-2 py-1 rounded-full bg-[hsl(var(--muted))] text-[hsl(var(--muted-foreground))]">
                {digest.category}
              </span>
              <span class="text-sm text-[hsl(var(--muted-foreground))] flex items-center">
                <Icon icon={Calendar} size={14} class="mr-1" color="currentColor" />
                {formatDate(digest.date)}
              </span>
            </div>
            
            <h3 class="text-lg font-semibold mb-2 text-[hsl(var(--foreground))]">{digest.title}</h3>
            <p class="text-sm text-[hsl(var(--muted-foreground))] mb-4">{digest.description}</p>
            
            <div class="flex items-center text-xs text-[hsl(var(--muted-foreground))]">
              <Icon icon={Clock} size={14} class="mr-1" color="currentColor" />
              <span>{digest.articleCount} articles</span>
            </div>
          </div>
          
          <div class="p-3 bg-[hsl(var(--muted)/0.3)] border-t border-[hsl(var(--border))] text-center">
            <span class="text-sm font-medium text-[hsl(var(--primary))]">Read Digest</span>
          </div>
        </button>
      {/each}
    </div>
  </section>
</div>