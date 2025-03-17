<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar nav component properties
   * @typedef {Object} SidebarNavProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {(props: any) => any} [children] - Children content to render
   */
  
  /** @type {SidebarNavProps} */
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

<nav class={cn("grid gap-1 px-2", className)} data-testid="sidebar-nav">
  {@render renderChildren()}
</nav> 