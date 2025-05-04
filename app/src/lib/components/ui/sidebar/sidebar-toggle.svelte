<script>
  import { cn } from "$lib/utils";
  import { ChevronLeft, ChevronRight } from "$lib/utils/lucide-compat.js";
  import Icon from "$lib/components/ui/icon/icon.svelte";
  
  /**
   * Sidebar toggle component properties
   * @typedef {Object} SidebarToggleProps
   * @property {string} [class] - Additional CSS classes for styling
   * @property {boolean} [expanded=false] - Whether the sidebar is expanded
   * @property {() => void} [onToggle] - Toggle callback function
   */
  
  /** @type {SidebarToggleProps} */
  let {
    class: className = "",
    expanded = false,
    onToggle = () => {}
  } = $props();
  
  /**
   * Handle toggle click event
   */
  function handleToggle() {
    onToggle();
  }
</script>

<button
  type="button"
  onclick={handleToggle}
  class={cn(
    "flex h-6 w-6 items-center justify-center rounded-md text-[hsl(var(--sidebar-foreground))]",
    "hover:bg-[hsl(var(--sidebar-accent)/0.5)] hover:text-[hsl(var(--sidebar-accent-foreground))]",
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-1",
    "transition-colors duration-[var(--duration-normal)] ease-[var(--ease-out)]",
    className
  )}
  aria-label={expanded ? "Collapse sidebar" : "Expand sidebar"}
  data-testid="sidebar-toggle"
>
  {#if expanded}
    <Icon icon={ChevronLeft} class="h-4 w-4" />
  {:else}
    <Icon icon={ChevronRight} class="h-4 w-4" />
  {/if}
</button> 