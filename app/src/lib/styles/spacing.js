/**
 * ASAP Digest Spacing Utilities
 * 
 * This file provides consistent spacing utilities for the application
 * to ensure proper margins and padding between components following the 8pt grid.
 */

/**
 * Standard spacing scale for the application based on the 8pt grid
 */
export const SPACING = {
    xs: 'p-1', // 4px (8pt grid exception for micro spacing)
    sm: 'p-2', // 8px
    md: 'p-4', // 16px
    lg: 'p-6', // 24px
    xl: 'p-8', // 32px
    '2xl': 'p-12', // 48px
};

/**
 * Standard margin scale for the application based on the 8pt grid
 */
export const MARGIN = {
    xs: 'm-1', // 4px (8pt grid exception for micro spacing)
    sm: 'm-2', // 8px
    md: 'm-4', // 16px
    lg: 'm-6', // 24px
    xl: 'm-8', // 32px
    '2xl': 'm-12', // 48px
};

/**
 * Standard gap scale for the application (for flex and grid) based on the 8pt grid
 */
export const GAP = {
    xs: 'gap-1', // 4px (8pt grid exception for micro spacing)
    sm: 'gap-2', // 8px
    md: 'gap-4', // 16px
    lg: 'gap-6', // 24px
    xl: 'gap-8', // 32px
    '2xl': 'gap-12', // 48px
};

/**
 * Widget spacing - defines standard spacing for widget components
 * All values follow the 8pt grid system
 */
export const WIDGET_SPACING = {
    wrapper: 'p-4 md:p-6', // Container padding (16px, md: 24px)
    header: 'mb-4',        // Space after header (16px)
    content: 'py-4',       // Content vertical padding (16px)
    footer: 'mt-4 pt-4',   // Footer top spacing and padding (16px)
    between: 'space-y-4',  // Space between elements (16px)
};

/**
 * Layout spacing - defines standard spacing for layout components
 * All values follow the 8pt grid system
 */
export const LAYOUT_SPACING = {
    section: 'mb-8 md:mb-12',   // Major sections (32px, md: 48px)
    container: 'px-4 py-6 md:px-6 md:py-8', // Container padding (h:16px,v:24px, md: h:24px,v:32px)
    pageHeader: 'mb-6 md:mb-8', // Page header bottom margin (24px, md: 32px)
    divider: 'my-6',            // Vertical spacing for dividers (24px)
};

/**
 * Grid spacing - defines standard spacing for grid layouts
 * All values follow the 8pt grid system
 */
export const GRID_SPACING = {
    standard: 'gap-6 md:gap-8', // Standard grid gap (24px, md: 32px)
    tight: 'gap-4',             // Tighter grid for dense layouts (16px)
    loose: 'gap-8 md:gap-12',   // Looser grid for more space (32px, md: 48px)
};

/**
 * Responsive spacing classes - applies different spacing at different breakpoints
 * All values follow the 8pt grid system
 */
export const RESPONSIVE_SPACING = {
    container: 'px-4 py-6 md:px-6 md:py-8 lg:px-8 lg:py-10',
    section: 'mb-6 md:mb-8 lg:mb-12',
    gridGap: 'gap-4 md:gap-6 lg:gap-8',
    itemMargin: 'mb-4 md:mb-6',
};

/**
 * Helper function to generate spacing class utilities
 * All values follow the 8pt grid system (with xs as 4px exception)
 * @param {string} direction - 'p', 'm', 'gap', etc.
 * @param {'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'} size - The size value
 * @returns {string} Tailwind class
 */
export function getSpacing(direction, size) {
    const sizeValue = {
        'xs': '1',  // 4px (8pt grid exception)
        'sm': '2',  // 8px
        'md': '4',  // 16px
        'lg': '6',  // 24px
        'xl': '8',  // 32px
        '2xl': '12', // 48px
    };

    return `${direction}-${sizeValue[size] || '4'}`;
}

/**
 * Returns widget spacing classes based on component type
 * All values follow the 8pt grid system
 * @param {'card' | 'list' | 'compact'} type - Widget type
 * @returns {Object} Spacing classes for the widget
 */
export function getWidgetSpacing(type = 'card') {
    const spacingMap = {
        'card': {
            wrapper: 'p-4 md:p-6',  // 16px, md: 24px
            header: 'mb-4',         // 16px
            content: 'py-4',        // 16px
            footer: 'mt-4 pt-4',    // 16px
        },
        'list': {
            wrapper: 'p-3',         // 12px
            header: 'mb-3',         // 12px
            content: 'py-3',        // 12px
            footer: 'mt-3 pt-3',    // 12px
        },
        'compact': {
            wrapper: 'p-2',         // 8px
            header: 'mb-2',         // 8px
            content: 'py-2',        // 8px
            footer: 'mt-2 pt-2',    // 8px
        }
    };

    return spacingMap[type] || spacingMap.card;
} 