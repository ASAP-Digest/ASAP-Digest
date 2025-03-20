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
        return "bg-primary text-primary-foreground hover:bg-primary/90 hover:shadow-glow-sm";
      case 'secondary':
        return "bg-secondary text-secondary-foreground hover:bg-secondary/90 hover:shadow-glow-sm";
      case 'outline':
        return "bg-transparent border border-primary text-primary hover:bg-primary/10 hover:shadow-glow-sm";
      case 'ghost':
        return "bg-transparent text-foreground hover:bg-foreground/10";
      case 'destructive':
        return "bg-destructive text-destructive-foreground hover:bg-destructive/90 hover:shadow-glow-sm";
      default:
        return "bg-primary text-primary-foreground hover:bg-primary/90";
    }
  }
  
  /** 
   * Get the appropriate classes for the selected size
   * @returns {string} CSS classes for the size
   */
  function getSizeClasses() {
    switch(size) {
      case 'sm':
        return "text-sm px-3 py-1.5";
      case 'md':
        return "text-base px-4 py-2";
      case 'lg':
        return "text-lg px-6 py-3";
      default:
        return "text-base px-4 py-2";
    }
  }
  
  // Define base class
  const baseClass = "inline-flex items-center justify-center rounded-md transition-all duration-normal";
  
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