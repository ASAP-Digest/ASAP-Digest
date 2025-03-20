// Utility functions for working with Lucide icons in Svelte 5 runes mode

/**
 * Transforms a Lucide SVG icon component into a format that our Icon wrapper can use
 * @param name The name of the icon
 * @param svgContent The SVG content as a string (typically the paths)
 * @returns An object containing the icon name and SVG content
 */
export function createIconObject(name: string, svgContent: string) {
  return {
    name,
    svgContent
  };
}

/**
 * Extracts the SVG content from a Lucide icon component
 * This is a temporary workaround until Lucide officially supports Svelte 5 runes mode
 * @param iconElement An SVG element from a Lucide icon
 * @returns The inner SVG content as a string
 */
export function extractSvgContent(iconElement: SVGElement): string {
  if (!iconElement) return '';
  
  // Extract all child path, circle, rect, etc. elements
  const paths = iconElement.innerHTML || '';
  return paths;
} 