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
 * Enhanced User object for Better Auth multi-provider support
 * Supports email/password, phone, social providers (Google, GitHub, LinkedIn), magic links
 * @typedef {Object} User
 * @property {string} id - User identifier (Better Auth primary key)
 * @property {string} email - User email address
 * @property {string} [name] - User's full name (Better Auth standard field)
 * @property {string} [displayName] - User display name (legacy compatibility)
 * @property {string} [username] - Username (from username plugin or custom)
 * @property {string} [firstName] - First name
 * @property {string} [lastName] - Last name
 * @property {string} [image] - Profile image URL (Better Auth standard field)
 * @property {string} [avatarUrl] - Legacy avatar URL field (for backward compatibility)
 * @property {boolean} [emailVerified] - Email verification status (Better Auth)
 * @property {string} [phone] - Phone number (from phone number plugin)
 * @property {boolean} [phoneVerified] - Phone verification status
 * @property {Date} [createdAt] - Account creation date (Better Auth)
 * @property {Date} [updatedAt] - Last update timestamp (Better Auth)
 * @property {string[]} [roles] - User roles/permissions
 * @property {UserPreferences} [preferences] - User preferences
 * @property {UserPlan} [plan] - Subscription plan info
 * @property {UserStats} [stats] - User analytics and progress
 * @property {Object} [metadata] - Additional metadata
 * @property {number} [metadata.wp_user_id] - WordPress user ID for API calls
 * @property {string[]} [metadata.roles] - User roles from metadata
 * @property {number} [wp_user_id] - WordPress user ID for API calls (direct access)
 * @property {Object[]} [accounts] - Linked social accounts (Better Auth accounts table)
 * @property {Object[]} [sessions] - Active sessions (Better Auth sessions table)
 * @property {string} [provider] - Primary authentication provider used
 * @property {Object} [socialData] - Additional data from social providers
 * @property {string} [socialData.googleId] - Google user ID
 * @property {string} [socialData.githubId] - GitHub user ID
 * @property {string} [socialData.linkedinId] - LinkedIn user ID
 * @property {string} [locale] - User locale/language preference
 * @property {string} [timezone] - User timezone
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
 * Get the appropriate avatar URL based on user preferences and Better Auth fields
 * Supports Better Auth's standard 'image' field and social provider avatars
 * 
 * @param {object} user The user object
 * @returns {string} The avatar URL
 */
