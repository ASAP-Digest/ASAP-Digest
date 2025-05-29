/**
 * Usage Tracking Store
 * Follows getUserData() pattern with comprehensive getters
 * 
 * @fileoverview Usage Tracking business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/** @type {import('svelte/store').Writable<Object[]>} */
export const usageStore = writable([]);

/**
 * Get comprehensive usage data with computed properties
 * @param {Object} usage - Raw usage data
 * @returns {Object} Usage helper with getters and methods
 */
export function getUsageData(usage) {
  if (!usage || typeof usage !== 'object' || !usage.id) {
    return {
      get id() { return null; },
      get userId() { return null; },
      get eventType() { return 'feature_use'; },
      get featureUsed() { return ''; },
      get usageCount() { return 0; },
      get costAttribution() { return 0; },
      get isValid() { return false; },
      toJSON() { return { id: null, isNew: true }; }
    };
  }

  return {
    // Core Identity
    get id() { return usage.id; },
    get userId() { return usage.userId || usage.user_id; },
    get eventType() { return usage.eventType || usage.event_type || 'feature_use'; },
    get featureUsed() { return usage.featureUsed || usage.feature_used || ''; },
    get usageCount() { return usage.usageCount || usage.usage_count || 1; },
    get timestamp() { return usage.timestamp || new Date().toISOString(); },
    
    // Cost & Billing
    get costAttribution() { return usage.costAttribution || usage.cost_attribution || 0; },
    get billingCategory() { return usage.billingCategory || usage.billing_category || 'base_plan'; },
    get hasCost() { return this.costAttribution > 0; },
    
    // Event Type Analysis
    get isApiCall() { return this.eventType === 'api_call'; },
    get isFeatureUse() { return this.eventType === 'feature_use'; },
    get isAiProcessing() { return this.eventType === 'ai_processing'; },
    
    // Usage Analysis
    get isHighUsage() { return this.usageCount >= 100; },
    get isLowUsage() { return this.usageCount < 10; },
    get isSingleUse() { return this.usageCount === 1; },
    
    // Resource Consumption
    get resourceConsumption() { 
      return usage.resourceConsumption || usage.resource_consumption || {
        cpu: 0, memory: 0, storage: 0, bandwidth: 0, 
        apiCalls: 0, aiTokens: 0, databaseQueries: 0
      }; 
    },
    get aiTokensUsage() { return this.resourceConsumption.aiTokens || 0; },
    get apiCallsUsage() { return this.resourceConsumption.apiCalls || 0; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.timestamp);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60));
    },
    get isRecent() { return this.age <= 60; },
    
    // Validation
    get isValid() { return !!(this.id && this.userId && this.featureUsed); },
    get isComplete() { return this.isValid && this.eventType; },
    get isBillable() { return this.isComplete && this.costAttribution > 0; },
    
    // Utility Methods
    getResourceUsage(type) { return this.resourceConsumption[type] || 0; },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        userId: this.userId,
        eventType: this.eventType,
        featureUsed: this.featureUsed,
        usageCount: this.usageCount,
        timestamp: this.timestamp,
        costAttribution: this.costAttribution,
        billingCategory: this.billingCategory,
        resourceConsumption: this.resourceConsumption
      };
    }
  };
}

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
      timestamp: new Date().toISOString()
    };

    usageStore.update(records => [...records, newUsage]);
    log(`[Usage] Tracked: ${newUsage.featureUsed}`, 'info');
    
    return getUsageData(newUsage);
  } catch (error) {
    log(`[Usage] Error: ${error.message}`, 'error');
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
    const { limit = 100, eventType = null } = options;
    
    const allRecords = await new Promise(resolve => {
      usageStore.subscribe(value => resolve(value))();
    });
    
    const filtered = allRecords
      .filter(record => {
        if (record.userId !== userId) return false;
        if (eventType && record.eventType !== eventType) return false;
        return true;
      })
      .slice(0, limit);

    return filtered.map(record => getUsageData(record));
  } catch (error) {
    log(`[Usage] Error getting user usage: ${error.message}`, 'error');
    return [];
  }
}

export default {
  store: usageStore,
  getUsageData,
  trackUsage,
  getUserUsage
}; 