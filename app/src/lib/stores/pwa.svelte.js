/**
 * PWA store - Provides state for PWA installation and status
 * This is a basic implementation with Svelte 5's runes
 */

// Internal state (not exported directly)
let _installPrompt = $state(null);
let _isInstallable = $state(false);
let _isPWA = $state(false);

// Exported functions returning the CURRENT VALUE of the internal state
/** @returns {any | null} */ // Use 'any' since we removed strict typing
export function getInstallPrompt() {
  // Return the value, NOT $derived(...)
  return _installPrompt; 
}
/** @returns {boolean} */
export function getIsInstallable() {
  // Return the value, NOT $derived(...)
  return _isInstallable;
}
/** @returns {boolean} */
export function getIsPWA() {
  // Return the value, NOT $derived(...)
  return _isPWA;
}

// Check if we're running in a PWA context
function checkIsPWA() {
  if (typeof window === 'undefined') return false;
  // Add type check for non-standard navigator.standalone
  const standalone = 'standalone' in navigator ? /** @type {{standalone?: boolean}} */ (navigator).standalone : false;
  return window.matchMedia('(display-mode: standalone)').matches || standalone === true;
}

// Initialize on import and update internal state
if (typeof window !== 'undefined') {
  // Check if we're in a PWA
  _isPWA = checkIsPWA(); // Update internal state
  
  // Listen for beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', (/** @type {any} */ e) => {
    e.preventDefault();
    _installPrompt = e; // Update internal state
    _isInstallable = true; // Update internal state
  });
  
  // Listen for appinstalled event
  window.addEventListener('appinstalled', () => {
    _installPrompt = null; // Update internal state
    _isInstallable = false; // Update internal state
    _isPWA = true; // Update internal state
  });
} 