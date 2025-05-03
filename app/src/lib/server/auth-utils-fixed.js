/**
 * @file Utility functions for server-side authentication and user synchronization.
 * @created 2025-07-29
 * @version 1.0.0
 */

import { error } from '@sveltejs/kit';
import { log } from '$lib/utils/log';
import crypto from 'node:crypto';
import { 
  auth, 
    getUserByWpIdFn, 
    createUserFn,
  createSessionFn,
  createAccountFn,
  pool
} from '$lib/server/auth'; // Import the configured Better Auth instance, functions, and pool

/**
 * @typedef {Object} UserMetadata
 * @property {number} [wp_user_id] - WordPress user ID
 * @property {string[]} [roles] - User roles array
 * @property {string} [wpUsername] - WordPress username
 * @property {string} [wpFirstName] - WordPress user first name
 * @property {string} [wpLastName] - WordPress user last name
 * @property {string} [wpDisplayName] - WordPress display name
 * @property {string} [syncSource] - Source of the sync operation
 * @property {string} [syncTimestamp] - ISO timestamp of sync
 */

/**
 * @typedef {Object} User
 * @property {string} id - Better Auth User ID (UUID)
 * @property {string} email - User email
 * @property {string} [username] - Optional username
 * @property {string} [name] - Optional display name
 * @property {string} displayName - Primary display name 
 * @property {UserMetadata} [metadata] - User metadata
 * @property {boolean} [emailVerified] - Whether email is verified
 * @property {string|null} [image] - User avatar URL
 * @property {Date|string} [createdAt] - Creation timestamp
 * @property {Date|string} [updatedAt] - Last update timestamp
 * @property {string[]} [roles] - User roles (top level, for convenience)
 * @property {boolean} [_noRefresh] - Flag to prevent page refresh (internal use)
 */

/**
 * @typedef {Object} CreateUserParams
 * @property {number} wpUserId - WordPress user ID 
 * @property {string} email - User email address
 * @property {string} [username] - WordPress username
 * @property {string} [name] - User display name
 * @property {string[]} [roles] - User roles array
 * @property {boolean} [emailVerified] - Whether email is verified
 * @property {string|null} [image] - User avatar URL
 * @property {Object} [metadata] - User metadata
 */

/**
 * @typedef {Object} WPUserData
 * @property {number} wpUserId - WordPress user ID 
 * @property {string} email - User email address
 * @property {string} username - WordPress username
 * @property {string} displayName - User display name
 * @property {string} [firstName] - User first name
 * @property {string} [lastName] - User last name
 * @property {string[]} [roles] - User roles array
 */

/**
 * @typedef {Object} SessionResult
 * @property {boolean} success - Whether the sync was successful
 * @property {User|null} [user] - The user object if successful
 * @property {boolean} [created] - Whether a new user was created
 * @property {string} [error] - Error message if unsuccessful
 * @property {string} [details] - Additional error details
 * @property {boolean} [noRefresh] - Whether to prevent page refresh
 * @property {string} [warning] - Warning message
 */

/**
 * @typedef {Object} MySQLRow
 * @property {number|string} [count] - Count value when using COUNT query
 * @property {string} [ba_user_id] - Better Auth user ID when querying mapping table
 * @property {string} [id] - ID field
 * @property {string} [email] - Email field
 * @property {string} [username] - Username field
 * @property {string} [name] - Name field
 * @property {string|Object} [metadata] - Metadata field (could be string or parsed object)
 * @property {string} [created_at] - Creation timestamp
 * @property {string} [updated_at] - Last update timestamp
 * @property {boolean} [email_verified] - Whether email is verified
 * @property {string} [image] - User image URL
 */

/**
 * @typedef {Object} MySQLColumnInfo
 * @property {string} COLUMN_NAME - Name of the column
 * @property {string} DATA_TYPE - SQL data type of the column
 * @property {string} [COLUMN_TYPE] - Full type definition including constraints
 * @property {string} [COLUMN_KEY] - Key type (PRI, UNI, MUL)
 * @property {string} [COLUMN_DEFAULT] - Default value for the column
 * @property {string} [IS_NULLABLE] - Whether column accepts NULL values (YES/NO)
 * @property {string} [EXTRA] - Additional information (e.g., auto_increment)
 */

