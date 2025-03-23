<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar section component properties
   * @typedef {Object} SidebarSectionProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {string} [title] - Section title
   * @property {Function} [children] - Child content to render
   */
  
  /** @type {SidebarSectionProps} */
  let {
    class: className = "",
    title = "",
    children
  } = $props();
</script>

<div class={cn("pb-4", className)} data-testid="sidebar-section">
  {#if title}
    <h3 class="mb-2 px-4 text-sm font-semibold text-[hsl(var(--sidebar-foreground)/0.85)] antialiased subpixel-antialiased">
      {title}
    </h3>
  {/if}
  {@render children?.()}
</div> 