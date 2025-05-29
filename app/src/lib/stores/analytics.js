/**
 * Analytics Dashboard Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Analytics Dashboard business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Analytics time range types
 * @typedef {'hour' | 'day' | 'week' | 'month' | 'quarter' | 'year' | 'custom'} TimeRange
 */

/**
 * Analytics metric types
 * @typedef {'content' | 'digest' | 'ai' | 'user' | 'system' | 'engagement' | 'performance'} MetricType
 */

/**
 * Enhanced Analytics Dashboard object with comprehensive fields
 * @typedef {Object} AnalyticsDashboard
 * @property {string} id - Dashboard identifier
 * @property {string} userId - Owner user ID
 * @property {string} name - Dashboard name
 * @property {string} description - Dashboard description
 * @property {TimeRange} timeRange - Current time range
 * @property {Date} startDate - Range start date
 * @property {Date} endDate - Range end date
 * @property {Object} contentMetrics - Content ingestion and quality metrics
 * @property {Object} digestMetrics - Digest creation and engagement metrics
 * @property {Object} aiMetrics - AI usage, costs, and performance metrics
 * @property {Object} userMetrics - User activity and engagement metrics
 * @property {Object} systemMetrics - System performance and health metrics
 * @property {Object} filters - Applied filters
 * @property {Object} comparisons - Comparison periods
 * @property {string[]} widgets - Active dashboard widgets
 * @property {Object} layout - Dashboard layout configuration
 * @property {boolean} isPublic - Whether dashboard is publicly accessible
 * @property {string[]} sharedWith - User IDs with access
 * @property {Object} exportSettings - Export configuration
 * @property {Object} scheduledReports - Scheduled report settings
 * @property {Date} lastRefresh - Last data refresh timestamp
 * @property {boolean} autoRefresh - Auto-refresh enabled
 * @property {number} refreshInterval - Refresh interval in minutes
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<AnalyticsDashboard[]>} */
export const analyticsDashboardStore = writable([]);

/**
 * Normalize analytics dashboard data from any source to consistent format
 * @param {Object} rawDashboardData - Raw analytics dashboard data
 * @returns {Object|null} Normalized analytics dashboard data
 */
function normalizeAnalyticsDashboardData(rawDashboardData) {
  if (!rawDashboardData || typeof rawDashboardData !== 'object' || !rawDashboardData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawDashboardData.id,
    userId: rawDashboardData.userId || rawDashboardData.user_id || null,
    name: rawDashboardData.name || 'Untitled Dashboard',
    description: rawDashboardData.description || '',
    
    // Time Range
    timeRange: rawDashboardData.timeRange || rawDashboardData.time_range || 'week',
    startDate: rawDashboardData.startDate || rawDashboardData.start_date || null,
    endDate: rawDashboardData.endDate || rawDashboardData.end_date || null,
    
    // Metrics
    contentMetrics: rawDashboardData.contentMetrics || rawDashboardData.content_metrics || {},
    digestMetrics: rawDashboardData.digestMetrics || rawDashboardData.digest_metrics || {},
    aiMetrics: rawDashboardData.aiMetrics || rawDashboardData.ai_metrics || {},
    userMetrics: rawDashboardData.userMetrics || rawDashboardData.user_metrics || {},
    systemMetrics: rawDashboardData.systemMetrics || rawDashboardData.system_metrics || {},
    
    // Configuration
    filters: rawDashboardData.filters || {},
    comparisons: rawDashboardData.comparisons || {},
    widgets: rawDashboardData.widgets || [],
    layout: rawDashboardData.layout || {},
    
    // Sharing
    isPublic: rawDashboardData.isPublic || rawDashboardData.is_public || false,
    sharedWith: rawDashboardData.sharedWith || rawDashboardData.shared_with || [],
    
    // Export & Reports
    exportSettings: rawDashboardData.exportSettings || rawDashboardData.export_settings || {},
    scheduledReports: rawDashboardData.scheduledReports || rawDashboardData.scheduled_reports || {},
    
    // Refresh Settings
    lastRefresh: rawDashboardData.lastRefresh || rawDashboardData.last_refresh || null,
    autoRefresh: rawDashboardData.autoRefresh || rawDashboardData.auto_refresh || false,
    refreshInterval: typeof rawDashboardData.refreshInterval === 'number' ? rawDashboardData.refreshInterval :
                     typeof rawDashboardData.refresh_interval === 'number' ? rawDashboardData.refresh_interval : 5,
    
    // Timestamps
    createdAt: rawDashboardData.createdAt || rawDashboardData.created_at || new Date().toISOString(),
    updatedAt: rawDashboardData.updatedAt || rawDashboardData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawDashboardData.wpSynced || rawDashboardData.wp_synced || false,
    lastWpSync: rawDashboardData.lastWpSync || rawDashboardData.last_wp_sync || null
  };
}

