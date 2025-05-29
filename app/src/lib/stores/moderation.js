/**
 * Moderation Workflow Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Moderation Workflow business object management for content review processes
 * 
 * ====================================================================
 * COMPREHENSIVE MODERATION WORKFLOW DOCUMENTATION
 * ====================================================================
 * 
 * This moderation system manages the complete content review workflow
 * from queue management through final decisions, following the established
 * getUserData() pattern with 70+ computed getters.
 * 
 * CORE FEATURES:
 * --------------
 * 1. Queue Management: Priority-based content review queues
 * 2. Auto-Moderation: Rule-based automatic approval/rejection
 * 3. Moderator Assignment: Workload distribution and expertise matching
 * 4. Review History: Complete audit trail of moderation decisions
 * 5. Appeal Process: Multi-stage appeal and escalation system
 * 6. Performance Tracking: Moderator accuracy and efficiency metrics
 * 7. Rule Engine: Configurable moderation rules and criteria
 * 8. Integration: Seamless content and user management integration
 * 
 * MODERATION STATES:
 * ------------------
 * 
 * 1. 'pending' - Awaiting review
 * 2. 'in_review' - Currently being reviewed
 * 3. 'approved' - Content approved for publication
 * 4. 'rejected' - Content rejected
 * 5. 'appealing' - Under appeal review
 * 6. 'escalated' - Escalated to senior moderator
 * 7. 'auto_approved' - Automatically approved by rules
 * 8. 'auto_rejected' - Automatically rejected by rules
 * 
 * USAGE PATTERNS:
 * ---------------
 * 
 * Basic Moderation State:
 * const moderation = getModerationData(rawModerationData);
 * console.log(moderation.queueStatus);         // 'active'
 * console.log(moderation.pendingCount);        // 25
 * console.log(moderation.averageReviewTime);   // 15.3 minutes
 * console.log(moderation.autoApprovalRate);    // 67.8%
 * 
 * Queue Analysis:
 * console.log(moderation.priorityItemCount);   // 5
 * console.log(moderation.oldestItemAge);       // 2.5 hours
 * console.log(moderation.queueEfficiency);     // 85.2%
 * console.log(moderation.backlogSeverity);     // 'moderate'
 * 
 * Moderator Performance:
 * console.log(moderation.activeModerators);    // ['mod1', 'mod2']
 * console.log(moderation.workloadBalance);     // 'well-balanced'
 * console.log(moderation.averageAccuracy);     // 94.7%
 * console.log(moderation.topPerformer);        // 'mod1'
 * 
 * ====================================================================
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Moderation status types
 * @typedef {'pending' | 'in_review' | 'approved' | 'rejected' | 'appealing' | 'escalated' | 'auto_approved' | 'auto_rejected'} ModerationStatus
 */

/**
 * Priority levels for moderation items
 * @typedef {'low' | 'normal' | 'high' | 'urgent' | 'critical'} ModerationPriority
 */

/**
 * Moderation queue item
 * @typedef {Object} QueueItem
 * @property {string} id - Queue item identifier
 * @property {string} contentId - Content being moderated
 * @property {string} contentType - Type of content (article, comment, etc.)
 * @property {ModerationStatus} status - Current moderation status
 * @property {ModerationPriority} priority - Priority level
 * @property {string} assignedTo - Assigned moderator ID
 * @property {Date} submittedAt - Submission timestamp
 * @property {Date} assignedAt - Assignment timestamp
 * @property {number} estimatedReviewTime - Estimated review time in minutes
 * @property {Object} flags - Content flags and reasons
 * @property {Object} metadata - Additional metadata
 */

/**
 * Moderation rule configuration
 * @typedef {Object} ModerationRule
 * @property {string} id - Rule identifier
 * @property {string} name - Rule name
 * @property {string} type - Rule type (keyword, sentiment, spam, etc.)
 * @property {Object} criteria - Rule criteria
 * @property {string} action - Action to take (approve, reject, flag, escalate)
 * @property {number} confidence - Confidence threshold
 * @property {boolean} enabled - Rule enabled status
 * @property {Object} metadata - Rule metadata
 */

