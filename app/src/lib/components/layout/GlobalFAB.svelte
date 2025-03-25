<!-- GlobalFAB.svelte - Site-wide floating action button -->
<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { fly } from 'svelte/transition';
  import { Plus, X, Calendar, LineChart, Check } from '$lib/utils/lucide-icons.js';
  import Icon from '$lib/components/ui/Icon.svelte';
  import NewItemsSelector from '$lib/components/selectors/NewItemsSelector.svelte';
  import { getContentTypeColor } from '$lib/utils/color-utils.js';
  
  const dispatch = createEventDispatcher();
  
  // State variables using Svelte 5 runes
  let showSelector = $state(false);
  let fabPosition = $state('corner'); // 'corner' or 'center'
  let showNotification = $state(false);
  let notificationMessage = $state('');
  
  // Load stored FAB position preference
  onMount(() => {
    const storedPosition = localStorage.getItem('fab-position');
    if (storedPosition === 'center' || storedPosition === 'corner') {
      fabPosition = storedPosition;
    }
  });
  
  // Toggle FAB position and save preference
  function toggleFabPosition() {
    fabPosition = fabPosition === 'corner' ? 'center' : 'corner';
    localStorage.setItem('fab-position', fabPosition);
  }
  
  // Toggle content selector visibility
  function toggleSelector() {
    showSelector = !showSelector;
  }
  
  // Handle when items are added through the selector
  function handleAdd(event) {
    // Dispatch both component event and global event
    dispatch('add', event.detail);
    
    // Dispatch a global event that can be listened to from anywhere
    const globalEvent = new CustomEvent('app:content-added', {
      detail: event.detail,
      bubbles: true
    });
    window.dispatchEvent(globalEvent);
    
    // Show notification
    const { items, type } = event.detail;
    const count = items.length;
    const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);
    notificationMessage = `${count} ${typeLabel}${count !== 1 ? 's' : ''} added`;
    showNotification = true;
    
    // Hide notification after 3 seconds
    setTimeout(() => {
      showNotification = false;
    }, 3000);
    
    showSelector = false;
  }
</script>

<!-- Floating Action Button -->
<div class="fixed {fabPosition === 'center' ? 'bottom-6 left-1/2 -translate-x-1/2' : 'bottom-6 right-6'} z-50">
  <!-- Main FAB Button -->
  <button 
    class="bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-full w-14 h-14 flex items-center justify-center shadow-lg hover:bg-[hsl(var(--primary)/0.9)] transition-all"
    onclick={toggleSelector}
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
  
  <!-- Success Notification -->
  {#if showNotification}
    <div 
      transition:fly={{y: -20, duration: 200}}
      class="absolute -top-16 right-0 bg-[hsl(var(--success)/0.9)] text-[hsl(var(--success-foreground))] px-4 py-2 rounded-lg shadow-lg flex items-center gap-2"
    >
      <Icon icon={Check} size={16} />
      <span>{notificationMessage}</span>
    </div>
  {/if}
</div>

<!-- Content Selector Modal -->
{#if showSelector}
  <NewItemsSelector 
    showFab={false}
    startOpen={true}
    on:close={toggleSelector} 
    on:add={handleAdd} 
  />
{/if} 