/**
 * Get comprehensive analytics dashboard data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} dashboard - Raw analytics dashboard data
 * @returns {Object} Analytics dashboard helper with getters and methods
 */
export function getAnalyticsData(dashboard) {
  const normalizedDashboard = normalizeAnalyticsDashboardData(dashboard);
  
  if (!normalizedDashboard) {
    return createEmptyAnalyticsHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedDashboard.id; },
    get userId() { return normalizedDashboard.userId; },
    get name() { return normalizedDashboard.name; },
    get description() { return normalizedDashboard.description; },
    get hasDescription() { return !!this.description; },
    
    // Time Range
    get timeRange() { return normalizedDashboard.timeRange; },
    get startDate() { return normalizedDashboard.startDate; },
    get endDate() { return normalizedDashboard.endDate; },
    get hasCustomRange() { return this.timeRange === 'custom'; },
    get isRealTime() { return this.timeRange === 'hour'; },
    get isShortTerm() { return ['hour', 'day'].includes(this.timeRange); },
    get isLongTerm() { return ['quarter', 'year'].includes(this.timeRange); },
    
    // Time Range Analysis
    get rangeDays() {
      if (!this.startDate || !this.endDate) return 0;
      const start = new Date(this.startDate);
      const end = new Date(this.endDate);
      return Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
    },
    get rangeHours() {
      if (!this.startDate || !this.endDate) return 0;
      const start = new Date(this.startDate);
      const end = new Date(this.endDate);
      return Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60));
    },
    
    // Content Metrics
    get contentMetrics() { return normalizedDashboard.contentMetrics; },
    get contentIngestionRate() { return this.contentMetrics.ingestionRate || 0; },
    get contentQualityAverage() { return this.contentMetrics.qualityAverage || 0; },
    get contentSourceCount() { return this.contentMetrics.sourceCount || 0; },
    get contentTotalItems() { return this.contentMetrics.totalItems || 0; },
    get contentApprovalRate() { return this.contentMetrics.approvalRate || 0; },
    get contentRejectionRate() { return 100 - this.contentApprovalRate; },
    get hasContentMetrics() { return Object.keys(this.contentMetrics).length > 0; },
    
    // Content Performance
    get isHighContentQuality() { return this.contentQualityAverage >= 80; },
    get isLowContentQuality() { return this.contentQualityAverage < 60; },
    get isHighApprovalRate() { return this.contentApprovalRate >= 80; },
    get isLowApprovalRate() { return this.contentApprovalRate < 60; },
    get contentPerformanceRating() {
      const quality = this.contentQualityAverage;
      const approval = this.contentApprovalRate;
      const average = (quality + approval) / 2;
      if (average >= 90) return 'excellent';
      if (average >= 75) return 'good';
      if (average >= 60) return 'average';
      return 'poor';
    },
    
    // Digest Metrics
    get digestMetrics() { return normalizedDashboard.digestMetrics; },
    get digestCreationRate() { return this.digestMetrics.creationRate || 0; },
    get digestOpenRate() { return this.digestMetrics.openRate || 0; },
    get digestClickRate() { return this.digestMetrics.clickRate || 0; },
    get digestEngagementScore() { return this.digestMetrics.engagementScore || 0; },
    get digestSubscriberCount() { return this.digestMetrics.subscriberCount || 0; },
    get digestDeliveryRate() { return this.digestMetrics.deliveryRate || 0; },
    get digestUnsubscribeRate() { return this.digestMetrics.unsubscribeRate || 0; },
    get hasDigestMetrics() { return Object.keys(this.digestMetrics).length > 0; },
    
    // Digest Performance
    get isHighEngagement() { return this.digestEngagementScore >= 75; },
    get isLowEngagement() { return this.digestEngagementScore < 50; },
    get isHighOpenRate() { return this.digestOpenRate >= 25; },
    get isLowOpenRate() { return this.digestOpenRate < 15; },
    get digestPerformanceRating() {
      const engagement = this.digestEngagementScore;
      const openRate = this.digestOpenRate * 4; // Scale to 100
      const clickRate = this.digestClickRate * 10; // Scale to 100
      const average = (engagement + openRate + clickRate) / 3;
      if (average >= 80) return 'excellent';
      if (average >= 65) return 'good';
      if (average >= 50) return 'average';
      return 'poor';
    },
    
    // AI Metrics
    get aiMetrics() { return normalizedDashboard.aiMetrics; },
    get aiTokensUsed() { return this.aiMetrics.tokensUsed || 0; },
    get aiCostTotal() { return this.aiMetrics.costTotal || 0; },
    get aiRequestCount() { return this.aiMetrics.requestCount || 0; },
    get aiSuccessRate() { return this.aiMetrics.successRate || 0; },
    get aiAverageLatency() { return this.aiMetrics.averageLatency || 0; },
    get aiCacheHitRate() { return this.aiMetrics.cacheHitRate || 0; },
    get aiErrorRate() { return 100 - this.aiSuccessRate; },
    get hasAIMetrics() { return Object.keys(this.aiMetrics).length > 0; },
    
    // AI Performance
    get aiCostPerToken() { return this.aiTokensUsed > 0 ? this.aiCostTotal / this.aiTokensUsed : 0; },
    get aiCostPerRequest() { return this.aiRequestCount > 0 ? this.aiCostTotal / this.aiRequestCount : 0; },
    get isHighAIPerformance() { return this.aiSuccessRate >= 95 && this.aiAverageLatency < 2000; },
    get isLowAIPerformance() { return this.aiSuccessRate < 90 || this.aiAverageLatency > 5000; },
    get isExpensiveAI() { return this.aiCostPerRequest > 0.01; },
    get isCostEffectiveAI() { return this.aiCostPerRequest < 0.001; },
    
    // User Metrics
    get userMetrics() { return normalizedDashboard.userMetrics; },
    get activeUsers() { return this.userMetrics.activeUsers || 0; },
    get newUsers() { return this.userMetrics.newUsers || 0; },
    get userRetentionRate() { return this.userMetrics.retentionRate || 0; },
    get averageSessionDuration() { return this.userMetrics.averageSessionDuration || 0; },
    get userEngagementScore() { return this.userMetrics.engagementScore || 0; },
    get hasUserMetrics() { return Object.keys(this.userMetrics).length > 0; },
    
    // User Performance
    get userGrowthRate() { 
      return this.activeUsers > 0 ? (this.newUsers / this.activeUsers) * 100 : 0;
    },
    get isHighUserEngagement() { return this.userEngagementScore >= 75; },
    get isLowUserEngagement() { return this.userEngagementScore < 50; },
    get isGrowingUserBase() { return this.userGrowthRate > 5; },
    
    // System Metrics
    get systemMetrics() { return normalizedDashboard.systemMetrics; },
    get systemUptime() { return this.systemMetrics.uptime || 0; },
    get systemResponseTime() { return this.systemMetrics.responseTime || 0; },
    get systemErrorRate() { return this.systemMetrics.errorRate || 0; },
    get systemCpuUsage() { return this.systemMetrics.cpuUsage || 0; },
    get systemMemoryUsage() { return this.systemMetrics.memoryUsage || 0; },
    get systemStorageUsage() { return this.systemMetrics.storageUsage || 0; },
    get hasSystemMetrics() { return Object.keys(this.systemMetrics).length > 0; },
    
    // System Performance
    get isSystemHealthy() { 
      return this.systemUptime >= 99 && this.systemErrorRate < 1 && this.systemResponseTime < 500;
    },
    get isSystemUnhealthy() {
      return this.systemUptime < 95 || this.systemErrorRate > 5 || this.systemResponseTime > 2000;
    },
    get systemHealthRating() {
      if (this.isSystemHealthy) return 'excellent';
      if (this.systemUptime >= 98 && this.systemErrorRate < 2) return 'good';
      if (this.systemUptime >= 95 && this.systemErrorRate < 5) return 'average';
      return 'poor';
    },
    
    // Configuration
    get filters() { return normalizedDashboard.filters; },
    get comparisons() { return normalizedDashboard.comparisons; },
    get widgets() { return normalizedDashboard.widgets; },
    get layout() { return normalizedDashboard.layout; },
    get hasFilters() { return Object.keys(this.filters).length > 0; },
    get hasComparisons() { return Object.keys(this.comparisons).length > 0; },
    get widgetCount() { return this.widgets.length; },
    get hasCustomLayout() { return Object.keys(this.layout).length > 0; },
    
    // Widget Analysis
    get hasContentWidgets() { return this.widgets.some(w => w.includes('content')); },
    get hasDigestWidgets() { return this.widgets.some(w => w.includes('digest')); },
    get hasAIWidgets() { return this.widgets.some(w => w.includes('ai')); },
    get hasUserWidgets() { return this.widgets.some(w => w.includes('user')); },
    get hasSystemWidgets() { return this.widgets.some(w => w.includes('system')); },
    
    // Sharing
    get isPublic() { return normalizedDashboard.isPublic; },
    get sharedWith() { return normalizedDashboard.sharedWith; },
    get isShared() { return this.sharedWith.length > 0; },
    get shareCount() { return this.sharedWith.length; },
    get isPrivate() { return !this.isPublic && !this.isShared; },
    
    // Export & Reports
    get exportSettings() { return normalizedDashboard.exportSettings; },
    get scheduledReports() { return normalizedDashboard.scheduledReports; },
    get hasExportSettings() { return Object.keys(this.exportSettings).length > 0; },
    get hasScheduledReports() { return Object.keys(this.scheduledReports).length > 0; },
    get canExport() { return this.hasExportSettings; },
    
    // Refresh Settings
    get lastRefresh() { return normalizedDashboard.lastRefresh; },
    get autoRefresh() { return normalizedDashboard.autoRefresh; },
    get refreshInterval() { return normalizedDashboard.refreshInterval; },
    get hasLastRefresh() { return !!this.lastRefresh; },
    
    // Refresh Analysis
    get minutesSinceRefresh() {
      if (!this.lastRefresh) return null;
      const last = new Date(this.lastRefresh);
      const now = new Date();
      return Math.floor((now.getTime() - last.getTime()) / (1000 * 60));
    },
    get isDataStale() {
      return this.minutesSinceRefresh > (this.refreshInterval * 2);
    },
    get needsRefresh() {
      return !this.autoRefresh && this.minutesSinceRefresh > this.refreshInterval;
    },
    
    // Timestamps
    get createdAt() { return normalizedDashboard.createdAt; },
    get updatedAt() { return normalizedDashboard.updatedAt; },
    
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
    
    // WordPress Integration
    get wpSynced() { return normalizedDashboard.wpSynced; },
    get lastWpSync() { return normalizedDashboard.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Overall Performance
    get overallPerformanceScore() {
      const scores = [];
      if (this.hasContentMetrics) scores.push(this.contentQualityAverage);
      if (this.hasDigestMetrics) scores.push(this.digestEngagementScore);
      if (this.hasAIMetrics) scores.push(this.aiSuccessRate);
      if (this.hasUserMetrics) scores.push(this.userEngagementScore);
      if (this.hasSystemMetrics) scores.push(this.systemUptime);
      
      return scores.length > 0 ? scores.reduce((a, b) => a + b, 0) / scores.length : 0;
    },
    get overallRating() {
      const score = this.overallPerformanceScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      return 'poor';
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.userId && this.name);
    },
    get isComplete() {
      return this.isValid && (this.hasContentMetrics || this.hasDigestMetrics || this.hasAIMetrics);
    },
    get isUsable() {
      return this.isComplete && !this.isDataStale;
    },
    
    // Utility Methods
    canUserAccess(userId) {
      return this.userId === userId || this.isPublic || this.sharedWith.includes(userId);
    },
    getMetricValue(category, metric, defaultValue = 0) {
      const categoryData = this[`${category}Metrics`];
      return categoryData[metric] || defaultValue;
    },
    hasMetricCategory(category) {
      return this[`has${category.charAt(0).toUpperCase() + category.slice(1)}Metrics`];
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        name: this.name,
        timeRange: this.timeRange,
        widgetCount: this.widgetCount,
        isValid: this.isValid,
        isComplete: this.isComplete,
        overallRating: this.overallRating,
        overallPerformanceScore: this.overallPerformanceScore
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        userId: this.userId,
        name: this.name,
        description: this.description,
        timeRange: this.timeRange,
        startDate: this.startDate,
        endDate: this.endDate,
        contentMetrics: this.contentMetrics,
        digestMetrics: this.digestMetrics,
        aiMetrics: this.aiMetrics,
        userMetrics: this.userMetrics,
        systemMetrics: this.systemMetrics,
        filters: this.filters,
        comparisons: this.comparisons,
        widgets: this.widgets,
        layout: this.layout,
        isPublic: this.isPublic,
        sharedWith: this.sharedWith,
        exportSettings: this.exportSettings,
        scheduledReports: this.scheduledReports,
        lastRefresh: this.lastRefresh,
        autoRefresh: this.autoRefresh,
        refreshInterval: this.refreshInterval,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync
      };
    }
  };
}

