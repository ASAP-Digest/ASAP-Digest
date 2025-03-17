<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar nav item component properties
   * @typedef {Object} SidebarNavItemProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {boolean} [active=false] - Whether this item is active
   * @property {boolean} [disabled=false] - Whether this item is disabled
   * @property {string} [href=''] - Navigation URL
   * @property {(props: {isHovered: boolean}) => any} [children] - Children content to render with hover state
   */
  
  /** @type {SidebarNavItemProps} */
  let {
    class: className = "",
    active = false,
    disabled = false,
    href = "",
    children = undefined
  } = $props();
  
  // Track hover state for visual feedback
  let isHovered = $state(false);
  
  /**
   * Renders the children content with hover state or an empty string if not provided
   * @returns {any} Rendered content
   */
  function renderChildren() {
    if (typeof children === 'function') {
      return children({ isHovered });
    }
    return '';
  }
</script>

<a
  {href}
  class={cn(
    "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium",
    active 
      ? "bg-[hsl(var(--sidebar-accent))] text-[hsl(var(--sidebar-accent-foreground))]" 
      : "hover:bg-[hsl(var(--sidebar-accent)/0.5)] hover:text-[hsl(var(--sidebar-accent-foreground))]",
    disabled ? "pointer-events-none opacity-50" : "",
    className
  )}
  aria-current={active ? "page" : undefined}
  tabindex={disabled ? -1 : 0}
  aria-disabled={disabled ? "true" : undefined}
  data-testid="sidebar-nav-item"
  onmouseenter={() => isHovered = true}
  onmouseleave={() => isHovered = false}
>
  <span class="flex items-center gap-3">
    {@render renderChildren()}
  </span>
</a> 