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

<main class="container mx-auto px-4 py-6">
  <h1 class="text-2xl font-bold text-[hsl(var(--foreground))] mb-6">Today's ASAP Digest</h1>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Articles Section -->
    <div class="col-span-1 md:col-span-2 space-y-6">
      <h2 class="text-xl font-semibold text-[hsl(var(--foreground))] mb-4">Top Articles</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <ArticleWidget 
          id="article-1"
          title="AI Development Accelerates"
          excerpt="New breakthroughs in artificial intelligence are changing the landscape of technology development."
          source="TechInsider"
          sourceUrl="https://techinsider.com/ai-development"
          date="Today"
          tags={['AI', 'Technology']}
        />
        <ArticleWidget 
          id="article-2"
          title="Market Update: Crypto Trends"
          excerpt="Bitcoin reaches new heights as institutional adoption continues to grow."
          source="CryptoNews"
          sourceUrl="https://cryptonews.com/bitcoin-trends"
          date="Yesterday"
          tags={['Crypto', 'Markets']}
        />
      </div>
    </div>

    <!-- Podcast Section -->
    <div class="col-span-1 md:col-span-2 space-y-6">
      <h2 class="text-xl font-semibold text-[hsl(var(--foreground))] mb-4">Featured Podcasts</h2>
      <div class="grid grid-cols-1 gap-6">
        <PodcastWidget 
          id="podcast-1"
          title="The AI Revolution"
          summary="Exploring the latest developments in artificial intelligence and their implications for society."
          episode={42}
          duration={25}
        />
      </div>
    </div>
  </div>

  <!-- More Sections with proper spacing -->
  <div class="mt-10 space-y-6">
    <h2 class="text-xl font-semibold text-[hsl(var(--foreground))] mb-4">From Your Interests</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- More widgets can go here -->
    </div>
  </div>
</main>

<style>
  .btn {
    transition: all 0.2s ease;
  }
</style>