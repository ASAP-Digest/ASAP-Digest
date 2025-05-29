/**
 * Notification System Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Notification business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Notification types
 * @typedef {'system' | 'digest' | 'content' | 'user' | 'security' | 'billing' | 'marketing'} NotificationType
 */

/**
 * Notification priority levels
 * @typedef {'low' | 'normal' | 'high' | 'urgent' | 'critical'} NotificationPriority
 */

/**
 * Notification status
 * @typedef {'unread' | 'read' | 'dismissed' | 'archived' | 'expired'} NotificationStatus
 */

/**
 * Delivery methods
 * @typedef {'email' | 'push' | 'in_app' | 'sms' | 'webhook'} DeliveryMethod
 */

/**
 * Enhanced Notification object with comprehensive fields
 * @typedef {Object} Notification
 * @property {string} id - Notification identifier
 * @property {NotificationType} type - Notification type/category
 * @property {string} title - Notification title
 * @property {string} message - Notification message content
 * @property {NotificationPriority} priority - Priority level
 * @property {string} recipientId - Recipient user ID
 * @property {string} recipientType - Recipient type (user/admin/system)
 * @property {NotificationStatus} status - Current status
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} readAt - Read timestamp
 * @property {Date} dismissedAt - Dismissed timestamp
 * @property {Date} expiresAt - Expiration timestamp
 * @property {boolean} actionRequired - Whether action is required
 * @property {string} actionUrl - URL for action
 * @property {Object} actionData - Additional action data
 * @property {string} category - Notification category
 * @property {string[]} tags - Notification tags
 * @property {string} relatedObjectId - Related object ID
 * @property {string} relatedObjectType - Related object type
 * @property {DeliveryMethod[]} deliveryMethods - Delivery methods
 * @property {Object} deliveryStatus - Delivery status per method
 * @property {number} deliveryAttempts - Number of delivery attempts
 * @property {Date} lastDeliveryAttempt - Last delivery attempt timestamp
 * @property {Object} metadata - Additional metadata
 * @property {string} senderId - Sender user ID
 * @property {string} senderType - Sender type
 * @property {Object} templateData - Template data for rendering
 * @property {string} templateId - Template identifier
 * @property {boolean} persistent - Whether notification persists
 * @property {Object} tracking - Tracking data (opens, clicks)
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<Notification[]>} */
export const notificationsStore = writable([]);

/**
 * Normalize notification data from any source to consistent format
 * @param {Object} rawNotificationData - Raw notification data
 * @returns {Object|null} Normalized notification data
 */
