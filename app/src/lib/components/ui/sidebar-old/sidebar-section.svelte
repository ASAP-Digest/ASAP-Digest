<script>
  import { cn } from "$lib/utils";
  
  /**
   * Properties for the sidebar section
   * @typedef {Object} SidebarSectionProps
   * @property {string} [class] - Additional CSS classes
   * @property {string} [title] - Section title
   */
  
  /** @type {SidebarSectionProps} */
  let {
    class: className = "",
    title = ""
  } = $props();
</script>

<div class={cn("pb-4", className)} data-testid="sidebar-section">
  {#if title}
    <h3 class="mb-2 px-4 text-sm font-semibold text-[hsl(var(--sidebar-foreground))]">
      {title}
    </h3>
  {/if}
  {@render $$slots.default({})}
</div> 