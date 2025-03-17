<script>
  import { cn } from "$lib/utils";
  
  /**
   * Properties for the sidebar
   * @typedef {Object} SidebarProps
   * @property {string} [class] - Additional CSS classes
   * @property {boolean} [open=false] - Whether the sidebar is open
   */
  
  /** @type {SidebarProps} */
  let {
    class: className = "",
    open = false
  } = $props();
</script>

<aside
  class={cn(
    "flex h-full flex-col overflow-hidden border-r border-[hsl(var(--sidebar-border))] bg-[hsl(var(--sidebar-background))] text-[hsl(var(--sidebar-foreground))]",
    open ? "w-[250px]" : "w-[50px]",
    className
  )}
  data-testid="sidebar"
  data-state={open ? "open" : "closed"}
>
  {@render $$slots.default({})}
</aside> 