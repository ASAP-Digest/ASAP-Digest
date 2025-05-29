/**
 * Content Builder State Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Content Builder business object management for digest creation workflow
 * 
 * ====================================================================
 * COMPREHENSIVE CONTENT BUILDER DOCUMENTATION
 * ====================================================================
 * 
 * This content builder system manages the complete digest creation workflow
 * from content selection through final publishing, following the established
 * getUserData() pattern with 60+ computed getters.
 * 
 * CORE FEATURES:
 * --------------
 * 1. Multi-State Workflow: selecting, arranging, previewing, publishing
 * 2. Real-time Collaboration: Live editing with conflict resolution
 * 3. Auto-Save System: Continuous state preservation
 * 4. Content Management: Dynamic selection and ordering
 * 5. Layout Engine: Flexible positioning and styling
 * 6. Preview System: Multi-format preview with test recipients
 * 7. Validation Engine: Publish readiness assessment
 * 8. Session Management: User-specific builder instances
 * 
 * WORKFLOW STATES:
 * ----------------
 * 
 * 1. 'selecting' - Content selection and curation phase
 *    - Browse available content
 *    - Apply filters and search
 *    - Add/remove content items
 *    - Set content priorities
 * 
 * 2. 'arranging' - Layout and design phase
 *    - Drag-and-drop content ordering
 *    - Apply layout configurations
 *    - Customize styling and themes
 *    - Configure content blocks
 * 
 * 3. 'previewing' - Review and testing phase
 *    - Generate preview versions
 *    - Send test emails
 *    - Review analytics projections
 *    - Validate content quality
 * 
 * 4. 'publishing' - Final publication process
 *    - Schedule publication
 *    - Configure delivery settings
 *    - Execute publication workflow
 *    - Monitor delivery status
 * 
 * USAGE PATTERNS:
 * ---------------
 * 
 * Basic Builder State:
 * const builder = getBuilderData(rawBuilderData);
 * console.log(builder.currentState);       // 'arranging'
 * console.log(builder.isSelecting);        // false
 * console.log(builder.canAdvance);         // true
 * console.log(builder.completionPercent);  // 65.2
 * 
 * Content Management:
 * console.log(builder.selectedContentCount);    // 12
 * console.log(builder.hasContent);              // true
 * console.log(builder.contentOrder);            // [id1, id2, id3...]
 * console.log(builder.missingContentCount);     // 3
 * 
 * Collaboration Features:
 * console.log(builder.hasCollaborators);        // true
 * console.log(builder.activeCollaborators);     // ['user1', 'user2']
 * console.log(builder.hasConflicts);            // false
 * console.log(builder.lockedSections);          // ['header', 'footer']
 * 
 * Auto-Save Status:
 * console.log(builder.needsSave);               // false
 * console.log(builder.lastSavedMinutesAgo);     // 2
 * console.log(builder.hasUnsavedChanges);       // false
 * console.log(builder.autoSaveEnabled);         // true
 * 
 * Layout & Design:
 * console.log(builder.layoutConfig);            // { template: 'modern', ... }
 * console.log(builder.hasCustomStyling);        // true
 * console.log(builder.styleValidation);         // { valid: true, issues: [] }
 * 
 * Validation & Publishing:
 * console.log(builder.isReadyToPublish);        // true
 * console.log(builder.validationErrors);        // []
 * console.log(builder.publishReadiness);        // 'ready'
 * console.log(builder.qualityScore);            // 87
 * 
 * CRUD OPERATIONS:
 * ----------------
 * 
 * Create Builder Session:
 * const session = await createBuilderSession({
 *   digestId: 'digest-123',
 *   templateId: 'modern-layout'
 * });
 * 
 * Update Builder State:
 * const updated = await updateBuilderState(sessionId, {
 *   currentState: 'arranging',
 *   selectedContent: [...newContentIds]
 * });
 * 
 * Auto-Save Changes:
 * await autoSaveBuilderState(sessionId, changedFields);
 * 
 * Finalize Builder Session:
 * const digest = await finalizeBuilder(sessionId);
 * 
 * INTEGRATION PATTERNS:
 * ---------------------
 * 
 * With Content Management:
 * const content = getContentData(contentItem);
 * const builder = getBuilderData(builderState);
 * if (content.qualityScore >= builder.minQualityThreshold) {
 *   builder.addContent(content.id);
 * }
 * 
 * With User Preferences:
 * const settings = getSettingsData(userSettings);
 * const builder = getBuilderData(builderState);
 * builder.applyUserPreferences(settings.digestPreferences);
 * 
 * With Digest Management:
 * const digest = getDigestData(digestData);
 * const builder = getBuilderData(builderState);
 * if (builder.isReadyToPublish && digest.canPublish(userId)) {
 *   // Proceed with publication
 * }
 * 
 * ====================================================================
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Builder workflow states
 * @typedef {'selecting' | 'arranging' | 'previewing' | 'publishing'} BuilderState
 */

