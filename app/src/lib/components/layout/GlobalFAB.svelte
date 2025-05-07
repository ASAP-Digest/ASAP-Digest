<!-- GlobalFAB.svelte - Site-wide floating action button -->
<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { fly } from 'svelte/transition';
  import { Plus, X, Calendar, LineChart, Check } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import NewItemsSelector from '$lib/components/selectors/NewItemsSelector.svelte';
  import { getContentTypeColor } from '$lib/utils/color-utils.js';
  
  const dispatch = createEventDispatcher();
  
  // State variables using Svelte 5 runes
  let showSelector = $state(false);
  let fabPosition = $state('corner'); // 'corner' or 'center'
  let showNotification = $state(false);
  let notificationMessage = $state('');
  let isSidebarCollapsed = $state(false);
  
  // Load stored FAB position preference and sidebar state
  onMount(() => {
    const storedPosition = localStorage.getItem('fab-position');
    if (storedPosition === 'center' || storedPosition === 'corner') {
      fabPosition = storedPosition;
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
  
  // Toggle FAB position and save preference
  function toggleFabPosition() {
    fabPosition = fabPosition === 'corner' ? 'center' : 'corner';
    localStorage.setItem('fab-position', fabPosition);
  }
  
  // Toggle content selector visibility
  function toggleSelector() {
    showSelector = !showSelector;
    console.log('Selector visibility:', showSelector);
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

  // Compute FAB position classes based on position and sidebar state
  $effect(() => {
    console.log('FAB position update:', fabPosition, 'Sidebar collapsed:', isSidebarCollapsed);
  });
</script>

<div class="global-fab {fabPosition === 'center' ? 'center' : 'corner'} {isSidebarCollapsed ? 'sidebar-collapsed' : ''}" style={fabPosition === 'corner' && !isSidebarCollapsed ? 'right: calc(1.5rem + 240px);' : ''}>
  <!-- Main FAB Button -->
  <button 
    class="fab-button"
    onclick={toggleSelector}
  >
    <Icon icon={Plus} size={24} color="currentColor" />
  </button>
  
  <!-- Position Toggle (small button) -->
  <button 
    class="fab-position-toggle"
    onclick={toggleFabPosition}
    title="Toggle position"
  >
    <Icon icon={fabPosition === 'corner' ? Calendar : LineChart} size={12} color="currentColor" />
  </button>
  
  <!-- Success Notification -->
  {#if showNotification}
    <div 
      transition:fly={{y: -20, duration: 200}}
      class="fab-notification"
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
    onclose={toggleSelector}
    onadd={handleAdd}
  />
{/if}

<style>
  /* Base FAB positioning */
  .global-fab {
    position: fixed;
    bottom: 1.5rem;
    z-index: var(--z-fab);
    transition: all 0.3s var(--ease-out);
  }

  /* Corner positioning */
  .global-fab.corner {
    right: 1.5rem;
  }

  /* Center positioning */
  .global-fab.center {
    left: 50%;
    transform: translateX(-50%);
  }

  /* Adjust for sidebar on desktop */
  @media (min-width: 1024px) {
    /* When sidebar is expanded, shift FAB position to account for sidebar width */
    .global-fab.corner:not(.sidebar-collapsed) {
      right: calc(1.5rem + 240px);
    }
  
    /* When sidebar is collapsed, only account for collapsed sidebar width */
    .global-fab.corner.sidebar-collapsed {
      right: calc(1.5rem + 64px);
    }
  }

  /* Main FAB button */
  .fab-button {
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

  .fab-button:hover {
    background-color: hsl(var(--primary)/0.9);
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }

  /* Position toggle button */
  .fab-position-toggle {
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

  .fab-position-toggle:hover {
    background-color: hsl(var(--muted)/0.9);
  }

  /* Notification style */
  .fab-notification {
    position: absolute;
    top: -4rem;
    right: 0;
    background-color: hsl(var(--success)/0.9);
    color: hsl(var(--success-foreground));
    padding: 0.5rem 1rem;
    border-radius: var(--radius-lg);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: var(--z-notification);
  }

  /* Center positioned notification should center above the FAB */
  .global-fab.center .fab-notification {
    right: auto;
    left: 50%;
    transform: translateX(-50%);
  }

  /* Mobile fixes */
  @media (max-width: 1023px) {
    .global-fab.corner, 
    .global-fab.corner.sidebar-collapsed,
    .global-fab.corner:not(.sidebar-collapsed) {
      right: 1.5rem;
    }
  }
</style> 