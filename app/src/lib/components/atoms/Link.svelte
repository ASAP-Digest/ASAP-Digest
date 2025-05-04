<!-- Link.svelte - Atomic link component -->
<script>
  import { cn } from "$lib/utils";
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { ExternalLink } from '$lib/utils/lucide-compat.js';

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
        return "text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))] transition-all duration-[var(--duration-fast)]";
      case 'underlined':
        return "text-[hsl(var(--link))] underline underline-offset-4 hover:text-[hsl(var(--link-hover))]";
      case 'muted':
        return "text-[hsl(var(--text-2))] hover:text-[hsl(var(--link-hover))]";
      case 'heading':
        return "text-[hsl(var(--text-1))] font-[var(--font-weight-semibold)] hover:text-[hsl(var(--link))] block text-[var(--font-size-lg)]";
      case 'text':
        return "text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))]";
      default:
        return "text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))]";
    }
  }
  
  // Base classes for all links
  const baseClass = "transition-all duration-[var(--duration-fast)] ease-[var(--ease-out)] no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-2";
  
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
    <Icon icon={ExternalLink} class="inline-block ml-2" size={14} />
  {/if}
</a> 