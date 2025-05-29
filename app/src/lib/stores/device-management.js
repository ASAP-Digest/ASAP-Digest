/**
 * Device Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/** @type {import('svelte/store').Writable<Object[]>} */
export const deviceManagementStore = writable([]);

function normalizeDeviceManagementData(rawDeviceData) {
  if (!rawDeviceData || typeof rawDeviceData !== 'object' || !rawDeviceData.id) {
    return null;
  }

  return {
    id: rawDeviceData.id,
    userId: rawDeviceData.userId || rawDeviceData.user_id || null,
    registeredDevices: Array.isArray(rawDeviceData.registeredDevices) ? rawDeviceData.registeredDevices : [],
    devicePreferences: Array.isArray(rawDeviceData.devicePreferences) ? rawDeviceData.devicePreferences : [],
    syncStatus: Array.isArray(rawDeviceData.syncStatus) ? rawDeviceData.syncStatus : [],
    offlineData: Array.isArray(rawDeviceData.offlineData) ? rawDeviceData.offlineData : [],
    pushNotificationTokens: Array.isArray(rawDeviceData.pushNotificationTokens) ? rawDeviceData.pushNotificationTokens : [],
    deviceCapabilities: Array.isArray(rawDeviceData.deviceCapabilities) ? rawDeviceData.deviceCapabilities : [],
    crossDeviceContinuity: rawDeviceData.crossDeviceContinuity || {
      enabled: true,
      handoffEnabled: true,
      universalClipboard: false,
      sharedBookmarks: true
    },
    conflictResolution: rawDeviceData.conflictResolution || {
      strategy: 'last_modified_wins',
      manualReview: true,
      autoMerge: false
    },
    createdAt: rawDeviceData.createdAt || rawDeviceData.created_at || new Date().toISOString(),
    updatedAt: rawDeviceData.updatedAt || rawDeviceData.updated_at || new Date().toISOString(),
    metadata: rawDeviceData.metadata || {}
  };
}

export function getDeviceManagementData(deviceManagement) {
  const normalizedDeviceManagement = normalizeDeviceManagementData(deviceManagement);
  
  if (!normalizedDeviceManagement) {
    return createEmptyDeviceManagementHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedDeviceManagement.id; },
    get userId() { return normalizedDeviceManagement.userId; },
    
    // Device Management
    get registeredDevices() { return normalizedDeviceManagement.registeredDevices; },
    get deviceCount() { return this.registeredDevices.length; },
    get hasDevices() { return this.deviceCount > 0; },
    get activeDevices() {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return this.registeredDevices.filter(device => 
        device.isActive || new Date(device.lastSeen) > oneHourAgo
      );
    },
    get activeDeviceCount() { return this.activeDevices.length; },
    get mobileDevices() { return this.registeredDevices.filter(d => d.type === 'mobile'); },
    get desktopDevices() { return this.registeredDevices.filter(d => d.type === 'desktop'); },
    get mobileDeviceCount() { return this.mobileDevices.length; },
    get hasMobileDevices() { return this.mobileDeviceCount > 0; },
    
    // Sync Status
    get syncStatus() { return normalizedDeviceManagement.syncStatus; },
    get devicesSynced() { return this.syncStatus.filter(status => status.status === 'synced'); },
    get devicesFailed() { return this.syncStatus.filter(status => status.status === 'failed'); },
    get syncedDeviceCount() { return this.devicesSynced.length; },
    get failedSyncCount() { return this.devicesFailed.length; },
    get hasSyncFailures() { return this.failedSyncCount > 0; },
    get syncSuccessRate() {
      return this.syncStatus.length > 0 ? (this.syncedDeviceCount / this.syncStatus.length) * 100 : 0;
    },
    
    // Cross-Device Features
    get crossDeviceContinuity() { return normalizedDeviceManagement.crossDeviceContinuity; },
    get continuityEnabled() { return this.crossDeviceContinuity.enabled; },
    get handoffEnabled() { return this.crossDeviceContinuity.handoffEnabled; },
    
    // Push Notifications
    get pushNotificationTokens() { return normalizedDeviceManagement.pushNotificationTokens; },
    get pushTokenCount() { return this.pushNotificationTokens.length; },
    get hasPushTokens() { return this.pushTokenCount > 0; },
    
    // Overall Score
    get overallDeviceScore() {
      let score = 100;
      if (!this.hasDevices) score -= 30;
      if (this.hasSyncFailures) score -= 25;
      if (!this.continuityEnabled) score -= 20;
      if (!this.hasPushTokens) score -= 25;
      return Math.max(0, Math.min(100, Math.round(score)));
    },
    get deviceManagementRating() {
      const score = this.overallDeviceScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    get needsAttention() {
      return !this.hasDevices || this.hasSyncFailures;
    },
    
    // Timestamps
    get createdAt() { return normalizedDeviceManagement.createdAt; },
    get updatedAt() { return normalizedDeviceManagement.updatedAt; },
    get metadata() { return normalizedDeviceManagement.metadata; },
    
    // Validation
    get isValid() { return !!(this.id && this.userId); },
    get isComplete() { return this.isValid && this.hasDevices; },
    get isOperational() { return this.isComplete && this.overallDeviceScore >= 60; },
    
    // Utility Methods
    getDeviceById(deviceId) {
      return this.registeredDevices.find(device => device.id === deviceId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        deviceCount: this.deviceCount,
        activeDeviceCount: this.activeDeviceCount,
        syncSuccessRate: this.syncSuccessRate,
        overallDeviceScore: this.overallDeviceScore,
        needsAttention: this.needsAttention,
        isValid: this.isValid,
        isOperational: this.isOperational
      };
    },
    
    // Serialization
    toJSON() {
      return normalizedDeviceManagement;
    }
  };
}

function createEmptyDeviceManagementHelper() {
  return {
    get id() { return null; },
    get userId() { return null; },
    get registeredDevices() { return []; },
    get deviceCount() { return 0; },
    get hasDevices() { return false; },
    get syncSuccessRate() { return 0; },
    get overallDeviceScore() { return 0; },
    get deviceManagementRating() { return 'critical'; },
    get needsAttention() { return true; },
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    getDeviceById() { return null; },
    get debugInfo() { return { id: null, isValid: false }; },
    toJSON() { return { id: null, isNew: true }; }
  };
}

export async function createDeviceManagementConfig(configData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create device management config');
    }

    const newConfig = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...configData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    if (browser && liveStore) {
      await liveStore.deviceManagement.create(newConfig);
    }

    deviceManagementStore.update(configs => [...configs, newConfig]);
    log(`[DeviceManagement] Created device management config: ${newConfig.id}`, 'info');
    return getDeviceManagementData(newConfig);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[DeviceManagement] Error creating config: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: deviceManagementStore,
  getDeviceManagementData,
  createDeviceManagementConfig
}; 