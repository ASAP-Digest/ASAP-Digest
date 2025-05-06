<!-- ButtonGroup.svelte - Atomic button group component -->
<script>
  import { cn } from "$lib/utils";
  
  /** @typedef {'default' | 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive'} ButtonVariant */
  /** @typedef {'sm' | 'md' | 'lg' | 'icon'} ButtonSize */
  
  let {
    variant = /** @type {ButtonVariant} */ ('default'),
    size = /** @type {ButtonSize} */ ('md'),
    tooltip = '',
    className = "",
    children = /** @type {import('svelte').Snippet | undefined} */ (undefined)
  } = $props();
  
  /**
   * Get the appropriate classes for the selected variant
   * @returns {string} CSS classes for the variant
   */
  function getVariantClasses() {
    switch(variant) {
      case 'primary':
        return "bg-[hsl(var(--brand))] text-[hsl(var(--brand-fg))] hover:bg-[hsl(var(--brand-hover))]";
      case 'secondary':
        return "bg-[hsl(var(--accent))] text-[hsl(var(--accent-fg))] hover:bg-[hsl(var(--accent-hover))]";
      case 'outline':
        return "bg-transparent border border-[hsl(var(--brand))] text-[hsl(var(--brand))] hover:bg-[hsl(var(--brand)/0.1)]";
      case 'ghost':
        return "bg-transparent text-[hsl(var(--text-1))] hover:bg-[hsl(var(--surface-2))]";
      case 'destructive':
        return "bg-[hsl(var(--functional-error))] text-[hsl(var(--functional-error-fg))] hover:bg-[hsl(var(--functional-error)/0.9)]";
      default:
        return "bg-transparent text-[hsl(var(--text-1))] hover:bg-[hsl(var(--surface-2))]";
    }
  }
  
  /**
   * Get the appropriate classes for the selected size
   * @returns {string} CSS classes for the size
   */
  function getSizeClasses() {
    switch(size) {
      case 'sm':
        return "p-1 text-[var(--font-size-sm)]"; // Small
      case 'md':
        return "p-2 text-[var(--font-size-base)]"; // Medium
      case 'lg':
        return "p-3 text-[var(--font-size-lg)]"; // Large
      case 'icon':
        return "p-1.5"; // Icon only
      default:
        return "p-2 text-[var(--font-size-base)]"; // Default to medium
    }
  }
  
  // Base classes
  const baseClass = "inline-flex items-center justify-center rounded-[var(--radius-md)] transition-all duration-[var(--duration-normal)] cursor-pointer";
  
  // Get variant and size classes
  let variantClass = $derived(getVariantClasses());
  let sizeClass = $derived(getSizeClasses());
</script>

<div 
  class={cn(baseClass, variantClass, sizeClass, className)}
  title={tooltip}
  role="group"
>
  {@render children?.()}
</div> 