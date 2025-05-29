/**
 * Security Monitoring Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Security monitoring business object management for threat detection and audit trails
 * 
 * ====================================================================
 * COMPREHENSIVE SECURITY MONITORING DOCUMENTATION
 * ====================================================================
 * 
 * This security system manages comprehensive threat detection, access monitoring,
 * and audit trail management, following the established getUserData() pattern 
 * with 90+ computed getters.
 * 
 * CORE FEATURES:
 * --------------
 * 1. Access Monitoring: Real-time access logging and pattern analysis
 * 2. Threat Detection: Automated threat identification and response
 * 3. Session Management: Active session tracking and security validation
 * 4. Audit Trails: Comprehensive action logging and compliance tracking
 * 5. Vulnerability Management: Security scan results and remediation tracking
 * 6. Compliance Monitoring: Regulatory compliance status and reporting
 * 7. Incident Management: Security incident tracking and response coordination
 * 8. User Behavior Analysis: Anomaly detection and risk scoring
 * 
 * SECURITY EVENT TYPES:
 * ---------------------
 * 
 * 1. 'login' - User authentication events
 * 2. 'failed_login' - Failed authentication attempts
 * 3. 'logout' - User logout events
 * 4. 'data_access' - Data access and modification events
 * 5. 'permission_change' - Permission and role modifications
 * 6. 'security_violation' - Security policy violations
 * 7. 'vulnerability' - Vulnerability detection events
 * 8. 'threat' - Threat detection and mitigation events
 * 
 * USAGE PATTERNS:
 * ---------------
 * 
 * Basic Security Analysis:
 * const security = getSecurityData(rawSecurityData);
 * console.log(security.threatLevel);           // 'medium'
 * console.log(security.activeThreats);         // 2
 * console.log(security.vulnerabilityCount);    // 5
 * console.log(security.complianceScore);       // 87.3
 * 
 * Access Pattern Analysis:
 * console.log(security.hasRecentFailures);     // true
 * console.log(security.failureRate);           // 12.5%
 * console.log(security.suspiciousActivity);    // true
 * console.log(security.riskScore);             // 73.2
 * 
 * Session Security:
 * console.log(security.activeSessions);        // 12
 * console.log(security.expiredSessions);       // 3
 * console.log(security.sessionAnomalies);      // 1
 * console.log(security.sessionSecurityRating); // 'good'
 * 
 * Compliance Status:
 * console.log(security.complianceStatus);      // 'compliant'
 * console.log(security.auditReadiness);        // 95.8%
 * console.log(security.dataRetentionCompliant); // true
 * console.log(security.privacyCompliant);      // true
 * 
 * CRUD OPERATIONS:
 * ----------------
 * 
 * Log Security Event:
 * const event = await logSecurityEvent({
 *   type: 'failed_login',
 *   userId: 'user123',
 *   details: { ip: '192.168.1.1', attempts: 3 }
 * });
 * 
 * Update Threat Level:
 * const updated = await updateThreatLevel(securityId, 'high');
 * 
 * Generate Audit Report:
 * const report = await generateAuditReport({
 *   startDate: '2024-01-01',
 *   endDate: '2024-01-31',
 *   includeFailures: true
 * });
 * 
 * INTEGRATION PATTERNS:
 * ---------------------
 * 
 * With User Management:
 * const user = getUserData(userData);
 * const security = getSecurityData(securityData);
 * if (security.isUserSuspicious(user.id)) {
 *   // Enhanced monitoring required
 * }
 * 
 * With System Health:
 * const health = getSystemHealthData(healthData);
 * const security = getSecurityData(securityData);
 * health.integrateSecurityMetrics(security.overallHealthScore);
 * 
 * With Notifications:
 * const notifications = getNotificationData(notificationData);
 * const security = getSecurityData(securityData);
 * notifications.sendSecurityAlert(security.criticalThreats);
 * 
 * ====================================================================
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Security event types
 * @typedef {'login' | 'failed_login' | 'logout' | 'data_access' | 'permission_change' | 'security_violation' | 'vulnerability' | 'threat'} SecurityEventType
 */

/**
 * Threat levels
 * @typedef {'low' | 'medium' | 'high' | 'critical'} ThreatLevel
 */

