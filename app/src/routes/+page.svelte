<script>
  import ArticleWidget from '$lib/components/widgets/ArticleWidget.svelte';
  import PodcastWidget from '$lib/components/widgets/PodcastWidget.svelte';
  import { onMount } from 'svelte';
  
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
   * Get column span classes based on widget size and screen size
   * @param {WidgetSize} size - Widget size
   * @returns {string} - Tailwind classes for column spans
   */
  function getColSpanClasses(size) {
    switch(size) {
      case 'large':
        return 'col-span-12 md:col-span-8 lg:col-span-8'; // Full width on mobile, 2/3 on larger screens
      case 'full-mobile':
        return 'col-span-12 md:col-span-6 lg:col-span-4'; // Full width on mobile, 1/2 on medium, 1/3 on large
      case 'full-tablet':
        return 'col-span-12 md:col-span-12 lg:col-span-6'; // Full width until large screens, then 1/2
      case 'normal':
      default:
        return 'col-span-12 md:col-span-6 lg:col-span-4'; // Full width on mobile, 1/2 on medium, 1/3 on large
    }
  }
  
  /**
   * Toggle grid debug mode for development
   * This enables the commented-out debug styles to help visualize the grid
   */
  function toggleGridDebug() {
    if (typeof window === 'undefined') return;
    
    document.body.classList.toggle('debug-grid');
  }
</script>

<!-- Main layout container -->
<div class="page-content max-w-[1440px] mx-auto px-6 sm:px-8 md:px-10 lg:px-12">
  <!-- Section Headers -->
  <div class="mb-10 space-y-3 p-3">
    <h1 class="text-[var(--font-size-3xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] mb-3 text-[hsl(var(--foreground))]">Your ASAP Digest</h1>
    <p class="text-[hsl(var(--muted-foreground))] font-[var(--font-body)] text-[var(--font-size-base)]">Customized content based on your interests</p>
  </div>

  <!-- Grid layout: Consistent 12-column grid across all breakpoints -->
  <div 
    class="grid w-full grid-cols-12 gap-4 md:gap-6 lg:gap-8"
  >
    <!-- News Section Header - Full width -->
    <div class="col-span-12 mt-2 mb-4">
      <h2 class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]">Latest News</h2>
    </div>
    
    <!-- News Widgets -->
    {#each newsWidgets as widget}
      <div 
        class="cursor-move draggable-widget {getColSpanClasses(widget.size)}"
        data-id={widget.id} 
        data-size={widget.size}
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
    <div class="col-span-12 mt-6 mb-4">
      <h2 class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]">Financial Updates</h2>
    </div>
    
    <!-- Finance Widgets -->
    {#each financeWidgets as widget}
      <div 
        class="cursor-move draggable-widget {getColSpanClasses(widget.size)}"
        data-id={widget.id} 
        data-size={widget.size}
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
    <div class="col-span-12 mt-6 mb-4">
      <h2 class="text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] text-[hsl(var(--foreground))]">Your Interests</h2>
    </div>
    
    <!-- Personalized Widgets -->
    {#each personalWidgets as widget}
      <div 
        class="cursor-move draggable-widget {getColSpanClasses(widget.size)}"
        data-id={widget.id} 
        data-size={widget.size}
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
  </div>
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
    margin-bottom: calc(var(--spacing-unit) * 3); /* Add spacing between widgets */
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
  :global(body.debug-grid .grid > *) {
    outline: 1px solid hsl(var(--primary) / 0.5);
    position: relative;
  }
  
  :global(body.debug-grid .grid > *::before) {
    content: attr(class);
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
  }
  
  :global(body.debug-grid .grid) {
    outline: 1px solid hsl(var(--secondary) / 0.7);
    position: relative;
  }

  :global(body.debug-grid .grid::before) {
    content: "GRID";
    position: absolute;
    top: -16px;
    left: 0;
    font-size: 10px;
    background: hsl(var(--background));
    padding: 2px;
    z-index: 9000;
    color: hsl(var(--secondary));
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

<!-- Debug button for development - can be commented out for production -->
<button class="debug-toggle" onclick={toggleGridDebug}>Toggle Grid Debug</button>