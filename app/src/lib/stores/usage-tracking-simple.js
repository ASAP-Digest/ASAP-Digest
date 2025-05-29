/**
 * Usage Tracking Store (Simplified)
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Usage Tracking business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/** @type {import('svelte/store').Writable<Object[]>} */
export const usageTrackingStore = writable([]);

/**
 * Normalize usage tracking data from any source to consistent format
 * @param {Object} rawUsageData - Raw usage tracking data
 * @returns {Object|null} Normalized usage tracking data
 */
function normalizeUsageTrackingData(rawUsageData) {
  if (!rawUsageData || typeof rawUsageData !== 'object' || !rawUsageData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawUsageData.id,
    userId: rawUsageData.userId || rawUsageData.user_id || null,
    sessionId: rawUsageData.sessionId || rawUsageData.session_id || null,
    
    // Event Information
    eventType: rawUsageData.eventType || rawUsageData.event_type || 'feature_use',
    featureUsed: rawUsageData.featureUsed || rawUsageData.feature_used || '',
    usageCount: typeof rawUsageData.usageCount === 'number' ? rawUsageData.usageCount : 1,
    timestamp: rawUsageData.timestamp || new Date().toISOString(),
    duration: typeof rawUsageData.duration === 'number' ? rawUsageData.duration : 0,
    
    // Resource Consumption
    resourceConsumption: rawUsageData.resourceConsumption || rawUsageData.resource_consumption || {
      cpu: 0,
      memory: 0,
      storage: 0,
      bandwidth: 0,
      apiCalls: 0,
      aiTokens: 0,
      databaseQueries: 0,
      fileOperations: 0
    },
    
    // Cost & Billing
    costAttribution: typeof rawUsageData.costAttribution === 'number' ? rawUsageData.costAttribution : 0,
    billingCategory: rawUsageData.billingCategory || rawUsageData.billing_category || 'base_plan',
    
    // Performance & Errors
    performanceImpact: typeof rawUsageData.performanceImpact === 'number' ? rawUsageData.performanceImpact : 0,
    errorOccurrences: rawUsageData.errorOccurrences || rawUsageData.error_occurrences || {
      count: 0,
      types: [],
      lastError: null,
      severity: 'none'
    },
    
    // Quotas & Limits
    quotaStatus: rawUsageData.quotaStatus || rawUsageData.quota_status || {
      current: 0,
      limit: 0,
      percentage: 0,
      resetDate: null
    },
    
    // Additional Data
    metadata: rawUsageData.metadata || {},
    deviceType: rawUsageData.deviceType || rawUsageData.device_type || 'unknown',
    
    // Timestamps
    createdAt: rawUsageData.createdAt || rawUsageData.created_at || new Date().toISOString(),
    updatedAt: rawUsageData.updatedAt || rawUsageData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawUsageData.wpSynced || rawUsageData.wp_synced || false,
    lastWpSync: rawUsageData.lastWpSync || rawUsageData.last_wp_sync || null
  };
}

/**
 * Get comprehensive usage tracking data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} usage - Raw usage tracking data
 * @returns {Object} Usage tracking helper with getters and methods
 */
