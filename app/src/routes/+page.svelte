<script>
  import { Calendar, Bookmark, Share2, ArrowUpRight } from 'lucide-svelte';
  import ArticleWidget from '$lib/components/widgets/ArticleWidget.svelte';
  import PodcastWidget from '$lib/components/widgets/PodcastWidget.svelte';
  import { fetchArticles } from '$lib/api/wordpress.js';
  import { onMount } from 'svelte';
  import { LAYOUT_SPACING, GRID_SPACING } from '$lib/styles/spacing.js';
  
  /**
   * Widget type definition
   * @typedef {Object} Widget
   * @property {string} type - Type of widget ('article' or 'podcast')
   * @property {string} id - Unique identifier for the widget
   */
  
  /**
   * Drag widget info
   * @typedef {Object} DraggedWidget
   * @property {number} widget - Index of the widget being dragged
   * @property {string} column - Column the widget is being dragged from ('left', 'center', or 'right')
   */
  
  /**
   * Drag target info
   * @typedef {Object} DragTarget
   * @property {string} column - Column the widget is being dragged to ('left', 'center', or 'right')
   * @property {number} index - Index where the widget should be inserted
   */
  
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
  
  // Track if a widget is being dragged
  let isDragging = $state(false);
  
  /**
   * Currently dragged widget
   * @type {DraggedWidget|null}
   */
  let draggedWidget = $state(null);
  
  /**
   * Current drag target
   * @type {DragTarget|null}
   */
  let dragTarget = $state(null);
  
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
   * @type {Array<Widget>}
   */
  const leftColumnWidgets = $state([
    { type: 'article', id: 'article-1' },
    { type: 'podcast', id: 'podcast-1' }
  ]);
  
  /**
   * @type {Array<Widget>}
   */
  const centerColumnWidgets = $state([
    { type: 'article', id: 'article-2' },
    { type: 'podcast', id: 'podcast-2' }
  ]);
  
  /**
   * @type {Array<Widget>}
   */
  const rightColumnWidgets = $state([
    { type: 'article', id: 'article-3' },
    { type: 'podcast', id: 'podcast-3' }
  ]);
  
  /**
   * Handle drag start event
   * @param {number} widget - Index of the widget being dragged
   * @param {string} column - Column the widget is being dragged from ('left', 'center', or 'right')
   */
  function handleDragStart(widget, column) {
    isDragging = true;
    draggedWidget = { widget, column };
  }
  
  /**
   * Handle drag over event
   * @param {string} column - Column the widget is being dragged over ('left', 'center', or 'right')
   * @param {number} index - Index where the widget would be inserted
   */
  function handleDragOver(column, index) {
    dragTarget = { column, index };
  }
  
  /**
   * Handle drop event to complete the drag and drop operation
   */
  function handleDrop() {
    if (!draggedWidget || !dragTarget) return;
    
    // Remove from original position
    let widgetToMove;
    if (draggedWidget.column === 'left') {
      widgetToMove = leftColumnWidgets.splice(draggedWidget.widget, 1)[0];
    } else if (draggedWidget.column === 'center') {
      widgetToMove = centerColumnWidgets.splice(draggedWidget.widget, 1)[0];
    } else if (draggedWidget.column === 'right') {
      widgetToMove = rightColumnWidgets.splice(draggedWidget.widget, 1)[0];
    }
    
    // Add to new position
    if (dragTarget.column === 'left') {
      leftColumnWidgets.splice(dragTarget.index, 0, widgetToMove);
    } else if (dragTarget.column === 'center') {
      centerColumnWidgets.splice(dragTarget.index, 0, widgetToMove);
    } else if (dragTarget.column === 'right') {
      rightColumnWidgets.splice(dragTarget.index, 0, widgetToMove);
    }
    
    // Reset drag state
    isDragging = false;
    draggedWidget = null;
    dragTarget = null;
  }
  
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

