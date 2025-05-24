<script>
  import ArticleWidget from '$lib/components/widgets/ArticleWidget.svelte';
  import PodcastWidget from '$lib/components/widgets/PodcastWidget.svelte';
  import { onMount } from 'svelte';
  import { page } from '$app/stores'; // Needed to check sidebar state via layout data if passed
  
  /**
   * @typedef {'normal'|'large'|'full-mobile'|'full-tablet'} WidgetSize
   */
  
  /**
   * @typedef {Object} Widget
   * @property {string} id - Unique identifier
   * @property {'article'|'podcast'} type - Widget type
   * @property {string} title - Widget title
   * @property {string} [excerpt] - Article excerpt if applicable
   * @property {string} [source] - Article source if applicable
   * @property {string} [date] - Article date if applicable
   * @property {string[]} [tags] - Article tags if applicable
   * @property {string} [sourceUrl] - Article source URL if applicable
   * @property {number} [episode] - Podcast episode if applicable
   * @property {number} [duration] - Podcast duration if applicable
   * @property {string} [summary] - Podcast summary if applicable
   * @property {WidgetSize} size - Widget size
   */
  
  /** @type {Widget[]} */
  const newsWidgets = [
    { id: 'news-1', type: 'article', title: 'AI Transforms Healthcare', excerpt: 'New research shows how artificial intelligence is revolutionizing diagnostics and treatment planning.', source: 'Tech Health Journal', date: '2024-03-18', tags: ['AI', 'Healthcare', 'Research'], sourceUrl: 'https://example.com/article', size: 'normal' },
    { id: 'news-2', type: 'podcast', title: 'AI in Healthcare', episode: 42, duration: 28, summary: 'Discussing the latest AI breakthroughs in medical diagnostics and treatment planning.', size: 'normal' }
  ];
  
  /** @type {Widget[]} */
  const financeWidgets = [
    { id: 'finance-1', type: 'article', title: 'Market Update: Crypto Trends', excerpt: 'Bitcoin reaches new heights as institutional adoption continues to grow.', source: 'Crypto Financial News', date: '2024-03-18', tags: ['Crypto', 'Bitcoin', 'Finance'], sourceUrl: 'https://example.com/crypto', size: 'large' },
    { id: 'finance-2', type: 'podcast', title: 'Crypto Market Analysis', episode: 43, duration: 32, summary: 'Analyzing the latest trends in cryptocurrency markets and institutional adoption.', size: 'normal' }
  ];
  
  /** @type {Widget[]} */
  const personalWidgets = [
    { id: 'personal-1', type: 'article', title: 'Personalized Topic', excerpt: 'Content based on your interests and reading history.', source: 'ASAP Digest', date: '2024-03-18', tags: ['Personalized', 'Interests'], sourceUrl: 'https://example.com/personalized', size: 'normal' },
    { id: 'personal-2', type: 'podcast', title: 'Topics You Follow', episode: 1, duration: 15, summary: 'Audio updates on topics you have expressed interest in.', size: 'full-mobile' }
  ];

  // NEW: Get sidebar state from layout (assuming it's passed down or available globally)
  // For simplicity, let's assume we can derive it from screen width for now
  // A better approach might involve a shared store or context API
  let hasSidebar = $state(true);
  $effect(() => {
    // Approximation: Sidebar is visible on desktop screens
    hasSidebar = typeof window !== 'undefined' && window.innerWidth >= 1024;
  });
  
  /**
   * Toggle grid debug mode for development
   * This enables debug styles to help visualize the grid
   */
  function toggleGridDebug() {
    if (typeof window === 'undefined') return;
    
    // Toggle debug class on body to enable debug styles
    document.body.classList.toggle('debug-grid');
    
    const grid = document.querySelector('.grid-stack');
    if (grid) {
      // Toggle the Gridstack animation class for visual debugging
      grid.classList.toggle('grid-stack-animate'); 
    }
  }

  // Enable grid debugging with a key press (Shift+Alt+D)
  function setupDebugToggle() {
    window.addEventListener('keydown', (e) => {
      if (e.shiftKey && e.altKey && e.key === 'D') {
        toggleGridDebug(); 
      }
    });
  }

  // Toggle debug mode with button
  function toggleDebugGrid() {
    toggleGridDebug(); 
  }

  // Set up debug toggle on mount
  onMount(() => {
    setupDebugToggle();
    return () => {
      // No custom cleanup needed for the old dnd
    };
  });

  /**
   * @typedef {import('./$types').PageData} PageData
   */

  /** @type {PageData} */
  let { data } = $props(); 

  // Reactive states based on data or local interactions
  import { getUserData } from '$lib/stores/user.js';
  
  const userData = $derived(() => {
    try {
      return getUserData(data?.user);
    } catch (error) {
      console.error('[Home Page] Error getting user data:', error);
      return getUserData(null); // Return default user data
    }
  });
  let showOnboarding = $state(!userData?.hasCompletedOnboarding);

  // Function to determine default Gridstack item dimensions based on widget size
  function getDefaultGridstackDimensions(widgetSize, hasSidebar) {
    let w, h;
    // Assign width and height based on your desired default layout
    // These are example values and should be refined based on the Golden Ratio Design System
    switch (widgetSize) {
      case 'large':
        w = hasSidebar ? 8 : 6; // Example: Wider without sidebar
        h = 4; // Example height
        break;
      case 'full-mobile':
        w = 12; // Always full width on mobile column count
        h = 3; // Example height
        break;
      case 'full-tablet':
        w = hasSidebar ? 12 : 8; // Example: Full width on tablet column count (8), wider without sidebar
        h = 3; // Example height
        break;
      case 'normal':
      default:
        w = hasSidebar ? 4 : 3; // Example: Standard 1/3 or 1/4 width
        h = 3; // Example height
        break;
    }
    // Ensure width is within bounds (1-12 for a 12-column grid)
    w = Math.max(1, Math.min(12, w));
    // Ensure height is reasonable (e.g., minimum 1)
    h = Math.max(1, h);
    return { w, h };
  }

