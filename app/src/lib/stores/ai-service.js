/**
 * AI Service Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview AI Service business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * AI provider types
 * @typedef {'openai' | 'anthropic' | 'huggingface' | 'google' | 'azure' | 'aws' | 'local'} AIProvider
 */

/**
 * AI task types
 * @typedef {'summarization' | 'classification' | 'sentiment' | 'extraction' | 'generation' | 'translation'} AITaskType
 */

/**
 * AI service status
 * @typedef {'active' | 'inactive' | 'error' | 'rate_limited' | 'quota_exceeded'} AIServiceStatus
 */

/**
 * Provider configuration object
 * @typedef {Object} ProviderConfig
 * @property {string} apiKey - API key for the provider
 * @property {string} endpoint - API endpoint URL
 * @property {Object} models - Available models configuration
 * @property {Object} rateLimits - Rate limiting settings
 * @property {Object} pricing - Pricing information
 * @property {number} priority - Provider priority (1-10)
 */

/**
 * Usage tracking data
 * @typedef {Object} UsageTracking
 * @property {number} tokensUsed - Total tokens consumed
 * @property {number} requestCount - Total requests made
 * @property {number} totalCost - Total cost incurred
 * @property {Object} dailyUsage - Daily usage breakdown
 * @property {Object} monthlyUsage - Monthly usage breakdown
 */

/**
 * Enhanced AI Service object with comprehensive fields
 * @typedef {Object} AIService
 * @property {string} id - Service identifier
 * @property {string} name - Service name
 * @property {string} description - Service description
 * @property {AIServiceStatus} status - Current status
 * @property {AIProvider} activeProvider - Currently active provider
 * @property {AIProvider[]} fallbackProviders - Fallback provider chain
 * @property {Object.<AIProvider, ProviderConfig>} providerConfigs - Provider configurations
 * @property {UsageTracking} usageTracking - Usage statistics
 * @property {Object.<AITaskType, Object>} taskConfigurations - Task-specific settings
 * @property {Object} modelSettings - Model configuration settings
 * @property {Object} cacheSettings - Caching configuration
 * @property {number} cacheHitRate - Cache hit rate percentage
 * @property {Object[]} errorRates - Error rate tracking
 * @property {Object[]} performanceMetrics - Performance monitoring
 * @property {Object} budgetLimits - Budget and cost controls
 * @property {Object[]} costAlerts - Cost alert configurations
 * @property {Object} quotaManagement - Quota management settings
 * @property {Object[]} processingQueue - Current processing queue
 * @property {Object} batchSettings - Batch processing configuration
 * @property {string} createdBy - Creator user ID
 * @property {string} managedBy - Manager user ID
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<AIService[]>} */
export const aiServicesStore = writable([]);

/**
 * Normalize AI service data from any source to consistent format
 * @param {Object} rawAIServiceData - Raw AI service data
 * @returns {Object|null} Normalized AI service data
 */
