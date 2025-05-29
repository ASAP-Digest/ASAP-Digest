/**
 * External Integrations Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview External integrations business object for third-party service management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Integration types
 * @typedef {'api' | 'webhook' | 'oauth' | 'rss' | 'database' | 'file' | 'email' | 'social'} IntegrationType
 */

/**
 * Integration status
 * @typedef {'active' | 'inactive' | 'error' | 'pending' | 'expired'} IntegrationStatus
 */

/**
 * Connected service
 * @typedef {Object} ConnectedService
 * @property {string} id - Service identifier
 * @property {string} name - Service name
 * @property {IntegrationType} type - Integration type
 * @property {IntegrationStatus} status - Current status
 * @property {Object} credentials - Service credentials
 * @property {Object} config - Service configuration
 * @property {Date} connectedAt - Connection timestamp
 * @property {Date} lastSync - Last sync timestamp
 */

/**
 * Enhanced External Integrations object with comprehensive fields
 * @typedef {Object} IntegrationData
 * @property {string} id - Integration instance identifier
 * @property {string} userId - User identifier
 * @property {ConnectedService[]} connectedServices - Connected service configurations
 * @property {Object[]} apiCredentials - API credential management
 * @property {Object[]} syncStatus - Synchronization status tracking
 * @property {Object[]} webhookConfigurations - Webhook endpoint configurations
 * @property {Object[]} dataMappings - Data transformation mappings
 * @property {Object[]} transformationRules - Data transformation rules
 * @property {Object[]} integrationHealth - Integration health monitoring
 * @property {Object} errorHandling - Error handling configuration
 * @property {Object[]} rateLimitStatus - Rate limiting status
 * @property {Object[]} quotaUsage - Quota usage tracking
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 */

/** @type {import('svelte/store').Writable<IntegrationData[]>} */
export const integrationStore = writable([]);

/**
 * Normalize integration data from any source to consistent format
 * @param {Object} rawIntegrationData - Raw integration data
 * @returns {Object|null} Normalized integration data
 */
function normalizeIntegrationData(rawIntegrationData) {
  if (!rawIntegrationData || typeof rawIntegrationData !== 'object' || !rawIntegrationData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawIntegrationData.id,
    userId: rawIntegrationData.userId || rawIntegrationData.user_id || null,
    
    // Service Connections
    connectedServices: Array.isArray(rawIntegrationData.connectedServices) ? rawIntegrationData.connectedServices :
                       Array.isArray(rawIntegrationData.connected_services) ? rawIntegrationData.connected_services : [],
    apiCredentials: Array.isArray(rawIntegrationData.apiCredentials) ? rawIntegrationData.apiCredentials :
                    Array.isArray(rawIntegrationData.api_credentials) ? rawIntegrationData.api_credentials : [],
    
    // Synchronization
    syncStatus: Array.isArray(rawIntegrationData.syncStatus) ? rawIntegrationData.syncStatus :
                Array.isArray(rawIntegrationData.sync_status) ? rawIntegrationData.sync_status : [],
    webhookConfigurations: Array.isArray(rawIntegrationData.webhookConfigurations) ? rawIntegrationData.webhookConfigurations :
                           Array.isArray(rawIntegrationData.webhook_configurations) ? rawIntegrationData.webhook_configurations : [],
    
    // Data Processing
    dataMappings: Array.isArray(rawIntegrationData.dataMappings) ? rawIntegrationData.dataMappings :
                  Array.isArray(rawIntegrationData.data_mappings) ? rawIntegrationData.data_mappings : [],
    transformationRules: Array.isArray(rawIntegrationData.transformationRules) ? rawIntegrationData.transformationRules :
                         Array.isArray(rawIntegrationData.transformation_rules) ? rawIntegrationData.transformation_rules : [],
    
    // Health & Monitoring
    integrationHealth: Array.isArray(rawIntegrationData.integrationHealth) ? rawIntegrationData.integrationHealth :
                       Array.isArray(rawIntegrationData.integration_health) ? rawIntegrationData.integration_health : [],
    errorHandling: rawIntegrationData.errorHandling || rawIntegrationData.error_handling || {
      retryPolicy: {},
      fallbackActions: [],
      alertThresholds: {},
      errorLogging: true
    },
    
    // Rate Limiting & Quotas
    rateLimitStatus: Array.isArray(rawIntegrationData.rateLimitStatus) ? rawIntegrationData.rateLimitStatus :
                     Array.isArray(rawIntegrationData.rate_limit_status) ? rawIntegrationData.rate_limit_status : [],
    quotaUsage: Array.isArray(rawIntegrationData.quotaUsage) ? rawIntegrationData.quotaUsage :
                Array.isArray(rawIntegrationData.quota_usage) ? rawIntegrationData.quota_usage : [],
    
    // Timestamps
    createdAt: rawIntegrationData.createdAt || rawIntegrationData.created_at || new Date().toISOString(),
    updatedAt: rawIntegrationData.updatedAt || rawIntegrationData.updated_at || new Date().toISOString(),
    
    // Metadata
    metadata: rawIntegrationData.metadata || {}
  };
}

