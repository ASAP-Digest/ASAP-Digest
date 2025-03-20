<script>
  import { cn } from "$lib/utils";
  import { ChevronLeft, ChevronRight } from "$lib/utils/lucide-icons";
  
  /**
   * Sidebar toggle component properties
   * @typedef {Object} SidebarToggleProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {boolean} [expanded=false] - Whether the sidebar is expanded
   * @property {() => void} [onToggle] - Toggle callback function
   */
  
  /** @type {SidebarToggleProps} */
  let {
    class: className = "",
    expanded = false,
    onToggle = () => {}
  } = $props();
  
  /**
   * Handle toggle click event
   */
  function handleToggle() {
    onToggle();
  }

  /**
   * @typedef {Object} IconObject
   * @property {string} name - The icon name
   * @property {string} svgContent - The SVG content as a string
   */

  /**
   * Render the SVG icon based on its content
   * @param {IconObject} icon - Icon object with svgContent
   * @param {string} className - CSS class for the icon
   * @returns {string} SVG HTML string
   */
  function renderIcon(icon, className) {
    return `<svg xmlns="http://www.w3.org/2000/svg" 
      width="24" height="24" 
      viewBox="0 0 24 24" 
      fill="none" 
      stroke="currentColor" 
      stroke-width="2" 
      stroke-linecap="round" 
      stroke-linejoin="round" 
      class="${className}">${icon.svgContent}</svg>`;
  }
</script>

<button
  type="button"
  onclick={handleToggle}
  class={cn(
    "flex h-6 w-6 items-center justify-center rounded-md text-[hsl(var(--sidebar-foreground))]",
    "hover:bg-[hsl(var(--sidebar-accent)/0.5)] hover:text-[hsl(var(--sidebar-accent-foreground))]",
    "focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring",
    className
  )}
  aria-label={expanded ? "Collapse sidebar" : "Expand sidebar"}
  data-testid="sidebar-toggle"
>
  {#if expanded}
    {@html renderIcon(ChevronLeft, "h-4 w-4")}
  {:else}
    {@html renderIcon(ChevronRight, "h-4 w-4")}
  {/if}
</button> 