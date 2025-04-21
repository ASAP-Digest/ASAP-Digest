/**
 * ⚠️ IMPORTANT: ONLY USE TAILWIND 4 SYNTAX ⚠️
 * 
 * This utility helps convert Tailwind 3 to Tailwind 4 syntax
 * You MUST use Tailwind 4 syntax for all new code! This tool is only
 * to help migrate legacy code. Follow these rules for all new code:
 * 
 * - NEVER use direct color names (text-red-500, bg-blue-400)
 * - NEVER use semantic names without HSL vars (bg-primary, text-foreground)
 * - ALWAYS use HSL variables: bg-[hsl(var(--primary))], text-[hsl(var(--foreground))]
 * - For fixed sizes, always use arbitrary values: h-[0.25rem] not h-1
 *
 * @see md-docs/TAILWIND4_GUIDELINES.md for full documentation
 */

/**
 * Utility functions to help with Tailwind 4 class migration and validation
 */

/**
 * Maps old color class syntax to new HSL variable syntax
 * @type {Object<string, string>}
 */
export const colorClassMap = {
    // Text colors
    'text-primary': 'text-[hsl(var(--primary))]',
    'text-secondary': 'text-[hsl(var(--secondary))]',
    'text-accent': 'text-[hsl(var(--accent))]',
    'text-background': 'text-[hsl(var(--background))]',
    'text-foreground': 'text-[hsl(var(--foreground))]',
    'text-muted': 'text-[hsl(var(--muted))]',
    'text-muted-foreground': 'text-[hsl(var(--muted-foreground))]',
    'text-card': 'text-[hsl(var(--card))]',
    'text-card-foreground': 'text-[hsl(var(--card-foreground))]',
    'text-popover': 'text-[hsl(var(--popover))]',
    'text-popover-foreground': 'text-[hsl(var(--popover-foreground))]',
    'text-destructive': 'text-[hsl(var(--destructive))]',
    'text-destructive-foreground': 'text-[hsl(var(--destructive-foreground))]',
    'text-border': 'text-[hsl(var(--border))]',
    'text-input': 'text-[hsl(var(--input))]',
    'text-ring': 'text-[hsl(var(--ring))]',

    // Background colors
    'bg-primary': 'bg-[hsl(var(--primary))]',
    'bg-secondary': 'bg-[hsl(var(--secondary))]',
    'bg-accent': 'bg-[hsl(var(--accent))]',
    'bg-background': 'bg-[hsl(var(--background))]',
    'bg-foreground': 'bg-[hsl(var(--foreground))]',
    'bg-muted': 'bg-[hsl(var(--muted))]',
    'bg-muted-foreground': 'bg-[hsl(var(--muted-foreground))]',
    'bg-card': 'bg-[hsl(var(--card))]',
    'bg-card-foreground': 'bg-[hsl(var(--card-foreground))]',
    'bg-popover': 'bg-[hsl(var(--popover))]',
    'bg-popover-foreground': 'bg-[hsl(var(--popover-foreground))]',
    'bg-destructive': 'bg-[hsl(var(--destructive))]',
    'bg-destructive-foreground': 'bg-[hsl(var(--destructive-foreground))]',
    'bg-border': 'bg-[hsl(var(--border))]',
    'bg-input': 'bg-[hsl(var(--input))]',
    'bg-ring': 'bg-[hsl(var(--ring))]',

    // Border colors
    'border-primary': 'border-[hsl(var(--primary))]',
    'border-secondary': 'border-[hsl(var(--secondary))]',
    'border-accent': 'border-[hsl(var(--accent))]',
    'border-background': 'border-[hsl(var(--background))]',
    'border-foreground': 'border-[hsl(var(--foreground))]',
    'border-muted': 'border-[hsl(var(--muted))]',
    'border-muted-foreground': 'border-[hsl(var(--muted-foreground))]',
    'border-card': 'border-[hsl(var(--card))]',
    'border-card-foreground': 'border-[hsl(var(--card-foreground))]',
    'border-popover': 'border-[hsl(var(--popover))]',
    'border-popover-foreground': 'border-[hsl(var(--popover-foreground))]',
    'border-destructive': 'border-[hsl(var(--destructive))]',
    'border-destructive-foreground': 'border-[hsl(var(--destructive-foreground))]',
    'border-border': 'border-[hsl(var(--border))]',
    'border-input': 'border-[hsl(var(--input))]',
    'border-ring': 'border-[hsl(var(--ring))]',

    // Ring colors
    'ring-primary': 'ring-[hsl(var(--primary))]',
    'ring-secondary': 'ring-[hsl(var(--secondary))]',
    'ring-accent': 'ring-[hsl(var(--accent))]',
    'ring-background': 'ring-[hsl(var(--background))]',
    'ring-foreground': 'ring-[hsl(var(--foreground))]',
    'ring-muted': 'ring-[hsl(var(--muted))]',
    'ring-muted-foreground': 'ring-[hsl(var(--muted-foreground))]',
    'ring-card': 'ring-[hsl(var(--card))]',
    'ring-card-foreground': 'ring-[hsl(var(--card-foreground))]',
    'ring-popover': 'ring-[hsl(var(--popover))]',
    'ring-popover-foreground': 'ring-[hsl(var(--popover-foreground))]',
    'ring-destructive': 'ring-[hsl(var(--destructive))]',
    'ring-destructive-foreground': 'ring-[hsl(var(--destructive-foreground))]',
    'ring-border': 'ring-[hsl(var(--border))]',
    'ring-input': 'ring-[hsl(var(--input))]',
    'ring-ring': 'ring-[hsl(var(--ring))]',
};

