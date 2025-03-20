<!-- Link.svelte - Atomic link component -->
<script>
  import { cn } from "$lib/utils";
  import Icon from '$lib/components/ui/Icon.svelte';
  import { ExternalLink } from '$lib/utils/lucide-icons.js';

  /** @typedef {'default' | 'underlined' | 'muted'} LinkVariant */
  
  let {
    href = '',
    variant = /** @type {LinkVariant} */ ('default'),
    external = false,
    className = "",
    id = "",
    title = "",
    ariaLabel = "",
    children = /** @type {import('svelte').Snippet | undefined} */ (undefined)
  } = $props();
  
  /**
   * Generate CSS classes based on the variant
   * @returns {string} CSS classes for the variant
   */
  function getVariantClasses() {
    switch(variant) {
      case 'default':
        return "text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--primary)/0.5)]";
      case 'underlined':
        return "text-[hsl(var(--primary))] underline underline-offset-4 hover:decoration-[hsl(var(--link-hover))]";
      case 'muted':
        return "text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--link-hover))]";
      default:
        return "text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.9)]";
    }
  }
  
  // Base classes for all links
  const baseClass = "transition-all duration-[var(--duration-normal)] no-underline focus-visible:outline-[2px] focus-visible:outline-[hsl(var(--ring))] focus-visible:outline-offset-2";
  
  // Get variant class based on variant prop using $derived for reactivity
  let variantClass = $derived(getVariantClasses());
</script>

<a 
  {href}
  class={cn(baseClass, variantClass, className)}
  target={external ? "_blank" : undefined}
  rel={external ? "noopener noreferrer" : undefined}
  {id}
  {title}
  aria-label={ariaLabel || title}
>
  {@render children?.()}
  {#if external}
    <Icon icon={ExternalLink} class="inline-block ml-[0.25rem]" size={14} color="currentColor" />
  {/if}
</a> 