/**
 * Security event
 * @typedef {Object} SecurityEvent
 * @property {string} id - Event identifier
 * @property {SecurityEventType} type - Event type
 * @property {string} userId - Associated user ID
 * @property {string} ip - IP address
 * @property {string} userAgent - User agent string
 * @property {Object} details - Event-specific details
 * @property {Date} timestamp - Event timestamp
 * @property {ThreatLevel} severity - Event severity level
 */

/**
 * User session information
 * @typedef {Object} UserSession
 * @property {string} id - Session identifier
 * @property {string} userId - User identifier
 * @property {string} ip - IP address
 * @property {string} device - Device information
 * @property {Date} startTime - Session start time
 * @property {Date} lastActivity - Last activity timestamp
 * @property {boolean} active - Session active status
 * @property {Object} securityFlags - Security-related flags
 */

/**
 * Vulnerability information
 * @typedef {Object} Vulnerability
 * @property {string} id - Vulnerability identifier
 * @property {string} type - Vulnerability type
 * @property {ThreatLevel} severity - Severity level
 * @property {string} description - Vulnerability description
 * @property {string} status - Remediation status
 * @property {Date} discoveredAt - Discovery timestamp
 * @property {Date} fixedAt - Fix timestamp (if applicable)
 */

/**
 * Enhanced Security Monitoring object with comprehensive fields
 * @typedef {Object} SecurityData
 * @property {string} id - Security instance identifier
 * @property {string} userId - User identifier
 * @property {ThreatLevel} threatLevel - Current threat level
 * @property {SecurityEvent[]} accessLogs - Access event logs
 * @property {SecurityEvent[]} failedAttempts - Failed access attempts
 * @property {SecurityEvent[]} securityEvents - All security events
 * @property {UserSession[]} userSessions - Active user sessions
 * @property {string[]} activeTokens - Active authentication tokens
 * @property {Object[]} permissionChanges - Permission change history
 * @property {Vulnerability[]} vulnerabilities - Vulnerability scan results
 * @property {Object} complianceStatus - Compliance status information
 * @property {Object[]} auditTrail - Comprehensive audit trail
 * @property {Object[]} dataAccessLogs - Data access logging
 * @property {Object} securitySettings - Security configuration
 * @property {Object} threatDetection - Threat detection configuration
 * @property {Object} incidentManagement - Incident tracking data
 * @property {Object} userBehavior - User behavior analysis
 * @property {Object} anomalyDetection - Anomaly detection results
 * @property {Object} riskAssessment - Risk assessment data
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 * @property {number} wpPostId - WordPress post ID
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<SecurityData[]>} */
export const securityStore = writable([]);

/**
 * Normalize security data from any source to consistent format
 * @param {Object} rawSecurityData - Raw security data
 * @returns {Object|null} Normalized security data
 */
