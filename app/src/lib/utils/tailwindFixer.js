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
 * Fixes all problematic classes in a string of class names
 * @param {string} classString - Space-separated class names
 * @returns {string} - Fixed class names
 */
export function fixClassString(classString) {
    if (!classString) return '';

    return classString.split(' ')
        .map(cls => {
            // First check if it's a color class
            if (colorClassMap[cls]) {
                return colorClassMap[cls];
            }

            // Then check if it needs fractional spacing fix
            if ((cls.startsWith('m-') || cls.startsWith('mx-') || cls.startsWith('my-') ||
                cls.startsWith('mt-') || cls.startsWith('mb-') || cls.startsWith('ml-') ||
                cls.startsWith('mr-') || cls.startsWith('p-') || cls.startsWith('px-') ||
                cls.startsWith('py-') || cls.startsWith('pt-') || cls.startsWith('pb-') ||
                cls.startsWith('pl-') || cls.startsWith('pr-')) &&
                cls.includes('/')) {
                return fixFractionalSpacing(cls);
            }

            return cls;
        })
        .join(' ');
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