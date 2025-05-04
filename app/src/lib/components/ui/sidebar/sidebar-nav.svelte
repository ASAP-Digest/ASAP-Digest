<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar navigation component properties
   * @typedef {Object} SidebarNavProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {import('svelte').Snippet} [children] - Child content to render
   */
  
  /** @type {SidebarNavProps} */
  let {
    class: className = "",
    children
  } = $props();
</script>

<nav 
  class={cn("grid gap-4 px-4 py-2 transition-all duration-[var(--duration-normal)] ease-[var(--ease-out)]", className)} 
  data-testid="sidebar-nav"
>
  {@render children?.()}
</nav> 