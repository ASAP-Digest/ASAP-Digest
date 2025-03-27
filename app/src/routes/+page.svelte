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

  // Keep track of dragged widget
  /** @type {string|null} */
  let draggedWidget = null;
  
  /** @type {HTMLElement|null} */
  let draggedElement = null;
  
  /** @type {HTMLElement|null} */
  let dropTarget = null;
  
  // NEW: Get sidebar state from layout (assuming it's passed down or available globally)
  // For simplicity, let's assume we can derive it from screen width for now
  // A better approach might involve a shared store or context API
  let hasSidebar = $state(false);
  $effect(() => {
    // Approximation: Sidebar is visible on desktop screens
    hasSidebar = typeof window !== 'undefined' && window.innerWidth >= 1024;
  });
  
  /**
   * Setup drag and drop functionality
   */
  onMount(() => {
    // Initialize draggable widgets
    initDraggableWidgets();
    
    // Clean up event listeners when component is destroyed
    return () => {
      cleanupDraggableWidgets();
    };
  });
  
  /**
   * Initialize drag and drop functionality
   */
  function initDraggableWidgets() {
    if (typeof window === 'undefined') return;
    
    const widgetElements = document.querySelectorAll('.draggable-widget');
    
    widgetElements.forEach(element => {
      element.setAttribute('draggable', 'true');
      
      // Add event listeners
      element.addEventListener('dragstart', handleDragStart);
      element.addEventListener('dragend', handleDragEnd);
      element.addEventListener('dragover', handleDragOver);
      element.addEventListener('dragenter', handleDragEnter);
      element.addEventListener('dragleave', handleDragLeave);
      element.addEventListener('drop', handleDrop);
    });
  }
  
  /**
   * Clean up drag and drop event listeners
   */
  function cleanupDraggableWidgets() {
    if (typeof window === 'undefined') return;
    
    const widgetElements = document.querySelectorAll('.draggable-widget');
    
    widgetElements.forEach(element => {
      // Remove event listeners
      element.removeEventListener('dragstart', handleDragStart);
      element.removeEventListener('dragend', handleDragEnd);
      element.removeEventListener('dragover', handleDragOver);
      element.removeEventListener('dragenter', handleDragEnter);
      element.removeEventListener('dragleave', handleDragLeave);
      element.removeEventListener('drop', handleDrop);
    });
  }
  
  /**
   * Handle drag start event
   * @param {Event} event - The drag event
   */
  function handleDragStart(event) {
    // Type cast event to DragEvent
    const dragEvent = /** @type {DragEvent} */ (event);
    if (!dragEvent.target) return;
    
    // Type cast target to HTMLElement
    const target = /** @type {HTMLElement} */ (dragEvent.target);
    draggedElement = target;
    draggedWidget = target.getAttribute('data-id');
    
    if (dragEvent.dataTransfer) {
      dragEvent.dataTransfer.effectAllowed = 'move';
      dragEvent.dataTransfer.setData('text/plain', draggedWidget || '');
      
      // Add dragging class for visual feedback
      setTimeout(() => {
        target.classList.add('dragging');
      }, 0);
    }
  }
  
  /**
   * Handle drag end event
   * @param {Event} event - The drag event
   */
  function handleDragEnd(event) {
    // Type cast event to DragEvent
    const dragEvent = /** @type {DragEvent} */ (event);
    if (!dragEvent.target) return;
    
    // Type cast target to HTMLElement
    const target = /** @type {HTMLElement} */ (dragEvent.target);
    
    // Remove dragging class
    target.classList.remove('dragging');
    
    // Reset drag state
    draggedElement = null;
    draggedWidget = null;
    
    // Remove drop target highlights
    const dropZones = document.querySelectorAll('.drag-over');
    dropZones.forEach(element => {
      element.classList.remove('drag-over');
    });
  }
  
  /**
   * Handle drag over event
   * @param {Event} event - The drag event
   */
  function handleDragOver(event) {
    // Type cast event to DragEvent
    const dragEvent = /** @type {DragEvent} */ (event);
    
    // Allow dropping
    dragEvent.preventDefault();
    if (dragEvent.dataTransfer) {
      dragEvent.dataTransfer.dropEffect = 'move';
    }
  }
  
  /**
   * Handle drag enter event
   * @param {Event} event - The drag event
   */
  function handleDragEnter(event) {
    // Type cast event to DragEvent
    const dragEvent = /** @type {DragEvent} */ (event);
    if (!dragEvent.target) return;
    
    // Add class to highlight drop target
    const target = findDropTarget(/** @type {HTMLElement} */ (dragEvent.target));
    if (target && draggedElement && target !== draggedElement) {
      target.classList.add('drag-over');
      dropTarget = target;
    }
  }
  
  /**
   * Handle drag leave event
   * @param {Event} event - The drag event
   */
  function handleDragLeave(event) {
    // Type cast event to DragEvent
    const dragEvent = /** @type {DragEvent} */ (event);
    if (!dragEvent.target) return;
    
    // Remove highlight from drop target
    const target = findDropTarget(/** @type {HTMLElement} */ (dragEvent.target));
    if (target) {
      target.classList.remove('drag-over');
    }
  }
  
  /**
   * Handle drop event
   * @param {Event} event - The drag event
   */
  function handleDrop(event) {
    // Type cast event to DragEvent
    const dragEvent = /** @type {DragEvent} */ (event);
    dragEvent.preventDefault();
    
    if (!dragEvent.target) return;
    
    // Get the dropped element
    const target = findDropTarget(/** @type {HTMLElement} */ (dragEvent.target));
    
    if (target && draggedElement && target !== draggedElement) {
      // Get parent containers for both elements
      const sourceContainer = draggedElement.parentNode;
      const targetContainer = target.parentNode;
      
      if (sourceContainer && targetContainer) {
        // Get references to next sibling of source
        const sourceNextSibling = draggedElement.nextElementSibling;
        
        // Swap the elements or insert at the right position
        if (targetContainer === sourceContainer) {
          // Same container, swap order
          targetContainer.insertBefore(draggedElement, target);
        } else {
          // Different containers, insert at each other's positions
          targetContainer.insertBefore(draggedElement, target);
          
          // If sourceNextSibling is null, it means dragged element was last
          if (sourceNextSibling) {
            sourceContainer.insertBefore(target, sourceNextSibling);
          } else {
            sourceContainer.appendChild(target);
          }
        }
        
        // Save the new layout configuration (in a real app, this would go to a DB or localStorage)
        saveLayout();
      }
    }
    
    // Clear drag over state
    if (target) {
      target.classList.remove('drag-over');
    }
  }
  
  /**
   * Find the closest draggable widget parent
   * @param {HTMLElement} element - The element to check
   * @returns {HTMLElement|null} - The closest draggable widget
   */
  function findDropTarget(element) {
    if (!element) return null;
    
    if (element.classList && element.classList.contains('draggable-widget')) {
      return element;
    }
    
    // Walk up the DOM tree to find the closest draggable widget
    let current = element;
    while (current && (!current.classList || !current.classList.contains('draggable-widget'))) {
      const parent = current.parentElement;
      if (!parent) break;
      current = parent;
    }
    
    return current.classList && current.classList.contains('draggable-widget') ? current : null;
  }
  
  /**
   * Save the current layout configuration
   */
  function saveLayout() {
    if (typeof window === 'undefined') return;
    
    // In a real application, you would save the new layout to localStorage or a database
    console.log('Layout saved');
  }

  /**
   * NEW: Enhanced Get column span classes based on widget size and sidebar presence
   * @param {WidgetSize} size - Widget size
   * @param {{ hasSidebar: boolean }} options - Options object
   * @returns {string} - Tailwind classes for column spans
   */
  function getColSpanClasses(size, { hasSidebar = true } = {}) {
    const baseClasses = 'transition-all duration-300';

    // Define column spans for different breakpoints and sidebar states
    const columnSpans = {
      // Mobile first (base) - Sidebar not visible
      base: {
        large: 'col-span-12',
        'full-mobile': 'col-span-12',
        'full-tablet': 'col-span-12',
        normal: 'col-span-12'
      },
      // Mobile landscape (sm) - Sidebar not visible
      sm: {
        large: 'sm:col-span-12',
        'full-mobile': 'sm:col-span-6',
        'full-tablet': 'sm:col-span-12',
        normal: 'sm:col-span-6'
      },
      // Tablet (md) - Sidebar not visible
      md: {
        large: 'md:col-span-8',
        'full-mobile': 'md:col-span-6',
        'full-tablet': 'md:col-span-12',
        normal: 'md:col-span-6'
      },
      // Desktop with sidebar (lg)
      lgWithSidebar: {
        large: 'lg:col-span-8', // Takes more space
        'full-mobile': 'lg:col-span-4',
        'full-tablet': 'lg:col-span-6',
        normal: 'lg:col-span-4' // Standard 1/3
      },
      // Desktop without sidebar (lg) - Widgets can take more relative space
      lgWithoutSidebar: {
        large: 'lg:col-span-6', // Takes half
        'full-mobile': 'lg:col-span-3', // Takes quarter
        'full-tablet': 'lg:col-span-4', // Takes third
        normal: 'lg:col-span-3' // Takes quarter
      }
    };

    // Determine desktop classes based on sidebar presence
    const lgClasses = hasSidebar
      ? columnSpans.lgWithSidebar[size] || columnSpans.lgWithSidebar.normal
      : columnSpans.lgWithoutSidebar[size] || columnSpans.lgWithoutSidebar.normal;

    // Combine classes for all breakpoints
    return `${baseClasses} ${columnSpans.base[size] || columnSpans.base.normal} ${columnSpans.sm[size] || columnSpans.sm.normal} ${columnSpans.md[size] || columnSpans.md.normal} ${lgClasses}`;
  }
  
  /**
   * Toggle grid debug mode for development
   * This enables the commented-out debug styles to help visualize the grid
   */
  function toggleGridDebug() {
    if (typeof window === 'undefined') return;
    
    document.body.classList.toggle('debug-grid');
  }

  // Enable grid debugging with a key press (Shift+Alt+D)
  function setupDebugToggle() {
    window.addEventListener('keydown', (e) => {
      if (e.shiftKey && e.altKey && e.key === 'D') {
        document.body.classList.toggle('debug-grid');
      }
    });
  }

  // Toggle debug mode with button
  function toggleDebugGrid() {
    document.body.classList.toggle('debug-grid');
  }

  // Set up debug toggle on mount
  onMount(() => {
    setupDebugToggle();
    initDraggableWidgets(); // Ensure drag/drop is initialized
    return () => {
      cleanupDraggableWidgets(); // Cleanup listeners
    };
  });
