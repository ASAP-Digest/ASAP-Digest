<script>
  import { cn } from "$lib/utils";
  
  /**
   * Properties for the sidebar header
   * @typedef {Object} SidebarHeaderProps
   * @property {string} [class] - Additional CSS classes
   */
  
  /** @type {SidebarHeaderProps} */
  let {
    class: className = ""
  } = $props();
</script>

<div
  class={cn(
    "flex h-14 items-center border-b border-[hsl(var(--sidebar-border))] px-4",
    className
  )}
  data-testid="sidebar-header"
>
  {@render $$slots.default({})}
</div> 