<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar header component properties
   * @typedef {Object} SidebarHeaderProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {(props: any) => any} [children] - Children content to render
   */
  
  /** @type {SidebarHeaderProps} */
  let {
    class: className = "",
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

<div
  class={cn(
    "flex h-14 items-center border-b border-[hsl(var(--sidebar-border))] px-4",
    className
  )}
  data-testid="sidebar-header"
>
  {@render renderChildren()}
</div> 