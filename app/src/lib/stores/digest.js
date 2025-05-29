/**
 * Digest Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Digest business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Digest status types
 * @typedef {'draft' | 'scheduled' | 'published' | 'archived'} DigestStatus
 */

/**
 * Content block for digest layout
 * @typedef {Object} ContentBlock
 * @property {string} id - Block identifier
 * @property {string} type - Block type (article, video, podcast, etc)
 * @property {string} contentId - Reference to content item
 * @property {number} order - Display order
 * @property {Object} layout - Layout configuration
 * @property {Object} styling - Styling options
 */

/**
 * Email configuration for digest delivery
 * @typedef {Object} EmailConfig
 * @property {string} subject - Email subject line
 * @property {string} previewText - Email preview text
 * @property {Object} sendSettings - Delivery settings
 * @property {string[]} testRecipients - Test email addresses
 */

/**
 * Digest analytics data
 * @typedef {Object} DigestAnalytics
 * @property {number} openRate - Email open rate percentage
 * @property {number} clickRate - Click-through rate percentage
 * @property {number} engagementScore - Overall engagement score
 * @property {Object} metrics - Detailed metrics
 */

/**
 * Enhanced Digest object with comprehensive fields
 * @typedef {Object} Digest
 * @property {string} id - Digest identifier
 * @property {string} title - Digest title
 * @property {string} description - Digest description
 * @property {DigestStatus} status - Current status
 * @property {string} authorId - Author user ID
 * @property {string[]} collaborators - Collaborator user IDs
 * @property {Object} permissions - Access permissions
 * @property {ContentBlock[]} contentBlocks - Content layout
 * @property {string} templateId - Template identifier
 * @property {Object} layoutConfig - Layout configuration
 * @property {Object} stylingOptions - Styling options
 * @property {EmailConfig} emailConfig - Email settings
 * @property {DigestAnalytics} analytics - Analytics data
 * @property {number} version - Version number
 * @property {Object[]} revisionHistory - Revision history
 * @property {string[]} tags - Tags for categorization
 * @property {string[]} categories - Categories
 * @property {string} targetAudience - Target audience
 * @property {Object} aiEnhancementSettings - AI enhancement config
 * @property {Object} autoGenerationRules - Auto-generation rules
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Date} publishedAt - Publication timestamp
 * @property {Date} scheduledFor - Scheduled publication time
 * @property {Object} metadata - Additional metadata
 * @property {number} wpPostId - WordPress post ID
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<Digest[]>} */
export const digestsStore = writable([]);

/**
 * Normalize digest data from any source to consistent format
 * @param {Object} rawDigestData - Raw digest data
 * @returns {Object|null} Normalized digest data
 */
