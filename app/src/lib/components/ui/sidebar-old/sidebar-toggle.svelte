<script>
  import { cn } from "$lib/utils";
  import { ChevronLeft, ChevronRight } from "lucide-svelte";
  
  /**
   * Properties for the sidebar toggle
   * @typedef {Object} SidebarToggleProps
   * @property {string} [class] - Additional CSS classes
   * @property {boolean} [expanded=true] - Whether the sidebar is expanded
   * @property {boolean} [disabled=false] - Whether the toggle is disabled
   * @property {() => void} [onToggle] - Callback when toggle is clicked
   */
  
  /** @type {SidebarToggleProps} */
  let {
    class: className = "",
    expanded = true,
    disabled = false,
    onToggle = () => {}
  } = $props();
</script>

<button
  type="button"
  class={cn(
    "inline-flex h-10 w-10 items-center justify-center rounded-md border border-[hsl(var(--sidebar-border))] bg-[hsl(var(--sidebar-background))] text-[hsl(var(--sidebar-foreground))] hover:bg-[hsl(var(--sidebar-accent)/0.5)] hover:text-[hsl(var(--sidebar-accent-foreground))]",
    disabled && "opacity-50 cursor-not-allowed",
    className
  )}
  aria-label={expanded ? "Collapse sidebar" : "Expand sidebar"}
  disabled={disabled}
  onclick={onToggle}
  data-testid="sidebar-toggle"
>
  {#if expanded}
    <ChevronLeft class="h-4 w-4" />
  {:else}
    <ChevronRight class="h-4 w-4" />
  {/if}
</button> 