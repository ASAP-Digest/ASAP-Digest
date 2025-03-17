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

<div 
  class={cn("mt-auto flex items-center border-t border-[hsl(var(--sidebar-border))] p-4", className)} 
  data-testid="sidebar-footer"
>
  {@render $$slots.default?.()}
</div> 