<script>
  import { onMount } from 'svelte';
  import { Calendar, Clock } from '$lib/utils/lucide-icons.js';
  import Icon from '$lib/components/ui/Icon.svelte';
  
  /** @typedef {Object} Digest 
   * @property {string} id - Digest ID
   * @property {string} title - Digest title
   * @property {string} date - Publication date
   * @property {number} articleCount - Number of articles
   * @property {string} category - Digest category
   * @property {string} description - Digest description
   */
  
  /** @type {Digest[]} */
  const recentDigests = [
    {
      id: 'finance-mar20',
      title: 'Finance Weekly Roundup',
      date: '2024-03-20',
      articleCount: 12,
      category: 'Finance',
      description: 'Key market trends and financial news from the past week.'
    },
    {
      id: 'tech-mar19',
      title: 'Tech Innovation Digest',
      date: '2024-03-19',
      articleCount: 8,
      category: 'Technology',
      description: 'Latest breakthroughs in AI, robotics, and consumer tech.'
    },
    {
      id: 'health-mar18',
      title: 'Healthcare Updates',
      date: '2024-03-18',
      articleCount: 10,
      category: 'Healthcare',
      description: 'Medical research highlights and healthcare industry news.'
    },
    {
      id: 'politics-mar18',
      title: 'Political Analysis',
      date: '2024-03-18',
      articleCount: 14,
      category: 'Politics',
      description: 'In-depth coverage of global political developments.'
    }
  ];
  
  /** @type {Digest[]} */
  const trendingDigests = [
    {
      id: 'crypto-mar17',
      title: 'Cryptocurrency Special Report',
      date: '2024-03-17',
      articleCount: 7,
      category: 'Finance',
      description: 'Analysis of recent cryptocurrency market movements.'
    },
    {
      id: 'climate-mar16',
      title: 'Climate Change Update',
      date: '2024-03-16',
      articleCount: 9,
      category: 'Environment',
      description: 'Latest research and policy changes regarding climate change.'
    }
  ];
  
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
        <div 
          class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer"
          onclick={() => viewDigest(digest.id)}
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
        </div>
      {/each}
    </div>
  </section>
  
  <!-- Trending Digests -->
  <section>
    <h2 class="text-xl font-semibold mb-4 text-[hsl(var(--foreground))]">Trending Digests</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {#each trendingDigests as digest}
        <div 
          class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer"
          onclick={() => viewDigest(digest.id)}
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
        </div>
      {/each}
    </div>
  </section>
</div>