export function getUsageData(usage) {
  const normalizedUsage = normalizeUsageTrackingData(usage);
  
  if (!normalizedUsage) {
    return createEmptyUsageHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedUsage.id; },
    get userId() { return normalizedUsage.userId; },
    get sessionId() { return normalizedUsage.sessionId; },
    get eventType() { return normalizedUsage.eventType; },
    get featureUsed() { return normalizedUsage.featureUsed; },
    get usageCount() { return normalizedUsage.usageCount; },
    get timestamp() { return normalizedUsage.timestamp; },
    get duration() { return normalizedUsage.duration; },
    
    // Event Type Analysis
    get isApiCall() { return this.eventType === 'api_call'; },
    get isFeatureUse() { return this.eventType === 'feature_use'; },
    get isResourceAccess() { return this.eventType === 'resource_access'; },
    get isDataOperation() { return this.eventType === 'data_operation'; },
    get isAiProcessing() { return this.eventType === 'ai_processing'; },
    get isContentAction() { return this.eventType === 'content_action'; },
    get isSystemEvent() { return this.eventType === 'system_event'; },
    
    // Usage Analysis
    get isHighUsage() { return this.usageCount >= 100; },
    get isMediumUsage() { return this.usageCount >= 10 && this.usageCount < 100; },
    get isLowUsage() { return this.usageCount < 10; },
    get isSingleUse() { return this.usageCount === 1; },
    get isRepeatedUse() { return this.usageCount > 1; },
    get isHeavyUse() { return this.usageCount >= 1000; },
    
    // Duration Analysis
    get durationSeconds() { return Math.floor(this.duration / 1000); },
    get durationMinutes() { return Math.floor(this.durationSeconds / 60); },
    get isQuickAction() { return this.duration <= 1000; },
    get isLongAction() { return this.duration >= 60000; },
    
    // Resource Consumption
    get resourceConsumption() { return normalizedUsage.resourceConsumption; },
    get cpuUsage() { return this.resourceConsumption.cpu; },
    get memoryUsage() { return this.resourceConsumption.memory; },
    get storageUsage() { return this.resourceConsumption.storage; },
    get bandwidthUsage() { return this.resourceConsumption.bandwidth; },
    get apiCallsUsage() { return this.resourceConsumption.apiCalls; },
    get aiTokensUsage() { return this.resourceConsumption.aiTokens; },
    get databaseQueriesUsage() { return this.resourceConsumption.databaseQueries; },
    get fileOperationsUsage() { return this.resourceConsumption.fileOperations; },
    
    // Resource Analysis
    get hasResourceUsage() {
      return this.cpuUsage > 0 || this.memoryUsage > 0 || this.storageUsage > 0 || 
             this.bandwidthUsage > 0 || this.apiCallsUsage > 0 || this.aiTokensUsage > 0;
    },
    get isResourceIntensive() {
      return this.cpuUsage > 80 || this.memoryUsage > 80 || this.aiTokensUsage > 1000;
    },
    get resourceIntensityScore() {
      let score = 0;
      if (this.cpuUsage > 50) score += 20;
      if (this.memoryUsage > 50) score += 20;
      if (this.aiTokensUsage > 500) score += 30;
      if (this.apiCallsUsage > 100) score += 15;
      if (this.databaseQueriesUsage > 50) score += 15;
      return Math.min(100, score);
    },
    
    // Cost & Billing
    get costAttribution() { return normalizedUsage.costAttribution; },
    get billingCategory() { return normalizedUsage.billingCategory; },
    get hasCost() { return this.costAttribution > 0; },
    get isExpensive() { return this.costAttribution >= 1.0; },
    get isCostly() { return this.costAttribution >= 0.1; },
    get isMinimalCost() { return this.costAttribution < 0.01; },
    
    // Billing Category Analysis
    get isBasePlan() { return this.billingCategory === 'base_plan'; },
    get isAiUsage() { return this.billingCategory === 'ai_usage'; },
    get isStorageBilling() { return this.billingCategory === 'storage'; },
    get isPremiumFeature() { return this.billingCategory === 'premium_features'; },
    get isOverage() { return this.billingCategory === 'overage'; },
    
    // Performance & Errors
    get performanceImpact() { return normalizedUsage.performanceImpact; },
    get errorOccurrences() { return normalizedUsage.errorOccurrences; },
    get errorCount() { return this.errorOccurrences.count; },
    get errorTypes() { return this.errorOccurrences.types; },
    get lastError() { return this.errorOccurrences.lastError; },
    get errorSeverity() { return this.errorOccurrences.severity; },
    
    // Performance Analysis
    get hasPerformanceImpact() { return this.performanceImpact > 0; },
    get isHighPerformanceImpact() { return this.performanceImpact >= 80; },
    get performanceRating() {
      if (this.performanceImpact >= 90) return 'critical';
      if (this.performanceImpact >= 70) return 'poor';
      if (this.performanceImpact >= 40) return 'average';
      if (this.performanceImpact >= 20) return 'good';
      return 'excellent';
    },
    
    // Error Analysis
    get hasErrors() { return this.errorCount > 0; },
    get hasMultipleErrors() { return this.errorCount > 1; },
    get isCriticalError() { return this.errorSeverity === 'critical'; },
    get errorRate() {
      return this.usageCount > 0 ? (this.errorCount / this.usageCount) * 100 : 0;
    },
    get isHighErrorRate() { return this.errorRate >= 10; },
    
    // Quotas & Limits
    get quotaStatus() { return normalizedUsage.quotaStatus; },
    get currentQuota() { return this.quotaStatus.current; },
    get quotaLimit() { return this.quotaStatus.limit; },
    get quotaPercentage() { return this.quotaStatus.percentage; },
    get quotaResetDate() { return this.quotaStatus.resetDate; },
    get hasQuotaLimit() { return this.quotaLimit > 0; },
    get isNearQuotaLimit() { return this.quotaPercentage >= 80; },
    get isOverQuotaLimit() { return this.quotaPercentage >= 100; },
    get quotaRemaining() { return Math.max(0, this.quotaLimit - this.currentQuota); },
    
    // Additional Data
    get metadata() { return normalizedUsage.metadata; },
    get deviceType() { return normalizedUsage.deviceType; },
    get hasMetadata() { return Object.keys(this.metadata).length > 0; },
    get isMobileDevice() { return this.deviceType === 'mobile'; },
    get isDesktopDevice() { return this.deviceType === 'desktop'; },
    get isTabletDevice() { return this.deviceType === 'tablet'; },
    
    // Timestamps
    get createdAt() { return normalizedUsage.createdAt; },
    get updatedAt() { return normalizedUsage.updatedAt; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60)); // minutes
    },
    get ageHours() { return Math.floor(this.age / 60); },
    get ageDays() { return Math.floor(this.ageHours / 24); },
    get isRecent() { return this.age <= 60; },
    get isToday() { return this.ageDays === 0; },
    get isThisWeek() { return this.ageDays <= 7; },
    get isOld() { return this.ageDays >= 30; },
    
    // WordPress Integration
    get wpSynced() { return normalizedUsage.wpSynced; },
    get lastWpSync() { return normalizedUsage.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Usage Scoring
    get usageScore() {
      let score = 0;
      
      // Base usage count (0-30 points)
      if (this.usageCount >= 1000) score += 30;
      else if (this.usageCount >= 100) score += 25;
      else if (this.usageCount >= 10) score += 15;
      else if (this.usageCount >= 1) score += 5;
      
      // Resource intensity (0-25 points)
      score += (this.resourceIntensityScore / 100) * 25;
      
      // Cost impact (0-20 points)
      if (this.costAttribution >= 10) score += 20;
      else if (this.costAttribution >= 1) score += 15;
      else if (this.costAttribution >= 0.1) score += 10;
      else if (this.costAttribution > 0) score += 5;
      
      // Performance impact (0-15 points)
      score += (this.performanceImpact / 100) * 15;
      
      // Error penalty (0-10 points deduction)
      if (this.hasErrors) {
        score -= Math.min(10, this.errorRate);
      }
      
      return Math.max(0, Math.min(100, score));
    },
    get usageRating() {
      const score = this.usageScore;
      if (score >= 90) return 'critical';
      if (score >= 75) return 'high';
      if (score >= 50) return 'medium';
      if (score >= 25) return 'low';
      return 'minimal';
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.userId && this.featureUsed);
    },
    get isComplete() {
      return this.isValid && this.eventType && this.timestamp;
    },
    get isBillable() {
      return this.isComplete && this.costAttribution > 0;
    },
    
    // Utility Methods
    getResourceUsage(resourceType) {
      return this.resourceConsumption[resourceType] || 0;
    },
    hasResourceType(resourceType) {
      return this.getResourceUsage(resourceType) > 0;
    },
    getMetadata(key, defaultValue = null) {
      return this.metadata[key] !== undefined ? this.metadata[key] : defaultValue;
    },
    hasMetadataKey(key) {
      return this.metadata[key] !== undefined;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        eventType: this.eventType,
        featureUsed: this.featureUsed,
        usageCount: this.usageCount,
        usageScore: this.usageScore,
        usageRating: this.usageRating,
        costAttribution: this.costAttribution,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isBillable: this.isBillable
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        userId: this.userId,
        sessionId: this.sessionId,
        eventType: this.eventType,
        featureUsed: this.featureUsed,
        usageCount: this.usageCount,
        timestamp: this.timestamp,
        duration: this.duration,
        resourceConsumption: this.resourceConsumption,
        costAttribution: this.costAttribution,
        billingCategory: this.billingCategory,
        performanceImpact: this.performanceImpact,
        errorOccurrences: this.errorOccurrences,
        quotaStatus: this.quotaStatus,
        metadata: this.metadata,
        deviceType: this.deviceType,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync
      };
    }
  };
}

