<script>
  import { cn } from "$lib/utils";
  
  /**
   * Sidebar section component properties
   * @typedef {Object} SidebarSectionProps
   * @property {string} [class] - Additional CSS classes for styling
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
    <h3 class="mb-2 px-4 text-sm font-semibold text-[hsl(var(--sidebar-foreground)/0.7)]">
      {title}
    </h3>
  {/if}
  <slot />
</div> 