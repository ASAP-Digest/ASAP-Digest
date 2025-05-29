/**
 * Data Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Data management business object for retention, backup, and compliance
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Enhanced Data Management object with comprehensive fields
 * @typedef {Object} DataManagementData
 * @property {string} id - Data management instance identifier
 * @property {string} userId - User identifier
 * @property {Object[]} retentionPolicies - Data retention policies
 * @property {Object[]} cleanupSchedules - Cleanup schedule configurations
 * @property {Object} backupStatus - Backup status information
 * @property {Object[]} recoveryPoints - Recovery point configurations
 * @property {Object[]} dataExports - Data export history
 * @property {Object} migrationStatus - Migration status tracking
 * @property {Object} storageUsage - Storage usage metrics
 * @property {Object[]} optimizationOpportunities - Storage optimization suggestions
 * @property {Object[]} complianceRequirements - Compliance requirement tracking
 * @property {Object[]} dataLineage - Data lineage tracking
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 */

/** @type {import('svelte/store').Writable<DataManagementData[]>} */
export const dataManagementStore = writable([]);

/**
 * Normalize data management data from any source to consistent format
 * @param {Object} rawDataMgmtData - Raw data management data
 * @returns {Object|null} Normalized data management data
 */
function normalizeDataManagementData(rawDataMgmtData) {
  if (!rawDataMgmtData || typeof rawDataMgmtData !== 'object' || !rawDataMgmtData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawDataMgmtData.id,
    userId: rawDataMgmtData.userId || rawDataMgmtData.user_id || null,
    
    // Retention & Cleanup
    retentionPolicies: Array.isArray(rawDataMgmtData.retentionPolicies) ? rawDataMgmtData.retentionPolicies :
                       Array.isArray(rawDataMgmtData.retention_policies) ? rawDataMgmtData.retention_policies : [],
    cleanupSchedules: Array.isArray(rawDataMgmtData.cleanupSchedules) ? rawDataMgmtData.cleanupSchedules :
                      Array.isArray(rawDataMgmtData.cleanup_schedules) ? rawDataMgmtData.cleanup_schedules : [],
    
    // Backup & Recovery
    backupStatus: rawDataMgmtData.backupStatus || rawDataMgmtData.backup_status || {
      lastBackup: null,
      nextBackup: null,
      backupSize: 0,
      status: 'unknown',
      errorCount: 0
    },
    recoveryPoints: Array.isArray(rawDataMgmtData.recoveryPoints) ? rawDataMgmtData.recoveryPoints :
                    Array.isArray(rawDataMgmtData.recovery_points) ? rawDataMgmtData.recovery_points : [],
    
    // Export & Migration
    dataExports: Array.isArray(rawDataMgmtData.dataExports) ? rawDataMgmtData.dataExports :
                 Array.isArray(rawDataMgmtData.data_exports) ? rawDataMgmtData.data_exports : [],
    migrationStatus: rawDataMgmtData.migrationStatus || rawDataMgmtData.migration_status || {
      inProgress: false,
      completedSteps: 0,
      totalSteps: 0,
      currentStep: null,
      errorCount: 0
    },
    
    // Storage & Optimization
    storageUsage: rawDataMgmtData.storageUsage || rawDataMgmtData.storage_usage || {
      totalUsed: 0,
      totalAvailable: 0,
      byCategory: {},
      growthRate: 0
    },
    optimizationOpportunities: Array.isArray(rawDataMgmtData.optimizationOpportunities) ? rawDataMgmtData.optimizationOpportunities :
                               Array.isArray(rawDataMgmtData.optimization_opportunities) ? rawDataMgmtData.optimization_opportunities : [],
    
    // Compliance & Lineage
    complianceRequirements: Array.isArray(rawDataMgmtData.complianceRequirements) ? rawDataMgmtData.complianceRequirements :
                            Array.isArray(rawDataMgmtData.compliance_requirements) ? rawDataMgmtData.compliance_requirements : [],
    dataLineage: Array.isArray(rawDataMgmtData.dataLineage) ? rawDataMgmtData.dataLineage :
                 Array.isArray(rawDataMgmtData.data_lineage) ? rawDataMgmtData.data_lineage : [],
    
    // Timestamps
    createdAt: rawDataMgmtData.createdAt || rawDataMgmtData.created_at || new Date().toISOString(),
    updatedAt: rawDataMgmtData.updatedAt || rawDataMgmtData.updated_at || new Date().toISOString(),
    
    // Metadata
    metadata: rawDataMgmtData.metadata || {}
  };
}

/**
 * Get comprehensive data management data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} dataMgmt - Raw data management data
 * @returns {Object} Data management helper with getters and methods
 */
