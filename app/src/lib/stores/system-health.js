/**
 * System Health Monitoring Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview System Health business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * System status types
 * @typedef {'healthy' | 'warning' | 'critical' | 'maintenance' | 'unknown'} SystemStatus
 */

/**
 * Service status types
 * @typedef {'online' | 'offline' | 'degraded' | 'maintenance'} ServiceStatus
 */

/**
 * Enhanced System Health object with comprehensive fields
 * @typedef {Object} SystemHealth
 * @property {string} id - Health check identifier
 * @property {SystemStatus} overallStatus - Overall system status
 * @property {ServiceStatus} crawlerStatus - Content crawler status
 * @property {ServiceStatus} aiServiceStatus - AI service status
 * @property {ServiceStatus} databaseStatus - Database status
 * @property {ServiceStatus} cacheStatus - Cache system status
 * @property {ServiceStatus} emailStatus - Email service status
 * @property {Object[]} errorCounts - Error count tracking
 * @property {Object[]} performanceMetrics - Performance monitoring data
 * @property {Object} queueStatus - Background job queue status
 * @property {Object[]} backgroundJobs - Active background jobs
 * @property {Object} resourceUsage - System resource utilization
 * @property {Object} uptimeStats - System uptime statistics
 * @property {number[]} responseTimes - Response time history
 * @property {Object[]} alerts - Active system alerts
 * @property {Object[]} notifications - System notifications
 * @property {Object[]} maintenanceWindows - Scheduled maintenance
 * @property {Object[]} systemLogs - Recent system logs
 * @property {Object} healthChecks - Individual health check results
 * @property {Object} dependencies - External dependency status
 * @property {Date} lastCheck - Last health check timestamp
 * @property {number} checkInterval - Health check interval (minutes)
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<SystemHealth[]>} */
export const systemHealthStore = writable([]);

/**
 * Normalize system health data from any source to consistent format
 * @param {Object} rawHealthData - Raw system health data
 * @returns {Object|null} Normalized system health data
 */
function normalizeSystemHealthData(rawHealthData) {
  if (!rawHealthData || typeof rawHealthData !== 'object' || !rawHealthData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawHealthData.id,
    overallStatus: rawHealthData.overallStatus || rawHealthData.overall_status || 'unknown',
    
    // Service Status
    crawlerStatus: rawHealthData.crawlerStatus || rawHealthData.crawler_status || 'unknown',
    aiServiceStatus: rawHealthData.aiServiceStatus || rawHealthData.ai_service_status || 'unknown',
    databaseStatus: rawHealthData.databaseStatus || rawHealthData.database_status || 'unknown',
    cacheStatus: rawHealthData.cacheStatus || rawHealthData.cache_status || 'unknown',
    emailStatus: rawHealthData.emailStatus || rawHealthData.email_status || 'unknown',
    
    // Error Tracking
    errorCounts: Array.isArray(rawHealthData.errorCounts) ? rawHealthData.errorCounts :
                 Array.isArray(rawHealthData.error_counts) ? rawHealthData.error_counts : [],
    
    // Performance Metrics
    performanceMetrics: Array.isArray(rawHealthData.performanceMetrics) ? rawHealthData.performanceMetrics :
                        Array.isArray(rawHealthData.performance_metrics) ? rawHealthData.performance_metrics : [],
    
    // Queue & Jobs
    queueStatus: rawHealthData.queueStatus || rawHealthData.queue_status || {},
    backgroundJobs: Array.isArray(rawHealthData.backgroundJobs) ? rawHealthData.backgroundJobs :
                    Array.isArray(rawHealthData.background_jobs) ? rawHealthData.background_jobs : [],
    
    // Resource Usage
    resourceUsage: rawHealthData.resourceUsage || rawHealthData.resource_usage || {
      memory: 0,
      cpu: 0,
      storage: 0,
      network: 0
    },
    
    // Uptime & Performance
    uptimeStats: rawHealthData.uptimeStats || rawHealthData.uptime_stats || {
      uptime: 0,
      totalDowntime: 0,
      availability: 0
    },
    responseTimes: Array.isArray(rawHealthData.responseTimes) ? rawHealthData.responseTimes :
                   Array.isArray(rawHealthData.response_times) ? rawHealthData.response_times : [],
    
    // Alerts & Notifications
    alerts: Array.isArray(rawHealthData.alerts) ? rawHealthData.alerts : [],
    notifications: Array.isArray(rawHealthData.notifications) ? rawHealthData.notifications : [],
    
    // Maintenance
    maintenanceWindows: Array.isArray(rawHealthData.maintenanceWindows) ? rawHealthData.maintenanceWindows :
                        Array.isArray(rawHealthData.maintenance_windows) ? rawHealthData.maintenance_windows : [],
    
    // Logs & Checks
    systemLogs: Array.isArray(rawHealthData.systemLogs) ? rawHealthData.systemLogs :
                Array.isArray(rawHealthData.system_logs) ? rawHealthData.system_logs : [],
    healthChecks: rawHealthData.healthChecks || rawHealthData.health_checks || {},
    dependencies: rawHealthData.dependencies || {},
    
    // Timing
    lastCheck: rawHealthData.lastCheck || rawHealthData.last_check || null,
    checkInterval: typeof rawHealthData.checkInterval === 'number' ? rawHealthData.checkInterval :
                   typeof rawHealthData.check_interval === 'number' ? rawHealthData.check_interval : 5,
    
    // Timestamps
    createdAt: rawHealthData.createdAt || rawHealthData.created_at || new Date().toISOString(),
    updatedAt: rawHealthData.updatedAt || rawHealthData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawHealthData.wpSynced || rawHealthData.wp_synced || false,
    lastWpSync: rawHealthData.lastWpSync || rawHealthData.last_wp_sync || null
  };
}

