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
  let tag = $derived(/** @type {keyof HTMLElementTagNameMap} */ (variantToTag[variant] || 'p'));
  
  // Compute CSS classes for the typography variant
  let variantClasses = $derived((() => {
    switch(variant) {
      case 'h1':
        return "text-[var(--font-size-4xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] tracking-[var(--tracking-tighter)] mb-[calc(var(--spacing-unit)*6)]";
      case 'h2':
        return "text-[var(--font-size-3xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)] tracking-[var(--tracking-tight)] mb-[calc(var(--spacing-unit)*5)]";
      case 'h3':
        return "text-[var(--font-size-2xl)] font-[var(--font-weight-semibold)] leading-[var(--line-height-snug)] tracking-[var(--tracking-tight)] mb-[calc(var(--spacing-unit)*4)]";
      case 'h4':
        return "text-[var(--font-size-xl)] font-[var(--font-weight-semibold)] leading-[var(--line-height-snug)] mb-[calc(var(--spacing-unit)*3)]";
      case 'h5':
        return "text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] leading-[var(--line-height-normal)] mb-[calc(var(--spacing-unit)*2)]";
      case 'h6':
        return "text-[var(--font-size-base)] font-[var(--font-weight-semibold)] leading-[var(--line-height-normal)] mb-[calc(var(--spacing-unit)*2)]";
      case 'p':
        return "text-[var(--font-size-base)] leading-[var(--line-height-relaxed)] mb-[calc(var(--spacing-unit)*4)]";
      case 'caption':
        return "text-[var(--font-size-sm)] leading-[var(--line-height-normal)] text-[hsl(var(--muted-foreground))]";
      case 'blockquote':
        return "pl-[calc(var(--spacing-unit)*4)] border-l-4 border-[hsl(var(--border))] italic text-[var(--font-size-lg)] mb-[calc(var(--spacing-unit)*4)]";
      case 'code':
        return "font-mono bg-[hsl(var(--muted))] px-[calc(var(--spacing-unit)*1)] py-[calc(var(--spacing-unit)*0.5)] rounded-[var(--radius-sm)] text-[var(--font-size-sm)]";
      default:
        return "text-[var(--font-size-base)] leading-[var(--line-height-relaxed)]";
    }
  })());
  
  // Apply font weight and style modifiers
  let fontModifiers = $derived((() => {
    let modifiers = "";
    if (bold) modifiers += " font-[var(--font-weight-bold)]";
    if (italic) modifiers += " italic";
    return modifiers;
  })());

  // Apply color if specified
  let colorStyle = $derived((() => {
    if (color) {
      return `color: ${color};`;
    }
    return '';
  })());
</script>

<svelte:element
  this={tag}
  class={cn(variantClasses, fontModifiers, className)}
  style={colorStyle}
>
  {@render children?.()}
</svelte:element> 