<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar component properties
   * @typedef {Object} SidebarProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {boolean} [open=false] - Whether the sidebar is expanded or collapsed
   * @property {(props: any) => any} [children] - Children content to render
   */
  
  /** @type {SidebarProps} */
  let {
    class: className = "",
    open = false,
    children = undefined
  } = $props();
  
  /**
   * Renders the children content or an empty string if not provided
   * @returns {any} Rendered content
   */
  function renderChildren() {
    if (typeof children === 'function') {
      return children({});
    }
    return '';
  }
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
  {@render renderChildren()}
</aside> 