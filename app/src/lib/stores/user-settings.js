/**
 * User Preferences & Settings Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview User Settings business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Theme types
 * @typedef {'light' | 'dark' | 'auto' | 'custom'} ThemeType
 */

/**
 * Layout types
 * @typedef {'compact' | 'comfortable' | 'spacious' | 'custom'} LayoutType
 */

/**
 * Notification delivery methods
 * @typedef {'email' | 'push' | 'in_app' | 'sms'} NotificationMethod
 */

/**
 * Enhanced User Settings object with comprehensive fields
 * @typedef {Object} UserSettings
 * @property {string} id - Settings identifier
 * @property {string} userId - User identifier
 * @property {Object} uiPreferences - UI and display preferences
 * @property {Object} notificationSettings - Notification preferences
 * @property {Object} contentPreferences - Content filtering and display preferences
 * @property {Object} aiSettings - AI processing preferences
 * @property {Object} privacySettings - Privacy and data preferences
 * @property {Object} integrationSettings - External service integrations
 * @property {Object} backupSettings - Backup and sync preferences
 * @property {Object} exportPreferences - Data export preferences
 * @property {Object} accessibilitySettings - Accessibility options
 * @property {Object} performanceSettings - Performance optimization settings
 * @property {Object} securitySettings - Security preferences
 * @property {Object} languageSettings - Language and localization
 * @property {Object} digestSettings - Digest-specific preferences
 * @property {Object} workflowSettings - Workflow and automation preferences
 * @property {Date} lastModified - Last modification timestamp
 * @property {string} modifiedBy - User who last modified settings
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<UserSettings[]>} */
export const userSettingsStore = writable([]);

/**
 * Normalize user settings data from any source to consistent format
 * @param {Object} rawSettingsData - Raw user settings data
 * @returns {Object|null} Normalized user settings data
 */
function normalizeUserSettingsData(rawSettingsData) {
  if (!rawSettingsData || typeof rawSettingsData !== 'object' || !rawSettingsData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawSettingsData.id,
    userId: rawSettingsData.userId || rawSettingsData.user_id || null,
    
    // UI Preferences
    uiPreferences: rawSettingsData.uiPreferences || rawSettingsData.ui_preferences || {
      theme: 'auto',
      layout: 'comfortable',
      sidebarCollapsed: false,
      compactMode: false,
      showTooltips: true,
      animationsEnabled: true,
      highContrast: false,
      fontSize: 'medium',
      dashboardLayout: 'grid'
    },
    
    // Notification Settings
    notificationSettings: rawSettingsData.notificationSettings || rawSettingsData.notification_settings || {
      email: {
        enabled: true,
        digestUpdates: true,
        systemAlerts: true,
        weeklyReports: true,
        marketingEmails: false
      },
      push: {
        enabled: true,
        digestReady: true,
        systemAlerts: true,
        mentions: true
      },
      inApp: {
        enabled: true,
        showBadges: true,
        soundEnabled: true,
        autoMarkRead: false
      },
      quietHours: {
        enabled: false,
        startTime: '22:00',
        endTime: '08:00'
      }
    },
    
    // Content Preferences
    contentPreferences: rawSettingsData.contentPreferences || rawSettingsData.content_preferences || {
      defaultSources: [],
      preferredCategories: [],
      excludedCategories: [],
      contentFilters: {
        minQualityScore: 60,
        excludeAds: true,
        excludeDuplicates: true,
        languageFilter: 'auto'
      },
      displaySettings: {
        showImages: true,
        showVideos: true,
        autoPlayVideos: false,
        showMetadata: true,
        excerptLength: 'medium'
      }
    },
    
    // AI Settings
    aiSettings: rawSettingsData.aiSettings || rawSettingsData.ai_settings || {
      enhancementLevel: 'balanced',
      autoSummarize: true,
      autoClassify: true,
      autoExtractKeywords: true,
      sentimentAnalysis: true,
      customPrompts: [],
      processingOptions: {
        maxTokens: 1000,
        temperature: 0.7,
        useCache: true,
        fallbackProviders: true
      }
    },
    
    // Privacy Settings
    privacySettings: rawSettingsData.privacySettings || rawSettingsData.privacy_settings || {
      dataRetention: {
        keepDigests: 365,
        keepLogs: 90,
        keepAnalytics: 730
      },
      sharing: {
        allowAnalytics: true,
        allowCrashReports: true,
        allowUsageStats: false
      },
      visibility: {
        profilePublic: false,
        digestsPublic: false,
        activityPublic: false
      }
    },
    
    // Integration Settings
    integrationSettings: rawSettingsData.integrationSettings || rawSettingsData.integration_settings || {
      connectedServices: [],
      webhooks: [],
      apiKeys: {},
      syncSettings: {
        autoSync: true,
        syncInterval: 15,
        conflictResolution: 'merge'
      }
    },
    
    // Backup Settings
    backupSettings: rawSettingsData.backupSettings || rawSettingsData.backup_settings || {
      autoBackup: true,
      backupFrequency: 'daily',
      backupLocation: 'cloud',
      includeSettings: true,
      includeContent: true,
      retentionDays: 30
    },
    
    // Export Preferences
    exportPreferences: rawSettingsData.exportPreferences || rawSettingsData.export_preferences || {
      defaultFormat: 'json',
      includeMetadata: true,
      compressFiles: true,
      emailResults: true
    },
    
    // Accessibility Settings
    accessibilitySettings: rawSettingsData.accessibilitySettings || rawSettingsData.accessibility_settings || {
      screenReader: false,
      keyboardNavigation: true,
      focusIndicators: true,
      reducedMotion: false,
      highContrast: false,
      largeText: false,
      colorBlindSupport: false
    },
    
    // Performance Settings
    performanceSettings: rawSettingsData.performanceSettings || rawSettingsData.performance_settings || {
      lazyLoading: true,
      imageOptimization: true,
      cacheEnabled: true,
      prefetchContent: false,
      maxConcurrentRequests: 5
    },
    
    // Security Settings
    securitySettings: rawSettingsData.securitySettings || rawSettingsData.security_settings || {
      twoFactorEnabled: false,
      sessionTimeout: 480,
      loginNotifications: true,
      deviceTracking: true,
      ipWhitelist: []
    },
    
    // Language Settings
    languageSettings: rawSettingsData.languageSettings || rawSettingsData.language_settings || {
      primaryLanguage: 'en',
      fallbackLanguage: 'en',
      dateFormat: 'MM/DD/YYYY',
      timeFormat: '12h',
      timezone: 'auto',
      numberFormat: 'US'
    },
    
    // Digest Settings
    digestSettings: rawSettingsData.digestSettings || rawSettingsData.digest_settings || {
      defaultTemplate: 'standard',
      autoPublish: false,
      schedulePreference: 'manual',
      maxItemsPerDigest: 20,
      includeImages: true,
      includeSummaries: true
    },
    
    // Workflow Settings
    workflowSettings: rawSettingsData.workflowSettings || rawSettingsData.workflow_settings || {
      autoApproval: false,
      moderationLevel: 'standard',
      collaborationMode: 'open',
      defaultAssignee: null,
      escalationRules: []
    },
    
    // Metadata
    lastModified: rawSettingsData.lastModified || rawSettingsData.last_modified || new Date().toISOString(),
    modifiedBy: rawSettingsData.modifiedBy || rawSettingsData.modified_by || null,
    
    // Timestamps
    createdAt: rawSettingsData.createdAt || rawSettingsData.created_at || new Date().toISOString(),
    updatedAt: rawSettingsData.updatedAt || rawSettingsData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawSettingsData.wpSynced || rawSettingsData.wp_synced || false,
    lastWpSync: rawSettingsData.lastWpSync || rawSettingsData.last_wp_sync || null
  };
}