function normalizeNotificationData(rawNotificationData) {
  if (!rawNotificationData || typeof rawNotificationData !== 'object' || !rawNotificationData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawNotificationData.id,
    type: rawNotificationData.type || 'system',
    title: rawNotificationData.title || '',
    message: rawNotificationData.message || '',
    priority: rawNotificationData.priority || 'normal',
    
    // Recipient Information
    recipientId: rawNotificationData.recipientId || rawNotificationData.recipient_id || null,
    recipientType: rawNotificationData.recipientType || rawNotificationData.recipient_type || 'user',
    
    // Status & Timing
    status: rawNotificationData.status || 'unread',
    createdAt: rawNotificationData.createdAt || rawNotificationData.created_at || new Date().toISOString(),
    readAt: rawNotificationData.readAt || rawNotificationData.read_at || null,
    dismissedAt: rawNotificationData.dismissedAt || rawNotificationData.dismissed_at || null,
    expiresAt: rawNotificationData.expiresAt || rawNotificationData.expires_at || null,
    
    // Action Information
    actionRequired: rawNotificationData.actionRequired || rawNotificationData.action_required || false,
    actionUrl: rawNotificationData.actionUrl || rawNotificationData.action_url || null,
    actionData: rawNotificationData.actionData || rawNotificationData.action_data || {},
    
    // Categorization
    category: rawNotificationData.category || 'general',
    tags: Array.isArray(rawNotificationData.tags) ? rawNotificationData.tags : [],
    relatedObjectId: rawNotificationData.relatedObjectId || rawNotificationData.related_object_id || null,
    relatedObjectType: rawNotificationData.relatedObjectType || rawNotificationData.related_object_type || null,
    
    // Delivery Configuration
    deliveryMethods: Array.isArray(rawNotificationData.deliveryMethods) ? rawNotificationData.deliveryMethods :
                     Array.isArray(rawNotificationData.delivery_methods) ? rawNotificationData.delivery_methods : ['in_app'],
    deliveryStatus: rawNotificationData.deliveryStatus || rawNotificationData.delivery_status || {},
    deliveryAttempts: typeof rawNotificationData.deliveryAttempts === 'number' ? rawNotificationData.deliveryAttempts :
                      typeof rawNotificationData.delivery_attempts === 'number' ? rawNotificationData.delivery_attempts : 0,
    lastDeliveryAttempt: rawNotificationData.lastDeliveryAttempt || rawNotificationData.last_delivery_attempt || null,
    
    // Additional Data
    metadata: rawNotificationData.metadata || {},
    senderId: rawNotificationData.senderId || rawNotificationData.sender_id || null,
    senderType: rawNotificationData.senderType || rawNotificationData.sender_type || 'system',
    
    // Template Information
    templateData: rawNotificationData.templateData || rawNotificationData.template_data || {},
    templateId: rawNotificationData.templateId || rawNotificationData.template_id || null,
    
    // Persistence & Tracking
    persistent: rawNotificationData.persistent || false,
    tracking: rawNotificationData.tracking || {
      opens: 0,
      clicks: 0,
      lastOpened: null,
      lastClicked: null
    },
    
    // Timestamps
    updatedAt: rawNotificationData.updatedAt || rawNotificationData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawNotificationData.wpSynced || rawNotificationData.wp_synced || false,
    lastWpSync: rawNotificationData.lastWpSync || rawNotificationData.last_wp_sync || null
  };
}

/**
 * Get comprehensive notification data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} notification - Raw notification data
 * @returns {Object} Notification helper with getters and methods
 */