</script>

<!-- NEW: Wrap everything in the content-grid -->
<div class="content-grid">
  <!-- Section Headers - Span full width of the content grid -->
  <div class="col-span-12 mb-10 space-y-3 p-3">
    <h1
      class="mb-3 text-[var(--font-size-3xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
    >
      Your ASAP Digest
    </h1>
    <p class="text-[var(--font-size-base)] font-[var(--font-body)] text-[hsl(var(--muted-foreground))]">
      Customized content based on your interests
    </p>
  </div>

  <!-- News Section Header - Full width -->
  <div class="col-span-12 mb-4 mt-2">
    <h2
      class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
    >
      Latest News
    </h2>
  </div>

  <!-- News Widgets -->
  {#each newsWidgets as widget (widget.id)}
    <div
      class="draggable-widget cursor-move {getColSpanClasses(widget.size, { hasSidebar })}"
      data-id={widget.id}
      data-size={widget.size}
      draggable="true"
    >
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
  {/each}

  <!-- Finance Section Header - Full width -->
  <div class="col-span-12 mb-4 mt-6">
    <h2
      class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
    >
      Financial Updates
    </h2>
  </div>

  <!-- Finance Widgets -->
  {#each financeWidgets as widget (widget.id)}
    <div
      class="draggable-widget cursor-move {getColSpanClasses(widget.size, { hasSidebar })}"
      data-id={widget.id}
      data-size={widget.size}
      draggable="true"
    >
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
  {/each}

  <!-- Personalized Section Header - Full width -->
  <div class="col-span-12 mb-4 mt-6">
    <h2
      class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]"
    >
      Your Interests
    </h2>
  </div>

  <!-- Personalized Widgets -->
  {#each personalWidgets as widget (widget.id)}
    <div
      class="draggable-widget cursor-move {getColSpanClasses(widget.size, { hasSidebar })}"
      data-id={widget.id}
      data-size={widget.size}
      draggable="true"
    >
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
  {/each}

  <!-- Debug button - Keep outside the grid if desired -->
  <button class="debug-toggle col-span-12" on:click={toggleDebugGrid}>Toggle Grid Debug</button>
</div>

<style>
  /* Styling for draggable elements */
  .cursor-move {
    transition: all var(--duration-normal) var(--ease-out);
  }
  
  .cursor-move:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px hsl(var(--foreground) / 0.1);
  }
  
  /* Drag and drop styles */
  .draggable-widget {
    user-select: none;
    width: 100%; /* Ensure widgets take full width of their grid cell */
    display: block; /* Ensure widgets display properly */
    /* Removed margin-bottom, gap is handled by content-grid */
  }
  
  .dragging {
    opacity: 0.5;
    transform: scale(0.95);
  }
  
  .drag-over {
    border: 2px dashed hsl(var(--primary));
    border-radius: var(--radius-md);
    background-color: hsl(var(--accent) / 0.2);
  }

  /* Grid Debug Styles - Only active when body has debug-grid class */
  /* :global selector needed as body class is outside component scope */
  :global(body.debug-grid .content-grid > *) {
    outline: 1px solid hsl(var(--primary) / 0.5);
    position: relative;
  }
  
  :global(body.debug-grid .content-grid > *::before) {
    content: attr(class); /* Show classes */
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
  
  :global(body.debug-grid .content-grid) {
    outline: 1px solid hsl(var(--secondary) / 0.7);
    position: relative;
  }

  :global(body.debug-grid .content-grid::before) {
    content: 'CONTENT-GRID';
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