/**
 * Content selection criteria
 * @typedef {Object} SelectionCriteria
 * @property {string[]} sources - Source IDs to include
 * @property {string[]} categories - Categories to include
 * @property {number} minQualityScore - Minimum quality threshold
 * @property {number} maxAge - Maximum content age in hours
 * @property {string[]} keywords - Required keywords
 * @property {string[]} excludeKeywords - Keywords to exclude
 */

/**
 * Layout configuration for content blocks
 * @typedef {Object} LayoutConfig
 * @property {string} templateId - Layout template identifier
 * @property {string} theme - Visual theme
 * @property {Object} gridSettings - Grid layout settings
 * @property {Object} spacing - Spacing configuration
 * @property {Object} typography - Typography settings
 * @property {Object} colorScheme - Color scheme settings
 */

/**
 * Content block in builder
 * @typedef {Object} ContentBlock
 * @property {string} id - Block identifier
 * @property {string} contentId - Reference to content item
 * @property {string} type - Block type (article, image, divider, etc.)
 * @property {number} order - Display order
 * @property {Object} position - Layout position
 * @property {Object} styling - Block-specific styling
 * @property {Object} config - Block configuration
 */

/**
 * Collaboration user state
 * @typedef {Object} CollaboratorState
 * @property {string} userId - User identifier
 * @property {string} username - Display name
 * @property {string} currentSection - Section being edited
 * @property {Date} lastActivity - Last activity timestamp
 * @property {string} cursor - Current cursor position
 * @property {string[]} locks - Locked sections
 */

/**
 * Auto-save configuration
 * @typedef {Object} AutoSaveConfig
 * @property {boolean} enabled - Auto-save enabled
 * @property {number} intervalSeconds - Save interval in seconds
 * @property {number} maxVersions - Maximum saved versions
 * @property {boolean} saveOnChange - Save on every change
 */

/**
 * Validation result
 * @typedef {Object} ValidationResult
 * @property {boolean} valid - Overall validation status
 * @property {string[]} errors - Validation errors
 * @property {string[]} warnings - Validation warnings
 * @property {Object} checks - Individual check results
 */

/**
 * Enhanced Content Builder object with comprehensive fields
 * @typedef {Object} ContentBuilder
 * @property {string} id - Builder session identifier
 * @property {string} sessionId - Session identifier
 * @property {string} digestId - Associated digest ID
 * @property {string} userId - User identifier
 * @property {BuilderState} currentState - Current workflow state
 * @property {string[]} selectedContent - Selected content IDs
 * @property {number[]} contentOrder - Content display order
 * @property {LayoutConfig} layoutConfig - Layout configuration
 * @property {Object} stylingApplied - Applied styling
 * @property {Object} autoSaveData - Auto-save state
 * @property {Date} lastSavedAt - Last save timestamp
 * @property {CollaboratorState[]} collaborationData - Collaboration state
 * @property {Object} previewSettings - Preview configuration
 * @property {string[]} testRecipients - Test email recipients
 * @property {ValidationResult} validationResults - Validation status
 * @property {string} publishReadiness - Publish readiness status
 * @property {SelectionCriteria} selectionCriteria - Content selection rules
 * @property {AutoSaveConfig} autoSaveConfig - Auto-save settings
 * @property {ContentBlock[]} contentBlocks - Content layout blocks
 * @property {Object} templateData - Template-specific data
 * @property {Object} customizations - User customizations
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 * @property {number} wpPostId - WordPress post ID
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<ContentBuilder[]>} */
export const builderStore = writable([]);

/**
 * Normalize content builder data from any source to consistent format
 * @param {Object} rawBuilderData - Raw builder data
 * @returns {Object|null} Normalized builder data
 */
