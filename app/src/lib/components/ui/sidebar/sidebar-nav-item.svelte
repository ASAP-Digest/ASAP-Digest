<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar navigation item component properties
   * @typedef {Object} SidebarNavItemProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {boolean} [active=false] - Whether the item is active
   * @property {boolean} [disabled=false] - Whether the item is disabled
   * @property {string} [href] - Link href
   * @property {Function} [children] - Child content to render with isHovered state
   */
  
  /** @type {SidebarNavItemProps} */
  let {
    class: className = "",
    active = false,
    disabled = false,
    href = undefined,
    children
  } = $props();
  
  let isHovered = $state(false);
  
  function handleMouseEnter() {
    isHovered = true;
  }
  
  function handleMouseLeave() {
    isHovered = false;
  }
</script>

<a
  {href}
  class={cn(
    "flex items-center rounded-md py-2 text-sm transition-colors antialiased subpixel-antialiased",
    "hover:bg-[hsl(var(--sidebar-accent)/0.15)] hover:text-[hsl(var(--sidebar-accent-foreground))]",
    "focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring",
    active && "bg-[hsl(var(--sidebar-accent)/0.15)] text-[hsl(var(--sidebar-accent-foreground))] font-medium",
    disabled && "pointer-events-none opacity-60",
    className
  )}
  aria-current={active ? "page" : null}
  onmouseenter={handleMouseEnter}
  onmouseleave={handleMouseLeave}
  data-state={active ? "active" : "inactive"}
  tabindex={disabled ? -1 : 0}
  data-testid="sidebar-nav-item"
>
  {@render children?.({isHovered})}
</a> 