# UI Components

This directory contains [shadcn-svelte](https://shadcn-svelte.com/) components used throughout the ASAP Digest application.

## ⚠️ IMPORTANT: ONLY USE TAILWIND 4 SYNTAX ⚠️

All components in this directory must use Tailwind 4 syntax:

- NEVER use direct color names (text-red-500)
- NEVER use semantic color names without HSL variables (bg-primary, border-border)
- ALWAYS use: bg-[hsl(var(--primary))], border-[hsl(var(--border))]
- For fixed sizing, use arbitrary values: h-[0.25rem] not h-1

See the full guidelines in `docs/TAILWIND4_GUIDELINES.md`

## Components

When adding new components or modifying existing ones:

1. Test all components in both light and dark mode
2. Ensure proper accessibility (ARIA attributes, keyboard navigation)
3. Follow the existing component patterns
4. Update this README when adding new components 