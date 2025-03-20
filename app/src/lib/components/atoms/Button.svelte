<!-- Button.svelte - Atomic button component -->
<script>
  import { cn } from "$lib/utils";

  /** @typedef {'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive'} ButtonVariant */
  /** @typedef {'sm' | 'md' | 'lg'} ButtonSize */
  
  /** @type {ButtonVariant} [variant="primary"] - Button variant */
  let { variant = 'primary' } = $props();
  
  /** @type {ButtonSize} [size="md"] - Button size */
  let { size = 'md' } = $props();
  
  /** @type {boolean} [disabled=false] - Whether the button is disabled */
  let { disabled = false } = $props();
  
  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
  
  /** @type {string} [type="button"] - Button type attribute */
  let { type = "button" } = $props();
  
  // Computed classes based on props
  let classes = $derived(() => {
    return {
      base: "inline-flex items-center justify-center rounded-[var(--radius-md)] transition-all duration-[var(--duration-normal)]",
      variant: getVariantClasses(),
      size: getSizeClasses(),
      state: disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer"
    };
  });
  
  /** Get the appropriate classes for the selected variant */
  function getVariantClasses() {
    switch(variant) {
      case 'primary':
        return "bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] hover:bg-[hsl(var(--primary)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--primary))]";
      case 'secondary':
        return "bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))] hover:bg-[hsl(var(--secondary)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--secondary))]";
      case 'outline':
        return "bg-transparent border border-[hsl(var(--primary))] text-[hsl(var(--primary))] hover:bg-[hsl(var(--primary)/0.1)] hover:shadow-[var(--glow-sm)_hsl(var(--primary))]";
      case 'ghost':
        return "bg-transparent text-[hsl(var(--foreground))] hover:bg-[hsl(var(--foreground)/0.1)]";
      case 'destructive':
        return "bg-[hsl(var(--destructive))] text-[hsl(var(--destructive-foreground))] hover:bg-[hsl(var(--destructive)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--destructive))]";
      default:
        return "bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] hover:bg-[hsl(var(--primary)/0.9)]";
    }
  }
  
  /** Get the appropriate classes for the selected size */
  function getSizeClasses() {
    switch(size) {
      case 'sm':
        return "text-[var(--font-size-sm)] px-[calc(var(--spacing-unit)*3)] py-[calc(var(--spacing-unit)*1.5)]";
      case 'md':
        return "text-[var(--font-size-base)] px-[calc(var(--spacing-unit)*4)] py-[calc(var(--spacing-unit)*2)]";
      case 'lg':
        return "text-[var(--font-size-lg)] px-[calc(var(--spacing-unit)*6)] py-[calc(var(--spacing-unit)*3)]";
      default:
        return "text-[var(--font-size-base)] px-[calc(var(--spacing-unit)*4)] py-[calc(var(--spacing-unit)*2)]";
    }
  }
</script>

<button 
  class="{cn(classes.base, classes.variant, classes.size, classes.state, className)}"
  {disabled}
  {type}
  on:click
  {...$$restProps}
>
  <slot />
</button> 