<main class="container mx-auto {LAYOUT_SPACING.container}">
  <h1 class="text-2xl font-bold text-[hsl(var(--foreground))] {LAYOUT_SPACING.pageHeader}">Today's ASAP Digest</h1>

  <!-- Three-column draggable widget layout -->
  <div class="grid grid-cols-1 md:grid-cols-3 {GRID_SPACING.standard}">
    
    <!-- Left Column -->
    <div 
      class="col-span-1 flex flex-col gap-6" 
      role="region"
      aria-label="Top Stories Column"
      ondragover={(e) => {
        e.preventDefault();
        handleDragOver('left', leftColumnWidgets.length);
      }}
      ondrop={(e) => {
        e.preventDefault();
        handleDrop();
      }}
    >
      <h2 class="text-xl font-semibold text-[hsl(var(--foreground))]">Top Stories</h2>
      
      {#each leftColumnWidgets as widget, index}
        <div 
          class="cursor-move" 
          role="listitem"
          draggable="true"
          ondragstart={() => handleDragStart(index, 'left')}
          ondragover={(e) => {
            e.preventDefault();
            handleDragOver('left', index);
          }}
        >
          {#if widget.type === 'article'}
            <ArticleWidget 
              id="article-1"
              title="AI Development Accelerates"
              excerpt="New breakthroughs in artificial intelligence are changing the landscape of technology development."
              source="TechInsider"
              sourceUrl="https://techinsider.com/ai-development"
              date="Today"
              tags={['AI', 'Technology']}
            />
          {:else if widget.type === 'podcast'}
            <PodcastWidget 
              id="podcast-1"
              title="The AI Revolution"
              summary="In this episode, we discuss how artificial intelligence is transforming industries and what it means for the future of work."
              episode={42}
              duration={28}
            />
          {/if}
        </div>
      {/each}
    </div>
    
    <!-- Center Column -->
    <div 
      class="col-span-1 flex flex-col gap-6" 
      role="region"
      aria-label="Featured Content Column"
      ondragover={(e) => {
        e.preventDefault();
        handleDragOver('center', centerColumnWidgets.length);
      }}
      ondrop={(e) => {
        e.preventDefault();
        handleDrop();
      }}
    >
      <h2 class="text-xl font-semibold text-[hsl(var(--foreground))]">Featured</h2>
      
      {#each centerColumnWidgets as widget, index}
        <div 
          class="cursor-move" 
          role="listitem"
          draggable="true"
          ondragstart={() => handleDragStart(index, 'center')}
          ondragover={(e) => {
            e.preventDefault();
            handleDragOver('center', index);
          }}
        >
          {#if widget.type === 'article'}
            <ArticleWidget 
              id="article-2"
              title="Market Update: Crypto Trends"
              excerpt="Bitcoin reaches new heights as institutional adoption continues to grow."
              source="CryptoNews"
              sourceUrl="https://cryptonews.com/bitcoin-trends"
              date="Yesterday"
              tags={['Crypto', 'Markets']}
            />
          {:else if widget.type === 'podcast'}
            <PodcastWidget 
              id="podcast-2"
              title="Crypto Market Analysis"
              summary="Our experts break down the latest trends in cryptocurrency markets and provide insights on where things might be heading."
              episode={43}
              duration={32}
            />
          {/if}
        </div>
      {/each}
    </div>
    
    <!-- Right Column -->
    <div 
      class="col-span-1 flex flex-col gap-6" 
      role="region"
      aria-label="Your Interests Column"
      ondragover={(e) => {
        e.preventDefault();
        handleDragOver('right', rightColumnWidgets.length);
      }}
      ondrop={(e) => {
        e.preventDefault();
        handleDrop();
      }}
    >
      <h2 class="text-xl font-semibold text-[hsl(var(--foreground))]">Your Interests</h2>
      
      {#each rightColumnWidgets as widget, index}
        <div 
          class="cursor-move" 
          role="listitem"
          draggable="true"
          ondragstart={() => handleDragStart(index, 'right')}
          ondragover={(e) => {
            e.preventDefault();
            handleDragOver('right', index);
          }}
        >
          {#if widget.type === 'article'}
            <ArticleWidget 
              id="article-3"
              title="The Rise of Decentralized Finance"
              excerpt="DeFi platforms are creating a new financial ecosystem that operates without traditional intermediaries."
              source="DeFiNews"
              sourceUrl="https://definews.com/rise-of-defi"
              date="3 days ago"
              tags={['DeFi', 'Blockchain']}
            />
          {:else if widget.type === 'podcast'}
            <PodcastWidget 
              id="podcast-3"
              title="Tech Startup Ecosystem"
              summary="We dive into the current state of the tech startup ecosystem, funding trends, and how new companies are navigating challenging economic conditions."
              episode={41}
              duration={25}
            />
          {/if}
        </div>
      {/each}
    </div>
  </div>
</main>

<style>
  /* Styling for draggable elements */
  .cursor-move {
    transition: all 0.2s ease;
  }
  
  .cursor-move:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  }
</style>