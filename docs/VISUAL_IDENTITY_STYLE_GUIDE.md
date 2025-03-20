# ASAP Digest Visual Identity Style Guide

## Overview

This style guide documents the comprehensive Atomic Design System for ASAP Digest, implemented using Tailwind 4 utilities. It serves as the single source of truth for our visual identity, ensuring consistency across all interfaces and components.

## Table of Contents

1. [Design Philosophy](#design-philosophy)
2. [Atomic Design Structure](#atomic-design-structure)
3. [Color System](#color-system)
4. [Typography System](#typography-system)
5. [Spacing System](#spacing-system)
6. [Animation & Transitions](#animation--transitions)
7. [Component Library](#component-library)
8. [Responsive Design](#responsive-design)
9. [Imagery Guidelines](#imagery-guidelines)
10. [Implementation Guidelines](#implementation-guidelines)
11. [Accessibility Standards](#accessibility-standards)
12. [Versioning](#versioning)

## Design Philosophy

ASAP Digest's visual identity follows these core principles:

- **Energy & Mystery:** Our designs convey both energy and intrigue, with vibrant neon elements that emerge from darker backgrounds
- **Consistency:** All UI elements adhere to the same design tokens and patterns
- **Modularity:** Components are built from smaller, reusable pieces
- **Scalability:** The system can grow without losing coherence 
- **Accessibility:** All elements meet WCAG 2.1 AA standards
- **Performance:** Optimized for minimal CSS and maximum rendering speed

## Atomic Design Structure

We follow Brad Frost's Atomic Design methodology with Tailwind 4's utility-first approach:

### Atoms

Fundamental building blocks that cannot be broken down further.

```html
<!-- Button atom example -->
<button class="px-[calc(var(--spacing-unit)*4)] py-[calc(var(--spacing-unit)*2)] bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-[var(--radius-md)]">
  Button Text
</button>
```

### Molecules

Simple combinations of atoms that function together as a unit.

```html
<!-- Card molecule example -->
<div class="flex flex-col border-[1px] border-[hsl(var(--border))] rounded-[var(--radius-md)] overflow-hidden">
  <div class="p-[calc(var(--spacing-unit)*4)]">
    <h3 class="text-[var(--font-size-lg)] font-medium">Card Title</h3>
    <p class="text-[var(--font-size-base)] text-[hsl(var(--muted-foreground))]">Card content</p>
  </div>
  <div class="flex justify-end p-[calc(var(--spacing-unit)*3)] border-t-[1px] border-[hsl(var(--border))]">
    <!-- Action buttons (atoms) here -->
  </div>
</div>
```

### Organisms

Complex components composed of molecules and/or atoms that form distinct sections of an interface.

```html
<!-- Header organism example -->
<header class="w-full border-b-[1px] border-[hsl(var(--border))] bg-[hsl(var(--background))]">
  <div class="container mx-auto px-[calc(var(--spacing-unit)*4)] py-[calc(var(--spacing-unit)*3)] flex justify-between items-center">
    <!-- Logo molecule -->
    <!-- Navigation molecule -->
    <!-- User profile molecule -->
  </div>
</header>
```

### Templates

Page layouts that place organisms into a structure without specific content.

### Pages

Templates with actual content, representing the final UI.

## Color System

Our color system reflects our "energy & mystery" brand identity with vibrant neons emerging from dark backgrounds. All colors are defined as HSL variables in the CSS root to ensure consistency and enable theming.

### Dark Base + Neon Accents

```css
:root {
  /* Dark foundation */
  --background: 220 13% 18%;        /* Deep blue-gray */
  --foreground: 210 40% 98%;        /* Off-white */
  --card: 220 13% 23%;              /* Slightly lighter deep blue-gray */
  --card-foreground: 210 40% 98%;   /* Off-white */
  --popover: 220 13% 23%;           /* Slightly lighter deep blue-gray */
  --popover-foreground: 210 40% 98%; /* Off-white */
  
  /* Neon primary colors */
  --primary: 326 100% 60%;           /* Neon pink */
  --primary-foreground: 210 40% 98%; /* Off-white */
  
  /* Secondary neon */
  --secondary: 175 98% 60%;          /* Neon cyan */
  --secondary-foreground: 220 13% 18%; /* Deep blue-gray */
  
  /* Supporting colors */
  --muted: 220 13% 28%;              /* Muted dark blue-gray */
  --muted-foreground: 210 40% 80%;   /* Light gray */
  
  /* Accent neon */
  --accent: 265 90% 65%;             /* Neon purple */
  --accent-foreground: 210 40% 98%;  /* Off-white */
  
  /* Alert colors */
  --destructive: 0 90% 60%;          /* Neon red */
  --destructive-foreground: 210 40% 98%; /* Off-white */
  
  /* UI elements */
  --border: 220 13% 30%;             /* Border color */
  --input: 220 13% 30%;              /* Input border */
  --ring: 326 100% 60%;              /* Focus ring (neon pink) */
  
  /* Additional neon accents */
  --neon-yellow: 60 100% 60%;        /* Neon yellow */
  --neon-green: 145 100% 60%;        /* Neon green */
  --neon-blue: 210 100% 60%;         /* Neon blue */
}

.dark {
  /* Dark mode is our default, but we adjust it slightly for true dark mode */
  --background: 220 13% 10%;         /* Deeper blue-gray */
  --foreground: 210 40% 98%;         /* Off-white */
  --card: 220 13% 15%;               /* Slightly lighter deep blue-gray */
}
```

### Usage Guidelines

- **ALWAYS** use the HSL variable syntax: `bg-[hsl(var(--primary))]`
- **NEVER** use direct color names: `bg-blue-500`
- Use neon colors as accents and highlights, not for large areas
- Maintain sufficient contrast ratios (minimum 4.5:1 for normal text)
- Dark backgrounds should dominate, with neon colors as "bursts of energy"
- For gradients, transition between neon colors for dynamic energy effects

### Color Combinations

1. **Primary UI Elements:**
   - Buttons, links, focus states: `var(--primary)` (neon pink)
   - Secondary actions: `var(--secondary)` (neon cyan)
   - Success states: `var(--neon-green)`
   - Error states: `var(--destructive)` (neon red)

2. **Content Areas:**
   - Main background: `var(--background)` (deep blue-gray)
   - Card backgrounds: `var(--card)` (slightly lighter blue-gray)
   - Borders: `var(--border)` (subtle dark line)

3. **Text:**
   - Primary text: `var(--foreground)` (off-white)
   - Secondary text: `var(--muted-foreground)` (light gray)
   - Accent text: Any neon color for emphasis

## Typography System

Typography is defined with a clear hierarchy using Inter for headings and a complementary font for body text.

### Font Families

```css
:root {
  --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  --font-body: 'Rubik', 'Open Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  --font-mono: 'JetBrains Mono', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
}
```

Inter pairs well with several body fonts:

- **Rubik:** A clean sans-serif with geometric touches that complements Inter's clarity
- **Open Sans:** A highly readable font that provides a slightly softer feel than Inter
- **Work Sans:** A modern sans-serif with excellent readability at small sizes
- **Source Sans Pro:** A versatile font that balances well with Inter's geometric forms

### Type Scale

```css
:root {
  --font-size-xs: 0.75rem;      /* 12px */
  --font-size-sm: 0.875rem;     /* 14px */
  --font-size-base: 1rem;       /* 16px */
  --font-size-lg: 1.125rem;     /* 18px */
  --font-size-xl: 1.25rem;      /* 20px */
  --font-size-2xl: 1.5rem;      /* 24px */
  --font-size-3xl: 1.875rem;    /* 30px */
  --font-size-4xl: 2.25rem;     /* 36px */
  --font-size-5xl: 3rem;        /* 48px */
}
```

### Font Weights

```css
:root {
  --font-weight-thin: 100;
  --font-weight-light: 300;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
  --font-weight-extrabold: 800;
}
```

### Line Heights

```css
:root {
  --line-height-none: 1;        /* tight */
  --line-height-tight: 1.25;    /* headings */
  --line-height-snug: 1.375;    
  --line-height-normal: 1.5;    /* body text */
  --line-height-relaxed: 1.625;
  --line-height-loose: 2;       /* spacious text */
}
```

### Typography Usage

```html
<!-- Headings (Inter) -->
<h1 class="font-[var(--font-sans)] text-[var(--font-size-4xl)] font-[var(--font-weight-bold)] leading-[var(--line-height-tight)]">Page Title</h1>
<h2 class="font-[var(--font-sans)] text-[var(--font-size-3xl)] font-[var(--font-weight-semibold)] leading-[var(--line-height-tight)]">Section Title</h2>
<h3 class="font-[var(--font-sans)] text-[var(--font-size-2xl)] font-[var(--font-weight-medium)] leading-[var(--line-height-tight)]">Subsection Title</h3>

<!-- Body text (Rubik or other paired font) -->
<p class="font-[var(--font-body)] text-[var(--font-size-base)] leading-[var(--line-height-normal)]">Regular paragraph text</p>
<p class="font-[var(--font-body)] text-[var(--font-size-sm)] leading-[var(--line-height-normal)] text-[hsl(var(--muted-foreground))]">Secondary text</p>
```

## Spacing System

Our spacing system uses a consistent scale based on a fundamental unit.

### Base Spacing Unit

```css
:root {
  --spacing-unit: 0.25rem; /* 4px at base font size */
}
```

### Spacing Scale

```css
/* In tailwind.config.js */
theme: {
  extend: {
    spacing: {
      '1': 'calc(var(--spacing-unit) * 1)',    /* 4px */
      '2': 'calc(var(--spacing-unit) * 2)',    /* 8px */
      '3': 'calc(var(--spacing-unit) * 3)',    /* 12px */
      '4': 'calc(var(--spacing-unit) * 4)',    /* 16px */
      '5': 'calc(var(--spacing-unit) * 5)',    /* 20px */
      '6': 'calc(var(--spacing-unit) * 6)',    /* 24px */
      '8': 'calc(var(--spacing-unit) * 8)',    /* 32px */
      '10': 'calc(var(--spacing-unit) * 10)',  /* 40px */
      '12': 'calc(var(--spacing-unit) * 12)',  /* 48px */
      '16': 'calc(var(--spacing-unit) * 16)',  /* 64px */
      '20': 'calc(var(--spacing-unit) * 20)',  /* 80px */
      '24': 'calc(var(--spacing-unit) * 24)',  /* 96px */
      '32': 'calc(var(--spacing-unit) * 32)',  /* 128px */
    }
  }
}
```

### Border Radius

```css
:root {
  --radius-none: 0;
  --radius-sm: 0.125rem;
  --radius-md: 0.25rem;
  --radius-lg: 0.5rem;
  --radius-xl: 0.75rem;
  --radius-2xl: 1rem;
  --radius-full: 9999px;
}
```

### Standard Spacing Classes

```css
/* In app.css */
.space-xs {
  @apply p-[calc(var(--spacing-unit)*2)];      /* 8px padding */
}

.space-sm {
  @apply p-[calc(var(--spacing-unit)*4)];      /* 16px padding */
}

.space-md {
  @apply p-[calc(var(--spacing-unit)*6)];      /* 24px padding */
}

.space-lg {
  @apply p-[calc(var(--spacing-unit)*8)];      /* 32px padding */
}

.space-xl {
  @apply p-[calc(var(--spacing-unit)*12)];     /* 48px padding */
}

/* Gap utilities */
.gap-xs {
  @apply gap-[calc(var(--spacing-unit)*2)];    /* 8px gap */
}

.gap-sm {
  @apply gap-[calc(var(--spacing-unit)*4)];    /* 16px gap */
}

.gap-md {
  @apply gap-[calc(var(--spacing-unit)*6)];    /* 24px gap */
}

.gap-lg {
  @apply gap-[calc(var(--spacing-unit)*8)];    /* 32px gap */
}

.gap-xl {
  @apply gap-[calc(var(--spacing-unit)*12)];   /* 48px gap */
}
```

### Usage Guidelines

- Use the spacing scale for all margins, paddings, gaps, and positioning
- Maintain consistent spacing rhythm across components
- Use larger spacing values for section separations, smaller for related items
- For fixed sizes, always use the `[calc(var(--spacing-unit)*X)]` syntax

## Animation & Transitions

Animations and transitions bring our interfaces to life, reinforcing our energy and mystery brand identity.

### Animation Variables

```css
:root {
  /* Durations */
  --duration-fast: 150ms;
  --duration-normal: 300ms;
  --duration-slow: 500ms;
  --duration-slower: 1000ms;
  
  /* Easings */
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
  --ease-elastic: cubic-bezier(0.68, -0.6, 0.32, 1.6);
  
  /* Neon glow */
  --glow-sm: 0 0 2px;
  --glow-md: 0 0 4px;
  --glow-lg: 0 0 8px;
}
```

### Core Animation Principles

1. **Purpose First:** Every animation must serve a purpose - guiding attention, providing feedback, or expressing brand identity
2. **Subtlety:** Animations should feel natural and enhance rather than distract from the experience
3. **Performance:** Optimize animations to maintain smooth performance across all devices
4. **Consistency:** Use similar timing and easing for similar types of interactions

### Common Animation Patterns

#### 1. Page Transitions

```css
/* Base page transition using CSS */
.page-enter {
  opacity: 0;
  transform: translateY(8px);
}

.page-enter-active {
  opacity: 1;
  transform: translateY(0);
  transition: opacity var(--duration-normal) var(--ease-out),
              transform var(--duration-normal) var(--ease-out);
}

.page-exit {
  opacity: 1;
}

.page-exit-active {
  opacity: 0;
  transform: translateY(-8px);
  transition: opacity var(--duration-normal) var(--ease-in),
              transform var(--duration-normal) var(--ease-in);
}
```

#### 2. Hover Effects

```css
/* Neon button hover */
.btn-neon {
  position: relative;
  transition: all var(--duration-normal) var(--ease-out);
}

.btn-neon:hover {
  box-shadow: var(--glow-md) hsl(var(--primary));
  transform: translateY(-2px);
}

.btn-neon:active {
  transform: translateY(0);
}
```

#### 3. Loading States

```css
/* Pulsing animation */
@keyframes pulse {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
  100% {
    opacity: 1;
  }
}

.loading-pulse {
  animation: pulse var(--duration-slower) var(--ease-in-out) infinite;
}
```

#### 4. Attention-Grabbing Effects

```css
/* Neon flash */
@keyframes neon-flash {
  0%, 100% {
    box-shadow: var(--glow-sm) hsl(var(--primary));
  }
  50% {
    box-shadow: var(--glow-lg) hsl(var(--primary));
  }
}

.flash-notification {
  animation: neon-flash var(--duration-slower) var(--ease-out) 2;
}
```

### Animation Guidelines

- **Performance:** Animate only `transform` and `opacity` when possible to utilize GPU acceleration
- **Duration:** Keep most UI animations between 150-300ms (use `--duration-fast` to `--duration-normal`)
- **Timing:** Use appropriate easing functions - `--ease-out` for entering elements, `--ease-in` for exiting
- **Reduced Motion:** Always provide alternatives for users with vestibular disorders or motion sensitivity

## Component Library

Our component library is built using Svelte 5 with a consistent architecture for all elements.

### Base Component Structure

```svelte
<!-- atoms/Button.svelte -->
<script>
  /** @type {string} [variant="primary"] - Button variant */
  let { variant = 'primary' } = $props();
  
  /** @type {string} [size="md"] - Button size */
  let { size = 'md' } = $props();
  
  /** @type {boolean} [disabled=false] - Whether the button is disabled */
  let { disabled = false } = $props();
  
  // Computed classes based on props
  let classes = $derived(() => {
    return {
      base: "inline-flex items-center justify-center rounded-[var(--radius-md)] transition-all duration-[var(--duration-normal)]",
      variant: getVariantClasses(),
      size: getSizeClasses(),
      state: disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer"
    };
  });
  
  /** Get the appropriate classes for the selected variant */
  function getVariantClasses() {
    switch(variant) {
      case 'primary':
        return "bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] hover:bg-[hsl(var(--primary)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--primary))]";
      case 'secondary':
        return "bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))] hover:bg-[hsl(var(--secondary)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--secondary))]";
      case 'outline':
        return "bg-transparent border border-[hsl(var(--primary))] text-[hsl(var(--primary))] hover:bg-[hsl(var(--primary)/0.1)] hover:shadow-[var(--glow-sm)_hsl(var(--primary))]";
      case 'ghost':
        return "bg-transparent text-[hsl(var(--foreground))] hover:bg-[hsl(var(--foreground)/0.1)]";
      case 'destructive':
        return "bg-[hsl(var(--destructive))] text-[hsl(var(--destructive-foreground))] hover:bg-[hsl(var(--destructive)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--destructive))]";
      default:
        return "bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] hover:bg-[hsl(var(--primary)/0.9)]";
    }
  }
  
  /** Get the appropriate classes for the selected size */
  function getSizeClasses() {
    switch(size) {
      case 'sm':
        return "text-[var(--font-size-sm)] px-[calc(var(--spacing-unit)*3)] py-[calc(var(--spacing-unit)*1.5)]";
      case 'md':
        return "text-[var(--font-size-base)] px-[calc(var(--spacing-unit)*4)] py-[calc(var(--spacing-unit)*2)]";
      case 'lg':
        return "text-[var(--font-size-lg)] px-[calc(var(--spacing-unit)*6)] py-[calc(var(--spacing-unit)*3)]";
      default:
        return "text-[var(--font-size-base)] px-[calc(var(--spacing-unit)*4)] py-[calc(var(--spacing-unit)*2)]";
    }
  }
</script>

<button 
  class="{classes.base} {classes.variant} {classes.size} {classes.state}"
  disabled={disabled}
  on:click
  {...$$restProps}
>
  <slot />
</button>
```

### Component States

All interactive components should have the following states:

1. **Default:** The base appearance
2. **Hover:** Visual feedback when a user hovers over the element
3. **Focus:** Clear indication when an element receives keyboard focus
4. **Active/Pressed:** Visual feedback during interaction (clicking/tapping)
5. **Disabled:** Subdued appearance when the component cannot be interacted with
6. **Loading:** Visual indicator when action is being processed

```svelte
<!-- Input state example -->
<div class="relative">
  <input 
    type="text"
    class="
      w-full px-[calc(var(--spacing-unit)*3)] py-[calc(var(--spacing-unit)*2)]
      bg-[hsl(var(--background))]
      border border-[hsl(var(--input))]
      rounded-[var(--radius-md)]
      text-[hsl(var(--foreground))]
      transition-colors duration-[var(--duration-normal)]
      
      /* Hover state */
      hover:border-[hsl(var(--primary)/0.5)]
      
      /* Focus state */
      focus:outline-none
      focus:border-[hsl(var(--primary))]
      focus:ring-1
      focus:ring-[hsl(var(--primary))]
      
      /* Disabled state */
      disabled:opacity-50
      disabled:cursor-not-allowed
      disabled:bg-[hsl(var(--muted))]
    "
    disabled={disabled}
    {...$$restProps}
  />
  
  <!-- Error state indicator -->
  {#if error}
    <div class="text-[hsl(var(--destructive))] text-[var(--font-size-sm)] mt-[calc(var(--spacing-unit)*1)]">
      {error}
    </div>
  {/if}
</div>
```

### Standard Component Properties

All components should support these common properties when applicable:

- `variant`: Visual style variations (primary, secondary, outline, ghost, etc.)
- `size`: Size variations (sm, md, lg, etc.)
- `disabled`: Disabled state
- `loading`: Loading state
- `class`: Custom classes to be applied to the component
- `data-*`: Data attributes for behavior hooks

### Layout Components

Standardized containers and layout elements:

```svelte
<!-- Container.svelte -->
<script>
  /** @type {string} [size="default"] - Container max width */
  let { size = 'default' } = $props();
  
  let maxWidth = $derived(() => {
    switch (size) {
      case 'sm': return 'max-w-[640px]';
      case 'md': return 'max-w-[768px]';
      case 'lg': return 'max-w-[1024px]';
      case 'xl': return 'max-w-[1280px]';
      case '2xl': return 'max-w-[1440px]';
      default: return 'max-w-[1440px]';
    }
  });
</script>

<div class="w-full {maxWidth} mx-auto px-[calc(var(--spacing-unit)*4)]">
  <slot />
</div>
```

### Grid System

Consistent grid layout using CSS Grid:

```css
.grid-layout {
  display: grid;
  grid-template-columns: repeat(var(--grid-columns, 12), minmax(0, 1fr));
  gap: var(--grid-gap, calc(var(--spacing-unit) * 6));
  width: 100%;
  max-width: var(--grid-max-width, 1440px);
  margin-left: auto;
  margin-right: auto;
}
```

## Responsive Design

Our responsive design approach ensures a consistent experience across all devices while optimizing for each screen size.

### Breakpoints

```css
/* In tailwind.config.js */
screens: {
  "mobile": "478px",
  "mobile-landscape": "767px",
  "tablet": "991px",
  "desktop": "1440px",
  "sm": "640px",
  "md": "768px",
  "lg": "1024px",
  "xl": "1280px",
  "2xl": "1536px",
}
```

### Responsive Principles

1. **Mobile-First Approach:** Design for mobile first, then enhance for larger screens
2. **Fluid Typography:** Scale text size based on viewport width
3. **Considerate Layout Shifts:** Minimize layout changes between breakpoints
4. **Touch-Friendly Targets:** Ensure interactive elements are at least 44×44px on touch devices
5. **Optimize Visual Hierarchy:** Adjust spacing and element prominence for different screen sizes

### Responsive Component Example

```svelte
<!-- ResponsiveCard.svelte -->
<div class="
  w-full
  rounded-[var(--radius-md)]
  overflow-hidden
  bg-[hsl(var(--card))]
  border border-[hsl(var(--border))]
  
  /* Mobile styling */
  flex flex-col
  
  /* Tablet+ styling */
  md:flex-row
">
  <div class="
    /* Mobile styling */
    w-full
    h-[200px]
    
    /* Tablet+ styling */
    md:w-[40%]
    md:h-auto
  ">
    <img 
      src={image} 
      alt={title}
      class="w-full h-full object-cover"
    />
  </div>
  
  <div class="
    p-[calc(var(--spacing-unit)*4)]
    
    /* Tablet+ styling */
    md:p-[calc(var(--spacing-unit)*6)]
    md:flex-1
  ">
    <h3 class="
      text-[var(--font-size-xl)]
      font-[var(--font-weight-semibold)]
      
      /* Tablet+ styling */
      md:text-[var(--font-size-2xl)]
    ">
      {title}
    </h3>
    <p class="mt-[calc(var(--spacing-unit)*2)]">
      {description}
    </p>
  </div>
</div>
```

## Imagery Guidelines

Our imagery style reflects our brand identity of energy and mystery.

### Image Style

1. **High Contrast:** Images should have clear contrast with dark backgrounds and vibrant elements
2. **Dynamic Energy:** Favor imagery with movement, light, and energy
3. **Mysterious Elements:** Include elements that create intrigue and depth
4. **Color Treatment:** Apply color overlays that align with our neon palette

### Technical Requirements

- **Format:** Use WebP with JPEG fallback for photos; SVG for illustrations
- **Quality:** JPEG compression at 80-85% quality
- **Resolution:** 2x for standard displays, optimize for screens
- **Lazy Loading:** Implement lazy loading for all non-critical images
- **Alt Text:** Provide descriptive alternative text for all images

### Image Component

```svelte
<!-- Image.svelte -->
<script>
  /** @type {string} src - Image source URL */
  let { src } = $props();
  
  /** @type {string} alt - Alternative text for image */
  let { alt } = $props();
  
  /** @type {string} [aspectRatio="16/9"] - Image aspect ratio */
  let { aspectRatio = "16/9" } = $props();
  
  /** @type {boolean} [lazy=true] - Whether to lazy load the image */
  let { lazy = true } = $props();
  
  /** @type {string} [objectFit="cover"] - Object-fit property */
  let { objectFit = "cover" } = $props();
</script>

<div class="relative overflow-hidden" style="aspect-ratio: {aspectRatio};">
  <img 
    src={src} 
    alt={alt}
    loading={lazy ? "lazy" : "eager"}
    class="w-full h-full object-{objectFit} transition-opacity duration-[var(--duration-normal)]"
  />
</div>
```

## Implementation Guidelines

### Component Organization

```
src/lib/components/
├── atoms/
│   ├── Button.svelte
│   ├── Input.svelte
│   ├── Typography.svelte
│   ├── Icon.svelte
│   └── ...
├── molecules/
│   ├── Card.svelte
│   ├── FormField.svelte
│   ├── Notification.svelte
│   ├── Avatar.svelte
│   └── ...
├── organisms/
│   ├── Header.svelte
│   ├── Sidebar.svelte
│   ├── DataTable.svelte
│   ├── SearchBar.svelte
│   └── ...
└── templates/
    ├── DashboardLayout.svelte
    ├── AuthLayout.svelte
    ├── ArticleLayout.svelte
    └── ...
```

### Implementation Strategy

1. **Audit Existing Components**
   - Identify inconsistent spacing/styling
   - Document current patterns

2. **Create Atomic Library**
   - Build base atoms with Tailwind 4
   - Compose molecules from atoms
   - Develop organisms from molecules

3. **Refactor Templates**
   - Apply consistent spacing system
   - Use CSS variables for tokens
   - Remove hard-coded values

4. **Page Implementation**
   - Apply real content to templates
   - Test responsive behaviors
   - Validate against accessibility standards

### Tailwind Configuration

```js
// tailwind.config.js
import { fontFamily } from "tailwindcss/defaultTheme";
import tailwindcssAnimate from "tailwindcss-animate";

/** @type {import('tailwindcss').Config} */
const config = {
  darkMode: ["class"],
  content: ["./src/**/*.{html,js,svelte,ts}"],
  safelist: ["dark"],
  theme: {
    screens: {
      "mobile": "478px",
      "mobile-landscape": "767px",
      "tablet": "991px",
      "desktop": "1440px",
      "sm": "640px",
      "md": "768px",
      "lg": "1024px",
      "xl": "1280px",
      "2xl": "1536px",
    },
    container: {
      center: true,
      padding: "10px",
      screens: {
        "2xl": "1440px"
      }
    },
    extend: {
      colors: {
        // All colors use HSL variable syntax
      },
      spacing: {
        // Spacing scale based on spacing unit
        '1': 'calc(var(--spacing-unit) * 1)',
        '2': 'calc(var(--spacing-unit) * 2)',
        // ...and so on
      },
      borderRadius: {
        xl: "calc(var(--radius) + 4px)",
        lg: "var(--radius)",
        md: "calc(var(--radius) - 2px)",
        sm: "calc(var(--radius) - 4px)"
      },
      fontFamily: {
        sans: ["var(--font-sans)", ...fontFamily.sans],
        body: ["var(--font-body)", ...fontFamily.sans],
        mono: ["var(--font-mono)", ...fontFamily.mono]
      },
      animation: {
        "accordion-down": "accordion-down 0.2s ease-out",
        "accordion-up": "accordion-up 0.2s ease-out",
        "caret-blink": "caret-blink 1.25s ease-out infinite",
      },
    },
  },
  plugins: [tailwindcssAnimate],
};

export default config;
```

### Code Standards

- Use proper JSDoc comments for all component props
- Use `$state` and `$derived` for reactivity in Svelte 5
- Follow the HSL variable syntax for all colors
- Use the spacing system consistently
- Import individual components directly:
  ```js
  // Good
  import Button from '$lib/components/atoms/Button.svelte';
  
  // Avoid
  import { Button } from '$lib/components';
  ```

## Accessibility Standards

All components must meet WCAG 2.1 AA standards:

- Maintain color contrast ratios of at least 4.5:1 for normal text
- Provide keyboard navigation for all interactive elements
- Include proper ARIA attributes
- Support screen readers through semantic HTML
- Ensure focus states are visible and consistent

### Focus States

All interactive elements should have visible focus states:

```css
:root {
  --focus-ring: 0 0 0 2px hsl(var(--background)), 0 0 0 4px hsl(var(--ring));
}

/* Apply to focusable elements */
.focus-ring:focus-visible {
  outline: none;
  box-shadow: var(--focus-ring);
}
```

## Versioning

We use a simple versioning scheme for our design system:

```
v[MAJOR].[MINOR].[PATCH]
```

- **MAJOR:** Breaking changes that require code updates
- **MINOR:** New features or components (backward compatible)
- **PATCH:** Bug fixes and minor visual adjustments

Example: v1.2.3

### Version History

Each release should include a changelog entry documenting changes.

Current Version: v1.0.0

## Conclusion

This Visual Identity Style Guide establishes a comprehensive Atomic Design System using Tailwind 4 utilities. By adhering to these guidelines, we ensure a consistent, accessible, and maintainable design system that scales with our application.

The combination of Atomic Design methodology and Tailwind's utility-first approach creates a flexible system that promotes design consistency while allowing for efficient development.

---

*Last updated: March 20, 2024*

*This document should be reviewed and updated quarterly to ensure it remains current with evolving design needs and platform capabilities.* 