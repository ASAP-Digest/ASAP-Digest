<!-- Link.svelte - Atomic link component -->
<script>
  import { cn } from "$lib/utils";
  import Icon from '$lib/components/ui/Icon.svelte';
  import { ExternalLink } from '$lib/utils/lucide-icons.js';

  /** @typedef {'default' | 'underlined' | 'muted' | 'heading' | 'text'} LinkVariant */
  
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
        return "text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.9)] hover:shadow-md";
      case 'underlined':
        return "text-[hsl(var(--primary))] underline underline-offset-4 hover:decoration-[hsl(var(--link-hover))]";
      case 'muted':
        return "text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--link-hover))]";
      case 'heading':
        return "text-[hsl(var(--foreground))] font-medium hover:text-[hsl(var(--primary))] block text-lg";
      case 'text':
        return "text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.8)]";
      default:
        return "text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.9)]";
    }
  }
  
  // Base classes for all links
  const baseClass = "transition-all duration-200 no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-2";
  
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
    <Icon icon={ExternalLink} class="inline-block ml-1" size={14} color="currentColor" />
  {/if}
</a> 