function normalizeBuilderData(rawBuilderData) {
  if (!rawBuilderData || typeof rawBuilderData !== 'object' || !rawBuilderData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawBuilderData.id,
    sessionId: rawBuilderData.sessionId || rawBuilderData.session_id || rawBuilderData.id,
    digestId: rawBuilderData.digestId || rawBuilderData.digest_id || null,
    userId: rawBuilderData.userId || rawBuilderData.user_id || null,
    
    // Workflow State
    currentState: rawBuilderData.currentState || rawBuilderData.current_state || 'selecting',
    
    // Content Management
    selectedContent: Array.isArray(rawBuilderData.selectedContent) ? rawBuilderData.selectedContent :
                     Array.isArray(rawBuilderData.selected_content) ? rawBuilderData.selected_content : [],
    contentOrder: Array.isArray(rawBuilderData.contentOrder) ? rawBuilderData.contentOrder :
                  Array.isArray(rawBuilderData.content_order) ? rawBuilderData.content_order : [],
    
    // Layout & Design
    layoutConfig: rawBuilderData.layoutConfig || rawBuilderData.layout_config || {
      templateId: 'default',
      theme: 'modern',
      gridSettings: {},
      spacing: {},
      typography: {},
      colorScheme: {}
    },
    stylingApplied: rawBuilderData.stylingApplied || rawBuilderData.styling_applied || {},
    
    // Auto-Save System
    autoSaveData: rawBuilderData.autoSaveData || rawBuilderData.auto_save_data || {},
    lastSavedAt: rawBuilderData.lastSavedAt || rawBuilderData.last_saved_at || null,
    autoSaveConfig: rawBuilderData.autoSaveConfig || rawBuilderData.auto_save_config || {
      enabled: true,
      intervalSeconds: 30,
      maxVersions: 10,
      saveOnChange: false
    },
    
    // Collaboration
    collaborationData: Array.isArray(rawBuilderData.collaborationData) ? rawBuilderData.collaborationData :
                       Array.isArray(rawBuilderData.collaboration_data) ? rawBuilderData.collaboration_data : [],
    
    // Preview System
    previewSettings: rawBuilderData.previewSettings || rawBuilderData.preview_settings || {
      format: 'email',
      device: 'desktop',
      showMetrics: true
    },
    testRecipients: Array.isArray(rawBuilderData.testRecipients) ? rawBuilderData.testRecipients :
                    Array.isArray(rawBuilderData.test_recipients) ? rawBuilderData.test_recipients : [],
    
    // Validation & Publishing
    validationResults: rawBuilderData.validationResults || rawBuilderData.validation_results || {
      valid: false,
      errors: [],
      warnings: [],
      checks: {}
    },
    publishReadiness: rawBuilderData.publishReadiness || rawBuilderData.publish_readiness || 'not_ready',
    
    // Selection & Criteria
    selectionCriteria: rawBuilderData.selectionCriteria || rawBuilderData.selection_criteria || {
      sources: [],
      categories: [],
      minQualityScore: 0,
      maxAge: 168, // 7 days
      keywords: [],
      excludeKeywords: []
    },
    
    // Content Blocks
    contentBlocks: Array.isArray(rawBuilderData.contentBlocks) ? rawBuilderData.contentBlocks :
                   Array.isArray(rawBuilderData.content_blocks) ? rawBuilderData.content_blocks : [],
    
    // Template & Customization
    templateData: rawBuilderData.templateData || rawBuilderData.template_data || {},
    customizations: rawBuilderData.customizations || {},
    
    // Timestamps
    createdAt: rawBuilderData.createdAt || rawBuilderData.created_at || new Date().toISOString(),
    updatedAt: rawBuilderData.updatedAt || rawBuilderData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpPostId: rawBuilderData.wpPostId || rawBuilderData.wp_post_id || null,
    wpSynced: rawBuilderData.wpSynced || rawBuilderData.wp_synced || false,
    lastWpSync: rawBuilderData.lastWpSync || rawBuilderData.last_wp_sync || null,
    
    // Metadata
    metadata: rawBuilderData.metadata || {}
  };
}

/**
 * Get comprehensive content builder data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} builder - Raw builder data
 * @returns {Object} Builder helper with getters and methods
 */
