<!-- Card.svelte - Atomic card component -->
<script>
  import { cn } from "$lib/utils";

  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
  
  /** @type {boolean} [hover=false] - Enable hover effects */
  let { hover = false } = $props();
  
  /** @type {boolean} [bordered=false] - Add border to card */
  let { bordered = false } = $props();
  
  /** @type {boolean} [shadow=true] - Add shadow to card */
  let { shadow = true } = $props();
  
  // Compute CSS classes for the card
  let cardClasses = $derived(() => {
    let classes = "bg-[hsl(var(--card))] text-[hsl(var(--card-foreground))] rounded-[var(--radius-lg)] overflow-hidden";
    
    if (bordered) classes += " border border-[hsl(var(--border))]";
    if (shadow) classes += " shadow-[var(--shadow-md)]";
    if (hover) classes += " transition-all duration-[var(--duration-normal)] hover:shadow-[var(--shadow-lg)] hover:translate-y-[-2px]";
    
    return classes;
  });
</script>

<div class="{cn(cardClasses, className)}" {...$$restProps}>
  <slot />
</div> 