/**
 * Maps fraction values to modern bracket notation
 * @type {Object<string, string>}
 */
export const fractionMap = {
    '1/2': '[0.5]',
    '1/3': '[0.33333]',
    '2/3': '[0.66667]',
    '1/4': '[0.25]',
    '3/4': '[0.75]',
    '1/5': '[0.2]',
    '2/5': '[0.4]',
    '3/5': '[0.6]',
    '4/5': '[0.8]',
};

/**
 * Fixes a fractional spacing class to use modern bracket notation
 * @param {string} className - The class to fix
 * @returns {string} - The fixed class
 */
export function fixFractionalSpacing(className) {
    // Skip if already using bracket notation
    if (className.includes('[')) return className;

    const parts = className.split('-');
    const prefix = parts[0]; // m, p, mx, my, etc.
    const value = parts.slice(1).join('-'); // The fractional value

    // Check if it's a fraction and map it
    for (const [fraction, bracketValue] of Object.entries(fractionMap)) {
        if (value === fraction) {
            return `${prefix}-${bracketValue}`;
        }
    }

    // Return original if no match
    return className;
}

/**
 * Fixes Tailwind color classes to use modern HSL variable syntax
 * @param {string} className - The class to fix
 * @returns {string} - The fixed class
 */
export function fixColorClass(className) {
    return colorClassMap[className] || className;
}


/**
 * Checks if an element has nested container issues
 * @param {HTMLElement} element - The element to check
 * @returns {boolean} - True if there are nested container issues
 */
export function hasNestedContainerIssue(element) {
    if (!element || !element.classList) return false;

    // Check if the element has the container class
    const hasContainer = Array.from(element.classList).includes('container');

    if (!hasContainer) return false;

    // Check if any parent has a container class
    let parent = element.parentElement;
    while (parent) {
        if (parent.classList && Array.from(parent.classList).includes('container')) {
            return true;
        }
        parent = parent.parentElement;
    }

    return false;
}

/**
 * Gets consistent margin/padding class recommendations
 * @param {string} type - 'm' for margin, 'p' for padding
 * @returns {Object} - Spacing recommendations
 */
export function getSpacingRecommendations(type = 'm') {
    return {
        [`${type}-0`]: 'No spacing',
        [`${type}-1`]: '0.25rem (4px)',
        [`${type}-2`]: '0.5rem (8px)',
        [`${type}-3`]: '0.75rem (12px)',
        [`${type}-4`]: '1rem (16px) - Recommended for general content',
        [`${type}-5`]: '1.25rem (20px)',
        [`${type}-6`]: '1.5rem (24px) - Recommended for section spacing',
        [`${type}-8`]: '2rem (32px)',
        [`${type}-10`]: '2.5rem (40px)',
        [`${type}-12`]: '3rem (48px) - Recommended for major component spacing',
        [`${type}-16`]: '4rem (64px)',
        [`${type}-auto`]: 'Auto margin (for centering)',
    };
}

