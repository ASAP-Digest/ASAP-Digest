/**
 * A utility function to generate a CSS class with a dynamic color value
 * @param {string} prefix - The CSS class prefix (e.g., 'bg', 'text', 'border')
 * @param {string} color - The color name (e.g., 'blue', 'red', 'green')
 * @param {string|number} [shade=500] - The color shade (e.g., 100, 200, 300, etc.)
 * @returns {string} The full CSS class name
 */
export function colorClass(prefix, color, shade = 500) {
    return `${prefix}-${color}-${shade}`;
}

/**
 * A utility function to get the HSL variable name for a theme color
 * @param {string} colorName - The theme color name
 * @returns {string} The HSL variable CSS
 */
export function themeColor(colorName) {
    return `hsl(var(--${colorName}))`;
}

/**
 * Content type to color mapping
 * @typedef {Object} ContentTypeColorMap
 * @property {string} article - Color for articles
 * @property {string} podcast - Color for podcasts
 * @property {string} keyterm - Color for key terms
 * @property {string} financial - Color for financial content
 * @property {string} xpost - Color for X posts
 * @property {string} reddit - Color for Reddit content
 * @property {string} event - Color for events
 * @property {string} polymarket - Color for Polymarket content
 * @property {string} default - Default color for unknown types
 */

/**
 * Common color mappings for content types
 * @type {ContentTypeColorMap}
 */
export const contentTypeColors = {
    article: 'blue',
    podcast: 'purple',
    keyterm: 'amber',
    financial: 'green',
    xpost: 'sky',
    reddit: 'orange',
    event: 'rose',
    polymarket: 'indigo',
    default: 'gray'
};

/**
 * Get a color for a content type
 * @param {string} contentType - The content type ID
 * @returns {string} The corresponding color name
 */
export function getContentTypeColor(contentType) {
    // Use a safer approach for TypeScript
    if (contentType === 'article') return contentTypeColors.article;
    if (contentType === 'podcast') return contentTypeColors.podcast;
    if (contentType === 'keyterm') return contentTypeColors.keyterm;
    if (contentType === 'financial') return contentTypeColors.financial;
    if (contentType === 'xpost') return contentTypeColors.xpost;
    if (contentType === 'reddit') return contentTypeColors.reddit;
    if (contentType === 'event') return contentTypeColors.event;
    if (contentType === 'polymarket') return contentTypeColors.polymarket;

    return contentTypeColors.default;
} 