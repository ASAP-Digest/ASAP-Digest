# Atomic Components

This directory contains atomic-level components following the Atomic Design methodology.

## Import Pattern

Import components using the barrel file:

```js
import { Button, Link, Typography } from '$lib/components/atoms';
```

## ⚠️ IMPORTANT: ONLY USE TAILWIND 4 SYNTAX ⚠️

All components in this directory must use Tailwind 4 syntax:

- NEVER use direct color names (text-red-500)
- NEVER use semantic color names without HSL variables (bg-primary, border-border)
- ALWAYS use: bg-[hsl(var(--primary))], border-[hsl(var(--border))]
- For fixed sizing, use arbitrary values: h-[0.25rem] not h-1

See the full guidelines in `md-docs/TAILWIND4_GUIDELINES.md`

## Components

### Button.svelte

A versatile button component with multiple variants and sizes:

- **Variants**: primary, secondary, outline, ghost, destructive
- **Sizes**: sm, md, lg
- **States**: default, hover, focus, disabled
- **Usage**: `<Button variant="primary" size="md">Click Me</Button>`

### Link.svelte

A consistent link component with support for different variants and external links:

- **Variants**: default, underlined, muted
- **Features**: External link detection with icon, focus states for accessibility
- **States**: default (link), hover, active, focus, visited
- **Usage**: `<Link href="/page" variant="underlined">Page Link</Link>` or `<Link href="https://example.com" external>External Link</Link>`

### Typography.svelte

Consistent text styling component with multiple variants:

- **Variants**: h1, h2, h3, h4, h5, h6, p, caption, blockquote, code
- **Usage**: `<Typography variant="h1">Heading Text</Typography>`

## Demo

Visit the [Design System](/design-system) page to see all components in action.

## Guidelines

When adding new components or modifying existing ones:

1. Test all components in both light and dark mode
2. Ensure proper accessibility (ARIA attributes, keyboard navigation)
3. Follow the existing component patterns
4. Update this README when adding new components
5. Add the component to the design system demo page
6. Consider creating a dedicated demo page for complex components 