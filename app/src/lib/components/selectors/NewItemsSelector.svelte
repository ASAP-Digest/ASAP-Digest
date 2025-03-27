<!-- NewItemsSelector.svelte - Enhanced content selection component -->
<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { fly, slide, fade } from 'svelte/transition';
  import Dialog from '$lib/components/ui/dialog/dialog.svelte';
  import DialogContent from '$lib/components/ui/dialog/dialog-content.svelte';
  import DialogHeader from '$lib/components/ui/dialog/dialog-header.svelte';
  import DialogTitle from '$lib/components/ui/dialog/dialog-title.svelte';
  import Button from '$lib/components/ui/button/button.svelte';
  import Input from '$lib/components/ui/input/input.svelte';
  import { Check, Plus, X, Search, FileText, Headphones, Key, DollarSign, Twitter, MessageSquare, Calendar, LineChart } from '$lib/utils/lucide-icons.js';
  import Icon from '$lib/components/ui/Icon.svelte';
  import { getContentTypeColor } from '$lib/utils/color-utils.js';
  
  const dispatch = createEventDispatcher();
  
  // Props
  let { 
    startOpen = false,           // Whether to open directly in content selection mode
    initialType = '',            // Initial content type to select
    showFab = true,              // Whether to show the FAB (false when used with GlobalFAB)
  } = $props();
  
  // State variables using Svelte 5 runes
  let selectedType = $state(initialType);
  let searchQuery = $state('');
  let contentItems = $state([]);
  let selectedItems = $state([]);
  let showFlyout = $state(false);
  let fabPosition = $state('corner'); // 'corner' or 'center'
  let isSidebarCollapsed = $state(false);
  
  // Define content type icons and colors
  const contentTypes = [
    { id: 'article', label: 'Article', icon: FileText, color: 'blue' },
    { id: 'podcast', label: 'Podcast', icon: Headphones, color: 'purple' },
    { id: 'keyterm', label: 'Key Term', icon: Key, color: 'amber' },
    { id: 'financial', label: 'Financial', icon: DollarSign, color: 'green' },
    { id: 'xpost', label: 'X Post', icon: Twitter, color: 'sky' },
    { id: 'reddit', label: 'Reddit', icon: MessageSquare, color: 'orange' },
    { id: 'event', label: 'Event', icon: Calendar, color: 'rose' },
    { id: 'polymarket', label: 'Polymarket', icon: LineChart, color: 'indigo' }
  ];
  
  // Sample content - would come from an API in production
  const contentSources = {
    article: [
      { id: 'a1', title: 'Latest Tech Innovations', image: 'https://placehold.co/600x400/3b82f6/FFFFFF?text=Tech', source: 'Tech Daily', excerpt: 'The newest innovations changing our daily lives' },
      { id: 'a2', title: 'Financial Markets Update', image: 'https://placehold.co/600x400/10b981/FFFFFF?text=Finance', source: 'Finance Weekly', excerpt: 'How markets responded to recent economic data' },
      { id: 'a3', title: 'Climate Change Solutions', image: 'https://placehold.co/600x400/84cc16/FFFFFF?text=Climate', source: 'Environment Today', excerpt: 'New approaches to addressing climate challenges' },
    ],
    podcast: [
      { id: 'p1', title: 'The Future of AI', image: 'https://placehold.co/600x400/8b5cf6/FFFFFF?text=AI', source: 'Tech Talks', duration: '32 min' },
      { id: 'p2', title: 'Crypto Market Analysis', image: 'https://placehold.co/600x400/6366f1/FFFFFF?text=Crypto', source: 'Blockchain Weekly', duration: '45 min' },
      { id: 'p3', title: 'Productivity Hacks', image: 'https://placehold.co/600x400/ec4899/FFFFFF?text=Productivity', source: 'Life Optimized', duration: '28 min' },
    ],
    keyterm: [
      { id: 'k1', title: 'Quantitative Easing', image: 'https://placehold.co/600x400/f59e0b/FFFFFF?text=QE', source: 'Economics Glossary', excerpt: 'Monetary policy used by central banks' },
      { id: 'k2', title: 'Web3', image: 'https://placehold.co/600x400/8b5cf6/FFFFFF?text=Web3', source: 'Tech Dictionary', excerpt: 'The next generation of internet technologies' },
    ],
    // Add sample data for other content types as needed
  };
  
  // Load stored FAB position preference and sidebar state
  onMount(() => {
    const storedPosition = localStorage.getItem('fab-position');
    if (storedPosition === 'center' || storedPosition === 'corner') {
      fabPosition = storedPosition;
    }
    
    // If startOpen is true, set the initial content type
    if (startOpen && initialType) {
      selectedType = initialType;
    }

    // Check sidebar state
    if (typeof window !== 'undefined' && window.localStorage) {
      const sidebarState = localStorage.getItem('sidebar-collapsed');
      isSidebarCollapsed = sidebarState === 'true';
    }

    // Add listener for sidebar state changes
    const handleSidebarChange = () => {
      isSidebarCollapsed = document.body.classList.contains('sidebar-collapsed');
    };

    const observer = new MutationObserver(handleSidebarChange);
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    return () => {
      observer.disconnect();
    };
  });
  
  // Update content items when type is selected
  $effect(() => {
    if (selectedType) {
      contentItems = contentSources[selectedType] || [];
    }
  });
  
  // Toggle selection of an item
  function toggleItemSelection(item) {
    const index = selectedItems.findIndex(i => i.id === item.id);
    if (index >= 0) {
      selectedItems = selectedItems.filter(i => i.id !== item.id);
    } else {
      selectedItems = [...selectedItems, item];
    }
  }
  
  // Check if an item is selected
  function isSelected(item) {
    return selectedItems.some(i => i.id === item.id);
  }
  
  // Filter items based on search query
  function filterItems() {
    if (!searchQuery.trim()) {
      contentItems = contentSources[selectedType] || [];
      return;
    }
    
    contentItems = (contentSources[selectedType] || []).filter(item => 
      item.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.source.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (item.excerpt && item.excerpt.toLowerCase().includes(searchQuery.toLowerCase()))
    );
  }
  
  // Add selected items to digest and close selector
  function addSelectedItems() {
    dispatch('add', { items: selectedItems, type: selectedType });
    resetAndClose();
  }
  
  // Close the content type selection flyout
  function closeFlyout() {
    showFlyout = false;
  }
  
  // Select a content type and close the flyout
  function selectContentType(type) {
    selectedType = type;
    showFlyout = false;
  }
  
  // Reset selection and close component
  function resetAndClose() {
    selectedType = '';
    selectedItems = [];
    showFlyout = false;
    dispatch('close');
  }
  
  // Toggle FAB position and save preference
  function toggleFabPosition() {
    fabPosition = fabPosition === 'corner' ? 'center' : 'corner';
    localStorage.setItem('fab-position', fabPosition);
  }
  
  // Get the appropriate color for a content type
  function getTypeColor(typeId) {
    const type = contentTypes.find(t => t.id === typeId);
    return type ? type.color : 'gray';
  }
