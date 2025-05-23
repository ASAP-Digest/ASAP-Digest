# Tailwind 4 Guidelines for ASAP Digest

⚠️ **IMPORTANT: ONLY USE TAILWIND 4 SYNTAX** ⚠️

## Core Rules

1. **NEVER** use direct color names (pre-defined color scales):
   - ❌ `text-red-500`, `bg-blue-400`, `border-gray-200`
   - ✅ `text-[#ef4444]`, `bg-[#60a5fa]`, `border-[#e5e7eb]`

2. **ALWAYS** use HSL variables for theme colors:
   - ❌ `bg-background`, `text-foreground`, `border-border`
   - ✅ `bg-[hsl(var(--background))]`, `text-[hsl(var(--foreground))]`, `border-[hsl(var(--border))]`

3. **ALWAYS** use arbitrary values for fixed sizes:
   - ❌ `h-1`, `w-4`, `mt-2`, `gap-3`
   - ✅ `h-[0.25rem]`, `w-[1rem]`, `mt-[0.5rem]`, `gap-[0.75rem]`

4. **ALERT**: Shadcn-svelte components may need additional fixes for Tailwind 4 compatibility.

## Common Issues Found in Our Codebase

The following issues have been found and fixed in our codebase:

1. **Direct color references in components**:
   - Buttons using `bg-primary` instead of `bg-[hsl(var(--primary))]`
   - Text using `text-gray-500` instead of `text-[#71717a]` or a theme variable
   - Border colors using direct names like `border-blue-300`

2. **Fixed utility classes without arbitrary values**:
   - Spacing utilities like `p-4`, `m-2`, `gap-3` need to use `p-[1rem]`, `m-[0.5rem]`, `gap-[0.75rem]`
   - Height/width utilities like `h-1`, `w-4` need to use `h-[0.25rem]`, `w-[1rem]`
   
3. **Semantic color names without HSL variables**:
   - UI components using `text-foreground` instead of `text-[hsl(var(--foreground))]`
   - Elements using `border-border` instead of `border-[hsl(var(--border))]`
   - Opacity variants like `bg-primary/10` instead of `bg-[hsl(var(--primary)/0.1)]`

## Critical Components to Check

These components have been updated but should be double-checked before deployment:

1. **UI Framework Components**:
   - Sheet components (sheet-content, sheet-title)
   - Card components (card-header, card-footer)
   - Button variants
   - Skeleton and tooltip components
   
2. **Widget Components**:
   - ArticleWidget
   - PodcastWidget
   - All similar content components

3. **Layout Components**:
   - Header
   - Footer
   - Navigation
   - Sidebars

## Common Conversions

### Color Utilities

| Tailwind 3 (Don't Use) | Tailwind 4 (Use Instead) |
|------------------------|--------------------------|
| `bg-primary` | `bg-[hsl(var(--primary))]` |
| `text-primary-foreground` | `text-[hsl(var(--primary-foreground))]` |
| `border-muted` | `border-[hsl(var(--muted))]` |
| `ring-ring` | `ring-[hsl(var(--ring))]` |
| `text-gray-500` | `text-[#71717a]` |
| `border-blue-300` | `border-[#93c5fd]` |

### Size Utilities

| Tailwind 3 (Don't Use) | Tailwind 4 (Use Instead) |
|------------------------|--------------------------|
| `h-1` | `h-[0.25rem]` |
| `w-2` | `w-[0.5rem]` |
| `p-3` | `p-[0.75rem]` |
| `m-4` | `m-[1rem]` |
| `gap-6` | `gap-[1.5rem]` |

## Arbitrary Value Syntax

Tailwind 4 uses arbitrary value syntax with square brackets for any value that isn't part of the predefined configuration:

```html
<!-- Example with HSL variables -->
<div class="bg-[hsl(var(--background))] text-[hsl(var(--foreground))]">
  <!-- content -->
</div>

<!-- Example with hex colors -->
<div class="bg-[#1e40af] text-[#ffffff]">
  <!-- content -->
</div>

<!-- Example with arbitrary sizes -->
<div class="w-[16.5rem] h-[42px] mt-[12px]">
  <!-- content -->
</div>
```

## Shadcn-svelte Components

Shadcn-svelte components designed for Tailwind 3 need updates to work with Tailwind 4:

1. Edit component `.svelte` files directly
2. Look for deprecated class names and replace with Tailwind 4 syntax
3. Update any `@apply` directives in component styles

## Build Error Prevention

Before pushing code:
1. Run a search for common Tailwind 3 patterns: `grep -r "(bg|text|border)-(primary|gray|red)" app/src/`
2. Check all UI components in the ui/ directory for compatibility
3. Run a test build and verify no CSS-related errors appear
4. Use the tailwindFixer.js utility to identify potential issues

## References

- [Tailwind CSS 4 Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide)
- [Shadcn UI Tailwind v4 Docs](https://ui.shadcn.com/docs/tailwind-v4)
- [Arbitrary Value Documentation](https://tailwindcss.com/docs/adding-custom-styles#using-arbitrary-values)

**Remember**: When in doubt, use arbitrary value syntax with square brackets. 

## Implementation Status Update - March 31, 2024

✅ **COMPLETE**: The ASAP Digest codebase is now fully compliant with Tailwind 4 syntax requirements.

All components in the application have been updated following the Visual Identity Implementation Checklist:

1. All direct color names have been replaced with HSL variables or hex codes
2. All semantic color references now use proper HSL variable syntax
3. All fixed utility classes now use arbitrary value syntax with square brackets
4. All spacing now uses CSS variables through the spacing scale
5. Typography has been standardized with CSS variables for font sizes, weights, and line heights
6. Border-radius values now use CSS variables for consistency
7. Icon system has been fully implemented with the Lucide icon compatibility layer

The Visual Identity Implementation Checklist has been completed and all components now conform to these guidelines. 