export function getDataManagementData(dataMgmt) {
  const normalizedDataMgmt = normalizeDataManagementData(dataMgmt);
  
  if (!normalizedDataMgmt) {
    return createEmptyDataManagementHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedDataMgmt.id; },
    get userId() { return normalizedDataMgmt.userId; },
    
    // Retention Policies
    get retentionPolicies() { return normalizedDataMgmt.retentionPolicies; },
    get retentionPolicyCount() { return this.retentionPolicies.length; },
    get hasRetentionPolicies() { return this.retentionPolicyCount > 0; },
    get activeRetentionPolicies() { return this.retentionPolicies.filter(p => p.active); },
    get activeRetentionPolicyCount() { return this.activeRetentionPolicies.length; },
    
    // Cleanup Management
    get cleanupSchedules() { return normalizedDataMgmt.cleanupSchedules; },
    get cleanupScheduleCount() { return this.cleanupSchedules.length; },
    get hasCleanupSchedules() { return this.cleanupScheduleCount > 0; },
    get activeCleanupSchedules() { return this.cleanupSchedules.filter(s => s.enabled); },
    get nextCleanupRun() {
      const nextRuns = this.activeCleanupSchedules
        .map(s => s.nextRun)
        .filter(Boolean)
        .sort();
      return nextRuns.length > 0 ? nextRuns[0] : null;
    },
    
    // Backup Status
    get backupStatus() { return normalizedDataMgmt.backupStatus; },
    get lastBackup() { return this.backupStatus.lastBackup; },
    get nextBackup() { return this.backupStatus.nextBackup; },
    get backupSize() { return this.backupStatus.backupSize; },
    get backupStatusText() { return this.backupStatus.status; },
    get backupErrorCount() { return this.backupStatus.errorCount; },
    get hasRecentBackup() {
      if (!this.lastBackup) return false;
      const dayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
      return new Date(this.lastBackup) > dayAgo;
    },
    get backupHealthy() { return this.hasRecentBackup && this.backupErrorCount === 0; },
    
    // Recovery Points
    get recoveryPoints() { return normalizedDataMgmt.recoveryPoints; },
    get recoveryPointCount() { return this.recoveryPoints.length; },
    get hasRecoveryPoints() { return this.recoveryPointCount > 0; },
    get latestRecoveryPoint() {
      return this.recoveryPoints.length > 0 ? 
        this.recoveryPoints.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))[0] : null;
    },
    
    // Storage Usage
    get storageUsage() { return normalizedDataMgmt.storageUsage; },
    get totalUsedStorage() { return this.storageUsage.totalUsed; },
    get totalAvailableStorage() { return this.storageUsage.totalAvailable; },
    get storageUtilization() {
      return this.totalAvailableStorage > 0 ? 
        (this.totalUsedStorage / this.totalAvailableStorage) * 100 : 0;
    },
    get storageGrowthRate() { return this.storageUsage.growthRate; },
    get isStorageNearFull() { return this.storageUtilization > 85; },
    get storageByCategory() { return this.storageUsage.byCategory; },
    
    // Optimization
    get optimizationOpportunities() { return normalizedDataMgmt.optimizationOpportunities; },
    get optimizationCount() { return this.optimizationOpportunities.length; },
    get hasOptimizationOpportunities() { return this.optimizationCount > 0; },
    get potentialSavings() {
      return this.optimizationOpportunities.reduce((total, opp) => total + (opp.estimatedSavings || 0), 0);
    },
    
    // Compliance
    get complianceRequirements() { return normalizedDataMgmt.complianceRequirements; },
    get complianceRequirementCount() { return this.complianceRequirements.length; },
    get hasComplianceRequirements() { return this.complianceRequirementCount > 0; },
    get metComplianceRequirements() { return this.complianceRequirements.filter(r => r.status === 'met'); },
    get complianceScore() {
      return this.complianceRequirementCount > 0 ? 
        (this.metComplianceRequirements.length / this.complianceRequirementCount) * 100 : 100;
    },
    get isCompliant() { return this.complianceScore >= 95; },
    
    // Data Lineage
    get dataLineage() { return normalizedDataMgmt.dataLineage; },
    get dataLineageCount() { return this.dataLineage.length; },
    get hasDataLineage() { return this.dataLineageCount > 0; },
    
    // Data Export History
    get dataExports() { return normalizedDataMgmt.dataExports; },
    get dataExportCount() { return this.dataExports.length; },
    get hasDataExports() { return this.dataExportCount > 0; },
    get recentExports() {
      const monthAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
      return this.dataExports.filter(exp => new Date(exp.createdAt) > monthAgo);
    },
    get recentExportCount() { return this.recentExports.length; },
    
    // Migration Status
    get migrationStatus() { return normalizedDataMgmt.migrationStatus; },
    get migrationInProgress() { return this.migrationStatus.inProgress; },
    get migrationProgress() {
      return this.migrationStatus.totalSteps > 0 ? 
        (this.migrationStatus.completedSteps / this.migrationStatus.totalSteps) * 100 : 0;
    },
    get migrationErrors() { return this.migrationStatus.errorCount; },
    get migrationHealthy() { return !this.migrationInProgress || this.migrationErrors === 0; },
    
    // Overall Health Score
    get overallHealthScore() {
      let score = 100;
      
      // Backup health (0-30 points)
      if (!this.backupHealthy) score -= 30;
      else if (this.backupErrorCount > 0) score -= 10;
      
      // Storage management (0-25 points)
      if (this.isStorageNearFull) score -= 25;
      else if (this.storageUtilization > 70) score -= 10;
      
      // Compliance (0-25 points)
      if (!this.isCompliant) {
        score -= (100 - this.complianceScore) * 0.25;
      }
      
      // Migration health (0-20 points)
      if (!this.migrationHealthy) score -= 20;
      
      return Math.max(0, Math.min(100, Math.round(score)));
    },
    get healthRating() {
      const score = this.overallHealthScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    
    // Timestamps
    get createdAt() { return normalizedDataMgmt.createdAt; },
    get updatedAt() { return normalizedDataMgmt.updatedAt; },
    get metadata() { return normalizedDataMgmt.metadata; },
    
    // Validation
    get isValid() { return !!(this.id && this.userId); },
    get isComplete() { return this.isValid && this.hasRetentionPolicies; },
    get isOperational() { return this.isComplete && this.overallHealthScore >= 60; },
    
    // Utility Methods
    getRetentionPolicy(policyId) {
      return this.retentionPolicies.find(policy => policy.id === policyId);
    },
    getCleanupSchedule(scheduleId) {
      return this.cleanupSchedules.find(schedule => schedule.id === scheduleId);
    },
    getComplianceRequirement(reqId) {
      return this.complianceRequirements.find(req => req.id === reqId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        overallHealthScore: this.overallHealthScore,
        healthRating: this.healthRating,
        retentionPolicyCount: this.retentionPolicyCount,
        backupHealthy: this.backupHealthy,
        storageUtilization: this.storageUtilization,
        complianceScore: this.complianceScore,
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
        retentionPolicies: this.retentionPolicies,
        cleanupSchedules: this.cleanupSchedules,
        backupStatus: this.backupStatus,
        recoveryPoints: this.recoveryPoints,
        dataExports: this.dataExports,
        migrationStatus: this.migrationStatus,
        storageUsage: this.storageUsage,
        optimizationOpportunities: this.optimizationOpportunities,
        complianceRequirements: this.complianceRequirements,
        dataLineage: this.dataLineage,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        metadata: this.metadata
      };
    }
  };
}