/**
 * Get comprehensive integration data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} integration - Raw integration data
 * @returns {Object} Integration helper with getters and methods
 */
export function getIntegrationData(integration) {
  const normalizedIntegration = normalizeIntegrationData(integration);
  
  if (!normalizedIntegration) {
    return createEmptyIntegrationHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedIntegration.id; },
    get userId() { return normalizedIntegration.userId; },
    
    // Connected Services
    get connectedServices() { return normalizedIntegration.connectedServices; },
    get connectedServiceCount() { return this.connectedServices.length; },
    get hasConnectedServices() { return this.connectedServiceCount > 0; },
    get activeServices() { return this.connectedServices.filter(s => s.status === 'active'); },
    get activeServiceCount() { return this.activeServices.length; },
    get inactiveServices() { return this.connectedServices.filter(s => s.status === 'inactive'); },
    get errorServices() { return this.connectedServices.filter(s => s.status === 'error'); },
    get errorServiceCount() { return this.errorServices.length; },
    get hasServiceErrors() { return this.errorServiceCount > 0; },
    
    // Service Types Analysis
    get servicesByType() {
      const types = {};
      this.connectedServices.forEach(service => {
        if (!types[service.type]) types[service.type] = [];
        types[service.type].push(service);
      });
      return types;
    },
    get apiServices() { return this.connectedServices.filter(s => s.type === 'api'); },
    get webhookServices() { return this.connectedServices.filter(s => s.type === 'webhook'); },
    get oauthServices() { return this.connectedServices.filter(s => s.type === 'oauth'); },
    get rssServices() { return this.connectedServices.filter(s => s.type === 'rss'); },
    get emailServices() { return this.connectedServices.filter(s => s.type === 'email'); },
    get socialServices() { return this.connectedServices.filter(s => s.type === 'social'); },
    
    // API Credentials
    get apiCredentials() { return normalizedIntegration.apiCredentials; },
    get apiCredentialCount() { return this.apiCredentials.length; },
    get hasApiCredentials() { return this.apiCredentialCount > 0; },
    get expiredCredentials() {
      return this.apiCredentials.filter(cred => {
        if (!cred.expiresAt) return false;
        return new Date(cred.expiresAt) < new Date();
      });
    },
    get expiredCredentialCount() { return this.expiredCredentials.length; },
    get hasExpiredCredentials() { return this.expiredCredentialCount > 0; },
    get soonToExpireCredentials() {
      const weekFromNow = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
      return this.apiCredentials.filter(cred => {
        if (!cred.expiresAt) return false;
        const expiryDate = new Date(cred.expiresAt);
        return expiryDate < weekFromNow && expiryDate > new Date();
      });
    },
    get credentialsNeedAttention() {
      return this.hasExpiredCredentials || this.soonToExpireCredentials.length > 0;
    },
    
    // Synchronization Status
    get syncStatus() { return normalizedIntegration.syncStatus; },
    get syncStatusCount() { return this.syncStatus.length; },
    get activeSyncs() { return this.syncStatus.filter(s => s.status === 'active'); },
    get failedSyncs() { return this.syncStatus.filter(s => s.status === 'failed'); },
    get pendingSyncs() { return this.syncStatus.filter(s => s.status === 'pending'); },
    get activeSyncCount() { return this.activeSyncs.length; },
    get failedSyncCount() { return this.failedSyncs.length; },
    get hasSyncFailures() { return this.failedSyncCount > 0; },
    get lastSyncTime() {
      const syncTimes = this.syncStatus
        .map(s => s.lastSync)
        .filter(Boolean)
        .sort((a, b) => new Date(b) - new Date(a));
      return syncTimes.length > 0 ? syncTimes[0] : null;
    },
    get hasRecentSync() {
      if (!this.lastSyncTime) return false;
      const hourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return new Date(this.lastSyncTime) > hourAgo;
    },
    
    // Webhook Management
    get webhookConfigurations() { return normalizedIntegration.webhookConfigurations; },
    get webhookCount() { return this.webhookConfigurations.length; },
    get hasWebhooks() { return this.webhookCount > 0; },
    get activeWebhooks() { return this.webhookConfigurations.filter(w => w.enabled); },
    get activeWebhookCount() { return this.activeWebhooks.length; },
    get webhookErrors() {
      return this.webhookConfigurations.filter(w => w.lastError && w.errorCount > 0);
    },
    get webhookErrorCount() { return this.webhookErrors.length; },
    get hasWebhookErrors() { return this.webhookErrorCount > 0; },
    
    // Data Processing
    get dataMappings() { return normalizedIntegration.dataMappings; },
    get transformationRules() { return normalizedIntegration.transformationRules; },
    get dataMappingCount() { return this.dataMappings.length; },
    get transformationRuleCount() { return this.transformationRules.length; },
    get hasDataMappings() { return this.dataMappingCount > 0; },
    get hasTransformationRules() { return this.transformationRuleCount > 0; },
    get activeMappings() { return this.dataMappings.filter(m => m.enabled); },
    get activeTransformations() { return this.transformationRules.filter(r => r.enabled); },
    
    // Health Monitoring
    get integrationHealth() { return normalizedIntegration.integrationHealth; },
    get errorHandling() { return normalizedIntegration.errorHandling; },
    get healthCheckCount() { return this.integrationHealth.length; },
    get healthyIntegrations() {
      return this.integrationHealth.filter(h => h.status === 'healthy');
    },
    get unhealthyIntegrations() {
      return this.integrationHealth.filter(h => h.status === 'unhealthy');
    },
    get healthyIntegrationCount() { return this.healthyIntegrations.length; },
    get unhealthyIntegrationCount() { return this.unhealthyIntegrations.length; },
    get overallHealthScore() {
      if (this.healthCheckCount === 0) return 0;
      return (this.healthyIntegrationCount / this.healthCheckCount) * 100;
    },
    get healthRating() {
      const score = this.overallHealthScore;
      if (score >= 95) return 'excellent';
      if (score >= 85) return 'good';
      if (score >= 70) return 'average';
      if (score >= 50) return 'poor';
      return 'critical';
    },
    
    // Rate Limiting & Quotas
    get rateLimitStatus() { return normalizedIntegration.rateLimitStatus; },
    get quotaUsage() { return normalizedIntegration.quotaUsage; },
    get rateLimitCount() { return this.rateLimitStatus.length; },
    get quotaCount() { return this.quotaUsage.length; },
    get rateLimitedServices() {
      return this.rateLimitStatus.filter(r => r.isLimited);
    },
    get rateLimitedServiceCount() { return this.rateLimitedServices.length; },
    get hasRateLimits() { return this.rateLimitedServiceCount > 0; },
    get quotaExceededServices() {
      return this.quotaUsage.filter(q => q.usage >= q.limit);
    },
    get quotaExceededCount() { return this.quotaExceededServices.length; },
    get hasQuotaExceeded() { return this.quotaExceededCount > 0; },
    get nearQuotaLimit() {
      return this.quotaUsage.filter(q => (q.usage / q.limit) >= 0.9);
    },
    get nearQuotaLimitCount() { return this.nearQuotaLimit.length; },
    
    // Overall Integration Score
    get overallIntegrationScore() {
      let score = 100;
      
      // Service health (0-30 points)
      if (this.hasServiceErrors) {
        const errorRate = (this.errorServiceCount / this.connectedServiceCount) * 100;
        score -= Math.min(30, errorRate * 0.3);
      }
      
      // Sync health (0-25 points)
      if (this.hasSyncFailures) {
        const failureRate = (this.failedSyncCount / this.syncStatusCount) * 100;
        score -= Math.min(25, failureRate * 0.25);
      }
      
      // Credential management (0-20 points)
      if (this.credentialsNeedAttention) {
        score -= Math.min(20, (this.expiredCredentialCount + this.soonToExpireCredentials.length) * 5);
      }
      
      // Rate limiting impact (0-15 points)
      if (this.hasRateLimits) {
        score -= Math.min(15, this.rateLimitedServiceCount * 3);
      }
      
      // Quota management (0-10 points)
      if (this.hasQuotaExceeded) {
        score -= Math.min(10, this.quotaExceededCount * 5);
      }
      
      return Math.max(0, Math.min(100, Math.round(score)));
    },
    get integrationRating() {
      const score = this.overallIntegrationScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    get needsAttention() {
      return this.hasServiceErrors || this.hasSyncFailures || 
             this.credentialsNeedAttention || this.hasWebhookErrors;
    },
    
    // Timestamps
    get createdAt() { return normalizedIntegration.createdAt; },
    get updatedAt() { return normalizedIntegration.updatedAt; },
    get metadata() { return normalizedIntegration.metadata; },
    
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
    get isStale() { return this.daysSinceUpdate > 7; },
    
    // Validation
    get isValid() { return !!(this.id && this.userId); },
    get isComplete() { return this.isValid && this.hasConnectedServices; },
    get isOperational() { return this.isComplete && this.overallIntegrationScore >= 60; },
    
    // Utility Methods
    getServiceById(serviceId) {
      return this.connectedServices.find(service => service.id === serviceId);
    },
    getServicesByType(serviceType) {
      return this.connectedServices.filter(service => service.type === serviceType);
    },
    getCredentialByService(serviceId) {
      return this.apiCredentials.find(cred => cred.serviceId === serviceId);
    },
    getSyncStatusByService(serviceId) {
      return this.syncStatus.find(sync => sync.serviceId === serviceId);
    },
    getWebhookByService(serviceId) {
      return this.webhookConfigurations.find(webhook => webhook.serviceId === serviceId);
    },
    getHealthByService(serviceId) {
      return this.integrationHealth.find(health => health.serviceId === serviceId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        connectedServiceCount: this.connectedServiceCount,
        activeServiceCount: this.activeServiceCount,
        errorServiceCount: this.errorServiceCount,
        overallIntegrationScore: this.overallIntegrationScore,
        integrationRating: this.integrationRating,
        needsAttention: this.needsAttention,
        healthRating: this.healthRating,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isOperational: this.isOperational
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        userId: this.userId,
        connectedServices: this.connectedServices,
        apiCredentials: this.apiCredentials,
        syncStatus: this.syncStatus,
        webhookConfigurations: this.webhookConfigurations,
        dataMappings: this.dataMappings,
        transformationRules: this.transformationRules,
        integrationHealth: this.integrationHealth,
        errorHandling: this.errorHandling,
        rateLimitStatus: this.rateLimitStatus,
        quotaUsage: this.quotaUsage,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        metadata: this.metadata
      };
    }
  };
}

