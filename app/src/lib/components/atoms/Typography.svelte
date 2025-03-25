<!-- Typography.svelte - Atomic typography component -->
<script>
  import { cn } from "$lib/utils";

  /** @typedef {'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'caption' | 'blockquote' | 'code'} TypographyVariant */
  
  let {
    variant = /** @type {TypographyVariant} */ ('p'),
    className = "",
    bold = false,
    italic = false,
    color = /** @type {string|null} */ (null),
    children = /** @type {import('svelte').Snippet | undefined} */ (undefined)
  } = $props();
  
  // Map variants to HTML tags (defaults to variant name if not specified)
  /** @type {Record<string, string>} */
  const variantToTag = {
    'caption': 'p',
    'h1': 'h1',
    'h2': 'h2',
    'h3': 'h3',
    'h4': 'h4',
    'h5': 'h5',
    'h6': 'h6',
    'p': 'p',
    'blockquote': 'blockquote',
    'code': 'code'
  };
  
  // Compute the HTML tag to render
  let tag = $derived(variantToTag[variant] || 'p');
  
  // Compute static CSS class mappings rather than computing them at runtime
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
  
  // Simple direct lookup instead of computing via function
  let variantClasses = $derived(variantClassMap[variant] || variantClassMap.p);
  
  // Simplify font modifier computation
  let fontModifiers = $derived([
    bold ? "font-[var(--font-weight-bold)]" : "",
    italic ? "italic" : ""
  ].filter(Boolean).join(" "));
  
  // Simplify color style calculation
  let colorStyle = $derived(color ? `color: ${color};` : '');
</script>

<svelte:element
  this={tag}
  class={cn(variantClasses, fontModifiers, className)}
  style={colorStyle}
>
  {@render children?.()}
</svelte:element> 