/**
 * Email & Communication Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Email & communication business object for delivery and campaign management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Email template types
 * @typedef {'digest' | 'welcome' | 'notification' | 'marketing' | 'transactional' | 'system'} TemplateType
 */

/**
 * Delivery status
 * @typedef {'pending' | 'sent' | 'delivered' | 'bounced' | 'failed' | 'opened' | 'clicked'} DeliveryStatus
 */

/**
 * Enhanced Email & Communication object with comprehensive fields
 * @typedef {Object} CommunicationData
 * @property {string} id - Communication instance identifier
 * @property {string} userId - User identifier
 * @property {Object[]} emailTemplates - Email template configurations
 * @property {Object[]} deliverySettings - Email delivery configurations
 * @property {Object[]} subscriberLists - Subscriber list management
 * @property {Object[]} segmentationRules - Subscriber segmentation rules
 * @property {Object[]} campaignPerformance - Campaign performance metrics
 * @property {Object} deliverabilityMetrics - Email deliverability tracking
 * @property {Object} bounceHandling - Bounce management configuration
 * @property {Object} unsubscribeManagement - Unsubscribe handling
 * @property {Object[]} personalizationRules - Email personalization rules
 * @property {Object[]} abTesting - A/B testing configurations
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 */

/** @type {import('svelte/store').Writable<CommunicationData[]>} */
export const communicationStore = writable([]);

/**
 * Normalize communication data from any source to consistent format
 * @param {Object} rawCommunicationData - Raw communication data
 * @returns {Object|null} Normalized communication data
 */
function normalizeCommunicationData(rawCommunicationData) {
  if (!rawCommunicationData || typeof rawCommunicationData !== 'object' || !rawCommunicationData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawCommunicationData.id,
    userId: rawCommunicationData.userId || rawCommunicationData.user_id || null,
    
    // Templates & Content
    emailTemplates: Array.isArray(rawCommunicationData.emailTemplates) ? rawCommunicationData.emailTemplates :
                    Array.isArray(rawCommunicationData.email_templates) ? rawCommunicationData.email_templates : [],
    deliverySettings: Array.isArray(rawCommunicationData.deliverySettings) ? rawCommunicationData.deliverySettings :
                      Array.isArray(rawCommunicationData.delivery_settings) ? rawCommunicationData.delivery_settings : [],
    
    // Subscriber Management
    subscriberLists: Array.isArray(rawCommunicationData.subscriberLists) ? rawCommunicationData.subscriberLists :
                     Array.isArray(rawCommunicationData.subscriber_lists) ? rawCommunicationData.subscriber_lists : [],
    segmentationRules: Array.isArray(rawCommunicationData.segmentationRules) ? rawCommunicationData.segmentationRules :
                       Array.isArray(rawCommunicationData.segmentation_rules) ? rawCommunicationData.segmentation_rules : [],
    
    // Performance & Analytics
    campaignPerformance: Array.isArray(rawCommunicationData.campaignPerformance) ? rawCommunicationData.campaignPerformance :
                         Array.isArray(rawCommunicationData.campaign_performance) ? rawCommunicationData.campaign_performance : [],
    deliverabilityMetrics: rawCommunicationData.deliverabilityMetrics || rawCommunicationData.deliverability_metrics || {
      deliveryRate: 0,
      bounceRate: 0,
      openRate: 0,
      clickRate: 0,
      unsubscribeRate: 0,
      spamComplaintRate: 0
    },
    
    // Management & Handling
    bounceHandling: rawCommunicationData.bounceHandling || rawCommunicationData.bounce_handling || {
      softBounceRetries: 3,
      hardBounceActions: [],
      suppressionLists: [],
      cleanupRules: []
    },
    unsubscribeManagement: rawCommunicationData.unsubscribeManagement || rawCommunicationData.unsubscribe_management || {
      automaticProcessing: true,
      confirmationRequired: false,
      gracePeriod: 24,
      resubscribeAllowed: true
    },
    
    // Personalization & Testing
    personalizationRules: Array.isArray(rawCommunicationData.personalizationRules) ? rawCommunicationData.personalizationRules :
                          Array.isArray(rawCommunicationData.personalization_rules) ? rawCommunicationData.personalization_rules : [],
    abTesting: Array.isArray(rawCommunicationData.abTesting) ? rawCommunicationData.abTesting :
               Array.isArray(rawCommunicationData.ab_testing) ? rawCommunicationData.ab_testing : [],
    
    // Timestamps
    createdAt: rawCommunicationData.createdAt || rawCommunicationData.created_at || new Date().toISOString(),
    updatedAt: rawCommunicationData.updatedAt || rawCommunicationData.updated_at || new Date().toISOString(),
    
    // Metadata
    metadata: rawCommunicationData.metadata || {}
  };
}