export function getAvatarUrl(user) {
  if (!user) return '/images/default-avatar.svg';
  
  // Debug logging (only when preferences are missing)
  if (!user.preferences) {
    console.debug('[Avatar Debug] User missing preferences, using defaults:', {
      image: user.image || 'none',
      avatarUrl: user.avatarUrl || 'none',
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
      'githubusercontent.com', // GitHub avatars
      'linkedin.com',
      'facebook.com',
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
  
  // PRIORITY 2: Check user preferences for specific avatar sources
  if (user.preferences?.avatarSource) {
    switch(user.preferences.avatarSource) {
      case 'synced':
        // If preference is synced but we don't have a synced avatar yet, try legacy then gravatar
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
  
  // PRIORITY 3: Fall back hierarchy (no specific preference)
  // synced > avatarUrl > gravatar > default priority
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
 * Normalize WordPress user ID to ensure consistent format
 * @param {Object} userData - Raw user data
 * @returns {number|null} Normalized WordPress user ID
 */
function extractWpUserId(userData) {
  if (!userData || typeof userData !== 'object') {
    return null;
  }

  // Try various WordPress user ID fields
  const wpId = userData.wp_user_id || 
               userData.wpUserId || 
               userData.metadata?.wp_user_id ||
               userData.metadata?.wp_sync?.wp_user_id ||
               null;

  if (wpId === null || wpId === undefined) {
    return null;
  }

  // Convert to number if it's a string
  const numericId = typeof wpId === 'string' ? parseInt(wpId, 10) : wpId;
  
  // Validate it's a positive integer
  return (typeof numericId === 'number' && numericId > 0 && !isNaN(numericId)) ? numericId : null;
}

/**
 * Extract roles from user data, handling various formats
 * @param {Object} userData - Raw user data
 * @returns {string[]} Array of role strings
 */
function extractRoles(userData) {
  if (!userData || typeof userData !== 'object') {
    return ['subscriber']; // Default role
  }

  // Check direct roles field ONLY if it has actual values
  if (Array.isArray(userData.roles) && userData.roles.length > 0) {
    return userData.roles;
  }

  // Check metadata roles (WordPress often stores the real roles here)
  if (Array.isArray(userData.metadata?.roles) && userData.metadata.roles.length > 0) {
    return userData.metadata.roles;
  }

  // Check if roles exists but is empty, then look for other role indicators
  if (Array.isArray(userData.roles) && userData.roles.length === 0) {
    // Look for WordPress role indicators in metadata
    if (userData.metadata?.user_role) {
      return [userData.metadata.user_role];
    }
    if (userData.metadata?.wp_capabilities) {
      // WordPress capabilities format: {"administrator": true}
      const roles = Object.keys(userData.metadata.wp_capabilities).filter(role => 
        userData.metadata.wp_capabilities[role] === true
      );
      if (roles.length > 0) return roles;
    }
  }

  // Default to subscriber if no roles found
  return ['subscriber'];
}

/**
 * Extract username from various possible sources in user data
 * This is the CANONICAL username extraction logic
 * @param {Object} userData - Raw user data from any source
 * @returns {string|null} Extracted username or null
 */
function extractUsername(userData) {
  if (!userData || typeof userData !== 'object') {
    return null;
  }

  // Priority order for username extraction:
  // 1. Direct username field
  // 2. Name field
  // 3. Metadata nickname (WordPress)
  // 4. Metadata username
  // 5. Metadata user_login (WordPress login)
  // 6. Metadata wpUsername
  // 7. null

  return userData.username || 
         userData.name || 
         userData.metadata?.nickname || 
         userData.metadata?.username || 
         userData.metadata?.user_login ||
         userData.metadata?.wpUsername ||
         null;
}

/**
 * Extract display name from user data with Better Auth multi-provider support
 * Handles Better Auth's standard 'name' field and social provider data
 * @param {Object} userData - Raw user data
 * @returns {string|null} Display name or null
 */
function extractDisplayName(userData) {
  if (!userData || typeof userData !== 'object') {
    return null;
  }

  // Priority order for display name extraction:
  // 1. Better Auth standard 'name' field
  // 2. Legacy displayName field (backward compatibility)
  // 3. Constructed from firstName + lastName
  // 4. WordPress fullname from metadata
  // 5. Username field
  // 6. Metadata nickname (WordPress)
  // 7. Email username part (fallback)

  if (userData.name) {
    return userData.name;
  }
  
  if (userData.displayName) {
    return userData.displayName;
  }
  
  // Try to construct from first + last name
  const firstName = userData.firstName || userData.metadata?.firstName || userData.metadata?.first_name;
  const lastName = userData.lastName || userData.metadata?.lastName || userData.metadata?.last_name;
  if (firstName && lastName) {
    return `${firstName} ${lastName}`;
  }
  if (firstName) {
    return firstName;
  }
  
  // Try WordPress fullname from metadata (description often contains full name)
  if (userData.metadata?.description) {
    // WordPress descriptions often contain user information, but might have HTML
    const cleanDescription = userData.metadata.description.replace(/<[^>]*>/g, '').trim();
    // If it looks like a name (less than 50 chars, no special chars), use it
    if (cleanDescription.length < 50 && /^[a-zA-Z\s&]+$/.test(cleanDescription)) {
      return cleanDescription;
    }
  }
  
  // Try username
  if (userData.username) {
    return userData.username;
  }
  
  // Try metadata nickname (WordPress)
  if (userData.metadata?.nickname) {
    return userData.metadata.nickname;
  }
  
  // Fallback to email username part
  if (userData.email) {
    return userData.email.split('@')[0];
  }
  
  return null;
}

/**
 * Normalize user data from any source to a consistent format
 * Enhanced for Better Auth multi-provider support (email/password, phone, social providers, magic links)
 * @param {Object} rawUserData - Raw user data from any source
 * @returns {Object|null} Normalized user data object
 */
function normalizeUserData(rawUserData) {
  if (!rawUserData || typeof rawUserData !== 'object' || !rawUserData.id) {
    return null;
  }

  const wpUserId = extractWpUserId(rawUserData);
  const displayName = extractDisplayName(rawUserData);
  const username = extractUsername(rawUserData);
  const roles = extractRoles(rawUserData);
  
  // Enhanced firstName/lastName extraction
  let firstName = rawUserData.firstName || rawUserData.metadata?.firstName || rawUserData.metadata?.first_name || null;
  let lastName = rawUserData.lastName || rawUserData.metadata?.lastName || rawUserData.metadata?.last_name || null;
  
  // If we don't have firstName/lastName but have displayName, try to extract them
  if (!firstName && !lastName && displayName) {
    const nameParts = displayName.trim().split(/\s+/);
    if (nameParts.length >= 2) {
      firstName = nameParts[0];
      lastName = nameParts.slice(1).join(' '); // Join all remaining parts as last name
    } else if (nameParts.length === 1) {
      firstName = nameParts[0];
      // Try to get lastName from WordPress metadata
      if (rawUserData.metadata?.nicename) {
        // WordPress nicename is often "first-last" format like "verious-smith"
        const niceParts = rawUserData.metadata.nicename.split('-');
        if (niceParts.length > 1) {
          // Take everything after the first part as potential last name
          lastName = niceParts.slice(1).map(part => 
            part.charAt(0).toUpperCase() + part.slice(1)
          ).join(' ');
        }
      }
    }
  }
  
  // If still no lastName, try the WordPress user_nicename field (might be different from nicename)
  if (!lastName && rawUserData.metadata?.user_nicename) {
    const niceParts = rawUserData.metadata.user_nicename.split('-');
    if (niceParts.length > 1 && firstName) {
      // Check if first part matches firstName (case insensitive)
      if (niceParts[0].toLowerCase() === firstName.toLowerCase()) {
        lastName = niceParts.slice(1).map(part => 
          part.charAt(0).toUpperCase() + part.slice(1)
        ).join(' ');
      }
    }
  }
  
  return {
    // Better Auth Core Fields
    id: rawUserData.id,
    betterAuthId: rawUserData.betterAuthId || rawUserData.id,
    email: rawUserData.email || '',
    name: rawUserData.name || displayName, // Better Auth standard field
    displayName: displayName, // Legacy compatibility
    username: username,
    firstName: firstName,
    lastName: lastName,
    
    // Better Auth Avatar Fields
    image: rawUserData.image || null, // Better Auth standard avatar field
    avatarUrl: rawUserData.avatarUrl || '', // Legacy compatibility
    
    // Better Auth Verification Fields
    emailVerified: rawUserData.emailVerified || rawUserData.email_verified || false,
    phone: rawUserData.phone || rawUserData.metadata?.phone || null,
    phoneVerified: rawUserData.phoneVerified || rawUserData.phone_verified || false,
    
    // Better Auth Timestamps
    createdAt: rawUserData.createdAt || rawUserData.created_at || null,
    updatedAt: rawUserData.updatedAt || rawUserData.updated_at || new Date().toISOString(),
    
    // Better Auth Provider Information
    provider: rawUserData.provider || 'email', // Primary auth provider
    accounts: rawUserData.accounts || [], // Linked social accounts
    sessions: rawUserData.sessions || [], // Active sessions
    
    // Social Provider Data
    socialData: {
      googleId: rawUserData.googleId || rawUserData.metadata?.googleId || null,
      githubId: rawUserData.githubId || rawUserData.metadata?.githubId || null,
      linkedinId: rawUserData.linkedinId || rawUserData.metadata?.linkedinId || null,
      facebookId: rawUserData.facebookId || rawUserData.metadata?.facebookId || null,
      ...rawUserData.socialData
    },
    
    // Localization
    locale: rawUserData.locale || rawUserData.metadata?.locale || 'en',
    timezone: rawUserData.timezone || rawUserData.metadata?.timezone || null,
    
    // Application-specific Fields
    roles: roles,
    plan: rawUserData.plan || { name: 'Free' },
    preferences: rawUserData.preferences || {},
    stats: rawUserData.stats || {},
    
    // WordPress Integration
    wpUserId: wpUserId,
    wp_user_id: wpUserId,
    syncStatus: rawUserData.syncStatus || 'synced',
    
    // Metadata (preserve any additional data)
    metadata: {
      // First spread existing metadata to preserve all fields
      ...rawUserData.metadata,
      // Then add/update Better Auth session/auth metadata (only if not already present)
      sessionToken: rawUserData.sessionToken || rawUserData.metadata?.sessionToken || null,
      lastLoginAt: rawUserData.lastLoginAt || rawUserData.metadata?.lastLoginAt || null,
      loginCount: rawUserData.loginCount || rawUserData.metadata?.loginCount || 0
    }
  };
}

export function getUserData(user) {
  // First, normalize the input data - this makes getUserData() handle raw data from any source
  const normalizedUser = normalizeUserData(user);
  
  if (!normalizedUser) {
    return createEmptyUserHelper();
  }

  // Now use the normalized data with the existing getter logic
  return {
    // Core Identity
    get id() { return normalizedUser.id || normalizedUser.betterAuthId || null; },
    get betterAuthId() { return normalizedUser.betterAuthId || normalizedUser.id || null; },
    get email() { return normalizedUser.email || null; },
    
    // Better Auth Standard Fields
    get name() { return normalizedUser.name || null; }, // Better Auth standard field
    get emailVerified() { return normalizedUser.emailVerified || false; },
    get phone() { return normalizedUser.phone || null; },
    get phoneVerified() { return normalizedUser.phoneVerified || false; },
    get createdAt() { return normalizedUser.createdAt || null; },
    
    // Better Auth Provider Information
    get provider() { return normalizedUser.provider || 'email'; },
    get accounts() { return normalizedUser.accounts || []; },
    get sessions() { return normalizedUser.sessions || []; },
    get hasMultipleAccounts() { return this.accounts.length > 1; },
    get linkedProviders() { 
      return this.accounts.map(account => account.provider || 'unknown');
    },
    
    // Social Provider Data
    get socialData() { return normalizedUser.socialData || {}; },
    get googleId() { return this.socialData.googleId || null; },
    get githubId() { return this.socialData.githubId || null; },
    get linkedinId() { return this.socialData.linkedinId || null; },
    get facebookId() { return this.socialData.facebookId || null; },
    get hasSocialAccounts() { 
      return !!(this.googleId || this.githubId || this.linkedinId || this.facebookId);
    },
    
    // Localization
    get locale() { return normalizedUser.locale || 'en'; },
    get timezone() { return normalizedUser.timezone || null; },
    
    // WordPress Integration
    get wpUserId() { 
      return normalizedUser.wpUserId || 
             normalizedUser.wp_user_id || 
             normalizedUser.metadata?.wpUserId ||
             normalizedUser.metadata?.wp_user_id || 
             (typeof normalizedUser.metadata?.wp_sync?.wp_user_id === 'number' ? normalizedUser.metadata.wp_sync.wp_user_id : null);
    },
    
    // Display Information
    get displayName() { 
      return normalizedUser.displayName || 
             normalizedUser.name || 
             normalizedUser.username || 
             normalizedUser.metadata?.displayName || 
             normalizedUser.metadata?.name || 
             normalizedUser.metadata?.nickname ||
             (normalizedUser.email ? normalizedUser.email.split('@')[0] : 'User');
    },
    get shortDisplayName() { 
      const name = this.displayName;
      if (name.length > 12) {
        return name.substring(0, 12) + '...';
      }
      return name;
    },
    get username() { 
      // Use the normalized username (already extracted using canonical logic)
      return normalizedUser.username;
    },
    get firstName() { 
      return normalizedUser.firstName || 
             normalizedUser.metadata?.firstName || 
             normalizedUser.metadata?.first_name || 
             (normalizedUser.displayName ? normalizedUser.displayName.split(' ')[0] : null);
    },
    get lastName() { 
      if (normalizedUser.lastName || normalizedUser.metadata?.lastName || normalizedUser.metadata?.last_name) {
        return normalizedUser.lastName || normalizedUser.metadata?.lastName || normalizedUser.metadata?.last_name;
      }
      if (normalizedUser.displayName) {
        const parts = normalizedUser.displayName.split(' ');
        return parts.length > 1 ? parts[parts.length - 1] : null;
      }
      return null;
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
    get image() { return normalizedUser.image || null; }, // Better Auth standard avatar field
    get avatarUrl() { 
      // PRIORITY 1: Return the direct avatarUrl if it exists (preserve original data)
      if (normalizedUser.avatarUrl) {
        return normalizedUser.avatarUrl;
      }
      // PRIORITY 2: Check metadata for avatarUrl
      if (normalizedUser.metadata?.avatarUrl) {
        return normalizedUser.metadata.avatarUrl;
      }
      // PRIORITY 3: Fall back to complex preference-based logic (gravatar priority)
      return getAvatarUrl(normalizedUser);
    },
    get gravatarUrl() { 
      return normalizedUser.gravatarUrl || 
             (normalizedUser.email ? createGravatarUrl(normalizedUser.email) : null);
    },
    
    // Roles & Permissions
    get roles() { 
      // Use the normalized roles (already extracted with proper fallbacks)
      return normalizedUser.roles;
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
      if (typeof normalizedUser.plan === 'object' && normalizedUser.plan?.name) {
        return normalizedUser.plan;
      }
      if (typeof normalizedUser.plan === 'string') {
        return { name: normalizedUser.plan };
      }
      if (normalizedUser.metadata?.plan) {
        return typeof normalizedUser.metadata.plan === 'object' ? normalizedUser.metadata.plan : { name: normalizedUser.metadata.plan };
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
      return normalizedUser.preferences || normalizedUser.metadata?.preferences || {};
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
      return normalizedUser.stats || normalizedUser.metadata?.stats || {};
    },
    get digestsRead() { 
      return this.stats.digestsRead || 0;
    },
    get lastActive() { 
      return normalizedUser.lastActive || 
             normalizedUser.metadata?.lastActive || 
             normalizedUser.updatedAt || 
             null;
    },
    
    // Sync & Status Information
    get syncStatus() { 
      return normalizedUser.syncStatus || 
             normalizedUser.metadata?.syncStatus || 
             (this.wpUserId ? 'synced' : 'pending');
    },
    get isSynced() { 
      return this.syncStatus === 'synced' && !!this.wpUserId;
    },
    get updatedAt() { 
      return normalizedUser.updatedAt || normalizedUser.metadata?.updatedAt || null;
    },
    get lastSynced() { 
      return normalizedUser.lastSynced || 
             normalizedUser.metadata?.lastSynced || 
             normalizedUser.metadata?.wp_sync?.synced_at || 
             null;
    },
    
    // Session Information
    get sessionToken() { 
      return normalizedUser.sessionToken || normalizedUser.metadata?.sessionToken || null;
    },
    get hasActiveSession() { 
      return !!this.sessionToken;
    },
    
    // Raw Data Access
    get metadata() { 
      return normalizedUser.metadata || {};
    },
    get rawUser() { 
      return normalizedUser;
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
      const result = {
        // Better Auth Core Fields
        id: this.id,
        betterAuthId: this.betterAuthId,
        email: this.email,
        name: this.name, // Better Auth standard field
        displayName: this.displayName, // Legacy compatibility
        username: this.username,
        firstName: this.firstName,
        lastName: this.lastName,
        
        // Better Auth Avatar Fields
        image: this.image, // Better Auth standard
        avatarUrl: this.avatarUrl, // Legacy compatibility
        
        // Better Auth Verification
        emailVerified: this.emailVerified,
        phone: this.phone,
        phoneVerified: this.phoneVerified,
        
        // Better Auth Provider Info
        provider: this.provider,
        accounts: this.accounts,
        socialData: this.socialData,
        
        // Better Auth Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        
        // Application Fields
        roles: this.roles,
        plan: this.plan,
        preferences: this.preferences,
        stats: this.stats,
        
        // WordPress Integration
        wpUserId: this.wpUserId,
        wp_user_id: this.wpUserId, // Include both for compatibility
        syncStatus: this.syncStatus,
        
        // Localization
        locale: this.locale,
        timezone: this.timezone,
        
        // Metadata
        metadata: this.metadata
      };
      
      // Debug logging for avatar URL issues
      if (!result.avatarUrl && normalizedUser.avatarUrl) {
        console.warn('[getUserData toJSON] Avatar URL lost during serialization!', {
          originalAvatarUrl: normalizedUser.avatarUrl,
          resultAvatarUrl: result.avatarUrl,
          thisAvatarUrl: this.avatarUrl
        });
      }
      
      return result;
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
    
    // Better Auth Standard Fields
    get name() { return null; }, // Better Auth standard field
    get emailVerified() { return false; },
    get phone() { return null; },
    get phoneVerified() { return false; },
    get createdAt() { return null; },
    
    // Better Auth Provider Information
    get provider() { return 'email'; },
    get accounts() { return []; },
    get sessions() { return []; },
    get hasMultipleAccounts() { return false; },
    get linkedProviders() { return []; },
    
    // Social Provider Data
    get socialData() { return {}; },
    get googleId() { return null; },
    get githubId() { return null; },
    get linkedinId() { return null; },
    get facebookId() { return null; },
    get hasSocialAccounts() { return false; },
    
    // Localization
    get locale() { return 'en'; },
    get timezone() { return null; },
    
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
    get image() { return null; }, // Better Auth standard avatar field
    get avatarUrl() { return '/images/default-avatar.svg'; },
    get gravatarUrl() { return null; },
    
    // Roles & Permissions
    get roles() { return ['subscriber']; },
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
        roles: ['subscriber'],
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
        roles: ['subscriber'],
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