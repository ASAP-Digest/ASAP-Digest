<!-- Button.svelte - Atomic button component -->
<script>
  import { cn } from "$lib/utils";

  /** @typedef {'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive'} ButtonVariant */
  /** @typedef {'sm' | 'md' | 'lg'} ButtonSize */
  /** @typedef {'button' | 'submit' | 'reset'} ButtonType */
  
  let {
    variant = /** @type {ButtonVariant} */ ('primary'),
    size = /** @type {ButtonSize} */ ('md'),
    disabled = false,
    className = "",
    type = /** @type {ButtonType} */ ("button"),
    id = "",
    name = "",
    value = "",
    ariaLabel = "",
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
        return "bg-[hsl(var(--brand))] text-[hsl(var(--brand-fg))] hover:bg-[hsl(var(--brand-hover))]";
    }
  }
  
  /** 
   * Get the appropriate classes for the selected size
   * @returns {string} CSS classes for the size
   */
  function getSizeClasses() {
    switch(size) {
      case 'sm':
        return "text-[var(--font-size-sm)] px-2 py-1"; // 8px/4px (8pt grid)
      case 'md':
        return "text-[var(--font-size-base)] px-4 py-2"; // 16px/8px (8pt grid)
      case 'lg':
        return "text-[var(--font-size-lg)] px-6 py-3"; // 24px/12px (8pt grid)
      default:
        return "text-[var(--font-size-base)] px-4 py-2"; // 16px/8px (8pt grid)
    }
  }
  
  // Define base class
  const baseClass = "inline-flex items-center justify-center rounded-[var(--radius-md)] transition-all duration-[var(--duration-normal)]";
  
  // Define state class based on disabled prop
  let stateClass = $derived(disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer");
  
  // Get variant class based on variant prop
  let variantClass = $derived(getVariantClasses());
  
  // Get size class based on size prop
  let sizeClass = $derived(getSizeClasses());
</script>

<button 
  class={cn(baseClass, variantClass, sizeClass, stateClass, className)}
  {disabled}
  {type}
  {id}
  {name}
  {value}
  aria-label={ariaLabel}
>
  {@render children?.()}
</button> 