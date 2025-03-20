<!-- Typography.svelte - Atomic typography component -->
<script>
  import { cn } from "$lib/utils";

  /** @typedef {'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'caption' | 'blockquote' | 'code'} TypographyVariant */
  
  /** @type {TypographyVariant} [variant="p"] - Typography variant */
  let { variant = 'p' } = $props();
  
  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
  
  /** @type {boolean} [bold=false] - Whether to apply bold styling */
  let { bold = false } = $props();
  
  /** @type {boolean} [italic=false] - Whether to apply italic styling */
  let { italic = false } = $props();

  /** @type {string} [color=null] - Custom text color */
  let { color = null } = $props();
  
  // Map variants to HTML tags (defaults to variant name if not specified)
  const variantToTag = {
    'caption': 'p',
    // All other variants map to themselves by default
  };
  
  // Compute the HTML tag to render
  let tag = $derived(() => variantToTag[variant] || variant);
  
  // Compute CSS classes for the typography variant
  let variantClasses = $derived(() => {
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
  });
  
  // Apply font weight and style modifiers
  let fontModifiers = $derived(() => {
    let modifiers = "";
    if (bold) modifiers += " font-[var(--font-weight-bold)]";
    if (italic) modifiers += " italic";
    return modifiers;
  });

  // Apply color if specified
  let colorStyle = $derived(() => {
    if (color) {
      return `color: ${color};`;
    }
    return '';
  });
</script>

<svelte:element
  this={tag}
  class="{cn(variantClasses, fontModifiers, className)}"
  style={colorStyle}
  {...$$restProps}
>
  <slot />
</svelte:element> 