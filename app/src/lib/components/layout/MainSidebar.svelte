<script lang="ts">
  import { page } from '$app/stores';
  import { 
    Home, 
    Calendar, 
    Compass, 
    Clock, 
    CreditCard, 
    Settings, 
    User, 
    HelpCircle, 
    ChevronLeft, 
    ChevronRight,
    LogOut,
    ChevronDown,
    Languages,
    CreditCard as CreditCardIcon,
    Bug
  } from 'lucide-svelte';
  // Import individual components directly
  import Root from '$lib/components/ui/sidebar/sidebar.svelte';
  import Header from '$lib/components/ui/sidebar/sidebar-header.svelte';
  import Content from '$lib/components/ui/sidebar/sidebar-content.svelte';
  import Group from '$lib/components/ui/sidebar/sidebar-group.svelte';
  import GroupLabel from '$lib/components/ui/sidebar/sidebar-group-label.svelte';
  import GroupContent from '$lib/components/ui/sidebar/sidebar-group-content.svelte';
  import Menu from '$lib/components/ui/sidebar/sidebar-menu.svelte';
  import MenuItem from '$lib/components/ui/sidebar/sidebar-menu-item.svelte';
  import Separator from '$lib/components/ui/sidebar/sidebar-separator.svelte';
  import Footer from '$lib/components/ui/sidebar/sidebar-footer.svelte';
  import { onMount } from 'svelte';
  import { Button } from '$lib/components/ui/button';
  
  // Reference to the avatar dropdown element for positioning with proper type
  /** @type {HTMLDivElement | null} */
  let avatarDropdownElement = $state(null);
  
  // Make path a derived state that updates when page changes
  let path = $derived($page.url.pathname);
  
  // Add state for sidebar collapsed
  let collapsed = $state(false);
  
  // Debug info for sidebar elements
  let icons = $state([]);
  let elements = $state([]);
  let debugActive = $state(false);
  let debugCollapsedState = $state(false);
  
  // Toggle debug mode
  function toggleDebug() {
    debugActive = !debugActive;
    if (debugActive) {
      gatherDebugInfo();
    }
  }
  
  // Collect debug information about the sidebar elements
  function gatherDebugInfo() {
    if (!debugActive) return;
    
    // Debug icons
    icons = [];
    const iconElements = document.querySelectorAll('.sidebar-icon');
    iconElements.forEach((el, index) => {
      const styles = window.getComputedStyle(el);
      const svgEl = el.querySelector('svg');
      const svgStyles = svgEl ? window.getComputedStyle(svgEl) : null;
      
      icons.push({
        index,
        width: styles.width,
        height: styles.height,
        minWidth: styles.minWidth,
        minHeight: styles.minHeight,
        display: styles.display,
        opacity: styles.opacity,
        visibility: styles.visibility,
        classes: el.getAttribute('class'),
        svgWidth: svgStyles?.width,
        svgHeight: svgStyles?.height,
        svgDisplay: svgStyles?.display,
        svgVisibility: svgStyles?.visibility,
        svgOpacity: svgStyles?.opacity
      });
    });
    
    // Debug general sidebar elements
    elements = [];
    const sidebar = document.querySelector('.sidebar-wrapper');
    const sidebarRoot = document.querySelector('[data-sidebar="sidebar"]');
    
    if (sidebar) {
      const styles = window.getComputedStyle(sidebar);
      elements.push({
        element: 'sidebar-wrapper',
        width: styles.width,
        minWidth: styles.minWidth,
        maxWidth: styles.maxWidth,
        display: styles.display,
        opacity: styles.opacity,
        visibility: styles.visibility,
        classes: sidebar.getAttribute('class'),
        collapsed: document.body.classList.contains('sidebar-collapsed')
      });
    }
    
    if (sidebarRoot) {
      const styles = window.getComputedStyle(sidebarRoot);
      elements.push({
        element: 'data-sidebar="sidebar"',
        width: styles.width,
        minWidth: styles.minWidth,
        maxWidth: styles.maxWidth,
        display: styles.display,
        opacity: styles.opacity,
        visibility: styles.visibility,
        classes: sidebarRoot.getAttribute('class')
      });
    }
    
    // Debug collapse state
    debugCollapsedState = document.body.classList.contains('sidebar-collapsed');
    
    console.table(icons);
    console.table(elements);
    console.log('[SidebarDebug] Collapsed state:', debugCollapsedState);
  }
  
  // Toggle sidebar collapsed state
  function toggleSidebar() {
    collapsed = !collapsed;
    console.log('[MainSidebar] Toggle state:', collapsed ? 'collapsed' : 'expanded');
    
    // Dispatch custom event for parent components
    const event = new CustomEvent('sidebarToggle', { detail: { collapsed } });
    document.dispatchEvent(event);
    
    // Add class to document body for layout adjustments
    if (collapsed) {
      document.body.classList.add('sidebar-collapsed');
      
      // Apply data-collapsed attribute to all sidebar components
      setTimeout(() => {
        // Set data-collapsed on all key sidebar elements
        document.querySelectorAll('[data-sidebar="content"], [data-sidebar="menu"]').forEach(el => {
          el.setAttribute('data-collapsed', 'true');
        });
        
        // Apply inline styles to SVG elements when collapsed
        const svgElements = document.querySelectorAll('svg');
        svgElements.forEach(svg => {
          svg.style.cssText = 'width: 20px !important; height: 20px !important; min-width: 20px !important; min-height: 20px !important; display: block !important; visibility: visible !important; opacity: 1 !important; flex-shrink: 0 !important; position: static !important; z-index: 50 !important; overflow: visible !important;';
        });
        
        // Apply inline styles to menu items
        const menuItems = document.querySelectorAll('[data-sidebar="menu-item"]');
        menuItems.forEach(item => {
          item.style.cssText = 'min-height: 40px !important; display: flex !important; align-items: center !important; justify-content: center !important; width: 100% !important; padding: 4px 0 !important; z-index: 30 !important; position: relative !important; overflow: visible !important;';
        });
      }, 50);
    } else {
      document.body.classList.remove('sidebar-collapsed');
      
      // Remove data-collapsed attribute from all sidebar components
      setTimeout(() => {
        // Remove data-collapsed from all key sidebar elements
        document.querySelectorAll('[data-sidebar="content"], [data-sidebar="menu"]').forEach(el => {
          el.removeAttribute('data-collapsed');
        });
        
        // Remove inline styles when expanded
        const svgElements = document.querySelectorAll('svg');
        svgElements.forEach(svg => {
          svg.style.cssText = '';
        });
        
        const menuItems = document.querySelectorAll('[data-sidebar="menu-item"]');
        menuItems.forEach(item => {
          item.style.cssText = '';
        });
      }, 50);
    }
    
    // Save state to localStorage
    if (typeof window !== 'undefined' && window.localStorage) {
      localStorage.setItem('sidebar-collapsed', String(collapsed));
    }
    
    // Gather debug info after a short delay to allow DOM updates
    setTimeout(gatherDebugInfo, 100);
  }
  
  // New logging function to inspect sidebar element classes
  function inspectSidebarElements() {
    // Get all sidebar elements
    const allSidebarElements = document.querySelectorAll('[data-sidebar]');
    console.group('[SidebarDebug] Sidebar element inspection (collapsed=' + collapsed + ')');
    
    allSidebarElements.forEach((el) => {
      const dataName = el.getAttribute('data-sidebar');
      const styles = window.getComputedStyle(el);
      console.log(`[SidebarDebug] Element: [data-sidebar="${dataName}"]`, {
        width: styles.width,
        display: styles.display,
        opacity: styles.opacity,
        visibility: styles.visibility,
        computedClasses: styles
      });
    });
    
    // Inspect all menu items
    const menuItems = document.querySelectorAll('[data-sidebar="menu-item"]');
    console.log('[SidebarDebug] MenuItems:', menuItems.length);
    
    console.groupEnd();
  }
  
  // Initialize collapsed state from localStorage if available
  onMount(() => {
    // Check if there's a stored preference in localStorage
    if (typeof window !== 'undefined' && window.localStorage) {
      const storedState = localStorage.getItem('sidebar-collapsed');
      if (storedState === 'true') {
        collapsed = true;
        document.body.classList.add('sidebar-collapsed');
        
        // Initialize data-collapsed attributes
        setTimeout(() => {
          document.querySelectorAll('[data-sidebar="content"], [data-sidebar="menu"]').forEach(el => {
            el.setAttribute('data-collapsed', 'true');
          });
        }, 0);
      }
    }
    
    // Check if body already has sidebar-collapsed class from parent
    if (typeof document !== 'undefined' && document.body.classList.contains('sidebar-collapsed') && !collapsed) {
      collapsed = true;
      
      // Initialize data-collapsed attributes
      setTimeout(() => {
        document.querySelectorAll('[data-sidebar="content"], [data-sidebar="menu"]').forEach(el => {
          el.setAttribute('data-collapsed', 'true');
        });
      }, 0);
    }
    
    console.log('[MainSidebar] Component mounted');
    console.log('[MainSidebar] Current path:', path);
    console.log('[MainSidebar] Initial collapsed state:', collapsed);
    
    // Add a visibility guard element to ensure icons stay visible when collapsed
    const visibilityGuard = document.createElement('style');
    visibilityGuard.id = 'sidebar-visibility-guard';
    visibilityGuard.textContent = `
      /* Critical icons visibility overrides */
      body.sidebar-collapsed [data-sidebar="sidebar"] svg,
      body.sidebar-collapsed svg.lucide,
      body.sidebar-collapsed svg.lucide-icon {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        max-width: none !important;
        max-height: none !important;
        position: static !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        z-index: 1000 !important;
        overflow: visible !important;
        pointer-events: auto !important;
        flex-shrink: 0 !important;
      }
      
      body.sidebar-collapsed [data-sidebar="menu-button"],
      body.sidebar-collapsed [data-sidebar="menu-item"] a,
      body.sidebar-collapsed [data-sidebar="menu-item"] button {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 32px !important;
        min-height: 32px !important;
        position: relative !important;
        padding: 8px !important;
        overflow: visible !important;
        z-index: 999 !important;
      }
    `;
    document.head.appendChild(visibilityGuard);
    
    // Listen for sidebar toggle events from the layout
    /**
     * @param {CustomEvent<{collapsed: boolean}>} event - The sidebar toggle event
     */
    const handleSidebarToggle = (event: CustomEvent<{collapsed: boolean}>) => {
      // Only update if the value is different to prevent infinite loops
      if (collapsed !== event.detail.collapsed) {
        collapsed = event.detail.collapsed;
        console.log('[MainSidebar] State updated from parent:', collapsed ? 'collapsed' : 'expanded');
        
        // Gather debug info when sidebar state is updated from parent
        setTimeout(gatherDebugInfo, 100);
      }
    };
    
    document.addEventListener('sidebarToggle', handleSidebarToggle as EventListener);
    
    // Set up handlers for dropdown positioning
    const handleResize = () => {
      if (isAvatarDropdownOpen) {
        positionDropdown();
      }
    };
    
    window.addEventListener('resize', handleResize);
    
    // Close dropdown when clicking outside
    /**
     * @param {MouseEvent} event - The mouse event
     */
    const handleOutsideClick = (event: MouseEvent) => {
      if (isAvatarDropdownOpen && avatarDropdownElement && 
         !avatarDropdownElement.contains(event.target as Node) && 
         !(event.target as Element).closest('.avatar-container')) {
        isAvatarDropdownOpen = false;
      }
    };
    
    document.addEventListener('click', handleOutsideClick as EventListener);
    
    // Call initial debug info gathering after a delay to ensure DOM is ready
    setTimeout(() => {
      inspectSidebarElements();
      gatherDebugInfo();
    }, 500);
    
    return () => {
      document.removeEventListener('sidebarToggle', handleSidebarToggle as EventListener);
      window.removeEventListener('resize', handleResize);
      document.removeEventListener('click', handleOutsideClick as EventListener);
    };
  });
  
  // Main navigation items with reactive closures for 'active' property
  const mainNavItems = [
    {
      label: "Home",
      url: "/",
      icon: Home,
      get active() { return path === '/' }
    },
    {
      label: "Today",
      url: "/today",
      icon: Calendar,
      get active() { return path.startsWith('/today') }
    },
    {
      label: "Explore",
      url: "/explore",
      icon: Compass,
      get active() { return path.startsWith('/explore') }
    },
    {
      label: "Time Machine",
      url: "/digest",
      icon: Clock,
      get active() { return path.startsWith('/digest') }
    },
    {
      label: "Plans",
      url: "/plans",
      icon: CreditCard,
      get active() { return path.startsWith('/plans') }
    },
    {
      label: "Debug", // Add Debug item
      url: "#debug",
      icon: Bug,
      get active() { return debugActive }
    }
  ];
  
  // Add observer for element visibility
  let visibilityObserver = $state(null);
  onMount(() => {
    // Create a visibility observer to monitor sidebar elements
    visibilityObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.target.classList.contains('sidebar-icon')) {
          const visible = entry.isIntersecting;
          console.log(`[SidebarDebug] Icon visibility changed: ${visible ? 'visible' : 'hidden'}`, 
            entry.target, entry.boundingClientRect);
        }
      });
    }, { threshold: [0, 0.5, 1] });
    
    // Observe all sidebar icons
    document.querySelectorAll('.sidebar-icon').forEach(icon => {
      visibilityObserver.observe(icon);
    });
    
    // Cleanup on unmount
    return () => {
      if (visibilityObserver) {
        visibilityObserver.disconnect();
      }
    };
  });
  
  // User data mock - would come from authentication in a real app
  const user = {
    name: "John Doe",
    email: "john.doe@example.com",
    avatar: "/images/avatar.png",
    plan: "Free" // Free, Spark, Pulse, Bolt
  };
  
  // Avatar dropdown open state
  let isAvatarDropdownOpen = $state(false);
  
  // Function to position the dropdown properly avoiding edge collisions
  function positionDropdown() {
    if (!isAvatarDropdownOpen || !avatarDropdownElement) return;
    
    const avatarButton = document.querySelector('.avatar-container');
    if (!avatarButton) return;
    
    const avatarRect = avatarButton.getBoundingClientRect();
    const dropdownRect = avatarDropdownElement.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const isSidebarCollapsed = document.body.classList.contains('sidebar-collapsed');
    
    // Reset any previous positioning
    avatarDropdownElement.style.top = '';
    avatarDropdownElement.style.left = '';
    avatarDropdownElement.style.bottom = '';
    avatarDropdownElement.style.right = '';
    
    if (isSidebarCollapsed) {
      // When collapsed:
      // Horizontal positioning - position to the right of the avatar
      const rightSpace = viewportWidth - avatarRect.right;
      
      if (rightSpace >= dropdownRect.width + 7) {
        // If there's enough space to the right, position there
        avatarDropdownElement.style.left = `${avatarRect.right + 7}px`;
      } else if (avatarRect.left >= dropdownRect.width + 7) {
        // If there's enough space to the left, position there
        avatarDropdownElement.style.right = `${viewportWidth - avatarRect.left + 7}px`;
      } else {
        // If no space left or right, position centered but ensure it's within viewport
        const leftPosition = Math.max(10, Math.min(viewportWidth - dropdownRect.width - 10, 
                                                 avatarRect.left - (dropdownRect.width - avatarRect.width) / 2));
        avatarDropdownElement.style.left = `${leftPosition}px`;
      }
      
      // Vertical positioning - position dropdown 7px above top right corner of avatar
      const preferredVerticalPosition = "above"; // Prefer above as per requirements
      
      if (preferredVerticalPosition === "above" && avatarRect.top >= dropdownRect.height + 7) {
        // Position 7px above the avatar (preferred placement)
        avatarDropdownElement.style.bottom = `${viewportHeight - avatarRect.top + 7}px`;
      } else if (viewportHeight - avatarRect.bottom >= dropdownRect.height + 7) {
        // Not enough space above, position below the avatar
        avatarDropdownElement.style.top = `${avatarRect.bottom + 7}px`;
      } else {
        // Not enough space above or below, center vertically
        avatarDropdownElement.style.top = `${Math.max(7, Math.min(viewportHeight - dropdownRect.height - 7, 
                                         (viewportHeight - dropdownRect.height) / 2))}px`;
      }
    } else {
      // When expanded:
      // Horizontal positioning - position relative to sidebar content
      if (avatarRect.left + dropdownRect.width > viewportWidth - 7) {
        // Not enough space to the right, position left
        avatarDropdownElement.style.right = `${viewportWidth - avatarRect.left + 7}px`;
      } else {
        // Enough space to the right
        avatarDropdownElement.style.left = `${avatarRect.left}px`;
      }
      
      // Vertical positioning - prefer above the avatar as per requirement
      const preferredVerticalPosition = "above"; // Prefer above as per requirements
      
      if (preferredVerticalPosition === "above" && avatarRect.top >= dropdownRect.height + 7) {
        // Position 7px above the avatar (preferred placement)
        avatarDropdownElement.style.bottom = `${viewportHeight - avatarRect.top + 7}px`;
      } else if (viewportHeight - avatarRect.bottom >= dropdownRect.height + 7) {
        // Not enough space above, position below the avatar
        avatarDropdownElement.style.top = `${avatarRect.bottom + 7}px`;
      } else {
        // Not enough space above or below, center vertically
        avatarDropdownElement.style.top = `${Math.max(7, Math.min(viewportHeight - dropdownRect.height - 7, 
                                         (viewportHeight - dropdownRect.height) / 2))}px`;
      }
    }
    
    // Add a small delay and reposition again to account for any layout shifts
    setTimeout(() => {
      // Ensure the dropdown is still visible and available
      if (!avatarDropdownElement) return;
      
      // Ensure the dropdown is fully within the viewport
      const updatedRect = avatarDropdownElement.getBoundingClientRect();
      
      // Fix horizontal overflow
      if (updatedRect.right > viewportWidth - 7) {
        avatarDropdownElement.style.left = `${viewportWidth - updatedRect.width - 7}px`;
      }
      if (updatedRect.left < 7) {
        avatarDropdownElement.style.left = '7px';
      }
      
      // Fix vertical overflow
      if (updatedRect.bottom > viewportHeight - 7) {
        avatarDropdownElement.style.top = `${viewportHeight - updatedRect.height - 7}px`;
      }
      if (updatedRect.top < 7) {
        avatarDropdownElement.style.top = '7px';
      }
    }, 10);
  }
  
  // Toggle avatar dropdown
  /**
   * @param {MouseEvent} event - The mouse event
   */
  function toggleAvatarDropdown(event: MouseEvent) {
    event.stopPropagation();
    isAvatarDropdownOpen = !isAvatarDropdownOpen;
    
    // If opening, position the dropdown
    if (isAvatarDropdownOpen) {
      // Use setTimeout to ensure DOM update first
      setTimeout(positionDropdown, 0);
    }
  }
  
  // Error handler for avatar image
  /**
   * @param {Event} event - The error event from the image
   */
  function handleImageError(event: Event) {
    // Type assertion for event.target as HTMLImageElement
    const imgElement = /** @type {HTMLImageElement} */ (event.target);
    if (imgElement instanceof HTMLImageElement) {
      imgElement.onerror = null;
      imgElement.src = 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E';
    }
  }
