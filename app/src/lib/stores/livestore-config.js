/**
 * LiveStore Configuration for ASAP Digest
 * Integrates seamlessly with V6 Auto-Login system
 * 
 * @fileoverview Local-first storage configuration using LiveStore
 * User authentication handled by existing V6 auto-login system
 */

// @ts-ignore - External package import
import { LiveStore } from '@livestore/client';
import { getUserData } from './user.js'; // Existing auto-login function
import { log } from '$lib/utils/log.js';

/**
 * @typedef {Object} LiveStoreConfig
 * @property {string} name - Database name
 * @property {Object} schema - Database schema
 * @property {Object} sync - Sync configuration
 */

/**
 * Get Better Auth token for LiveStore sync
 * Uses existing auto-login infrastructure
 * @returns {Promise<string|null>} Auth token
 */
async function getBetterAuthToken() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return null;
    
    // Get token from existing Better Auth session
    const response = await fetch('/api/auth/session', {
      credentials: 'include'
    });
    
    if (response.ok) {
      const session = await response.json();
      return session.token;
    }
    
    return null;
  } catch (error) {
    log(`[LiveStore] Error getting auth token: ${error.message}`, 'error');
    return null;
  }
}

/**
 * LiveStore configuration with auto-login integration
 */
export const liveStore = new LiveStore({
  name: 'asap-digest',
  schema: {
    // User data (enhanced, but auto-login remains authoritative)
    users: {
      id: 'string',
      email: 'string', 
      displayName: 'string',
      preferences: 'json',
      metadata: 'json',
      // V6 Auto-Login integration fields
      wpUserId: 'number',
      betterAuthId: 'string',
      autoLoginSynced: 'boolean',
      lastAutoLoginSync: 'datetime',
      // Local-first enhancements
      localPreferences: 'json',
      offlineCapabilities: 'json',
      createdAt: 'datetime',
      updatedAt: 'datetime'
    },
    
    // Business objects (primary focus for local-first)
    digests: {
      id: 'string',
      title: 'string',
      description: 'text',
      status: 'string',
      authorId: 'string',
      contentBlocks: 'json',
      analytics: 'json',
      collaborators: 'json',
      // WordPress integration
      wpPostId: 'number',
      wpSynced: 'boolean',
      lastWpSync: 'datetime',
      // Metadata
      createdAt: 'datetime',
      updatedAt: 'datetime',
      lastModifiedBy: 'string'
    },
    
    content: {
      id: 'string',
      sourceId: 'string',
      title: 'string',
      content: 'text',
      metadata: 'json',
      aiProcessedData: 'json',
      qualityScore: 'number',
      status: 'string',
      usageCount: 'number',
      lastUsedInDigest: 'string',
      lastUsedAt: 'datetime',
      mediaAttachments: 'json',
      source: 'json',
      // WordPress integration
      wpPostId: 'number',
      wpSynced: 'boolean',
      lastWpSync: 'datetime',
      // Moderation
      moderationStatus: 'string',
      moderationNotes: 'text',
      moderatedAt: 'datetime',
      moderatedBy: 'string',
      // Metadata
      createdAt: 'datetime',
      updatedAt: 'datetime'
    },
    
    sources: {
      id: 'string',
      name: 'string',
      type: 'string',
      url: 'string',
      configuration: 'json',
      credentials: 'json',
      status: 'string',
      lastSync: 'datetime',
      syncFrequency: 'number',
      contentCount: 'number',
      errorCount: 'number',
      lastError: 'text',
      // WordPress integration
      wpSynced: 'boolean',
      lastWpSync: 'datetime',
      // Metadata
      createdAt: 'datetime',
      updatedAt: 'datetime',
      createdBy: 'string'
    },
    
    analytics: {
      id: 'string',
      entityType: 'string',
      entityId: 'string',
      eventType: 'string',
      eventData: 'json',
      timestamp: 'datetime',
      userId: 'string',
      sessionId: 'string',
      // Aggregation
      aggregatedData: 'json',
      aggregatedAt: 'datetime'
    },
    
    workflows: {
      id: 'string',
      name: 'string',
      description: 'text',
      type: 'string',
      configuration: 'json',
      status: 'string',
      triggers: 'json',
      actions: 'json',
      lastRun: 'datetime',
      runCount: 'number',
      successCount: 'number',
      errorCount: 'number',
      // Metadata
      createdAt: 'datetime',
      updatedAt: 'datetime',
      createdBy: 'string'
    }
  },
  
  sync: {
    url: '/api/livestore-sync',
    auth: getBetterAuthToken, // Uses existing Better Auth
    // Respect auto-login priority for user data
    conflictResolution: 'auto-login-priority',
    // Sync interval
    interval: 30000,
    // Only sync when authenticated
    enabled: async () => {
      const currentUser = await getUserData();
      return !!currentUser;
    }
  }
});

/**
 * Initialize local-first system with auto-login integration
 * This function should be called after auto-login completes
 * @returns {Promise<LiveStore>} Configured LiveStore instance
 */
export async function initializeLocalFirstWithAutoLogin() {
  try {
    log('[LiveStore] Initializing local-first system with auto-login integration', 'info');
    
    // Wait for auto-login to complete
    const currentUser = await getUserData();
    
    if (currentUser) {
      log(`[LiveStore] Auto-login active for user: ${currentUser.email}`, 'info');
      
      // Sync current user to LiveStore (auto-login data takes priority)
      await liveStore.users.upsert({
        id: currentUser.id,
        email: currentUser.email,
        displayName: currentUser.getDisplayName(),
        wpUserId: currentUser.wp_user_id,
        betterAuthId: currentUser.id,
        autoLoginSynced: true,
        lastAutoLoginSync: new Date().toISOString(),
        metadata: currentUser.metadata || {},
        preferences: currentUser.preferences || {},
        // Initialize local-first fields
        localPreferences: {},
        offlineCapabilities: {
          enabled: false,
          syncOnReconnect: true
        },
        updatedAt: new Date().toISOString()
      });
      
      log('[LiveStore] User data synced to local store', 'debug');
    } else {
      log('[LiveStore] No authenticated user found, local-first features limited', 'warn');
    }
    
    return liveStore;
    
  } catch (error) {
    log(`[LiveStore] Error initializing local-first system: ${error.message}`, 'error');
    throw error;
  }
}

/**
 * Check if local-first system is ready
 * @returns {Promise<boolean>} Whether system is ready
 */
export async function isLocalFirstReady() {
  try {
    const currentUser = await getUserData();
    return !!currentUser;
  } catch (error) {
    return false;
  }
} 