<script>
  import { Calendar, Bookmark, Share2, ArrowUpRight } from 'lucide-svelte';
  import ArticleWidget from '$lib/components/widgets/ArticleWidget.svelte';
  import PodcastWidget from '$lib/components/widgets/PodcastWidget.svelte';
  import { fetchArticles } from '$lib/api/wordpress.js';
  import { onMount } from 'svelte';
  
  /**
   * State for articles
   * @type {Array<import('$lib/api/wordpress.js').ArticleProps>}
   */
  let articles = $state([]);
  
  /**
   * Loading state for articles
   * @type {boolean}
   */
  let isLoading = $state(true);
  
  /**
   * Error state for articles
   * @type {string|null}
   */
  let error = $state(null);
  
  // Sample article data as fallback
  const sampleArticles = [
    {
      id: 'article-1',
      title: 'The Impact of AI on Financial Markets',
      excerpt: 'Artificial intelligence is revolutionizing financial markets with predictive analytics and automated trading strategies that are reshaping how investors approach decision-making.',
      source: 'Financial Times',
      sourceUrl: 'https://ft.com',
      imageUrl: 'https://images.unsplash.com/photo-1642964059019-4296421060ae?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=928&q=80',
      date: '2025-03-15',
      tags: ['AI', 'Markets', 'FinTech']
    },
    {
      id: 'article-2',
      title: 'Digital Privacy in the Age of Big Data',
      excerpt: 'As tech companies gather increasing amounts of user data, privacy concerns are mounting. New regulations aim to protect consumers while allowing innovation.',
      source: 'TechCrunch',
      sourceUrl: 'https://techcrunch.com',
      imageUrl: 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=928&q=80',
      date: '2025-03-14',
      tags: ['Privacy', 'Tech', 'Regulation']
    },
    {
      id: 'article-3',
      title: 'The Rise of Decentralized Finance',
      excerpt: 'DeFi platforms are creating a new financial ecosystem that operates without traditional intermediaries, offering both opportunities and risks for investors.',
      source: 'CoinDesk',
      sourceUrl: 'https://coindesk.com',
      imageUrl: 'https://images.unsplash.com/photo-1639762681057-408e52192e55?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=928&q=80',
      date: '2025-03-13',
      tags: ['DeFi', 'Crypto', 'Finance']
    }
  ];
  
  // Sample podcast data as placeholder
  const samplePodcasts = [
    {
      id: 'podcast-1',
      title: 'The Future of AI',
      summary: 'In this episode, we explore how artificial intelligence will shape the future of various industries and our daily lives. Experts discuss emerging trends and potential challenges.',
      episode: 12,
      duration: 28
    },
    {
      id: 'podcast-2',
      title: 'Crypto Market Analysis',
      summary: 'Our financial experts break down the latest developments in cryptocurrency markets, regulatory changes, and what investors should know about the evolving digital asset landscape.',
      episode: 11,
      duration: 32
    },
    {
      id: 'podcast-3',
      title: 'Tech Startup Ecosystem',
      summary: 'We dive into the current state of the tech startup ecosystem, funding trends, and how new companies are navigating challenging economic conditions while pursuing innovation.',
      episode: 10,
      duration: 25
    }
  ];
  
  /**
   * Fetch articles from the WordPress API
   */
  async function loadArticles() {
    isLoading = true;
    error = null;
    
    try {
      const fetchedArticles = await fetchArticles({ perPage: 9 });
      
      if (fetchedArticles && fetchedArticles.length > 0) {
        articles = fetchedArticles;
      } else {
        // If no articles are returned, use sample articles as fallback
        articles = sampleArticles;
        console.warn('No articles returned from API, using sample data');
      }
    } catch (err) {
      console.error('Failed to fetch articles:', err);
      error = err instanceof Error ? err.message : String(err);
      articles = sampleArticles; // Use sample articles as fallback
    } finally {
      isLoading = false;
    }
  }
  
  // Load articles on component mount
  onMount(loadArticles);
</script>

<div class="space-y-8">
  <section class="bg-primary/10 rounded-lg p-6 md:p-8">
    <h1 class="text-2xl md:text-3xl font-bold mb-4">Welcome to ASAP Digest</h1>
    <p class="text-lg mb-6">Your daily curated feed of essential AI, tech, and finance updates.</p>
    <div class="flex flex-col sm:flex-row gap-4">
      <a href="/today" class="btn bg-primary text-white hover:bg-primary/90 px-4 py-2 rounded-md flex items-center gap-2">
        <Calendar size={18} />
        <span>Today's Digest</span>
      </a>
      <a href="/auth/login" class="btn bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 px-4 py-2 rounded-md flex items-center gap-2">
        <Bookmark size={18} />
        <span>Personalize Your Feed</span>
      </a>
    </div>
  </section>

  <section>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Latest Digests</h2>
      <a href="/archive" class="text-primary flex items-center gap-1 text-sm">
        <span>View All</span>
        <ArrowUpRight size={16} />
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each Array(6) as _, i}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4">
            <div class="flex justify-between items-start mb-3">
              <span class="text-sm text-gray-500 dark:text-gray-400">{new Date().toLocaleDateString()}</span>
              <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <Share2 size={16} />
              </button>
            </div>
            <h3 class="font-medium mb-2">Daily Digest #{i + 1}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
            </p>
            <a href={`/digest/${i + 1}`} class="text-primary text-sm flex items-center gap-1">
              Read More
              <ArrowUpRight size={14} />
            </a>
          </div>
        </div>
      {/each}
    </div>
  </section>

  <section>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Today's Articles</h2>
      <a href="/articles" class="text-primary flex items-center gap-1 text-sm">
        <span>View All</span>
        <ArrowUpRight size={16} />
      </a>
    </div>
    
    {#if isLoading}
      <div class="flex justify-center items-center p-12">
        <div class="w-12 h-12 border-t-4 border-primary border-solid rounded-full animate-spin"></div>
      </div>
    {:else if error}
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <p>Error loading articles: {error}</p>
        <p class="text-sm mt-1">Showing sample articles instead.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {#each sampleArticles as article}
          <ArticleWidget {...article} />
        {/each}
      </div>
    {:else}
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {#each articles as article}
          <ArticleWidget {...article} />
        {/each}
      </div>
    {/if}
  </section>
  
  <!-- Podcasts Section -->
  <section>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Featured Podcasts</h2>
      <a href="/podcasts" class="text-primary flex items-center gap-1 text-sm">
        <span>View All</span>
        <ArrowUpRight size={16} />
      </a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {#each samplePodcasts as podcast}
        <PodcastWidget {...podcast} />
      {/each}
    </div>
  </section>
</div>

<style>
  .btn {
    transition: all 0.2s ease;
  }
</style>