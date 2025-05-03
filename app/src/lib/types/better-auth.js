/**
 * @file Type definitions for Better Auth integration
 * @version 1.1
 * @implements jsdoc-type-definitions-for-ba-protocol
 */

/**
 * @typedef {Object} BetterAuthUser
 * @property {string} id - Unique user identifier
 * @property {string} email - User's email address
 * @property {string} [username] - Optional username
 * @property {string} [name] - Optional display name
 * @property {string} [displayName] - Display name (fallback hierarchy: displayName -> name -> username -> email)
 * @property {string} [display_name] - Alternative display name property (for backward compatibility)
 * @property {string[]} [roles] - User roles from metadata
 * @property {string} [betterAuthId] - Same as id, for backward compatibility
 * @property {Date|string} [createdAt] - Creation timestamp
 * @property {Date|string} [created_at] - Alternative creation timestamp (for backward compatibility)
 * @property {Date|string} [updatedAt] - Last update timestamp
 * @property {Date|string} [updated_at] - Alternative update timestamp (for backward compatibility)
 * @property {boolean} [emailVerified] - Whether email is verified
 * @property {string|null} [image] - User avatar URL
 * @property {string} [avatarUrl] - Alternative avatar URL (for backward compatibility)
 * @property {string} [syncStatus] - Status of sync with WordPress
 * @property {string} [sessionToken] - Session token for client use
 * @property {Object} [metadata] - Additional user metadata
 * @property {string} [metadata.firstName] - First name
 * @property {string} [metadata.lastName] - Last name
 * @property {string|number} [metadata.wp_user_id] - WordPress user ID
 * @property {string[]} [metadata.roles] - User roles
 * @property {Object} [metadata.wp_sync] - WordPress sync metadata
 * @property {string} [metadata.wp_sync.synced_at] - Last sync timestamp
 * @property {string} [metadata.wpUsername] - WordPress username
 * @property {string} [metadata.wpFirstName] - WordPress first name
 * @property {string} [metadata.wpLastName] - WordPress last name
 * @property {string} [metadata.wpDisplayName] - WordPress display name
 * @property {string} [metadata.syncSource] - Source of synchronization
 * @property {string} [metadata.syncTimestamp] - Timestamp of synchronization
 */

/**
 * @typedef {Object} BetterAuthSession
 * @property {string} sessionId - Session unique identifier
 * @property {string} userId - User ID associated with session
 * @property {string} token - Session token
 * @property {Date|string} expiresAt - Expiration timestamp
 * @property {Date|string} createdAt - Creation timestamp
 * @property {string} [refreshToken] - Token for refreshing the session
 * @property {Date|string} [refreshTokenExpiresAt] - Refresh token expiration
 * @property {BetterAuthUser} [user] - Associated user object
 */

/**
 * @typedef {Object} BetterAuthAccount
 * @property {string} id - Unique account identifier
 * @property {string} userId - User ID associated with account
 * @property {string} provider - Authentication provider name
 * @property {string} providerAccountId - ID from the provider
 * @property {Date|string} created_at - Creation timestamp
 * @property {Object} [providerData] - Additional provider-specific data
 */

/**
 * @typedef {Object} WordPressUserData
 * @property {string|number} wpUserId - WordPress user ID
 * @property {string} email - User email
 * @property {string} username - Username
 * @property {string} [displayName] - Display name
 * @property {string} [firstName] - First name
 * @property {string} [lastName] - Last name
 * @property {string[]} [roles] - WordPress roles
 */

/**
 * @typedef {Object} DatabaseConfig
 * @property {Object} dialect - Kysely dialect instance
 * @property {string} type - Database type (e.g., "mysql")
 */

/**
 * @typedef {Object} BetterAuthSessionManager
 * @property {function(Request): Promise<BetterAuthSession|null>} getSession - Get session from request
 * @property {function(string, string, Date): Promise<BetterAuthSession>} createSession - Create new session
 * @property {function(string): Promise<BetterAuthSession|null>} refreshSession - Refresh session
 * @property {function(string): Promise<boolean>} deleteSession - Delete session
 */

/**
 * @typedef {Object} BetterAuthAdapter
 * @property {function(string): Promise<BetterAuthUser|null>} getUserById - Get user by ID
 * @property {function(string): Promise<BetterAuthUser|null>} getUserByEmail - Get user by email
 * @property {function(string): Promise<BetterAuthSession|null>} getSessionByToken - Get session by token
 * @property {function(string, string, Date): Promise<BetterAuthSession|null>} createSession - Create session
 * @property {function(string): Promise<boolean>} deleteSession - Delete session
 * @property {function(number): Promise<BetterAuthUser|null>} getUserByWpId - Get user by WordPress ID
 * @property {function(Object): Promise<BetterAuthUser|null>} createUser - Create user
 * @property {function(Object): Promise<boolean>} createAccount - Create account link
 */