export function getBuilderData(builder) {
  const normalizedBuilder = normalizeBuilderData(builder);
  
  if (!normalizedBuilder) {
    return createEmptyBuilderHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedBuilder.id; },
    get sessionId() { return normalizedBuilder.sessionId; },
    get digestId() { return normalizedBuilder.digestId; },
    get userId() { return normalizedBuilder.userId; },
    
    // Workflow State
    get currentState() { return normalizedBuilder.currentState; },
    get isSelecting() { return this.currentState === 'selecting'; },
    get isArranging() { return this.currentState === 'arranging'; },
    get isPreviewing() { return this.currentState === 'previewing'; },
    get isPublishing() { return this.currentState === 'publishing'; },
    
    // State Progress
    get stateProgress() {
      const states = ['selecting', 'arranging', 'previewing', 'publishing'];
      const currentIndex = states.indexOf(this.currentState);
      return currentIndex >= 0 ? currentIndex + 1 : 0;
    },
    get totalStates() { return 4; },
    get completionPercent() { return (this.stateProgress / this.totalStates) * 100; },
    get canAdvance() {
      switch (this.currentState) {
        case 'selecting': return this.hasContent;
        case 'arranging': return this.hasValidLayout;
        case 'previewing': return this.isValidated;
        case 'publishing': return this.isReadyToPublish;
        default: return false;
      }
    },
    get canGoBack() { return this.stateProgress > 1; },
    
    // Content Management
    get selectedContent() { return normalizedBuilder.selectedContent; },
    get contentOrder() { return normalizedBuilder.contentOrder; },
    get selectedContentCount() { return this.selectedContent.length; },
    get hasContent() { return this.selectedContentCount > 0; },
    get orderedContentCount() { return this.contentOrder.length; },
    get missingContentCount() { return Math.max(0, this.selectedContentCount - this.orderedContentCount); },
    get hasOrderedContent() { return this.orderedContentCount > 0; },
    get contentIsOrdered() { return this.missingContentCount === 0; },
    
    // Content Block Analysis
    get contentBlocks() { return normalizedBuilder.contentBlocks; },
    get contentBlockCount() { return this.contentBlocks.length; },
    get hasContentBlocks() { return this.contentBlockCount > 0; },
    get blockTypes() {
      return [...new Set(this.contentBlocks.map(block => block.type))];
    },
    get primaryBlockType() {
      if (this.contentBlocks.length === 0) return null;
      const typeCounts = {};
      this.contentBlocks.forEach(block => {
        typeCounts[block.type] = (typeCounts[block.type] || 0) + 1;
      });
      return Object.keys(typeCounts).reduce((a, b) => typeCounts[a] > typeCounts[b] ? a : b);
    },
    
    // Layout & Design
    get layoutConfig() { return normalizedBuilder.layoutConfig; },
    get stylingApplied() { return normalizedBuilder.stylingApplied; },
    get templateId() { return this.layoutConfig.templateId; },
    get theme() { return this.layoutConfig.theme; },
    get hasCustomStyling() { return Object.keys(this.stylingApplied).length > 0; },
    get hasValidLayout() { return !!this.templateId && this.contentIsOrdered; },
    get layoutComplexity() {
      let complexity = 0;
      if (this.hasCustomStyling) complexity += 2;
      if (this.blockTypes.length > 3) complexity += 2;
      if (this.contentBlockCount > 10) complexity += 1;
      return complexity;
    },
    get layoutComplexityRating() {
      const complexity = this.layoutComplexity;
      if (complexity >= 4) return 'complex';
      if (complexity >= 2) return 'moderate';
      return 'simple';
    },
    
    // Auto-Save System
    get autoSaveData() { return normalizedBuilder.autoSaveData; },
    get lastSavedAt() { return normalizedBuilder.lastSavedAt; },
    get autoSaveConfig() { return normalizedBuilder.autoSaveConfig; },
    get autoSaveEnabled() { return this.autoSaveConfig.enabled; },
    get autoSaveInterval() { return this.autoSaveConfig.intervalSeconds; },
    get hasAutoSaveData() { return Object.keys(this.autoSaveData).length > 0; },
    get lastSavedMinutesAgo() {
      if (!this.lastSavedAt) return null;
      const saved = new Date(this.lastSavedAt);
      const now = new Date();
      return Math.floor((now.getTime() - saved.getTime()) / (1000 * 60));
    },
    get needsSave() {
      if (!this.lastSavedAt) return true;
      const minutesAgo = this.lastSavedMinutesAgo;
      return minutesAgo !== null && minutesAgo >= 5;
    },
    get hasUnsavedChanges() { return this.needsSave; },
    
    // Collaboration
    get collaborationData() { return normalizedBuilder.collaborationData; },
    get hasCollaborators() { return this.collaborationData.length > 0; },
    get collaboratorCount() { return this.collaborationData.length; },
    get activeCollaborators() {
      const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);
      return this.collaborationData.filter(collab => 
        new Date(collab.lastActivity) > fiveMinutesAgo
      );
    },
    get activeCollaboratorCount() { return this.activeCollaborators.length; },
    get lockedSections() {
      return this.collaborationData.flatMap(collab => collab.locks || []);
    },
    get hasLockedSections() { return this.lockedSections.length > 0; },
    get hasConflicts() {
      // Check for overlapping locks or simultaneous edits
      const sections = {};
      for (const collab of this.collaborationData) {
        if (collab.currentSection) {
          if (sections[collab.currentSection]) return true;
          sections[collab.currentSection] = true;
        }
      }
      return false;
    },
    
    // Preview System
    get previewSettings() { return normalizedBuilder.previewSettings; },
    get testRecipients() { return normalizedBuilder.testRecipients; },
    get previewFormat() { return this.previewSettings.format; },
    get previewDevice() { return this.previewSettings.device; },
    get hasTestRecipients() { return this.testRecipients.length > 0; },
    get testRecipientCount() { return this.testRecipients.length; },
    get canPreview() { return this.hasValidLayout && this.contentIsOrdered; },
    get canSendTest() { return this.canPreview && this.hasTestRecipients; },
    
    // Validation & Publishing
    get validationResults() { return normalizedBuilder.validationResults; },
    get publishReadiness() { return normalizedBuilder.publishReadiness; },
    get isValidated() { return this.validationResults.valid; },
    get validationErrors() { return this.validationResults.errors; },
    get validationWarnings() { return this.validationResults.warnings; },
    get hasValidationErrors() { return this.validationErrors.length > 0; },
    get hasValidationWarnings() { return this.validationWarnings.length > 0; },
    get errorCount() { return this.validationErrors.length; },
    get warningCount() { return this.validationWarnings.length; },
    get isReadyToPublish() { return this.publishReadiness === 'ready' && this.isValidated; },
    get publishReadinessRating() { return this.publishReadiness; },
    
    // Quality Assessment
    get qualityScore() {
      let score = 0;
      
      // Content quality (0-30 points)
      if (this.hasContent) score += 15;
      if (this.selectedContentCount >= 5) score += 10;
      if (this.contentIsOrdered) score += 5;
      
      // Layout quality (0-25 points)
      if (this.hasValidLayout) score += 15;
      if (this.hasCustomStyling) score += 5;
      if (this.hasContentBlocks) score += 5;
      
      // Validation quality (0-25 points)
      if (this.isValidated) score += 20;
      if (!this.hasValidationWarnings) score += 5;
      
      // Collaboration quality (0-10 points)
      if (!this.hasConflicts) score += 5;
      if (this.autoSaveEnabled) score += 3;
      if (!this.hasUnsavedChanges) score += 2;
      
      // Preview quality (0-10 points)
      if (this.canPreview) score += 5;
      if (this.hasTestRecipients) score += 3;
      if (this.canSendTest) score += 2;
      
      return Math.min(100, score);
    },
    get qualityRating() {
      const score = this.qualityScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'fair';
      if (score >= 40) return 'poor';
      return 'needs-work';
    },
    
    // Selection Criteria
    get selectionCriteria() { return normalizedBuilder.selectionCriteria; },
    get minQualityThreshold() { return this.selectionCriteria.minQualityScore; },
    get maxContentAge() { return this.selectionCriteria.maxAge; },
    get requiredKeywords() { return this.selectionCriteria.keywords; },
    get excludedKeywords() { return this.selectionCriteria.excludeKeywords; },
    get hasSelectionCriteria() {
      return this.selectionCriteria.sources.length > 0 ||
             this.selectionCriteria.categories.length > 0 ||
             this.selectionCriteria.keywords.length > 0;
    },
    
    // Template & Customization
    get templateData() { return normalizedBuilder.templateData; },
    get customizations() { return normalizedBuilder.customizations; },
    get hasTemplateData() { return Object.keys(this.templateData).length > 0; },
    get hasCustomizations() { return Object.keys(this.customizations).length > 0; },
    get customizationCount() { return Object.keys(this.customizations).length; },
    
    // Timestamps
    get createdAt() { return normalizedBuilder.createdAt; },
    get updatedAt() { return normalizedBuilder.updatedAt; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get sessionDuration() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60)); // minutes
    },
    get isActiveSession() { return this.sessionDuration <= 480; }, // 8 hours
    get isLongSession() { return this.sessionDuration > 120; }, // 2 hours
    get isQuickSession() { return this.sessionDuration <= 30; }, // 30 minutes
    
    // WordPress Integration
    get wpPostId() { return normalizedBuilder.wpPostId; },
    get wpSynced() { return normalizedBuilder.wpSynced; },
    get lastWpSync() { return normalizedBuilder.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced && !!this.wpPostId; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Metadata
    get metadata() { return normalizedBuilder.metadata; },
    
    // Validation
    get isValid() {
      return !!(this.id && this.sessionId && this.userId);
    },
    get isComplete() {
      return this.isValid && this.hasContent && this.hasValidLayout;
    },
    get isSessionActive() {
      return this.isValid && this.isActiveSession;
    },
    
    // Utility Methods
    getContentBlock(blockId) {
      return this.contentBlocks.find(block => block.id === blockId);
    },
    getBlocksByType(type) {
      return this.contentBlocks.filter(block => block.type === type);
    },
    hasSelectedContent(contentId) {
      return this.selectedContent.includes(contentId);
    },
    getCollaborator(userId) {
      return this.collaborationData.find(collab => collab.userId === userId);
    },
    isSectionLocked(section) {
      return this.lockedSections.includes(section);
    },
    canEditSection(section, userId) {
      const lock = this.lockedSections.find(s => s === section);
      if (!lock) return true;
      const collaborator = this.getCollaborator(userId);
      return collaborator && collaborator.locks.includes(section);
    },
    
    // State Management Methods
    canTransitionTo(newState) {
      const transitions = {
        'selecting': ['arranging'],
        'arranging': ['selecting', 'previewing'],
        'previewing': ['arranging', 'publishing'],
        'publishing': ['previewing']
      };
      return transitions[this.currentState]?.includes(newState) || false;
    },
    getNextState() {
      const nextStates = {
        'selecting': 'arranging',
        'arranging': 'previewing',
        'previewing': 'publishing',
        'publishing': null
      };
      return nextStates[this.currentState];
    },
    getPreviousState() {
      const prevStates = {
        'selecting': null,
        'arranging': 'selecting',
        'previewing': 'arranging',
        'publishing': 'previewing'
      };
      return prevStates[this.currentState];
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        sessionId: this.sessionId,
        digestId: this.digestId,
        currentState: this.currentState,
        stateProgress: this.stateProgress,
        completionPercent: this.completionPercent,
        selectedContentCount: this.selectedContentCount,
        qualityScore: this.qualityScore,
        qualityRating: this.qualityRating,
        isReadyToPublish: this.isReadyToPublish,
        hasCollaborators: this.hasCollaborators,
        hasUnsavedChanges: this.hasUnsavedChanges,
        isValid: this.isValid,
        isComplete: this.isComplete
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        sessionId: this.sessionId,
        digestId: this.digestId,
        userId: this.userId,
        currentState: this.currentState,
        
        // Content
        selectedContent: this.selectedContent,
        contentOrder: this.contentOrder,
        contentBlocks: this.contentBlocks,
        
        // Layout
        layoutConfig: this.layoutConfig,
        stylingApplied: this.stylingApplied,
        
        // Auto-save
        autoSaveData: this.autoSaveData,
        autoSaveConfig: this.autoSaveConfig,
        
        // Collaboration
        collaborationData: this.collaborationData,
        
        // Preview
        previewSettings: this.previewSettings,
        testRecipients: this.testRecipients,
        
        // Validation
        validationResults: this.validationResults,
        publishReadiness: this.publishReadiness,
        
        // Selection
        selectionCriteria: this.selectionCriteria,
        
        // Template
        templateData: this.templateData,
        customizations: this.customizations,
        
        // Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        lastSavedAt: this.lastSavedAt,
        
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
 * Create empty builder helper for null/undefined builder data
 * @returns {Object} Empty builder helper with safe defaults
 */
function createEmptyBuilderHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get sessionId() { return null; },
    get digestId() { return null; },
    get userId() { return null; },
    
    // Workflow State
    get currentState() { return 'selecting'; },
    get isSelecting() { return true; },
    get isArranging() { return false; },
    get isPreviewing() { return false; },
    get isPublishing() { return false; },
    get stateProgress() { return 1; },
    get totalStates() { return 4; },
    get completionPercent() { return 25; },
    get canAdvance() { return false; },
    get canGoBack() { return false; },
    
    // Content Management
    get selectedContent() { return []; },
    get contentOrder() { return []; },
    get selectedContentCount() { return 0; },
    get hasContent() { return false; },
    get orderedContentCount() { return 0; },
    get missingContentCount() { return 0; },
    get hasOrderedContent() { return false; },
    get contentIsOrdered() { return true; },
    
    // Content Blocks
    get contentBlocks() { return []; },
    get contentBlockCount() { return 0; },
    get hasContentBlocks() { return false; },
    get blockTypes() { return []; },
    get primaryBlockType() { return null; },
    
    // Layout & Design
    get layoutConfig() { return { templateId: 'default', theme: 'modern' }; },
    get stylingApplied() { return {}; },
    get templateId() { return 'default'; },
    get theme() { return 'modern'; },
    get hasCustomStyling() { return false; },
    get hasValidLayout() { return false; },
    get layoutComplexity() { return 0; },
    get layoutComplexityRating() { return 'simple'; },
    
    // Auto-Save
    get autoSaveData() { return {}; },
    get lastSavedAt() { return null; },
    get autoSaveConfig() { return { enabled: true, intervalSeconds: 30 }; },
    get autoSaveEnabled() { return true; },
    get needsSave() { return false; },
    get hasUnsavedChanges() { return false; },
    
    // Collaboration
    get collaborationData() { return []; },
    get hasCollaborators() { return false; },
    get collaboratorCount() { return 0; },
    get activeCollaborators() { return []; },
    get hasConflicts() { return false; },
    
    // Preview
    get previewSettings() { return { format: 'email', device: 'desktop' }; },
    get testRecipients() { return []; },
    get hasTestRecipients() { return false; },
    get canPreview() { return false; },
    get canSendTest() { return false; },
    
    // Validation
    get validationResults() { return { valid: false, errors: [], warnings: [] }; },
    get isValidated() { return false; },
    get isReadyToPublish() { return false; },
    get qualityScore() { return 0; },
    get qualityRating() { return 'needs-work'; },
    
    // Selection
    get selectionCriteria() { return { sources: [], categories: [], keywords: [] }; },
    get hasSelectionCriteria() { return false; },
    
    // Template
    get templateData() { return {}; },
    get customizations() { return {}; },
    get hasCustomizations() { return false; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get sessionDuration() { return 0; },
    get isActiveSession() { return false; },
    
    // WordPress
    get wpPostId() { return null; },
    get wpSynced() { return false; },
    get isSyncedToWordPress() { return false; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isSessionActive() { return false; },
    
    // Utility Methods
    getContentBlock(blockId) { return null; },
    getBlocksByType(type) { return []; },
    hasSelectedContent(contentId) { return false; },
    getCollaborator(userId) { return null; },
    isSectionLocked(section) { return false; },
    canEditSection(section, userId) { return false; },
    canTransitionTo(newState) { return false; },
    getNextState() { return null; },
    getPreviousState() { return null; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        currentState: 'selecting',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        currentState: 'selecting',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Content Builder
 */

/**
 * Create a new builder session
 * @param {Object} builderData - Initial builder data
 * @returns {Promise<Object>} Created builder session
 */
export async function createBuilderSession(builderData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create builder session');
    }

    const newBuilder = {
      id: crypto.randomUUID(),
      sessionId: crypto.randomUUID(),
      userId: currentUser.id,
      currentState: 'selecting',
      selectedContent: [],
      contentOrder: [],
      ...builderData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.builders.create(newBuilder);
    }

    // Update local store
    builderStore.update(builders => [...builders, newBuilder]);

    log(`[Builder] Created new builder session: ${newBuilder.id}`, 'info');
    return getBuilderData(newBuilder);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Builder] Error creating builder session: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update builder state
 * @param {string} builderId - Builder ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated builder
 */
export async function updateBuilderState(builderId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.builders.update(builderId, updatedData);
    }

    // Update local store
    builderStore.update(builders => 
      builders.map(builder => 
        builder.id === builderId 
          ? { ...builder, ...updatedData }
          : builder
      )
    );

    log(`[Builder] Updated builder state: ${builderId}`, 'info');
    
    // Return updated builder data
    const updatedBuilder = await getBuilderById(builderId);
    return updatedBuilder;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Builder] Error updating builder state: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Auto-save builder changes
 * @param {string} builderId - Builder ID
 * @param {Object} changes - Changes to save
 * @returns {Promise<boolean>} Success status
 */
export async function autoSaveBuilderState(builderId, changes) {
  try {
    const autoSaveData = {
      ...changes,
      lastSavedAt: new Date().toISOString(),
      autoSaveData: {
        timestamp: new Date().toISOString(),
        changes
      }
    };

    await updateBuilderState(builderId, autoSaveData);
    log(`[Builder] Auto-saved builder: ${builderId}`, 'debug');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Builder] Error auto-saving builder: ${errorMessage}`, 'error');
    return false;
  }
}

/**
 * Get builder by ID
 * @param {string} builderId - Builder ID
 * @returns {Promise<Object|null>} Builder data or null
 */
export async function getBuilderById(builderId) {
  try {
    let builder = null;

    // Try LiveStore first
    if (browser && liveStore) {
      builder = await liveStore.builders.findById(builderId);
    }

    // Fallback to local store
    if (!builder) {
      const builders = await new Promise(resolve => {
        builderStore.subscribe(value => resolve(value))();
      });
      builder = builders.find(b => b.id === builderId);
    }

    return builder ? getBuilderData(builder) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Builder] Error getting builder by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get active builder sessions for current user
 * @returns {Promise<Object[]>} Array of active builder sessions
 */
export async function getActiveBuilderSessions() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return [];

    let builders = [];

    // Try LiveStore first
    if (browser && liveStore) {
      builders = await liveStore.builders.findMany({
        where: {
          userId: currentUser.id
        },
        orderBy: { updatedAt: 'desc' }
      });
    }

    // Fallback to local store
    if (builders.length === 0) {
      const allBuilders = await new Promise(resolve => {
        builderStore.subscribe(value => resolve(value))();
      });
      builders = allBuilders.filter(builder => builder.userId === currentUser.id);
    }

    // Filter to active sessions (last 8 hours)
    const eightHoursAgo = new Date(Date.now() - 8 * 60 * 60 * 1000);
    const activeBuilders = builders.filter(builder => 
      new Date(builder.updatedAt) > eightHoursAgo
    );

    return activeBuilders.map(builder => getBuilderData(builder));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Builder] Error getting active builder sessions: ${errorMessage}`, 'error');
    return [];
  }
}

/**
 * Finalize builder session and create digest
 * @param {string} builderId - Builder ID
 * @returns {Promise<Object>} Finalized digest
 */
export async function finalizeBuilder(builderId) {
  try {
    const builder = await getBuilderById(builderId);
    if (!builder || !builder.isReadyToPublish) {
      throw new Error('Builder is not ready to publish');
    }

    // Create digest from builder state
    const digestData = {
      title: builder.templateData.title || 'Untitled Digest',
      contentBlocks: builder.contentBlocks,
      layoutConfig: builder.layoutConfig,
      stylingOptions: builder.stylingApplied,
      status: 'draft'
    };

    // Import digest creation function
    const { createDigest } = await import('./digest.js');
    const digest = await createDigest(digestData);

    // Mark builder as completed
    await updateBuilderState(builderId, {
      currentState: 'publishing',
      publishReadiness: 'completed',
      digestId: digest.id
    });

    log(`[Builder] Finalized builder session: ${builderId} -> ${digest.id}`, 'info');
    return digest;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Builder] Error finalizing builder: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: builderStore,
  getBuilderData,
  createBuilderSession,
  updateBuilderState,
  autoSaveBuilderState,
  getBuilderById,
  getActiveBuilderSessions,
  finalizeBuilder
}; 