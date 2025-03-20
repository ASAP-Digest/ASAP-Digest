<!-- Card.svelte - Atomic card component -->
<script>
  import { cn } from "$lib/utils";

  let {
    className = "",
    hover = false,
    bordered = false,
    shadow = true,
    children = /** @type {import('svelte').Snippet | undefined} */ (undefined)
  } = $props();
  
  // Compute CSS classes for the card
  let cardClasses = $derived((() => {
    let classes = "bg-[hsl(var(--card))] text-[hsl(var(--card-foreground))] rounded-[var(--radius-lg)] overflow-hidden";
    
    if (bordered) classes += " border border-[hsl(var(--border))]";
    if (shadow) classes += " shadow-[var(--shadow-md)]";
    if (hover) classes += " transition-all duration-[var(--duration-normal)] hover:shadow-[var(--shadow-lg)] hover:translate-y-[-2px]";
    
    return classes;
  })());
</script>

<div class={cn(cardClasses, className)}>
  {@render children?.()}
</div> 