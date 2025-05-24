/**
 * API Configuration Utility
 * Provides centralized API URL configuration
 */

import { dev } from '$app/environment';

/**
 * Get the API base URL for WordPress REST API calls
 * @returns {string} The base URL for API calls
 */
export function getApiUrl() {
  // Return the WordPress domain, not the SvelteKit app domain
  if (dev) {
    return 'https://asapdigest.local';
  }
  
  // In production, this should point to the WordPress domain
  return 'https://asapdigest.com';
}

/**
 * Get the full WordPress REST API base URL
 * @returns {string} The full REST API base URL
 */
export function getWpApiUrl() {
  return `${getApiUrl()}/wp-json`;
}

/**
 * Get the ASAP Digest API base URL
 * @returns {string} The ASAP Digest API base URL
 */
export function getAsapApiUrl() {
  return `${getWpApiUrl()}/asap/v1`;
} 