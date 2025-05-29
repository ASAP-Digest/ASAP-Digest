/**
 * Usage Tracking Store
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

/**
 * Usage event types
 * @typedef {'api_call' | 'feature_use' | 'resource_access' | 'data_operation' | 'ai_processing' | 'content_action' | 'system_event'} UsageEventType
 */

/**
 * Resource types
 * @typedef {'cpu' | 'memory' | 'storage' | 'bandwidth' | 'api_tokens' | 'ai_tokens' | 'database_queries' | 'file_operations'} ResourceType
 */

/**
 * Billing categories
 * @typedef {'base_plan' | 'ai_usage' | 'storage' | 'bandwidth' | 'api_calls' | 'premium_features' | 'overage'} BillingCategory
 */

/**
 * Enhanced Usage Tracking object with comprehensive fields
 * @typedef {Object} UsageRecord
 * @property {string} id - Usage record identifier
 * @property {string} userId - User identifier
 * @property {string} sessionId - Session identifier
 * @property {UsageEventType} eventType - Type of usage event
 * @property {string} featureUsed - Specific feature or endpoint used
 * @property {number} usageCount - Number of times used
 * @property {Date} timestamp - Event timestamp
 * @property {number} duration - Duration in milliseconds
 * @property {Object} sessionData - Session context data
 * @property {Object} resourceConsumption - Resource usage details
 * @property {number} costAttribution - Cost associated with usage
 * @property {BillingCategory} billingCategory - Billing category
 * @property {number} performanceImpact - Performance impact score
 * @property {Object} errorOccurrences - Error tracking data
 * @property {Object} aggregatedStats - Aggregated statistics
 * @property {Object} trendData - Trend analysis data
 * @property {Object} quotaStatus - Quota and limit status
 * @property {Object} limitWarnings - Limit warning data
 * @property {Object} metadata - Additional metadata
 * @property {string} userAgent - User agent string
 * @property {string} ipAddress - IP address (hashed)
 * @property {string} location - Geographic location
 * @property {string} deviceType - Device type
 * @property {Object} contextData - Additional context
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<UsageRecord[]>} */
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
    usageCount: typeof rawUsageData.usageCount === 'number' ? rawUsageData.usageCount :
                typeof rawUsageData.usage_count === 'number' ? rawUsageData.usage_count : 1,
    timestamp: rawUsageData.timestamp || new Date().toISOString(),
    duration: typeof rawUsageData.duration === 'number' ? rawUsageData.duration : 0,
    
    // Session & Context
    sessionData: rawUsageData.sessionData || rawUsageData.session_data || {
      startTime: null,
      endTime: null,
      totalDuration: 0,
      pageViews: 0,
      interactions: 0
    },
    
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
    costAttribution: typeof rawUsageData.costAttribution === 'number' ? rawUsageData.costAttribution :
                     typeof rawUsageData.cost_attribution === 'number' ? rawUsageData.cost_attribution : 0,
    billingCategory: rawUsageData.billingCategory || rawUsageData.billing_category || 'base_plan',
    
    // Performance & Errors
    performanceImpact: typeof rawUsageData.performanceImpact === 'number' ? rawUsageData.performanceImpact :
                       typeof rawUsageData.performance_impact === 'number' ? rawUsageData.performance_impact : 0,
    errorOccurrences: rawUsageData.errorOccurrences || rawUsageData.error_occurrences || {
      count: 0,
      types: [],
      lastError: null,
      severity: 'none'
    },
    
    // Analytics
    aggregatedStats: rawUsageData.aggregatedStats || rawUsageData.aggregated_stats || {
      hourly: {},
      daily: {},
      weekly: {},
      monthly: {}
    },
    trendData: rawUsageData.trendData || rawUsageData.trend_data || {
      direction: 'stable',
      changePercent: 0,
      periodComparison: 'same'
    },
    
    // Quotas & Limits
    quotaStatus: rawUsageData.quotaStatus || rawUsageData.quota_status || {
      current: 0,
      limit: 0,
      percentage: 0,
      resetDate: null
    },
    limitWarnings: rawUsageData.limitWarnings || rawUsageData.limit_warnings || {
      hasWarnings: false,
      warningLevel: 'none',
      thresholdReached: [],
      estimatedOverage: 0
    },
    
    // Additional Data
    metadata: rawUsageData.metadata || {},
    userAgent: rawUsageData.userAgent || rawUsageData.user_agent || '',
    ipAddress: rawUsageData.ipAddress || rawUsageData.ip_address || '',
    location: rawUsageData.location || '',
    deviceType: rawUsageData.deviceType || rawUsageData.device_type || 'unknown',
    contextData: rawUsageData.contextData || rawUsageData.context_data || {},
    
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
    
    // Cost & Billing
    get costAttribution() { return normalizedUsage.costAttribution; },
    get billingCategory() { return normalizedUsage.billingCategory; },
    get hasCost() { return this.costAttribution > 0; },
    get isExpensive() { return this.costAttribution >= 1.0; },
    get isCostly() { return this.costAttribution >= 0.1; },
    get isMinimalCost() { return this.costAttribution < 0.01; },
    
    // Performance & Errors
    get performanceImpact() { return normalizedUsage.performanceImpact; },
    get errorOccurrences() { return normalizedUsage.errorOccurrences; },
    get errorCount() { return this.errorOccurrences.count; },
    get hasErrors() { return this.errorCount > 0; },
    
    // Quotas & Limits
    get quotaStatus() { return normalizedUsage.quotaStatus; },
    get currentQuota() { return this.quotaStatus.current; },
    get quotaLimit() { return this.quotaStatus.limit; },
    get quotaPercentage() { return this.quotaStatus.percentage; },
    get isNearQuotaLimit() { return this.quotaPercentage >= 80; },
    get isOverQuotaLimit() { return this.quotaPercentage >= 100; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60));
    },
    get isRecent() { return this.age <= 60; },
    get isToday() { return this.ageDays === 0; },
    
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
    getMetadata(key, defaultValue = null) {
      return this.metadata[key] !== undefined ? this.metadata[key] : defaultValue;
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
        createdAt: this.createdAt,
        updatedAt: this.updatedAt
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
    get isResourceAccess() { return false; },
    get isDataOperation() { return false; },
    get isAiProcessing() { return false; },
    get isContentAction() { return false; },
    get isSystemEvent() { return false; },
    get isHighUsage() { return false; },
    get isMediumUsage() { return false; },
    get isLowUsage() { return true; },
    get isSingleUse() { return false; },
    get isRepeatedUse() { return false; },
    get isHeavyUse() { return false; },
    get resourceConsumption() { return { cpu: 0, memory: 0, storage: 0, bandwidth: 0, apiCalls: 0, aiTokens: 0, databaseQueries: 0, fileOperations: 0 }; },
    get cpuUsage() { return 0; },
    get memoryUsage() { return 0; },
    get storageUsage() { return 0; },
    get bandwidthUsage() { return 0; },
    get apiCallsUsage() { return 0; },
    get aiTokensUsage() { return 0; },
    get databaseQueriesUsage() { return 0; },
    get fileOperationsUsage() { return 0; },
    get costAttribution() { return 0; },
    get billingCategory() { return 'base_plan'; },
    get hasCost() { return false; },
    get isExpensive() { return false; },
    get isCostly() { return false; },
    get isMinimalCost() { return true; },
    get performanceImpact() { return 0; },
    get errorOccurrences() { return { count: 0, types: [], lastError: null, severity: 'none' }; },
    get errorCount() { return 0; },
    get hasErrors() { return false; },
    get quotaStatus() { return { current: 0, limit: 0, percentage: 0, resetDate: null }; },
    get currentQuota() { return 0; },
    get quotaLimit() { return 0; },
    get quotaPercentage() { return 0; },
    get isNearQuotaLimit() { return false; },
    get isOverQuotaLimit() { return false; },
    get age() { return 0; },
    get isRecent() { return false; },
    get isToday() { return false; },
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