function normalizeDigestData(rawDigestData) {
  if (!rawDigestData || typeof rawDigestData !== 'object' || !rawDigestData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawDigestData.id,
    title: rawDigestData.title || 'Untitled Digest',
    description: rawDigestData.description || '',
    status: rawDigestData.status || 'draft',
    
    // Authorship & Collaboration
    authorId: rawDigestData.authorId || rawDigestData.author_id || null,
    collaborators: Array.isArray(rawDigestData.collaborators) ? rawDigestData.collaborators : [],
    permissions: rawDigestData.permissions || {},
    
    // Content & Layout
    contentBlocks: Array.isArray(rawDigestData.contentBlocks) ? rawDigestData.contentBlocks : 
                   Array.isArray(rawDigestData.content_blocks) ? rawDigestData.content_blocks : [],
    templateId: rawDigestData.templateId || rawDigestData.template_id || 'default',
    layoutConfig: rawDigestData.layoutConfig || rawDigestData.layout_config || {},
    stylingOptions: rawDigestData.stylingOptions || rawDigestData.styling_options || {},
    
    // Email Configuration
    emailConfig: rawDigestData.emailConfig || rawDigestData.email_config || {
      subject: rawDigestData.title || 'Untitled Digest',
      previewText: '',
      sendSettings: {},
      testRecipients: []
    },
    
    // Analytics
    analytics: rawDigestData.analytics || {
      openRate: 0,
      clickRate: 0,
      engagementScore: 0,
      metrics: {}
    },
    
    // Versioning
    version: rawDigestData.version || 1,
    revisionHistory: Array.isArray(rawDigestData.revisionHistory) ? rawDigestData.revisionHistory :
                     Array.isArray(rawDigestData.revision_history) ? rawDigestData.revision_history : [],
    
    // Categorization
    tags: Array.isArray(rawDigestData.tags) ? rawDigestData.tags : [],
    categories: Array.isArray(rawDigestData.categories) ? rawDigestData.categories : [],
    targetAudience: rawDigestData.targetAudience || rawDigestData.target_audience || 'general',
    
    // AI & Automation
    aiEnhancementSettings: rawDigestData.aiEnhancementSettings || rawDigestData.ai_enhancement_settings || {},
    autoGenerationRules: rawDigestData.autoGenerationRules || rawDigestData.auto_generation_rules || {},
    
    // Timestamps
    createdAt: rawDigestData.createdAt || rawDigestData.created_at || new Date().toISOString(),
    updatedAt: rawDigestData.updatedAt || rawDigestData.updated_at || new Date().toISOString(),
    publishedAt: rawDigestData.publishedAt || rawDigestData.published_at || null,
    scheduledFor: rawDigestData.scheduledFor || rawDigestData.scheduled_for || null,
    
    // WordPress Integration
    wpPostId: rawDigestData.wpPostId || rawDigestData.wp_post_id || null,
    wpSynced: rawDigestData.wpSynced || rawDigestData.wp_synced || false,
    lastWpSync: rawDigestData.lastWpSync || rawDigestData.last_wp_sync || null,
    
    // Metadata
    metadata: rawDigestData.metadata || {}
  };
}

/**
 * Get comprehensive digest data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} digest - Raw digest data
 * @returns {Object} Digest helper with getters and methods
 */
