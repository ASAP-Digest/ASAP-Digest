// Utility functions for working with Lucide icons in Svelte 5 runes mode
import { browser } from '$app/environment';

/**
 * Creates a Svelte component from an icon definition
 * @param {string} name The name of the icon
 * @param {string} svgContent The SVG content as a string (typically the paths)
 * @returns {any} A Svelte component that renders the icon
 */
export function createIconObject(name, svgContent) {
  return {
    name,
    svgContent,
    toString() {
      return svgContent;
    }
  };
}

/**
 * Extracts the SVG content from a Lucide icon component
 * This is a temporary workaround until Lucide officially supports Svelte 5 runes mode
 * @param {SVGElement} iconElement An SVG element from a Lucide icon
 * @returns {string} The inner SVG content as a string
 */
export function extractSvgContent(iconElement) {
  if (!iconElement) return '';
  
  // Extract all child path, circle, rect, etc. elements
  const paths = iconElement.innerHTML || '';
  return paths;
} 