/**
 * Create empty data management helper for null/undefined data
 * @returns {Object} Empty data management helper with safe defaults
 */
function createEmptyDataManagementHelper() {
  return {
    get id() { return null; },
    get userId() { return null; },
    get retentionPolicies() { return []; },
    get retentionPolicyCount() { return 0; },
    get hasRetentionPolicies() { return false; },
    get cleanupSchedules() { return []; },
    get backupStatus() { return { status: 'unknown' }; },
    get backupHealthy() { return false; },
    get storageUsage() { return { totalUsed: 0, totalAvailable: 0 }; },
    get storageUtilization() { return 0; },
    get complianceScore() { return 0; },
    get overallHealthScore() { return 0; },
    get healthRating() { return 'critical'; },
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    getRetentionPolicy(policyId) { return null; },
    getCleanupSchedule(scheduleId) { return null; },
    getComplianceRequirement(reqId) { return null; },
    get debugInfo() { return { id: null, isValid: false }; },
    toJSON() { return { id: null, isNew: true }; }
  };
}

/**
 * CRUD Operations for Data Management
 */

export async function createDataManagementConfig(configData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create data management config');
    }

    const newConfig = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...configData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    if (browser && liveStore) {
      await liveStore.dataManagement.create(newConfig);
    }

    dataManagementStore.update(configs => [...configs, newConfig]);
    log(`[DataMgmt] Created data management config: ${newConfig.id}`, 'info');
    return getDataManagementData(newConfig);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[DataMgmt] Error creating config: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: dataManagementStore,
  getDataManagementData,
  createDataManagementConfig
}; 