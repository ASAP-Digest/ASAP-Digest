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

  // Combined widget array to demonstrate grid layout
  const allWidgets = [...newsWidgets, ...financeWidgets, ...personalWidgets];
  
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
    const widgetElements = document.querySelectorAll('.draggable-widget');
    
    widgetElements.forEach(element => {
      element.setAttribute('draggable', 'true');
      
      // Use type casting for event listeners
      element.addEventListener('dragstart', /** @param {Event} e */ e => handleDragStart(/** @type {DragEvent} */ (e)));
      element.addEventListener('dragend', /** @param {Event} e */ e => handleDragEnd(/** @type {DragEvent} */ (e)));
      element.addEventListener('dragover', /** @param {Event} e */ e => handleDragOver(/** @type {DragEvent} */ (e)));
      element.addEventListener('dragenter', /** @param {Event} e */ e => handleDragEnter(/** @type {DragEvent} */ (e)));
      element.addEventListener('dragleave', /** @param {Event} e */ e => handleDragLeave(/** @type {DragEvent} */ (e)));
      element.addEventListener('drop', /** @param {Event} e */ e => handleDrop(/** @type {DragEvent} */ (e)));
    });
  }
  
  /**
   * Clean up drag and drop event listeners
   */
  function cleanupDraggableWidgets() {
    const widgetElements = document.querySelectorAll('.draggable-widget');
    
    widgetElements.forEach(element => {
      // Use type casting for event listeners removal
      element.removeEventListener('dragstart', /** @param {Event} e */ e => handleDragStart(/** @type {DragEvent} */ (e)));
      element.removeEventListener('dragend', /** @param {Event} e */ e => handleDragEnd(/** @type {DragEvent} */ (e)));
      element.removeEventListener('dragover', /** @param {Event} e */ e => handleDragOver(/** @type {DragEvent} */ (e)));
      element.removeEventListener('dragenter', /** @param {Event} e */ e => handleDragEnter(/** @type {DragEvent} */ (e)));
      element.removeEventListener('dragleave', /** @param {Event} e */ e => handleDragLeave(/** @type {DragEvent} */ (e)));
      element.removeEventListener('drop', /** @param {Event} e */ e => handleDrop(/** @type {DragEvent} */ (e)));
    });
  }
  
  /**
   * Handle drag start event
   * @param {DragEvent} event - The drag event
   */
  function handleDragStart(event) {
    if (!event.target) return;
    
    // Type cast target to HTMLElement
    const target = /** @type {HTMLElement} */ (event.target);
    draggedElement = target;
    draggedWidget = target.getAttribute('data-id');
    
    if (event.dataTransfer) {
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', draggedWidget || '');
      
      // Add dragging class for visual feedback
      setTimeout(() => {
        target.classList.add('dragging');
      }, 0);
    }
  }
  
  /**
   * Handle drag end event
   * @param {DragEvent} event - The drag event
   */
  function handleDragEnd(event) {
    // Type cast target to HTMLElement
    const target = /** @type {HTMLElement} */ (event.target);
    
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
   * @param {DragEvent} event - The drag event
   */
  function handleDragOver(event) {
    // Allow dropping
    event.preventDefault();
    if (event.dataTransfer) {
      event.dataTransfer.dropEffect = 'move';
    }
  }
  
  /**
   * Handle drag enter event
   * @param {DragEvent} event - The drag event
   */
  function handleDragEnter(event) {
    // Add class to highlight drop target
    const target = findDropTarget(/** @type {HTMLElement} */ (event.target));
    if (target && draggedElement && target !== draggedElement) {
      target.classList.add('drag-over');
      dropTarget = target;
    }
  }
  
  /**
   * Handle drag leave event
   * @param {DragEvent} event - The drag event
   */
  function handleDragLeave(event) {
    // Remove highlight from drop target
    const target = findDropTarget(/** @type {HTMLElement} */ (event.target));
    if (target) {
      target.classList.remove('drag-over');
    }
  }
  
  /**
   * Handle drop event
   * @param {DragEvent} event - The drag event
   */
  function handleDrop(event) {
    event.preventDefault();
    
    // Get the dropped element
    const target = findDropTarget(/** @type {HTMLElement} */ (event.target));
    
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
        return 'col-span-4 sm:col-span-3 md:col-span-3 lg:col-span-6 xl:col-span-6 2xl:col-span-6';
      case 'full-mobile':
        return 'col-span-4 sm:col-span-3 md:col-span-3 lg:col-span-3 xl:col-span-3 2xl:col-span-3';
      case 'full-tablet':
        return 'col-span-4 sm:col-span-6 md:col-span-6 lg:col-span-3 xl:col-span-3 2xl:col-span-3';
      case 'normal':
      default:
        return 'col-span-2 sm:col-span-3 md:col-span-3 lg:col-span-3 xl:col-span-3 2xl:col-span-3';
    }
  }
</script>

<!-- Main layout container -->
<div class="w-full max-w-[1440px] mx-auto p-4 md:p-6">
  <!-- Section Headers -->
  <div class="mb-6 space-y-2">
    <h1 class="text-2xl md:text-3xl font-bold text-[hsl(var(--foreground))]">Your ASAP Digest</h1>
    <p class="text-[hsl(var(--muted-foreground))]">Customized content based on your interests</p>
  </div>

  <!-- Grid layout: 
    - Mobile (default): 2 columns of 2 grids (4 total)
    - Tablet (sm/md): 2 columns of 3 grids (6 total) 
    - Desktop (lg+): 4 columns of 3 grids (12 total)
  -->
  <div class="grid w-full gap-4 md:gap-6
    grid-cols-4  /* Mobile: 4 column grid system (2 columns of 2 grid units) */
    sm:grid-cols-6  /* Tablet: 6 column grid system (2 columns of 3 grid units) */
    md:grid-cols-6  
    lg:grid-cols-12  /* Desktop: 12 column grid system (4 columns of 3 grid units) */
    xl:grid-cols-12 
    2xl:grid-cols-12"
  >
    <!-- News Section Header - Full width -->
    <div class="col-span-4 sm:col-span-6 md:col-span-6 lg:col-span-12 xl:col-span-12 2xl:col-span-12 mt-2">
      <h2 class="text-xl font-bold text-[hsl(var(--foreground))]">Latest News</h2>
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
    <div class="col-span-4 sm:col-span-6 md:col-span-6 lg:col-span-12 xl:col-span-12 2xl:col-span-12 mt-6">
      <h2 class="text-xl font-bold text-[hsl(var(--foreground))]">Financial Updates</h2>
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
    <div class="col-span-4 sm:col-span-6 md:col-span-6 lg:col-span-12 xl:col-span-12 2xl:col-span-12 mt-6">
      <h2 class="text-xl font-bold text-[hsl(var(--foreground))]">Your Interests</h2>
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
    transition: all 0.2s ease;
  }
  
  .cursor-move:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  }
  
  /* Drag and drop styles */
  .draggable-widget {
    user-select: none;
  }
  
  .dragging {
    opacity: 0.5;
    transform: scale(0.95);
  }
  
  .drag-over {
    border: 2px dashed hsl(var(--primary));
    border-radius: 0.5rem;
    background-color: hsl(var(--accent) / 0.2);
  }
</style>