/**
 * Moderator assignment and workload
 * @typedef {Object} ModeratorAssignment
 * @property {string} userId - Moderator user ID
 * @property {string} expertise - Area of expertise
 * @property {number} currentWorkload - Current queue items assigned
 * @property {number} capacity - Maximum capacity
 * @property {number} averageReviewTime - Average review time
 * @property {number} accuracyRate - Historical accuracy rate
 * @property {boolean} available - Currently available
 * @property {Date} lastActivity - Last activity timestamp
 */

/**
 * Review decision and history
 * @typedef {Object} ReviewDecision
 * @property {string} id - Decision identifier
 * @property {string} itemId - Queue item ID
 * @property {string} moderatorId - Moderator who made decision
 * @property {string} decision - Decision made (approved, rejected, etc.)
 * @property {string} reason - Reason for decision
 * @property {number} confidence - Confidence in decision
 * @property {Date} decidedAt - Decision timestamp
 * @property {Object} notes - Additional notes
 */

/**
 * Enhanced Moderation object with comprehensive fields
 * @typedef {Object} ModerationData
 * @property {string} id - Moderation system identifier
 * @property {string} queueId - Queue identifier
 * @property {string} organizationId - Organization identifier
 * @property {string} status - Overall queue status
 * @property {QueueItem[]} queueItems - Items in moderation queue
 * @property {number} pendingCount - Number of pending items
 * @property {number} priorityItemCount - High priority items
 * @property {ModerationRule[]} moderationRules - Active moderation rules
 * @property {Object} autoApprovalCriteria - Auto-approval configuration
 * @property {ModeratorAssignment[]} moderatorAssignments - Moderator workload
 * @property {Object} workloadDistribution - Workload distribution settings
 * @property {ReviewDecision[]} reviewHistory - Historical decisions
 * @property {Object} decisionPatterns - Decision pattern analysis
 * @property {Object} escalationRules - Escalation configuration
 * @property {Object} appealProcess - Appeal process configuration
 * @property {Object} performanceMetrics - System performance metrics
 * @property {Object} accuracyTracking - Accuracy tracking data
 * @property {Object} queueSettings - Queue configuration
 * @property {Object} notifications - Notification settings
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 * @property {number} wpPostId - WordPress post ID
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<ModerationData[]>} */
export const moderationStore = writable([]);

/**
 * Normalize moderation data from any source to consistent format
 * @param {Object} rawModerationData - Raw moderation data
 * @returns {Object|null} Normalized moderation data
 */