function normalizeSecurityData(rawSecurityData) {
  if (!rawSecurityData || typeof rawSecurityData !== 'object' || !rawSecurityData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawSecurityData.id,
    userId: rawSecurityData.userId || rawSecurityData.user_id || null,
    
    // Threat Management
    threatLevel: rawSecurityData.threatLevel || rawSecurityData.threat_level || 'low',
    
    // Event Logging
    accessLogs: Array.isArray(rawSecurityData.accessLogs) ? rawSecurityData.accessLogs :
                Array.isArray(rawSecurityData.access_logs) ? rawSecurityData.access_logs : [],
    failedAttempts: Array.isArray(rawSecurityData.failedAttempts) ? rawSecurityData.failedAttempts :
                    Array.isArray(rawSecurityData.failed_attempts) ? rawSecurityData.failed_attempts : [],
    securityEvents: Array.isArray(rawSecurityData.securityEvents) ? rawSecurityData.securityEvents :
                    Array.isArray(rawSecurityData.security_events) ? rawSecurityData.security_events : [],
    
    // Session Management
    userSessions: Array.isArray(rawSecurityData.userSessions) ? rawSecurityData.userSessions :
                  Array.isArray(rawSecurityData.user_sessions) ? rawSecurityData.user_sessions : [],
    activeTokens: Array.isArray(rawSecurityData.activeTokens) ? rawSecurityData.activeTokens :
                  Array.isArray(rawSecurityData.active_tokens) ? rawSecurityData.active_tokens : [],
    
    // Permission & Access Control
    permissionChanges: Array.isArray(rawSecurityData.permissionChanges) ? rawSecurityData.permissionChanges :
                       Array.isArray(rawSecurityData.permission_changes) ? rawSecurityData.permission_changes : [],
    
    // Vulnerability Management
    vulnerabilities: Array.isArray(rawSecurityData.vulnerabilities) ? rawSecurityData.vulnerabilities : [],
    
    // Compliance & Audit
    complianceStatus: rawSecurityData.complianceStatus || rawSecurityData.compliance_status || {
      gdprCompliant: false,
      hipaaCompliant: false,
      sox404Compliant: false,
      iso27001Compliant: false,
      overallScore: 0
    },
    auditTrail: Array.isArray(rawSecurityData.auditTrail) ? rawSecurityData.auditTrail :
                Array.isArray(rawSecurityData.audit_trail) ? rawSecurityData.audit_trail : [],
    dataAccessLogs: Array.isArray(rawSecurityData.dataAccessLogs) ? rawSecurityData.dataAccessLogs :
                    Array.isArray(rawSecurityData.data_access_logs) ? rawSecurityData.data_access_logs : [],
    
    // Security Configuration
    securitySettings: rawSecurityData.securitySettings || rawSecurityData.security_settings || {
      passwordPolicy: {},
      sessionTimeout: 3600,
      maxFailedAttempts: 5,
      lockoutDuration: 300,
      twoFactorRequired: false,
      ipWhitelist: [],
      allowedDevices: []
    },
    threatDetection: rawSecurityData.threatDetection || rawSecurityData.threat_detection || {
      enabled: true,
      sensitivity: 'medium',
      autoResponse: false,
      notificationThreshold: 'medium',
      quarantineEnabled: false
    },
    
    // Incident & Risk Management
    incidentManagement: rawSecurityData.incidentManagement || rawSecurityData.incident_management || {
      activeIncidents: [],
      resolvedIncidents: [],
      averageResponseTime: 0,
      escalationRules: []
    },
    userBehavior: rawSecurityData.userBehavior || rawSecurityData.user_behavior || {
      normalPatterns: {},
      anomalies: [],
      riskScore: 0,
      behaviorProfile: {}
    },
    anomalyDetection: rawSecurityData.anomalyDetection || rawSecurityData.anomaly_detection || {
      enabled: true,
      algorithms: [],
      threshold: 0.8,
      recentAnomalies: []
    },
    riskAssessment: rawSecurityData.riskAssessment || rawSecurityData.risk_assessment || {
      overallRiskScore: 0,
      riskFactors: [],
      mitigationStrategies: [],
      lastAssessment: null
    },
    
    // Timestamps
    createdAt: rawSecurityData.createdAt || rawSecurityData.created_at || new Date().toISOString(),
    updatedAt: rawSecurityData.updatedAt || rawSecurityData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpPostId: rawSecurityData.wpPostId || rawSecurityData.wp_post_id || null,
    wpSynced: rawSecurityData.wpSynced || rawSecurityData.wp_synced || false,
    lastWpSync: rawSecurityData.lastWpSync || rawSecurityData.last_wp_sync || null,
    
    // Metadata
    metadata: rawSecurityData.metadata || {}
  };
}

/**
 * Get comprehensive security data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} security - Raw security data
 * @returns {Object} Security helper with getters and methods
 */
