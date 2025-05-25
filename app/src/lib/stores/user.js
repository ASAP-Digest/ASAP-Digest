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
 * @property {number} [metadata.wp_user_id] - WordPress user ID for API calls
 * @property {string[]} [metadata.roles] - User roles from metadata
 * @property {number} [wp_user_id] - WordPress user ID for API calls (direct access)
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
  
  // Debug logging (only when preferences are missing)
  if (!user.preferences) {
    console.debug('[Avatar Debug] User missing preferences, using defaults:', {
      url: user.avatarUrl || 'none',
      email: user.email || 'none',
      hasPreferences: false
  });
  }
  
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

/**
 * Get the WordPress user ID from user object
 * Checks both direct wp_user_id property and metadata.wp_user_id
 * 
 * @param {object} user The user object
 * @returns {number|null} The WordPress user ID or null if not found
 */
export function getWordPressUserId(user) {
  if (!user) return null;
  
  // Check direct property first
  if (user.wp_user_id && typeof user.wp_user_id === 'number') {
    return user.wp_user_id;
  }
  
  // Check metadata
  if (user.metadata?.wp_user_id && typeof user.metadata.wp_user_id === 'number') {
    return user.metadata.wp_user_id;
  }
  
  console.debug('[WordPress User ID] Not found in user object:', {
    hasDirectProperty: !!user.wp_user_id,
    hasMetadata: !!user.metadata?.wp_user_id,
    userKeys: Object.keys(user)
  });
  
  return null;
}

/**
 * Get comprehensive user data with unified access to all user properties.
 * This function normalizes user data from various sources and provides consistent access patterns.
 * 
 * Returns an object with getters and methods for:
 * - Core identity: id, betterAuthId, email, wpUserId
 * - Display info: displayName, shortDisplayName, username, firstName, lastName, fullName
 * - Avatar & visual: avatarUrl, gravatarUrl
 * - Roles & permissions: roles, isAdmin, isEditor, hasRole(), primaryRole
 * - Subscription: plan, planName, isPremium
 * - Preferences: preferences, avatarPreference, theme, isDarkMode
 * - Statistics: stats, digestsRead, lastActive
 * - Sync status: syncStatus, isSynced, updatedAt, lastSynced
 * - Session: sessionToken, hasActiveSession
 * - Utilities: metadata, rawUser, hasMetadata(), getMetadata(), isValid, isComplete
 * - Debug: debugInfo, toJSON()
 * 
 * @param {object|null} user The user object from any source (Better Auth, WordPress, etc.)
 * @returns {object} Comprehensive user data object with getters and methods
 */
