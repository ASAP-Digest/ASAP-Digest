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
 * Calculate the optimal image dimensions based on the desired width/height and the original image dimensions
 * @param {Object} originalDimensions - Original image dimensions
 * @param {number} originalDimensions.width - Original width
 * @param {number} originalDimensions.height - Original height
 * @param {Object} desiredDimensions - Desired dimensions
 * @param {number} [desiredDimensions.width] - Desired width
 * @param {number} [desiredDimensions.height] - Desired height
 * @returns {Object} Optimal dimensions that maintain aspect ratio
 */
export function calculateOptimalDimensions(originalDimensions, desiredDimensions) {
  const { width: originalWidth, height: originalHeight } = originalDimensions;
  const { width: desiredWidth, height: desiredHeight } = desiredDimensions;
  
  // If both dimensions are specified, return the desired dimensions
  if (desiredWidth && desiredHeight) {
    return { width: desiredWidth, height: desiredHeight };
  }
  
  // If no dimensions are specified, return the original dimensions
  if (!desiredWidth && !desiredHeight) {
    return { width: originalWidth, height: originalHeight };
  }
  
  // Calculate aspect ratio
  const aspectRatio = originalWidth / originalHeight;
  
  // If only width is specified, calculate height based on aspect ratio
  if (desiredWidth && !desiredHeight) {
    return {
      width: desiredWidth,
      height: Math.round(desiredWidth / aspectRatio)
    };
  }
  
  // If only height is specified, calculate width based on aspect ratio
  if (!desiredWidth && desiredHeight) {
    return {
      width: Math.round(desiredHeight * aspectRatio),
      height: desiredHeight
    };
  }
  
  // Should never reach here, but just in case
  return { width: originalWidth, height: originalHeight };
}

/**
 * Get an optimized image URL for the given image
 * @param {Object} image - Image object with sourceUrl and mediaDetails
 * @param {Object} [options] - Options for the optimized image
 * @param {number} [options.width] - Desired width
 * @param {number} [options.height] - Desired height
 * @param {string} [options.fit='cover'] - How the image should fit (cover, contain, fill)
 * @param {string} [options.format='webp'] - Desired image format (webp, jpg, png)
 * @returns {string} Optimized image URL
 */
export function getImageUrl(image, options = {}) {
  if (!image || !image.sourceUrl) {
    return '';
  }
  
  const { sourceUrl } = image;
  const mediaDetails = image.mediaDetails || { width: 0, height: 0 };
  const originalDimensions = {
    width: mediaDetails.width || 800,
    height: mediaDetails.height || 600
  };
  
  // Default options
  const {
    width,
    height,
    fit = 'cover',
    format = 'webp'
  } = options;
  
  // Calculate optimal dimensions
  const dimensions = calculateOptimalDimensions(
    originalDimensions,
    { width, height }
  );
  
  // Check if WordPress has already generated a scaled version with these dimensions
  if (image.sizes && Array.isArray(image.sizes)) {
    const matchingSize = image.sizes.find(size => {
      // Allow a margin of error of 5px
      return Math.abs(size.width - dimensions.width) <= 5 &&
             Math.abs(size.height - dimensions.height) <= 5;
    });
    
    if (matchingSize) {
      return matchingSize.sourceUrl;
    }
  }
  
  // If not using a service or dynamic resizing
  return sourceUrl;
  
  // If using a service like Cloudinary (add this as needed)
  /*
  return `https://res.cloudinary.com/your-account/image/fetch/w_${dimensions.width},h_${dimensions.height},c_${fit}/${encodeURIComponent(sourceUrl)}`;
  */
}

/**
 * Generate a blurhash or LQIP (Low Quality Image Placeholder) URL
 * @param {string} sourceUrl - Original image URL
 * @param {Object} [options] - Options for the placeholder
 * @param {number} [options.width=20] - Width of placeholder
 * @param {number} [options.height=20] - Height of placeholder
 * @param {number} [options.quality=30] - Quality of placeholder (1-100)
 * @returns {string} Placeholder URL
 */
export function getImagePlaceholder(sourceUrl, options = {}) {
  if (!sourceUrl) {
    return '';
  }
  
  // Default options
  const {
    width = 20,
    height = 20,
    quality = 30
  } = options;
  
  // Simple LQIP approach - resize to very small dimensions with low quality
  // This assumes the server supports dynamic resizing
  return `${sourceUrl}?w=${width}&h=${height}&q=${quality}`;
  
  // If using a service like Cloudinary (add this as needed)
  /*
  return `https://res.cloudinary.com/your-account/image/fetch/w_${width},h_${height},q_${quality}/f_auto/${encodeURIComponent(sourceUrl)}`;
  */
}

/**
 * Get responsive image srcset
 * @param {Object} image - Image object with sourceUrl and mediaDetails
 * @param {Object} [options] - Options for the srcset
 * @param {number[]} [options.widths=[320, 640, 960, 1280, 1920]] - Widths to include in srcset
 * @param {string} [options.format='webp'] - Image format
 * @returns {string} srcset attribute value
 */
export function getImageSrcset(image, options = {}) {
  if (!image || !image.sourceUrl) {
    return '';
  }
  
  const { sourceUrl } = image;
  const mediaDetails = image.mediaDetails || { width: 0, height: 0 };
  const originalDimensions = {
    width: mediaDetails.width || 800,
    height: mediaDetails.height || 600
  };
  
  // Default options
  const {
    widths = [320, 640, 960, 1280, 1920],
    format = 'webp'
  } = options;
  
  // Filter widths to only include those smaller than the original
  const filteredWidths = widths.filter(w => w <= originalDimensions.width);
  
  // Always include the original width
  if (!filteredWidths.includes(originalDimensions.width)) {
    filteredWidths.push(originalDimensions.width);
  }
  
  // Sort widths
  filteredWidths.sort((a, b) => a - b);
  
  // Generate srcset
  return filteredWidths.map(width => {
    const height = Math.round(width / (originalDimensions.width / originalDimensions.height));
    const url = getImageUrl(image, { width, height, format });
    return `${url} ${width}w`;
  }).join(', ');
}

/**
 * Get a responsive sizes attribute based on the image's intended usage
 * @param {string} usage - How the image will be used (e.g., 'hero', 'thumbnail', 'gallery')
 * @returns {string} sizes attribute value
 */
export function getImageSizes(usage = 'content') {
  switch (usage) {
    case 'hero':
      return '(min-width: 1280px) 1280px, 100vw';
    case 'thumbnail':
      return '(min-width: 1024px) 300px, (min-width: 768px) 250px, 200px';
    case 'gallery':
      return '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw';
    case 'content':
    default:
      return '(min-width: 1024px) 800px, (min-width: 768px) 90vw, 100vw';
  }
}

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