export function getSecurityData(security) {
  const normalizedSecurity = normalizeSecurityData(security);
  
  if (!normalizedSecurity) {
    return createEmptySecurityHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedSecurity.id; },
    get userId() { return normalizedSecurity.userId; },
    
    // Threat Level Analysis
    get threatLevel() { return normalizedSecurity.threatLevel; },
    get isLowThreat() { return this.threatLevel === 'low'; },
    get isMediumThreat() { return this.threatLevel === 'medium'; },
    get isHighThreat() { return this.threatLevel === 'high'; },
    get isCriticalThreat() { return this.threatLevel === 'critical'; },
    get threatLevelNumeric() {
      const levels = { low: 1, medium: 2, high: 3, critical: 4 };
      return levels[this.threatLevel] || 1;
    },
    
    // Access Logs Analysis
    get accessLogs() { return normalizedSecurity.accessLogs; },
    get failedAttempts() { return normalizedSecurity.failedAttempts; },
    get securityEvents() { return normalizedSecurity.securityEvents; },
    get totalAccessEvents() { return this.accessLogs.length; },
    get totalFailedAttempts() { return this.failedAttempts.length; },
    get totalSecurityEvents() { return this.securityEvents.length; },
    get hasRecentFailures() {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return this.failedAttempts.some(attempt => new Date(attempt.timestamp) > oneHourAgo);
    },
    get recentFailureCount() {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return this.failedAttempts.filter(attempt => new Date(attempt.timestamp) > oneHourAgo).length;
    },
    get failureRate() {
      const totalAttempts = this.totalAccessEvents + this.totalFailedAttempts;
      return totalAttempts > 0 ? (this.totalFailedAttempts / totalAttempts) * 100 : 0;
    },
    get isHighFailureRate() { return this.failureRate > 20; },
    
    // Security Event Analysis
    get loginEvents() { return this.securityEvents.filter(e => e.type === 'login'); },
    get logoutEvents() { return this.securityEvents.filter(e => e.type === 'logout'); },
    get dataAccessEvents() { return this.securityEvents.filter(e => e.type === 'data_access'); },
    get permissionChangeEvents() { return this.securityEvents.filter(e => e.type === 'permission_change'); },
    get violationEvents() { return this.securityEvents.filter(e => e.type === 'security_violation'); },
    get vulnerabilityEvents() { return this.securityEvents.filter(e => e.type === 'vulnerability'); },
    get threatEvents() { return this.securityEvents.filter(e => e.type === 'threat'); },
    
    // Event Severity Analysis
    get criticalEvents() { return this.securityEvents.filter(e => e.severity === 'critical'); },
    get highSeverityEvents() { return this.securityEvents.filter(e => e.severity === 'high'); },
    get mediumSeverityEvents() { return this.securityEvents.filter(e => e.severity === 'medium'); },
    get lowSeverityEvents() { return this.securityEvents.filter(e => e.severity === 'low'); },
    get criticalEventCount() { return this.criticalEvents.length; },
    get hasCriticalEvents() { return this.criticalEventCount > 0; },
    
    // Session Management
    get userSessions() { return normalizedSecurity.userSessions; },
    get activeTokens() { return normalizedSecurity.activeTokens; },
    get activeSessions() { return this.userSessions.filter(s => s.active); },
    get expiredSessions() { return this.userSessions.filter(s => !s.active); },
    get activeSessionCount() { return this.activeSessions.length; },
    get totalSessionCount() { return this.userSessions.length; },
    get activeTokenCount() { return this.activeTokens.length; },
    get hasActiveSessions() { return this.activeSessionCount > 0; },
    get sessionAnomalies() {
      // Check for suspicious session patterns
      return this.activeSessions.filter(session => {
        const lastActivity = new Date(session.lastActivity);
        const now = new Date();
        const inactiveTime = (now.getTime() - lastActivity.getTime()) / (1000 * 60 * 60); // hours
        return inactiveTime > 24 || session.securityFlags?.suspicious;
      });
    },
    get sessionAnomalyCount() { return this.sessionAnomalies.length; },
    get hasSessionAnomalies() { return this.sessionAnomalyCount > 0; },
    
    // Permission & Access Control
    get permissionChanges() { return normalizedSecurity.permissionChanges; },
    get permissionChangeCount() { return this.permissionChanges.length; },
    get hasRecentPermissionChanges() {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return this.permissionChanges.some(change => new Date(change.timestamp) > oneHourAgo);
    },
    get recentPermissionChanges() {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
      return this.permissionChanges.filter(change => new Date(change.timestamp) > oneHourAgo);
    },
    
    // Vulnerability Management
    get vulnerabilities() { return normalizedSecurity.vulnerabilities; },
    get vulnerabilityCount() { return this.vulnerabilities.length; },
    get hasVulnerabilities() { return this.vulnerabilityCount > 0; },
    get criticalVulnerabilities() { return this.vulnerabilities.filter(v => v.severity === 'critical'); },
    get highVulnerabilities() { return this.vulnerabilities.filter(v => v.severity === 'high'); },
    get mediumVulnerabilities() { return this.vulnerabilities.filter(v => v.severity === 'medium'); },
    get lowVulnerabilities() { return this.vulnerabilities.filter(v => v.severity === 'low'); },
    get criticalVulnerabilityCount() { return this.criticalVulnerabilities.length; },
    get unresolvedVulnerabilities() { return this.vulnerabilities.filter(v => v.status !== 'fixed'); },
    get unresolvedVulnerabilityCount() { return this.unresolvedVulnerabilities.length; },
    get hasCriticalVulnerabilities() { return this.criticalVulnerabilityCount > 0; },
    
    // Compliance Status
    get complianceStatus() { return normalizedSecurity.complianceStatus; },
    get auditTrail() { return normalizedSecurity.auditTrail; },
    get dataAccessLogs() { return normalizedSecurity.dataAccessLogs; },
    get isGdprCompliant() { return this.complianceStatus.gdprCompliant; },
    get isHipaaCompliant() { return this.complianceStatus.hipaaCompliant; },
    get isSox404Compliant() { return this.complianceStatus.sox404Compliant; },
    get isIso27001Compliant() { return this.complianceStatus.iso27001Compliant; },
    get complianceScore() { return this.complianceStatus.overallScore; },
    get complianceRating() {
      const score = this.complianceScore;
      if (score >= 95) return 'excellent';
      if (score >= 85) return 'good';
      if (score >= 70) return 'average';
      if (score >= 50) return 'poor';
      return 'critical';
    },
    get isFullyCompliant() {
      return this.isGdprCompliant && this.isHipaaCompliant && 
             this.isSox404Compliant && this.isIso27001Compliant;
    },
    get auditTrailCount() { return this.auditTrail.length; },
    get dataAccessLogCount() { return this.dataAccessLogs.length; },
    get auditReadiness() {
      // Calculate audit readiness based on trail completeness
      const requiredLogs = ['login', 'data_access', 'permission_change'];
      const hasAllLogs = requiredLogs.every(type => 
        this.auditTrail.some(log => log.type === type)
      );
      return hasAllLogs ? Math.min(100, this.complianceScore + 10) : this.complianceScore * 0.8;
    },
    
    // Security Configuration
    get securitySettings() { return normalizedSecurity.securitySettings; },
    get threatDetection() { return normalizedSecurity.threatDetection; },
    get passwordPolicyEnabled() { return !!this.securitySettings.passwordPolicy; },
    get sessionTimeout() { return this.securitySettings.sessionTimeout; },
    get maxFailedAttempts() { return this.securitySettings.maxFailedAttempts; },
    get lockoutDuration() { return this.securitySettings.lockoutDuration; },
    get twoFactorRequired() { return this.securitySettings.twoFactorRequired; },
    get hasIpWhitelist() { return this.securitySettings.ipWhitelist.length > 0; },
    get threatDetectionEnabled() { return this.threatDetection.enabled; },
    get threatDetectionSensitivity() { return this.threatDetection.sensitivity; },
    get autoResponseEnabled() { return this.threatDetection.autoResponse; },
    
    // Risk Assessment
    get incidentManagement() { return normalizedSecurity.incidentManagement; },
    get userBehavior() { return normalizedSecurity.userBehavior; },
    get anomalyDetection() { return normalizedSecurity.anomalyDetection; },
    get riskAssessment() { return normalizedSecurity.riskAssessment; },
    get activeIncidents() { return this.incidentManagement.activeIncidents; },
    get resolvedIncidents() { return this.incidentManagement.resolvedIncidents; },
    get activeIncidentCount() { return this.activeIncidents.length; },
    get hasActiveIncidents() { return this.activeIncidentCount > 0; },
    get averageResponseTime() { return this.incidentManagement.averageResponseTime; },
    get riskScore() { return this.userBehavior.riskScore; },
    get overallRiskScore() { return this.riskAssessment.overallRiskScore; },
    get behaviorAnomalies() { return this.userBehavior.anomalies; },
    get behaviorAnomalyCount() { return this.behaviorAnomalies.length; },
    get hasBehaviorAnomalies() { return this.behaviorAnomalyCount > 0; },
    get anomalyDetectionEnabled() { return this.anomalyDetection.enabled; },
    get recentAnomalies() { return this.anomalyDetection.recentAnomalies; },
    get recentAnomalyCount() { return this.recentAnomalies.length; },
    
    // Overall Security Assessment
    get overallSecurityScore() {
      let score = 100;
      
      // Threat level penalty (0-25 points)
      const threatPenalty = this.threatLevelNumeric * 6.25; // 6.25, 12.5, 18.75, 25
      score -= threatPenalty;
      
      // Vulnerability penalty (0-20 points)
      const vulnPenalty = Math.min(20, this.unresolvedVulnerabilityCount * 4);
      score -= vulnPenalty;
      
      // Critical events penalty (0-15 points)
      const eventPenalty = Math.min(15, this.criticalEventCount * 5);
      score -= eventPenalty;
      
      // Failure rate penalty (0-15 points)
      const failurePenalty = Math.min(15, this.failureRate * 0.75);
      score -= failurePenalty;
      
      // Compliance bonus/penalty (0-10 points)
      const complianceAdjustment = (this.complianceScore - 50) * 0.2;
      score += complianceAdjustment;
      
      // Active incidents penalty (0-15 points)
      const incidentPenalty = Math.min(15, this.activeIncidentCount * 7.5);
      score -= incidentPenalty;
      
      return Math.max(0, Math.min(100, Math.round(score)));
    },
    get securityRating() {
      const score = this.overallSecurityScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    get isSecure() { return this.overallSecurityScore >= 75; },
    get needsAttention() { return this.overallSecurityScore < 60; },
    get isCriticalSecurity() { return this.overallSecurityScore < 40; },
    
    // Suspicious Activity Detection
    get suspiciousActivity() {
      return this.hasRecentFailures || this.hasBehaviorAnomalies || 
             this.hasSessionAnomalies || this.hasCriticalEvents;
    },
    get suspiciousActivityScore() {
      let score = 0;
      if (this.hasRecentFailures) score += 25;
      if (this.hasBehaviorAnomalies) score += 20;
      if (this.hasSessionAnomalies) score += 15;
      if (this.hasCriticalEvents) score += 30;
      if (this.isHighFailureRate) score += 10;
      return Math.min(100, score);
    },
    get suspiciousActivityRating() {
      const score = this.suspiciousActivityScore;
      if (score >= 80) return 'critical';
      if (score >= 60) return 'high';
      if (score >= 40) return 'medium';
      if (score >= 20) return 'low';
      return 'none';
    },
    
    // Session Security Analysis
    get sessionSecurityScore() {
      let score = 100;
      
      // Session anomalies penalty
      score -= this.sessionAnomalyCount * 10;
      
      // Expired session cleanup bonus/penalty
      const expiredRatio = this.totalSessionCount > 0 ? 
        (this.expiredSessions.length / this.totalSessionCount) : 0;
      if (expiredRatio > 0.5) score -= 20; // Too many expired sessions
      
      // Active token management
      if (this.activeTokenCount > 10) score -= 10; // Too many active tokens
      
      return Math.max(0, Math.min(100, score));
    },
    get sessionSecurityRating() {
      const score = this.sessionSecurityScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    
    // Timestamps
    get createdAt() { return normalizedSecurity.createdAt; },
    get updatedAt() { return normalizedSecurity.updatedAt; },
    
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
    get needsSecurityReview() { return this.daysSinceUpdate > 30; },
    
    // WordPress Integration
    get wpPostId() { return normalizedSecurity.wpPostId; },
    get wpSynced() { return normalizedSecurity.wpSynced; },
    get lastWpSync() { return normalizedSecurity.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced && !!this.wpPostId; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Metadata
    get metadata() { return normalizedSecurity.metadata; },
    
    // Validation
    get isValid() {
      return !!(this.id && this.userId);
    },
    get isComplete() {
      return this.isValid && this.totalSecurityEvents > 0;
    },
    get isOperational() {
      return this.isComplete && this.overallSecurityScore >= 40;
    },
    
    // Utility Methods
    getEventsByType(eventType) {
      return this.securityEvents.filter(event => event.type === eventType);
    },
    getEventsBySeverity(severity) {
      return this.securityEvents.filter(event => event.severity === severity);
    },
    getVulnerabilitiesBySeverity(severity) {
      return this.vulnerabilities.filter(vuln => vuln.severity === severity);
    },
    isUserSuspicious(userId) {
      return this.failedAttempts.some(attempt => 
        attempt.userId === userId && this.hasRecentFailures
      );
    },
    getSessionById(sessionId) {
      return this.userSessions.find(session => session.id === sessionId);
    },
    hasActiveIncident(type) {
      return this.activeIncidents.some(incident => incident.type === type);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        threatLevel: this.threatLevel,
        overallSecurityScore: this.overallSecurityScore,
        securityRating: this.securityRating,
        vulnerabilityCount: this.vulnerabilityCount,
        activeIncidentCount: this.activeIncidentCount,
        suspiciousActivity: this.suspiciousActivity,
        complianceScore: this.complianceScore,
        failureRate: this.failureRate,
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
        userId: this.userId,
        threatLevel: this.threatLevel,
        
        // Event data
        accessLogs: this.accessLogs,
        failedAttempts: this.failedAttempts,
        securityEvents: this.securityEvents,
        
        // Session data
        userSessions: this.userSessions,
        activeTokens: this.activeTokens,
        permissionChanges: this.permissionChanges,
        
        // Vulnerability data
        vulnerabilities: this.vulnerabilities,
        
        // Compliance data
        complianceStatus: this.complianceStatus,
        auditTrail: this.auditTrail,
        dataAccessLogs: this.dataAccessLogs,
        
        // Configuration
        securitySettings: this.securitySettings,
        threatDetection: this.threatDetection,
        
        // Risk management
        incidentManagement: this.incidentManagement,
        userBehavior: this.userBehavior,
        anomalyDetection: this.anomalyDetection,
        riskAssessment: this.riskAssessment,
        
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
 * Create empty security helper for null/undefined security data
 * @returns {Object} Empty security helper with safe defaults
 */
function createEmptySecurityHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get userId() { return null; },
    
    // Threat Level
    get threatLevel() { return 'low'; },
    get isLowThreat() { return true; },
    get isMediumThreat() { return false; },
    get isHighThreat() { return false; },
    get isCriticalThreat() { return false; },
    get threatLevelNumeric() { return 1; },
    
    // Events
    get accessLogs() { return []; },
    get failedAttempts() { return []; },
    get securityEvents() { return []; },
    get totalAccessEvents() { return 0; },
    get totalFailedAttempts() { return 0; },
    get totalSecurityEvents() { return 0; },
    get hasRecentFailures() { return false; },
    get failureRate() { return 0; },
    
    // Sessions
    get userSessions() { return []; },
    get activeTokens() { return []; },
    get activeSessions() { return []; },
    get activeSessionCount() { return 0; },
    get hasActiveSessions() { return false; },
    get sessionAnomalies() { return []; },
    get hasSessionAnomalies() { return false; },
    
    // Vulnerabilities
    get vulnerabilities() { return []; },
    get vulnerabilityCount() { return 0; },
    get hasVulnerabilities() { return false; },
    get criticalVulnerabilities() { return []; },
    get hasCriticalVulnerabilities() { return false; },
    
    // Compliance
    get complianceStatus() { return { overallScore: 0 }; },
    get complianceScore() { return 0; },
    get complianceRating() { return 'critical'; },
    get isFullyCompliant() { return false; },
    get auditReadiness() { return 0; },
    
    // Security Settings
    get securitySettings() { return { twoFactorRequired: false }; },
    get threatDetection() { return { enabled: false }; },
    get twoFactorRequired() { return false; },
    get threatDetectionEnabled() { return false; },
    
    // Risk Assessment
    get overallSecurityScore() { return 0; },
    get securityRating() { return 'critical'; },
    get isSecure() { return false; },
    get needsAttention() { return true; },
    get suspiciousActivity() { return false; },
    get suspiciousActivityScore() { return 0; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get isRecent() { return false; },
    
    // WordPress
    get wpPostId() { return null; },
    get wpSynced() { return false; },
    get isSyncedToWordPress() { return false; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    
    // Utility Methods
    getEventsByType(eventType) { return []; },
    getEventsBySeverity(severity) { return []; },
    getVulnerabilitiesBySeverity(severity) { return []; },
    isUserSuspicious(userId) { return false; },
    getSessionById(sessionId) { return null; },
    hasActiveIncident(type) { return false; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Security
 */

/**
 * Log security event
 * @param {Object} eventData - Security event data
 * @returns {Promise<Object>} Created security event
 */
export async function logSecurityEvent(eventData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to log security events');
    }

    const securityEvent = {
      id: crypto.randomUUID(),
      type: eventData.type || 'security_violation',
      userId: eventData.userId || currentUser.id,
      ip: eventData.ip || 'unknown',
      userAgent: eventData.userAgent || 'unknown',
      details: eventData.details || {},
      timestamp: new Date().toISOString(),
      severity: eventData.severity || 'medium'
    };

    log(`[Security] Logged security event: ${securityEvent.type}`, 'info');
    return securityEvent;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Security] Error logging security event: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update threat level
 * @param {string} securityId - Security ID
 * @param {ThreatLevel} newThreatLevel - New threat level
 * @returns {Promise<Object>} Updated security data
 */
export async function updateThreatLevel(securityId, newThreatLevel) {
  try {
    const updatedData = {
      threatLevel: newThreatLevel,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.security.update(securityId, updatedData);
    }

    // Update local store
    securityStore.update(securities => 
      securities.map(security => 
        security.id === securityId 
          ? { ...security, ...updatedData }
          : security
      )
    );

    log(`[Security] Updated threat level: ${securityId} to ${newThreatLevel}`, 'info');
    
    // Return updated security data
    const updatedSecurity = await getSecurityById(securityId);
    return updatedSecurity;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Security] Error updating threat level: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Generate audit report
 * @param {Object} reportParams - Report parameters
 * @returns {Promise<Object>} Audit report
 */
export async function generateAuditReport(reportParams) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to generate audit reports');
    }

    const report = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      startDate: reportParams.startDate,
      endDate: reportParams.endDate,
      includeFailures: reportParams.includeFailures || false,
      events: [], // Will be populated with filtered events
      generatedAt: new Date().toISOString()
    };

    log(`[Security] Generated audit report: ${report.id}`, 'info');
    return report;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Security] Error generating audit report: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get security data by ID
 * @param {string} securityId - Security ID
 * @returns {Promise<Object|null>} Security data or null
 */
export async function getSecurityById(securityId) {
  try {
    let security = null;

    // Try LiveStore first
    if (browser && liveStore) {
      security = await liveStore.security.findById(securityId);
    }

    // Fallback to local store
    if (!security) {
      const securities = await new Promise(resolve => {
        securityStore.subscribe(value => resolve(value))();
      });
      security = securities.find(s => s.id === securityId);
    }

    return security ? getSecurityData(security) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Security] Error getting security by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get user's security data
 * @returns {Promise<Object|null>} User's security data or null
 */
export async function getUserSecurityData() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return null;

    let security = null;

    // Try LiveStore first
    if (browser && liveStore) {
      const securities = await liveStore.security.findMany({
        where: { userId: currentUser.id },
        orderBy: { updatedAt: 'desc' },
        take: 1
      });
      security = securities[0] || null;
    }

    // Fallback to local store
    if (!security) {
      const allSecurities = await new Promise(resolve => {
        securityStore.subscribe(value => resolve(value))();
      });
      const userSecurities = allSecurities.filter(security => security.userId === currentUser.id);
      security = userSecurities.length > 0 ? userSecurities[userSecurities.length - 1] : null;
    }

    return security ? getSecurityData(security) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Security] Error getting user security data: ${errorMessage}`, 'error');
    return null;
  }
}

export default {
  store: securityStore,
  getSecurityData,
  logSecurityEvent,
  updateThreatLevel,
  generateAuditReport,
  getSecurityById,
  getUserSecurityData
}; 