</script>

<style>
  /* Complete overhaul of sidebar collapse styles */
  .sidebar-wrapper {
    position: relative;
    width: 240px;
    min-width: 240px;
    max-width: 240px;
    height: 100%;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), min-width 0.3s cubic-bezier(0.4, 0, 0.2, 1), max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-x: hidden;
    overflow-y: hidden;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
  }
  
  /* Collapsed state dimensions based on shadcn pattern */
  :global(body.sidebar-collapsed) .sidebar-wrapper,
  :global(body.sidebar-collapsed) *[data-testid="sidebar"] {
    width: 64px !important; 
    min-width: 64px !important;
    max-width: 64px !important;
  }
  
  /* Reset all width calculations for shadcn components */
  :global(body.sidebar-collapsed) [data-sidebar="sidebar"],
  :global(body.sidebar-collapsed) *[data-sidebar="sidebar"] {
    width: 64px !important;
    min-width: 64px !important;
    max-width: 64px !important;
    box-sizing: border-box !important;
    overflow-x: hidden !important;
  }
  
  /* Fix menu items when collapsed */
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li {
    width: 100% !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
  }
  
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a {
    width: 100% !important;
    padding: 0.75rem 0 !important; /* Increased from 0.5rem for better touch targets */
    justify-content: center !important;
    align-items: center !important;
  }
  
  /* Force hide all content that should be hidden in collapsed state */
  :global(body.sidebar-collapsed) .sidebar-content-collapsible {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
    opacity: 0 !important;
    visibility: hidden !important;
    position: absolute !important;
    overflow: hidden !important;
    pointer-events: none !important;
  }
  
  /* Base sidebar icon styles with fixed dimensions */
  .sidebar-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    min-width: 24px;
    min-height: 24px;
    margin-right: 0.75rem;
    flex-shrink: 0;
    position: relative;
    transition: margin 0.3s ease-in-out;
  }
  
  /* Icon SVG sizing - ensure proper display */
  .sidebar-icon svg {
    width: 20px;
    height: 20px;
    min-width: 20px;
    min-height: 20px;
    flex-shrink: 0;
    transition: transform 0.3s ease-in-out;
  }
  
  /* Ensure icons are centered and visible in collapsed state */
  :global(body.sidebar-collapsed) .sidebar-icon {
    margin: 0 auto !important;
    padding: 0 !important;
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    flex-shrink: 0 !important;
  }
  
  /* Fix icon sizing and position in collapsed state */
  :global(body.sidebar-collapsed) .sidebar-icon svg {
    width: 20px !important;
    height: 20px !important;
    min-width: 20px !important;
    min-height: 20px !important;
    flex-shrink: 0 !important;
  }
  
  /* Always keep toggle button visible with higher z-index */
  .sidebar-toggle,
  :global(.sidebar-toggle),
  :global([role="button"].sidebar-toggle),
  :global(button.sidebar-toggle) {
    position: absolute !important;
    right: -11px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    z-index: 9999 !important;
    width: 22px !important;
    height: 22px !important;
    border-radius: 50% !important;
    background-color: hsl(var(--background)) !important;
    border: 1px solid hsl(var(--border)) !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.3s ease-in-out !important;
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
  }
  
  /* Fix focus style for toggle button */
  .sidebar-toggle:focus-visible,
  :global(.sidebar-toggle:focus-visible) {
    outline: 2px solid hsl(var(--primary)) !important;
    outline-offset: 2px !important;
  }
  
  /* Hover effect for toggle */
  .sidebar-toggle:hover,
  :global(.sidebar-toggle:hover) {
    background-color: hsl(var(--muted)) !important;
    transform: translateY(-50%) scale(1.05) !important;
  }
  
  /* Active state for toggle */
  .sidebar-toggle:active,
  :global(.sidebar-toggle:active) {
    transform: translateY(-50%) scale(0.95) !important;
  }
  
  /* Target direct ShadCn containers for proper layout */
  :global(body.sidebar-collapsed) [data-sidebar="header"],
  :global(body.sidebar-collapsed) [data-sidebar="content"],
  :global(body.sidebar-collapsed) [data-sidebar="footer"] {
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
    width: 64px !important;
  }
  
  /* Remove padding from content in collapsed state */
  :global(body.sidebar-collapsed) [data-sidebar="content"] {
    padding: 0 !important;
  }
  
  /* Logo centering in collapsed state */
  .header-logo {
    display: flex;
    align-items: center;
    margin-right: auto;
    transition: margin 0.3s ease-in-out;
  }
  
  :global(body.sidebar-collapsed) .header-logo {
    margin: 0 auto;
    justify-content: center;
    width: 100%;
  }
  
  /* Hide collapsible content in collapsed state */
  .sidebar-content-collapsible {
    opacity: 1;
    transition: opacity 0.2s ease-in-out;
    visibility: visible;
  }
  
  /* ShadCn menu adjustments for collapsed mode */
  :global(body.sidebar-collapsed) [data-sidebar="menu"] {
    width: 100% !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 0.25rem !important;
    padding: 0 !important;
    margin: 0 !important;
    align-items: center !important;
  }
  
  /* Override any gap classes when collapsed */
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a {
    gap: 0 !important;
  }
  
  /* Center elements in collapsed mode */
  :global(body.sidebar-collapsed) [data-sidebar="group"] {
    padding: 0 !important;
    width: 100% !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
  }
  
  /* Avatar styling with fixed dimensions */
  .avatar {
    width: 2.5rem;
    height: 2.5rem;
    min-width: 2.5rem;
    min-height: 2.5rem;
    border-radius: 9999px;
    overflow: hidden;
    border: 1px solid hsl(var(--border));
    flex-shrink: 0;
    transition: all 0.3s ease-in-out;
  }
  
  /* Ensure avatar content is properly sized */
  .avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  
  /* Avatar transformation in collapsed state */
  :global(body.sidebar-collapsed) .avatar {
    width: 2rem !important;
    height: 2rem !important;
    min-width: 2rem !important;
    min-height: 2rem !important;
    border-radius: 0.375rem !important; /* Square corners when collapsed */
  }
  
  /* Avatar container alignment */
  .avatar-container {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 0.375rem;
    transition: background-color 0.2s ease-in-out, padding 0.3s ease-in-out;
    width: 100%;
  }
  
  :global(body.sidebar-collapsed) .avatar-container {
    padding: 0.5rem 0;
    justify-content: center;
    width: 100%;
  }
  
  /* Hover effect for avatar container */
  .avatar-container:hover {
    background-color: hsl(var(--muted)/0.2);
  }
  
  /* Dropdown menu positioning */
  .avatar-dropdown {
    position: fixed;
    z-index: 9999;
    width: 16rem;
    background-color: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: 0.375rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    padding: 0.5rem;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    animation: fadeIn 0.2s ease-out;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  /* Group label display in collapsed state */
  :global(body.sidebar-collapsed) .sidebar-group-label {
    display: none !important;
  }
  
  /* Recent digests visibility in collapsed state */
  :global(body.sidebar-collapsed) .recent-digests {
    display: none !important;
  }
  
  /* Basic menu item styling */
  .sidebar-menu-item a,
  [data-sidebar="menu-item"] a {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 1.625rem 0.75rem;
    border-radius: 0.375rem;
    transition: all 0.3s ease-in-out;
  }
  
  /* Menu hover effects */
  .menu-item-hover:hover,
  [data-sidebar="menu-item"] a:hover {
    background-color: hsl(var(--muted)/0.3);
  }
  
  .menu-item-hover.active,
  [data-sidebar="menu-item"] a.active {
    background-color: hsl(var(--primary)/0.1);
    color: hsl(var(--primary));
    font-weight: 600;
  }
  
  /* Collapsed state menu item adjustments */
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] a,
  :global(body.sidebar-collapsed) .sidebar-menu-item a {
    justify-content: center !important;
    padding: 0.75rem 0 !important;
    gap: 0 !important;
    width: 100% !important;
  }
  
  /* Ensure text is completely hidden in collapsed state */
  :global(body.sidebar-collapsed) span:not(.sidebar-icon span) {
    display: none !important;
    visibility: hidden !important;
    position: absolute !important;
    overflow: hidden !important;
    width: 0 !important;
    height: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }
  
  /* Complete overhaul of ul elements */
  :global(body.sidebar-collapsed) ul,
  :global(body.sidebar-collapsed) li {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
  }
  
  /* Dropdown styles */
  .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: background-color 0.2s;
    font-size: 0.875rem;
    width: 100%;
  }
  
  .dropdown-item:hover {
    background-color: hsl(var(--muted)/0.3);
  }
  
  /* Upgrade button styling */
  .upgrade-button {
    display: block;
    width: 100%;
    padding: 0.5rem;
    text-align: center;
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 0.5rem;
    transition: opacity 0.2s;
  }
  
  .upgrade-button:hover {
    opacity: 0.9;
  }

  /* Fix icon sizing and position in collapsed state */
  :global(body.sidebar-collapsed) .sidebar-icon,
  :global(body.sidebar-collapsed) [class*="sidebar-icon"] {
    margin: 0 auto !important;
    padding: 0 !important;
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    flex-shrink: 0 !important;
    opacity: 1 !important;
    visibility: visible !important;
  }

  /* Fix icon SVG sizing and position in collapsed state */
  :global(body.sidebar-collapsed) .sidebar-icon svg,
  :global(body.sidebar-collapsed) [class*="sidebar-icon"] svg {
    width: 20px !important;
    height: 20px !important;
    min-width: 20px !important;
    min-height: 20px !important;
    flex-shrink: 0 !important;
    opacity: 1 !important;
    visibility: visible !important;
    display: block !important;
  }

  /* Override hidden span when in sidebar-icon */
  :global(body.sidebar-collapsed) .sidebar-icon span,
  :global(body.sidebar-collapsed) [class*="sidebar-icon"] span {
    display: none !important;
  }

  /* When body has sidebar-collapsed, force all spans to none EXCEPT inside sidebar-icon */
  :global(body.sidebar-collapsed) span:not(.sidebar-icon span):not([class*="sidebar-icon"] span) {
    display: none !important;
    visibility: hidden !important;
    position: absolute !important;
    overflow: hidden !important;
    width: 0 !important;
    height: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }

  /* Collapsed state menu item adjustments */
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] a,
  :global(body.sidebar-collapsed) .sidebar-menu-item a {
    justify-content: center !important;
    padding: 0.75rem 0 !important;
    gap: 0 !important;
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
  }

  /* Fix for Svelte 5 scoped classes */
  :global(body.sidebar-collapsed) [class*="s-"] .sidebar-icon,
  :global(body.sidebar-collapsed) .sidebar-icon[class*="s-"] {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    margin: 0 auto !important;
  }

  :global(body.sidebar-collapsed) [class*="s-"] .sidebar-icon svg,
  :global(body.sidebar-collapsed) .sidebar-icon[class*="s-"] svg {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
  }

  /* Ensure menu items show correctly */
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li {
    opacity: 1 !important;
    visibility: visible !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
  }

  /* Avatar transformation in collapsed state */
  :global(body.sidebar-collapsed) .avatar,
  :global(body.sidebar-collapsed) [class*="avatar"] {
    width: 2rem !important;
    height: 2rem !important;
    min-width: 2rem !important;
    min-height: 2rem !important;
    border-radius: 0.375rem !important; /* Square corners when collapsed */
    opacity: 1 !important;
    visibility: visible !important;
    display: block !important;
  }

  /* Create a new emergency fix section for Svelte 5 scoped classes */
  /* EMERGENCY FIXES FOR COLLAPSED STATE */
  :global(body.sidebar-collapsed) a [class*="s-"] .sidebar-icon,
  :global(body.sidebar-collapsed) a [class*="s-"] .sidebar-icon svg,
  :global(body.sidebar-collapsed) a[class*="s-"] .sidebar-icon,
  :global(body.sidebar-collapsed) a[class*="s-"] .sidebar-icon svg,
  :global(body.sidebar-collapsed) div[class*="s-"] .sidebar-icon,
  :global(body.sidebar-collapsed) div[class*="s-"] .sidebar-icon svg,
  :global(body.sidebar-collapsed) .sidebar-icon[class*="s-"],
  :global(body.sidebar-collapsed) .sidebar-icon[class*="s-"] svg,
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] [class*="s-"] .sidebar-icon,
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] [class*="s-"] .sidebar-icon svg {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    width: 24px !important;
    height: 24px !important;
    position: static !important;
    margin: 0 auto !important;
  }

  :global(body.sidebar-collapsed) a [class*="s-"] .sidebar-icon svg,
  :global(body.sidebar-collapsed) a[class*="s-"] .sidebar-icon svg,
  :global(body.sidebar-collapsed) div[class*="s-"] .sidebar-icon svg,
  :global(body.sidebar-collapsed) .sidebar-icon[class*="s-"] svg,
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] [class*="s-"] .sidebar-icon svg {
    width: 20px !important;
    height: 20px !important;
    min-width: 20px !important;
    min-height: 20px !important;
    display: block !important;
  }

  /* Force display of all svg icons in collapsed state */
  :global(body.sidebar-collapsed) svg {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
  }

  /* Special fix for the specific classes in the DOM */
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a .s-Ye0sG_sAbYc3,
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a.s-Ye0sG_sAbYc3 {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: 100% !important;
    justify-content: center !important;
    align-items: center !important;
  }

  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a .sidebar-icon.s-Ye0sG_sAbYc3,
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a .s-Ye0sG_sAbYc3 .sidebar-icon {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    position: static !important;
    margin: 0 auto !important;
  }

  /* Direct path to the icon svgs based on DOM structure */
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a .sidebar-icon svg,
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a .sidebar-icon .lucide,
  :global(body.sidebar-collapsed) [data-sidebar="menu"] li a .sidebar-icon .lucide-icon {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: 20px !important;
    height: 20px !important;
  }

  /* Direct target for Lucide components and their child elements */
  :global(body.sidebar-collapsed) .lucide,
  :global(body.sidebar-collapsed) .lucide-icon,
  :global(body.sidebar-collapsed) .lucide *,
  :global(body.sidebar-collapsed) .lucide-icon * {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
  }
  
  /* Fix for any remaining SVG elements */
  :global(body.sidebar-collapsed) path,
  :global(body.sidebar-collapsed) circle,
  :global(body.sidebar-collapsed) rect,
  :global(body.sidebar-collapsed) line,
  :global(body.sidebar-collapsed) polyline {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
  }
  
  /* Target icons directly based on their source SVG components */
  :global(body.sidebar-collapsed) [data-lucide] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    width: 20px !important;
    height: 20px !important;
  }

  /* Enhanced forced display of icons in collapsed state */
  @keyframes forceIconShow {
    0%, 100% { opacity: 1; visibility: visible; }
  }

  /* Target all SVG icons with ultimate specificity */
  :global(body.sidebar-collapsed) svg,
  :global(body.sidebar-collapsed) .lucide,
  :global(body.sidebar-collapsed) .lucide-icon,
  :global(body.sidebar-collapsed) [data-sidebar="menu-button"] svg,
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] svg {
    width: 20px !important;
    height: 20px !important;
    min-width: 20px !important;
    min-height: 20px !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    flex-shrink: 0 !important;
    flex-grow: 0 !important;
    pointer-events: auto !important;
    position: static !important;
    z-index: 50 !important;
    overflow: visible !important;
    animation: forceIconShow 0.1s forwards !important;
  }

  /* Ultimate override - for Svelte 5 scoped classes */
  :global(body.sidebar-collapsed) [class*="s-"] svg,
  :global(body.sidebar-collapsed) svg[class*="s-"],
  :global(body.sidebar-collapsed) [data-sidebar] [class*="s-"] svg,
  :global(body.sidebar-collapsed) [data-sidebar] svg[class*="s-"] {
    width: 20px !important;
    height: 20px !important;
    min-width: 20px !important;
    min-height: 20px !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    flex-shrink: 0 !important;
    z-index: 50 !important;
    position: static !important;
    transform: none !important;
    animation: forceIconShow 0.1s forwards !important;
  }

  /* Force icon containers to show */
  :global(body.sidebar-collapsed) .sidebar-icon,
  :global(body.sidebar-collapsed) [class*="sidebar-icon"],
  :global(body.sidebar-collapsed) [data-sidebar="menu-button"],
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] > a,
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"] > button {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: auto !important;
    min-width: 32px !important;
    min-height: 32px !important;
    padding: 6px !important;
    position: relative !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 40 !important;
    overflow: visible !important;
    transform: none !important;
    animation: forceIconShow 0.1s forwards !important;
  }

  /* Force parent containers to properly size */
  :global(body.sidebar-collapsed) [data-sidebar="menu"],
  :global(body.sidebar-collapsed) [data-sidebar="menu-item"],
  :global(body.sidebar-collapsed) ul,
  :global(body.sidebar-collapsed) li {
    min-height: 40px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    padding: 4px 0 !important;
    z-index: 30 !important;
    position: relative !important;
    overflow: visible !important;
  }

  /* Add direct inline style to SVG elements via attribute when sidebar collapses */
  @media screen {
    :global(body.sidebar-collapsed) svg {
      width: 20px;
      height: 20px;
      min-width: 20px;
      min-height: 20px;
      display: block;
      visibility: visible;
      opacity: 1;
    }
  }