/**
 * Utility to help identify and fix shadcn-svelte theme classes
 * that need to be updated for Tailwind 4 compatibility
 */

// Map of shadcn theme classes to their HSL variable equivalents
export const themeClassMap = {
    // Background colors
    'bg-background': 'bg-[hsl(var(--background))]',
    'bg-foreground': 'bg-[hsl(var(--foreground))]',
    'bg-primary': 'bg-[hsl(var(--primary))]',
    'bg-primary-foreground': 'bg-[hsl(var(--primary-foreground))]',
    'bg-secondary': 'bg-[hsl(var(--secondary))]',
    'bg-secondary-foreground': 'bg-[hsl(var(--secondary-foreground))]',
    'bg-muted': 'bg-[hsl(var(--muted))]',
    'bg-muted-foreground': 'bg-[hsl(var(--muted-foreground))]',
    'bg-accent': 'bg-[hsl(var(--accent))]',
    'bg-accent-foreground': 'bg-[hsl(var(--accent-foreground))]',
    'bg-destructive': 'bg-[hsl(var(--destructive))]',
    'bg-destructive-foreground': 'bg-[hsl(var(--destructive-foreground))]',
    'bg-card': 'bg-[hsl(var(--card))]',
    'bg-card-foreground': 'bg-[hsl(var(--card-foreground))]',
    'bg-popover': 'bg-[hsl(var(--popover))]',
    'bg-popover-foreground': 'bg-[hsl(var(--popover-foreground))]',
    'bg-border': 'bg-[hsl(var(--border))]',
    'bg-input': 'bg-[hsl(var(--input))]',
    'bg-ring': 'bg-[hsl(var(--ring))]',

    // Text colors
    'text-background': 'text-[hsl(var(--background))]',
    'text-foreground': 'text-[hsl(var(--foreground))]',
    'text-primary': 'text-[hsl(var(--primary))]',
    'text-primary-foreground': 'text-[hsl(var(--primary-foreground))]',
    'text-secondary': 'text-[hsl(var(--secondary))]',
    'text-secondary-foreground': 'text-[hsl(var(--secondary-foreground))]',
    'text-muted': 'text-[hsl(var(--muted))]',
    'text-muted-foreground': 'text-[hsl(var(--muted-foreground))]',
    'text-accent': 'text-[hsl(var(--accent))]',
    'text-accent-foreground': 'text-[hsl(var(--accent-foreground))]',
    'text-destructive': 'text-[hsl(var(--destructive))]',
    'text-destructive-foreground': 'text-[hsl(var(--destructive-foreground))]',
    'text-card': 'text-[hsl(var(--card))]',
    'text-card-foreground': 'text-[hsl(var(--card-foreground))]',
    'text-popover': 'text-[hsl(var(--popover))]',
    'text-popover-foreground': 'text-[hsl(var(--popover-foreground))]',

    // Border colors
    'border-background': 'border-[hsl(var(--background))]',
    'border-foreground': 'border-[hsl(var(--foreground))]',
    'border-primary': 'border-[hsl(var(--primary))]',
    'border-secondary': 'border-[hsl(var(--secondary))]',
    'border-muted': 'border-[hsl(var(--muted))]',
    'border-accent': 'border-[hsl(var(--accent))]',
    'border-destructive': 'border-[hsl(var(--destructive))]',
    'border-card': 'border-[hsl(var(--card))]',
    'border-popover': 'border-[hsl(var(--popover))]',
    'border-border': 'border-[hsl(var(--border))]',
    'border-input': 'border-[hsl(var(--input))]',

    // Ring colors
    'ring-background': 'ring-[hsl(var(--background))]',
    'ring-foreground': 'ring-[hsl(var(--foreground))]',
    'ring-primary': 'ring-[hsl(var(--primary))]',
    'ring-secondary': 'ring-[hsl(var(--secondary))]',
    'ring-muted': 'ring-[hsl(var(--muted))]',
    'ring-accent': 'ring-[hsl(var(--accent))]',
    'ring-destructive': 'ring-[hsl(var(--destructive))]',
    'ring-border': 'ring-[hsl(var(--border))]',
    'ring-ring': 'ring-[hsl(var(--ring))]',

    // Outline colors
    'outline-background': 'outline-[hsl(var(--background))]',
    'outline-foreground': 'outline-[hsl(var(--foreground))]',
    'outline-primary': 'outline-[hsl(var(--primary))]',
    'outline-secondary': 'outline-[hsl(var(--secondary))]',
    'outline-muted': 'outline-[hsl(var(--muted))]',
    'outline-accent': 'outline-[hsl(var(--accent))]',
    'outline-destructive': 'outline-[hsl(var(--destructive))]',

    // Shadow colors
    'shadow-background': 'shadow-[hsl(var(--background))]',
    'shadow-foreground': 'shadow-[hsl(var(--foreground))]',
    'shadow-primary': 'shadow-[hsl(var(--primary))]',
    'shadow-secondary': 'shadow-[hsl(var(--secondary))]',
    'shadow-muted': 'shadow-[hsl(var(--muted))]',
    'shadow-accent': 'shadow-[hsl(var(--accent))]',
    'shadow-destructive': 'shadow-[hsl(var(--destructive))]',

    // Color with opacity variations (common patterns)
    'bg-primary/10': 'bg-[hsl(var(--primary)/0.1)]',
    'bg-primary/20': 'bg-[hsl(var(--primary)/0.2)]',
    'bg-primary/50': 'bg-[hsl(var(--primary)/0.5)]',
    'bg-primary/80': 'bg-[hsl(var(--primary)/0.8)]',
    'text-primary/10': 'text-[hsl(var(--primary)/0.1)]',
    'text-primary/20': 'text-[hsl(var(--primary)/0.2)]',
    'text-primary/50': 'text-[hsl(var(--primary)/0.5)]',
    'text-primary/80': 'text-[hsl(var(--primary)/0.8)]',
    'border-primary/10': 'border-[hsl(var(--primary)/0.1)]',
    'border-primary/20': 'border-[hsl(var(--primary)/0.2)]',
    'border-primary/50': 'border-[hsl(var(--primary)/0.5)]',
};

