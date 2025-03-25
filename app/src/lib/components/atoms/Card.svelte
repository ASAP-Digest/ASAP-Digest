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
  
  // Using simple concatenation instead of complex derived computation
  let cardClasses = $derived([
    "bg-[hsl(var(--card))] text-[hsl(var(--card-foreground))] rounded-[var(--radius-lg)] overflow-hidden",
    bordered ? "border border-[hsl(var(--border))]" : "",
    shadow ? "shadow-[var(--shadow-md)]" : "",
    hover ? "transition-all duration-[var(--duration-normal)] hover:shadow-[var(--shadow-lg)] hover:translate-y-[-2px)]" : ""
  ].filter(Boolean).join(" "));
</script>

<div class={cn(cardClasses, className)}>
  {@render children?.()}
</div> 