/**
 * Image optimization utilities for ASAP Digest
 * Provides functions for optimizing and lazy-loading images
 */

// Default image sizes for responsive images
const DEFAULT_SIZES = [320, 640, 960, 1280, 1920];

/**
 * Generate srcset attribute for responsive images
 * @param {string} baseUrl - Base URL of the image
 * @param {Array<number>} sizes - Array of image widths
 * @param {string} format - Image format (webp, jpg, etc.)
 * @returns {string} - srcset attribute value
 */
export function generateSrcset(baseUrl, sizes = DEFAULT_SIZES, format = 'webp') {
    // Extract base filename without extension
    const urlParts = baseUrl.split('.');
    const extension = urlParts.pop();
    const basePath = urlParts.join('.');

    // If format is not specified, use the original extension
    const outputFormat = format || extension;

    // Generate srcset string
    return sizes
        .map(size => `${basePath}-${size}.${outputFormat} ${size}w`)
        .join(', ');
}

/**
 * Create a lazy-loaded image element
 * @param {Object} options - Image options
 * @param {string} options.src - Image source URL
 * @param {string} options.alt - Image alt text
 * @param {string} options.className - Additional CSS classes
 * @param {number} options.width - Image width
 * @param {number} options.height - Image height
 * @param {string} options.srcset - Image srcset attribute
 * @param {string} options.sizes - Image sizes attribute
 * @returns {HTMLImageElement} - Image element
 */
export function createLazyImage({
    src,
    alt = '',
    className = '',
    width,
    height,
    srcset = '',
    sizes = '100vw'
}) {
    // Create image element
    const img = document.createElement('img');

    // Set basic attributes
    img.alt = alt;
    img.className = `lazy ${className}`.trim();

    // Set dimensions if provided
    if (width) img.width = width;
    if (height) img.height = height;

    // Set data attributes for lazy loading
    img.dataset.src = src;
    img.loading = 'lazy';

    // Add low-quality placeholder
    img.src = generatePlaceholder(width, height);

    // Add srcset and sizes if provided
    if (srcset) {
        img.dataset.srcset = srcset;
        img.dataset.sizes = sizes;
    }

    return img;
}

/**
 * Generate a placeholder image
 * @param {number} width - Image width
 * @param {number} height - Image height
 * @param {string} color - Placeholder color
 * @returns {string} - Data URL for placeholder
 */
export function generatePlaceholder(width = 100, height = 100, color = '#f0f0f0') {
    // Create a small SVG placeholder
    const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
      <rect width="100%" height="100%" fill="${color}" />
    </svg>
  `;

    // Convert to base64 data URL
    return `data:image/svg+xml;base64,${btoa(svg.trim())}`;
}

/**
 * Optimize image loading in a container
 * @param {HTMLElement} container - Container element
 */
export function optimizeImagesInContainer(container) {
    // Find all images in container
    const images = container.querySelectorAll('img:not([loading="lazy"])');

    // Add lazy loading to each image
    images.forEach(img => {
        // Skip images that already have lazy loading
        if (img.hasAttribute('loading')) return;

        // Add lazy loading attribute
        img.loading = 'lazy';

        // Add class for fade-in effect
        img.classList.add('lazy');

        // Store original src in data attribute
        if (!img.dataset.src) {
            img.dataset.src = img.src;

            // Set placeholder
            if (img.width && img.height) {
                img.src = generatePlaceholder(img.width, img.height);
            }
        }
    });
}

/**
 * Convert an image to WebP format if supported
 * @param {string} url - Original image URL
 * @returns {string} - WebP URL if supported, original URL otherwise
 */
export function getWebPImageUrl(url) {
    // Check if WebP is supported
    const supportsWebP = localStorage.getItem('supportsWebP');

    if (supportsWebP === 'true') {
        // Convert URL to WebP
        const urlParts = url.split('.');
        const extension = urlParts.pop();

        // Only convert common image formats
        if (['jpg', 'jpeg', 'png'].includes(extension.toLowerCase())) {
            return `${urlParts.join('.')}.webp`;
        }
    }

    return url;
}

/**
 * Detect WebP support and store in localStorage
 */
export function detectWebPSupport() {
    if (typeof window === 'undefined') return;

    // Check if we've already detected support
    if (localStorage.getItem('supportsWebP') !== null) return;

    const canvas = document.createElement('canvas');
    if (canvas.getContext && canvas.getContext('2d')) {
        // Check if browser can create WebP images
        const supportsWebP = canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        localStorage.setItem('supportsWebP', supportsWebP.toString());
    } else {
        // Canvas not supported, assume WebP is not supported
        localStorage.setItem('supportsWebP', 'false');
    }
}

/**
 * Initialize image optimization
 */
export function initImageOptimization() {
    if (typeof window === 'undefined') return;

    // Detect WebP support
    detectWebPSupport();

    // Optimize all images in the document
    optimizeImagesInContainer(document.body);

    // Set up mutation observer to optimize new images
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (node.tagName === 'IMG') {
                            // Single image added
                            if (!node.hasAttribute('loading')) {
                                node.loading = 'lazy';
                                node.classList.add('lazy');
                            }
                        } else {
                            // Container with potential images added
                            optimizeImagesInContainer(node);
                        }
                    }
                });
            }
        });
    });

    // Start observing the document
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    return observer;
} 