/**
 * Get comprehensive communication data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} communication - Raw communication data
 * @returns {Object} Communication helper with getters and methods
 */
export function getCommunicationData(communication) {
  const normalizedCommunication = normalizeCommunicationData(communication);
  
  if (!normalizedCommunication) {
    return createEmptyCommunicationHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedCommunication.id; },
    get userId() { return normalizedCommunication.userId; },
    
    // Email Templates
    get emailTemplates() { return normalizedCommunication.emailTemplates; },
    get emailTemplateCount() { return this.emailTemplates.length; },
    get hasEmailTemplates() { return this.emailTemplateCount > 0; },
    get activeTemplates() { return this.emailTemplates.filter(t => t.active); },
    get activeTemplateCount() { return this.activeTemplates.length; },
    get templatesByType() {
      const types = {};
      this.emailTemplates.forEach(template => {
        if (!types[template.type]) types[template.type] = [];
        types[template.type].push(template);
      });
      return types;
    },
    get digestTemplates() { return this.emailTemplates.filter(t => t.type === 'digest'); },
    get welcomeTemplates() { return this.emailTemplates.filter(t => t.type === 'welcome'); },
    get notificationTemplates() { return this.emailTemplates.filter(t => t.type === 'notification'); },
    get marketingTemplates() { return this.emailTemplates.filter(t => t.type === 'marketing'); },
    get transactionalTemplates() { return this.emailTemplates.filter(t => t.type === 'transactional'); },
    
    // Delivery Settings
    get deliverySettings() { return normalizedCommunication.deliverySettings; },
    get deliverySettingCount() { return this.deliverySettings.length; },
    get hasDeliverySettings() { return this.deliverySettingCount > 0; },
    get activeDeliverySettings() { return this.deliverySettings.filter(s => s.enabled); },
    get primaryDeliverySetting() {
      return this.deliverySettings.find(s => s.isPrimary) || this.deliverySettings[0] || null;
    },
    get hasValidDeliverySettings() {
      return this.activeDeliverySettings.some(s => s.validated && !s.hasErrors);
    },
    
    // Subscriber Lists
    get subscriberLists() { return normalizedCommunication.subscriberLists; },
    get subscriberListCount() { return this.subscriberLists.length; },
    get hasSubscriberLists() { return this.subscriberListCount > 0; },
    get activeSubscriberLists() { return this.subscriberLists.filter(l => l.active); },
    get totalSubscribers() {
      return this.subscriberLists.reduce((total, list) => total + (list.subscriberCount || 0), 0);
    },
    get activeSubscribers() {
      return this.activeSubscriberLists.reduce((total, list) => total + (list.subscriberCount || 0), 0);
    },
    get largestList() {
      return this.subscriberLists.reduce((largest, list) => 
        (list.subscriberCount || 0) > (largest?.subscriberCount || 0) ? list : largest, null
      );
    },
    
    // Segmentation
    get segmentationRules() { return normalizedCommunication.segmentationRules; },
    get segmentationRuleCount() { return this.segmentationRules.length; },
    get hasSegmentationRules() { return this.segmentationRuleCount > 0; },
    get activeSegmentationRules() { return this.segmentationRules.filter(r => r.enabled); },
    get segmentCount() {
      return this.segmentationRules.reduce((total, rule) => total + (rule.segmentCount || 0), 0);
    },
    get averageSegmentSize() {
      return this.segmentCount > 0 ? Math.round(this.totalSubscribers / this.segmentCount) : 0;
    },
    
    // Campaign Performance
    get campaignPerformance() { return normalizedCommunication.campaignPerformance; },
    get campaignCount() { return this.campaignPerformance.length; },
    get hasCampaigns() { return this.campaignCount > 0; },
    get recentCampaigns() {
      const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
      return this.campaignPerformance.filter(c => new Date(c.sentAt) > thirtyDaysAgo);
    },
    get recentCampaignCount() { return this.recentCampaigns.length; },
    get averageCampaignOpenRate() {
      if (this.campaignCount === 0) return 0;
      const totalOpenRate = this.campaignPerformance.reduce((sum, c) => sum + (c.openRate || 0), 0);
      return totalOpenRate / this.campaignCount;
    },
    get averageCampaignClickRate() {
      if (this.campaignCount === 0) return 0;
      const totalClickRate = this.campaignPerformance.reduce((sum, c) => sum + (c.clickRate || 0), 0);
      return totalClickRate / this.campaignCount;
    },
    get bestPerformingCampaign() {
      return this.campaignPerformance.reduce((best, campaign) => 
        (campaign.openRate || 0) > (best?.openRate || 0) ? campaign : best, null
      );
    },
    
    // Deliverability Metrics
    get deliverabilityMetrics() { return normalizedCommunication.deliverabilityMetrics; },
    get deliveryRate() { return this.deliverabilityMetrics.deliveryRate; },
    get bounceRate() { return this.deliverabilityMetrics.bounceRate; },
    get openRate() { return this.deliverabilityMetrics.openRate; },
    get clickRate() { return this.deliverabilityMetrics.clickRate; },
    get unsubscribeRate() { return this.deliverabilityMetrics.unsubscribeRate; },
    get spamComplaintRate() { return this.deliverabilityMetrics.spamComplaintRate; },
    get isDeliverabilityGood() { return this.deliveryRate >= 95 && this.bounceRate <= 5; },
    get isEngagementGood() { return this.openRate >= 20 && this.clickRate >= 2; },
    get hasDeliverabilityIssues() { 
      return this.bounceRate > 10 || this.spamComplaintRate > 0.5 || this.deliveryRate < 90;
    },
    
    // Bounce Handling
    get bounceHandling() { return normalizedCommunication.bounceHandling; },
    get unsubscribeManagement() { return normalizedCommunication.unsubscribeManagement; },
    get softBounceRetries() { return this.bounceHandling.softBounceRetries; },
    get hardBounceActions() { return this.bounceHandling.hardBounceActions; },
    get suppressionLists() { return this.bounceHandling.suppressionLists; },
    get automaticUnsubscribeProcessing() { return this.unsubscribeManagement.automaticProcessing; },
    get unsubscribeGracePeriod() { return this.unsubscribeManagement.gracePeriod; },
    get resubscribeAllowed() { return this.unsubscribeManagement.resubscribeAllowed; },
    
    // Personalization
    get personalizationRules() { return normalizedCommunication.personalizationRules; },
    get personalizationRuleCount() { return this.personalizationRules.length; },
    get hasPersonalizationRules() { return this.personalizationRuleCount > 0; },
    get activePersonalizationRules() { return this.personalizationRules.filter(r => r.enabled); },
    get personalizationCoverage() {
      return this.totalSubscribers > 0 ? 
        (this.activePersonalizationRules.length / this.totalSubscribers) * 100 : 0;
    },
    
    // A/B Testing
    get abTesting() { return normalizedCommunication.abTesting; },
    get abTestCount() { return this.abTesting.length; },
    get hasAbTesting() { return this.abTestCount > 0; },
    get activeAbTests() { return this.abTesting.filter(t => t.status === 'active'); },
    get completedAbTests() { return this.abTesting.filter(t => t.status === 'completed'); },
    get activeAbTestCount() { return this.activeAbTests.length; },
    get abTestSuccessRate() {
      const completedTests = this.completedAbTests;
      if (completedTests.length === 0) return 0;
      const successfulTests = completedTests.filter(t => t.hasWinner);
      return (successfulTests.length / completedTests.length) * 100;
    },
    
    // Overall Communication Score
    get overallCommunicationScore() {
      let score = 100;
      
      // Deliverability (0-30 points)
      if (this.hasDeliverabilityIssues) {
        score -= Math.min(30, (this.bounceRate * 3) + (this.spamComplaintRate * 10));
      }
      
      // Engagement (0-25 points)
      if (!this.isEngagementGood) {
        const engagementPenalty = Math.max(0, 25 - this.openRate - (this.clickRate * 5));
        score -= Math.min(25, engagementPenalty);
      }
      
      // Template coverage (0-20 points)
      if (!this.hasEmailTemplates) {
        score -= 20;
      } else if (this.activeTemplateCount < 3) {
        score -= 10;
      }
      
      // Subscriber management (0-15 points)
      if (!this.hasSubscriberLists) {
        score -= 15;
      } else if (this.totalSubscribers < 10) {
        score -= 10;
      }
      
      // Configuration (0-10 points)
      if (!this.hasValidDeliverySettings) {
        score -= 10;
      } else if (!this.hasSegmentationRules) {
        score -= 5;
      }
      
      return Math.max(0, Math.min(100, Math.round(score)));
    },
    get communicationRating() {
      const score = this.overallCommunicationScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    get needsAttention() {
      return this.hasDeliverabilityIssues || !this.hasValidDeliverySettings || 
             !this.hasEmailTemplates || this.totalSubscribers === 0;
    },
    
    // Timestamps
    get createdAt() { return normalizedCommunication.createdAt; },
    get updatedAt() { return normalizedCommunication.updatedAt; },
    get metadata() { return normalizedCommunication.metadata; },
    
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
    get isComplete() { return this.isValid && this.hasEmailTemplates && this.hasValidDeliverySettings; },
    get isOperational() { return this.isComplete && this.overallCommunicationScore >= 60; },
    
    // Utility Methods
    getTemplateById(templateId) {
      return this.emailTemplates.find(template => template.id === templateId);
    },
    getTemplatesByType(templateType) {
      return this.emailTemplates.filter(template => template.type === templateType);
    },
    getSubscriberListById(listId) {
      return this.subscriberLists.find(list => list.id === listId);
    },
    getCampaignById(campaignId) {
      return this.campaignPerformance.find(campaign => campaign.id === campaignId);
    },
    getAbTestById(testId) {
      return this.abTesting.find(test => test.id === testId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        emailTemplateCount: this.emailTemplateCount,
        totalSubscribers: this.totalSubscribers,
        deliveryRate: this.deliveryRate,
        openRate: this.openRate,
        overallCommunicationScore: this.overallCommunicationScore,
        communicationRating: this.communicationRating,
        needsAttention: this.needsAttention,
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
        emailTemplates: this.emailTemplates,
        deliverySettings: this.deliverySettings,
        subscriberLists: this.subscriberLists,
        segmentationRules: this.segmentationRules,
        campaignPerformance: this.campaignPerformance,
        deliverabilityMetrics: this.deliverabilityMetrics,
        bounceHandling: this.bounceHandling,
        unsubscribeManagement: this.unsubscribeManagement,
        personalizationRules: this.personalizationRules,
        abTesting: this.abTesting,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        metadata: this.metadata
      };
    }
  };
}