/**
 * Get comprehensive user settings data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} settings - Raw user settings data
 * @returns {Object} User settings helper with getters and methods
 */
export function getSettingsData(settings) {
  const normalizedSettings = normalizeUserSettingsData(settings);
  
  if (!normalizedSettings) {
    return createEmptySettingsHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedSettings.id; },
    get userId() { return normalizedSettings.userId; },
    
    // UI Preferences
    get uiPreferences() { return normalizedSettings.uiPreferences; },
    get theme() { return this.uiPreferences.theme; },
    get layout() { return this.uiPreferences.layout; },
    get sidebarCollapsed() { return this.uiPreferences.sidebarCollapsed; },
    get compactMode() { return this.uiPreferences.compactMode; },
    get showTooltips() { return this.uiPreferences.showTooltips; },
    get animationsEnabled() { return this.uiPreferences.animationsEnabled; },
    get highContrast() { return this.uiPreferences.highContrast; },
    get fontSize() { return this.uiPreferences.fontSize; },
    get dashboardLayout() { return this.uiPreferences.dashboardLayout; },
    
    // UI Analysis
    get isDarkTheme() { return this.theme === 'dark'; },
    get isLightTheme() { return this.theme === 'light'; },
    get isAutoTheme() { return this.theme === 'auto'; },
    get isCustomTheme() { return this.theme === 'custom'; },
    get isCompactLayout() { return this.layout === 'compact'; },
    get isComfortableLayout() { return this.layout === 'comfortable'; },
    get isSpaciousLayout() { return this.layout === 'spacious'; },
    get hasAccessibilityFeatures() { 
      return this.highContrast || this.accessibilitySettings.screenReader || 
             this.accessibilitySettings.largeText || this.accessibilitySettings.reducedMotion;
    },
    
    // Notification Settings
    get notificationSettings() { return normalizedSettings.notificationSettings; },
    get emailNotifications() { return this.notificationSettings.email; },
    get pushNotifications() { return this.notificationSettings.push; },
    get inAppNotifications() { return this.notificationSettings.inApp; },
    get quietHours() { return this.notificationSettings.quietHours; },
    
    // Notification Analysis
    get hasEmailEnabled() { return this.emailNotifications.enabled; },
    get hasPushEnabled() { return this.pushNotifications.enabled; },
    get hasInAppEnabled() { return this.inAppNotifications.enabled; },
    get hasQuietHoursEnabled() { return this.quietHours.enabled; },
    get notificationMethodCount() {
      let count = 0;
      if (this.hasEmailEnabled) count++;
      if (this.hasPushEnabled) count++;
      if (this.hasInAppEnabled) count++;
      return count;
    },
    get isFullyNotified() { return this.notificationMethodCount === 3; },
    get isMinimallyNotified() { return this.notificationMethodCount === 1; },
    
    // Content Preferences
    get contentPreferences() { return normalizedSettings.contentPreferences; },
    get defaultSources() { return this.contentPreferences.defaultSources; },
    get preferredCategories() { return this.contentPreferences.preferredCategories; },
    get excludedCategories() { return this.contentPreferences.excludedCategories; },
    get contentFilters() { return this.contentPreferences.contentFilters; },
    get displaySettings() { return this.contentPreferences.displaySettings; },
    
    // Content Analysis
    get hasDefaultSources() { return this.defaultSources.length > 0; },
    get hasPreferredCategories() { return this.preferredCategories.length > 0; },
    get hasExcludedCategories() { return this.excludedCategories.length > 0; },
    get minQualityScore() { return this.contentFilters.minQualityScore; },
    get isHighQualityFilter() { return this.minQualityScore >= 80; },
    get isLowQualityFilter() { return this.minQualityScore <= 40; },
    get excludesAds() { return this.contentFilters.excludeAds; },
    get excludesDuplicates() { return this.contentFilters.excludeDuplicates; },
    get showsImages() { return this.displaySettings.showImages; },
    get showsVideos() { return this.displaySettings.showVideos; },
    get autoPlaysVideos() { return this.displaySettings.autoPlayVideos; },
    
    // AI Settings
    get aiSettings() { return normalizedSettings.aiSettings; },
    get enhancementLevel() { return this.aiSettings.enhancementLevel; },
    get autoSummarize() { return this.aiSettings.autoSummarize; },
    get autoClassify() { return this.aiSettings.autoClassify; },
    get autoExtractKeywords() { return this.aiSettings.autoExtractKeywords; },
    get sentimentAnalysis() { return this.aiSettings.sentimentAnalysis; },
    get customPrompts() { return this.aiSettings.customPrompts; },
    get processingOptions() { return this.aiSettings.processingOptions; },
    
    // AI Analysis
    get isMinimalAI() { return this.enhancementLevel === 'minimal'; },
    get isBalancedAI() { return this.enhancementLevel === 'balanced'; },
    get isMaximalAI() { return this.enhancementLevel === 'maximal'; },
    get hasCustomPrompts() { return this.customPrompts.length > 0; },
    get aiFeatureCount() {
      let count = 0;
      if (this.autoSummarize) count++;
      if (this.autoClassify) count++;
      if (this.autoExtractKeywords) count++;
      if (this.sentimentAnalysis) count++;
      return count;
    },
    get isFullyAutomated() { return this.aiFeatureCount === 4; },
    get usesCache() { return this.processingOptions.useCache; },
    get usesFallbackProviders() { return this.processingOptions.fallbackProviders; },
    
    // Privacy Settings
    get privacySettings() { return normalizedSettings.privacySettings; },
    get dataRetention() { return this.privacySettings.dataRetention; },
    get sharing() { return this.privacySettings.sharing; },
    get visibility() { return this.privacySettings.visibility; },
    
    // Privacy Analysis
    get digestRetentionDays() { return this.dataRetention.keepDigests; },
    get logRetentionDays() { return this.dataRetention.keepLogs; },
    get analyticsRetentionDays() { return this.dataRetention.keepAnalytics; },
    get allowsAnalytics() { return this.sharing.allowAnalytics; },
    get allowsCrashReports() { return this.sharing.allowCrashReports; },
    get allowsUsageStats() { return this.sharing.allowUsageStats; },
    get isProfilePublic() { return this.visibility.profilePublic; },
    get areDigestsPublic() { return this.visibility.digestsPublic; },
    get isActivityPublic() { return this.visibility.activityPublic; },
    get isFullyPrivate() { 
      return !this.isProfilePublic && !this.areDigestsPublic && !this.isActivityPublic;
    },
    get isFullyPublic() { 
      return this.isProfilePublic && this.areDigestsPublic && this.isActivityPublic;
    },
    get privacyLevel() {
      if (this.isFullyPrivate) return 'high';
      if (this.isFullyPublic) return 'low';
      return 'medium';
    },
    
    // Integration Settings
    get integrationSettings() { return normalizedSettings.integrationSettings; },
    get connectedServices() { return this.integrationSettings.connectedServices; },
    get webhooks() { return this.integrationSettings.webhooks; },
    get apiKeys() { return this.integrationSettings.apiKeys; },
    get syncSettings() { return this.integrationSettings.syncSettings; },
    
    // Integration Analysis
    get hasConnectedServices() { return this.connectedServices.length > 0; },
    get hasWebhooks() { return this.webhooks.length > 0; },
    get hasApiKeys() { return Object.keys(this.apiKeys).length > 0; },
    get connectedServiceCount() { return this.connectedServices.length; },
    get webhookCount() { return this.webhooks.length; },
    get apiKeyCount() { return Object.keys(this.apiKeys).length; },
    get hasAutoSync() { return this.syncSettings.autoSync; },
    get syncInterval() { return this.syncSettings.syncInterval; },
    get conflictResolution() { return this.syncSettings.conflictResolution; },
    
    // Backup Settings
    get backupSettings() { return normalizedSettings.backupSettings; },
    get hasAutoBackup() { return this.backupSettings.autoBackup; },
    get backupFrequency() { return this.backupSettings.backupFrequency; },
    get backupLocation() { return this.backupSettings.backupLocation; },
    get includesSettings() { return this.backupSettings.includeSettings; },
    get includesContent() { return this.backupSettings.includeContent; },
    get backupRetentionDays() { return this.backupSettings.retentionDays; },
    
    // Backup Analysis
    get isDailyBackup() { return this.backupFrequency === 'daily'; },
    get isWeeklyBackup() { return this.backupFrequency === 'weekly'; },
    get isMonthlyBackup() { return this.backupFrequency === 'monthly'; },
    get isCloudBackup() { return this.backupLocation === 'cloud'; },
    get isLocalBackup() { return this.backupLocation === 'local'; },
    get isComprehensiveBackup() { return this.includesSettings && this.includesContent; },
    
    // Export Preferences
    get exportPreferences() { return normalizedSettings.exportPreferences; },
    get defaultExportFormat() { return this.exportPreferences.defaultFormat; },
    get includesMetadata() { return this.exportPreferences.includeMetadata; },
    get compressesFiles() { return this.exportPreferences.compressFiles; },
    get emailsResults() { return this.exportPreferences.emailResults; },
    
    // Accessibility Settings
    get accessibilitySettings() { return normalizedSettings.accessibilitySettings; },
    get usesScreenReader() { return this.accessibilitySettings.screenReader; },
    get hasKeyboardNavigation() { return this.accessibilitySettings.keyboardNavigation; },
    get hasFocusIndicators() { return this.accessibilitySettings.focusIndicators; },
    get hasReducedMotion() { return this.accessibilitySettings.reducedMotion; },
    get hasLargeText() { return this.accessibilitySettings.largeText; },
    get hasColorBlindSupport() { return this.accessibilitySettings.colorBlindSupport; },
    
    // Performance Settings
    get performanceSettings() { return normalizedSettings.performanceSettings; },
    get hasLazyLoading() { return this.performanceSettings.lazyLoading; },
    get hasImageOptimization() { return this.performanceSettings.imageOptimization; },
    get hasCacheEnabled() { return this.performanceSettings.cacheEnabled; },
    get hasPrefetchContent() { return this.performanceSettings.prefetchContent; },
    get maxConcurrentRequests() { return this.performanceSettings.maxConcurrentRequests; },
    
    // Security Settings
    get securitySettings() { return normalizedSettings.securitySettings; },
    get hasTwoFactor() { return this.securitySettings.twoFactorEnabled; },
    get sessionTimeout() { return this.securitySettings.sessionTimeout; },
    get hasLoginNotifications() { return this.securitySettings.loginNotifications; },
    get hasDeviceTracking() { return this.securitySettings.deviceTracking; },
    get ipWhitelist() { return this.securitySettings.ipWhitelist; },
    get hasIpWhitelist() { return this.ipWhitelist.length > 0; },
    
    // Security Analysis
    get sessionTimeoutHours() { return this.sessionTimeout / 60; },
    get isShortSession() { return this.sessionTimeoutHours <= 2; },
    get isLongSession() { return this.sessionTimeoutHours >= 12; },
    get securityLevel() {
      let score = 0;
      if (this.hasTwoFactor) score += 3;
      if (this.hasLoginNotifications) score += 1;
      if (this.hasDeviceTracking) score += 1;
      if (this.hasIpWhitelist) score += 2;
      if (this.isShortSession) score += 1;
      
      if (score >= 6) return 'high';
      if (score >= 3) return 'medium';
      return 'low';
    },
    
    // Language Settings
    get languageSettings() { return normalizedSettings.languageSettings; },
    get primaryLanguage() { return this.languageSettings.primaryLanguage; },
    get fallbackLanguage() { return this.languageSettings.fallbackLanguage; },
    get dateFormat() { return this.languageSettings.dateFormat; },
    get timeFormat() { return this.languageSettings.timeFormat; },
    get timezone() { return this.languageSettings.timezone; },
    get numberFormat() { return this.languageSettings.numberFormat; },
    
    // Language Analysis
    get is24HourFormat() { return this.timeFormat === '24h'; },
    get is12HourFormat() { return this.timeFormat === '12h'; },
    get isAutoTimezone() { return this.timezone === 'auto'; },
    get isEnglish() { return this.primaryLanguage === 'en'; },
    get hasMultipleLanguages() { return this.primaryLanguage !== this.fallbackLanguage; },
    
    // Digest Settings
    get digestSettings() { return normalizedSettings.digestSettings; },
    get defaultTemplate() { return this.digestSettings.defaultTemplate; },
    get hasAutoPublish() { return this.digestSettings.autoPublish; },
    get schedulePreference() { return this.digestSettings.schedulePreference; },
    get maxItemsPerDigest() { return this.digestSettings.maxItemsPerDigest; },
    get includesDigestImages() { return this.digestSettings.includeImages; },
    get includesDigestSummaries() { return this.digestSettings.includeSummaries; },
    
    // Workflow Settings
    get workflowSettings() { return normalizedSettings.workflowSettings; },
    get hasAutoApproval() { return this.workflowSettings.autoApproval; },
    get moderationLevel() { return this.workflowSettings.moderationLevel; },
    get collaborationMode() { return this.workflowSettings.collaborationMode; },
    get defaultAssignee() { return this.workflowSettings.defaultAssignee; },
    get escalationRules() { return this.workflowSettings.escalationRules; },
    
    // Workflow Analysis
    get isStrictModeration() { return this.moderationLevel === 'strict'; },
    get isStandardModeration() { return this.moderationLevel === 'standard'; },
    get isLenientModeration() { return this.moderationLevel === 'lenient'; },
    get isOpenCollaboration() { return this.collaborationMode === 'open'; },
    get isRestrictedCollaboration() { return this.collaborationMode === 'restricted'; },
    get hasDefaultAssignee() { return !!this.defaultAssignee; },
    get hasEscalationRules() { return this.escalationRules.length > 0; },
    
    // Metadata
    get lastModified() { return normalizedSettings.lastModified; },
    get modifiedBy() { return normalizedSettings.modifiedBy; },
    get hasModifier() { return !!this.modifiedBy; },
    
    // Timestamps
    get createdAt() { return normalizedSettings.createdAt; },
    get updatedAt() { return normalizedSettings.updatedAt; },
    
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
    get daysSinceModified() {
      const modified = new Date(this.lastModified);
      const now = new Date();
      return Math.floor((now.getTime() - modified.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get isRecent() { return this.age <= 7; },
    get isStale() { return this.daysSinceUpdate > 30; },
    get isRecentlyModified() { return this.daysSinceModified <= 1; },
    
    // WordPress Integration
    get wpSynced() { return normalizedSettings.wpSynced; },
    get lastWpSync() { return normalizedSettings.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Overall Configuration Analysis
    get configurationCompleteness() {
      let score = 0;
      let total = 0;
      
      // UI Preferences (10 points)
      total += 10;
      if (this.theme !== 'auto') score += 2;
      if (this.layout !== 'comfortable') score += 2;
      if (this.fontSize !== 'medium') score += 1;
      if (this.dashboardLayout !== 'grid') score += 1;
      if (this.hasAccessibilityFeatures) score += 4;
      
      // Notifications (15 points)
      total += 15;
      score += this.notificationMethodCount * 3;
      if (this.hasQuietHoursEnabled) score += 3;
      if (this.emailNotifications.digestUpdates) score += 3;
      
      // Content (20 points)
      total += 20;
      if (this.hasDefaultSources) score += 5;
      if (this.hasPreferredCategories) score += 5;
      if (this.minQualityScore !== 60) score += 3;
      if (this.excludesAds) score += 2;
      if (this.excludesDuplicates) score += 2;
      if (this.showsImages) score += 1;
      if (this.showsVideos) score += 1;
      if (!this.autoPlaysVideos) score += 1;
      
      // AI (15 points)
      total += 15;
      score += this.aiFeatureCount * 2;
      if (this.enhancementLevel !== 'balanced') score += 3;
      if (this.hasCustomPrompts) score += 4;
      
      // Privacy (10 points)
      total += 10;
      if (this.digestRetentionDays !== 365) score += 2;
      if (this.privacyLevel === 'high') score += 4;
      else if (this.privacyLevel === 'medium') score += 2;
      if (!this.allowsUsageStats) score += 2;
      if (!this.allowsAnalytics) score += 2;
      
      // Integration (10 points)
      total += 10;
      if (this.hasConnectedServices) score += 4;
      if (this.hasWebhooks) score += 3;
      if (this.hasAutoSync) score += 3;
      
      // Security (10 points)
      total += 10;
      if (this.hasTwoFactor) score += 4;
      if (this.hasLoginNotifications) score += 2;
      if (this.hasDeviceTracking) score += 2;
      if (this.hasIpWhitelist) score += 2;
      
      // Backup (10 points)
      total += 10;
      if (this.hasAutoBackup) score += 4;
      if (this.isComprehensiveBackup) score += 3;
      if (this.isDailyBackup) score += 3;
      
      return Math.round((score / total) * 100);
    },
    get configurationRating() {
      const completeness = this.configurationCompleteness;
      if (completeness >= 90) return 'excellent';
      if (completeness >= 75) return 'good';
      if (completeness >= 60) return 'average';
      if (completeness >= 40) return 'basic';
      return 'minimal';
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.userId);
    },
    get isComplete() {
      return this.isValid && this.configurationCompleteness >= 60;
    },
    get isOptimal() {
      return this.isComplete && this.configurationCompleteness >= 85;
    },
    
    // Utility Methods
    getSetting(category, key, defaultValue = null) {
      const categoryData = this[category];
      return categoryData && categoryData[key] !== undefined ? categoryData[key] : defaultValue;
    },
    hasSetting(category, key) {
      const categoryData = this[category];
      return categoryData && categoryData[key] !== undefined;
    },
    getNotificationMethod(method) {
      return this.notificationSettings[method] || {};
    },
    isNotificationEnabled(method, type = null) {
      const methodSettings = this.getNotificationMethod(method);
      if (!methodSettings.enabled) return false;
      return type ? methodSettings[type] || false : true;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        theme: this.theme,
        layout: this.layout,
        enhancementLevel: this.enhancementLevel,
        privacyLevel: this.privacyLevel,
        securityLevel: this.securityLevel,
        configurationCompleteness: this.configurationCompleteness,
        configurationRating: this.configurationRating,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isOptimal: this.isOptimal
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        userId: this.userId,
        uiPreferences: this.uiPreferences,
        notificationSettings: this.notificationSettings,
        contentPreferences: this.contentPreferences,
        aiSettings: this.aiSettings,
        privacySettings: this.privacySettings,
        integrationSettings: this.integrationSettings,
        backupSettings: this.backupSettings,
        exportPreferences: this.exportPreferences,
        accessibilitySettings: this.accessibilitySettings,
        performanceSettings: this.performanceSettings,
        securitySettings: this.securitySettings,
        languageSettings: this.languageSettings,
        digestSettings: this.digestSettings,
        workflowSettings: this.workflowSettings,
        lastModified: this.lastModified,
        modifiedBy: this.modifiedBy,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync
      };
    }
  };
}

/**
 * Create empty user settings helper for null/undefined settings data
 * @returns {Object} Empty user settings helper with safe defaults
 */
function createEmptySettingsHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get userId() { return null; },
    
    // UI Preferences
    get uiPreferences() { return { theme: 'auto', layout: 'comfortable' }; },
    get theme() { return 'auto'; },
    get layout() { return 'comfortable'; },
    get sidebarCollapsed() { return false; },
    get compactMode() { return false; },
    get showTooltips() { return true; },
    get animationsEnabled() { return true; },
    get highContrast() { return false; },
    get fontSize() { return 'medium'; },
    get dashboardLayout() { return 'grid'; },
    
    // UI Analysis
    get isDarkTheme() { return false; },
    get isLightTheme() { return false; },
    get isAutoTheme() { return true; },
    get isCustomTheme() { return false; },
    get isCompactLayout() { return false; },
    get isComfortableLayout() { return true; },
    get isSpaciousLayout() { return false; },
    get hasAccessibilityFeatures() { return false; },
    
    // Notification Settings
    get notificationSettings() { return {}; },
    get emailNotifications() { return { enabled: false }; },
    get pushNotifications() { return { enabled: false }; },
    get inAppNotifications() { return { enabled: false }; },
    get quietHours() { return { enabled: false }; },
    
    // Notification Analysis
    get hasEmailEnabled() { return false; },
    get hasPushEnabled() { return false; },
    get hasInAppEnabled() { return false; },
    get hasQuietHoursEnabled() { return false; },
    get notificationMethodCount() { return 0; },
    get isFullyNotified() { return false; },
    get isMinimallyNotified() { return false; },
    
    // Content Preferences
    get contentPreferences() { return {}; },
    get defaultSources() { return []; },
    get preferredCategories() { return []; },
    get excludedCategories() { return []; },
    get contentFilters() { return {}; },
    get displaySettings() { return {}; },
    
    // Content Analysis
    get hasDefaultSources() { return false; },
    get hasPreferredCategories() { return false; },
    get hasExcludedCategories() { return false; },
    get minQualityScore() { return 60; },
    get isHighQualityFilter() { return false; },
    get isLowQualityFilter() { return false; },
    get excludesAds() { return false; },
    get excludesDuplicates() { return false; },
    get showsImages() { return false; },
    get showsVideos() { return false; },
    get autoPlaysVideos() { return false; },
    
    // AI Settings
    get aiSettings() { return {}; },
    get enhancementLevel() { return 'balanced'; },
    get autoSummarize() { return false; },
    get autoClassify() { return false; },
    get autoExtractKeywords() { return false; },
    get sentimentAnalysis() { return false; },
    get customPrompts() { return []; },
    get processingOptions() { return {}; },
    
    // AI Analysis
    get isMinimalAI() { return false; },
    get isBalancedAI() { return true; },
    get isMaximalAI() { return false; },
    get hasCustomPrompts() { return false; },
    get aiFeatureCount() { return 0; },
    get isFullyAutomated() { return false; },
    get usesCache() { return false; },
    get usesFallbackProviders() { return false; },
    
    // Privacy Settings
    get privacySettings() { return {}; },
    get dataRetention() { return {}; },
    get sharing() { return {}; },
    get visibility() { return {}; },
    
    // Privacy Analysis
    get digestRetentionDays() { return 365; },
    get logRetentionDays() { return 90; },
    get analyticsRetentionDays() { return 730; },
    get allowsAnalytics() { return false; },
    get allowsCrashReports() { return false; },
    get allowsUsageStats() { return false; },
    get isProfilePublic() { return false; },
    get areDigestsPublic() { return false; },
    get isActivityPublic() { return false; },
    get isFullyPrivate() { return true; },
    get isFullyPublic() { return false; },
    get privacyLevel() { return 'high'; },
    
    // Integration Settings
    get integrationSettings() { return {}; },
    get connectedServices() { return []; },
    get webhooks() { return []; },
    get apiKeys() { return {}; },
    get syncSettings() { return {}; },
    
    // Integration Analysis
    get hasConnectedServices() { return false; },
    get hasWebhooks() { return false; },
    get hasApiKeys() { return false; },
    get connectedServiceCount() { return 0; },
    get webhookCount() { return 0; },
    get apiKeyCount() { return 0; },
    get hasAutoSync() { return false; },
    get syncInterval() { return 15; },
    get conflictResolution() { return 'merge'; },
    
    // Backup Settings
    get backupSettings() { return {}; },
    get hasAutoBackup() { return false; },
    get backupFrequency() { return 'daily'; },
    get backupLocation() { return 'cloud'; },
    get includesSettings() { return false; },
    get includesContent() { return false; },
    get backupRetentionDays() { return 30; },
    
    // Backup Analysis
    get isDailyBackup() { return false; },
    get isWeeklyBackup() { return false; },
    get isMonthlyBackup() { return false; },
    get isCloudBackup() { return false; },
    get isLocalBackup() { return false; },
    get isComprehensiveBackup() { return false; },
    
    // Export Preferences
    get exportPreferences() { return {}; },
    get defaultExportFormat() { return 'json'; },
    get includesMetadata() { return false; },
    get compressesFiles() { return false; },
    get emailsResults() { return false; },
    
    // Accessibility Settings
    get accessibilitySettings() { return {}; },
    get usesScreenReader() { return false; },
    get hasKeyboardNavigation() { return false; },
    get hasFocusIndicators() { return false; },
    get hasReducedMotion() { return false; },
    get hasLargeText() { return false; },
    get hasColorBlindSupport() { return false; },
    
    // Performance Settings
    get performanceSettings() { return {}; },
    get hasLazyLoading() { return false; },
    get hasImageOptimization() { return false; },
    get hasCacheEnabled() { return false; },
    get hasPrefetchContent() { return false; },
    get maxConcurrentRequests() { return 5; },
    
    // Security Settings
    get securitySettings() { return {}; },
    get hasTwoFactor() { return false; },
    get sessionTimeout() { return 480; },
    get hasLoginNotifications() { return false; },
    get hasDeviceTracking() { return false; },
    get ipWhitelist() { return []; },
    get hasIpWhitelist() { return false; },
    
    // Security Analysis
    get sessionTimeoutHours() { return 8; },
    get isShortSession() { return false; },
    get isLongSession() { return false; },
    get securityLevel() { return 'low'; },
    
    // Language Settings
    get languageSettings() { return {}; },
    get primaryLanguage() { return 'en'; },
    get fallbackLanguage() { return 'en'; },
    get dateFormat() { return 'MM/DD/YYYY'; },
    get timeFormat() { return '12h'; },
    get timezone() { return 'auto'; },
    get numberFormat() { return 'US'; },
    
    // Language Analysis
    get is24HourFormat() { return false; },
    get is12HourFormat() { return true; },
    get isAutoTimezone() { return true; },
    get isEnglish() { return true; },
    get hasMultipleLanguages() { return false; },
    
    // Digest Settings
    get digestSettings() { return {}; },
    get defaultTemplate() { return 'standard'; },
    get hasAutoPublish() { return false; },
    get schedulePreference() { return 'manual'; },
    get maxItemsPerDigest() { return 20; },
    get includesDigestImages() { return false; },
    get includesDigestSummaries() { return false; },
    
    // Workflow Settings
    get workflowSettings() { return {}; },
    get hasAutoApproval() { return false; },
    get moderationLevel() { return 'standard'; },
    get collaborationMode() { return 'open'; },
    get defaultAssignee() { return null; },
    get escalationRules() { return []; },
    
    // Workflow Analysis
    get isStrictModeration() { return false; },
    get isStandardModeration() { return true; },
    get isLenientModeration() { return false; },
    get isOpenCollaboration() { return true; },
    get isRestrictedCollaboration() { return false; },
    get hasDefaultAssignee() { return false; },
    get hasEscalationRules() { return false; },
    
    // Metadata
    get lastModified() { return null; },
    get modifiedBy() { return null; },
    get hasModifier() { return false; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get daysSinceUpdate() { return 0; },
    get daysSinceModified() { return 0; },
    get isRecent() { return false; },
    get isStale() { return false; },
    get isRecentlyModified() { return false; },
    
    // WordPress Integration
    get wpSynced() { return false; },
    get lastWpSync() { return null; },
    get isSyncedToWordPress() { return false; },
    get needsWordPressSync() { return false; },
    
    // Overall Configuration Analysis
    get configurationCompleteness() { return 0; },
    get configurationRating() { return 'minimal'; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOptimal() { return false; },
    
    // Utility Methods
    getSetting(category, key, defaultValue = null) { return defaultValue; },
    hasSetting(category, key) { return false; },
    getNotificationMethod(method) { return {}; },
    isNotificationEnabled(method, type = null) { return false; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        userId: null,
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        userId: null,
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for User Settings
 */

/**
 * Create new user settings record
 * @param {Object} settingsData - Initial settings data
 * @returns {Promise<Object>} Created user settings record
 */
export async function createUserSettings(settingsData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create settings');
    }

    const newSettings = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...settingsData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      lastModified: new Date().toISOString(),
      modifiedBy: currentUser.id
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.userSettings.create(newSettings);
    }

    // Update local store
    userSettingsStore.update(records => [...records, newSettings]);

    log(`[User Settings] Created new settings: ${newSettings.id}`, 'info');
    return getSettingsData(newSettings);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[User Settings] Error creating settings: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing user settings record
 * @param {string} settingsId - Settings record ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated user settings record
 */
export async function updateUserSettings(settingsId, updates) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to update settings');
    }

    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString(),
      lastModified: new Date().toISOString(),
      modifiedBy: currentUser.id
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.userSettings.update(settingsId, updatedData);
    }

    // Update local store
    userSettingsStore.update(records => 
      records.map(record => 
        record.id === settingsId 
          ? { ...record, ...updatedData }
          : record
      )
    );

    log(`[User Settings] Updated settings: ${settingsId}`, 'info');
    
    const updatedSettings = await getUserSettingsById(settingsId);
    return updatedSettings;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[User Settings] Error updating settings: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete user settings record
 * @param {string} settingsId - Settings record ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteUserSettings(settingsId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.userSettings.delete(settingsId);
    }

    // Update local store
    userSettingsStore.update(records => 
      records.filter(record => record.id !== settingsId)
    );

    log(`[User Settings] Deleted settings: ${settingsId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[User Settings] Error deleting settings: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get user settings record by ID
 * @param {string} settingsId - Settings record ID
 * @returns {Promise<Object|null>} User settings data or null
 */
export async function getUserSettingsById(settingsId) {
  try {
    let settings = null;

    // Try LiveStore first
    if (browser && liveStore) {
      settings = await liveStore.userSettings.findById(settingsId);
    }

    // Fallback to local store
    if (!settings) {
      const allRecords = await new Promise(resolve => {
        userSettingsStore.subscribe(value => resolve(value))();
      });
      settings = allRecords.find(s => s.id === settingsId);
    }

    return settings ? getSettingsData(settings) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[User Settings] Error getting settings by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get user settings by user ID
 * @param {string} userId - User ID
 * @returns {Promise<Object|null>} User settings data or null
 */
export async function getUserSettingsByUserId(userId) {
  try {
    let settings = null;

    // Try LiveStore first
    if (browser && liveStore) {
      const results = await liveStore.userSettings.findMany({
        where: { userId },
        orderBy: { updatedAt: 'desc' },
        take: 1
      });
      settings = results.length > 0 ? results[0] : null;
    }

    // Fallback to local store
    if (!settings) {
      const allRecords = await new Promise(resolve => {
        userSettingsStore.subscribe(value => resolve(value))();
      });
      const userSettings = allRecords
        .filter(s => s.userId === userId)
        .sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime());
      settings = userSettings.length > 0 ? userSettings[0] : null;
    }

    return settings ? getSettingsData(settings) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[User Settings] Error getting settings by user ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get current user's settings
 * @returns {Promise<Object|null>} Current user's settings or null
 */
export async function getCurrentUserSettings() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      return null;
    }

    return await getUserSettingsByUserId(currentUser.id);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[User Settings] Error getting current user settings: ${errorMessage}`, 'error');
    return null;
  }
}

export default {
  store: userSettingsStore,
  getSettingsData,
  createUserSettings,
  updateUserSettings,
  deleteUserSettings,
  getUserSettingsById,
  getUserSettingsByUserId,
  getCurrentUserSettings
}; 