export function getUserData(user) {
  if (!user) {
    return createEmptyUserHelper();
  }

  return {
    // Core Identity
    get id() { return user.id || user.betterAuthId || null; },
    get betterAuthId() { return user.betterAuthId || user.id || null; },
    get email() { return user.email || null; },
    
    // WordPress Integration
    get wpUserId() { 
      return user.wpUserId || 
             user.wp_user_id || 
             user.metadata?.wpUserId ||
             user.metadata?.wp_user_id || 
             (typeof user.metadata?.wp_sync?.wp_user_id === 'number' ? user.metadata.wp_sync.wp_user_id : null);
    },
    
    // Display Information
    get displayName() { 
      return user.displayName || 
             user.name || 
             user.username || 
             (user.email ? user.email.split('@')[0] : 'User');
    },
    get shortDisplayName() { 
      const name = this.displayName;
      if (name.length > 12) {
        return name.substring(0, 12) + '...';
      }
      return name;
    },
    get username() { return user.username || user.name || null; },
    get firstName() { 
      return user.firstName || 
             user.metadata?.firstName || 
             user.metadata?.first_name || 
             null;
    },
    get lastName() { 
      return user.lastName || 
             user.metadata?.lastName || 
             user.metadata?.last_name || 
             null;
    },
    get fullName() {
      const first = this.firstName;
      const last = this.lastName;
      if (first && last) return `${first} ${last}`;
      if (first) return first;
      if (last) return last;
      return this.displayName;
    },
    
    // Avatar & Visual Identity
    get avatarUrl() { 
      return getAvatarUrl(user);
    },
    get gravatarUrl() { 
      return user.gravatarUrl || 
             (user.email ? createGravatarUrl(user.email) : null);
    },
    
    // Roles & Permissions
    get roles() { 
      // Priority: direct roles > metadata.roles > default
      if (Array.isArray(user.roles) && user.roles.length > 0) {
        return user.roles;
      }
      if (Array.isArray(user.metadata?.roles) && user.metadata.roles.length > 0) {
        return user.metadata.roles;
      }
      return ['subscriber']; // Default role
    },
    get isAdmin() { 
      return this.roles.includes('administrator') || this.roles.includes('admin');
    },
    get isEditor() { 
      return this.roles.includes('editor') || this.isAdmin;
    },
    hasRole(role) { 
      return this.roles.includes(role);
    },
    get primaryRole() {
      const roleHierarchy = ['administrator', 'admin', 'editor', 'author', 'contributor', 'subscriber'];
      for (const role of roleHierarchy) {
        if (this.roles.includes(role)) return role;
      }
      return this.roles[0] || 'subscriber';
    },
    
    // Subscription & Plan
    get plan() { 
      if (typeof user.plan === 'object' && user.plan?.name) {
        return user.plan;
      }
      if (typeof user.plan === 'string') {
        return { name: user.plan };
      }
      if (user.metadata?.plan) {
        return typeof user.metadata.plan === 'object' ? user.metadata.plan : { name: user.metadata.plan };
      }
      return { name: 'Free' };
    },
    get planName() { 
      return this.plan.name || 'Free';
    },
    get isPremium() { 
      const premiumPlans = ['Spark', 'Pulse', 'Bolt', 'Pro', 'Premium'];
      return premiumPlans.includes(this.planName);
    },
    
    // Preferences
    get preferences() { 
      return user.preferences || user.metadata?.preferences || {};
    },
    get avatarPreference() { 
      return this.preferences.avatarSource || 'synced';
    },
    get theme() { 
      return this.preferences.display?.theme || 'light';
    },
    get isDarkMode() { 
      return this.preferences.display?.darkMode || false;
    },
    
    // Statistics & Usage
    get stats() { 
      return user.stats || user.metadata?.stats || {};
    },
    get digestsRead() { 
      return this.stats.digestsRead || 0;
    },
    get lastActive() { 
      return user.lastActive || 
             user.metadata?.lastActive || 
             user.updatedAt || 
             null;
    },
    
    // Sync & Status Information
    get syncStatus() { 
      return user.syncStatus || 
             user.metadata?.syncStatus || 
             (this.wpUserId ? 'synced' : 'pending');
    },
    get isSynced() { 
      return this.syncStatus === 'synced' && !!this.wpUserId;
    },
    get updatedAt() { 
      return user.updatedAt || user.metadata?.updatedAt || null;
    },
    get lastSynced() { 
      return user.lastSynced || 
             user.metadata?.lastSynced || 
             user.metadata?.wp_sync?.synced_at || 
             null;
    },
    
    // Session Information
    get sessionToken() { 
      return user.sessionToken || user.metadata?.sessionToken || null;
    },
    get hasActiveSession() { 
      return !!this.sessionToken;
    },
    
    // Raw Data Access
    get metadata() { 
      return user.metadata || {};
    },
    get rawUser() { 
      return user;
    },
    
    // Utility Methods
    hasMetadata(key) { 
      return key in this.metadata;
    },
    getMetadata(key, defaultValue = null) { 
      return this.metadata[key] ?? defaultValue;
    },
    
    // Validation Methods
    get isValid() { 
      return !!(this.id && this.email);
    },
    get isComplete() { 
      return !!(this.id && this.email && this.displayName && this.wpUserId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        betterAuthId: this.betterAuthId,
        wpUserId: this.wpUserId,
        email: this.email,
        displayName: this.displayName,
        roles: this.roles,
        syncStatus: this.syncStatus,
        isSynced: this.isSynced,
        isValid: this.isValid,
        isComplete: this.isComplete,
        hasMetadata: Object.keys(this.metadata).length > 0,
        metadataKeys: Object.keys(this.metadata)
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        betterAuthId: this.betterAuthId,
        email: this.email,
        displayName: this.displayName,
        username: this.username,
        avatarUrl: this.avatarUrl,
        roles: this.roles,
        plan: this.plan,
        preferences: this.preferences,
        stats: this.stats,
        wpUserId: this.wpUserId,
        wp_user_id: this.wpUserId, // Include both for compatibility
        syncStatus: this.syncStatus,
        updatedAt: this.updatedAt,
        metadata: this.metadata
      };
    }
  };
}

/**
 * Creates an empty user helper for null/undefined users
 * @returns {object} Empty user helper with safe defaults
 */
function createEmptyUserHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get betterAuthId() { return null; },
    get email() { return null; },
    
    // WordPress Integration
    get wpUserId() { return null; },
    
    // Display Information
    get displayName() { return 'Guest'; },
    get shortDisplayName() { return 'Guest'; },
    get username() { return null; },
    get firstName() { return null; },
    get lastName() { return null; },
    get fullName() { return 'Guest'; },
    
    // Avatar & Visual Identity
    get avatarUrl() { return '/images/default-avatar.svg'; },
    get gravatarUrl() { return null; },
    
    // Roles & Permissions
    get roles() { return []; },
    get isAdmin() { return false; },
    get isEditor() { return false; },
    hasRole(role) { 
      return this.roles.includes(role);
    },
    get primaryRole() { return 'guest'; },
    
    // Subscription & Plan
    get plan() { return { name: 'Free' }; },
    get planName() { return 'Free'; },
    get isPremium() { return false; },
    
    // Preferences
    get preferences() { return {}; },
    get avatarPreference() { return 'default'; },
    get theme() { return 'light'; },
    get isDarkMode() { return false; },
    
    // Statistics & Usage
    get stats() { return {}; },
    get digestsRead() { return 0; },
    get lastActive() { return null; },
    
    // Sync & Status Information
    get syncStatus() { return 'none'; },
    get isSynced() { return false; },
    get updatedAt() { return null; },
    get lastSynced() { return null; },
    
    // Session Information
    get sessionToken() { return null; },
    get hasActiveSession() { return false; },
    
    // Raw Data Access
    get metadata() { return {}; },
    get rawUser() { return null; },
    
    // Utility Methods
    hasMetadata(key) { return false; },
    getMetadata(key, defaultValue = null) { return defaultValue; },
    
    // Validation Methods
    get isValid() { return false; },
    get isComplete() { return false; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        betterAuthId: null,
        wpUserId: null,
        email: null,
        displayName: 'Guest',
        roles: [],
        syncStatus: 'none',
        isSynced: false,
        isValid: false,
        isComplete: false,
        hasMetadata: false,
        metadataKeys: []
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        email: null,
        displayName: 'Guest',
        roles: [],
        plan: { name: 'Free' },
        isGuest: true
      };
    }
  };
}

export default { 
  store: userStore, 
  rootStore: userRootStore,
  updateUserData, 
  clearUserData,
  getAvatarUrl,
  getShortDisplayName,
  getWordPressUserId,
  getUserData
}; 