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
  
  // Accept collapsed state AND toggle function as props
  let {
    collapsed = false,
    toggleSidebar = () => { console.error('toggleSidebar prop not provided to MainSidebar'); },
    isMobile = false, // Receive isMobile prop
    closeMobileMenu = () => {} // Receive closeMobileMenu prop
  } = $props();
  
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
    },
    {
      label: "Debug", // Add Debug item
      url: "#debug",
      icon: Bug,
      get active() { return debugActive }
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
		width: var(--sidebar-width-expanded); /* Use CSS variable */
		min-width: var(--sidebar-width-expanded);
		max-width: var(--sidebar-width-expanded);
		height: 100%;
		transition: width 0.3s var(--ease-out), min-width 0.3s var(--ease-out), max-width 0.3s var(--ease-out);
		overflow-x: hidden;
		overflow-y: hidden; /* Let internal content scroll */
		flex-shrink: 0;
		display: flex;
		flex-direction: column;
	}
	
	/* Collapsed state dimensions - Applied via class:collapsed */
	.sidebar-wrapper.collapsed {
		width: var(--sidebar-width-collapsed);
		min-width: var(--sidebar-width-collapsed);
		max-width: var(--sidebar-width-collapsed);
	}

	/* Target Shadcn Root component when wrapper is collapsed */
	.sidebar-wrapper.collapsed :global([data-sidebar="sidebar"]) {
		width: var(--sidebar-width-collapsed);
		min-width: var(--sidebar-width-collapsed);
		max-width: var(--sidebar-width-collapsed);
		box-sizing: border-box;
		overflow-x: hidden;
	}
	
	/* Fix menu items when collapsed */
	.sidebar-wrapper.collapsed :global([data-sidebar="menu"] li) {
		width: 100%;
		display: flex;
		justify-content: center;
		align-items: center;
	}

	.sidebar-wrapper.collapsed :global([data-sidebar="menu"] li a) {
		width: 100%;
		padding: 0.75rem 0; /* 12px */
		justify-content: center;
		align-items: center;
	}
	
	/* Hide collapsible content in collapsed state */
	.sidebar-wrapper.collapsed .sidebar-content-collapsible {
		display: none;
		/* Use more robust hiding techniques */
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
	
	/* Base sidebar icon styles */
	.sidebar-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 1.5rem; /* 24px */
		height: 1.5rem; /* 24px */
		margin-right: 0.75rem; /* 12px */
		flex-shrink: 0;
		position: relative;
		transition: margin 0.3s ease-in-out;
	}
	
	/* Icon SVG sizing */
	.sidebar-icon :global(svg) { /* Target SVG inside */
		width: 1.25rem; /* 20px */
		height: 1.25rem; /* 20px */
		flex-shrink: 0;
		transition: transform 0.3s ease-in-out;
	}
	
	/* Collapsed state icon styling */
	.sidebar-wrapper.collapsed .sidebar-icon {
		margin: 0 auto; /* Center horizontally */
		padding: 0;
		width: 1.5rem; /* 24px */
		height: 1.5rem; /* 24px */
		display: flex;
		align-items: center;
		justify-content: center;
		position: relative;
		flex-shrink: 0;
	}

	.sidebar-wrapper.collapsed .sidebar-icon :global(svg) { /* Target SVG inside */
		width: 1.25rem; /* 20px */
		height: 1.25rem; /* 20px */
		flex-shrink: 0;
	}
	
	/* Sidebar toggle button */
	.sidebar-toggle {
		position: absolute !important; /* Keep !important if needed to override shadcn */
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
		z-index: var(--z-sidebar-trigger); /* Ensure it's above sidebar content */
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
	
	/* Container padding in collapsed state */
	.sidebar-wrapper.collapsed :global([data-sidebar="header"]),
	.sidebar-wrapper.collapsed :global([data-sidebar="content"]),
	.sidebar-wrapper.collapsed :global([data-sidebar="footer"]) {
		padding-left: 0.5rem; /* 8px */
		padding-right: 0.5rem; /* 8px */
		width: var(--sidebar-width-collapsed);
	}

	.sidebar-wrapper.collapsed :global([data-sidebar="content"]) {
		padding-top: 0; /* Adjust as needed */
		padding-bottom: 0;
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
		border-radius: 0.375rem; /* 6px */
	}
	
	/* Avatar container */
	.avatar-container {
		display: flex;
		align-items: center;
		padding: 0.5rem; /* 8px */
		cursor: pointer;
		border-radius: 0.375rem; /* 6px */
		transition: background-color 0.2s ease-in-out, padding 0.3s ease-in-out;
		width: 100%;
	}
	
	.sidebar-wrapper.collapsed .avatar-container {
		padding: 0.5rem 0; /* 8px */
		justify-content: center;
		width: 100%;
	}
	
	.avatar-container:hover {
		background-color: hsl(var(--muted)/0.2);
	}
	
	/* Dropdown menu */
	.avatar-dropdown {
		position: fixed; /* Keep fixed for positioning relative to viewport */
		width: 16rem; /* Tailwind w-64 */
		background-color: hsl(var(--background));
		border: 1px solid hsl(var(--border));
		border-radius: 0.375rem; /* Tailwind rounded-md */
		box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1); /* Tailwind shadow-lg */
		padding: 0.5rem; /* Tailwind p-2 */
		max-height: calc(100vh - 7.5rem); /* Uses rem */
		overflow-y: auto;
		z-index: var(--z-dropdown); /* Use z-index variable */
		animation: fadeIn 0.2s ease-out;
	}
	
	@keyframes fadeIn {
		from { opacity: 0; transform: translateY(-0.3125rem); } /* -5px */
		to { opacity: 1; transform: translateY(0); }
	}
	
	/* Hide group label in collapsed state */
	.sidebar-wrapper.collapsed .sidebar-group-label {
		display: none;
	}
	
	/* Hide recent digests in collapsed state */
	.sidebar-wrapper.collapsed .recent-digests {
		display: none;
	}
	
	/* Menu item styling */
	.sidebar-menu-item a { /* Target link within menu item */
		display: flex;
		align-items: center;
		width: 100%;
		padding: 0.5rem 0.75rem; /* Adjust padding */
		border-radius: 0.375rem; /* 6px */
		transition: all 0.3s ease-in-out;
		text-decoration: none;
		gap: 0.75rem; /* Add gap for spacing */
		white-space: nowrap;
	}

	/* Ensure text span doesn't shrink */
	.sidebar-menu-item a span.sidebar-content-collapsible {
		flex-shrink: 1; /* Allow text to shrink if needed, but prefer not to wrap */
		overflow: hidden; /* Hide overflow if it does shrink */
		text-overflow: ellipsis; /* Add ellipsis if text overflows */
	}

	/* Menu hover/active effects */
	.menu-item-hover:hover,
	.sidebar-menu-item a:hover {
		background-color: hsl(var(--muted)/0.3);
	}
	
	.sidebar-menu-item a.active {
		background-color: hsl(var(--primary)/0.1);
		color: hsl(var(--primary));
		font-weight: 600;
	}
	/* Ensure active icon color matches text */
	.sidebar-menu-item a.active .sidebar-icon svg {
		color: hsl(var(--primary));
	}

	
	/* Collapsed state menu item styling */
	.sidebar-wrapper.collapsed .sidebar-menu-item a {
		justify-content: center;
		padding: 0.75rem 0; /* Adjust vertical padding */
		gap: 0;
		width: 100%;
		display: flex;
		align-items: center;
	}

	/* Hide spans (text labels) in collapsed mode - More specific */
	.sidebar-wrapper.collapsed .sidebar-menu-item a span:not(.sidebar-icon span) {
		display: none;
	}
	/* Ensure the tooltip span itself is not hidden if it's nested differently */
	.sidebar-wrapper.collapsed .sidebar-menu-item a .sidebar-content-collapsible {
		display: none;
	}

	
	/* List item styling */
	.sidebar-wrapper.collapsed :global(ul),
	.sidebar-wrapper.collapsed :global(li) {
		margin: 0;
		padding: 0;
		width: 100%;
		display: flex;
		justify-content: center;
		align-items: center;
	}
	
	/* Dropdown item styling */
	.dropdown-item {
		display: flex;
		align-items: center;
		gap: 0.5rem; /* 8px */
		padding: 0.5rem; /* 8px */
		border-radius: 0.375rem; /* 6px */
		transition: background-color 0.2s;
		font-size: 0.875rem; /* 14px */
		width: 100%;
	}
	
	.dropdown-item:hover {
		background-color: hsl(var(--muted)/0.3);
	}
	
	/* Upgrade button */
	.upgrade-button {
		display: block;
		width: 100%;
		padding: 0.5rem; /* 8px */
		text-align: center;
		background-color: hsl(var(--primary));
		color: hsl(var(--primary-foreground));
		border-radius: 0.375rem; /* 6px */
		font-size: 0.875rem; /* 14px */
		font-weight: 500;
		margin-top: 0.5rem; /* 8px */
		transition: opacity 0.2s;
	}
	
	.upgrade-button:hover {
		opacity: 0.9;
	}
	
	/* Force svg visibility in collapsed mode - Ensure icons are always visible */
	.sidebar-wrapper.collapsed .sidebar-icon :global(svg) {
		display: block;
		visibility: visible;
		opacity: 1;
	}

	/* Tooltip Styling */
	.tooltip {
		position: absolute;
		left: 100%; /* Position to the right of the item */
		top: 50%;
		transform: translateY(-50%);
		margin-left: 0.75rem; /* Space from icon */
		background-color: hsl(var(--popover));
		color: hsl(var(--popover-foreground));
		padding: 0.25rem 0.5rem; /* Smaller padding */
		border-radius: var(--radius-md);
		font-size: var(--font-size-xs); /* Smaller font */
		font-weight: var(--font-weight-medium);
		white-space: nowrap;
		z-index: var(--z-sidebar-tooltip);
		opacity: 0; /* Hidden by default */
		visibility: hidden;
		transition: opacity 0.15s ease-out, visibility 0.15s ease-out;
		pointer-events: none; /* Prevent tooltip from blocking hover */
	}

	/* Show tooltip on hover of the parent link when sidebar is collapsed */
	.sidebar-wrapper.collapsed .sidebar-menu-item a:hover .tooltip {
		opacity: 1;
		visibility: visible;
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
        class="sidebar-toggle"
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
    
    <Content class="flex-grow overflow-y-auto" {collapsed}>
      <Group class="pb-[1rem] pt-[1rem]">
        <Menu class="space-y-[0.75rem]" {collapsed}>
          {#each mainNavItems as item (item.label)}
            <MenuItem class="sidebar-menu-item" {collapsed}>
              <a 
                href={item.url} 
                class="relative menu-item-hover"
                class:active={item.active}
                data-sveltekit-preload-data="hover"
                onclick={handleMenuItemClick}
              >
                <div class="sidebar-icon">
                  {#if item.icon}
                    <Icon icon={item.icon} size={20} color="currentColor" />
                  {/if}
                </div>
                <span class="sidebar-content-collapsible font-[600]">{item.label}</span>

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
              <MenuItem class="sidebar-menu-item" {collapsed}>
                <a 
                  href={item.url} 
                  class="relative menu-item-hover"
                  class:active={item.active}
                  data-sveltekit-preload-data="hover"
                  onclick={handleMenuItemClick}
                >
                  <div class="sidebar-icon">
                    {#if item.icon}
                      <Icon icon={item.icon} size={20} color="currentColor" />
                    {/if}
                  </div>
                  <span class="sidebar-content-collapsible font-[600]">{item.label}</span>
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
        <GroupLabel class="sidebar-group-label px-[0.75rem] py-[0.5rem] text-[0.75rem] font-[700] uppercase text-[hsl(var(--sidebar-foreground)/0.7)]">
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
    
    <Footer class="mt-auto border-t border-[hsl(var(--sidebar-border)/0.8)] px-[1rem] py-[1rem]">
      <div class="relative">
        <button class="avatar-container w-full text-left" onclick={toggleAvatarDropdown} aria-haspopup="true" aria-expanded={isAvatarDropdownOpen}>
          <div class="avatar">
            <img src={user.avatar} alt={user.name} onerror={handleImageError} class="h-full w-full object-cover" />
          </div>
          <div class="sidebar-content-collapsible ml-[0.5rem]">
            <div class="font-semibold">{user.name}</div>
            <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.plan}</div>
          </div>
          <div class="sidebar-content-collapsible ml-auto">
            <Icon icon={ChevronDown} size={16} class={`transition-transform duration-200 ${isAvatarDropdownOpen ? 'rotate-180' : ''}`} />
          </div>
        </button>
        
        {#if isAvatarDropdownOpen}
          <div class="avatar-dropdown" bind:this={avatarDropdownElement}>
            <div class="p-[0.5rem] border-b border-[hsl(var(--border))] dark:border-[hsl(var(--muted-foreground)/0.2)]">
              <div class="font-semibold">{user.name}</div>
              <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] dark:text-[hsl(var(--muted-foreground)/0.8)]">{user.email}</div>
              <div class="text-[0.75rem] font-[500] mt-[0.25rem] text-[hsl(var(--primary))]">{user.plan}</div>
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