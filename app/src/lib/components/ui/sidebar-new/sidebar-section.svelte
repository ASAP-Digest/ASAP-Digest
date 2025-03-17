<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar section component properties
   * @typedef {Object} SidebarSectionProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {string} [title] - Optional section title
   * @property {(props: any) => any} [children] - Children content to render
   */
  
  /** @type {SidebarSectionProps} */
  let {
    class: className = "",
    title = "",
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

<div class={cn("pb-4", className)} data-testid="sidebar-section">
  {#if title}
    <h3 class="mb-2 px-4 text-sm font-semibold text-[hsl(var(--sidebar-foreground))]">
      {title}
    </h3>
  {/if}
  {@render renderChildren()}
</div> 