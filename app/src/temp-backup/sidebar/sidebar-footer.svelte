<script>
  import { cn } from "$lib/utils";
  
  /**
   * Properties for the sidebar footer
   * @typedef {Object} SidebarFooterProps
   * @property {string} [class] - Additional CSS classes
   */
  
  /** @type {SidebarFooterProps} */
  let {
    class: className = ""
  } = $props();
</script>

<div class={cn("px-4 py-2", className)} data-testid="sidebar-footer">
  {@render $$slots.default({})}
</div> 