export function getNotificationData(notification) {
  const normalizedNotification = normalizeNotificationData(notification);
  
  if (!normalizedNotification) {
    return createEmptyNotificationHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedNotification.id; },
    get type() { return normalizedNotification.type; },
    get title() { return normalizedNotification.title; },
    get message() { return normalizedNotification.message; },
    get priority() { return normalizedNotification.priority; },
    
    // Type Analysis
    get isSystemNotification() { return this.type === 'system'; },
    get isDigestNotification() { return this.type === 'digest'; },
    get isContentNotification() { return this.type === 'content'; },
    get isUserNotification() { return this.type === 'user'; },
    get isSecurityNotification() { return this.type === 'security'; },
    get isBillingNotification() { return this.type === 'billing'; },
    get isMarketingNotification() { return this.type === 'marketing'; },
    
    // Priority Analysis
    get isLowPriority() { return this.priority === 'low'; },
    get isNormalPriority() { return this.priority === 'normal'; },
    get isHighPriority() { return this.priority === 'high'; },
    get isUrgentPriority() { return this.priority === 'urgent'; },
    get isCriticalPriority() { return this.priority === 'critical'; },
    get isImportant() { return this.isHighPriority || this.isUrgentPriority || this.isCriticalPriority; },
    get priorityLevel() {
      const levels = { low: 1, normal: 2, high: 3, urgent: 4, critical: 5 };
      return levels[this.priority] || 2;
    },
    
    // Recipient Information
    get recipientId() { return normalizedNotification.recipientId; },
    get recipientType() { return normalizedNotification.recipientType; },
    get isUserRecipient() { return this.recipientType === 'user'; },
    get isAdminRecipient() { return this.recipientType === 'admin'; },
    get isSystemRecipient() { return this.recipientType === 'system'; },
    
    // Status & State
    get status() { return normalizedNotification.status; },
    get isUnread() { return this.status === 'unread'; },
    get isRead() { return this.status === 'read'; },
    get isDismissed() { return this.status === 'dismissed'; },
    get isArchived() { return this.status === 'archived'; },
    get isExpired() { return this.status === 'expired'; },
    get isActive() { return this.isUnread || this.isRead; },
    get isInactive() { return this.isDismissed || this.isArchived || this.isExpired; },
    
    // Timing
    get createdAt() { return normalizedNotification.createdAt; },
    get readAt() { return normalizedNotification.readAt; },
    get dismissedAt() { return normalizedNotification.dismissedAt; },
    get expiresAt() { return normalizedNotification.expiresAt; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60)); // minutes
    },
    get ageHours() { return Math.floor(this.age / 60); },
    get ageDays() { return Math.floor(this.ageHours / 24); },
    get isRecent() { return this.age <= 60; }, // within 1 hour
    get isOld() { return this.ageDays >= 7; }, // older than 7 days
    get isStale() { return this.ageDays >= 30; }, // older than 30 days
    
    get hasBeenRead() { return !!this.readAt; },
    get hasBeenDismissed() { return !!this.dismissedAt; },
    get hasExpiration() { return !!this.expiresAt; },
    get isExpiredByTime() {
      if (!this.hasExpiration) return false;
      return new Date() > new Date(this.expiresAt);
    },
    get timeToExpiry() {
      if (!this.hasExpiration) return null;
      const expiry = new Date(this.expiresAt);
      const now = new Date();
      return Math.max(0, Math.floor((expiry.getTime() - now.getTime()) / (1000 * 60))); // minutes
    },
    get isExpiringSoon() {
      return this.hasExpiration && this.timeToExpiry <= 60; // within 1 hour
    },
    
    get readTime() {
      if (!this.hasBeenRead) return null;
      const read = new Date(this.readAt);
      const created = new Date(this.createdAt);
      return Math.floor((read.getTime() - created.getTime()) / (1000 * 60)); // minutes to read
    },
    get wasReadQuickly() { return this.readTime !== null && this.readTime <= 5; },
    get wasReadSlowly() { return this.readTime !== null && this.readTime >= 60; },
    
    // Action Information
    get actionRequired() { return normalizedNotification.actionRequired; },
    get actionUrl() { return normalizedNotification.actionUrl; },
    get actionData() { return normalizedNotification.actionData; },
    get hasAction() { return this.actionRequired || !!this.actionUrl; },
    get hasActionData() { return Object.keys(this.actionData).length > 0; },
    get isActionable() { return this.hasAction && this.isActive; },
    
    // Categorization
    get category() { return normalizedNotification.category; },
    get tags() { return normalizedNotification.tags; },
    get relatedObjectId() { return normalizedNotification.relatedObjectId; },
    get relatedObjectType() { return normalizedNotification.relatedObjectType; },
    get hasTags() { return this.tags.length > 0; },
    get hasRelatedObject() { return !!this.relatedObjectId; },
    get tagCount() { return this.tags.length; },
    
    // Tag Analysis
    hasTag(tag) { return this.tags.includes(tag); },
    get isUrgentTag() { return this.hasTag('urgent'); },
    get isImportantTag() { return this.hasTag('important'); },
    get isPersonalTag() { return this.hasTag('personal'); },
    get isWorkTag() { return this.hasTag('work'); },
    
    // Delivery Configuration
    get deliveryMethods() { return normalizedNotification.deliveryMethods; },
    get deliveryStatus() { return normalizedNotification.deliveryStatus; },
    get deliveryAttempts() { return normalizedNotification.deliveryAttempts; },
    get lastDeliveryAttempt() { return normalizedNotification.lastDeliveryAttempt; },
    
    // Delivery Analysis
    get hasEmailDelivery() { return this.deliveryMethods.includes('email'); },
    get hasPushDelivery() { return this.deliveryMethods.includes('push'); },
    get hasInAppDelivery() { return this.deliveryMethods.includes('in_app'); },
    get hasSmsDelivery() { return this.deliveryMethods.includes('sms'); },
    get hasWebhookDelivery() { return this.deliveryMethods.includes('webhook'); },
    get deliveryMethodCount() { return this.deliveryMethods.length; },
    get isMultiChannel() { return this.deliveryMethodCount > 1; },
    
    get emailDelivered() { return this.deliveryStatus.email === 'delivered'; },
    get pushDelivered() { return this.deliveryStatus.push === 'delivered'; },
    get inAppDelivered() { return this.deliveryStatus.in_app === 'delivered'; },
    get smsDelivered() { return this.deliveryStatus.sms === 'delivered'; },
    get webhookDelivered() { return this.deliveryStatus.webhook === 'delivered'; },
    
    get emailFailed() { return this.deliveryStatus.email === 'failed'; },
    get pushFailed() { return this.deliveryStatus.push === 'failed'; },
    get inAppFailed() { return this.deliveryStatus.in_app === 'failed'; },
    get smsFailed() { return this.deliveryStatus.sms === 'failed'; },
    get webhookFailed() { return this.deliveryStatus.webhook === 'failed'; },
    
    get deliveredCount() {
      return this.deliveryMethods.filter(method => 
        this.deliveryStatus[method] === 'delivered'
      ).length;
    },
    get failedCount() {
      return this.deliveryMethods.filter(method => 
        this.deliveryStatus[method] === 'failed'
      ).length;
    },
    get pendingCount() {
      return this.deliveryMethods.filter(method => 
        !this.deliveryStatus[method] || this.deliveryStatus[method] === 'pending'
      ).length;
    },
    
    get isFullyDelivered() { return this.deliveredCount === this.deliveryMethodCount; },
    get isPartiallyDelivered() { return this.deliveredCount > 0 && this.deliveredCount < this.deliveryMethodCount; },
    get hasDeliveryFailures() { return this.failedCount > 0; },
    get hasPendingDeliveries() { return this.pendingCount > 0; },
    get deliverySuccessRate() {
      return this.deliveryMethodCount > 0 ? (this.deliveredCount / this.deliveryMethodCount) * 100 : 0;
    },
    
    get hasDeliveryAttempts() { return this.deliveryAttempts > 0; },
    get isRetryNeeded() { return this.hasDeliveryFailures && this.deliveryAttempts < 3; },
    get maxRetriesReached() { return this.deliveryAttempts >= 3; },
    
    // Additional Data
    get metadata() { return normalizedNotification.metadata; },
    get senderId() { return normalizedNotification.senderId; },
    get senderType() { return normalizedNotification.senderType; },
    get hasMetadata() { return Object.keys(this.metadata).length > 0; },
    get hasSender() { return !!this.senderId; },
    get isSystemSent() { return this.senderType === 'system'; },
    get isUserSent() { return this.senderType === 'user'; },
    get isAdminSent() { return this.senderType === 'admin'; },
    
    // Template Information
    get templateData() { return normalizedNotification.templateData; },
    get templateId() { return normalizedNotification.templateId; },
    get hasTemplate() { return !!this.templateId; },
    get hasTemplateData() { return Object.keys(this.templateData).length > 0; },
    get isTemplated() { return this.hasTemplate || this.hasTemplateData; },
    
    // Persistence & Tracking
    get persistent() { return normalizedNotification.persistent; },
    get tracking() { return normalizedNotification.tracking; },
    get opens() { return this.tracking.opens; },
    get clicks() { return this.tracking.clicks; },
    get lastOpened() { return this.tracking.lastOpened; },
    get lastClicked() { return this.tracking.lastClicked; },
    
    get hasBeenOpened() { return this.opens > 0; },
    get hasBeenClicked() { return this.clicks > 0; },
    get hasEngagement() { return this.hasBeenOpened || this.hasBeenClicked; },
    get clickThroughRate() {
      return this.opens > 0 ? (this.clicks / this.opens) * 100 : 0;
    },
    get isHighEngagement() { return this.clickThroughRate > 20; },
    get isLowEngagement() { return this.opens > 0 && this.clickThroughRate < 5; },
    
    // Timestamps
    get updatedAt() { return normalizedNotification.updatedAt; },
    get daysSinceUpdate() {
      const updated = new Date(this.updatedAt);
      const now = new Date();
      return Math.floor((now.getTime() - updated.getTime()) / (1000 * 60 * 60 * 24));
    },
    get isRecentlyUpdated() { return this.daysSinceUpdate <= 1; },
    
    // WordPress Integration
    get wpSynced() { return normalizedNotification.wpSynced; },
    get lastWpSync() { return normalizedNotification.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Content Analysis
    get titleLength() { return this.title.length; },
    get messageLength() { return this.message.length; },
    get hasLongTitle() { return this.titleLength > 50; },
    get hasLongMessage() { return this.messageLength > 200; },
    get isShortNotification() { return this.titleLength <= 20 && this.messageLength <= 50; },
    get isDetailedNotification() { return this.titleLength > 30 || this.messageLength > 100; },
    
    // Notification Scoring
    get importanceScore() {
      let score = this.priorityLevel * 20; // 20-100 base score
      
      if (this.actionRequired) score += 20;
      if (this.isSecurityNotification) score += 15;
      if (this.isCriticalPriority) score += 25;
      if (this.isUrgentTag) score += 15;
      if (this.isImportantTag) score += 10;
      if (this.isExpiringSoon) score += 20;
      
      return Math.min(100, score);
    },
    get importanceRating() {
      const score = this.importanceScore;
      if (score >= 90) return 'critical';
      if (score >= 75) return 'high';
      if (score >= 50) return 'medium';
      if (score >= 25) return 'low';
      return 'minimal';
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.title && this.recipientId);
    },
    get isComplete() {
      return this.isValid && this.message && this.type;
    },
    get isDeliverable() {
      return this.isComplete && this.deliveryMethods.length > 0 && this.isActive;
    },
    
    // Utility Methods
    hasDeliveryMethod(method) {
      return this.deliveryMethods.includes(method);
    },
    getDeliveryStatus(method) {
      return this.deliveryStatus[method] || 'pending';
    },
    isDeliverySuccessful(method) {
      return this.getDeliveryStatus(method) === 'delivered';
    },
    isDeliveryFailed(method) {
      return this.getDeliveryStatus(method) === 'failed';
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
        type: this.type,
        priority: this.priority,
        status: this.status,
        importanceScore: this.importanceScore,
        importanceRating: this.importanceRating,
        deliverySuccessRate: this.deliverySuccessRate,
        age: this.age,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isDeliverable: this.isDeliverable
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        type: this.type,
        title: this.title,
        message: this.message,
        priority: this.priority,
        recipientId: this.recipientId,
        recipientType: this.recipientType,
        status: this.status,
        createdAt: this.createdAt,
        readAt: this.readAt,
        dismissedAt: this.dismissedAt,
        expiresAt: this.expiresAt,
        actionRequired: this.actionRequired,
        actionUrl: this.actionUrl,
        actionData: this.actionData,
        category: this.category,
        tags: this.tags,
        relatedObjectId: this.relatedObjectId,
        relatedObjectType: this.relatedObjectType,
        deliveryMethods: this.deliveryMethods,
        deliveryStatus: this.deliveryStatus,
        deliveryAttempts: this.deliveryAttempts,
        lastDeliveryAttempt: this.lastDeliveryAttempt,
        metadata: this.metadata,
        senderId: this.senderId,
        senderType: this.senderType,
        templateData: this.templateData,
        templateId: this.templateId,
        persistent: this.persistent,
        tracking: this.tracking,
        updatedAt: this.updatedAt,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync
      };
    }
  };
}

