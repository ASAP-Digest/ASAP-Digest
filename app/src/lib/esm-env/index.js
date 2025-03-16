// Shim for esm-env package to resolve Svelte 5 dependency issues
export const DEV = process.env.NODE_ENV !== 'production';
export const BROWSER = typeof window !== 'undefined';
export const NODE = typeof process !== 'undefined' &&
    typeof process.versions !== 'undefined' &&
    typeof process.versions.node !== 'undefined'; 