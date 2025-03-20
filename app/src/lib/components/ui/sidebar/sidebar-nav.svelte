<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar navigation component properties
   * @typedef {Object} SidebarNavProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {Function} [children] - Child content to render
   */
  
  /** @type {SidebarNavProps} */
  let {
    class: className = "",
    children
  } = $props();
</script>

<nav 
  class={cn("grid gap-2 px-4 py-2 transition-all duration-200", className)} 
  data-testid="sidebar-nav"
>
  {@render children?.()}
</nav> 