export function getDigestData(digest) {
  const normalizedDigest = normalizeDigestData(digest);
  
  if (!normalizedDigest) {
    return createEmptyDigestHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedDigest.id; },
    get title() { return normalizedDigest.title; },
    get description() { return normalizedDigest.description; },
    get status() { return normalizedDigest.status; },
    
    // Status Checks
    get isDraft() { return this.status === 'draft'; },
    get isScheduled() { return this.status === 'scheduled'; },
    get isPublished() { return this.status === 'published'; },
    get isArchived() { return this.status === 'archived'; },
    get isActive() { return this.status === 'published' || this.status === 'scheduled'; },
    
    // Authorship & Collaboration
    get authorId() { return normalizedDigest.authorId; },
    get collaborators() { return normalizedDigest.collaborators; },
    get permissions() { return normalizedDigest.permissions; },
    get hasCollaborators() { return this.collaborators.length > 0; },
    get collaboratorCount() { return this.collaborators.length; },
    
    // Content & Layout
    get contentBlocks() { return normalizedDigest.contentBlocks; },
    get contentCount() { return this.contentBlocks.length; },
    get hasContent() { return this.contentCount > 0; },
    get templateId() { return normalizedDigest.templateId; },
    get layoutConfig() { return normalizedDigest.layoutConfig; },
    get stylingOptions() { return normalizedDigest.stylingOptions; },
    
    // Content Analysis
    get contentTypes() {
      return [...new Set(this.contentBlocks.map(block => block.type))];
    },
    get primaryContentType() {
      if (this.contentBlocks.length === 0) return null;
      const typeCounts = {};
      this.contentBlocks.forEach(block => {
        typeCounts[block.type] = (typeCounts[block.type] || 0) + 1;
      });
      return Object.keys(typeCounts).reduce((a, b) => typeCounts[a] > typeCounts[b] ? a : b);
    },
    
    // Email Configuration
    get emailConfig() { return normalizedDigest.emailConfig; },
    get emailSubject() { return this.emailConfig.subject; },
    get emailPreviewText() { return this.emailConfig.previewText; },
    get sendSettings() { return this.emailConfig.sendSettings; },
    get testRecipients() { return this.emailConfig.testRecipients; },
    get hasTestRecipients() { return this.testRecipients.length > 0; },
    
    // Analytics
    get analytics() { return normalizedDigest.analytics; },
    get openRate() { return this.analytics.openRate; },
    get clickRate() { return this.analytics.clickRate; },
    get engagementScore() { return this.analytics.engagementScore; },
    get metrics() { return this.analytics.metrics; },
    get hasAnalytics() { return this.openRate > 0 || this.clickRate > 0; },
    
    // Performance Ratings
    get performanceRating() {
      if (!this.hasAnalytics) return 'unrated';
      const score = this.engagementScore;
      if (score >= 80) return 'excellent';
      if (score >= 60) return 'good';
      if (score >= 40) return 'average';
      if (score >= 20) return 'poor';
      return 'very-poor';
    },
    
    // Versioning
    get version() { return normalizedDigest.version; },
    get revisionHistory() { return normalizedDigest.revisionHistory; },
    get hasRevisions() { return this.revisionHistory.length > 0; },
    get revisionCount() { return this.revisionHistory.length; },
    get lastRevision() { 
      return this.hasRevisions ? this.revisionHistory[this.revisionHistory.length - 1] : null;
    },
    
    // Categorization
    get tags() { return normalizedDigest.tags; },
    get categories() { return normalizedDigest.categories; },
    get targetAudience() { return normalizedDigest.targetAudience; },
    get hasTags() { return this.tags.length > 0; },
    get hasCategories() { return this.categories.length > 0; },
    get tagCount() { return this.tags.length; },
    get categoryCount() { return this.categories.length; },
    
    // AI & Automation
    get aiEnhancementSettings() { return normalizedDigest.aiEnhancementSettings; },
    get autoGenerationRules() { return normalizedDigest.autoGenerationRules; },
    get hasAIEnhancements() { return Object.keys(this.aiEnhancementSettings).length > 0; },
    get hasAutoGeneration() { return Object.keys(this.autoGenerationRules).length > 0; },
    
    // Timestamps
    get createdAt() { return normalizedDigest.createdAt; },
    get updatedAt() { return normalizedDigest.updatedAt; },
    get publishedAt() { return normalizedDigest.publishedAt; },
    get scheduledFor() { return normalizedDigest.scheduledFor; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get daysSinceUpdate() {
      const updated = new Date(this.updatedAt);
      const now = new Date();
      return Math.floor((now.getTime() - updated.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get isRecent() { return this.age <= 7; },
    get isStale() { return this.daysSinceUpdate > 30; },
    
    // Schedule Analysis
    get isScheduledForFuture() {
      if (!this.scheduledFor) return false;
      return new Date(this.scheduledFor) > new Date();
    },
    get isOverdue() {
      if (!this.scheduledFor || this.isPublished) return false;
      return new Date(this.scheduledFor) < new Date();
    },
    get timeUntilScheduled() {
      if (!this.scheduledFor) return null;
      const scheduled = new Date(this.scheduledFor);
      const now = new Date();
      return scheduled.getTime() - now.getTime(); // milliseconds
    },
    
    // WordPress Integration
    get wpPostId() { return normalizedDigest.wpPostId; },
    get wpSynced() { return normalizedDigest.wpSynced; },
    get lastWpSync() { return normalizedDigest.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced && !!this.wpPostId; },
    get needsWordPressSync() { 
      if (!this.wpPostId) return true;
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Metadata
    get metadata() { return normalizedDigest.metadata; },
    
    // Validation
    get isValid() {
      return !!(this.id && this.title && this.authorId);
    },
    get isComplete() {
      return this.isValid && this.hasContent && this.emailSubject;
    },
    get isReadyToPublish() {
      return this.isComplete && (this.isDraft || this.isScheduled);
    },
    get isReadyToSchedule() {
      return this.isComplete && this.isDraft;
    },
    
    // Utility Methods
    hasTag(tag) {
      return this.tags.includes(tag);
    },
    hasCategory(category) {
      return this.categories.includes(category);
    },
    hasCollaborator(userId) {
      return this.collaborators.includes(userId);
    },
    getContentBlock(blockId) {
      return this.contentBlocks.find(block => block.id === blockId);
    },
    getContentByType(type) {
      return this.contentBlocks.filter(block => block.type === type);
    },
    
    // Permission Checks
    canEdit(userId) {
      return this.authorId === userId || this.hasCollaborator(userId);
    },
    canPublish(userId) {
      // Add role-based checks here if needed
      return this.canEdit(userId);
    },
    canDelete(userId) {
      return this.authorId === userId;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        title: this.title,
        status: this.status,
        authorId: this.authorId,
        contentCount: this.contentCount,
        collaboratorCount: this.collaboratorCount,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isReadyToPublish: this.isReadyToPublish,
        wpSynced: this.wpSynced,
        age: this.age,
        performanceRating: this.performanceRating
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        title: this.title,
        description: this.description,
        status: this.status,
        
        // Authorship
        authorId: this.authorId,
        collaborators: this.collaborators,
        permissions: this.permissions,
        
        // Content
        contentBlocks: this.contentBlocks,
        templateId: this.templateId,
        layoutConfig: this.layoutConfig,
        stylingOptions: this.stylingOptions,
        
        // Email
        emailConfig: this.emailConfig,
        
        // Analytics
        analytics: this.analytics,
        
        // Versioning
        version: this.version,
        revisionHistory: this.revisionHistory,
        
        // Categorization
        tags: this.tags,
        categories: this.categories,
        targetAudience: this.targetAudience,
        
        // AI & Automation
        aiEnhancementSettings: this.aiEnhancementSettings,
        autoGenerationRules: this.autoGenerationRules,
        
        // Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        publishedAt: this.publishedAt,
        scheduledFor: this.scheduledFor,
        
        // WordPress
        wpPostId: this.wpPostId,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync,
        
        // Metadata
        metadata: this.metadata
      };
    }
  };
}

/**
 * Create empty digest helper for null/undefined digests
 * @returns {Object} Empty digest helper with safe defaults
 */
function createEmptyDigestHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get title() { return 'New Digest'; },
    get description() { return ''; },
    get status() { return 'draft'; },
    
    // Status Checks
    get isDraft() { return true; },
    get isScheduled() { return false; },
    get isPublished() { return false; },
    get isArchived() { return false; },
    get isActive() { return false; },
    
    // Authorship & Collaboration
    get authorId() { return null; },
    get collaborators() { return []; },
    get permissions() { return {}; },
    get hasCollaborators() { return false; },
    get collaboratorCount() { return 0; },
    
    // Content & Layout
    get contentBlocks() { return []; },
    get contentCount() { return 0; },
    get hasContent() { return false; },
    get templateId() { return 'default'; },
    get layoutConfig() { return {}; },
    get stylingOptions() { return {}; },
    get contentTypes() { return []; },
    get primaryContentType() { return null; },
    
    // Email Configuration
    get emailConfig() { return { subject: '', previewText: '', sendSettings: {}, testRecipients: [] }; },
    get emailSubject() { return ''; },
    get emailPreviewText() { return ''; },
    get sendSettings() { return {}; },
    get testRecipients() { return []; },
    get hasTestRecipients() { return false; },
    
    // Analytics
    get analytics() { return { openRate: 0, clickRate: 0, engagementScore: 0, metrics: {} }; },
    get openRate() { return 0; },
    get clickRate() { return 0; },
    get engagementScore() { return 0; },
    get metrics() { return {}; },
    get hasAnalytics() { return false; },
    get performanceRating() { return 'unrated'; },
    
    // Versioning
    get version() { return 1; },
    get revisionHistory() { return []; },
    get hasRevisions() { return false; },
    get revisionCount() { return 0; },
    get lastRevision() { return null; },
    
    // Categorization
    get tags() { return []; },
    get categories() { return []; },
    get targetAudience() { return 'general'; },
    get hasTags() { return false; },
    get hasCategories() { return false; },
    get tagCount() { return 0; },
    get categoryCount() { return 0; },
    
    // AI & Automation
    get aiEnhancementSettings() { return {}; },
    get autoGenerationRules() { return {}; },
    get hasAIEnhancements() { return false; },
    get hasAutoGeneration() { return false; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get publishedAt() { return null; },
    get scheduledFor() { return null; },
    get age() { return 0; },
    get daysSinceUpdate() { return 0; },
    get isRecent() { return false; },
    get isStale() { return false; },
    
    // Schedule Analysis
    get isScheduledForFuture() { return false; },
    get isOverdue() { return false; },
    get timeUntilScheduled() { return null; },
    
    // WordPress Integration
    get wpPostId() { return null; },
    get wpSynced() { return false; },
    get lastWpSync() { return null; },
    get isSyncedToWordPress() { return false; },
    get needsWordPressSync() { return false; },
    
    // Metadata
    get metadata() { return {}; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isReadyToPublish() { return false; },
    get isReadyToSchedule() { return false; },
    
    // Utility Methods
    hasTag(tag) { return false; },
    hasCategory(category) { return false; },
    hasCollaborator(userId) { return false; },
    getContentBlock(blockId) { return null; },
    getContentByType(type) { return []; },
    
    // Permission Checks
    canEdit(userId) { return false; },
    canPublish(userId) { return false; },
    canDelete(userId) { return false; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        title: 'New Digest',
        status: 'draft',
        authorId: null,
        contentCount: 0,
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        title: 'New Digest',
        status: 'draft',
        contentBlocks: [],
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Digests
 */

/**
 * Create a new digest
 * @param {Object} digestData - Initial digest data
 * @returns {Promise<Object>} Created digest
 */
export async function createDigest(digestData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create digest');
    }

    const newDigest = {
      id: crypto.randomUUID(),
      ...digestData,
      authorId: currentUser.id,
      status: 'draft',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.digests.create(newDigest);
    }

    // Update local store
    digestsStore.update(digests => [...digests, newDigest]);

    log(`[Digest] Created new digest: ${newDigest.id}`, 'info');
    return getDigestData(newDigest);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Digest] Error creating digest: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update an existing digest
 * @param {string} digestId - Digest ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated digest
 */
export async function updateDigest(digestId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.digests.update(digestId, updatedData);
    }

    // Update local store
    digestsStore.update(digests => 
      digests.map(digest => 
        digest.id === digestId 
          ? { ...digest, ...updatedData }
          : digest
      )
    );

    log(`[Digest] Updated digest: ${digestId}`, 'info');
    
    // Return updated digest data
    const updatedDigest = await getDigestById(digestId);
    return updatedDigest;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Digest] Error updating digest: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete a digest
 * @param {string} digestId - Digest ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteDigest(digestId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.digests.delete(digestId);
    }

    // Update local store
    digestsStore.update(digests => 
      digests.filter(digest => digest.id !== digestId)
    );

    log(`[Digest] Deleted digest: ${digestId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Digest] Error deleting digest: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get digest by ID
 * @param {string} digestId - Digest ID
 * @returns {Promise<Object|null>} Digest data or null
 */
export async function getDigestById(digestId) {
  try {
    let digest = null;

    // Try LiveStore first
    if (browser && liveStore) {
      digest = await liveStore.digests.findById(digestId);
    }

    // Fallback to local store
    if (!digest) {
      const digests = await new Promise(resolve => {
        digestsStore.subscribe(value => resolve(value))();
      });
      digest = digests.find(d => d.id === digestId);
    }

    return digest ? getDigestData(digest) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Digest] Error getting digest by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get all digests for current user
 * @returns {Promise<Object[]>} Array of digest data objects
 */
export async function getUserDigests() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return [];

    let digests = [];

    // Try LiveStore first
    if (browser && liveStore) {
      digests = await liveStore.digests.findMany({
        where: {
          OR: [
            { authorId: currentUser.id },
            { collaborators: { contains: currentUser.id } }
          ]
        }
      });
    }

    // Fallback to local store
    if (digests.length === 0) {
      const allDigests = await new Promise(resolve => {
        digestsStore.subscribe(value => resolve(value))();
      });
      digests = allDigests.filter(digest => 
        digest.authorId === currentUser.id || 
        digest.collaborators?.includes(currentUser.id)
      );
    }

    return digests.map(digest => getDigestData(digest));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Digest] Error getting user digests: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: digestsStore,
  getDigestData,
  createDigest,
  updateDigest,
  deleteDigest,
  getDigestById,
  getUserDigests
}; 