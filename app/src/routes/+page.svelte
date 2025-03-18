<script>
  import { Calendar, Bookmark, Share2, ArrowUpRight } from 'lucide-svelte';
  import ArticleWidget from '$lib/components/widgets/ArticleWidget.svelte';
  import PodcastWidget from '$lib/components/widgets/PodcastWidget.svelte';
  import { fetchArticles } from '$lib/api/wordpress.js';
  import { onMount } from 'svelte';
  import { GAP, GRID_SPACING } from '$lib/styles/spacing.js';
  
  // Widget management state
  let leftColumnWidgets = [
    { id: 'left-1', type: 'article' },
    { id: 'left-2', type: 'podcast' }
  ];
  
  let centerColumnWidgets = [
    { id: 'center-1', type: 'article' },
    { id: 'center-2', type: 'podcast' }
  ];
  
  let rightColumnWidgets = [
    { id: 'right-1', type: 'article' },
    { id: 'right-2', type: 'podcast' }
  ];
  
  // Drag and drop state
  let draggedWidget = null;
  let dragSource = null;
  
  function handleDragStart(index, source) {
    if (source === 'left') {
      draggedWidget = leftColumnWidgets[index];
    } else if (source === 'center') {
      draggedWidget = centerColumnWidgets[index];
    } else if (source === 'right') {
      draggedWidget = rightColumnWidgets[index];
    }
    dragSource = source;
  }
  
  function handleDragOver(target, index) {
    // Implement drag over logic
    // This could update a visual indicator of where the widget would be dropped
  }
  
  function handleDrop() {
    if (!draggedWidget || !dragSource) return;
    
    // Handle moving widgets between columns
    // This is a placeholder for the actual implementation
    console.log(`Moving widget from ${dragSource} to target`);
    
    // Reset drag state
    draggedWidget = null;
    dragSource = null;
  }
</script>

<div class="grid-layout">
  <h1 class="text-2xl font-bold text-[hsl(var(--foreground))] mb-8 col-span-full">Today's ASAP Digest</h1>

  <!-- Three-column draggable widget layout using the standardized grid system -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 col-span-full">
    <!-- Left Column -->
    <div 
      class="flex flex-col gap-8" 
      role="region"
      aria-label="Latest News Column"
      ondragover={(e) => {
        e.preventDefault();
        handleDragOver('left', leftColumnWidgets.length);
      }}
      ondrop={(e) => {
        e.preventDefault();
        handleDrop();
      }}
    >
      <h2 class="text-xl font-semibold text-[hsl(var(--foreground))]">Latest News</h2>
      
      {#each leftColumnWidgets as widget, index}
        <div 
          class="cursor-move mb-8" 
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
              title="AI Transforms Healthcare"
              excerpt="New research shows how artificial intelligence is revolutionizing diagnostics and treatment planning."
              source="TechNews"
              sourceUrl="https://technews.com/ai-healthcare"
              date="Today"
              tags={['AI', 'Healthcare']}
            />
          {:else if widget.type === 'podcast'}
            <PodcastWidget 
              id="podcast-1"
              title="AI in Healthcare"
              summary="In this episode, we discuss the latest advancements in AI-powered healthcare solutions and their potential impact on patient outcomes."
              episode={42}
              duration={28}
            />
          {/if}
        </div>
      {/each}
    </div>
    
    <!-- Center Column -->
    <div 
      class="flex flex-col gap-8" 
      role="region"
      aria-label="Financial Updates Column"
      ondragover={(e) => {
        e.preventDefault();
        handleDragOver('center', centerColumnWidgets.length);
      }}
      ondrop={(e) => {
        e.preventDefault();
        handleDrop();
      }}
    >
      <h2 class="text-xl font-semibold text-[hsl(var(--foreground))]">Financial Updates</h2>
      
      {#each centerColumnWidgets as widget, index}
        <div 
          class="cursor-move mb-8" 
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
      class="flex flex-col gap-8" 
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
          class="cursor-move mb-8" 
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
</div>

<style>
  /* Styling for draggable elements */
  .cursor-move {
    transition: all 0.2s ease;
  }
  
  .cursor-move:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  }
  
  /* Enhance grid layout for better visualization */
  :global(.grid-layout) {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    column-gap: 1.5rem;
    row-gap: 1.5rem;
    width: 100%;
  }
  
  :global(.col-span-full) {
    grid-column: 1 / -1;
  }
</style>