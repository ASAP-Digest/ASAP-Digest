<script>
  import { cn } from "$lib/utils";
  
  /**
   * Properties for the sidebar content
   * @typedef {Object} SidebarContentProps
   * @property {string} [class] - Additional CSS classes
   */
  
  /** @type {SidebarContentProps} */
  let {
    class: className = ""
  } = $props();
</script>

<div class={cn("flex-1 overflow-auto", className)} data-testid="sidebar-content">
  {@render $$slots.default({})}
</div> 