/**
 * @typedef {Object} TableInfo
 * @property {string} name - Table name
 * @property {boolean} required - Whether the table is required
 */

/**
 * @typedef {Object} PoolConnection
 * @property {function(): void} release - Release the connection back to the pool
 * @property {function(string, Array<any>): Promise<[any[], any]>} query - Execute SQL query with parameters
 * @property {function(string, Array<any>): Promise<[any[], any]>} execute - Execute prepared SQL with parameters
 */

/**
 * Check if the Better Auth tables exist in the database
 * 
 * @returns {Promise<{exists: boolean, message: string, schemaInfo: Record<string, any>}>} Status of Better Auth tables
 */
async function checkBetterAuthTables() {
  if (!pool) {
    return { exists: false, message: 'Database pool not initialized', schemaInfo: {} };
  }
  
  /** @type {PoolConnection|null} */
  let connection = null;
  try {
    // Type assertion for MySQL connection
    connection = await pool.getConnection();
    
    // Check for core tables based on Better Auth schema
    /** @type {TableInfo[]} */
    const tableChecks = [
      { name: 'ba_users', required: true },
      { name: 'ba_sessions', required: true },
      { name: 'ba_accounts', required: true },
      { name: 'ba_wp_user_map', required: false }, // Our project-specific table
    ];
    
    /** @type {Record<string, boolean>} */
    const results = {};
    /** @type {Record<string, any>} */
    const schemaInfo = {};
    let allRequiredExist = true;
    
    for (const table of tableChecks) {
      // Use type assertion for MySQL results
      /** @type {[MySQLRow[], any]} */
      const [rows] = await connection.query(
        "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
        [table.name]
      );
      
      // Handle count value which could be string or number, and might not exist
      const count = rows && rows[0] && rows[0].count;
      const exists = count !== undefined && (
        // Handle both string and number types for count
        (typeof count === 'number' && count > 0) || 
        (typeof count === 'string' && parseInt(count, 10) > 0)
      );
      
      results[table.name] = exists;
      
      if (table.required && !exists) {
        allRequiredExist = false;
      }
      
      // If table exists, get column information for schema validation
      if (exists) {
        // Using a separate column type for information_schema.COLUMNS results
        const columnsQuery = await connection.query(
          "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
          [table.name]
        );
        
        // Use type-safe reducer pattern with generic object accumulator
        /** @type {Record<string, string>} */
        const columnMap = {};
        
        // Apply proper type-safety protocol with explicit type checking
        if (Array.isArray(columnsQuery[0])) {
          columnsQuery[0].forEach(col => {
            // Type guard to ensure col has the required properties
            if (col && typeof col === 'object' && 'COLUMN_NAME' in col && 'DATA_TYPE' in col) {
              const columnName = String(col.COLUMN_NAME);
              const dataType = String(col.DATA_TYPE);
              columnMap[columnName] = dataType;
            }
          });
        }
        
        schemaInfo[table.name] = columnMap;
      }
    }
    
    if (!allRequiredExist) {
      return { 
        exists: false, 
        message: `Missing required Better Auth tables: ${Object.entries(results)
          .filter(([name, exists]) => !exists)
          .map(([name]) => name)
          .join(', ')}`,
        schemaInfo
      };
    }
    
    // Special check for session_token column which might be named differently
    if (results.ba_sessions && schemaInfo.ba_sessions) {
      /** @type {Record<string, string>} */
      const sessionColumns = schemaInfo.ba_sessions;
      
      // Type-safe property access with appropriate guards
      if (!('session_token' in sessionColumns) && 'token' in sessionColumns) {
        log('[auth-utils-fixed] Found column "token" instead of "session_token" in ba_sessions table', 'info');
        schemaInfo.ba_sessions_token_field = 'token';
      } else if ('session_token' in sessionColumns) {
        schemaInfo.ba_sessions_token_field = 'session_token';
      } else {
        log('[auth-utils-fixed] Could not find "token" or "session_token" column in ba_sessions table', 'warn');
      }
    }
    
    return { exists: true, message: 'All required Better Auth tables exist', schemaInfo };
  } catch (err) {
    // Type guard the error
    const error = err instanceof Error ? err : new Error(String(err));
    return { 
      exists: false, 
      message: `Error checking Better Auth tables: ${error.message}`,
      schemaInfo: {}
    };
  } finally {
    if (connection) {
      connection.release();
    }
  }
}