/**
 * Create empty analytics dashboard helper for null/undefined dashboards
 * @returns {Object} Empty analytics dashboard helper with safe defaults
 */
function createEmptyAnalyticsHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get userId() { return null; },
    get name() { return 'New Dashboard'; },
    get description() { return ''; },
    get hasDescription() { return false; },
    
    // Time Range
    get timeRange() { return 'week'; },
    get startDate() { return null; },
    get endDate() { return null; },
    get hasCustomRange() { return false; },
    get isRealTime() { return false; },
    get isShortTerm() { return false; },
    get isLongTerm() { return false; },
    get rangeDays() { return 0; },
    get rangeHours() { return 0; },
    
    // Content Metrics
    get contentMetrics() { return {}; },
    get contentIngestionRate() { return 0; },
    get contentQualityAverage() { return 0; },
    get contentSourceCount() { return 0; },
    get contentTotalItems() { return 0; },
    get contentApprovalRate() { return 0; },
    get contentRejectionRate() { return 0; },
    get hasContentMetrics() { return false; },
    get isHighContentQuality() { return false; },
    get isLowContentQuality() { return false; },
    get isHighApprovalRate() { return false; },
    get isLowApprovalRate() { return false; },
    get contentPerformanceRating() { return 'unrated'; },
    
    // Digest Metrics
    get digestMetrics() { return {}; },
    get digestCreationRate() { return 0; },
    get digestOpenRate() { return 0; },
    get digestClickRate() { return 0; },
    get digestEngagementScore() { return 0; },
    get digestSubscriberCount() { return 0; },
    get digestDeliveryRate() { return 0; },
    get digestUnsubscribeRate() { return 0; },
    get hasDigestMetrics() { return false; },
    get isHighEngagement() { return false; },
    get isLowEngagement() { return false; },
    get isHighOpenRate() { return false; },
    get isLowOpenRate() { return false; },
    get digestPerformanceRating() { return 'unrated'; },
    
    // AI Metrics
    get aiMetrics() { return {}; },
    get aiTokensUsed() { return 0; },
    get aiCostTotal() { return 0; },
    get aiRequestCount() { return 0; },
    get aiSuccessRate() { return 0; },
    get aiAverageLatency() { return 0; },
    get aiCacheHitRate() { return 0; },
    get aiErrorRate() { return 0; },
    get hasAIMetrics() { return false; },
    get aiCostPerToken() { return 0; },
    get aiCostPerRequest() { return 0; },
    get isHighAIPerformance() { return false; },
    get isLowAIPerformance() { return false; },
    get isExpensiveAI() { return false; },
    get isCostEffectiveAI() { return false; },
    
    // User Metrics
    get userMetrics() { return {}; },
    get activeUsers() { return 0; },
    get newUsers() { return 0; },
    get userRetentionRate() { return 0; },
    get averageSessionDuration() { return 0; },
    get userEngagementScore() { return 0; },
    get hasUserMetrics() { return false; },
    get userGrowthRate() { return 0; },
    get isHighUserEngagement() { return false; },
    get isLowUserEngagement() { return false; },
    get isGrowingUserBase() { return false; },
    
    // System Metrics
    get systemMetrics() { return {}; },
    get systemUptime() { return 0; },
    get systemResponseTime() { return 0; },
    get systemErrorRate() { return 0; },
    get systemCpuUsage() { return 0; },
    get systemMemoryUsage() { return 0; },
    get systemStorageUsage() { return 0; },
    get hasSystemMetrics() { return false; },
    get isSystemHealthy() { return false; },
    get isSystemUnhealthy() { return false; },
    get systemHealthRating() { return 'unrated'; },
    
    // Configuration
    get filters() { return {}; },
    get comparisons() { return {}; },
    get widgets() { return []; },
    get layout() { return {}; },
    get hasFilters() { return false; },
    get hasComparisons() { return false; },
    get widgetCount() { return 0; },
    get hasCustomLayout() { return false; },
    get hasContentWidgets() { return false; },
    get hasDigestWidgets() { return false; },
    get hasAIWidgets() { return false; },
    get hasUserWidgets() { return false; },
    get hasSystemWidgets() { return false; },
    
    // Sharing
    get isPublic() { return false; },
    get sharedWith() { return []; },
    get isShared() { return false; },
    get shareCount() { return 0; },
    get isPrivate() { return true; },
    
    // Export & Reports
    get exportSettings() { return {}; },
    get scheduledReports() { return {}; },
    get hasExportSettings() { return false; },
    get hasScheduledReports() { return false; },
    get canExport() { return false; },
    
    // Refresh Settings
    get lastRefresh() { return null; },
    get autoRefresh() { return false; },
    get refreshInterval() { return 5; },
    get hasLastRefresh() { return false; },
    get minutesSinceRefresh() { return null; },
    get isDataStale() { return false; },
    get needsRefresh() { return false; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get daysSinceUpdate() { return 0; },
    get isRecent() { return false; },
    get isStale() { return false; },
    
    // WordPress Integration
    get wpSynced() { return false; },
    get lastWpSync() { return null; },
    get isSyncedToWordPress() { return false; },
    get needsWordPressSync() { return false; },
    
    // Overall Performance
    get overallPerformanceScore() { return 0; },
    get overallRating() { return 'unrated'; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isUsable() { return false; },
    
    // Utility Methods
    canUserAccess(userId) { return false; },
    getMetricValue(category, metric, defaultValue = 0) { return defaultValue; },
    hasMetricCategory(category) { return false; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        name: 'New Dashboard',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        name: 'New Dashboard',
        timeRange: 'week',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Analytics Dashboards
 */

/**
 * Create new analytics dashboard
 * @param {Object} dashboardData - Initial dashboard data
 * @returns {Promise<Object>} Created analytics dashboard
 */
export async function createAnalyticsDashboard(dashboardData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create analytics dashboard');
    }

    const newDashboard = {
      id: crypto.randomUUID(),
      ...dashboardData,
      userId: currentUser.id,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.analyticsDashboards.create(newDashboard);
    }

    // Update local store
    analyticsDashboardStore.update(dashboards => [...dashboards, newDashboard]);

    log(`[Analytics] Created new dashboard: ${newDashboard.id}`, 'info');
    return getAnalyticsData(newDashboard);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Analytics] Error creating dashboard: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing analytics dashboard
 * @param {string} dashboardId - Dashboard ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated analytics dashboard
 */
export async function updateAnalyticsDashboard(dashboardId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.analyticsDashboards.update(dashboardId, updatedData);
    }

    // Update local store
    analyticsDashboardStore.update(dashboards => 
      dashboards.map(dashboard => 
        dashboard.id === dashboardId 
          ? { ...dashboard, ...updatedData }
          : dashboard
      )
    );

    log(`[Analytics] Updated dashboard: ${dashboardId}`, 'info');
    
    const updatedDashboard = await getAnalyticsDashboardById(dashboardId);
    return updatedDashboard;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Analytics] Error updating dashboard: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete analytics dashboard
 * @param {string} dashboardId - Dashboard ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteAnalyticsDashboard(dashboardId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.analyticsDashboards.delete(dashboardId);
    }

    // Update local store
    analyticsDashboardStore.update(dashboards => 
      dashboards.filter(dashboard => dashboard.id !== dashboardId)
    );

    log(`[Analytics] Deleted dashboard: ${dashboardId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Analytics] Error deleting dashboard: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get analytics dashboard by ID
 * @param {string} dashboardId - Dashboard ID
 * @returns {Promise<Object|null>} Analytics dashboard data or null
 */
export async function getAnalyticsDashboardById(dashboardId) {
  try {
    let dashboard = null;

    // Try LiveStore first
    if (browser && liveStore) {
      dashboard = await liveStore.analyticsDashboards.findById(dashboardId);
    }

    // Fallback to local store
    if (!dashboard) {
      const allDashboards = await new Promise(resolve => {
        analyticsDashboardStore.subscribe(value => resolve(value))();
      });
      dashboard = allDashboards.find(d => d.id === dashboardId);
    }

    return dashboard ? getAnalyticsData(dashboard) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Analytics] Error getting dashboard by ID: ${errorMessage}`, 'error');
    return null;
  }
}

export default {
  store: analyticsDashboardStore,
  getAnalyticsData,
  createAnalyticsDashboard,
  updateAnalyticsDashboard,
  deleteAnalyticsDashboard,
  getAnalyticsDashboardById
}; 