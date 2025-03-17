/**
 * ASAP Digest Spacing Utilities
 * 
 * This file provides consistent spacing utilities for the application
 * to ensure proper margins and padding between components.
 */

/**
 * Standard spacing scale for the application
 */
export const SPACING = {
    xs: 'p-2', // 0.5rem
    sm: 'p-4', // 1rem
    md: 'p-6', // 1.5rem
    lg: 'p-8', // 2rem
    xl: 'p-10', // 2.5rem
    '2xl': 'p-12', // 3rem
};

/**
 * Standard margin scale for the application
 */
export const MARGIN = {
    xs: 'm-2', // 0.5rem
    sm: 'm-4', // 1rem
    md: 'm-6', // 1.5rem
    lg: 'm-8', // 2rem
    xl: 'm-10', // 2.5rem
    '2xl': 'm-12', // 3rem
};

/**
 * Standard gap scale for the application (for flex and grid)
 */
export const GAP = {
    xs: 'gap-2', // 0.5rem
    sm: 'gap-4', // 1rem
    md: 'gap-6', // 1.5rem
    lg: 'gap-8', // 2rem
    xl: 'gap-10', // 2.5rem
    '2xl': 'gap-12', // 3rem
};

/**
 * Widget spacing - defines standard spacing for widget components
 */
export const WIDGET_SPACING = {
    wrapper: 'p-4 md:p-6', // Container padding
    header: 'mb-4',        // Space after header
    content: 'py-4',       // Content vertical padding
    footer: 'mt-4 pt-4',   // Footer top spacing and padding
    between: 'space-y-4',  // Space between elements
};

/**
 * Layout spacing - defines standard spacing for layout components
 */
export const LAYOUT_SPACING = {
    section: 'mb-8 md:mb-12',   // Major sections
    container: 'px-4 py-6 md:px-6 md:py-8', // Container padding
    pageHeader: 'mb-6 md:mb-8', // Page header bottom margin
    divider: 'my-6',            // Vertical spacing for dividers
};

/**
 * Grid spacing - defines standard spacing for grid layouts
 */
export const GRID_SPACING = {
    standard: 'gap-6 md:gap-8', // Standard grid gap
    tight: 'gap-4',             // Tighter grid for dense layouts
    loose: 'gap-8 md:gap-12',   // Looser grid for more space
};

/**
 * Responsive spacing classes - applies different spacing at different breakpoints
 */
export const RESPONSIVE_SPACING = {
    container: 'px-4 py-6 md:px-6 md:py-8 lg:px-8 lg:py-10',
    section: 'mb-6 md:mb-8 lg:mb-12',
    gridGap: 'gap-4 md:gap-6 lg:gap-8',
    itemMargin: 'mb-4 md:mb-6',
};

/**
 * Helper function to generate spacing class utilities
 * @param {string} direction - 'p', 'm', 'gap', etc.
 * @param {'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'} size - The size value
 * @returns {string} Tailwind class
 */
export function getSpacing(direction, size) {
    const sizeValue = {
        'xs': '2',  // 0.5rem
        'sm': '4',  // 1rem
        'md': '6',  // 1.5rem
        'lg': '8',  // 2rem
        'xl': '10', // 2.5rem
        '2xl': '12', // 3rem
    };

    return `${direction}-${sizeValue[size] || '4'}`;
}

/**
 * Returns widget spacing classes based on component type
 * @param {'card' | 'list' | 'compact'} type - Widget type
 * @returns {Object} Spacing classes for the widget
 */
export function getWidgetSpacing(type = 'card') {
    const spacingMap = {
        'card': {
            wrapper: 'p-4 md:p-6',
            header: 'mb-4',
            content: 'py-4',
            footer: 'mt-4 pt-4',
        },
        'list': {
            wrapper: 'p-3 md:p-4',
            header: 'mb-3',
            content: 'py-3',
            footer: 'mt-3 pt-3',
        },
        'compact': {
            wrapper: 'p-2 md:p-3',
            header: 'mb-2',
            content: 'py-2',
            footer: 'mt-2 pt-2',
        }
    };

    return spacingMap[type] || spacingMap.card;
} 