/**
 * Get comprehensive system health data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} health - Raw system health data
 * @returns {Object} System health helper with getters and methods
 */
export function getSystemHealthData(health) {
  const normalizedHealth = normalizeSystemHealthData(health);
  
  if (!normalizedHealth) {
    return createEmptySystemHealthHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedHealth.id; },
    get overallStatus() { return normalizedHealth.overallStatus; },
    
    // Overall Status Checks
    get isHealthy() { return this.overallStatus === 'healthy'; },
    get hasWarnings() { return this.overallStatus === 'warning'; },
    get isCritical() { return this.overallStatus === 'critical'; },
    get isInMaintenance() { return this.overallStatus === 'maintenance'; },
    get isUnknown() { return this.overallStatus === 'unknown'; },
    get isOperational() { return this.isHealthy || this.hasWarnings; },
    
    // Service Status
    get crawlerStatus() { return normalizedHealth.crawlerStatus; },
    get aiServiceStatus() { return normalizedHealth.aiServiceStatus; },
    get databaseStatus() { return normalizedHealth.databaseStatus; },
    get cacheStatus() { return normalizedHealth.cacheStatus; },
    get emailStatus() { return normalizedHealth.emailStatus; },
    
    // Service Status Checks
    get isCrawlerOnline() { return this.crawlerStatus === 'online'; },
    get isAIServiceOnline() { return this.aiServiceStatus === 'online'; },
    get isDatabaseOnline() { return this.databaseStatus === 'online'; },
    get isCacheOnline() { return this.cacheStatus === 'online'; },
    get isEmailOnline() { return this.emailStatus === 'online'; },
    
    get allServicesOnline() {
      return this.isCrawlerOnline && this.isAIServiceOnline && 
             this.isDatabaseOnline && this.isCacheOnline && this.isEmailOnline;
    },
    get criticalServicesOnline() {
      return this.isDatabaseOnline && this.isAIServiceOnline;
    },
    get offlineServices() {
      const services = [];
      if (!this.isCrawlerOnline) services.push('crawler');
      if (!this.isAIServiceOnline) services.push('ai-service');
      if (!this.isDatabaseOnline) services.push('database');
      if (!this.isCacheOnline) services.push('cache');
      if (!this.isEmailOnline) services.push('email');
      return services;
    },
    get offlineServiceCount() { return this.offlineServices.length; },
    
    // Error Tracking
    get errorCounts() { return normalizedHealth.errorCounts; },
    get hasErrors() { return this.errorCounts.length > 0; },
    get totalErrors() {
      return this.errorCounts.reduce((sum, error) => sum + (error.count || 0), 0);
    },
    get recentErrors() {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return this.errorCounts.filter(error => new Date(error.timestamp) > oneHourAgo);
    },
    get recentErrorCount() {
      return this.recentErrors.reduce((sum, error) => sum + (error.count || 0), 0);
    },
    get errorRate() {
      const total = this.performanceMetrics.reduce((sum, metric) => sum + (metric.requests || 0), 0);
      return total > 0 ? (this.totalErrors / total) * 100 : 0;
    },
    
    // Performance Metrics
    get performanceMetrics() { return normalizedHealth.performanceMetrics; },
    get hasPerformanceData() { return this.performanceMetrics.length > 0; },
    get averageResponseTime() {
      if (this.responseTimes.length === 0) return 0;
      return this.responseTimes.reduce((sum, time) => sum + time, 0) / this.responseTimes.length;
    },
    get maxResponseTime() {
      return this.responseTimes.length > 0 ? Math.max(...this.responseTimes) : 0;
    },
    get minResponseTime() {
      return this.responseTimes.length > 0 ? Math.min(...this.responseTimes) : 0;
    },
    
    // Performance Analysis
    get performanceRating() {
      const avgResponse = this.averageResponseTime;
      if (avgResponse < 200) return 'excellent';
      if (avgResponse < 500) return 'good';
      if (avgResponse < 1000) return 'average';
      if (avgResponse < 2000) return 'poor';
      return 'critical';
    },
    get isPerformanceGood() { return this.averageResponseTime < 500; },
    get isPerformancePoor() { return this.averageResponseTime > 1000; },
    
    // Queue & Jobs
    get queueStatus() { return normalizedHealth.queueStatus; },
    get backgroundJobs() { return normalizedHealth.backgroundJobs; },
    get queueLength() { return this.queueStatus.length || 0; },
    get activeJobCount() { return this.backgroundJobs.filter(job => job.status === 'running').length; },
    get pendingJobCount() { return this.backgroundJobs.filter(job => job.status === 'pending').length; },
    get failedJobCount() { return this.backgroundJobs.filter(job => job.status === 'failed').length; },
    get hasQueueBacklog() { return this.queueLength > 100; },
    get hasFailedJobs() { return this.failedJobCount > 0; },
    
    // Resource Usage
    get resourceUsage() { return normalizedHealth.resourceUsage; },
    get memoryUsage() { return this.resourceUsage.memory; },
    get cpuUsage() { return this.resourceUsage.cpu; },
    get storageUsage() { return this.resourceUsage.storage; },
    get networkUsage() { return this.resourceUsage.network; },
    
    // Resource Analysis
    get isMemoryHigh() { return this.memoryUsage > 80; },
    get isCpuHigh() { return this.cpuUsage > 80; },
    get isStorageHigh() { return this.storageUsage > 90; },
    get isNetworkHigh() { return this.networkUsage > 80; },
    get hasResourceIssues() {
      return this.isMemoryHigh || this.isCpuHigh || this.isStorageHigh || this.isNetworkHigh;
    },
    get resourceHealthRating() {
      const maxUsage = Math.max(this.memoryUsage, this.cpuUsage, this.storageUsage, this.networkUsage);
      if (maxUsage < 50) return 'excellent';
      if (maxUsage < 70) return 'good';
      if (maxUsage < 85) return 'warning';
      return 'critical';
    },
    
    // Uptime & Availability
    get uptimeStats() { return normalizedHealth.uptimeStats; },
    get uptime() { return this.uptimeStats.uptime; },
    get totalDowntime() { return this.uptimeStats.totalDowntime; },
    get availability() { return this.uptimeStats.availability; },
    get responseTimes() { return normalizedHealth.responseTimes; },
    
    // Uptime Analysis
    get uptimePercentage() { return this.availability; },
    get isHighAvailability() { return this.availability >= 99.9; },
    get isLowAvailability() { return this.availability < 99.0; },
    get uptimeRating() {
      if (this.availability >= 99.9) return 'excellent';
      if (this.availability >= 99.5) return 'good';
      if (this.availability >= 99.0) return 'average';
      return 'poor';
    },
    
    // Alerts & Notifications
    get alerts() { return normalizedHealth.alerts; },
    get notifications() { return normalizedHealth.notifications; },
    get hasAlerts() { return this.alerts.length > 0; },
    get hasNotifications() { return this.notifications.length > 0; },
    get criticalAlerts() { return this.alerts.filter(alert => alert.severity === 'critical'); },
    get warningAlerts() { return this.alerts.filter(alert => alert.severity === 'warning'); },
    get hasCriticalAlerts() { return this.criticalAlerts.length > 0; },
    get alertCount() { return this.alerts.length; },
    get notificationCount() { return this.notifications.length; },
    
    // Maintenance
    get maintenanceWindows() { return normalizedHealth.maintenanceWindows; },
    get hasScheduledMaintenance() { return this.maintenanceWindows.length > 0; },
    get nextMaintenance() {
      const upcoming = this.maintenanceWindows
        .filter(window => new Date(window.startTime) > new Date())
        .sort((a, b) => new Date(a.startTime).getTime() - new Date(b.startTime).getTime());
      return upcoming.length > 0 ? upcoming[0] : null;
    },
    get isInMaintenanceWindow() {
      const now = new Date();
      return this.maintenanceWindows.some(window => 
        new Date(window.startTime) <= now && new Date(window.endTime) >= now
      );
    },
    
    // Logs & Health Checks
    get systemLogs() { return normalizedHealth.systemLogs; },
    get healthChecks() { return normalizedHealth.healthChecks; },
    get dependencies() { return normalizedHealth.dependencies; },
    get hasSystemLogs() { return this.systemLogs.length > 0; },
    get hasHealthChecks() { return Object.keys(this.healthChecks).length > 0; },
    get hasDependencies() { return Object.keys(this.dependencies).length > 0; },
    get recentLogs() {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return this.systemLogs.filter(log => new Date(log.timestamp) > oneHourAgo);
    },
    get errorLogs() {
      return this.systemLogs.filter(log => log.level === 'error');
    },
    
    // Health Check Analysis
    get passedHealthChecks() {
      return Object.values(this.healthChecks).filter(check => check.status === 'pass').length;
    },
    get failedHealthChecks() {
      return Object.values(this.healthChecks).filter(check => check.status === 'fail').length;
    },
    get healthCheckSuccessRate() {
      const total = Object.keys(this.healthChecks).length;
      return total > 0 ? (this.passedHealthChecks / total) * 100 : 0;
    },
    
    // Timing
    get lastCheck() { return normalizedHealth.lastCheck; },
    get checkInterval() { return normalizedHealth.checkInterval; },
    get hasLastCheck() { return !!this.lastCheck; },
    get minutesSinceLastCheck() {
      if (!this.lastCheck) return null;
      const last = new Date(this.lastCheck);
      const now = new Date();
      return Math.floor((now.getTime() - last.getTime()) / (1000 * 60));
    },
    get isCheckOverdue() {
      return this.minutesSinceLastCheck > (this.checkInterval * 2);
    },
    get needsHealthCheck() {
      return !this.hasLastCheck || this.minutesSinceLastCheck > this.checkInterval;
    },
    
    // Timestamps
    get createdAt() { return normalizedHealth.createdAt; },
    get updatedAt() { return normalizedHealth.updatedAt; },
    
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
    get isRecent() { return this.age <= 1; },
    get isStale() { return this.daysSinceUpdate > 1; },
    
    // WordPress Integration
    get wpSynced() { return normalizedHealth.wpSynced; },
    get lastWpSync() { return normalizedHealth.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Overall Health Score
    get healthScore() {
      let score = 100;
      
      // Service availability (40% weight)
      const serviceScore = (this.allServicesOnline ? 40 : this.criticalServicesOnline ? 20 : 0);
      
      // Performance (20% weight)
      const perfScore = this.isPerformanceGood ? 20 : this.isPerformancePoor ? 0 : 10;
      
      // Resource usage (20% weight)
      const resourceScore = this.hasResourceIssues ? 0 : 20;
      
      // Error rate (10% weight)
      const errorScore = this.errorRate < 1 ? 10 : this.errorRate < 5 ? 5 : 0;
      
      // Uptime (10% weight)
      const uptimeScore = this.isHighAvailability ? 10 : this.isLowAvailability ? 0 : 5;
      
      return serviceScore + perfScore + resourceScore + errorScore + uptimeScore;
    },
    get healthRating() {
      const score = this.healthScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.overallStatus);
    },
    get isComplete() {
      return this.isValid && this.hasPerformanceData;
    },
    get isUsable() {
      return this.isComplete && !this.isCheckOverdue;
    },
    
    // Utility Methods
    getServiceStatus(serviceName) {
      return this[`${serviceName}Status`] || 'unknown';
    },
    isServiceOnline(serviceName) {
      return this.getServiceStatus(serviceName) === 'online';
    },
    getResourceUsage(resourceType) {
      return this.resourceUsage[resourceType] || 0;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        overallStatus: this.overallStatus,
        healthScore: this.healthScore,
        healthRating: this.healthRating,
        allServicesOnline: this.allServicesOnline,
        hasResourceIssues: this.hasResourceIssues,
        isValid: this.isValid,
        isComplete: this.isComplete
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        overallStatus: this.overallStatus,
        crawlerStatus: this.crawlerStatus,
        aiServiceStatus: this.aiServiceStatus,
        databaseStatus: this.databaseStatus,
        cacheStatus: this.cacheStatus,
        emailStatus: this.emailStatus,
        errorCounts: this.errorCounts,
        performanceMetrics: this.performanceMetrics,
        queueStatus: this.queueStatus,
        backgroundJobs: this.backgroundJobs,
        resourceUsage: this.resourceUsage,
        uptimeStats: this.uptimeStats,
        responseTimes: this.responseTimes,
        alerts: this.alerts,
        notifications: this.notifications,
        maintenanceWindows: this.maintenanceWindows,
        systemLogs: this.systemLogs,
        healthChecks: this.healthChecks,
        dependencies: this.dependencies,
        lastCheck: this.lastCheck,
        checkInterval: this.checkInterval,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync
      };
    }
  };
}

