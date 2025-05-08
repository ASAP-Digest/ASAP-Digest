<!-- GlobalFAB.svelte - Site-wide floating action button -->
<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { fly } from 'svelte/transition';
  import { 
    Plus, 
    X, 
    Calendar, 
    LineChart, 
    Check, 
    ArrowsLeftRight,
    FileText,
    Headphones,
    Key,
    DollarSign,
    Twitter,
    MessageSquare
  } from '$lib/utils/lucide-compat.js';
  
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import NewItemsSelector from '$lib/components/selectors/NewItemsSelector.svelte';
  import { getContentTypeColor } from '$lib/utils/color-utils.js';
  import { browser } from '$app/environment';
  
  const dispatch = createEventDispatcher();
  
  // State variables using Svelte 5 runes
  let showSelector = $state(false);
  let fabPosition = $state('corner'); // 'corner' or 'center'
  let showNotification = $state(false);
  let notificationMessage = $state('');
  let isSidebarCollapsed = $state(false);
  let selectedType = $state('');
  
  // New radial menu state
  let showFlyout = $state(false);
  let flyoutPosition = $state('top'); // 'top', 'left', 'right'
  
  // Content type definitions for radial menu
  const contentTypeDetails = [
    { id: 'article', label: 'Articles' },
    { id: 'podcast', label: 'Podcasts' },
    { id: 'keyterm', label: 'Key Terms' },
    { id: 'financial', label: 'Financial' },
    { id: 'xpost', label: 'X Posts' },
    { id: 'reddit', label: 'Reddit' }
  ];
  
  // Define content type icons
  const contentTypeIcons = {
    article: FileText,
    podcast: Headphones,
    keyterm: Key,
    financial: DollarSign,
    xpost: Twitter,
    reddit: MessageSquare
  };
  
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
  
  /**
   * Calculate the appropriate position for the flyout based on screen edges
   * @returns {string} The position for the flyout
   */
  function calculateFlyoutPosition() {
    if (!browser) return 'top';
    
    // Get viewport dimensions
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    // Get FAB position
    const fabElement = document.querySelector('.global-fab');
    if (!fabElement) return 'top';
    
    const fabRect = fabElement.getBoundingClientRect();
    
    // Calculate position - default to 'top' unless close to top edge
    if (fabRect.top < 300) {
      return 'bottom';
    } else if (fabRect.left < 200) {
      return 'right';
    } else if (viewportWidth - fabRect.right < 200) {
      return 'left';
    } else {
      return 'top';
    }
  }
  
  /**
   * Toggle the flyout visibility with edge detection
   */
  function toggleFlyout() {
    console.log('Toggling flyout, current showFlyout:', showFlyout);
    
    // Always close selector when toggling flyout
    showSelector = false;
    
    if (!showFlyout) {
      // Calculate position before showing
      flyoutPosition = calculateFlyoutPosition();
    }
    
    showFlyout = !showFlyout;
    console.log('New showFlyout state:', showFlyout);
  }
  
  /**
   * Select content type from the radial menu and open selector
   * @param {string} type
   */
  function selectContentType(type) {
    console.log('Selecting content type:', type);
    showFlyout = false;
    selectedType = type;
    // Add a small delay before opening selector
    setTimeout(() => {
      console.log('Opening selector after content type selection');
      showSelector = true;
    }, 50);
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
    class="fab-button {showFlyout ? 'active' : ''}"
    onclick={toggleFlyout}
    aria-label="Add new content"
  >
    <Icon icon={showFlyout ? X : Plus} size={24} color="currentColor" />
  </button>
  
  <!-- Position Toggle (small button) -->
  <button 
    class="fab-position-toggle"
    onclick={toggleFabPosition}
    title="Toggle position"
  >
    <Icon icon={ArrowsLeftRight} size={14} color="currentColor" />
  </button>
  
  <!-- Radial Flyout Menu with Arc Pattern -->
  {#if showFlyout}
    <div class="radial-menu {fabPosition === 'center' ? 'center' : 'corner'}">
      {#each contentTypeDetails as type, i}
        <button 
          transition:fly|local={{
            delay: i * 60, // Slightly quicker delay
            duration: 300,
            y: 20,
            opacity: 0,
            easing: 'cubic-bezier(0.16, 1, 0.3, 1)'
          }}
          class="radial-menu-item" 
          style="--index: {i}; --total: {contentTypeDetails.length};"
          onclick={() => selectContentType(type.id)}
          aria-label="Add {type.label}"
          title="{type.label}" 
        >
          <div class="radial-menu-icon">
            <Icon icon={contentTypeIcons[type.id] || FileText} size={20} />
          </div>
        </button>
      {/each}
    </div>
  {/if}
  
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
    initialType={selectedType}
    onclose={() => { showSelector = false; selectedType = ''; }}
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
    width: 4rem;
    height: 4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transition: all 0.2s var(--ease-out);
    z-index: 10;
  }

  .fab-button:hover {
    background-color: hsl(var(--primary)/0.9);
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }
  
  .fab-button.active {
    background-color: hsl(var(--background));
    border: 1px solid hsl(var(--primary));
    color: hsl(var(--foreground));
    box-shadow: var(--shadow-sm);
  }
  
  .fab-button.active svg {
    transform: rotate(135deg);
  }

  /* Position toggle button */
  .fab-position-toggle {
    position: absolute;
    left: -1.5rem;
    bottom: 0.5rem;
    background-color: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    border-radius: 9999px;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.2s var(--ease-out);
    z-index: 11;
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
  
  /* Radial Menu Styling */
  .radial-menu {
    position: absolute;
    bottom: 0;
    right: 0;
    z-index: 90; /* Below the FAB */
    padding: 0;
    pointer-events: none;
    width: 15rem; /* Larger area for menu items */
    height: 15rem; /* Larger area for menu items */
  }
  
  /* When FAB is centered, we need to center the radial menu */
  .radial-menu.center {
    right: -7.5rem; /* Centered under FAB */
    transform-origin: center bottom; /* Change origin for centered menu */
  }
  
  .radial-menu-item {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--primary));
    border-radius: 9999px; /* Circular */
    width: 3rem; /* Slightly larger for better tap targets */
    height: 3rem; /* Slightly larger for better tap targets */
    box-shadow: var(--shadow-md);
    cursor: pointer;
    pointer-events: all;
    transition: all 0.2s ease-out;
    /* Base positioning */
    bottom: 0.5rem; /* Starting position (with offset for FAB) */
    right: 0.5rem; /* Starting position (with offset for FAB) */
  }
  
  /* Different positioning based on FAB position */
  .radial-menu:not(.center) .radial-menu-item {
    transform-origin: bottom right;
    /* Calculate rotation based on index and total - create 135 degree arc */
    transform: rotate(calc(var(--index) * (135deg / max(var(--total) - 1, 1)))) translate(-6rem, 0);
  }
  
  .radial-menu:not(.center) .radial-menu-item:hover {
    transform: rotate(calc(var(--index) * (135deg / max(var(--total) - 1, 1)))) translate(-6.3rem, 0) scale(1.1);
    background: hsl(var(--accent));
    box-shadow: 0 0 10px hsl(var(--primary) / 0.5);
  }
  
  /* Centered FAB positioning for menu items */
  .radial-menu.center .radial-menu-item {
    transform-origin: center bottom;
    /* For centered FAB, create a semi-circular arc above the FAB */
    transform: rotate(calc(var(--index) * (180deg / max(var(--total) - 1, 1)) - 90deg)) translate(0, -6rem);
    right: 7.5rem; /* Center horizontally within the menu */
  }
  
  .radial-menu.center .radial-menu-item:hover {
    transform: rotate(calc(var(--index) * (180deg / max(var(--total) - 1, 1)) - 90deg)) translate(0, -6.3rem) scale(1.1);
    background: hsl(var(--accent));
    box-shadow: 0 0 10px hsl(var(--primary) / 0.5);
  }
  
  /* Icon rotation compensation */
  .radial-menu:not(.center) .radial-menu-icon {
    color: hsl(var(--primary));
    /* Adjust icon rotation to stay upright regardless of button rotation */
    transform: rotate(calc(var(--index) * (-135deg / max(var(--total) - 1, 1))));
  }
  
  .radial-menu.center .radial-menu-icon {
    color: hsl(var(--primary));
    /* Different compensation for centered menu */
    transform: rotate(calc(90deg - var(--index) * (180deg / max(var(--total) - 1, 1))));
  }
  
  .radial-menu-label {
    font-weight: 500;
    color: hsl(var(--foreground));
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