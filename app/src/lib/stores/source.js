/**
 * Source Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Source business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Source status types
 * @typedef {'active' | 'paused' | 'error' | 'disabled'} SourceStatus
 */

/**
 * Source type categories
 * @typedef {'rss' | 'api' | 'scraper' | 'webhook' | 'manual'} SourceType
 */

/**
 * Health metrics for source monitoring
 * @typedef {Object} HealthMetrics
 * @property {number} successRate - Success rate percentage
 * @property {number} errorCount - Total error count
 * @property {number} avgResponseTime - Average response time in ms
 * @property {Date} lastSuccessfulFetch - Last successful fetch timestamp
 * @property {string} lastError - Last error message
 */

/**
 * Content statistics for source performance
 * @typedef {Object} ContentStats
 * @property {number} totalFetched - Total content items fetched
 * @property {number} approvedRate - Approval rate percentage
 * @property {number} qualityAvg - Average quality score
 * @property {number} duplicateRate - Duplicate detection rate
 */

/**
 * Enhanced Source object with comprehensive fields
 * @typedef {Object} Source
 * @property {string} id - Source identifier
 * @property {string} name - Source name
 * @property {string} description - Source description
 * @property {string} url - Source URL
 * @property {SourceType} sourceType - Type of source
 * @property {SourceStatus} status - Current status
 * @property {Object} configuration - Source configuration
 * @property {Object} credentials - Authentication credentials
 * @property {Object} schedule - Fetch schedule configuration
 * @property {HealthMetrics} healthMetrics - Health monitoring data
 * @property {ContentStats} contentStats - Content performance stats
 * @property {Object} rateLimits - Rate limiting configuration
 * @property {Object} quotaSettings - Quota management
 * @property {number} priorityLevel - Source priority (1-10)
 * @property {Object[]} errorLog - Error history
 * @property {Object[]} performanceHistory - Performance tracking
 * @property {Object} aiProcessingRules - AI processing configuration
 * @property {Object} contentFilters - Content filtering rules
 * @property {string} createdBy - Creator user ID
 * @property {string} managedBy - Manager user ID
 * @property {Date} lastModified - Last modification timestamp
 * @property {Date} lastSync - Last synchronization timestamp
 * @property {Date} nextSync - Next scheduled sync
 * @property {number} syncFrequency - Sync frequency in minutes
 * @property {number} contentCount - Total content items from source
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<Source[]>} */
export const sourcesStore = writable([]);

/**
 * Normalize source data from any source to consistent format
 * @param {Object} rawSourceData - Raw source data
 * @returns {Object|null} Normalized source data
 */
