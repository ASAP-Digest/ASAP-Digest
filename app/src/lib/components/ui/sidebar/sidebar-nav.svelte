<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar navigation component properties
   * @typedef {Object} SidebarNavProps
   * @property {string} [class] - Additional CSS classes for styling
   */
  
  /** @type {SidebarNavProps} */
  let {
    class: className = ""
  } = $props();
</script>

<nav 
  class={cn("grid gap-1 px-2", className)} 
  data-testid="sidebar-nav"
>
  <slot />
</nav> 