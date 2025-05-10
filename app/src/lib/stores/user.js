import { writable } from 'svelte/store';
import { browser } from '$app/environment';

/**
 * Avatar source preference
 * @typedef {'synced' | 'profile' | 'gravatar' | 'default'} AvatarPreference
 */

/**
 * User preferences object
 * @typedef {Object} UserPreferences
 * @property {AvatarPreference} avatarSource - Source to use for user avatar
 * @property {boolean} [useGravatar] - Legacy property for backwards compatibility
 * @property {Object} [display] - Display preferences
 * @property {boolean} [display.darkMode] - Use dark mode
 * @property {string} [display.theme] - Theme preference
 * @property {Object} [notifications] - Notification preferences
 * @property {boolean} [notifications.digest] - Receive digest notifications
 * @property {boolean} [notifications.push] - Enable push notifications
 * @property {boolean} [notifications.email] - Receive email notifications
 * @property {Object} [tts] - Text-to-speech preferences
 * @property {string} [tts.voice] - Preferred TTS voice
 * @property {number} [tts.rate] - Preferred TTS speech rate
 * @property {string} [tts.language] - Preferred TTS language
 * @property {boolean} [tts.autoPlay] - Auto-play TTS for content
 */

/**
 * Subscription plan details
 * @typedef {Object} UserPlan
 * @property {string} name - Plan name (Free, Spark, Pulse, Bolt)
 * @property {string} [level] - Numerical level (1-4)
 * @property {Date} [startDate] - When subscription started
 * @property {Date} [endDate] - When subscription ends
 * @property {Date} [trialEndDate] - When trial period ends
 * @property {boolean} [isActive] - If subscription is active
 * @property {string} [paymentStatus] - Current payment status
 */

/**
 * User analytics and progress data
 * @typedef {Object} UserStats
 * @property {number} [digestsRead] - Number of digests read
 * @property {number} [widgetsExplored] - Number of widgets explored
 * @property {Date} [lastActive] - Last active timestamp
 * @property {Object} [usage] - API usage metrics
 * @property {number} [usage.digestsRemaining] - Remaining digests
 * @property {number} [usage.searchesRemaining] - Remaining searches
 */

/**
 * @typedef {Object} User
 * @property {string} id - User identifier
 * @property {string} email - User email address
 * @property {string} [displayName] - User display name
 * @property {string[]} [roles] - User roles/permissions
 * @property {string} [avatarUrl] - URL to user avatar
 * @property {string} [gravatarUrl] - URL to gravatar image
 * @property {UserPreferences} [preferences] - User preferences
 * @property {UserPlan} [plan] - Subscription plan info
 * @property {UserStats} [stats] - User analytics and progress
 * @property {Object} [metadata] - Additional metadata
 * @property {string} [updatedAt] - When user data was last updated
 */

/** @type {import('svelte/store').Writable<User|null>} */
export const user = writable(null); 

/**
 * Create a Gravatar URL from an email address
 * 
 * @param {string|null} email The email address
 * @returns {string} The Gravatar URL
 */
export function createGravatarUrl(email) {
  if (!email) return '/images/default-avatar.svg';
  
  // Simple hash function for demo purposes
  // In production, use a proper MD5 implementation
  let hash = '';
  for (let i = 0; i < email.length; i++) {
    hash += email.charCodeAt(i).toString(16);
  }
  
  return `https://secure.gravatar.com/avatar/${hash}?s=300&d=mp&r=g`;
}

/**
 * Get the appropriate avatar URL based on user preferences
 * 
 * @param {object} user The user object
 * @returns {string} The avatar URL
 */