function normalizeSourceData(rawSourceData) {
  if (!rawSourceData || typeof rawSourceData !== 'object' || !rawSourceData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawSourceData.id,
    name: rawSourceData.name || 'Untitled Source',
    description: rawSourceData.description || '',
    url: rawSourceData.url || '',
    sourceType: rawSourceData.sourceType || rawSourceData.source_type || rawSourceData.type || 'rss',
    status: rawSourceData.status || 'active',
    
    // Configuration
    configuration: rawSourceData.configuration || rawSourceData.config || {},
    credentials: rawSourceData.credentials || rawSourceData.auth || {},
    schedule: rawSourceData.schedule || {
      frequency: 60, // minutes
      enabled: true,
      timezone: 'UTC'
    },
    
    // Health Metrics
    healthMetrics: rawSourceData.healthMetrics || rawSourceData.health_metrics || {
      successRate: 0,
      errorCount: 0,
      avgResponseTime: 0,
      lastSuccessfulFetch: null,
      lastError: null
    },
    
    // Content Statistics
    contentStats: rawSourceData.contentStats || rawSourceData.content_stats || {
      totalFetched: 0,
      approvedRate: 0,
      qualityAvg: 0,
      duplicateRate: 0
    },
    
    // Rate Limiting & Quotas
    rateLimits: rawSourceData.rateLimits || rawSourceData.rate_limits || {
      requestsPerMinute: 60,
      requestsPerHour: 1000,
      requestsPerDay: 10000
    },
    quotaSettings: rawSourceData.quotaSettings || rawSourceData.quota_settings || {
      dailyLimit: 1000,
      monthlyLimit: 30000,
      currentUsage: 0
    },
    priorityLevel: typeof rawSourceData.priorityLevel === 'number' ? rawSourceData.priorityLevel :
                   typeof rawSourceData.priority_level === 'number' ? rawSourceData.priority_level : 5,
    
    // Logging & History
    errorLog: Array.isArray(rawSourceData.errorLog) ? rawSourceData.errorLog :
              Array.isArray(rawSourceData.error_log) ? rawSourceData.error_log : [],
    performanceHistory: Array.isArray(rawSourceData.performanceHistory) ? rawSourceData.performanceHistory :
                        Array.isArray(rawSourceData.performance_history) ? rawSourceData.performance_history : [],
    
    // AI & Filtering
    aiProcessingRules: rawSourceData.aiProcessingRules || rawSourceData.ai_processing_rules || {},
    contentFilters: rawSourceData.contentFilters || rawSourceData.content_filters || {},
    
    // Management
    createdBy: rawSourceData.createdBy || rawSourceData.created_by || null,
    managedBy: rawSourceData.managedBy || rawSourceData.managed_by || null,
    lastModified: rawSourceData.lastModified || rawSourceData.last_modified || new Date().toISOString(),
    
    // Synchronization
    lastSync: rawSourceData.lastSync || rawSourceData.last_sync || null,
    nextSync: rawSourceData.nextSync || rawSourceData.next_sync || null,
    syncFrequency: typeof rawSourceData.syncFrequency === 'number' ? rawSourceData.syncFrequency :
                   typeof rawSourceData.sync_frequency === 'number' ? rawSourceData.sync_frequency : 60,
    contentCount: typeof rawSourceData.contentCount === 'number' ? rawSourceData.contentCount :
                  typeof rawSourceData.content_count === 'number' ? rawSourceData.content_count : 0,
    
    // Timestamps
    createdAt: rawSourceData.createdAt || rawSourceData.created_at || new Date().toISOString(),
    updatedAt: rawSourceData.updatedAt || rawSourceData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawSourceData.wpSynced || rawSourceData.wp_synced || false,
    lastWpSync: rawSourceData.lastWpSync || rawSourceData.last_wp_sync || null
  };
}

/**
 * Get comprehensive source data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} source - Raw source data
 * @returns {Object} Source helper with getters and methods
 */