/**
 * Create empty communication helper for null/undefined data
 * @returns {Object} Empty communication helper with safe defaults
 */
function createEmptyCommunicationHelper() {
  return {
    get id() { return null; },
    get userId() { return null; },
    get emailTemplates() { return []; },
    get emailTemplateCount() { return 0; },
    get hasEmailTemplates() { return false; },
    get deliverySettings() { return []; },
    get hasValidDeliverySettings() { return false; },
    get subscriberLists() { return []; },
    get totalSubscribers() { return 0; },
    get campaignPerformance() { return []; },
    get deliverabilityMetrics() { return { deliveryRate: 0, bounceRate: 0, openRate: 0, clickRate: 0 }; },
    get deliveryRate() { return 0; },
    get openRate() { return 0; },
    get overallCommunicationScore() { return 0; },
    get communicationRating() { return 'critical'; },
    get needsAttention() { return true; },
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    getTemplateById(templateId) { return null; },
    getSubscriberListById(listId) { return null; },
    get debugInfo() { return { id: null, isValid: false }; },
    toJSON() { return { id: null, isNew: true }; }
  };
}

/**
 * CRUD Operations for Communication
 */

export async function createCommunicationConfig(configData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create communication config');
    }

    const newConfig = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...configData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    if (browser && liveStore) {
      await liveStore.communication.create(newConfig);
    }

    communicationStore.update(configs => [...configs, newConfig]);
    log(`[Communication] Created communication config: ${newConfig.id}`, 'info');
    return getCommunicationData(newConfig);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Communication] Error creating config: ${errorMessage}`, 'error');
    throw error;
  }
}

export async function createEmailTemplate(templateData) {
  try {
    const template = {
      id: crypto.randomUUID(),
      ...templateData,
      createdAt: new Date().toISOString(),
      active: templateData.active !== false
    };

    log(`[Communication] Created email template: ${template.name}`, 'info');
    return template;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Communication] Error creating template: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: communicationStore,
  getCommunicationData,
  createCommunicationConfig,
  createEmailTemplate
}; 