/**
 * Create empty usage helper for null/undefined usage data
 * @returns {Object} Empty usage helper with safe defaults
 */
function createEmptyUsageHelper() {
  return {
    get id() { return null; },
    get userId() { return null; },
    get sessionId() { return null; },
    get eventType() { return 'feature_use'; },
    get featureUsed() { return ''; },
    get usageCount() { return 0; },
    get timestamp() { return null; },
    get duration() { return 0; },
    get isApiCall() { return false; },
    get isFeatureUse() { return true; },
    get isHighUsage() { return false; },
    get isLowUsage() { return true; },
    get resourceConsumption() { return { cpu: 0, memory: 0, storage: 0, bandwidth: 0, apiCalls: 0, aiTokens: 0, databaseQueries: 0, fileOperations: 0 }; },
    get costAttribution() { return 0; },
    get hasCost() { return false; },
    get performanceImpact() { return 0; },
    get errorCount() { return 0; },
    get hasErrors() { return false; },
    get quotaPercentage() { return 0; },
    get isValid() { return false; },
    get isComplete() { return false; },
    get isBillable() { return false; },
    getResourceUsage(resourceType) { return 0; },
    getMetadata(key, defaultValue = null) { return defaultValue; },
    toJSON() {
      return {
        id: null,
        userId: null,
        eventType: 'feature_use',
        featureUsed: '',
        usageCount: 0,
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Usage Tracking
 */

/**
 * Track usage event
 * @param {Object} usageData - Usage event data
 * @returns {Promise<Object>} Created usage record
 */
export async function trackUsage(usageData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to track usage');
    }

    const newUsage = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...usageData,
      timestamp: new Date().toISOString(),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.usage.create(newUsage);
    }

    // Update local store
    usageTrackingStore.update(records => [...records, newUsage]);

    log(`[Usage] Tracked usage: ${newUsage.featureUsed}`, 'info');
    return getUsageData(newUsage);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Usage] Error tracking usage: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get usage records for user
 * @param {string} userId - User ID
 * @param {Object} options - Query options
 * @returns {Promise<Object[]>} Array of usage data
 */
export async function getUserUsage(userId, options = {}) {
  try {
    const {
      eventType = null,
      featureUsed = null,
      limit = 100,
      offset = 0,
      orderBy = 'timestamp',
      order = 'desc'
    } = options;

    let usageRecords = [];

    // Try LiveStore first
    if (browser && liveStore) {
      const where = { userId };
      if (eventType) where.eventType = eventType;
      if (featureUsed) where.featureUsed = featureUsed;

      usageRecords = await liveStore.usage.findMany({
        where,
        orderBy: { [orderBy]: order },
        take: limit,
        skip: offset
      });
    }

    // Fallback to local store
    if (usageRecords.length === 0) {
      const allRecords = await new Promise(resolve => {
        usageTrackingStore.subscribe(value => resolve(value))();
      });
      
      usageRecords = allRecords
        .filter(record => {
          if (record.userId !== userId) return false;
          if (eventType && record.eventType !== eventType) return false;
          if (featureUsed && record.featureUsed !== featureUsed) return false;
          return true;
        })
        .sort((a, b) => {
          const aVal = new Date(a[orderBy]).getTime();
          const bVal = new Date(b[orderBy]).getTime();
          return order === 'desc' ? bVal - aVal : aVal - bVal;
        })
        .slice(offset, offset + limit);
    }

    return usageRecords.map(record => getUsageData(record));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Usage] Error getting user usage: ${errorMessage}`, 'error');
    return [];
  }
}

/**
 * Get current user's usage
 * @param {Object} options - Query options
 * @returns {Promise<Object[]>} Array of usage data
 */
export async function getCurrentUserUsage(options = {}) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      return [];
    }

    return await getUserUsage(currentUser.id, options);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Usage] Error getting current user usage: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: usageTrackingStore,
  getUsageData,
  trackUsage,
  getUserUsage,
  getCurrentUserUsage
}; 