</script>

<!-- Main Gridstack Container -->
<div class="grid-stack">
  <!-- Header Section -->
  <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="1">
  <div class="grid-stack-item-content">
    <div class="mb-10 space-y-3 p-3">
      <h1
        class="mb-3 text-[var(--font-size-3xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
      >
        Your ASAP Digest
      </h1>
      <p class="text-[var(--font-size-base)] font-[var(--font-body)] text-[hsl(var(--muted-foreground))]">
        Customized content based on your interests
      </p>
    </div>
  </div>
</div>

<!-- Latest News Section Header -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="1">
  <div class="grid-stack-item-content">
    <div class="mb-4 mt-2">
      <h2
        class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
      >
        Latest News
      </h2>
    </div>
  </div>
</div>

<!-- News Widgets -->
{#each newsWidgets as widget (widget.id)}
  {@const { w, h } = getDefaultGridstackDimensions(widget.size, hasSidebar)}
  <div
    class="grid-stack-item" 
    data-gs-id={widget.id} 
    data-gs-w={w} 
    data-gs-h={h} 
    data-gs-auto-position="true"
  >
    <div class="grid-stack-item-content">
      {#if widget.type === 'article'}
        <ArticleWidget 
          id={widget.id || ''}
          title={widget.title || ''}
          excerpt={widget.excerpt || ''}
          source={widget.source || ''}
          date={widget.date || ''}
          tags={widget.tags || []}
          sourceUrl={widget.sourceUrl || ''}
        />
      {:else if widget.type === 'podcast'}
        <PodcastWidget
          id={widget.id || ''}
          title={widget.title || ''}
          episode={typeof widget.episode === 'number' ? widget.episode : 1}
          duration={typeof widget.duration === 'number' ? widget.duration : 0}
          summary={widget.summary || ''}
        />
      {/if}
    </div>
  </div>
{/each}

<!-- Financial Updates Section Header -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="1">
  <div class="grid-stack-item-content">
    <div class="mb-4 mt-6">
      <h2
        class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
      >
        Financial Updates
      </h2>
    </div>
  </div>
</div>

<!-- Finance Widgets -->
{#each financeWidgets as widget (widget.id)}
  {@const { w, h } = getDefaultGridstackDimensions(widget.size, hasSidebar)}
  <div
    class="grid-stack-item" 
    data-gs-id={widget.id} 
    data-gs-w={w} 
    data-gs-h={h} 
    data-gs-auto-position="true"
  >
    <div class="grid-stack-item-content">
      {#if widget.type === 'article'}
        <ArticleWidget 
          id={widget.id || ''}
          title={widget.title || ''}
          excerpt={widget.excerpt || ''}
          source={widget.source || ''}
          date={widget.date || ''}
          tags={widget.tags || []}
          sourceUrl={widget.sourceUrl || ''}
        />
      {:else if widget.type === 'podcast'}
        <PodcastWidget
          id={widget.id || ''}
          title={widget.title || ''}
          episode={typeof widget.episode === 'number' ? widget.episode : 1}
          duration={typeof widget.duration === 'number' ? widget.duration : 0}
          summary={widget.summary || ''}
        />
      {/if}
    </div>
  </div>
{/each}

<!-- Your Interests Section Header -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="1">
  <div class="grid-stack-item-content">
    <h2
      class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
    >
      Your Interests
    </h2>
  </div>
</div>

<!-- Personal Widgets -->
{#each personalWidgets as widget (widget.id)}
  {@const { w, h } = getDefaultGridstackDimensions(widget.size, hasSidebar)}
  <div
    class="grid-stack-item" 
    data-gs-id={widget.id} 
    data-gs-w={w} 
    data-gs-h={h} 
    data-gs-auto-position="true"
  >
    <div class="grid-stack-item-content">
      {#if widget.type === 'article'}
        <ArticleWidget 
          id={widget.id || ''}
          title={widget.title || ''}
          excerpt={widget.excerpt || ''}
          source={widget.source || ''}
          date={widget.date || ''}
          tags={widget.tags || []}
          sourceUrl={widget.sourceUrl || ''}
        />
      {:else if widget.type === 'podcast'}
        <PodcastWidget
          id={widget.id || ''}
          title={widget.title || ''}
          episode={typeof widget.episode === 'number' ? widget.episode : 1}
          duration={typeof widget.duration === 'number' ? widget.duration : 0}
          summary={widget.summary || ''}
        />
      {/if}
    </div>
  </div>
{/each}
</div>

<!-- Place the debug button outside the Gridstack container if it's not part of the grid layout -->
<!-- Alternatively, if it should be movable, wrap it in a grid-stack-item -->
<button class="debug-toggle" onclick={toggleDebugGrid}>Toggle Grid Debug</button>

<style>
  /* Removed old custom drag and drop styles */

  /* Grid Debug Styles - Only active when body has debug-grid class */
  /* Updated selectors to target Gridstack items and the grid container */
  :global(body.debug-grid .grid-stack > .grid-stack-item > .grid-stack-item-content) {
    outline: 1px solid hsl(var(--primary) / 0.5);
    position: relative;
  }

  :global(body.debug-grid .grid-stack > .grid-stack-item > .grid-stack-item-content::before) {
    content: '[x:' attr(data-gs-x) ' y:' attr(data-gs-y) ' w:' attr(data-gs-w) ' h:' attr(data-gs-h) ']'; /* Show Gridstack attributes */
    position: absolute;
    top: 0;
    left: 0;
    font-size: 10px;
    background: hsl(var(--background));
    padding: 2px;
    z-index: 9000;
    color: hsl(var(--primary));
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    pointer-events: none; /* Prevent interference */
  }

  :global(body.debug-grid .grid-stack) {
    outline: 1px solid hsl(var(--secondary) / 0.7);
    position: relative;
  }

  :global(body.debug-grid .grid-stack::before) {
    content: 'GRIDSTACK';
    position: absolute;
    top: -16px;
    left: 0;
    font-size: 10px;
    background: hsl(var(--background));
    padding: 2px;
    z-index: 9000;
    color: hsl(var(--secondary));
    pointer-events: none; /* Prevent interference */
  }

  /* Add debug button to toggle grid debug mode */
  :global(.debug-toggle) {
    position: fixed;
    bottom: 10px;
    left: 10px;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    color: hsl(var(--foreground));
    padding: 5px 10px;
    font-size: 12px;
    border-radius: var(--radius-sm);
    z-index: 9999;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.2s;
  }

  :global(.debug-toggle:hover) {
    opacity: 1;
  }
</style>