function normalizeModerationData(rawModerationData) {
  if (!rawModerationData || typeof rawModerationData !== 'object' || !rawModerationData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawModerationData.id,
    queueId: rawModerationData.queueId || rawModerationData.queue_id || rawModerationData.id,
    organizationId: rawModerationData.organizationId || rawModerationData.organization_id || null,
    status: rawModerationData.status || 'active',
    
    // Queue Management
    queueItems: Array.isArray(rawModerationData.queueItems) ? rawModerationData.queueItems :
                Array.isArray(rawModerationData.queue_items) ? rawModerationData.queue_items : [],
    pendingCount: typeof rawModerationData.pendingCount === 'number' ? rawModerationData.pendingCount :
                  typeof rawModerationData.pending_count === 'number' ? rawModerationData.pending_count : 0,
    priorityItemCount: typeof rawModerationData.priorityItemCount === 'number' ? rawModerationData.priorityItemCount :
                       typeof rawModerationData.priority_item_count === 'number' ? rawModerationData.priority_item_count : 0,
    
    // Rules & Automation
    moderationRules: Array.isArray(rawModerationData.moderationRules) ? rawModerationData.moderationRules :
                     Array.isArray(rawModerationData.moderation_rules) ? rawModerationData.moderation_rules : [],
    autoApprovalCriteria: rawModerationData.autoApprovalCriteria || rawModerationData.auto_approval_criteria || {
      minQualityScore: 80,
      trustedSources: [],
      keywordWhitelist: [],
      autoApprovalRate: 0
    },
    
    // Moderator Management
    moderatorAssignments: Array.isArray(rawModerationData.moderatorAssignments) ? rawModerationData.moderatorAssignments :
                          Array.isArray(rawModerationData.moderator_assignments) ? rawModerationData.moderator_assignments : [],
    workloadDistribution: rawModerationData.workloadDistribution || rawModerationData.workload_distribution || {
      strategy: 'round_robin',
      maxItemsPerModerator: 10,
      expertiseMatching: true,
      loadBalancing: true
    },
    
    // History & Decisions
    reviewHistory: Array.isArray(rawModerationData.reviewHistory) ? rawModerationData.reviewHistory :
                   Array.isArray(rawModerationData.review_history) ? rawModerationData.review_history : [],
    decisionPatterns: rawModerationData.decisionPatterns || rawModerationData.decision_patterns || {
      approvalRate: 0,
      rejectionRate: 0,
      escalationRate: 0,
      averageReviewTime: 0
    },
    
    // Escalation & Appeals
    escalationRules: rawModerationData.escalationRules || rawModerationData.escalation_rules || {
      autoEscalateAfter: 60, // minutes
      escalationCriteria: [],
      seniorModerators: []
    },
    appealProcess: rawModerationData.appealProcess || rawModerationData.appeal_process || {
      enabled: true,
      maxAppeals: 2,
      appealTimeoutDays: 7,
      reviewerPool: []
    },
    
    // Performance & Metrics
    performanceMetrics: rawModerationData.performanceMetrics || rawModerationData.performance_metrics || {
      throughput: 0,
      averageQueueTime: 0,
      peakQueueSize: 0,
      busyHours: []
    },
    accuracyTracking: rawModerationData.accuracyTracking || rawModerationData.accuracy_tracking || {
      overallAccuracy: 0,
      falsePositives: 0,
      falseNegatives: 0,
      agreementRate: 0
    },
    
    // Configuration
    queueSettings: rawModerationData.queueSettings || rawModerationData.queue_settings || {
      maxQueueSize: 1000,
      priorityWeights: { critical: 10, urgent: 5, high: 3, normal: 1, low: 0.5 },
      timeoutMinutes: 120,
      batchSize: 10
    },
    notifications: rawModerationData.notifications || {
      queueAlerts: true,
      performanceAlerts: true,
      escalationAlerts: true,
      backlogWarnings: true
    },
    
    // Timestamps
    createdAt: rawModerationData.createdAt || rawModerationData.created_at || new Date().toISOString(),
    updatedAt: rawModerationData.updatedAt || rawModerationData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpPostId: rawModerationData.wpPostId || rawModerationData.wp_post_id || null,
    wpSynced: rawModerationData.wpSynced || rawModerationData.wp_synced || false,
    lastWpSync: rawModerationData.lastWpSync || rawModerationData.last_wp_sync || null,
    
    // Metadata
    metadata: rawModerationData.metadata || {}
  };
}

/**
 * Get comprehensive moderation data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} moderation - Raw moderation data
 * @returns {Object} Moderation helper with getters and methods
 */