/**
 * Create empty system health helper for null/undefined health data
 * @returns {Object} Empty system health helper with safe defaults
 */
function createEmptySystemHealthHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get overallStatus() { return 'unknown'; },
    
    // Overall Status Checks
    get isHealthy() { return false; },
    get hasWarnings() { return false; },
    get isCritical() { return false; },
    get isInMaintenance() { return false; },
    get isUnknown() { return true; },
    get isOperational() { return false; },
    
    // Service Status
    get crawlerStatus() { return 'unknown'; },
    get aiServiceStatus() { return 'unknown'; },
    get databaseStatus() { return 'unknown'; },
    get cacheStatus() { return 'unknown'; },
    get emailStatus() { return 'unknown'; },
    
    // Service Status Checks
    get isCrawlerOnline() { return false; },
    get isAIServiceOnline() { return false; },
    get isDatabaseOnline() { return false; },
    get isCacheOnline() { return false; },
    get isEmailOnline() { return false; },
    get allServicesOnline() { return false; },
    get criticalServicesOnline() { return false; },
    get offlineServices() { return []; },
    get offlineServiceCount() { return 0; },
    
    // Error Tracking
    get errorCounts() { return []; },
    get hasErrors() { return false; },
    get totalErrors() { return 0; },
    get recentErrors() { return []; },
    get recentErrorCount() { return 0; },
    get errorRate() { return 0; },
    
    // Performance Metrics
    get performanceMetrics() { return []; },
    get hasPerformanceData() { return false; },
    get averageResponseTime() { return 0; },
    get maxResponseTime() { return 0; },
    get minResponseTime() { return 0; },
    get performanceRating() { return 'unknown'; },
    get isPerformanceGood() { return false; },
    get isPerformancePoor() { return false; },
    
    // Queue & Jobs
    get queueStatus() { return {}; },
    get backgroundJobs() { return []; },
    get queueLength() { return 0; },
    get activeJobCount() { return 0; },
    get pendingJobCount() { return 0; },
    get failedJobCount() { return 0; },
    get hasQueueBacklog() { return false; },
    get hasFailedJobs() { return false; },
    
    // Resource Usage
    get resourceUsage() { return { memory: 0, cpu: 0, storage: 0, network: 0 }; },
    get memoryUsage() { return 0; },
    get cpuUsage() { return 0; },
    get storageUsage() { return 0; },
    get networkUsage() { return 0; },
    get isMemoryHigh() { return false; },
    get isCpuHigh() { return false; },
    get isStorageHigh() { return false; },
    get isNetworkHigh() { return false; },
    get hasResourceIssues() { return false; },
    get resourceHealthRating() { return 'unknown'; },
    
    // Uptime & Availability
    get uptimeStats() { return { uptime: 0, totalDowntime: 0, availability: 0 }; },
    get uptime() { return 0; },
    get totalDowntime() { return 0; },
    get availability() { return 0; },
    get responseTimes() { return []; },
    get uptimePercentage() { return 0; },
    get isHighAvailability() { return false; },
    get isLowAvailability() { return false; },
    get uptimeRating() { return 'unknown'; },
    
    // Alerts & Notifications
    get alerts() { return []; },
    get notifications() { return []; },
    get hasAlerts() { return false; },
    get hasNotifications() { return false; },
    get criticalAlerts() { return []; },
    get warningAlerts() { return []; },
    get hasCriticalAlerts() { return false; },
    get alertCount() { return 0; },
    get notificationCount() { return 0; },
    
    // Maintenance
    get maintenanceWindows() { return []; },
    get hasScheduledMaintenance() { return false; },
    get nextMaintenance() { return null; },
    get isInMaintenanceWindow() { return false; },
    
    // Logs & Health Checks
    get systemLogs() { return []; },
    get healthChecks() { return {}; },
    get dependencies() { return {}; },
    get hasSystemLogs() { return false; },
    get hasHealthChecks() { return false; },
    get hasDependencies() { return false; },
    get recentLogs() { return []; },
    get errorLogs() { return []; },
    get passedHealthChecks() { return 0; },
    get failedHealthChecks() { return 0; },
    get healthCheckSuccessRate() { return 0; },
    
    // Timing
    get lastCheck() { return null; },
    get checkInterval() { return 5; },
    get hasLastCheck() { return false; },
    get minutesSinceLastCheck() { return null; },
    get isCheckOverdue() { return false; },
    get needsHealthCheck() { return true; },
    
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
    
    // Overall Health Score
    get healthScore() { return 0; },
    get healthRating() { return 'unknown'; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isUsable() { return false; },
    
    // Utility Methods
    getServiceStatus(serviceName) { return 'unknown'; },
    isServiceOnline(serviceName) { return false; },
    getResourceUsage(resourceType) { return 0; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        overallStatus: 'unknown',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        overallStatus: 'unknown',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for System Health
 */

/**
 * Create new system health record
 * @param {Object} healthData - Initial health data
 * @returns {Promise<Object>} Created system health record
 */
export async function createSystemHealth(healthData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create system health record');
    }

    const newHealth = {
      id: crypto.randomUUID(),
      ...healthData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.systemHealth.create(newHealth);
    }

    // Update local store
    systemHealthStore.update(records => [...records, newHealth]);

    log(`[System Health] Created new health record: ${newHealth.id}`, 'info');
    return getSystemHealthData(newHealth);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[System Health] Error creating health record: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing system health record
 * @param {string} healthId - Health record ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated system health record
 */
export async function updateSystemHealth(healthId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.systemHealth.update(healthId, updatedData);
    }

    // Update local store
    systemHealthStore.update(records => 
      records.map(record => 
        record.id === healthId 
          ? { ...record, ...updatedData }
          : record
      )
    );

    log(`[System Health] Updated health record: ${healthId}`, 'info');
    
    const updatedHealth = await getSystemHealthById(healthId);
    return updatedHealth;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[System Health] Error updating health record: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete system health record
 * @param {string} healthId - Health record ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteSystemHealth(healthId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.systemHealth.delete(healthId);
    }

    // Update local store
    systemHealthStore.update(records => 
      records.filter(record => record.id !== healthId)
    );

    log(`[System Health] Deleted health record: ${healthId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[System Health] Error deleting health record: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get system health record by ID
 * @param {string} healthId - Health record ID
 * @returns {Promise<Object|null>} System health data or null
 */
export async function getSystemHealthById(healthId) {
  try {
    let health = null;

    // Try LiveStore first
    if (browser && liveStore) {
      health = await liveStore.systemHealth.findById(healthId);
    }

    // Fallback to local store
    if (!health) {
      const allRecords = await new Promise(resolve => {
        systemHealthStore.subscribe(value => resolve(value))();
      });
      health = allRecords.find(h => h.id === healthId);
    }

    return health ? getSystemHealthData(health) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[System Health] Error getting health record by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get latest system health record
 * @returns {Promise<Object|null>} Latest system health data or null
 */
export async function getLatestSystemHealth() {
  try {
    let records = [];

    // Try LiveStore first
    if (browser && liveStore) {
      records = await liveStore.systemHealth.findMany({
        orderBy: { createdAt: 'desc' },
        take: 1
      });
    }

    // Fallback to local store
    if (records.length === 0) {
      const allRecords = await new Promise(resolve => {
        systemHealthStore.subscribe(value => resolve(value))();
      });
      records = allRecords
        .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
        .slice(0, 1);
    }

    return records.length > 0 ? getSystemHealthData(records[0]) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[System Health] Error getting latest health record: ${errorMessage}`, 'error');
    return null;
  }
}

export default {
  store: systemHealthStore,
  getSystemHealthData,
  createSystemHealth,
  updateSystemHealth,
  deleteSystemHealth,
  getSystemHealthById,
  getLatestSystemHealth
}; 