export function getSourceData(source) {
  const normalizedSource = normalizeSourceData(source);
  
  if (!normalizedSource) {
    return createEmptySourceHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedSource.id; },
    get name() { return normalizedSource.name; },
    get description() { return normalizedSource.description; },
    get url() { return normalizedSource.url; },
    get sourceType() { return normalizedSource.sourceType; },
    get status() { return normalizedSource.status; },
    
    // Status Checks
    get isActive() { return this.status === 'active'; },
    get isPaused() { return this.status === 'paused'; },
    get hasError() { return this.status === 'error'; },
    get isDisabled() { return this.status === 'disabled'; },
    get isOperational() { return this.isActive || this.isPaused; },
    
    // Source Type Checks
    get isRSS() { return this.sourceType === 'rss'; },
    get isAPI() { return this.sourceType === 'api'; },
    get isScraper() { return this.sourceType === 'scraper'; },
    get isWebhook() { return this.sourceType === 'webhook'; },
    get isManual() { return this.sourceType === 'manual'; },
    get isAutomated() { return !this.isManual; },
    
    // Configuration
    get configuration() { return normalizedSource.configuration; },
    get credentials() { return normalizedSource.credentials; },
    get schedule() { return normalizedSource.schedule; },
    get hasCredentials() { return Object.keys(this.credentials).length > 0; },
    get isScheduled() { return this.schedule.enabled; },
    get scheduleFrequency() { return this.schedule.frequency; },
    
    // Health Metrics
    get healthMetrics() { return normalizedSource.healthMetrics; },
    get successRate() { return this.healthMetrics.successRate; },
    get errorCount() { return this.healthMetrics.errorCount; },
    get avgResponseTime() { return this.healthMetrics.avgResponseTime; },
    get lastSuccessfulFetch() { return this.healthMetrics.lastSuccessfulFetch; },
    get lastError() { return this.healthMetrics.lastError; },
    
    // Health Status
    get isHealthy() { return this.successRate >= 90 && this.errorCount < 10; },
    get isUnhealthy() { return this.successRate < 50 || this.errorCount > 50; },
    get healthStatus() {
      if (this.isHealthy) return 'healthy';
      if (this.isUnhealthy) return 'unhealthy';
      return 'warning';
    },
    get hasRecentErrors() { return this.errorCount > 0 && this.lastError; },
    
    // Content Statistics
    get contentStats() { return normalizedSource.contentStats; },
    get totalFetched() { return this.contentStats.totalFetched; },
    get approvedRate() { return this.contentStats.approvedRate; },
    get qualityAvg() { return this.contentStats.qualityAvg; },
    get duplicateRate() { return this.contentStats.duplicateRate; },
    get contentCount() { return normalizedSource.contentCount; },
    
    // Content Performance
    get isProductive() { return this.totalFetched > 100 && this.approvedRate > 70; },
    get isHighQuality() { return this.qualityAvg >= 70 && this.duplicateRate < 20; },
    get contentRating() {
      const score = (this.approvedRate + this.qualityAvg) / 2;
      if (score >= 80) return 'excellent';
      if (score >= 60) return 'good';
      if (score >= 40) return 'average';
      if (score >= 20) return 'poor';
      return 'very-poor';
    },
    
    // Rate Limiting & Quotas
    get rateLimits() { return normalizedSource.rateLimits; },
    get quotaSettings() { return normalizedSource.quotaSettings; },
    get priorityLevel() { return normalizedSource.priorityLevel; },
    get isHighPriority() { return this.priorityLevel >= 8; },
    get isLowPriority() { return this.priorityLevel <= 3; },
    get quotaUsage() { return this.quotaSettings.currentUsage; },
    get quotaRemaining() { return this.quotaSettings.dailyLimit - this.quotaUsage; },
    get isNearQuotaLimit() { return this.quotaUsage / this.quotaSettings.dailyLimit > 0.8; },
    
    // Logging & History
    get errorLog() { return normalizedSource.errorLog; },
    get performanceHistory() { return normalizedSource.performanceHistory; },
    get hasErrorHistory() { return this.errorLog.length > 0; },
    get recentErrors() { 
      const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
      return this.errorLog.filter(error => new Date(error.timestamp) > oneDayAgo);
    },
    get recentErrorCount() { return this.recentErrors.length; },
    
    // AI & Filtering
    get aiProcessingRules() { return normalizedSource.aiProcessingRules; },
    get contentFilters() { return normalizedSource.contentFilters; },
    get hasAIProcessing() { return Object.keys(this.aiProcessingRules).length > 0; },
    get hasContentFilters() { return Object.keys(this.contentFilters).length > 0; },
    
    // Management
    get createdBy() { return normalizedSource.createdBy; },
    get managedBy() { return normalizedSource.managedBy; },
    get lastModified() { return normalizedSource.lastModified; },
    get hasManager() { return !!this.managedBy; },
    
    // Synchronization
    get lastSync() { return normalizedSource.lastSync; },
    get nextSync() { return normalizedSource.nextSync; },
    get syncFrequency() { return normalizedSource.syncFrequency; },
    get hasBeenSynced() { return !!this.lastSync; },
    get isOverdue() {
      if (!this.nextSync) return false;
      return new Date(this.nextSync) < new Date();
    },
    get timeUntilNextSync() {
      if (!this.nextSync) return null;
      const next = new Date(this.nextSync);
      const now = new Date();
      return next.getTime() - now.getTime(); // milliseconds
    },
    
    // Timestamps
    get createdAt() { return normalizedSource.createdAt; },
    get updatedAt() { return normalizedSource.updatedAt; },
    
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
    get daysSinceLastSync() {
      if (!this.lastSync) return null;
      const synced = new Date(this.lastSync);
      const now = new Date();
      return Math.floor((now.getTime() - synced.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get isRecent() { return this.age <= 7; },
    get isStale() { return this.daysSinceUpdate > 30; },
    get isSyncStale() { return this.daysSinceLastSync !== null && this.daysSinceLastSync > 7; },
    
    // WordPress Integration
    get wpSynced() { return normalizedSource.wpSynced; },
    get lastWpSync() { return normalizedSource.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.name && this.url && this.sourceType);
    },
    get isComplete() {
      return this.isValid && this.createdBy;
    },
    get isConfigured() {
      return this.isComplete && (this.sourceType === 'manual' || Object.keys(this.configuration).length > 0);
    },
    get isReadyToSync() {
      return this.isConfigured && this.isActive && !this.isNearQuotaLimit;
    },
    
    // Utility Methods
    canUserManage(userId) {
      return this.createdBy === userId || this.managedBy === userId;
    },
    getErrorsByType(errorType) {
      return this.errorLog.filter(error => error.type === errorType);
    },
    getRecentPerformance(days = 7) {
      const cutoff = new Date(Date.now() - days * 24 * 60 * 60 * 1000);
      return this.performanceHistory.filter(entry => new Date(entry.timestamp) > cutoff);
    },
    
    // Performance Analysis
    getAverageResponseTime(days = 7) {
      const recent = this.getRecentPerformance(days);
      if (recent.length === 0) return 0;
      const total = recent.reduce((sum, entry) => sum + (entry.responseTime || 0), 0);
      return total / recent.length;
    },
    getSuccessRateForPeriod(days = 7) {
      const recent = this.getRecentPerformance(days);
      if (recent.length === 0) return 0;
      const successful = recent.filter(entry => entry.success).length;
      return (successful / recent.length) * 100;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        name: this.name,
        sourceType: this.sourceType,
        status: this.status,
        healthStatus: this.healthStatus,
        contentCount: this.contentCount,
        successRate: this.successRate,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isConfigured: this.isConfigured,
        isReadyToSync: this.isReadyToSync,
        age: this.age,
        contentRating: this.contentRating
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        name: this.name,
        description: this.description,
        url: this.url,
        sourceType: this.sourceType,
        status: this.status,
        
        // Configuration
        configuration: this.configuration,
        credentials: this.credentials,
        schedule: this.schedule,
        
        // Health & Performance
        healthMetrics: this.healthMetrics,
        contentStats: this.contentStats,
        
        // Rate Limiting
        rateLimits: this.rateLimits,
        quotaSettings: this.quotaSettings,
        priorityLevel: this.priorityLevel,
        
        // Logging
        errorLog: this.errorLog,
        performanceHistory: this.performanceHistory,
        
        // AI & Filtering
        aiProcessingRules: this.aiProcessingRules,
        contentFilters: this.contentFilters,
        
        // Management
        createdBy: this.createdBy,
        managedBy: this.managedBy,
        lastModified: this.lastModified,
        
        // Synchronization
        lastSync: this.lastSync,
        nextSync: this.nextSync,
        syncFrequency: this.syncFrequency,
        contentCount: this.contentCount,
        
        // Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        
        // WordPress
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync
      };
    }
  };
}