/**
 * Find problematic shadcn theme classes in the provided classString
 * @param {string} classString - The class string to check
 * @returns {Array} - Array of found problematic classes
 */
export function findProblematicClasses(classString) {
    if (!classString) return [];

    const classes = classString.split(/\s+/);
    return classes.filter(cls =>
        Object.keys(themeClassMap).some(key =>
            cls === key || cls.startsWith(`${key}/`)
        )
    );
}



/**
 * Comprehensive utility to fix all types of problematic Tailwind classes
 * @param {string} classString - The class string to fix
 * @returns {string} - Fixed class string
 */
export function fixClassString(classString) {
    if (!classString) return '';

    // First handle direct theme class replacements (bg-primary, text-muted, etc)
    let result = classString;
    const problematicClasses = findProblematicClasses(classString);

    problematicClasses.forEach(cls => {
        // Handle opacity variants (like bg-primary/50)
        if (cls.includes('/')) {
            const [baseClass, opacity] = cls.split('/');
            if (themeClassMap[baseClass]) {
                const fixedBaseClass = themeClassMap[baseClass].replace(']', `/${opacity})]`);
                result = result.replace(cls, fixedBaseClass);
            }
        } else if (themeClassMap[cls]) {
            result = result.replace(cls, themeClassMap[cls]);
        }
    });

    // Then handle other class types (colors from colorClassMap and fractional spacing)
    return result.split(' ')
        .map(cls => {
            // Fix color classes not caught by themeClassMap
            if (colorClassMap && colorClassMap[cls]) {
                return colorClassMap[cls];
            }

            // Fix fractional spacing classes
            if ((cls.startsWith('m-') || cls.startsWith('mx-') || cls.startsWith('my-') ||
                cls.startsWith('mt-') || cls.startsWith('mb-') || cls.startsWith('ml-') ||
                cls.startsWith('mr-') || cls.startsWith('p-') || cls.startsWith('px-') ||
                cls.startsWith('py-') || cls.startsWith('pt-') || cls.startsWith('pb-') ||
                cls.startsWith('pl-') || cls.startsWith('pr-')) &&
                cls.includes('/')) {
                return typeof fixFractionalSpacing === 'function' ?
                    fixFractionalSpacing(cls) : cls;
            }

            return cls;
        })
        .join(' ');
}