export function getModerationData(moderation) {
  const normalizedModeration = normalizeModerationData(moderation);
  
  if (!normalizedModeration) {
    return createEmptyModerationHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedModeration.id; },
    get queueId() { return normalizedModeration.queueId; },
    get organizationId() { return normalizedModeration.organizationId; },
    get status() { return normalizedModeration.status; },
    
    // Queue Status Analysis
    get isActive() { return this.status === 'active'; },
    get isPaused() { return this.status === 'paused'; },
    get isMaintenance() { return this.status === 'maintenance'; },
    get queueStatus() { return this.status; },
    
    // Queue Management
    get queueItems() { return normalizedModeration.queueItems; },
    get pendingCount() { return normalizedModeration.pendingCount; },
    get priorityItemCount() { return normalizedModeration.priorityItemCount; },
    get totalQueueSize() { return this.queueItems.length; },
    get hasQueueItems() { return this.totalQueueSize > 0; },
    get hasPriorityItems() { return this.priorityItemCount > 0; },
    
    // Queue Analysis
    get queueUtilization() {
      const maxSize = this.queueSettings.maxQueueSize;
      return maxSize > 0 ? (this.totalQueueSize / maxSize) * 100 : 0;
    },
    get queueLoad() {
      const utilization = this.queueUtilization;
      if (utilization >= 90) return 'critical';
      if (utilization >= 75) return 'high';
      if (utilization >= 50) return 'moderate';
      if (utilization >= 25) return 'low';
      return 'minimal';
    },
    get oldestItemAge() {
      if (this.queueItems.length === 0) return 0;
      const oldest = this.queueItems.reduce((oldest, item) => {
        const itemAge = new Date() - new Date(item.submittedAt);
        return itemAge > oldest ? itemAge : oldest;
      }, 0);
      return Math.floor(oldest / (1000 * 60)); // minutes
    },
    get averageItemAge() {
      if (this.queueItems.length === 0) return 0;
      const totalAge = this.queueItems.reduce((total, item) => {
        return total + (new Date() - new Date(item.submittedAt));
      }, 0);
      return Math.floor(totalAge / this.queueItems.length / (1000 * 60)); // minutes
    },
    get backlogSeverity() {
      const oldestAge = this.oldestItemAge;
      if (oldestAge >= 240) return 'critical'; // 4 hours
      if (oldestAge >= 120) return 'high';     // 2 hours
      if (oldestAge >= 60) return 'moderate';  // 1 hour
      if (oldestAge >= 30) return 'low';       // 30 minutes
      return 'minimal';
    },
    
    // Item Status Breakdown
    get pendingItems() { return this.queueItems.filter(item => item.status === 'pending'); },
    get inReviewItems() { return this.queueItems.filter(item => item.status === 'in_review'); },
    get approvedItems() { return this.queueItems.filter(item => item.status === 'approved'); },
    get rejectedItems() { return this.queueItems.filter(item => item.status === 'rejected'); },
    get escalatedItems() { return this.queueItems.filter(item => item.status === 'escalated'); },
    get appealingItems() { return this.queueItems.filter(item => item.status === 'appealing'); },
    
    // Priority Breakdown
    get criticalItems() { return this.queueItems.filter(item => item.priority === 'critical'); },
    get urgentItems() { return this.queueItems.filter(item => item.priority === 'urgent'); },
    get highPriorityItems() { return this.queueItems.filter(item => item.priority === 'high'); },
    get normalPriorityItems() { return this.queueItems.filter(item => item.priority === 'normal'); },
    get lowPriorityItems() { return this.queueItems.filter(item => item.priority === 'low'); },
    
    // Rules & Automation
    get moderationRules() { return normalizedModeration.moderationRules; },
    get autoApprovalCriteria() { return normalizedModeration.autoApprovalCriteria; },
    get activeRuleCount() { return this.moderationRules.filter(rule => rule.enabled).length; },
    get totalRuleCount() { return this.moderationRules.length; },
    get hasActiveRules() { return this.activeRuleCount > 0; },
    get autoApprovalRate() { return this.autoApprovalCriteria.autoApprovalRate; },
    get hasAutoApproval() { return this.autoApprovalRate > 0; },
    
    // Rule Analysis
    get ruleEffectiveness() {
      if (this.totalRuleCount === 0) return 0;
      return (this.activeRuleCount / this.totalRuleCount) * 100;
    },
    get automationLevel() {
      const rate = this.autoApprovalRate;
      if (rate >= 80) return 'high';
      if (rate >= 50) return 'moderate';
      if (rate >= 20) return 'low';
      return 'minimal';
    },
    
    // Moderator Management
    get moderatorAssignments() { return normalizedModeration.moderatorAssignments; },
    get workloadDistribution() { return normalizedModeration.workloadDistribution; },
    get totalModerators() { return this.moderatorAssignments.length; },
    get activeModerators() { 
      return this.moderatorAssignments.filter(mod => mod.available);
    },
    get activeModeratorsCount() { return this.activeModerators.length; },
    get hasModerators() { return this.totalModerators > 0; },
    get hasActiveModerators() { return this.activeModeratorsCount > 0; },
    
    // Workload Analysis
    get totalWorkload() {
      return this.moderatorAssignments.reduce((total, mod) => total + mod.currentWorkload, 0);
    },
    get averageWorkload() {
      if (this.totalModerators === 0) return 0;
      return this.totalWorkload / this.totalModerators;
    },
    get workloadBalance() {
      if (this.activeModeratorsCount === 0) return 'no-moderators';
      const workloads = this.activeModerators.map(mod => mod.currentWorkload);
      const max = Math.max(...workloads);
      const min = Math.min(...workloads);
      const variance = max - min;
      
      if (variance <= 2) return 'well-balanced';
      if (variance <= 5) return 'balanced';
      if (variance <= 10) return 'unbalanced';
      return 'severely-unbalanced';
    },
    get capacityUtilization() {
      const totalCapacity = this.moderatorAssignments.reduce((total, mod) => total + mod.capacity, 0);
      return totalCapacity > 0 ? (this.totalWorkload / totalCapacity) * 100 : 0;
    },
    
    // Performance Metrics
    get performanceMetrics() { return normalizedModeration.performanceMetrics; },
    get accuracyTracking() { return normalizedModeration.accuracyTracking; },
    get throughput() { return this.performanceMetrics.throughput; },
    get averageQueueTime() { return this.performanceMetrics.averageQueueTime; },
    get peakQueueSize() { return this.performanceMetrics.peakQueueSize; },
    get overallAccuracy() { return this.accuracyTracking.overallAccuracy; },
    get falsePositiveRate() { return this.accuracyTracking.falsePositives; },
    get falseNegativeRate() { return this.accuracyTracking.falseNegatives; },
    get agreementRate() { return this.accuracyTracking.agreementRate; },
    
    // Performance Ratings
    get throughputRating() {
      const rate = this.throughput;
      if (rate >= 100) return 'excellent';
      if (rate >= 75) return 'good';
      if (rate >= 50) return 'average';
      if (rate >= 25) return 'poor';
      return 'very-poor';
    },
    get accuracyRating() {
      const accuracy = this.overallAccuracy;
      if (accuracy >= 95) return 'excellent';
      if (accuracy >= 90) return 'good';
      if (accuracy >= 85) return 'average';
      if (accuracy >= 80) return 'poor';
      return 'unacceptable';
    },
    get queueEfficiency() {
      let score = 0;
      
      // Queue utilization (0-25 points)
      const utilization = this.queueUtilization;
      if (utilization < 50) score += 25;
      else if (utilization < 75) score += 20;
      else if (utilization < 90) score += 15;
      else score += 5;
      
      // Average queue time (0-25 points)
      const avgTime = this.averageQueueTime;
      if (avgTime < 15) score += 25;
      else if (avgTime < 30) score += 20;
      else if (avgTime < 60) score += 15;
      else score += 5;
      
      // Accuracy (0-25 points)
      const accuracy = this.overallAccuracy;
      if (accuracy >= 95) score += 25;
      else if (accuracy >= 90) score += 20;
      else if (accuracy >= 85) score += 15;
      else score += 5;
      
      // Automation (0-25 points)
      const automation = this.autoApprovalRate;
      if (automation >= 70) score += 25;
      else if (automation >= 50) score += 20;
      else if (automation >= 30) score += 15;
      else score += 5;
      
      return Math.min(100, score);
    },
    get efficiencyRating() {
      const efficiency = this.queueEfficiency;
      if (efficiency >= 90) return 'excellent';
      if (efficiency >= 75) return 'good';
      if (efficiency >= 60) return 'average';
      if (efficiency >= 40) return 'poor';
      return 'critical';
    },
    
    // Review History & Patterns
    get reviewHistory() { return normalizedModeration.reviewHistory; },
    get decisionPatterns() { return normalizedModeration.decisionPatterns; },
    get totalReviews() { return this.reviewHistory.length; },
    get hasReviewHistory() { return this.totalReviews > 0; },
    get approvalRate() { return this.decisionPatterns.approvalRate; },
    get rejectionRate() { return this.decisionPatterns.rejectionRate; },
    get escalationRate() { return this.decisionPatterns.escalationRate; },
    get averageReviewTime() { return this.decisionPatterns.averageReviewTime; },
    
    // Recent Activity Analysis
    get recentReviews() {
      const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
      return this.reviewHistory.filter(review => new Date(review.decidedAt) > oneDayAgo);
    },
    get recentReviewCount() { return this.recentReviews.length; },
    get recentApprovals() { return this.recentReviews.filter(r => r.decision === 'approved').length; },
    get recentRejections() { return this.recentReviews.filter(r => r.decision === 'rejected').length; },
    get dailyReviewRate() { return this.recentReviewCount; },
    
    // Top Performers
    get topPerformer() {
      if (this.moderatorAssignments.length === 0) return null;
      return this.moderatorAssignments.reduce((top, mod) => {
        return mod.accuracyRate > (top?.accuracyRate || 0) ? mod : top;
      }, null);
    },
    get fastestModerator() {
      if (this.moderatorAssignments.length === 0) return null;
      return this.moderatorAssignments.reduce((fastest, mod) => {
        return mod.averageReviewTime < (fastest?.averageReviewTime || Infinity) ? mod : fastest;
      }, null);
    },
    
    // Escalation & Appeals
    get escalationRules() { return normalizedModeration.escalationRules; },
    get appealProcess() { return normalizedModeration.appealProcess; },
    get autoEscalationEnabled() { return this.escalationRules.autoEscalateAfter > 0; },
    get appealProcessEnabled() { return this.appealProcess.enabled; },
    get maxAppealsAllowed() { return this.appealProcess.maxAppeals; },
    get appealTimeoutDays() { return this.appealProcess.appealTimeoutDays; },
    
    // Configuration
    get queueSettings() { return normalizedModeration.queueSettings; },
    get notifications() { return normalizedModeration.notifications; },
    get maxQueueSize() { return this.queueSettings.maxQueueSize; },
    get timeoutMinutes() { return this.queueSettings.timeoutMinutes; },
    get batchSize() { return this.queueSettings.batchSize; },
    get hasNotifications() { return Object.values(this.notifications).some(enabled => enabled); },
    
    // Timestamps
    get createdAt() { return normalizedModeration.createdAt; },
    get updatedAt() { return normalizedModeration.updatedAt; },
    
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
    get isStale() { return this.daysSinceUpdate > 1; },
    
    // WordPress Integration
    get wpPostId() { return normalizedModeration.wpPostId; },
    get wpSynced() { return normalizedModeration.wpSynced; },
    get lastWpSync() { return normalizedModeration.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced && !!this.wpPostId; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Metadata
    get metadata() { return normalizedModeration.metadata; },
    
    // Validation
    get isValid() {
      return !!(this.id && this.queueId);
    },
    get isComplete() {
      return this.isValid && this.hasActiveModerators;
    },
    get isOperational() {
      return this.isComplete && this.isActive;
    },
    
    // Utility Methods
    getQueueItem(itemId) {
      return this.queueItems.find(item => item.id === itemId);
    },
    getItemsByStatus(status) {
      return this.queueItems.filter(item => item.status === status);
    },
    getItemsByPriority(priority) {
      return this.queueItems.filter(item => item.priority === priority);
    },
    getModerator(userId) {
      return this.moderatorAssignments.find(mod => mod.userId === userId);
    },
    getRule(ruleId) {
      return this.moderationRules.find(rule => rule.id === ruleId);
    },
    canAssignTo(moderatorId) {
      const moderator = this.getModerator(moderatorId);
      return moderator && moderator.available && moderator.currentWorkload < moderator.capacity;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        queueId: this.queueId,
        status: this.status,
        totalQueueSize: this.totalQueueSize,
        pendingCount: this.pendingCount,
        activeModeratorsCount: this.activeModeratorsCount,
        queueEfficiency: this.queueEfficiency,
        efficiencyRating: this.efficiencyRating,
        backlogSeverity: this.backlogSeverity,
        workloadBalance: this.workloadBalance,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isOperational: this.isOperational
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        queueId: this.queueId,
        organizationId: this.organizationId,
        status: this.status,
        
        // Queue data
        queueItems: this.queueItems,
        pendingCount: this.pendingCount,
        priorityItemCount: this.priorityItemCount,
        
        // Rules
        moderationRules: this.moderationRules,
        autoApprovalCriteria: this.autoApprovalCriteria,
        
        // Moderators
        moderatorAssignments: this.moderatorAssignments,
        workloadDistribution: this.workloadDistribution,
        
        // History
        reviewHistory: this.reviewHistory,
        decisionPatterns: this.decisionPatterns,
        
        // Escalation
        escalationRules: this.escalationRules,
        appealProcess: this.appealProcess,
        
        // Performance
        performanceMetrics: this.performanceMetrics,
        accuracyTracking: this.accuracyTracking,
        
        // Configuration
        queueSettings: this.queueSettings,
        notifications: this.notifications,
        
        // Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        
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
 * Create empty moderation helper for null/undefined moderation data
 * @returns {Object} Empty moderation helper with safe defaults
 */
function createEmptyModerationHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get queueId() { return null; },
    get organizationId() { return null; },
    get status() { return 'inactive'; },
    
    // Queue Status
    get isActive() { return false; },
    get isPaused() { return false; },
    get queueStatus() { return 'inactive'; },
    
    // Queue Management
    get queueItems() { return []; },
    get pendingCount() { return 0; },
    get priorityItemCount() { return 0; },
    get totalQueueSize() { return 0; },
    get hasQueueItems() { return false; },
    get hasPriorityItems() { return false; },
    
    // Queue Analysis
    get queueUtilization() { return 0; },
    get queueLoad() { return 'minimal'; },
    get oldestItemAge() { return 0; },
    get averageItemAge() { return 0; },
    get backlogSeverity() { return 'minimal'; },
    
    // Rules & Automation
    get moderationRules() { return []; },
    get autoApprovalCriteria() { return { autoApprovalRate: 0 }; },
    get activeRuleCount() { return 0; },
    get hasActiveRules() { return false; },
    get autoApprovalRate() { return 0; },
    get hasAutoApproval() { return false; },
    get ruleEffectiveness() { return 0; },
    get automationLevel() { return 'minimal'; },
    
    // Moderators
    get moderatorAssignments() { return []; },
    get totalModerators() { return 0; },
    get activeModerators() { return []; },
    get activeModeratorsCount() { return 0; },
    get hasModerators() { return false; },
    get hasActiveModerators() { return false; },
    get workloadBalance() { return 'no-moderators'; },
    
    // Performance
    get performanceMetrics() { return { throughput: 0, averageQueueTime: 0 }; },
    get accuracyTracking() { return { overallAccuracy: 0 }; },
    get queueEfficiency() { return 0; },
    get efficiencyRating() { return 'critical'; },
    get throughputRating() { return 'very-poor'; },
    get accuracyRating() { return 'unacceptable'; },
    
    // History
    get reviewHistory() { return []; },
    get decisionPatterns() { return { approvalRate: 0, rejectionRate: 0 }; },
    get totalReviews() { return 0; },
    get hasReviewHistory() { return false; },
    
    // Configuration
    get queueSettings() { return { maxQueueSize: 1000, timeoutMinutes: 120 }; },
    get notifications() { return {}; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    
    // WordPress
    get wpPostId() { return null; },
    get wpSynced() { return false; },
    get isSyncedToWordPress() { return false; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    
    // Utility Methods
    getQueueItem(itemId) { return null; },
    getItemsByStatus(status) { return []; },
    getModerator(userId) { return null; },
    canAssignTo(moderatorId) { return false; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        status: 'inactive',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        status: 'inactive',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Moderation
 */

/**
 * Create moderation queue
 * @param {Object} moderationData - Initial moderation data
 * @returns {Promise<Object>} Created moderation queue
 */
export async function createModerationQueue(moderationData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create moderation queue');
    }

    const newModeration = {
      id: crypto.randomUUID(),
      queueId: crypto.randomUUID(),
      organizationId: currentUser.organizationId || null,
      status: 'active',
      queueItems: [],
      pendingCount: 0,
      ...moderationData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.moderation.create(newModeration);
    }

    // Update local store
    moderationStore.update(queues => [...queues, newModeration]);

    log(`[Moderation] Created moderation queue: ${newModeration.id}`, 'info');
    return getModerationData(newModeration);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Moderation] Error creating moderation queue: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update moderation queue
 * @param {string} moderationId - Moderation ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated moderation queue
 */
export async function updateModerationQueue(moderationId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.moderation.update(moderationId, updatedData);
    }

    // Update local store
    moderationStore.update(queues => 
      queues.map(queue => 
        queue.id === moderationId 
          ? { ...queue, ...updatedData }
          : queue
      )
    );

    log(`[Moderation] Updated moderation queue: ${moderationId}`, 'info');
    
    // Return updated moderation data
    const updatedModeration = await getModerationById(moderationId);
    return updatedModeration;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Moderation] Error updating moderation queue: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get moderation queue by ID
 * @param {string} moderationId - Moderation ID
 * @returns {Promise<Object|null>} Moderation data or null
 */
export async function getModerationById(moderationId) {
  try {
    let moderation = null;

    // Try LiveStore first
    if (browser && liveStore) {
      moderation = await liveStore.moderation.findById(moderationId);
    }

    // Fallback to local store
    if (!moderation) {
      const queues = await new Promise(resolve => {
        moderationStore.subscribe(value => resolve(value))();
      });
      moderation = queues.find(q => q.id === moderationId);
    }

    return moderation ? getModerationData(moderation) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Moderation] Error getting moderation by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get active moderation queues
 * @returns {Promise<Object[]>} Array of active moderation queues
 */
export async function getActiveModerationQueues() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return [];

    let queues = [];

    // Try LiveStore first
    if (browser && liveStore) {
      queues = await liveStore.moderation.findMany({
        where: {
          status: 'active'
        },
        orderBy: { updatedAt: 'desc' }
      });
    }

    // Fallback to local store
    if (queues.length === 0) {
      const allQueues = await new Promise(resolve => {
        moderationStore.subscribe(value => resolve(value))();
      });
      queues = allQueues.filter(queue => queue.status === 'active');
    }

    return queues.map(queue => getModerationData(queue));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Moderation] Error getting active moderation queues: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: moderationStore,
  getModerationData,
  createModerationQueue,
  updateModerationQueue,
  getModerationById,
  getActiveModerationQueues
}; 