/**
 * Create empty source helper for null/undefined sources
 * @returns {Object} Empty source helper with safe defaults
 */
function createEmptySourceHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get name() { return 'New Source'; },
    get description() { return ''; },
    get url() { return ''; },
    get sourceType() { return 'rss'; },
    get status() { return 'active'; },
    
    // Status Checks
    get isActive() { return true; },
    get isPaused() { return false; },
    get hasError() { return false; },
    get isDisabled() { return false; },
    get isOperational() { return true; },
    
    // Source Type Checks
    get isRSS() { return true; },
    get isAPI() { return false; },
    get isScraper() { return false; },
    get isWebhook() { return false; },
    get isManual() { return false; },
    get isAutomated() { return true; },
    
    // Configuration
    get configuration() { return {}; },
    get credentials() { return {}; },
    get schedule() { return { frequency: 60, enabled: true, timezone: 'UTC' }; },
    get hasCredentials() { return false; },
    get isScheduled() { return true; },
    get scheduleFrequency() { return 60; },
    
    // Health Metrics
    get healthMetrics() { return { successRate: 0, errorCount: 0, avgResponseTime: 0, lastSuccessfulFetch: null, lastError: null }; },
    get successRate() { return 0; },
    get errorCount() { return 0; },
    get avgResponseTime() { return 0; },
    get lastSuccessfulFetch() { return null; },
    get lastError() { return null; },
    get isHealthy() { return false; },
    get isUnhealthy() { return false; },
    get healthStatus() { return 'unknown'; },
    get hasRecentErrors() { return false; },
    
    // Content Statistics
    get contentStats() { return { totalFetched: 0, approvedRate: 0, qualityAvg: 0, duplicateRate: 0 }; },
    get totalFetched() { return 0; },
    get approvedRate() { return 0; },
    get qualityAvg() { return 0; },
    get duplicateRate() { return 0; },
    get contentCount() { return 0; },
    get isProductive() { return false; },
    get isHighQuality() { return false; },
    get contentRating() { return 'unrated'; },
    
    // Rate Limiting & Quotas
    get rateLimits() { return { requestsPerMinute: 60, requestsPerHour: 1000, requestsPerDay: 10000 }; },
    get quotaSettings() { return { dailyLimit: 1000, monthlyLimit: 30000, currentUsage: 0 }; },
    get priorityLevel() { return 5; },
    get isHighPriority() { return false; },
    get isLowPriority() { return false; },
    get quotaUsage() { return 0; },
    get quotaRemaining() { return 1000; },
    get isNearQuotaLimit() { return false; },
    
    // Logging & History
    get errorLog() { return []; },
    get performanceHistory() { return []; },
    get hasErrorHistory() { return false; },
    get recentErrors() { return []; },
    get recentErrorCount() { return 0; },
    
    // AI & Filtering
    get aiProcessingRules() { return {}; },
    get contentFilters() { return {}; },
    get hasAIProcessing() { return false; },
    get hasContentFilters() { return false; },
    
    // Management
    get createdBy() { return null; },
    get managedBy() { return null; },
    get lastModified() { return null; },
    get hasManager() { return false; },
    
    // Synchronization
    get lastSync() { return null; },
    get nextSync() { return null; },
    get syncFrequency() { return 60; },
    get hasBeenSynced() { return false; },
    get isOverdue() { return false; },
    get timeUntilNextSync() { return null; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get daysSinceUpdate() { return 0; },
    get daysSinceLastSync() { return null; },
    get isRecent() { return false; },
    get isStale() { return false; },
    get isSyncStale() { return false; },
    
    // WordPress Integration
    get wpSynced() { return false; },
    get lastWpSync() { return null; },
    get isSyncedToWordPress() { return false; },
    get needsWordPressSync() { return false; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isConfigured() { return false; },
    get isReadyToSync() { return false; },
    
    // Utility Methods
    canUserManage(userId) { return false; },
    getErrorsByType(errorType) { return []; },
    getRecentPerformance(days = 7) { return []; },
    getAverageResponseTime(days = 7) { return 0; },
    getSuccessRateForPeriod(days = 7) { return 0; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        name: 'New Source',
        sourceType: 'rss',
        status: 'active',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        name: 'New Source',
        sourceType: 'rss',
        status: 'active',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Sources
 */

/**
 * Create new source
 * @param {Object} sourceData - Initial source data
 * @returns {Promise<Object>} Created source
 */
export async function createSource(sourceData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create source');
    }

    const newSource = {
      id: crypto.randomUUID(),
      ...sourceData,
      createdBy: currentUser.id,
      status: 'active',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.sources.create(newSource);
    }

    // Update local store
    sourcesStore.update(sources => [...sources, newSource]);

    log(`[Source] Created new source: ${newSource.id}`, 'info');
    return getSourceData(newSource);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Source] Error creating source: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing source
 * @param {string} sourceId - Source ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated source
 */
export async function updateSource(sourceId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.sources.update(sourceId, updatedData);
    }

    // Update local store
    sourcesStore.update(sources => 
      sources.map(source => 
        source.id === sourceId 
          ? { ...source, ...updatedData }
          : source
      )
    );

    log(`[Source] Updated source: ${sourceId}`, 'info');
    
    const updatedSource = await getSourceById(sourceId);
    return updatedSource;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Source] Error updating source: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete source
 * @param {string} sourceId - Source ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteSource(sourceId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.sources.delete(sourceId);
    }

    // Update local store
    sourcesStore.update(sources => 
      sources.filter(source => source.id !== sourceId)
    );

    log(`[Source] Deleted source: ${sourceId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Source] Error deleting source: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get source by ID
 * @param {string} sourceId - Source ID
 * @returns {Promise<Object|null>} Source data or null
 */
export async function getSourceById(sourceId) {
  try {
    let source = null;

    // Try LiveStore first
    if (browser && liveStore) {
      source = await liveStore.sources.findById(sourceId);
    }

    // Fallback to local store
    if (!source) {
      const allSources = await new Promise(resolve => {
        sourcesStore.subscribe(value => resolve(value))();
      });
      source = allSources.find(s => s.id === sourceId);
    }

    return source ? getSourceData(source) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Source] Error getting source by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get sources by status
 * @param {SourceStatus} status - Source status
 * @returns {Promise<Object[]>} Array of source data objects
 */
export async function getSourcesByStatus(status) {
  try {
    let sources = [];

    // Try LiveStore first
    if (browser && liveStore) {
      sources = await liveStore.sources.findMany({
        where: { status }
      });
    }

    // Fallback to local store
    if (sources.length === 0) {
      const allSources = await new Promise(resolve => {
        sourcesStore.subscribe(value => resolve(value))();
      });
      sources = allSources.filter(source => source.status === status);
    }

    return sources.map(source => getSourceData(source));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Source] Error getting sources by status: ${errorMessage}`, 'error');
    return [];
  }
}

/**
 * Get user's sources
 * @returns {Promise<Object[]>} Array of user's source data objects
 */
export async function getUserSources() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return [];

    let sources = [];

    // Try LiveStore first
    if (browser && liveStore) {
      sources = await liveStore.sources.findMany({
        where: {
          OR: [
            { createdBy: currentUser.id },
            { managedBy: currentUser.id }
          ]
        }
      });
    }

    // Fallback to local store
    if (sources.length === 0) {
      const allSources = await new Promise(resolve => {
        sourcesStore.subscribe(value => resolve(value))();
      });
      sources = allSources.filter(source => 
        source.createdBy === currentUser.id || 
        source.managedBy === currentUser.id
      );
    }

    return sources.map(source => getSourceData(source));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Source] Error getting user sources: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: sourcesStore,
  getSourceData,
  createSource,
  updateSource,
  deleteSource,
  getSourceById,
  getSourcesByStatus,
  getUserSources
}; 