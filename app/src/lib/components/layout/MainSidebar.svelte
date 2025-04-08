<script>
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
    Bug,
    PanelLeft,
    Bell,
    LineChart
  } from '$lib/utils/lucide-icons.js';
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
  import Icon from "$lib/components/ui/Icon.svelte";

  /**
   * @typedef {Object} IconObject
   * @property {string} name - The icon name
   * @property {string} svgContent - The SVG content as a string
   */

  /**
   * Render the SVG icon based on its content
   * @param {{name: string, svgContent: string}} icon - Icon object with svgContent
   * @param {number} size - Size of the icon in pixels
   * @param {string} [className] - Optional CSS class for the icon
   * @returns {string} SVG HTML string
   */
  function renderIcon(icon, size = 24, className = '') {
    if (!icon || typeof icon !== 'object' || !icon.svgContent) {
      console.error(`[IconError] Invalid icon object:`, icon);
      // Return an empty SVG as fallback to prevent breaking the UI
      return `<svg xmlns="http://www.w3.org/2000/svg" 
        width="${size}" height="${size}" 
        viewBox="0 0 24 24" 
        class="${className || ''}"></svg>`;
    }
    
    return `<svg xmlns="http://www.w3.org/2000/svg" 
      width="${size}" height="${size}" 
      viewBox="0 0 24 24" 
      fill="none" 
      stroke="currentColor" 
      stroke-width="2" 
      stroke-linecap="round" 
      stroke-linejoin="round" 
      class="${className}">${icon.svgContent}</svg>`;
  }
  
  // Reference to the avatar dropdown element for positioning with proper type
  /** @type {HTMLDivElement | null} */
  let avatarDropdownElement = $state(null);
  
  // Make path a derived state that updates when page changes
  let path = $derived($page.url.pathname);
  
  // Debug info for sidebar elements
  /** @type {Array<any>} */
  let icons = $state([]);
  
  /** @type {Array<any>} */
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
  
  // Accept props including the user data
  let {
    collapsed = false,
    toggleSidebar = () => { console.error('toggleSidebar prop not provided to MainSidebar'); },
    isMobile = false,
    closeMobileMenu = () => {},
    /** @type {User} */
    user = null
  } = $props();

  // Log received user data when it changes
  $effect(() => {
    console.debug('[MainSidebar $effect] Received user prop:', JSON.stringify(user));
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
      label: "Dashboard",
      url: "/dashboard",
      icon: LineChart,
      get active() { return path.startsWith('/dashboard') }
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
    }
  ];
  
  // Dev navigation items - only shown in development mode
  const devNavItems = [
    {
      label: "Design System",
      url: "/design-system",
      icon: PanelLeft,
      get active() { return path.startsWith('/design-system') }
    },
    {
      label: "Debug", // Add Debug item
      url: "#debug",
      icon: Bug,
      get active() { return debugActive }
    },
    {
      label: "Demo",
      url: "/demo",
      icon: Compass,
      get active() { return path.startsWith('/demo') }
    }
  ];
  
  // Show dev items only in development mode
  const isDev = import.meta.env.DEV;
  
  // Add observer for element visibility
  let visibilityObserver = $state(null);
  onMount(() => {
    console.log('[MainSidebar] Component mounted');
    
    // Cleanup on unmount
    return () => {
      if (visibilityObserver) {
        visibilityObserver.disconnect();
      }
    };
  });
  
  // Avatar dropdown open state
  let isAvatarDropdownOpen = $state(false);
  
  // Close dropdown when clicking outside
  /**
   * @param {MouseEvent} event - The mouse event
   */
  function handleClickOutside(event) {
    if (isAvatarDropdownOpen && avatarDropdownElement && 
        !avatarDropdownElement.contains(event.target) && 
        !event.target.closest('.avatar-container')) {
      isAvatarDropdownOpen = false;
    }
  }
  
  // Add document click listener on mount
  onMount(() => {
    console.log('[MainSidebar] Component mounted');
    document.addEventListener('click', handleClickOutside);
    
    // Cleanup on unmount
    return () => {
      document.removeEventListener('click', handleClickOutside);
      if (visibilityObserver) {
        visibilityObserver.disconnect();
      }
    };
  });
  
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
  function toggleAvatarDropdown(event) {
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
  function handleImageError(event) {
    // Type assertion for event.target as HTMLImageElement
    const imgElement = /** @type {HTMLImageElement} */ (event.target);
    if (imgElement instanceof HTMLImageElement) {
      imgElement.onerror = null;
      imgElement.src = 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E';
    }
  }

  // Function to handle menu item clicks, closing mobile menu if needed
  /**
   * @param {MouseEvent} event
   */
  function handleMenuItemClick(event) {
    if (isMobile) {
      // Check if the click target is an anchor tag
      const target = event.target;
      if (target instanceof HTMLAnchorElement && target.closest('a')) {
         closeMobileMenu();
      }
    }
    // Allow default navigation
  }
</script>

<style lang="postcss">
	/* Core sidebar styles */
	.sidebar-wrapper {
		position: relative;
		width: var(--sidebar-width-expanded);
		min-width: var(--sidebar-width-expanded);
		max-width: var(--sidebar-width-expanded);
		height: 100%;
		transition: width 0.3s var(--ease-out), min-width 0.3s var(--ease-out), max-width 0.3s var(--ease-out);
		overflow-y: hidden;
		flex-shrink: 0;
		display: flex;
		flex-direction: column;
		overflow-x: hidden; /* Hide horizontal overflow in expanded state */
	}
	
	/* Collapsed state dimensions */
	.sidebar-wrapper.collapsed {
		width: var(--sidebar-width-collapsed);
		min-width: var(--sidebar-width-collapsed);
		max-width: var(--sidebar-width-collapsed);
		overflow-x: visible; /* Allow tooltips to show */
		overflow-y: visible; /* Allow potential dropdowns */
	}

	/* Override potential Shadcn overflow issues */
	.sidebar-wrapper.collapsed :global([data-sidebar="sidebar"]),
	.sidebar-wrapper.collapsed :global([data-sidebar="content"]) {
		overflow: visible !important;
	}

	/* Reset padding added by Shadcn components when collapsed */
	.sidebar-wrapper.collapsed :global([data-sidebar="header"]),
	.sidebar-wrapper.collapsed :global([data-sidebar="content"]),
	.sidebar-wrapper.collapsed :global([data-sidebar="footer"]) {
		padding-left: 0.5rem;
		padding-right: 0.5rem;
		width: var(--sidebar-width-collapsed);
	}
	.sidebar-wrapper.collapsed :global([data-sidebar="content"]) {
		padding-top: 0.5rem; /* Add some vertical padding back if needed */
		padding-bottom: 0.5rem;
	}

	/* Fix list items when collapsed */
	.sidebar-wrapper.collapsed :global([data-sidebar="menu"] li) {
		position: relative; /* Context for tooltip */
	}
	/* Ensure anchor takes full width for hover */
	.sidebar-wrapper.collapsed :global([data-sidebar="menu"] li > a) {
		width: 100%;
		display: flex;
		justify-content: center;
		align-items: center; /* Vertically center icon */
		padding-top: 0.75rem;
		padding-bottom: 0.75rem;
		height: 2.75rem; /* Give consistent height */
	}

	/* Hide text labels Robustly */
	.sidebar-wrapper.collapsed .menu-item-label,
	.sidebar-wrapper.collapsed .avatar-text-content, /* Target avatar text */
	.sidebar-wrapper.collapsed .avatar-chevron /* Target avatar chevron */
	 {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border-width: 0;
	}

	/* Icon styling */
	.menu-item-icon {
		flex-shrink: 0; /* Prevent icon shrinking */
		display: flex; /* Ensure flex properties apply */
		align-items: center; /* Center icon vertically if needed */
		justify-content: center; /* Center icon horizontally if needed */
	}
	/* Ensure icon SVG maintains size */
	.menu-item-icon :global(svg) {
		width: 1.25rem; /* 20px */
		height: 1.25rem; /* 20px */
		display: block; /* Ensure it behaves as a block */
		flex-shrink: 0; /* Prevent SVG itself from shrinking */
		color: hsl(var(--muted-foreground)); /* Default icon color */
	}

	/* Styles for the anchor tag itself */
	.menu-item-link {
		display: flex;
		align-items: center;
		width: 100%;
		padding: 0.5rem 0.75rem;
		border-radius: 0.375rem; /* rounded-md */
		text-decoration: none;
		gap: 0.75rem; /* space-x-3 */
		flex-wrap: nowrap;
		white-space: nowrap;
		overflow: hidden; /* Hide text overflow in expanded */
		text-overflow: ellipsis;
		transition: background-color 0.2s ease-out, color 0.2s ease-out;
		position: relative; /* Needed for tooltip context */
		color: hsl(var(--foreground)); /* Default text color */
		height: 2.5rem; /* Consistent height */
	}
	.menu-item-link:hover {
		background-color: hsl(var(--muted)/0.3);
		color: hsl(var(--foreground)); /* Ensure hover keeps text color */
	}
	.menu-item-link:hover .menu-item-icon :global(svg) {
		color: hsl(var(--foreground)); /* Icon color on hover */
	}
	/* Active State Styling */
	.menu-item-link.active {
		background-color: hsl(var(--primary)); /* Primary background */
		color: hsl(var(--primary-foreground)); /* Primary foreground text */
		font-weight: 600;
	}
	.menu-item-link.active .menu-item-icon :global(svg) {
		color: hsl(var(--primary-foreground)); /* Primary foreground icon */
	}

	/* Specific collapsed styles for the link */
	.sidebar-wrapper.collapsed .menu-item-link {
		justify-content: center;
		padding: 0.75rem 0;
		overflow: visible; /* Allow tooltip to show */
		width: 2.75rem; /* Center the icon better */
		margin: 0 auto; /* Center the link block */
	}
	/* Fix icon size/squishing when collapsed */
	.sidebar-wrapper.collapsed .menu-item-icon {
		width: 1.25rem; /* Explicit width for container */
		height: 1.25rem; /* Explicit height for container */
	}
	/* Default icon color when collapsed */
	.sidebar-wrapper.collapsed .menu-item-link .menu-item-icon :global(svg) {
		color: hsl(var(--muted-foreground));
	}
	/* Hover icon color when collapsed */
	.sidebar-wrapper.collapsed .menu-item-link:hover .menu-item-icon :global(svg) {
		color: hsl(var(--foreground));
	}
	/* Active icon color when collapsed */
	.sidebar-wrapper.collapsed .menu-item-link.active .menu-item-icon :global(svg) {
		color: hsl(var(--primary-foreground)); /* Match active state */
	}
	
	/* Sidebar toggle button */
	.sidebar-toggle {
		position: absolute !important;
		right: -0.75rem !important;
		top: 1.5rem !important;
		transform: translateY(-50%) !important;
		width: 1.5rem !important;
		height: 1.5rem !important;
		border-radius: 9999px !important;
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
		z-index: var(--z-sidebar-trigger) !important; /* Ensure higher z-index */
	}
	
	/* Button states */
	.sidebar-toggle:focus-visible {
		outline: 2px solid hsl(var(--primary));
		outline-offset: 2px;
	}

	.sidebar-toggle:hover {
		background-color: hsl(var(--muted));
		transform: translateY(-50%) scale(1.05) !important;
	}
	
	.sidebar-toggle:active {
		transform: translateY(-50%) scale(0.95) !important;
	}
	
	/* Logo styling */
	.header-logo {
		display: flex;
		align-items: center;
		margin-right: auto;
		transition: margin 0.3s ease-in-out;
		overflow: hidden; /* Prevent text showing during transition */
		white-space: nowrap;
	}
	
	.sidebar-wrapper.collapsed .header-logo {
		margin: 0 auto;
		justify-content: center;
		width: 100%;
	}
	/* Hide logo text when collapsed */
	.sidebar-wrapper.collapsed .header-logo span {
		 display: none; /* Simple hide */
	}
	/* Show only icon part of logo when collapsed */
	.sidebar-wrapper.collapsed .header-logo::before {
		content: '⚡️'; /* Or use an SVG icon */
		display: block;
		font-size: 1.5rem; /* Adjust size */
		line-height: 1; /* Ensure proper alignment */
	}
	
	/* Avatar styling */
	.avatar {
		width: 2.5rem; /* 40px */
		height: 2.5rem; /* 40px */
		min-width: 2.5rem;
		min-height: 2.5rem;
		border-radius: 9999px;
		overflow: hidden;
		border: 1px solid hsl(var(--border));
		flex-shrink: 0;
		transition: all 0.3s ease-in-out;
	}
	
	.avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	
	.sidebar-wrapper.collapsed .avatar {
		width: 2rem; /* 32px */
		height: 2rem; /* 32px */
		min-width: 2rem;
		min-height: 2rem;
	}
	
	/* Hide group label & recent digests when collapsed (more specific) */
	.sidebar-wrapper.collapsed .sidebar-group-label,
	.sidebar-wrapper.collapsed .recent-digests {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border-width: 0;
	}

	/* Tooltip Styling */
	.tooltip {
		position: absolute;
		left: 100%;
		top: 50%;
		transform: translateY(-50%);
		margin-left: 0.75rem;
		background-color: hsl(var(--popover));
		color: hsl(var(--popover-foreground));
		padding: 0.25rem 0.5rem;
		border-radius: var(--radius-md);
		font-size: var(--font-size-xs);
		font-weight: var(--font-weight-medium);
		white-space: nowrap;
		z-index: var(--z-sidebar-tooltip);
		opacity: 0;
		visibility: hidden;
		transition: opacity 0.15s ease-out, visibility 0.15s ease-out;
		pointer-events: none;
	}

	/* Show tooltip on hover of the parent link when sidebar is collapsed */
	.sidebar-wrapper.collapsed .menu-item-link:hover .tooltip {
		opacity: 1;
		visibility: visible;
	}

	/* Fix z-index for sidebar toggle button */
	.sidebar-toggle {
		position: absolute !important;
		right: -0.75rem !important;
		top: 1.5rem !important;
		transform: translateY(-50%) !important;
		width: 1.5rem !important;
		height: 1.5rem !important;
		border-radius: 9999px !important;
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
		z-index: var(--z-sidebar-trigger) !important; /* Ensure higher z-index */
	}
	
	/* Icon styling - use highlight color for inactive menu items */
	.menu-item-link:not(.active) .menu-item-icon :global(svg) {
		color: hsl(var(--sidebar-highlight)); /* Highlight color for icons */
	}
	
	.menu-item-link:not(.active) {
		color: hsl(var(--foreground)); /* Base text color for non-active items */
	}
	
	/* Active State Styling - toned down background */
	.menu-item-link.active {
		background-color: hsl(var(--active-menu-bg)); /* Toned down primary */
		color: hsl(var(--primary-foreground)); /* Primary foreground text */
		font-weight: 600;
	}
	
	/* Hide group labels when sidebar is collapsed */
	.sidebar-wrapper.collapsed .sidebar-group-label,
	.sidebar-wrapper.collapsed .recent-digests-label {
		display: none !important;
	}
	
	/* Ensure avatar dropdown has proper z-index and visibility */
	.avatar-dropdown {
		z-index: var(--z-dropdown);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

</style>

<div 
  class="sidebar-wrapper" 
  data-testid="sidebar" 
  class:collapsed={collapsed} 
>
  <Root 
    class="flex h-full flex-col border-r border-[hsl(var(--sidebar-border)/0.8)] bg-[hsl(var(--sidebar-background))] text-[hsl(var(--sidebar-foreground))] shadow-[1px_0_5px_rgba(0,0,0,0.05)]"
  >
    <Header class="relative border-b border-[hsl(var(--sidebar-border)/0.8)] px-[0.75rem] py-[1rem]">
      <div class="flex items-center justify-between px-[0.5rem]">
        <a href="/" class="header-logo">
          <span class="font-[600] text-[1.125rem]"> ASAP</span>
        </a>
      </div>
      <button 
        class="sidebar-toggle z-[var(--z-sidebar-trigger)]"
        onclick={toggleSidebar}
        aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
      >
        {#if collapsed}
          <Icon icon={ChevronRight} size={16} color="currentColor" />
        {:else}
          <Icon icon={ChevronLeft} size={16} color="currentColor" />
        {/if}
      </button>
    </Header>
    
    <Content class="flex-grow overflow-y-auto" {collapsed} style={collapsed ? 'overflow: visible !important;' : ''}>
      <Group class="pb-[1rem] pt-[1rem]">
        <Menu class="space-y-[0.75rem]" {collapsed}>
          {#each mainNavItems as item (item.label)}
            <MenuItem {collapsed}>
              <a 
                href={item.url} 
                class="menu-item-link px-3!"
                class:active={item.active}
                data-sveltekit-preload-data="hover"
                onclick={handleMenuItemClick}
                title={collapsed ? item.label : ''}
              >
                <div class="menu-item-icon">
                  {#if item.icon}
                    <Icon icon={item.icon} size={20} color="currentColor" />
                  {/if}
                </div>
                <span class="menu-item-label font-[600]">{item.label}</span>

                <div role="tooltip" class="tooltip">
                    {item.label}
                  </div>
              </a>
            </MenuItem>
          {/each}
        </Menu>
      </Group>
      
      {#if isDev}
        <Group class="pb-[1rem] pt-[0.5rem]">
          <GroupLabel class="sidebar-group-label px-[0.75rem] py-[0.5rem] text-[0.75rem] font-[700] uppercase text-[hsl(var(--sidebar-foreground)/0.7)]">
            Developer Tools
          </GroupLabel>
          <Menu class="space-y-[0.75rem]" {collapsed}>
            {#each devNavItems as item (item.label)}
              <MenuItem {collapsed}>
                <a 
                  href={item.url} 
                  class="menu-item-link"
                  class:active={item.active}
                  data-sveltekit-preload-data="hover"
                  onclick={handleMenuItemClick}
                  title={collapsed ? item.label : ''}
                >
                  <div class="menu-item-icon">
                    {#if item.icon}
                      <Icon icon={item.icon} size={20} color="currentColor" />
                    {/if}
                  </div>
                  <span class="menu-item-label font-[600]">{item.label}</span>
                  <div role="tooltip" class="tooltip">
                      {item.label}
                    </div>
                </a>
              </MenuItem>
            {/each}
          </Menu>
        </Group>
      {/if}
      
      <Separator class="my-[0.75rem] h-px bg-[hsl(var(--sidebar-border)/0.8)]" />

      <Group class="recent-digests pb-[1rem]">
        <GroupLabel class="sidebar-group-label recent-digests-label px-[0.75rem] py-[0.5rem] text-[0.75rem] font-[700] uppercase text-[hsl(var(--sidebar-foreground)/0.7)]">
          Recent Digests
        </GroupLabel>
        <GroupContent class="space-y-[0.75rem] sidebar-content-collapsible">
          <Menu class="space-y-[0.75rem]" {collapsed}>
            {#each ['Tech Digest', 'Finance Update', 'Health News'] as digest}
              <MenuItem class="sidebar-menu-item" {collapsed}>
                <a 
                  href={`/digest/${digest.toLowerCase().replace(/\s+/g, '-')}`} 
                  class="menu-item-hover flex w-full items-center justify-start py-[0.625rem] text-[0.875rem]"
                  data-sveltekit-preload-data="hover"
                  onclick={handleMenuItemClick}
                >
                  <span class="font-[600]">{digest}</span>
                </a>
              </MenuItem>
            {/each}
          </Menu>
        </GroupContent>
      </Group>
    </Content>
    
    <Footer class="mt-auto border-t border-[hsl(var(--sidebar-border)/0.8)] px-4 py-4">
      <div class="relative">
        {#if user}
          <button
            class="avatar-container flex w-full items-center rounded-md p-2 text-left transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.2)]"
            class:justify-center={collapsed}
            onclick={toggleAvatarDropdown}
            aria-haspopup="true"
            aria-expanded={isAvatarDropdownOpen}
          >
            <div class="avatar">
              <img 
                src={user.avatarUrl || 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%%22 height=%22100%%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%225%22/%3E%3Cpath d=%22M20 21a8 8 0 0 0-16 0%22/%3E%3C/svg%3E'} 
                alt={user.displayName || 'User Avatar'} 
                onerror={handleImageError} 
                class="h-full w-full object-cover" 
              />
            </div>
            <div class="avatar-text-content ml-2 flex-grow overflow-hidden">
              <div class="truncate font-semibold">{user.displayName || 'User Name'}</div>
              <div class="truncate text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.plan || 'Plan'}</div>
            </div>
            <div class="avatar-chevron ml-auto">
              <Icon icon={ChevronDown} size={16} class={`transition-transform duration-200 ${isAvatarDropdownOpen ? 'rotate-180' : ''}`} />
            </div>
          </button>
        {:else}
          <a href="/login" class="flex w-full items-center justify-center rounded-md p-2 text-left transition-colors duration-200 hover:bg-[hsl(var(--muted)/0.2)]">
             Sign In
          </a>
        {/if}
        
        {#if isAvatarDropdownOpen && user}
          <div
            class="avatar-dropdown fixed z-[var(--z-dropdown)] w-64 max-h-[calc(100vh-7.5rem)] overflow-y-auto rounded-md border border-[hsl(var(--border))] bg-[hsl(var(--background))] p-2 shadow-lg animate-fadeIn"
            bind:this={avatarDropdownElement}
          >
            <div class="border-b border-[hsl(var(--border))] p-[0.5rem] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="font-semibold">{user.displayName || 'User Name'}</div>
              <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.email || 'user@example.com'}</div>
              <div class="mt-[0.25rem] text-[0.75rem] font-[500] text-[hsl(var(--primary))]">{user.plan || 'Plan'}</div>
            </div>
            
            <div class="py-[0.25rem]">
              <a href="/profile" class="dropdown-item">
                <span class="sidebar-icon"><Icon icon={User} size={16} /></span>
                <span>Profile</span>
              </a>
              <a href="/notifications" class="dropdown-item">
                <span class="sidebar-icon"><Icon icon={Bell} size={16} /></span>
                <span>Notifications</span>
              </a>
              <a href="/billing" class="dropdown-item">
                <span class="sidebar-icon"><Icon icon={CreditCardIcon} size={16} /></span>
                <span>Billing</span>
              </a>
              <a href="/settings" class="dropdown-item">
                <span class="sidebar-icon"><Icon icon={Settings} size={16} /></span>
                <span>Settings</span>
              </a>
              <a href="/logout" class="dropdown-item">
                <span class="sidebar-icon"><Icon icon={LogOut} size={16} /></span>
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
              <div class="flex items-center justify-between">
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