/**
 * Create empty notification helper for null/undefined notification data
 * @returns {Object} Empty notification helper with safe defaults
 */
function createEmptyNotificationHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get type() { return 'system'; },
    get title() { return ''; },
    get message() { return ''; },
    get priority() { return 'normal'; },
    
    // Type Analysis
    get isSystemNotification() { return true; },
    get isDigestNotification() { return false; },
    get isContentNotification() { return false; },
    get isUserNotification() { return false; },
    get isSecurityNotification() { return false; },
    get isBillingNotification() { return false; },
    get isMarketingNotification() { return false; },
    
    // Priority Analysis
    get isLowPriority() { return false; },
    get isNormalPriority() { return true; },
    get isHighPriority() { return false; },
    get isUrgentPriority() { return false; },
    get isCriticalPriority() { return false; },
    get isImportant() { return false; },
    get priorityLevel() { return 2; },
    
    // Recipient Information
    get recipientId() { return null; },
    get recipientType() { return 'user'; },
    get isUserRecipient() { return true; },
    get isAdminRecipient() { return false; },
    get isSystemRecipient() { return false; },
    
    // Status & State
    get status() { return 'unread'; },
    get isUnread() { return true; },
    get isRead() { return false; },
    get isDismissed() { return false; },
    get isArchived() { return false; },
    get isExpired() { return false; },
    get isActive() { return true; },
    get isInactive() { return false; },
    
    // Timing
    get createdAt() { return null; },
    get readAt() { return null; },
    get dismissedAt() { return null; },
    get expiresAt() { return null; },
    
    // Time Analysis
    get age() { return 0; },
    get ageHours() { return 0; },
    get ageDays() { return 0; },
    get isRecent() { return false; },
    get isOld() { return false; },
    get isStale() { return false; },
    get hasBeenRead() { return false; },
    get hasBeenDismissed() { return false; },
    get hasExpiration() { return false; },
    get isExpiredByTime() { return false; },
    get timeToExpiry() { return null; },
    get isExpiringSoon() { return false; },
    get readTime() { return null; },
    get wasReadQuickly() { return false; },
    get wasReadSlowly() { return false; },
    
    // Action Information
    get actionRequired() { return false; },
    get actionUrl() { return null; },
    get actionData() { return {}; },
    get hasAction() { return false; },
    get hasActionData() { return false; },
    get isActionable() { return false; },
    
    // Categorization
    get category() { return 'general'; },
    get tags() { return []; },
    get relatedObjectId() { return null; },
    get relatedObjectType() { return null; },
    get hasTags() { return false; },
    get hasRelatedObject() { return false; },
    get tagCount() { return 0; },
    
    // Tag Analysis
    hasTag(tag) { return false; },
    get isUrgentTag() { return false; },
    get isImportantTag() { return false; },
    get isPersonalTag() { return false; },
    get isWorkTag() { return false; },
    
    // Delivery Configuration
    get deliveryMethods() { return ['in_app']; },
    get deliveryStatus() { return {}; },
    get deliveryAttempts() { return 0; },
    get lastDeliveryAttempt() { return null; },
    
    // Delivery Analysis
    get hasEmailDelivery() { return false; },
    get hasPushDelivery() { return false; },
    get hasInAppDelivery() { return true; },
    get hasSmsDelivery() { return false; },
    get hasWebhookDelivery() { return false; },
    get deliveryMethodCount() { return 1; },
    get isMultiChannel() { return false; },
    get emailDelivered() { return false; },
    get pushDelivered() { return false; },
    get inAppDelivered() { return false; },
    get smsDelivered() { return false; },
    get webhookDelivered() { return false; },
    get emailFailed() { return false; },
    get pushFailed() { return false; },
    get inAppFailed() { return false; },
    get smsFailed() { return false; },
    get webhookFailed() { return false; },
    get deliveredCount() { return 0; },
    get failedCount() { return 0; },
    get pendingCount() { return 1; },
    get isFullyDelivered() { return false; },
    get isPartiallyDelivered() { return false; },
    get hasDeliveryFailures() { return false; },
    get hasPendingDeliveries() { return true; },
    get deliverySuccessRate() { return 0; },
    get hasDeliveryAttempts() { return false; },
    get isRetryNeeded() { return false; },
    get maxRetriesReached() { return false; },
    
    // Additional Data
    get metadata() { return {}; },
    get senderId() { return null; },
    get senderType() { return 'system'; },
    get hasMetadata() { return false; },
    get hasSender() { return false; },
    get isSystemSent() { return true; },
    get isUserSent() { return false; },
    get isAdminSent() { return false; },
    
    // Template Information
    get templateData() { return {}; },
    get templateId() { return null; },
    get hasTemplate() { return false; },
    get hasTemplateData() { return false; },
    get isTemplated() { return false; },
    
    // Persistence & Tracking
    get persistent() { return false; },
    get tracking() { return { opens: 0, clicks: 0, lastOpened: null, lastClicked: null }; },
    get opens() { return 0; },
    get clicks() { return 0; },
    get lastOpened() { return null; },
    get lastClicked() { return null; },
    get hasBeenOpened() { return false; },
    get hasBeenClicked() { return false; },
    get hasEngagement() { return false; },
    get clickThroughRate() { return 0; },
    get isHighEngagement() { return false; },
    get isLowEngagement() { return false; },
    
    // Timestamps
    get updatedAt() { return null; },
    get daysSinceUpdate() { return 0; },
    get isRecentlyUpdated() { return false; },
    
    // WordPress Integration
    get wpSynced() { return false; },
    get lastWpSync() { return null; },
    get isSyncedToWordPress() { return false; },
    get needsWordPressSync() { return false; },
    
    // Content Analysis
    get titleLength() { return 0; },
    get messageLength() { return 0; },
    get hasLongTitle() { return false; },
    get hasLongMessage() { return false; },
    get isShortNotification() { return true; },
    get isDetailedNotification() { return false; },
    
    // Notification Scoring
    get importanceScore() { return 40; },
    get importanceRating() { return 'low'; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isDeliverable() { return false; },
    
    // Utility Methods
    hasDeliveryMethod(method) { return method === 'in_app'; },
    getDeliveryStatus(method) { return 'pending'; },
    isDeliverySuccessful(method) { return false; },
    isDeliveryFailed(method) { return false; },
    getMetadata(key, defaultValue = null) { return defaultValue; },
    hasMetadataKey(key) { return false; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        type: 'system',
        priority: 'normal',
        status: 'unread',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        type: 'system',
        title: '',
        message: '',
        priority: 'normal',
        status: 'unread',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Notifications
 */

/**
 * Create new notification
 * @param {Object} notificationData - Initial notification data
 * @returns {Promise<Object>} Created notification record
 */
export async function createNotification(notificationData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create notifications');
    }

    const newNotification = {
      id: crypto.randomUUID(),
      ...notificationData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      senderId: notificationData.senderId || currentUser.id,
      senderType: notificationData.senderType || 'user'
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.notifications.create(newNotification);
    }

    // Update local store
    notificationsStore.update(records => [...records, newNotification]);

    log(`[Notifications] Created new notification: ${newNotification.id}`, 'info');
    return getNotificationData(newNotification);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error creating notification: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing notification
 * @param {string} notificationId - Notification ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated notification record
 */
export async function updateNotification(notificationId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.notifications.update(notificationId, updatedData);
    }

    // Update local store
    notificationsStore.update(records => 
      records.map(record => 
        record.id === notificationId 
          ? { ...record, ...updatedData }
          : record
      )
    );

    log(`[Notifications] Updated notification: ${notificationId}`, 'info');
    
    const updatedNotification = await getNotificationById(notificationId);
    return updatedNotification;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error updating notification: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Mark notification as read
 * @param {string} notificationId - Notification ID
 * @returns {Promise<Object>} Updated notification record
 */
export async function markNotificationAsRead(notificationId) {
  return await updateNotification(notificationId, {
    status: 'read',
    readAt: new Date().toISOString()
  });
}

/**
 * Mark notification as dismissed
 * @param {string} notificationId - Notification ID
 * @returns {Promise<Object>} Updated notification record
 */
export async function dismissNotification(notificationId) {
  return await updateNotification(notificationId, {
    status: 'dismissed',
    dismissedAt: new Date().toISOString()
  });
}

/**
 * Archive notification
 * @param {string} notificationId - Notification ID
 * @returns {Promise<Object>} Updated notification record
 */
export async function archiveNotification(notificationId) {
  return await updateNotification(notificationId, {
    status: 'archived'
  });
}

/**
 * Track notification open
 * @param {string} notificationId - Notification ID
 * @returns {Promise<Object>} Updated notification record
 */
export async function trackNotificationOpen(notificationId) {
  try {
    const notification = await getNotificationById(notificationId);
    if (!notification) {
      throw new Error('Notification not found');
    }

    const updatedTracking = {
      ...notification.tracking,
      opens: notification.opens + 1,
      lastOpened: new Date().toISOString()
    };

    return await updateNotification(notificationId, {
      tracking: updatedTracking
    });

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error tracking notification open: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Track notification click
 * @param {string} notificationId - Notification ID
 * @returns {Promise<Object>} Updated notification record
 */
export async function trackNotificationClick(notificationId) {
  try {
    const notification = await getNotificationById(notificationId);
    if (!notification) {
      throw new Error('Notification not found');
    }

    const updatedTracking = {
      ...notification.tracking,
      clicks: notification.clicks + 1,
      lastClicked: new Date().toISOString()
    };

    return await updateNotification(notificationId, {
      tracking: updatedTracking
    });

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error tracking notification click: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete notification
 * @param {string} notificationId - Notification ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteNotification(notificationId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.notifications.delete(notificationId);
    }

    // Update local store
    notificationsStore.update(records => 
      records.filter(record => record.id !== notificationId)
    );

    log(`[Notifications] Deleted notification: ${notificationId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error deleting notification: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get notification by ID
 * @param {string} notificationId - Notification ID
 * @returns {Promise<Object|null>} Notification data or null
 */
export async function getNotificationById(notificationId) {
  try {
    let notification = null;

    // Try LiveStore first
    if (browser && liveStore) {
      notification = await liveStore.notifications.findById(notificationId);
    }

    // Fallback to local store
    if (!notification) {
      const allRecords = await new Promise(resolve => {
        notificationsStore.subscribe(value => resolve(value))();
      });
      notification = allRecords.find(n => n.id === notificationId);
    }

    return notification ? getNotificationData(notification) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error getting notification by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get notifications for user
 * @param {string} userId - User ID
 * @param {Object} options - Query options
 * @returns {Promise<Object[]>} Array of notification data
 */
export async function getNotificationsForUser(userId, options = {}) {
  try {
    const {
      status = null,
      type = null,
      priority = null,
      limit = 50,
      offset = 0,
      orderBy = 'createdAt',
      order = 'desc'
    } = options;

    let notifications = [];

    // Try LiveStore first
    if (browser && liveStore) {
      const where = { recipientId: userId };
      if (status) where.status = status;
      if (type) where.type = type;
      if (priority) where.priority = priority;

      notifications = await liveStore.notifications.findMany({
        where,
        orderBy: { [orderBy]: order },
        take: limit,
        skip: offset
      });
    }

    // Fallback to local store
    if (notifications.length === 0) {
      const allRecords = await new Promise(resolve => {
        notificationsStore.subscribe(value => resolve(value))();
      });
      
      notifications = allRecords
        .filter(n => {
          if (n.recipientId !== userId) return false;
          if (status && n.status !== status) return false;
          if (type && n.type !== type) return false;
          if (priority && n.priority !== priority) return false;
          return true;
        })
        .sort((a, b) => {
          const aVal = new Date(a[orderBy]).getTime();
          const bVal = new Date(b[orderBy]).getTime();
          return order === 'desc' ? bVal - aVal : aVal - bVal;
        })
        .slice(offset, offset + limit);
    }

    return notifications.map(n => getNotificationData(n));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error getting notifications for user: ${errorMessage}`, 'error');
    return [];
  }
}

/**
 * Get current user's notifications
 * @param {Object} options - Query options
 * @returns {Promise<Object[]>} Array of notification data
 */
export async function getCurrentUserNotifications(options = {}) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      return [];
    }

    return await getNotificationsForUser(currentUser.id, options);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error getting current user notifications: ${errorMessage}`, 'error');
    return [];
  }
}

/**
 * Get unread notification count for user
 * @param {string} userId - User ID
 * @returns {Promise<number>} Unread notification count
 */
export async function getUnreadNotificationCount(userId) {
  try {
    const notifications = await getNotificationsForUser(userId, { 
      status: 'unread',
      limit: 1000 // Get all unread to count
    });
    return notifications.length;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error getting unread count: ${errorMessage}`, 'error');
    return 0;
  }
}

/**
 * Mark all notifications as read for user
 * @param {string} userId - User ID
 * @returns {Promise<number>} Number of notifications marked as read
 */
export async function markAllNotificationsAsRead(userId) {
  try {
    const unreadNotifications = await getNotificationsForUser(userId, { 
      status: 'unread',
      limit: 1000
    });

    let markedCount = 0;
    for (const notification of unreadNotifications) {
      await markNotificationAsRead(notification.id);
      markedCount++;
    }

    log(`[Notifications] Marked ${markedCount} notifications as read for user: ${userId}`, 'info');
    return markedCount;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Notifications] Error marking all as read: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: notificationsStore,
  getNotificationData,
  createNotification,
  updateNotification,
  markNotificationAsRead,
  dismissNotification,
  archiveNotification,
  trackNotificationOpen,
  trackNotificationClick,
  deleteNotification,
  getNotificationById,
  getNotificationsForUser,
  getCurrentUserNotifications,
  getUnreadNotificationCount,
  markAllNotificationsAsRead
}; 