function normalizeAIServiceData(rawAIServiceData) {
  if (!rawAIServiceData || typeof rawAIServiceData !== 'object' || !rawAIServiceData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawAIServiceData.id,
    name: rawAIServiceData.name || 'AI Service',
    description: rawAIServiceData.description || '',
    status: rawAIServiceData.status || 'active',
    
    // Provider Management
    activeProvider: rawAIServiceData.activeProvider || rawAIServiceData.active_provider || 'openai',
    fallbackProviders: Array.isArray(rawAIServiceData.fallbackProviders) ? rawAIServiceData.fallbackProviders :
                       Array.isArray(rawAIServiceData.fallback_providers) ? rawAIServiceData.fallback_providers : [],
    providerConfigs: rawAIServiceData.providerConfigs || rawAIServiceData.provider_configs || {},
    
    // Usage Tracking
    usageTracking: rawAIServiceData.usageTracking || rawAIServiceData.usage_tracking || {
      tokensUsed: 0,
      requestCount: 0,
      totalCost: 0,
      dailyUsage: {},
      monthlyUsage: {}
    },
    
    // Task Configuration
    taskConfigurations: rawAIServiceData.taskConfigurations || rawAIServiceData.task_configurations || {},
    modelSettings: rawAIServiceData.modelSettings || rawAIServiceData.model_settings || {
      temperature: 0.7,
      maxTokens: 1000,
      topP: 1.0,
      frequencyPenalty: 0,
      presencePenalty: 0
    },
    
    // Caching
    cacheSettings: rawAIServiceData.cacheSettings || rawAIServiceData.cache_settings || {
      enabled: true,
      ttl: 3600,
      maxSize: 1000
    },
    cacheHitRate: typeof rawAIServiceData.cacheHitRate === 'number' ? rawAIServiceData.cacheHitRate :
                  typeof rawAIServiceData.cache_hit_rate === 'number' ? rawAIServiceData.cache_hit_rate : 0,
    
    // Performance & Monitoring
    errorRates: Array.isArray(rawAIServiceData.errorRates) ? rawAIServiceData.errorRates :
                Array.isArray(rawAIServiceData.error_rates) ? rawAIServiceData.error_rates : [],
    performanceMetrics: Array.isArray(rawAIServiceData.performanceMetrics) ? rawAIServiceData.performanceMetrics :
                        Array.isArray(rawAIServiceData.performance_metrics) ? rawAIServiceData.performance_metrics : [],
    
    // Budget & Cost Management
    budgetLimits: rawAIServiceData.budgetLimits || rawAIServiceData.budget_limits || {
      daily: 100,
      monthly: 1000,
      yearly: 10000
    },
    costAlerts: Array.isArray(rawAIServiceData.costAlerts) ? rawAIServiceData.costAlerts :
                Array.isArray(rawAIServiceData.cost_alerts) ? rawAIServiceData.cost_alerts : [],
    quotaManagement: rawAIServiceData.quotaManagement || rawAIServiceData.quota_management || {
      enabled: true,
      softLimit: 80,
      hardLimit: 95
    },
    
    // Processing Queue
    processingQueue: Array.isArray(rawAIServiceData.processingQueue) ? rawAIServiceData.processingQueue :
                     Array.isArray(rawAIServiceData.processing_queue) ? rawAIServiceData.processing_queue : [],
    batchSettings: rawAIServiceData.batchSettings || rawAIServiceData.batch_settings || {
      enabled: false,
      batchSize: 10,
      maxWaitTime: 5000
    },
    
    // Management
    createdBy: rawAIServiceData.createdBy || rawAIServiceData.created_by || null,
    managedBy: rawAIServiceData.managedBy || rawAIServiceData.managed_by || null,
    
    // Timestamps
    createdAt: rawAIServiceData.createdAt || rawAIServiceData.created_at || new Date().toISOString(),
    updatedAt: rawAIServiceData.updatedAt || rawAIServiceData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawAIServiceData.wpSynced || rawAIServiceData.wp_synced || false,
    lastWpSync: rawAIServiceData.lastWpSync || rawAIServiceData.last_wp_sync || null
  };
}

/**
 * Get comprehensive AI service data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} aiService - Raw AI service data
 * @returns {Object} AI service helper with getters and methods
 */
