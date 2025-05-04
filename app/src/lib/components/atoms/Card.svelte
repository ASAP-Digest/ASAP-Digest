<!-- Card.svelte - Atomic card component -->
<script>
  import { cn } from "$lib/utils";

  /** @typedef {import('svelte').Snippet} Snippet */

  let {
    className = "",
    hover = false,
    bordered = false,
    shadow = true,
    // children prop removed - replaced by named snippets
    header = /** @type {Snippet | undefined} */ (undefined),
    content = /** @type {Snippet | undefined} */ (undefined),
    footer = /** @type {Snippet | undefined} */ (undefined)
  } = $props();
  
  // Using simple concatenation instead of complex derived computation
  let cardClasses = $derived([
    "bg-[hsl(var(--surface-1))] text-[hsl(var(--text-1))] rounded-[var(--radius-lg)] overflow-hidden",
    bordered ? "border border-[hsl(var(--border))]" : "",
    shadow ? "shadow-[var(--shadow-md)]" : "",
    hover ? "transition-all duration-[var(--duration-normal)] hover:shadow-[var(--shadow-lg)] hover:translate-y-[-2px]" : ""
  ].filter(Boolean).join(" "));
</script>

<div class={cn(cardClasses, className)}>
  {#if header}
    {@render header()}
  {/if}
  {#if content}
    {@render content()}
  {/if}
  {#if footer}
    {@render footer()}
  {/if}
  <!-- <p>Card Content Placeholder</p> -->
</div> 