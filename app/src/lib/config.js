/**
 * @file Environment configuration with TypeScript definitions
 * @description Provides typed access to environment variables with documentation
 */

/**
 * WordPress API URLs and endpoints
 * @type {string}
 */
export const WP_API_URL = import.meta.env.PUBLIC_WP_API_URL || 'https://asapdigest.local';

/**
 * WordPress GraphQL URL
 * @type {string}
 */
export const WP_GRAPHQL_URL = import.meta.env.PUBLIC_WP_GRAPHQL_URL || 'https://asapdigest.local/graphql';

/**
 * WordPress site URL
 * @type {string}
 */
export const SITE_URL = import.meta.env.PUBLIC_SITE_URL || 'https://asapdigest.local';

/**
 * Sync secret used for server-to-server communication between WordPress and SvelteKit
 * @type {string}
 */
export const SYNC_SECRET = import.meta.env.ASAP_SK_SYNC_SECRET || 'shared_secret_for_server_to_server_auth';

/**
 * WordPress session check URL
 * @type {string}
 */
export const WP_CHECK_SESSION_URL = import.meta.env.PUBLIC_WP_CHECK_SESSION_URL || 'https://asapdigest.local/wp-json/asap/v1/check-wp-session';

/**
 * Auth API URL used for authentication requests
 * @type {string}
 */
export const AUTH_API_URL = import.meta.env.PUBLIC_AUTH_API_URL || 'https://asapdigest.local';

/**
 * Better Auth secret used for token signing
 * @type {string}
 */
export const BETTER_AUTH_SECRET = import.meta.env.BETTER_AUTH_SECRET || 'development_auth_secret';

/**
 * Database connection URL
 * @type {string}
 */
export const DATABASE_URL = import.meta.env.DATABASE_URL || 'mysql://root:root@localhost/asapdigest';

/**
 * Site name for display
 * @type {string}
 */
export const SITE_NAME = import.meta.env.PUBLIC_SITE_NAME || '⚡️ ASAP Digest';

/**
 * Current environment (development, production, etc)
 * @type {string}
 */
export const APP_ENV = import.meta.env.PUBLIC_APP_ENV || 'development';

/**
 * Application URL
 * @type {string}
 */
export const APP_URL = import.meta.env.PUBLIC_APP_URL || 'https://localhost:5173';

/**
 * Flag to enable/disable PWA features
 * @type {boolean}
 */
export const ENABLE_PWA = import.meta.env.PUBLIC_ENABLE_PWA === 'true';

/**
 * Application version
 * @type {string}
 */
export const APP_VERSION = import.meta.env.PUBLIC_APP_VERSION || '1.0.0';

/**
 * Checks if the current environment is development
 * @type {boolean}
 */
export const IS_DEV = APP_ENV === 'development';

/**
 * Checks if the current environment is production
 * @type {boolean}
 */
export const IS_PROD = APP_ENV === 'production';

/**
 * Log level for application logs
 * @type {string}
 */
export const LOG_LEVEL = import.meta.env.LOG_LEVEL || 'debug';

/**
 * Session duration in days
 * @type {number}
 */
export const SESSION_DURATION_DAYS = parseInt(import.meta.env.SESSION_DURATION_DAYS || '30', 10); 