export function getAIServiceData(aiService) {
  const normalizedAIService = normalizeAIServiceData(aiService);
  
  if (!normalizedAIService) {
    return createEmptyAIServiceHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedAIService.id; },
    get name() { return normalizedAIService.name; },
    get description() { return normalizedAIService.description; },
    get status() { return normalizedAIService.status; },
    
    // Status Checks
    get isActive() { return this.status === 'active'; },
    get isInactive() { return this.status === 'inactive'; },
    get hasError() { return this.status === 'error'; },
    get isRateLimited() { return this.status === 'rate_limited'; },
    get isQuotaExceeded() { return this.status === 'quota_exceeded'; },
    get isOperational() { return this.isActive; },
    
    // Provider Management
    get activeProvider() { return normalizedAIService.activeProvider; },
    get fallbackProviders() { return normalizedAIService.fallbackProviders; },
    get providerConfigs() { return normalizedAIService.providerConfigs; },
    get hasFallbacks() { return this.fallbackProviders.length > 0; },
    get fallbackCount() { return this.fallbackProviders.length; },
    get configuredProviders() { return Object.keys(this.providerConfigs); },
    get providerCount() { return this.configuredProviders.length; },
    
    // Provider Status
    get activeProviderConfig() { 
      return this.providerConfigs[this.activeProvider] || null;
    },
    get isActiveProviderConfigured() { 
      return !!this.activeProviderConfig;
    },
    get availableModels() {
      return this.activeProviderConfig?.models || {};
    },
    get hasMultipleProviders() { return this.providerCount > 1; },
    
    // Usage Tracking
    get usageTracking() { return normalizedAIService.usageTracking; },
    get tokensUsed() { return this.usageTracking.tokensUsed; },
    get requestCount() { return this.usageTracking.requestCount; },
    get totalCost() { return this.usageTracking.totalCost; },
    get dailyUsage() { return this.usageTracking.dailyUsage; },
    get monthlyUsage() { return this.usageTracking.monthlyUsage; },
    
    // Usage Analysis
    get averageCostPerRequest() {
      return this.requestCount > 0 ? this.totalCost / this.requestCount : 0;
    },
    get averageTokensPerRequest() {
      return this.requestCount > 0 ? this.tokensUsed / this.requestCount : 0;
    },
    get todayUsage() {
      const today = new Date().toISOString().split('T')[0];
      return this.dailyUsage[today] || { tokens: 0, requests: 0, cost: 0 };
    },
    get thisMonthUsage() {
      const thisMonth = new Date().toISOString().substring(0, 7);
      return this.monthlyUsage[thisMonth] || { tokens: 0, requests: 0, cost: 0 };
    },
    
    // Task Configuration
    get taskConfigurations() { return normalizedAIService.taskConfigurations; },
    get modelSettings() { return normalizedAIService.modelSettings; },
    get configuredTasks() { return Object.keys(this.taskConfigurations); },
    get taskCount() { return this.configuredTasks.length; },
    get hasTaskConfigurations() { return this.taskCount > 0; },
    
    // Model Settings
    get temperature() { return this.modelSettings.temperature; },
    get maxTokens() { return this.modelSettings.maxTokens; },
    get topP() { return this.modelSettings.topP; },
    get frequencyPenalty() { return this.modelSettings.frequencyPenalty; },
    get presencePenalty() { return this.modelSettings.presencePenalty; },
    
    // Caching
    get cacheSettings() { return normalizedAIService.cacheSettings; },
    get cacheHitRate() { return normalizedAIService.cacheHitRate; },
    get isCacheEnabled() { return this.cacheSettings.enabled; },
    get cacheTTL() { return this.cacheSettings.ttl; },
    get cacheMaxSize() { return this.cacheSettings.maxSize; },
    get cacheEfficiency() {
      if (this.cacheHitRate >= 80) return 'excellent';
      if (this.cacheHitRate >= 60) return 'good';
      if (this.cacheHitRate >= 40) return 'average';
      if (this.cacheHitRate >= 20) return 'poor';
      return 'very-poor';
    },
    
    // Performance & Monitoring
    get errorRates() { return normalizedAIService.errorRates; },
    get performanceMetrics() { return normalizedAIService.performanceMetrics; },
    get hasErrors() { return this.errorRates.length > 0; },
    get recentErrorRate() {
      const recent = this.errorRates.slice(-10);
      if (recent.length === 0) return 0;
      const totalErrors = recent.reduce((sum, rate) => sum + rate.errorCount, 0);
      const totalRequests = recent.reduce((sum, rate) => sum + rate.requestCount, 0);
      return totalRequests > 0 ? (totalErrors / totalRequests) * 100 : 0;
    },
    get averageResponseTime() {
      if (this.performanceMetrics.length === 0) return 0;
      const total = this.performanceMetrics.reduce((sum, metric) => sum + metric.responseTime, 0);
      return total / this.performanceMetrics.length;
    },
    
    // Performance Rating
    get performanceRating() {
      const errorRate = this.recentErrorRate;
      const responseTime = this.averageResponseTime;
      
      if (errorRate < 1 && responseTime < 1000) return 'excellent';
      if (errorRate < 5 && responseTime < 3000) return 'good';
      if (errorRate < 10 && responseTime < 5000) return 'average';
      if (errorRate < 20 && responseTime < 10000) return 'poor';
      return 'very-poor';
    },
    
    // Budget & Cost Management
    get budgetLimits() { return normalizedAIService.budgetLimits; },
    get costAlerts() { return normalizedAIService.costAlerts; },
    get quotaManagement() { return normalizedAIService.quotaManagement; },
    get dailyBudget() { return this.budgetLimits.daily; },
    get monthlyBudget() { return this.budgetLimits.monthly; },
    get yearlyBudget() { return this.budgetLimits.yearly; },
    
    // Budget Analysis
    get dailyBudgetUsed() {
      return (this.todayUsage.cost / this.dailyBudget) * 100;
    },
    get monthlyBudgetUsed() {
      return (this.thisMonthUsage.cost / this.monthlyBudget) * 100;
    },
    get isNearDailyLimit() { return this.dailyBudgetUsed > this.quotaManagement.softLimit; },
    get isNearMonthlyLimit() { return this.monthlyBudgetUsed > this.quotaManagement.softLimit; },
    get hasExceededDailyBudget() { return this.dailyBudgetUsed > this.quotaManagement.hardLimit; },
    get hasExceededMonthlyBudget() { return this.monthlyBudgetUsed > this.quotaManagement.hardLimit; },
    
    // Processing Queue
    get processingQueue() { return normalizedAIService.processingQueue; },
    get batchSettings() { return normalizedAIService.batchSettings; },
    get queueLength() { return this.processingQueue.length; },
    get hasQueuedItems() { return this.queueLength > 0; },
    get isBatchProcessingEnabled() { return this.batchSettings.enabled; },
    get batchSize() { return this.batchSettings.batchSize; },
    get maxWaitTime() { return this.batchSettings.maxWaitTime; },
    
    // Queue Analysis
    get queueStatus() {
      if (this.queueLength === 0) return 'empty';
      if (this.queueLength < 10) return 'light';
      if (this.queueLength < 50) return 'moderate';
      if (this.queueLength < 100) return 'heavy';
      return 'overloaded';
    },
    get averageQueueWaitTime() {
      if (this.processingQueue.length === 0) return 0;
      const now = new Date();
      const totalWait = this.processingQueue.reduce((sum, item) => {
        const queueTime = new Date(item.queuedAt);
        return sum + (now.getTime() - queueTime.getTime());
      }, 0);
      return totalWait / this.processingQueue.length;
    },
    
    // Management
    get createdBy() { return normalizedAIService.createdBy; },
    get managedBy() { return normalizedAIService.managedBy; },
    get hasManager() { return !!this.managedBy; },
    
    // Timestamps
    get createdAt() { return normalizedAIService.createdAt; },
    get updatedAt() { return normalizedAIService.updatedAt; },
    
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
    get wpSynced() { return normalizedAIService.wpSynced; },
    get lastWpSync() { return normalizedAIService.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.name && this.activeProvider);
    },
    get isComplete() {
      return this.isValid && this.isActiveProviderConfigured;
    },
    get isReadyForUse() {
      return this.isComplete && this.isOperational && !this.hasExceededDailyBudget;
    },
    get isOptimallyConfigured() {
      return this.isReadyForUse && this.hasFallbacks && this.isCacheEnabled;
    },
    
    // Utility Methods
    canUserManage(userId) {
      return this.createdBy === userId || this.managedBy === userId;
    },
    getTaskConfig(taskType) {
      return this.taskConfigurations[taskType] || null;
    },
    getProviderConfig(provider) {
      return this.providerConfigs[provider] || null;
    },
    hasTaskType(taskType) {
      return !!this.getTaskConfig(taskType);
    },
    
    // Cost Analysis Methods
    estimateCost(tokens, provider = null) {
      const targetProvider = provider || this.activeProvider;
      const config = this.getProviderConfig(targetProvider);
      if (!config || !config.pricing) return 0;
      
      const pricing = config.pricing;
      return (tokens / 1000) * (pricing.inputTokens || pricing.tokens || 0);
    },
    
    // Performance Methods
    getErrorRateForPeriod(hours = 24) {
      const cutoff = new Date(Date.now() - hours * 60 * 60 * 1000);
      const recentErrors = this.errorRates.filter(rate => new Date(rate.timestamp) > cutoff);
      
      if (recentErrors.length === 0) return 0;
      const totalErrors = recentErrors.reduce((sum, rate) => sum + rate.errorCount, 0);
      const totalRequests = recentErrors.reduce((sum, rate) => sum + rate.requestCount, 0);
      return totalRequests > 0 ? (totalErrors / totalRequests) * 100 : 0;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        name: this.name,
        status: this.status,
        activeProvider: this.activeProvider,
        providerCount: this.providerCount,
        requestCount: this.requestCount,
        totalCost: this.totalCost,
        queueLength: this.queueLength,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isReadyForUse: this.isReadyForUse,
        performanceRating: this.performanceRating,
        cacheEfficiency: this.cacheEfficiency
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        name: this.name,
        description: this.description,
        status: this.status,
        
        // Provider management
        activeProvider: this.activeProvider,
        fallbackProviders: this.fallbackProviders,
        providerConfigs: this.providerConfigs,
        
        // Usage tracking
        usageTracking: this.usageTracking,
        
        // Configuration
        taskConfigurations: this.taskConfigurations,
        modelSettings: this.modelSettings,
        
        // Caching
        cacheSettings: this.cacheSettings,
        cacheHitRate: this.cacheHitRate,
        
        // Performance
        errorRates: this.errorRates,
        performanceMetrics: this.performanceMetrics,
        
        // Budget
        budgetLimits: this.budgetLimits,
        costAlerts: this.costAlerts,
        quotaManagement: this.quotaManagement,
        
        // Processing
        processingQueue: this.processingQueue,
        batchSettings: this.batchSettings,
        
        // Management
        createdBy: this.createdBy,
        managedBy: this.managedBy,
        
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
 * Create empty AI service helper for null/undefined services
 * @returns {Object} Empty AI service helper with safe defaults
 */
function createEmptyAIServiceHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get name() { return 'New AI Service'; },
    get description() { return ''; },
    get status() { return 'inactive'; },
    
    // Status Checks
    get isActive() { return false; },
    get isInactive() { return true; },
    get hasError() { return false; },
    get isRateLimited() { return false; },
    get isQuotaExceeded() { return false; },
    get isOperational() { return false; },
    
    // Provider Management
    get activeProvider() { return 'openai'; },
    get fallbackProviders() { return []; },
    get providerConfigs() { return {}; },
    get hasFallbacks() { return false; },
    get fallbackCount() { return 0; },
    get configuredProviders() { return []; },
    get providerCount() { return 0; },
    get activeProviderConfig() { return null; },
    get isActiveProviderConfigured() { return false; },
    get availableModels() { return {}; },
    get hasMultipleProviders() { return false; },
    
    // Usage Tracking
    get usageTracking() { return { tokensUsed: 0, requestCount: 0, totalCost: 0, dailyUsage: {}, monthlyUsage: {} }; },
    get tokensUsed() { return 0; },
    get requestCount() { return 0; },
    get totalCost() { return 0; },
    get dailyUsage() { return {}; },
    get monthlyUsage() { return {}; },
    get averageCostPerRequest() { return 0; },
    get averageTokensPerRequest() { return 0; },
    get todayUsage() { return { tokens: 0, requests: 0, cost: 0 }; },
    get thisMonthUsage() { return { tokens: 0, requests: 0, cost: 0 }; },
    
    // Task Configuration
    get taskConfigurations() { return {}; },
    get modelSettings() { return { temperature: 0.7, maxTokens: 1000, topP: 1.0, frequencyPenalty: 0, presencePenalty: 0 }; },
    get configuredTasks() { return []; },
    get taskCount() { return 0; },
    get hasTaskConfigurations() { return false; },
    get temperature() { return 0.7; },
    get maxTokens() { return 1000; },
    get topP() { return 1.0; },
    get frequencyPenalty() { return 0; },
    get presencePenalty() { return 0; },
    
    // Caching
    get cacheSettings() { return { enabled: true, ttl: 3600, maxSize: 1000 }; },
    get cacheHitRate() { return 0; },
    get isCacheEnabled() { return true; },
    get cacheTTL() { return 3600; },
    get cacheMaxSize() { return 1000; },
    get cacheEfficiency() { return 'unrated'; },
    
    // Performance & Monitoring
    get errorRates() { return []; },
    get performanceMetrics() { return []; },
    get hasErrors() { return false; },
    get recentErrorRate() { return 0; },
    get averageResponseTime() { return 0; },
    get performanceRating() { return 'unrated'; },
    
    // Budget & Cost Management
    get budgetLimits() { return { daily: 100, monthly: 1000, yearly: 10000 }; },
    get costAlerts() { return []; },
    get quotaManagement() { return { enabled: true, softLimit: 80, hardLimit: 95 }; },
    get dailyBudget() { return 100; },
    get monthlyBudget() { return 1000; },
    get yearlyBudget() { return 10000; },
    get dailyBudgetUsed() { return 0; },
    get monthlyBudgetUsed() { return 0; },
    get isNearDailyLimit() { return false; },
    get isNearMonthlyLimit() { return false; },
    get hasExceededDailyBudget() { return false; },
    get hasExceededMonthlyBudget() { return false; },
    
    // Processing Queue
    get processingQueue() { return []; },
    get batchSettings() { return { enabled: false, batchSize: 10, maxWaitTime: 5000 }; },
    get queueLength() { return 0; },
    get hasQueuedItems() { return false; },
    get isBatchProcessingEnabled() { return false; },
    get batchSize() { return 10; },
    get maxWaitTime() { return 5000; },
    get queueStatus() { return 'empty'; },
    get averageQueueWaitTime() { return 0; },
    
    // Management
    get createdBy() { return null; },
    get managedBy() { return null; },
    get hasManager() { return false; },
    
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
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isReadyForUse() { return false; },
    get isOptimallyConfigured() { return false; },
    
    // Utility Methods
    canUserManage(userId) { return false; },
    getTaskConfig(taskType) { return null; },
    getProviderConfig(provider) { return null; },
    hasTaskType(taskType) { return false; },
    estimateCost(tokens, provider = null) { return 0; },
    getErrorRateForPeriod(hours = 24) { return 0; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        name: 'New AI Service',
        status: 'inactive',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        name: 'New AI Service',
        status: 'inactive',
        activeProvider: 'openai',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for AI Services
 */

/**
 * Create new AI service
 * @param {Object} serviceData - Initial service data
 * @returns {Promise<Object>} Created AI service
 */
export async function createAIService(serviceData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create AI service');
    }

    const newService = {
      id: crypto.randomUUID(),
      ...serviceData,
      createdBy: currentUser.id,
      status: 'inactive',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.aiServices.create(newService);
    }

    // Update local store
    aiServicesStore.update(services => [...services, newService]);

    log(`[AI Service] Created new AI service: ${newService.id}`, 'info');
    return getAIServiceData(newService);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Service] Error creating AI service: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing AI service
 * @param {string} serviceId - Service ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated AI service
 */
export async function updateAIService(serviceId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.aiServices.update(serviceId, updatedData);
    }

    // Update local store
    aiServicesStore.update(services => 
      services.map(service => 
        service.id === serviceId 
          ? { ...service, ...updatedData }
          : service
      )
    );

    log(`[AI Service] Updated AI service: ${serviceId}`, 'info');
    
    const updatedService = await getAIServiceById(serviceId);
    return updatedService;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Service] Error updating AI service: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete AI service
 * @param {string} serviceId - Service ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteAIService(serviceId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.aiServices.delete(serviceId);
    }

    // Update local store
    aiServicesStore.update(services => 
      services.filter(service => service.id !== serviceId)
    );

    log(`[AI Service] Deleted AI service: ${serviceId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Service] Error deleting AI service: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get AI service by ID
 * @param {string} serviceId - Service ID
 * @returns {Promise<Object|null>} AI service data or null
 */
export async function getAIServiceById(serviceId) {
  try {
    let service = null;

    // Try LiveStore first
    if (browser && liveStore) {
      service = await liveStore.aiServices.findById(serviceId);
    }

    // Fallback to local store
    if (!service) {
      const allServices = await new Promise(resolve => {
        aiServicesStore.subscribe(value => resolve(value))();
      });
      service = allServices.find(s => s.id === serviceId);
    }

    return service ? getAIServiceData(service) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Service] Error getting AI service by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get user's AI services
 * @returns {Promise<Object[]>} Array of user's AI service data objects
 */
export async function getUserAIServices() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return [];

    let services = [];

    // Try LiveStore first
    if (browser && liveStore) {
      services = await liveStore.aiServices.findMany({
        where: {
          OR: [
            { createdBy: currentUser.id },
            { managedBy: currentUser.id }
          ]
        }
      });
    }

    // Fallback to local store
    if (services.length === 0) {
      const allServices = await new Promise(resolve => {
        aiServicesStore.subscribe(value => resolve(value))();
      });
      services = allServices.filter(service => 
        service.createdBy === currentUser.id || 
        service.managedBy === currentUser.id
      );
    }

    return services.map(service => getAIServiceData(service));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Service] Error getting user AI services: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: aiServicesStore,
  getAIServiceData,
  createAIService,
  updateAIService,
  deleteAIService,
  getAIServiceById,
  getUserAIServices
}; 