export function getAvatarUrl(user) {
  if (!user) return '/images/default-avatar.svg';
  
  // Debug logging but with more useful info
  console.debug('[Avatar Debug] User avatar data:', {
    url: user.avatarUrl,
    email: user.email,
    pref: user.preferences?.avatarSource
  });
  
  // PRIORITY 1: Always check for WordPress/auth synced avatar
  // This is the top priority - if we have an avatarUrl that looks like a synced one
  if (user.avatarUrl) {
    // These are common patterns for synced avatars from various services
    const syncedPatterns = [
      'gravatar.com',
      'googleusercontent.com',
      'linkedin.com',
      'wp-content/uploads',
      'wp-avatar',
      'wordpress',
      '?s=96',
      'mm&r=g'  // Common in Gravatar URLs
    ];
    
    // If URL contains any of the synced patterns, it's probably a synced avatar
    const isSyncedAvatar = syncedPatterns.some(pattern => 
      user.avatarUrl.includes(pattern)
    );
    
    // If we have a synced avatar and user preference is set to 'synced' (or not set)
    if (isSyncedAvatar && (!user.preferences?.avatarSource || user.preferences.avatarSource === 'synced')) {
      console.debug('[Avatar] Using synced avatar from WP/auth:', user.avatarUrl);
      return user.avatarUrl;
    }
  }
  
  // For other preferences, check user preferences if available
  if (user.preferences?.avatarSource) {
    switch(user.preferences.avatarSource) {
      case 'synced':
        // If preference is synced but we don't have a synced avatar yet, try to use gravatar
        if (user.avatarUrl) {
          console.debug('[Avatar] Using avatarUrl for synced preference:', user.avatarUrl);
          return user.avatarUrl;
        }
        // Fall back to gravatar
        console.debug('[Avatar] No synced avatar available, falling back to gravatar');
        return user.gravatarUrl || (user.email ? createGravatarUrl(user.email) : '/images/default-avatar.svg');
        
      case 'profile':
        // For profile preference, use custom uploaded avatarUrl if available
        if (user.avatarUrl && !user.avatarUrl.includes('gravatar.com')) {
          console.debug('[Avatar] Using custom profile avatar:', user.avatarUrl);
          return user.avatarUrl;
        }
        console.debug('[Avatar] No custom profile avatar available, using default');
        return '/images/default-avatar.svg';
        
      case 'gravatar':
        console.debug('[Avatar] Using gravatar preference');
        return user.gravatarUrl || (user.email ? createGravatarUrl(user.email) : '/images/default-avatar.svg');
        
      case 'default':
        console.debug('[Avatar] Using default avatar by preference');
        return '/images/default-avatar.svg';
    }
  }
  
  // If no specific preference or preference not recognized,
  // fall back to synced > avatarUrl > gravatar > default priority
  if (user.avatarUrl) {
    console.debug('[Avatar] No specific preference, using available avatarUrl:', user.avatarUrl);
    return user.avatarUrl;
  }
  
  console.debug('[Avatar] Falling back to gravatar or default');
  return user.gravatarUrl || (user.email ? createGravatarUrl(user.email) : '/images/default-avatar.svg');
}

/**
 * Get a shortened display name for UI presentation
 * 
 * @param {object} user The user object 
 * @returns {string} A shortened name for display
 */
export function getShortDisplayName(user) {
  if (!user) return 'User';
  
  if (user.displayName) {
    // Get first name or first two characters
    const firstPart = user.displayName.split(' ')[0];
    return firstPart.length > 12 ? firstPart.substring(0, 12) + '...' : firstPart;
  }
  
  if (user.email) {
    // Get username part of email
    const username = user.email.split('@')[0];
    return username.length > 12 ? username.substring(0, 12) + '...' : username;
  }
  
  return 'User';
}

// Create the auth store (used for local auth state)
const initialLocalValue = browser && localStorage.getItem('authUser') 
  ? JSON.parse(localStorage.getItem('authUser') || '{}') 
  : null;

if (browser && initialLocalValue) {
  console.log('[Auth Store] Initialized from localStorage:', initialLocalValue.id, initialLocalValue.email);
}

// Create the writeable store
export const userStore = writable(initialLocalValue);

// Create the root store for general use (typically synced with authStore)
export const userRootStore = writable(null);

/**
 * Sync data between authStore and user store
 * This ensures both stores have the same data for consistency
 * @param {Object|null} userData - User data to sync
 */
function syncUserStores(userData) {
  if (browser) {
    // Get current user data from store without triggering a subscription
    let currentUserData = null;
    userRootStore.subscribe(value => {
      currentUserData = value;
    })();
    
    // Only update if the data has actually changed
    if (JSON.stringify(userData) !== JSON.stringify(currentUserData)) {
      console.log('[Auth Store] Syncing user data to userRootStore');
      userRootStore.set(userData || null);
    } else {
      console.log('[Auth Store] User data unchanged, skipping sync');
    }
  }
}

// On authStore change, update localStorage and userRootStore
userStore.subscribe(user => {
  if (browser) {
    // Only persist to localStorage if there's data to persist
    if (user?.id) {
      localStorage.setItem('authUser', JSON.stringify(user));
      console.log('[Auth Store] Persisted to localStorage:', user.id, user.email);
    } else if (user === null) {
      localStorage.removeItem('authUser');
      console.log('[Auth Store] Removed from localStorage');
    }
    
    // Sync with userRootStore
    syncUserStores(user);
  }
});

/**
 * Updates user data in authStore
 * 
 * @param {Object|null} userData - New user data
 */
export function updateUserData(userData) {
  console.log('[Auth Store] Updating user data:', userData?.id || 'null');
  userStore.set(userData);
}

/**
 * Clear user data from authStore and localStorage
 */
export function clearUserData() {
  console.log('[Auth Store] Clearing user data');
  updateUserData(null);
}

export default { 
  store: userStore, 
  rootStore: userRootStore,
  updateUserData, 
  clearUserData,
  getAvatarUrl,
  getShortDisplayName
}; 