<script>
  import { cn } from "$lib/utils";
  
  /**
   * Properties for the sidebar navigation
   * @typedef {Object} SidebarNavProps
   * @property {string} [class] - Additional CSS classes
   */
  
  /** @type {SidebarNavProps} */
  let {
    class: className = ""
  } = $props();
</script>

<nav
  class={cn(
    "grid gap-1 px-2",
    className
  )}
  data-testid="sidebar-nav"
>
  {@render $$slots.default({})}
</nav> 