</style>

<div 
  class="sidebar-wrapper" 
  data-testid="sidebar" 
  class:collapsed={collapsed} 
  style={collapsed ? 'width: 64px !important; min-width: 64px !important; max-width: 64px !important;' : 'width: 240px; min-width: 240px; max-width: 240px;'}
>
  <Root 
    class="h-full border-r border-[hsl(var(--sidebar-border)/0.8)] bg-[hsl(var(--sidebar-background))] text-[hsl(var(--sidebar-foreground))] shadow-[1px_0_5px_rgba(0,0,0,0.05)]"
    style={collapsed ? 'width: 64px !important; min-width: 64px !important; max-width: 64px !important;' : ''}
  >
    <Header class="py-[1rem] px-[0.75rem] border-b border-[hsl(var(--sidebar-border)/0.8)] relative">
      <div class="flex items-center justify-between px-[0.5rem]">
        <a href="/" class="header-logo">
          <span class="font-[600] text-[1.125rem]">⚡️ ASAP</span>
        </a>
      </div>
      <!-- Enhanced toggle button with stronger styling -->
      <button 
        type="button" 
        onclick={toggleSidebar} 
        class="sidebar-toggle"
        id="sidebar-toggle-button"
        aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
        style="z-index: 9999 !important; display: flex !important; visibility: visible !important; opacity: 1 !important;"
      >
        {#if collapsed}
          <ChevronRight size={16} class="opacity-100" />
        {:else}
          <ChevronLeft size={16} class="opacity-100" />
        {/if}
      </button>
    </Header>
    
    <Content 
      class="overflow-y-auto" 
      collapsed={collapsed}
      style={collapsed ? 'width: 64px !important; min-width: 64px !important; max-width: 64px !important;' : ''}>
      <Group class="pb-[1rem] pt-[1rem]">
        <Menu class="space-y-[0.75rem]" collapsed={collapsed}>
          {#each mainNavItems as item (item.label)}
            <MenuItem class="px-0 sidebar-menu-item">
              <a 
                href={item.url} 
                class="{item.active ? 'active' : ''} menu-item-hover flex items-center gap-[0.75rem] w-full"
                data-sveltekit-preload-data="hover"
                style={collapsed ? 'justify-content: center !important; padding-left: 0 !important; padding-right: 0 !important;' : ''}
              >
                <div class="sidebar-icon" style="display: flex !important; visibility: visible !important; opacity: 1 !important;">
                  {#if item.icon}
                    <item.icon size={20} style="display: block !important; visibility: visible !important; opacity: 1 !important;" />
                  {/if}
                </div>
                <span class="sidebar-content-collapsible font-[600]">{item.label}</span>
              </a>
            </MenuItem>
          {/each}
        </Menu>
      </Group>
      
      <Separator class="my-[0.75rem] bg-[hsl(var(--sidebar-border)/0.8)] h-[1px]" />

      <Group class="pb-[1rem] recent-digests">
        <GroupLabel class="sidebar-group-label px-[0.75rem] py-[0.5rem] text-[0.75rem] uppercase font-[700] text-[hsl(var(--sidebar-foreground)/0.7)]" child={() => "Recent Digests"}>
          Recent Digests
        </GroupLabel>
        <GroupContent class="space-y-[0.75rem] sidebar-content-collapsible">
          <Menu class="space-y-[0.75rem]">
            {#each ['Tech Digest', 'Finance Update', 'Health News'] as digest}
              <MenuItem class="px-0">
                <a 
                  href={`/digest/${digest.toLowerCase().replace(/\s+/g, '-')}`} 
                  class="menu-item-hover flex items-center w-full justify-start py-[0.625rem] px-[0.75rem] text-[0.875rem]"
                  data-sveltekit-preload-data="hover"
                >
                  <span class="font-[600]">{digest}</span>
                </a>
              </MenuItem>
            {/each}
          </Menu>
        </GroupContent>
      </Group>
    </Content>
    
    <Footer class="mt-auto py-[1rem] px-[1rem] border-t border-[hsl(var(--sidebar-border)/0.8)]">
      <!-- User profile area with dropdown -->
      <div class="relative">
        <button class="avatar-container w-full text-left" onclick={toggleAvatarDropdown} aria-haspopup="true" aria-expanded={isAvatarDropdownOpen}>
          <div class="avatar">
            <img src={user.avatar} alt={user.name} onerror={handleImageError} class="w-full h-full object-cover" />
          </div>
          <div class="ml-[0.5rem] sidebar-content-collapsible">
            <div class="font-semibold">{user.name}</div>
            <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.plan}</div>
          </div>
          <ChevronRight size={16} class="ml-auto transition-transform duration-200 {isAvatarDropdownOpen ? 'rotate-90' : ''} sidebar-content-collapsible" />
        </button>
        
        {#if isAvatarDropdownOpen}
          <div class="avatar-dropdown" bind:this={avatarDropdownElement}>
            <div class="p-[0.5rem] border-b border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="font-semibold">{user.name}</div>
              <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.email}</div>
              <div class="text-[0.75rem] font-[500] mt-[0.25rem] text-[hsl(var(--primary))]">{user.plan}</div>
            </div>
            
            <div class="py-[0.25rem]">
              <a href="/billing" class="dropdown-item">
                <CreditCard size={16} />
                <span>Billing</span>
              </a>
              <a href="/settings" class="dropdown-item">
                <Settings size={16} />
                <span>Settings</span>
              </a>
              <a href="/logout" class="dropdown-item">
                <LogOut size={16} />
                <span>Sign Out</span>
              </a>
            </div>
            
            <div class="p-[0.5rem] border-t border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="text-[0.75rem] font-semibold mb-[0.5rem]">Preferences</div>
              <div class="flex justify-between items-center mb-[0.5rem]">
                <span class="text-[0.75rem]">Theme</span>
                <div class="flex space-x-[0.25rem]">
                  <button class="p-[0.25rem] rounded-[0.375rem] bg-[hsl(var(--background))] border border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]" aria-label="Light theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                  </button>
                  <button class="p-[0.25rem] rounded-[0.375rem] bg-[hsl(var(--muted))] border border-[hsl(var(--muted-foreground)/0.2)]" aria-label="Dark theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[hsl(var(--background))]"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                  </button>
                  <button class="p-[0.25rem] rounded-[0.375rem] bg-[hsl(var(--muted)/0.2)] border border-[hsl(var(--border))] dark:bg-[hsl(var(--muted)/0.2)] dark:border-[hsl(var(--muted-foreground)/0.2)]" aria-label="System theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </button>
                </div>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[0.75rem]">Language</span>
                <select class="text-[0.75rem] p-[0.25rem] rounded-[0.375rem] bg-transparent border border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
                  <option>English</option>
                  <option>Spanish</option>
                  <option>French</option>
                </select>
              </div>
            </div>
            
            {#if user.plan !== 'Bolt'}
              <button class="upgrade-button">
                Upgrade Plan
              </button>
            {/if}
          </div>
        {/if}
      </div>
    </Footer>
  </Root>
</div> 