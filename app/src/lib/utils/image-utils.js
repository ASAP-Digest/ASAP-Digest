/**
 * Utilities for image optimization and management
 * @module utils/image-utils
 */

/**
 * @typedef {Object} ImageSize
 * @property {number} width - Image width
 * @property {number} height - Image height
 */

/**
 * @typedef {Object} ImageDetails
 * @property {string} sourceUrl - URL to the image
 * @property {string} [source_url] - URL to the image (snake_case version)
 * @property {Object} [mediaDetails] - Media details from WordPress
 * @property {number} [mediaDetails.width] - Original width
 * @property {number} [mediaDetails.height] - Original height
 * @property {Object[]} [mediaDetails.sizes] - Available sizes
 */

/**
 * Default image sizes used in the app
 */
export const IMAGE_SIZES = {
  THUMBNAIL: { width: 150, height: 150 },
  SMALL: { width: 300, height: 200 },
  MEDIUM: { width: 600, height: 400 },
  LARGE: { width: 1200, height: 800 }
};

/**
 * Default placeholder image URL
 */
export const DEFAULT_PLACEHOLDER = '/images/placeholder.jpg';

/**
 * Get optimal image URL based on requested size
 * 
 * @param {ImageDetails|string|null} image - Image details or URL
 * @param {ImageSize|number} size - Desired size (width/height object or width as number)
 * @param {number} [height] - Optional height if width provided as number
 * @returns {string} - Optimal image URL
 */
export function getOptimalImageUrl(image, size, height) {
  // Handle missing image
  if (!image) {
    return DEFAULT_PLACEHOLDER;
  }
  
  // Handle string URLs directly
  if (typeof image === 'string') {
    return image;
  }
  
  // Normalize size parameter
  let targetWidth, targetHeight;
  if (typeof size === 'object') {
    targetWidth = size.width;
    targetHeight = size.height;
  } else {
    targetWidth = size;
    targetHeight = height || targetWidth;
  }
  
  // Get the source URL from the image object
  const sourceUrl = image.sourceUrl || image.source_url || DEFAULT_PLACEHOLDER;
  
  // WordPress already has built-in resizing via URL parameters
  if (sourceUrl.includes('wp-content/uploads') && !sourceUrl.includes('?')) {
    return `${sourceUrl}?w=${targetWidth}&h=${targetHeight}&crop=1`;
  }
  
  // Return the original URL if we can't optimize
  return sourceUrl;
}

/**
 * Get image dimensions from an image object
 * 
 * @param {ImageDetails|string|null} image - Image details or URL
 * @returns {ImageSize} - Image dimensions
 */
export function getImageDimensions(image) {
  if (!image || typeof image === 'string') {
    return { width: 0, height: 0 };
  }
  
  if (image.mediaDetails) {
    return {
      width: image.mediaDetails.width || 0,
      height: image.mediaDetails.height || 0
    };
  }
  
  return { width: 0, height: 0 };
}

/**
 * Calculate responsive sizes based on container width
 * 
 * @param {number} containerWidth - Width of the container
 * @returns {Object} - Object with sizes for different breakpoints
 */
export function getResponsiveSizes(containerWidth) {
  return {
    xs: Math.min(containerWidth, 300),
    sm: Math.min(containerWidth, 600),
    md: Math.min(containerWidth, 900),
    lg: Math.min(containerWidth, 1200)
  };
}

/**
 * Generate srcset string for responsive images
 * 
 * @param {ImageDetails|string} image - Image details or URL
 * @param {number[]} widths - Array of widths for srcset
 * @returns {string} - Formatted srcset attribute value
 */
export function generateSrcset(image, widths = [300, 600, 900, 1200]) {
  if (!image || typeof image === 'string') {
    return '';
  }
  
  return widths
    .map(width => {
      const url = getOptimalImageUrl(image, { width, height: Math.round(width * 2/3) });
      return `${url} ${width}w`;
    })
    .join(', ');
}

/**
 * Generate a CSS background-image with proper fallback
 * 
 * @param {ImageDetails|string|null} image - Image details or URL
 * @param {ImageSize} [size] - Optional desired size
 * @returns {string} - CSS background-image value
 */
export function getCssBackgroundImage(image, size) {
  const url = getOptimalImageUrl(image, size || IMAGE_SIZES.MEDIUM);
  return `background-image: url('${url}');`;
} 