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
  
  // Load stored FAB position preference
  onMount(() => {
    const storedPosition = localStorage.getItem('fab-position');
    if (storedPosition === 'center' || storedPosition === 'corner') {
      fabPosition = storedPosition;
    }
    
    // If startOpen is true, set the initial content type
    if (startOpen && initialType) {
      selectedType = initialType;
    }
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
  <div class="fixed {fabPosition === 'center' ? 'bottom-6 left-1/2 -translate-x-1/2' : 'bottom-6 right-6'} z-50">
    <!-- Main FAB Button -->
    <button 
      class="bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-full w-14 h-14 flex items-center justify-center shadow-lg hover:bg-[hsl(var(--primary)/0.9)] transition-all"
      onclick={() => showFlyout = !showFlyout}
    >
      <Icon icon={Plus} size={24} />
    </button>
    
    <!-- Position Toggle (small button) -->
    <button 
      class="absolute -left-6 bottom-1 bg-[hsl(var(--muted))] text-[hsl(var(--muted-foreground))] rounded-full w-5 h-5 flex items-center justify-center shadow-sm hover:bg-[hsl(var(--muted)/0.9)]"
      onclick={toggleFabPosition}
      title="Toggle position"
    >
      <Icon icon={fabPosition === 'corner' ? Calendar : LineChart} size={12} />
    </button>
    
    <!-- Flyout Menu for Content Type Selection -->
    {#if showFlyout}
      <div 
        transition:fly={{y: 20, duration: 200}} 
        class="{fabPosition === 'center' ? 'left-1/2 -translate-x-1/2 bottom-20' : 'right-0 bottom-20'} absolute bg-[hsl(var(--background))] rounded-lg shadow-xl p-4 grid grid-cols-4 gap-3 min-w-[320px] border border-[hsl(var(--border))]"
      >
        {#each contentTypes as type}
          <button 
            class="flex flex-col items-center p-2 rounded-lg hover:bg-[hsl(var(--muted))] transition-colors"
            onclick={() => selectContentType(type.id)}
          >
            <div class="w-10 h-10 bg-[hsl(var(--muted))] rounded-full flex items-center justify-center mb-1">
              <Icon icon={type.icon} size={20} color="currentColor" class="text-[hsl(var(--primary))]" />
            </div>
            <span class="text-xs">{type.label}</span>
          </button>
        {/each}
        
        <button onclick={closeFlyout} class="absolute top-2 right-2 text-[hsl(var(--muted-foreground))]">
          <Icon icon={X} size={16} />
        </button>
      </div>
    {/if}
  </div>
{/if}

{#if selectedType || startOpen}
  <!-- Visual Grid Selection Dialog -->
  <Dialog open={true} onClose={resetAndClose}>
    <DialogContent class="max-w-4xl p-6">
      <DialogHeader>
        <DialogTitle class="text-xl font-medium flex items-center gap-2">
          {#if selectedType}
            {#if contentTypes.find(t => t.id === selectedType)}
              {@const type = contentTypes.find(t => t.id === selectedType)}
              {#if type.icon}
                <div class="w-6 h-6 bg-[hsl(var(--muted))] rounded-full flex items-center justify-center">
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-4">
          {#each contentTypes as type}
            <button 
              class="flex flex-col items-center p-4 rounded-lg border border-[hsl(var(--border))] hover:bg-[hsl(var(--muted))] transition-colors"
              onclick={() => selectContentType(type.id)}
            >
              <div class="w-12 h-12 bg-[hsl(var(--muted))] rounded-full flex items-center justify-center mb-2">
                <Icon icon={type.icon} size={24} color="currentColor" class="text-[hsl(var(--primary))]" />
              </div>
              <span class="text-sm font-medium">{type.label}</span>
            </button>
          {/each}
        </div>
      {:else}
        <!-- Search Bar -->
        <div class="mb-4 flex">
          <div class="relative flex-grow">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[hsl(var(--muted-foreground))]">
              <Icon icon={Search} size={16} />
            </div>
            <Input 
              value={searchQuery}
              oninput={filterItems} 
              placeholder="Search content..." 
              class="w-full pl-9"
            />
          </div>
        </div>
        
        <!-- Content Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[60vh] overflow-y-auto p-1">
          {#each contentItems as item (item.id)}
            <div 
              class="bg-[hsl(var(--card))] rounded-lg overflow-hidden shadow-sm border border-[hsl(var(--border))] transition-all cursor-pointer {isSelected(item) ? 'ring-2 ring-[hsl(var(--primary))]' : 'hover:shadow-md'}"
              onclick={() => toggleItemSelection(item)}
            >
              <div class="aspect-video relative bg-[hsl(var(--muted))]">
                <img src={item.image} alt={item.title} class="w-full h-full object-cover" />
                {#if isSelected(item)}
                  <div class="absolute top-2 right-2 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-full w-6 h-6 flex items-center justify-center">
                    <Icon icon={Check} size={16} />
                  </div>
                {/if}
              </div>
              <div class="p-3">
                <h3 class="font-medium text-sm line-clamp-1">{item.title}</h3>
                <p class="text-xs text-[hsl(var(--muted-foreground))] mt-1 line-clamp-2">
                  {item.excerpt || (item.duration ? `Duration: ${item.duration}` : '')}
                </p>
                <div class="mt-2 flex items-center text-xs text-[hsl(var(--muted-foreground))]">
                  <span>{item.source}</span>
                </div>
              </div>
            </div>
          {/each}
          
          {#if contentItems.length === 0}
            <div class="col-span-full text-center py-12 text-[hsl(var(--muted-foreground))]">
              No content found. Try adjusting your search.
            </div>
          {/if}
        </div>
        
        <!-- Action Buttons -->
        <div class="mt-4 flex justify-between items-center">
          <Button variant="outline" onclick={() => selectedType = ''}>
            Back
          </Button>
          <div>
            <span class="mr-2 text-sm">{selectedItems.length} selected</span>
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