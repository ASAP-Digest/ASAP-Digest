<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar content component properties
   * @typedef {Object} SidebarContentProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {(props: any) => any} [children] - Children content to render
   */
  
  /** @type {SidebarContentProps} */
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
  class={cn("flex-1 overflow-auto py-2", className)} 
  data-testid="sidebar-content"
>
  {@render renderChildren()}
</div> 