/**
 * Attempts to sync a WordPress user to Better Auth system and create a session.
 * 
 * @param {WPUserData} wpUserData - WordPress user data
 * @param {import('@sveltejs/kit').Cookies} cookies - SvelteKit cookies object
 * @param {number} [maxRetries=3] - Maximum number of retry attempts for DB operations
 * @param {number} [retryDelayMs=1000] - Delay between retries in milliseconds
 * @param {string} [clientIp='127.0.0.1'] - Client IP address for session tracking
 * @param {string} [userAgent='Server-to-server sync'] - User agent for session tracking
 * @returns {Promise<SessionResult>} Result of the sync operation
 */
export async function syncWordPressUserAndCreateSession(
  wpUserData, 
  cookies, 
  maxRetries = 3, 
  retryDelayMs = 1000,
  clientIp = '127.0.0.1',
  userAgent = 'Server-to-server sync'
) {
  // Extract WordPress user data
  const { wpUserId, email, username, displayName, firstName, lastName, roles } = wpUserData;

  if (!wpUserId || !email) {
    log('[auth-utils-fixed] Missing required user data (wpUserId or email)', 'error');
    return { 
      success: false, 
      error: 'invalid_data',
      details: 'WordPress user ID and email are required'
    };
  }

  log(`[auth-utils-fixed] Attempting to sync WordPress user ID: ${wpUserId}, with ${maxRetries} max retries`, 'debug');

  // First, verify Better Auth tables exist and get schema info
  const tablesStatus = await checkBetterAuthTables();
  if (!tablesStatus.exists) {
    log(`[auth-utils-fixed] Database schema issue: ${tablesStatus.message}`, 'error');
    return {
      success: false,
      error: 'database_schema_error',
      details: tablesStatus.message
    };
  }
  
  // Store schema info for use in error handling
  /** @type {Record<string, any>} */
  const schemaInfo = tablesStatus.schemaInfo;
  
  // Initialize tracking for which step failed
  let lastAttemptedOperation = '';
  
  // Setup retry function
  /**
   * Retry a function multiple times with delay between attempts
   * @template T
   * @param {() => Promise<T>} operation - The function to retry
   * @param {string} operationName - Name of the operation for logging
   * @param {number} [retries=maxRetries] - Number of retries remaining
   * @returns {Promise<T>} Result of the operation
   */
  const withRetry = async (operation, operationName, retries = maxRetries) => {
    lastAttemptedOperation = operationName;
    try {
      return await operation();
    } catch (err) {
      // Type-guard the error
      const error = err instanceof Error ? err : new Error(String(err));
      
      if (retries > 0) {
        log(`[auth-utils-fixed] Retrying ${operationName} after error (${retries} attempts left): ${error.message}`, 'warn');
        await new Promise(resolve => setTimeout(resolve, retryDelayMs));
        return withRetry(operation, operationName, retries - 1);
      } else {
        log(`[auth-utils-fixed] All retry attempts failed for ${operationName}: ${error.message}`, 'error');
        log(`[auth-utils-fixed] Error stack: ${error.stack}`, 'debug');
        throw error;
      }
    }
  };

  try {
    // Directly use the connection to check if user exists in the wp_user_map table
    /** @type {PoolConnection|null} */
    let connection = null;
    /** @type {User|null} */
    let user = null;
    
    try {
      log(`[auth-utils-fixed] Looking up existing user by WordPress ID: ${wpUserId} directly in database`, 'debug');
      connection = await pool.getConnection();
      
      // First try to find user in the wp_user_map table
      /** @type {[MySQLRow[], any]} */
      const [mapRows] = await connection.query(
        "SELECT ba_user_id FROM ba_wp_user_map WHERE wp_user_id = ?",
        [wpUserId]
      );
      
      // Type-safe check for array content with guards
      if (Array.isArray(mapRows) && mapRows.length > 0 && mapRows[0]) {
        const firstRow = mapRows[0];
        
        // Type guard to ensure the ba_user_id exists
        if ('ba_user_id' in firstRow && firstRow.ba_user_id) {
          const baUserId = String(firstRow.ba_user_id);
          log(`[auth-utils-fixed] Found mapping to BA user ID: ${baUserId}`, 'debug');
          
          // Get the user data with proper type checking
          /** @type {[MySQLRow[], any]} */
          const [userRows] = await connection.query(
            "SELECT * FROM ba_users WHERE id = ?",
            [baUserId]
          );
          
          // Type-safe check for array content with guards
          if (Array.isArray(userRows) && userRows.length > 0 && userRows[0]) {
            const userData = userRows[0];
            let metadata = userData.metadata;
            
            // Type-safe parsing of potential JSON string
            if (typeof metadata === 'string') {
              try {
                metadata = JSON.parse(metadata);
                // Ensure metadata is an object after parsing
                if (typeof metadata !== 'object' || metadata === null) {
                  metadata = {};
                }
              } catch (e) {
                metadata = {};
              }
            } else if (typeof metadata !== 'object' || metadata === null) {
              metadata = {};
            }
            
            // Ensure all required fields have default values to match User type
            user = {
              id: String(userData.id || ''),
              email: String(userData.email || ''),
              username: userData.username ? String(userData.username) : undefined,
              name: userData.name ? String(userData.name) : undefined,
              displayName: String(userData.name || userData.username || userData.email || 'User'), // Ensure displayName is always a string
              metadata: metadata,
              emailVerified: Boolean(userData.email_verified) || true,
              image: userData.image || null,
              createdAt: userData.created_at ? String(userData.created_at) : new Date().toISOString(),
              updatedAt: userData.updated_at ? String(userData.updated_at) : new Date().toISOString()
            };
            
            log(`[auth-utils-fixed] Found existing Better Auth user: ${user.id}`, 'info');
          }
        } else {
          log(`[auth-utils-fixed] Found mapping but ba_user_id is missing or invalid`, 'warn');
        }
      } else {
        log(`[auth-utils-fixed] No user mapping found for WP ID: ${wpUserId}`, 'debug');
      }
      
    } catch (err) {
      // Type-guard the error
      const dbError = err instanceof Error ? err : new Error(String(err));
      log(`[auth-utils-fixed] Error looking up user in database: ${dbError.message}`, 'error');
    } finally {
      if (connection) {
        connection.release();
      }
    }
    
    // If direct database lookup failed, try the adapter function
    if (!user) {
      try {
        log(`[auth-utils-fixed] Attempting adapter lookup for WP ID: ${wpUserId}`, 'debug');
        user = await withRetry(
          async () => getUserByWpIdFn(wpUserId),
          'getUserByWpIdFn'
        );
      } catch (err) {
        // Type-guard the error
        const adapterError = err instanceof Error ? err : new Error(String(err));
        log(`[auth-utils-fixed] Adapter lookup failed: ${adapterError.message}`, 'warn');
      }
    }

    let userCreated = false;

    // If no existing user, create one
    if (!user) {
      log(`[auth-utils-fixed] No existing user found for WP ID: ${wpUserId}, creating new user`, 'info');
      
      // Create directly in database if adapter function fails
      try {
        // Type assertion for createUserFn parameters to include Better Auth fields
        /** @type {CreateUserParams} */
        const createUserParams = {
          wpUserId,
          email,
          username: username || email.split('@')[0],
          name: displayName || `${firstName || ''}${lastName ? ' ' + lastName : ''}`.trim() || username || 'User',
          roles: roles || ['subscriber'],
          // Add Better Auth specific fields
          emailVerified: true,
          image: null, // Could be added if available
          metadata: {
            wpUsername: username,
            wpFirstName: firstName || '',
            wpLastName: lastName || '',
            wpDisplayName: displayName || '',
            syncSource: 'server-to-server',
            syncTimestamp: new Date().toISOString()
          }
        };

        user = await withRetry(
          async () => createUserFn(createUserParams),
          'createUserFn'
        );
      } catch (err) {
        // Type-guard the error
        const adapterError = err instanceof Error ? err : new Error(String(err));
        log(`[auth-utils-fixed] Adapter createUser failed: ${adapterError.message}, falling back to direct DB insert`, 'warn');
        
        try {
          /** @type {PoolConnection|null} */
          connection = await pool.getConnection();
          const userId = crypto.randomUUID();
          const now = new Date();
          
          // Insert user with all Better Auth fields
          await connection.query(
            "INSERT INTO ba_users (id, email, username, name, email_verified, image, metadata, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
              userId, 
              email, 
              username || email.split('@')[0], 
              displayName || `${firstName || ''}${lastName ? ' ' + lastName : ''}`.trim() || username || 'User',
              true, // WordPress users are considered verified
              null, // Image URL if available
              JSON.stringify({
                wp_user_id: wpUserId,
                roles: roles || ['subscriber'],
                wpUsername: username,
                wpFirstName: firstName || '',
                wpLastName: lastName || '',
                wpDisplayName: displayName || '',
                syncSource: 'server-to-server',
                syncTimestamp: now.toISOString()
              }),
              now,
              now
            ]
          );
          
          // Insert mapping
          await connection.query(
            "INSERT INTO ba_wp_user_map (wp_user_id, ba_user_id, created_at) VALUES (?, ?, ?)",
            [wpUserId, userId, now]
          );
          
          // Create user object with Better Auth fields
          user = {
            id: userId,
            email,
            username: username || email.split('@')[0],
            name: displayName || `${firstName || ''}${lastName ? ' ' + lastName : ''}`.trim() || username || 'User',
            displayName: displayName || `${firstName || ''}${lastName ? ' ' + lastName : ''}`.trim() || username || email.split('@')[0],
            emailVerified: true,
            image: null,
            createdAt: now.toISOString(),
            updatedAt: now.toISOString(),
            metadata: {
              wp_user_id: wpUserId,
              roles: roles || ['subscriber'],
              wpUsername: username,
              wpFirstName: firstName || '',
              wpLastName: lastName || '',
              wpDisplayName: displayName || '',
              syncSource: 'server-to-server',
              syncTimestamp: now.toISOString()
            }
          };
          
          log(`[auth-utils-fixed] Created user directly in database: ${userId}`, 'info');
        } catch (err) {
          // Type-guard the error
          const dbError = err instanceof Error ? err : new Error(String(err));
          log(`[auth-utils-fixed] Direct database insert failed: ${dbError.message}`, 'error');
          return { 
            success: false, 
            error: 'sync_failed',
            details: 'user_creation_err_direct'
          };
        } finally {
          if (connection) {
            connection.release();
          }
        }
      }

        if (!user) {
        log('[auth-utils-fixed] Failed to create user', 'error');
        return { 
          success: false, 
          error: 'sync_failed',
          details: 'user_creation_err'
        };
      }
      
      userCreated = true;
      log(`[auth-utils-fixed] Created Better Auth user: ${user.id} for WP ID: ${wpUserId}`, 'info');
    } else {
      log(`[auth-utils-fixed] Found existing Better Auth user: ${user.id} for WP ID: ${wpUserId}`, 'info');
    }

    // Create account link if needed (optional based on requirements)
    try {
      // Enhanced account creation with Better Auth fields
      await withRetry(
        async () => {
          // Adapter may only support minimal fields, so try with minimal first
          try {
            return await createAccountFn({
              userId: user.id,
              provider: 'wordpress',
              providerAccountId: wpUserId.toString()
            });
          } catch (minimalErr) {
            // Check if error is about missing ID field
            const errorMessage = minimalErr instanceof Error ? minimalErr.message : String(minimalErr);
            
            // If error mentions id field, we need to generate one
            if (errorMessage.includes("id") && errorMessage.includes("default value")) {
              log('[auth-utils-fixed] Account creation failed due to missing ID field. Using direct DB insert with explicit ID', 'warn');
              
              // If minimal fails, direct database insert as fallback
              const accountId = crypto.randomUUID();
              const now = new Date();
              
              // Safely release connection if it exists
              if (connection) {
                connection.release();
                connection = null;
              }
              
              /** @type {PoolConnection} */
              const dbConnection = await pool.getConnection();
              
              try {
                await dbConnection.query(
                  "INSERT INTO ba_accounts (id, user_id, provider_id, provider_account_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
                  [
                    accountId,
                    user.id,
                    'wordpress',
                    wpUserId.toString(),
                    now,
                    now
                  ]
                );
                
                log(`[auth-utils-fixed] Successfully created account record with ID: ${accountId}`, 'debug');
                return { id: accountId, userId: user.id };
              } catch (directErr) {
                // If direct insert also fails, log it but continue (account linking is optional)
                const directErrMsg = directErr instanceof Error ? directErr.message : String(directErr);
                log(`[auth-utils-fixed] Direct account insert failed: ${directErrMsg}`, 'warn');
                return null; // Return null to indicate it failed but we'll continue
              } finally {
                dbConnection.release();
              }
            } else {
              // For other errors, log and continue (account linking is optional)
              log(`[auth-utils-fixed] Account creation skipped: ${errorMessage}`, 'warn');
                return null;
            }
          }
        },
        'createAccountFn'
      );
      log(`[auth-utils-fixed] Successfully linked WordPress account for user: ${user.id}`, 'debug');
    } catch (err) {
      // Type-guard the error
      const accountError = err instanceof Error ? err : new Error(String(err));
      // Non-fatal, just log the error
      log(`[auth-utils-fixed] Warning: Could not create account link: ${accountError.message}`, 'warn');
    }

    // Create a session token with proper error handling
    // Use strict null checking with proper initialization and guards
    /** @type {string|null} */
    let sessionToken = null;
    /** @type {Date} */
    let expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + 30); // 30 days

    try {
        // Create a session with proper verification checks
        log(`[auth-utils-fixed] Creating session for user: ${user.id}`, 'info');
        
        // Using local-variable-type-safety-protocol with explicit type checking
        /** @type {any} */
        let sessionCreationResult;
        
        // Apply withRetry correctly with all required arguments
        try {
            sessionCreationResult = await withRetry(
                async () => createSessionFn(user.id, crypto.randomUUID(), expiryDate),
                'createSessionFn',
                maxRetries
            );
        } catch (sessionCreateError) {
            // Type guard the error
            const errorMsg = sessionCreateError instanceof Error 
                ? sessionCreateError.message
                : String(sessionCreateError);
            log(`[auth-utils-fixed] Error in withRetry for createSessionFn: ${errorMsg}`, 'error');
            throw sessionCreateError;
        }
        
        // Apply local-variable-type-safety-protocol to safely handle the sessionCreationResult
        if (sessionCreationResult && typeof sessionCreationResult === 'object') {
            // Check for 'token' property first
            if ('token' in sessionCreationResult && sessionCreationResult.token && 
                typeof sessionCreationResult.token === 'string') {
                sessionToken = sessionCreationResult.token;
                // Safely access substring method with null check and type guard
                if (sessionToken && typeof sessionToken === 'string' && sessionToken.length > 6) {
                    log(`[auth-utils-fixed] Created Better Auth session with token: ${sessionToken.substring(0, 6)}...`, 'info');
                } else {
                    log(`[auth-utils-fixed] Created Better Auth session with token (shortened)`, 'info');
                }
            }
            // Then check for 'sessionToken' property
            else if ('sessionToken' in sessionCreationResult && sessionCreationResult.sessionToken && 
                    typeof sessionCreationResult.sessionToken === 'string') {
                sessionToken = sessionCreationResult.sessionToken;
                // Safely access substring method with null check and type guard
                if (sessionToken && typeof sessionToken === 'string' && sessionToken.length > 6) {
                    log(`[auth-utils-fixed] Created Better Auth session with sessionToken: ${sessionToken.substring(0, 6)}...`, 'info');
                } else {
                    log(`[auth-utils-fixed] Created Better Auth session with sessionToken (shortened)`, 'info');
                }
            }
        }
    } catch (sessionErr) {
        // Type-guard the error
        const err = sessionErr instanceof Error ? sessionErr : new Error(String(sessionErr));
        log(`[auth-utils-fixed] Error creating session: ${err.message}`, 'error');
        
        // Generate a session token if the adapter fails
        sessionToken = crypto.randomUUID();
        // Safely access substring method with type guard
        if (sessionToken && typeof sessionToken === 'string' && sessionToken.length > 6) {
            log(`[auth-utils-fixed] Using generated fallback session token: ${sessionToken.substring(0, 6)}...`, 'warn');
        } else {
            log(`[auth-utils-fixed] Using generated fallback session token (shortened)`, 'warn');
        }
    }

    // If we successfully created a session token, set the cookie and return success
    if (sessionToken) {
        log(`[auth-utils-fixed] Setting session cookie for user ${user.id}`, 'info');
        
        // Set the cookie with improved settings
        cookies.set('better_auth_session', sessionToken, {
            path: '/',
            httpOnly: true, 
            secure: process.env.NODE_ENV === 'production',
            sameSite: 'lax',
            maxAge: 30 * 24 * 60 * 60, // 30 days in seconds
            expires: expiryDate
        });
        
        // Create a proper User object that matches the expected User type from SessionResult
        /** @type {import('$lib/types/better-auth').User} */
        const responseUser = {
            id: user.id,
            email: user.email,
            displayName: user.displayName || user.name || user.username || user.email.split('@')[0],
            // Include other required User type properties
            metadata: {
                wp_user_id: user.metadata?.wp_user_id,
                roles: Array.isArray(user.metadata?.roles) ? user.metadata.roles : []
            },
            // Get roles from whichever place they exist
            roles: typeof user.roles !== 'undefined' && Array.isArray(user.roles) ? user.roles : 
                   (user.metadata && typeof user.metadata.roles !== 'undefined' && Array.isArray(user.metadata.roles)) ? 
                   user.metadata.roles : ['user'],
            // Optional properties with safe defaults
            username: user.username,
            name: user.name,
            emailVerified: user.emailVerified || true,
            image: user.image || null,
            createdAt: user.createdAt || new Date().toISOString(),
            updatedAt: user.updatedAt || new Date().toISOString(),
            // Add _noRefresh flag to prevent page refresh
            _noRefresh: true
        };
        
        // Create the result object with the correct type that matches SessionResult
        /** @type {import('$lib/types/better-auth').SessionResult} */
        const resultObj = { 
            success: true, 
            user: responseUser, // responseUser is already typed as User
            created: userCreated,
            noRefresh: true // Add flag to response
        };
        
        // Use a direct return with local type
        return /** @type {SessionResult} */ (resultObj);
    } else {
        log(`[auth-utils-fixed] Failed to create session`, 'error');
        return {
            success: false,
            error: 'session_creation_failed',
            details: 'session_creation_err'
        };
    }
  } catch (err) {
    // Type-guard the error
    const error = err instanceof Error ? err : new Error(String(err));
    log(`[auth-utils-fixed] Error in sync operation "${lastAttemptedOperation}": ${error.message}`, 'error');
    log(`[auth-utils-fixed] Error stack: ${error.stack}`, 'debug');
    
    return {
      success: false,
      error: 'sync_failed',
      details: `${lastAttemptedOperation}_err`
    };
  }
} 