<!-- Typography.svelte - Atomic typography component -->
<script>
  import { cn } from "$lib/utils";
  /** @typedef {import('svelte/elements').HTMLAttributes<HTMLElement>} HTMLAttributes */
  /** @typedef {import('svelte').Snippet} Snippet */

  /** @typedef {'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'caption' | 'blockquote' | 'code'} TypographyVariant */

  /**
   * @typedef {Object} TypographyProps
   * @property {TypographyVariant} [variant='p'] - Typography variant
   * @property {string} [className] - Additional CSS classes
   * @property {boolean} [bold=false] - Whether the text is bold
   * @property {boolean} [italic=false] - Whether the text is italic
   * @property {string | null} [color=null] - Text color override
   * @property {Snippet | undefined} [children] - Child content
   * @property {string} [textContent] - Alternative text content
   */
  
  /** @type {TypographyProps} */
  let { 
    variant = /** @type {TypographyVariant} */ ('p'), 
    className = "", 
    bold = false, 
    italic = false, 
    color = /** @type {string | null} */ (null),
    children = /** @type {Snippet | undefined} */ (undefined), // Optional snippet prop
    textContent = "" // Add optional textContent prop
  } = $props();
  
  // Map variants to HTML tags
  /** @type {Record<string, string>} */
  const variantToTag = {
    'caption': 'p', 'h1': 'h1', 'h2': 'h2', 'h3': 'h3', 'h4': 'h4', 'h5': 'h5', 'h6': 'h6',
    'p': 'p', 'blockquote': 'blockquote', 'code': 'code'
  };
  
  // Static CSS class mappings updated for GRDSP
  /** @type {Record<string, string>} */
  const variantClassMap = {
    'h1': "text-[var(--font-size-xl)] font-[var(--font-weight-semibold)] leading-[var(--line-height-heading)] tracking-[var(--tracking-tight)] mb-6",
    'h2': "text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] leading-[var(--line-height-heading)] tracking-[var(--tracking-tight)] mb-5",
    'h3': "text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] leading-[var(--line-height-heading)] tracking-[var(--tracking-normal)] mb-4",
    'h4': "text-[var(--font-size-base)] font-[var(--font-weight-semibold)] leading-[var(--line-height-body)] mb-3",
    'h5': "text-[var(--font-size-base)] font-[var(--font-weight-semibold)] leading-[var(--line-height-body)] mb-2",
    'h6': "text-[var(--font-size-sm)] font-[var(--font-weight-semibold)] leading-[var(--line-height-body)] mb-2",
    'p': "text-[var(--font-size-base)] leading-[var(--line-height-body)] mb-4",
    'caption': "text-[var(--font-size-sm)] leading-[var(--line-height-body)] text-[hsl(var(--text-2))] mb-2",
    'blockquote': "pl-4 border-l-4 border-[hsl(var(--border))] italic text-[var(--font-size-base)] mb-4",
    'code': "font-mono bg-[hsl(var(--surface-2))] px-2 py-1 rounded-[var(--radius-sm)] text-[var(--font-size-sm)]"
  };

  // Compute the HTML tag to render
  let tag = $derived(variantToTag[variant] || 'p');

  // Compute static CSS class mappings
  let variantClasses = $derived(variantClassMap[variant] || variantClassMap.p);

  // Compute font modifiers
  let fontModifiers = $derived([
    bold ? "font-[var(--font-weight-semibold)]" : "",
    italic ? "italic" : ""
  ].filter(Boolean).join(" "));

  // Compute color style
  let colorStyle = $derived(color ? `color: ${color};` : '');

  // Compute the final class list
  let computedClass = $derived(cn(
    variantClasses,
    fontModifiers,
    "text-[hsl(var(--text-1))]", // Default text color from GRDSP
    className 
  ));

</script>

<!-- Render children snippet if provided, otherwise render textContent prop -->
<svelte:element this={tag} class={computedClass} style={colorStyle}>
  {#if children}
    {@render children()}
  {:else if textContent}
    {textContent}
  {/if}
</svelte:element>

<!-- Original Code:
<svelte:element this={tag} class={computedClass} style={colorStyle}>
  {@render children}
</svelte:element>
--> 