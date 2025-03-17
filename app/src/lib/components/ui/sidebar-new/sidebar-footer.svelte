<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar footer component properties
   * @typedef {Object} SidebarFooterProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {(props: any) => any} [children] - Children content to render
   */
  
  /** @type {SidebarFooterProps} */
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
  class={cn("mt-auto flex items-center border-t border-[hsl(var(--sidebar-border))] p-4", className)}
  data-testid="sidebar-footer"
>
  {@render renderChildren()}
</div> 