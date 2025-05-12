import { browser } from '$app/environment';
import { writable } from 'svelte/store';

/**
 * @typedef {'light' | 'dark' | 'system' | 'new' | 'nightwave'} ThemeValue
 */

/**
 * @typedef {Object} ThemeInfo
 * @property {ThemeValue} value - Theme identifier
 * @property {string} name - Display name
 * @property {string} description - Short description of the theme
 * @property {string} icon - Emoji or icon for the theme
 */

// Initialize from localStorage if available, default to 'dark' otherwise
const defaultTheme = 'dark';

/**
 * Get the initial theme value from localStorage or use the default
 * @returns {ThemeValue} The initial theme value
 */
function getInitialTheme() {
  if (browser) {
    try {
      const savedTheme = localStorage.getItem('theme');
      // Only return if it's a valid theme value
      if (savedTheme && ['light', 'dark', 'system', 'new', 'nightwave'].includes(savedTheme)) {
        return /** @type {ThemeValue} */ (savedTheme);
      }
    } catch (e) {
      console.error('Error reading theme from localStorage:', e);
    }
  }
  return defaultTheme;
}

/**
 * Svelte store for the current theme.
 * @type {import('svelte/store').Writable<ThemeValue>}
 */
export const theme = writable(getInitialTheme());

/**
 * Set the theme and persist to localStorage
 * @param {ThemeValue} value - The theme value to set
 */
export function setTheme(value) {
  if (browser) {
    try {
      console.log(`Setting theme to: ${value}`);
      // First update DOM
      document.documentElement.setAttribute('data-theme', value);
      // Then persist to localStorage
      localStorage.setItem('theme', value);
      // Then update store
      theme.set(value);
    } catch (e) {
      console.error('Error setting theme:', e);
    }
  }
}

/**
 * Initialize theme on load if browser environment
 */
if (browser) {
  const initialTheme = getInitialTheme();
  console.log('Initial theme:', initialTheme);
  document.documentElement.setAttribute('data-theme', initialTheme);
  
  // Subscribe to system preference changes if using 'system' theme
  if (initialTheme === 'system') {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    const applySystemTheme = (e) => {
      const systemTheme = e.matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-system-theme', systemTheme);
    };
    
    // Apply initially
    applySystemTheme(prefersDark);
    
    // Listen for changes
    prefersDark.addEventListener('change', applySystemTheme);
  }
}

/**
 * Get list of available themes
 * @returns {ThemeInfo[]} Array of available themes
 */
export function getAvailableThemes() {
  return [
    {
      value: 'light',
      name: 'Light',
      description: 'Clean bright interface',
      icon: '☀️'
    },
    {
      value: 'dark',
      name: 'Dark',
      description: 'Standard dark mode',
      icon: '🌙'
    },
    {
      value: 'system',
      name: 'System',
      description: 'Follow system preference',
      icon: '💻'
    },
    {
      value: 'new',
      name: 'New Theme',
      description: 'Experimental new colors',
      icon: '🎨'
    },
    {
      value: 'nightwave',
      name: 'Nightwave',
      description: 'Black & charcoal with electric blue accents',
      icon: '🌊'
    }
  ];
}