/**
 * Create empty integration helper for null/undefined data
 * @returns {Object} Empty integration helper with safe defaults
 */
function createEmptyIntegrationHelper() {
  return {
    get id() { return null; },
    get userId() { return null; },
    get connectedServices() { return []; },
    get connectedServiceCount() { return 0; },
    get hasConnectedServices() { return false; },
    get activeServiceCount() { return 0; },
    get errorServiceCount() { return 0; },
    get hasServiceErrors() { return false; },
    get apiCredentials() { return []; },
    get hasExpiredCredentials() { return false; },
    get syncStatus() { return []; },
    get hasSyncFailures() { return false; },
    get webhookConfigurations() { return []; },
    get hasWebhookErrors() { return false; },
    get overallHealthScore() { return 0; },
    get healthRating() { return 'critical'; },
    get overallIntegrationScore() { return 0; },
    get integrationRating() { return 'critical'; },
    get needsAttention() { return false; },
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    getServiceById(serviceId) { return null; },
    getServicesByType(serviceType) { return []; },
    get debugInfo() { return { id: null, isValid: false }; },
    toJSON() { return { id: null, isNew: true }; }
  };
}

/**
 * CRUD Operations for Integrations
 */

export async function createIntegration(integrationData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create integrations');
    }

    const newIntegration = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...integrationData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    if (browser && liveStore) {
      await liveStore.integrations.create(newIntegration);
    }

    integrationStore.update(integrations => [...integrations, newIntegration]);
    log(`[Integration] Created integration: ${newIntegration.id}`, 'info');
    return getIntegrationData(newIntegration);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Integration] Error creating integration: ${errorMessage}`, 'error');
    throw error;
  }
}

export async function connectService(integrationId, serviceData) {
  try {
    const service = {
      id: crypto.randomUUID(),
      ...serviceData,
      connectedAt: new Date().toISOString(),
      status: 'active'
    };

    // Update integration with new service
    integrationStore.update(integrations => 
      integrations.map(integration => 
        integration.id === integrationId 
          ? { 
              ...integration, 
              connectedServices: [...(integration.connectedServices || []), service],
              updatedAt: new Date().toISOString()
            }
          : integration
      )
    );

    log(`[Integration] Connected service: ${service.name}`, 'info');
    return service;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Integration] Error connecting service: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: integrationStore,
  getIntegrationData,
  createIntegration,
  connectService
}; 