/**
 * @fileoverview Global JSDoc definitions for Svelte components
 */

/**
 * @typedef {Object} SvelteComponent
 * @property {Function} $destroy - Destroys the component
 * @property {Function} $set - Sets component properties
 * @property {Function} $on - Subscribes to component events
 */

// Add a module declaration for .svelte files
// This tells JSDoc that .svelte files should be treated as SvelteComponent exports
/**
 * @typedef {Object} SvelteComponentModule
 * @property {SvelteComponent} default - The default exported component
 */

/**
 * Helper comment to use before svelte imports:
 * // @ts-ignore - Svelte component import
 */

export {}; 