/**
 * @typedef {Object} BetterAuth
 * @property {BetterAuthSessionManager} sessionManager - Session management functions
 * @property {BetterAuthAdapter} adapter - Database adapter functions
 * @property {Object} options - Configuration options
 * @property {function(Request): Promise<Response>} handler - API request handler
 */

/**
 * @typedef {Object} AuthSuccessResponse
 * @property {boolean} success - Always true for success
 * @property {Object} user - User data
 * @property {string} user.id - User ID
 * @property {string} user.email - User email
 * @property {string} [user.displayName] - User display name
 * @property {string[]} [user.roles] - User roles
 */

/**
 * @typedef {Object} AuthErrorResponse
 * @property {boolean} success - Always false for errors
 * @property {string} error - Error code
 * @property {string} [message] - Optional error message
 */

/**
 * @typedef {Object} SessionResponse
 * @property {boolean} authenticated - Whether user is authenticated
 * @property {Object} [user] - User data if authenticated
 * @property {string} user.id - User ID
 * @property {string} user.email - User email
 * @property {string} [user.displayName] - User display name
 * @property {string[]} [user.roles] - User roles
 * @property {string} [user.updatedAt] - Last update timestamp
 * @property {string} [error] - Error code if there was an error
 */

/**
 * @typedef {Object} User
 * @property {string} id - Better Auth User ID (UUID)
 * @property {string} email - User email
 * @property {string} [username] - Optional username
 * @property {string} [name] - Optional display name
 * @property {string} displayName - Primary display name 
 * @property {Object} [metadata] - User metadata
 * @property {number} [metadata.wp_user_id] - WordPress user ID
 * @property {string[]} [metadata.roles] - User roles array
 * @property {boolean} [emailVerified] - Whether email is verified
 * @property {string|null} [image] - User avatar URL
 * @property {Date|string} [createdAt] - Creation timestamp
 * @property {Date|string} [updatedAt] - Last update timestamp
 * @property {string[]} [roles] - User roles (top level, for convenience)
 * @property {boolean} [_noRefresh] - Flag to prevent page refresh (internal use)
 */

/**
 * @typedef {Object} SessionResult
 * @property {boolean} success - Whether the operation was successful
 * @property {User|null} [user] - User data if success is true
 * @property {boolean} [created] - Whether a new user was created
 * @property {string} [error] - Error message if unsuccessful
 * @property {string} [details] - Additional error details
 * @property {boolean} [noRefresh] - Whether to prevent page refresh
 * @property {string} [warning] - Warning message
 */

/**
 * Converts a BetterAuthUser to the standard User type
 * @param {BetterAuthUser} betterAuthUser - The Better Auth user object
 * @returns {User|null} Standardized user object or null if no input
 */
export function convertToStandardUser(betterAuthUser) {
  if (!betterAuthUser) return null;
  
  // Handle WordPress user ID with type safety
  let wpUserId = undefined;
  if (betterAuthUser.metadata && 'wp_user_id' in betterAuthUser.metadata) {
    const rawId = betterAuthUser.metadata.wp_user_id;
    wpUserId = typeof rawId === 'number' ? rawId : 
              typeof rawId === 'string' ? parseInt(rawId, 10) || undefined : undefined;
  }
  
  // Handle roles with type safety
  let roles = undefined;
  if (betterAuthUser.roles && Array.isArray(betterAuthUser.roles)) {
    roles = betterAuthUser.roles;
  } else if (betterAuthUser.metadata && 'roles' in betterAuthUser.metadata && 
             Array.isArray(betterAuthUser.metadata.roles)) {
    roles = betterAuthUser.metadata.roles;
  }
  
  // Create standardized metadata
  const standardMetadata = {
    wp_user_id: wpUserId,
    roles: roles
  };
  
  return {
    id: betterAuthUser.id,
    email: betterAuthUser.email,
    username: betterAuthUser.username,
    name: betterAuthUser.name || betterAuthUser.display_name,
    displayName: betterAuthUser.displayName || betterAuthUser.display_name || 
                betterAuthUser.name || betterAuthUser.username || 
                (betterAuthUser.email ? betterAuthUser.email.split('@')[0] : 'User'),
    metadata: standardMetadata,
    emailVerified: betterAuthUser.emailVerified,
    image: betterAuthUser.image || betterAuthUser.avatarUrl,
    createdAt: betterAuthUser.createdAt || betterAuthUser.created_at,
    updatedAt: betterAuthUser.updatedAt || betterAuthUser.updated_at,
    roles: roles,
    _noRefresh: false
  };
}

// Export for use in JSDoc references
export { }; 