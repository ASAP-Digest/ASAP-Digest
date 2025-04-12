<!-- Typography.svelte - Atomic typography component -->
<script>
  import { cn } from "$lib/utils";
  /** @typedef {import('svelte/elements').HTMLAttributes<HTMLElement>} HTMLAttributes */
  /** @typedef {import('svelte').Snippet} Snippet */

  /** @typedef {'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'caption' | 'blockquote' | 'code'} TypographyVariant */

  // Destructure props, make children optional, add textContent
  let { 
    variant = /** @type {TypographyVariant} */ ('p'), 
    className = "", 
    bold = false, 
    italic = false, 
    color = /** @type {string | null} */ (null),
    children = /** @type {Snippet | undefined} */ (undefined), // Optional snippet prop
    textContent = "" // Add optional textContent prop
  } = $props();

  // --- Restore Internal Logic --- 
  
  // Map variants to HTML tags
  /** @type {Record<string, string>} */
  const variantToTag = {
    'caption': 'p', 'h1': 'h1', 'h2': 'h2', 'h3': 'h3', 'h4': 'h4', 'h5': 'h5', 'h6': 'h6',
    'p': 'p', 'blockquote': 'blockquote', 'code': 'code'
  };
  
  // Static CSS class mappings
  /** @type {Record<string, string>} */
  const variantClassMap = {
    'h1': "text-[var(--font-size-4xl)] font-[var(--font-weight-extrabold)] leading-[var(--line-height-tight)] tracking-[var(--tracking-tighter)] mb-[calc(var(--spacing-unit)*8)]",
    'h2': "text-[var(--font-size-3xl)] font-[var(--font-weight-extrabold)] leading-[var(--line-height-tight)] tracking-[var(--tracking-tight)] mb-[calc(var(--spacing-unit)*6)]",
    'h3': "text-[var(--font-size-2xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-snug)] tracking-[var(--tracking-tight)] mb-[calc(var(--spacing-unit)*5)]",
    'h4': "text-[var(--font-size-xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-snug)] mb-[calc(var(--spacing-unit)*4)]",
    'h5': "text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] leading-[var(--line-height-normal)] mb-[calc(var(--spacing-unit)*3)]",
    'h6': "text-[var(--font-size-base)] font-[var(--font-weight-semibold)] leading-[var(--line-height-normal)] mb-[calc(var(--spacing-unit)*3)]",
    'p': "text-[var(--font-size-base)] leading-[var(--line-height-loose)] mb-[calc(var(--spacing-unit)*5)]",
    'caption': "text-[var(--font-size-sm)] leading-[var(--line-height-normal)] text-[hsl(var(--muted-foreground))] mb-[calc(var(--spacing-unit)*2)]",
    'blockquote': "pl-[calc(var(--spacing-unit)*6)] border-l-[4px] border-[hsl(var(--border))] italic text-[var(--font-size-lg)] mb-[calc(var(--spacing-unit)*5)]",
    'code': "font-mono bg-[hsl(var(--muted))] px-[calc(var(--spacing-unit)*1.5)] py-[calc(var(--spacing-unit)*0.8)] rounded-[var(--radius-sm)] text-[var(--font-size-sm)]"
  };

  // Compute the HTML tag to render
  let tag = $derived(variantToTag[variant] || 'p');

  // Compute static CSS class mappings
  let variantClasses = $derived(variantClassMap[variant] || variantClassMap.p);

  // Compute font modifiers
  let fontModifiers = $derived([
    bold ? "font-[var(--font-weight-bold)]" : "",
    italic ? "italic" : ""
  ].filter(Boolean).join(" "));

  // Compute color style
  let colorStyle = $derived(color ? `color: ${color};` : '');

  // Compute the final class list
  let computedClass = $derived(cn(
    variantClasses,
    fontModifiers,
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