</script>

<!-- Content Type Selection Floating Action Button -->
{#if !selectedType && showFab}
  <div class="selector-fab {fabPosition === 'center' ? 'center' : 'corner'} {isSidebarCollapsed ? 'sidebar-collapsed' : ''}" style={fabPosition === 'corner' && !isSidebarCollapsed ? 'right: calc(1.5rem + 240px);' : ''}>
    <!-- Main FAB Button -->
    <button 
      class="selector-fab-button"
      onclick={() => showFlyout = !showFlyout}
    >
      <Icon icon={Plus} size={24} />
    </button>
    
    <!-- Position Toggle (small button) -->
    <button 
      class="selector-fab-position-toggle"
      onclick={toggleFabPosition}
      title="Toggle position"
    >
      <Icon icon={fabPosition === 'corner' ? Calendar : LineChart} size={12} />
    </button>
    
    <!-- Flyout Menu for Content Type Selection -->
    {#if showFlyout}
      <div transition:fly={{y: 20, duration: 200}} class="selector-flyout">
        {#each contentTypes as type}
          <button 
            class="selector-flyout-button"
            onclick={() => selectContentType(type.id)}
          >
            <div class="selector-flyout-icon">
              <Icon icon={type.icon} size={20} color="currentColor" class="text-[hsl(var(--primary))]" />
            </div>
            <span class="selector-flyout-label">{type.label}</span>
          </button>
        {/each}
        
        <button onclick={closeFlyout} class="selector-flyout-close">
          <Icon icon={X} size={16} />
        </button>
      </div>
    {/if}
  </div>
{/if}

{#if selectedType || startOpen}
  <!-- Visual Grid Selection Dialog -->
  <Dialog open={true} onClose={resetAndClose}>
    <DialogContent class="selector-dialog-content">
      <DialogHeader>
        <DialogTitle class="text-[1.25rem] font-medium flex items-center gap-[0.5rem]">
          {#if selectedType}
            {#if contentTypes.find(t => t.id === selectedType)}
              {@const type = contentTypes.find(t => t.id === selectedType)}
              {#if type.icon}
                <div class="w-[1.5rem] h-[1.5rem] bg-[hsl(var(--muted))] rounded-full flex items-center justify-center">
                  <Icon icon={type.icon} size={14} class="text-[hsl(var(--primary))]" />
                </div>
              {/if}
              <span>Select {type.label} Content</span>
            {/if}
          {:else}
            <span>Select Content</span>
          {/if}
        </DialogTitle>
      </DialogHeader>
      
      <!-- Type Selection (when opened directly without a type) -->
      {#if !selectedType}
        <div class="selector-grid">
          {#each contentTypes as type}
            <button 
              class="selector-grid-button"
              onclick={() => selectContentType(type.id)}
            >
              <div class="selector-grid-icon">
                <Icon icon={type.icon} size={24} color="currentColor" class="text-[hsl(var(--primary))]" />
              </div>
              <span class="selector-grid-label">{type.label}</span>
            </button>
          {/each}
        </div>
      {:else}
        <!-- Search Bar -->
        <div class="selector-search">
          <div class="selector-search-wrapper">
            <div class="selector-search-icon">
              <Icon icon={Search} size={16} />
            </div>
            <Input 
              value={searchQuery}
              oninput={filterItems} 
              placeholder="Search content..." 
              class="selector-search-input"
            />
          </div>
        </div>
        
        <!-- Content Grid -->
        <div class="selector-content-grid">
          {#each contentItems as item (item.id)}
            <div 
              class="selector-content-item {isSelected(item) ? 'selected' : ''}"
              onclick={() => toggleItemSelection(item)}
            >
              <div class="selector-content-image-wrapper">
                <img src={item.image} alt={item.title} class="selector-content-image" />
                {#if isSelected(item)}
                  <div class="selector-content-selected-badge">
                    <Icon icon={Check} size={16} />
                  </div>
                {/if}
              </div>
              <div class="selector-content-details">
                <h3 class="selector-content-title">{item.title}</h3>
                <p class="selector-content-excerpt">
                  {item.excerpt || (item.duration ? `Duration: ${item.duration}` : '')}
                </p>
                <div class="selector-content-source">
                  <span>{item.source}</span>
                </div>
              </div>
            </div>
          {/each}
          
          {#if contentItems.length === 0}
            <div class="selector-content-empty">
              No content found. Try adjusting your search.
            </div>
          {/if}
        </div>
        
        <!-- Action Buttons -->
        <div class="selector-actions">
          <Button variant="outline" onclick={() => selectedType = ''}>
            Back
          </Button>
          <div class="selector-selected-count">
            <span class="selector-count-text">{selectedItems.length} selected</span>
            <Button 
              variant="default" 
              onclick={addSelectedItems} 
              disabled={selectedItems.length === 0}
            >
              Add Selected
            </Button>
          </div>
        </div>
      {/if}
    </DialogContent>
  </Dialog>
{/if}

<style>
  /* Base FAB styling */
  .selector-fab {
    position: fixed;
    bottom: 1.5rem;
    z-index: var(--z-fab);
    transition: all 0.3s var(--ease-out);
  }

  /* Corner positioning */
  .selector-fab.corner {
    right: 1.5rem;
  }

  /* Center positioning */
  .selector-fab.center {
    left: 50%;
    transform: translateX(-50%);
  }

  /* Adjust for sidebar on desktop */
  @media (min-width: 1024px) {
    /* When sidebar is expanded, shift FAB position to account for sidebar width */
    .selector-fab.corner:not(.sidebar-collapsed) {
      right: calc(1.5rem + 240px);
    }
  
    /* When sidebar is collapsed, only account for collapsed sidebar width */
    .selector-fab.corner.sidebar-collapsed {
      right: calc(1.5rem + 64px);
    }
  }

  /* Main FAB button */
  .selector-fab-button {
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 9999px;
    width: 3.5rem;
    height: 3.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transition: all 0.2s var(--ease-out);
  }

  .selector-fab-button:hover {
    background-color: hsl(var(--primary)/0.9);
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }

  /* Position toggle button */
  .selector-fab-position-toggle {
    position: absolute;
    left: -1.5rem;
    bottom: 0.25rem;
    background-color: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    border-radius: 9999px;
    width: 1.25rem;
    height: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.2s var(--ease-out);
  }

  .selector-fab-position-toggle:hover {
    background-color: hsl(var(--muted)/0.9);
  }

  /* Flyout menu */
  .selector-flyout {
    position: absolute;
    background-color: hsl(var(--background));
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    border: 1px solid hsl(var(--border));
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    min-width: 20rem;
    z-index: var(--z-fab-flyout);
  }

  /* Position flyout based on FAB position */
  .selector-fab.corner .selector-flyout {
    right: 0;
    bottom: 5rem;
  }

  .selector-fab.center .selector-flyout {
    bottom: 5rem;
    left: 50%;
    transform: translateX(-50%);
  }

  /* Flyout buttons */
  .selector-flyout-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    border-radius: var(--radius-lg);
    transition: background-color 0.2s var(--ease-out);
  }

  .selector-flyout-button:hover {
    background-color: hsl(var(--muted));
  }

  .selector-flyout-icon {
    width: 2.5rem;
    height: 2.5rem;
    background-color: hsl(var(--muted));
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.25rem;
  }

  .selector-flyout-label {
    font-size: 0.75rem;
  }

  .selector-flyout-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: hsl(var(--muted-foreground));
  }

  /* Dialog content styling */
  .selector-dialog-content {
    max-width: 64rem;
    padding: 1.5rem;
    z-index: var(--z-modal);
  }

  /* Type selection grid */
  .selector-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    padding: 1rem 0;
  }

  @media (min-width: 640px) {
    .selector-grid {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  .selector-grid-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border-radius: var(--radius-lg);
    border: 1px solid hsl(var(--border));
    transition: background-color 0.2s var(--ease-out);
  }

  .selector-grid-button:hover {
    background-color: hsl(var(--muted));
  }

  .selector-grid-icon {
    width: 3rem;
    height: 3rem;
    background-color: hsl(var(--muted));
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
  }

  .selector-grid-label {
    font-size: 0.875rem;
    font-weight: 500;
  }

  /* Search bar */
  .selector-search {
    margin-bottom: 1rem;
    display: flex;
  }

  .selector-search-wrapper {
    position: relative;
    flex-grow: 1;
  }

  .selector-search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: hsl(var(--muted-foreground));
  }

  .selector-search-input {
    width: 100%;
    padding-left: 2.25rem;
  }

  /* Content grid */
  .selector-content-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1rem;
    max-height: 60vh;
    overflow-y: auto;
    padding: 0.25rem;
  }

  @media (min-width: 640px) {
    .selector-content-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (min-width: 1024px) {
    .selector-content-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  .selector-content-item {
    background-color: hsl(var(--card));
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid hsl(var(--border));
    transition: all 0.2s var(--ease-out);
    cursor: pointer;
  }

  .selector-content-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }

  .selector-content-item.selected {
    box-shadow: 0 0 0 2px hsl(var(--primary));
  }

  .selector-content-image-wrapper {
    position: relative;
    background-color: hsl(var(--muted));
    aspect-ratio: 16 / 9;
  }

  .selector-content-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .selector-content-selected-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 9999px;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .selector-content-details {
    padding: 0.75rem;
  }

  .selector-content-title {
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .selector-content-excerpt {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-top: 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .selector-content-source {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
  }

  .selector-content-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem 0;
    color: hsl(var(--muted-foreground));
  }

  /* Action buttons */
  .selector-actions {
    margin-top: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .selector-selected-count {
    display: flex;
    align-items: center;
  }

  .selector-count-text {
    margin-right: 0.5rem;
    font-size: 0.875rem;
  }

  /* Mobile responsiveness */
  @media (max-width: 640px) {
    .selector-flyout {
      min-width: calc(100vw - 3rem);
      left: 50%;
      transform: translateX(-50%);
      right: auto;
    }

    .selector-dialog-content {
      width: 100vw;
      max-width: 100vw;
      height: 100vh;
      max-height: 100vh;
      border-radius: 0;
      padding: 1rem;
    }
  }

  /* Mobile fixes */
  @media (max-width: 1023px) {
    .selector-fab.corner, 
    .selector-fab.corner.sidebar-collapsed,
    .selector-fab.corner:not(.sidebar-collapsed) {
      right: 1.5rem;
    }
  }
</style> 