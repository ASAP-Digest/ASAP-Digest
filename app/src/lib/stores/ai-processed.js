/**
 * AI Processing Results Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview AI Processing Results business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * AI processing status types
 * @typedef {'queued' | 'processing' | 'completed' | 'failed' | 'cached' | 'expired'} ProcessingStatus
 */

/**
 * AI task types
 * @typedef {'summarization' | 'classification' | 'sentiment' | 'extraction' | 'generation' | 'translation' | 'moderation'} AITaskType
 */

/**
 * AI provider types
 * @typedef {'openai' | 'anthropic' | 'huggingface' | 'google' | 'azure' | 'aws' | 'local'} AIProvider
 */

/**
 * Processing result quality
 * @typedef {'excellent' | 'good' | 'average' | 'poor' | 'failed'} ResultQuality
 */

/**
 * Enhanced AI Processing Result object with comprehensive fields
 * @typedef {Object} AIProcessingResult
 * @property {string} id - Result identifier
 * @property {string} contentId - Source content identifier
 * @property {AITaskType} taskType - Type of AI task performed
 * @property {AIProvider} providerUsed - AI provider that processed the task
 * @property {string} modelUsed - Specific model used for processing
 * @property {string} inputHash - Hash of input data for caching
 * @property {Object} outputData - Processed result data
 * @property {number} confidenceScore - AI confidence in result (0-100)
 * @property {number} processingTime - Time taken to process (ms)
 * @property {number} tokenUsage - Tokens consumed during processing
 * @property {number} cost - Cost incurred for processing
 * @property {number} version - Result version number
 * @property {ProcessingStatus} status - Current processing status
 * @property {Object} errorDetails - Error information if failed
 * @property {number} retryCount - Number of retry attempts
 * @property {number} feedbackScore - Human feedback score (1-5)
 * @property {boolean} humanValidated - Whether result was human validated
 * @property {string} validatorId - User ID who validated result
 * @property {Date} validatedAt - Validation timestamp
 * @property {number} reuseCount - How many times result was reused
 * @property {string} cacheKey - Cache key for result storage
 * @property {Date} expiresAt - Cache expiration timestamp
 * @property {Object} metadata - Additional processing metadata
 * @property {Object} qualityMetrics - Quality assessment metrics
 * @property {string} requestedBy - User who requested processing
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<AIProcessingResult[]>} */
export const aiProcessingResultsStore = writable([]);

/**
 * Normalize AI processing result data from any source to consistent format
 * @param {Object} rawResultData - Raw AI processing result data
 * @returns {Object|null} Normalized AI processing result data
 */
function normalizeAIProcessingResultData(rawResultData) {
  if (!rawResultData || typeof rawResultData !== 'object' || !rawResultData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawResultData.id,
    contentId: rawResultData.contentId || rawResultData.content_id || null,
    taskType: rawResultData.taskType || rawResultData.task_type || 'summarization',
    providerUsed: rawResultData.providerUsed || rawResultData.provider_used || 'openai',
    modelUsed: rawResultData.modelUsed || rawResultData.model_used || 'gpt-3.5-turbo',
    
    // Input/Output
    inputHash: rawResultData.inputHash || rawResultData.input_hash || null,
    outputData: rawResultData.outputData || rawResultData.output_data || {},
    confidenceScore: typeof rawResultData.confidenceScore === 'number' ? rawResultData.confidenceScore :
                     typeof rawResultData.confidence_score === 'number' ? rawResultData.confidence_score : 0,
    
    // Performance Metrics
    processingTime: typeof rawResultData.processingTime === 'number' ? rawResultData.processingTime :
                    typeof rawResultData.processing_time === 'number' ? rawResultData.processing_time : 0,
    tokenUsage: typeof rawResultData.tokenUsage === 'number' ? rawResultData.tokenUsage :
                typeof rawResultData.token_usage === 'number' ? rawResultData.token_usage : 0,
    cost: typeof rawResultData.cost === 'number' ? rawResultData.cost : 0,
    
    // Versioning & Status
    version: typeof rawResultData.version === 'number' ? rawResultData.version : 1,
    status: rawResultData.status || 'queued',
    errorDetails: rawResultData.errorDetails || rawResultData.error_details || null,
    retryCount: typeof rawResultData.retryCount === 'number' ? rawResultData.retryCount :
                typeof rawResultData.retry_count === 'number' ? rawResultData.retry_count : 0,
    
    // Quality & Validation
    feedbackScore: typeof rawResultData.feedbackScore === 'number' ? rawResultData.feedbackScore :
                   typeof rawResultData.feedback_score === 'number' ? rawResultData.feedback_score : 0,
    humanValidated: rawResultData.humanValidated || rawResultData.human_validated || false,
    validatorId: rawResultData.validatorId || rawResultData.validator_id || null,
    validatedAt: rawResultData.validatedAt || rawResultData.validated_at || null,
    
    // Usage & Caching
    reuseCount: typeof rawResultData.reuseCount === 'number' ? rawResultData.reuseCount :
                typeof rawResultData.reuse_count === 'number' ? rawResultData.reuse_count : 0,
    cacheKey: rawResultData.cacheKey || rawResultData.cache_key || null,
    expiresAt: rawResultData.expiresAt || rawResultData.expires_at || null,
    
    // Metadata
    metadata: rawResultData.metadata || {},
    qualityMetrics: rawResultData.qualityMetrics || rawResultData.quality_metrics || {},
    requestedBy: rawResultData.requestedBy || rawResultData.requested_by || null,
    
    // Timestamps
    createdAt: rawResultData.createdAt || rawResultData.created_at || new Date().toISOString(),
    updatedAt: rawResultData.updatedAt || rawResultData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpSynced: rawResultData.wpSynced || rawResultData.wp_synced || false,
    lastWpSync: rawResultData.lastWpSync || rawResultData.last_wp_sync || null
  };
}

/**
 * Get comprehensive AI processing result data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} result - Raw AI processing result data
 * @returns {Object} AI processing result helper with getters and methods
 */
export function getAIProcessedData(result) {
  const normalizedResult = normalizeAIProcessingResultData(result);
  
  if (!normalizedResult) {
    return createEmptyAIProcessedHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedResult.id; },
    get contentId() { return normalizedResult.contentId; },
    get taskType() { return normalizedResult.taskType; },
    get providerUsed() { return normalizedResult.providerUsed; },
    get modelUsed() { return normalizedResult.modelUsed; },
    
    // Task Type Checks
    get isSummarization() { return this.taskType === 'summarization'; },
    get isClassification() { return this.taskType === 'classification'; },
    get isSentimentAnalysis() { return this.taskType === 'sentiment'; },
    get isExtraction() { return this.taskType === 'extraction'; },
    get isGeneration() { return this.taskType === 'generation'; },
    get isTranslation() { return this.taskType === 'translation'; },
    get isModeration() { return this.taskType === 'moderation'; },
    
    // Provider Checks
    get usedOpenAI() { return this.providerUsed === 'openai'; },
    get usedAnthropic() { return this.providerUsed === 'anthropic'; },
    get usedHuggingFace() { return this.providerUsed === 'huggingface'; },
    get usedGoogle() { return this.providerUsed === 'google'; },
    get usedLocal() { return this.providerUsed === 'local'; },
    
    // Input/Output
    get inputHash() { return normalizedResult.inputHash; },
    get outputData() { return normalizedResult.outputData; },
    get confidenceScore() { return normalizedResult.confidenceScore; },
    get hasOutput() { return Object.keys(this.outputData).length > 0; },
    
    // Confidence Analysis
    get confidenceLevel() {
      if (this.confidenceScore >= 90) return 'very-high';
      if (this.confidenceScore >= 75) return 'high';
      if (this.confidenceScore >= 60) return 'medium';
      if (this.confidenceScore >= 40) return 'low';
      return 'very-low';
    },
    get isHighConfidence() { return this.confidenceScore >= 75; },
    get isLowConfidence() { return this.confidenceScore < 60; },
    
    // Performance Metrics
    get processingTime() { return normalizedResult.processingTime; },
    get tokenUsage() { return normalizedResult.tokenUsage; },
    get cost() { return normalizedResult.cost; },
    get processingTimeSeconds() { return this.processingTime / 1000; },
    get costPerToken() { return this.tokenUsage > 0 ? this.cost / this.tokenUsage : 0; },
    
    // Performance Analysis
    get processingSpeed() {
      if (this.processingTime < 1000) return 'very-fast';
      if (this.processingTime < 3000) return 'fast';
      if (this.processingTime < 10000) return 'average';
      if (this.processingTime < 30000) return 'slow';
      return 'very-slow';
    },
    get isFastProcessing() { return this.processingTime < 3000; },
    get isSlowProcessing() { return this.processingTime > 10000; },
    get isExpensive() { return this.cost > 0.01; }, // $0.01 threshold
    get isCostEffective() { return this.cost < 0.001; }, // $0.001 threshold
    
    // Versioning & Status
    get version() { return normalizedResult.version; },
    get status() { return normalizedResult.status; },
    get errorDetails() { return normalizedResult.errorDetails; },
    get retryCount() { return normalizedResult.retryCount; },
    
    // Status Checks
    get isQueued() { return this.status === 'queued'; },
    get isProcessing() { return this.status === 'processing'; },
    get isCompleted() { return this.status === 'completed'; },
    get isFailed() { return this.status === 'failed'; },
    get isCached() { return this.status === 'cached'; },
    get isExpired() { return this.status === 'expired'; },
    get isSuccessful() { return this.isCompleted || this.isCached; },
    get hasErrors() { return this.isFailed && !!this.errorDetails; },
    get hasRetries() { return this.retryCount > 0; },
    get isRetryable() { return this.isFailed && this.retryCount < 3; },
    
    // Quality & Validation
    get feedbackScore() { return normalizedResult.feedbackScore; },
    get humanValidated() { return normalizedResult.humanValidated; },
    get validatorId() { return normalizedResult.validatorId; },
    get validatedAt() { return normalizedResult.validatedAt; },
    get hasFeedback() { return this.feedbackScore > 0; },
    get hasValidator() { return !!this.validatorId; },
    
    // Quality Analysis
    get qualityRating() {
      if (this.humanValidated && this.feedbackScore >= 4) return 'excellent';
      if (this.humanValidated && this.feedbackScore >= 3) return 'good';
      if (this.confidenceScore >= 80) return 'good';
      if (this.confidenceScore >= 60) return 'average';
      if (this.isFailed) return 'failed';
      return 'poor';
    },
    get isHighQuality() { 
      return (this.humanValidated && this.feedbackScore >= 4) || 
             (!this.humanValidated && this.confidenceScore >= 80);
    },
    get needsValidation() { 
      return !this.humanValidated && this.isCompleted && this.confidenceScore < 80;
    },
    
    // Usage & Caching
    get reuseCount() { return normalizedResult.reuseCount; },
    get cacheKey() { return normalizedResult.cacheKey; },
    get expiresAt() { return normalizedResult.expiresAt; },
    get isReused() { return this.reuseCount > 0; },
    get isPopular() { return this.reuseCount >= 5; },
    get isCacheable() { return !!this.cacheKey; },
    get hasExpiration() { return !!this.expiresAt; },
    get isExpiredCache() {
      return this.hasExpiration && new Date(this.expiresAt) < new Date();
    },
    get timeUntilExpiration() {
      if (!this.hasExpiration) return null;
      const expires = new Date(this.expiresAt);
      const now = new Date();
      return expires.getTime() - now.getTime(); // milliseconds
    },
    
    // Metadata
    get metadata() { return normalizedResult.metadata; },
    get qualityMetrics() { return normalizedResult.qualityMetrics; },
    get requestedBy() { return normalizedResult.requestedBy; },
    get hasMetadata() { return Object.keys(this.metadata).length > 0; },
    get hasQualityMetrics() { return Object.keys(this.qualityMetrics).length > 0; },
    
    // Timestamps
    get createdAt() { return normalizedResult.createdAt; },
    get updatedAt() { return normalizedResult.updatedAt; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get ageHours() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60)); // hours
    },
    get daysSinceUpdate() {
      const updated = new Date(this.updatedAt);
      const now = new Date();
      return Math.floor((now.getTime() - updated.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get isRecent() { return this.ageHours <= 24; },
    get isStale() { return this.age > 30; },
    get isFresh() { return this.ageHours <= 1; },
    
    // WordPress Integration
    get wpSynced() { return normalizedResult.wpSynced; },
    get lastWpSync() { return normalizedResult.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Output Data Helpers (task-specific)
    get summary() { return this.outputData.summary || ''; },
    get keywords() { return this.outputData.keywords || []; },
    get entities() { return this.outputData.entities || []; },
    get classification() { return this.outputData.classification || ''; },
    get sentiment() { return this.outputData.sentiment || 'neutral'; },
    get translation() { return this.outputData.translation || ''; },
    get generatedText() { return this.outputData.generatedText || ''; },
    get moderationFlags() { return this.outputData.moderationFlags || []; },
    
    // Output Analysis
    get hasSummary() { return !!this.summary; },
    get hasKeywords() { return this.keywords.length > 0; },
    get hasEntities() { return this.entities.length > 0; },
    get hasClassification() { return !!this.classification; },
    get hasTranslation() { return !!this.translation; },
    get hasGeneratedText() { return !!this.generatedText; },
    get hasModerationFlags() { return this.moderationFlags.length > 0; },
    get keywordCount() { return this.keywords.length; },
    get entityCount() { return this.entities.length; },
    get flagCount() { return this.moderationFlags.length; },
    
    // Sentiment Analysis
    get isPositiveSentiment() { return this.sentiment === 'positive'; },
    get isNegativeSentiment() { return this.sentiment === 'negative'; },
    get isNeutralSentiment() { return this.sentiment === 'neutral'; },
    
    // Validation
    get isValid() {
      return !!(this.id && this.contentId && this.taskType);
    },
    get isComplete() {
      return this.isValid && this.isSuccessful && this.hasOutput;
    },
    get isUsable() {
      return this.isComplete && !this.isExpiredCache && this.confidenceScore >= 40;
    },
    get isReliable() {
      return this.isUsable && (this.humanValidated || this.confidenceScore >= 75);
    },
    
    // Utility Methods
    canUserValidate(userId) {
      return !this.humanValidated && this.isCompleted;
    },
    getOutputValue(key, defaultValue = null) {
      return this.outputData[key] || defaultValue;
    },
    hasOutputKey(key) {
      return key in this.outputData;
    },
    
    // Cost Analysis Methods
    getCostPerSecond() {
      return this.processingTimeSeconds > 0 ? this.cost / this.processingTimeSeconds : 0;
    },
    getEfficiencyScore() {
      // Combines speed, cost, and confidence into efficiency score
      const speedScore = Math.max(0, 100 - (this.processingTimeSeconds * 10));
      const costScore = Math.max(0, 100 - (this.cost * 10000));
      const confidenceScore = this.confidenceScore;
      return (speedScore + costScore + confidenceScore) / 3;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        contentId: this.contentId,
        taskType: this.taskType,
        providerUsed: this.providerUsed,
        status: this.status,
        confidenceScore: this.confidenceScore,
        processingTime: this.processingTime,
        cost: this.cost,
        reuseCount: this.reuseCount,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isUsable: this.isUsable,
        qualityRating: this.qualityRating,
        confidenceLevel: this.confidenceLevel
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        contentId: this.contentId,
        taskType: this.taskType,
        providerUsed: this.providerUsed,
        modelUsed: this.modelUsed,
        
        // Input/Output
        inputHash: this.inputHash,
        outputData: this.outputData,
        confidenceScore: this.confidenceScore,
        
        // Performance
        processingTime: this.processingTime,
        tokenUsage: this.tokenUsage,
        cost: this.cost,
        
        // Status
        version: this.version,
        status: this.status,
        errorDetails: this.errorDetails,
        retryCount: this.retryCount,
        
        // Quality
        feedbackScore: this.feedbackScore,
        humanValidated: this.humanValidated,
        validatorId: this.validatorId,
        validatedAt: this.validatedAt,
        
        // Usage
        reuseCount: this.reuseCount,
        cacheKey: this.cacheKey,
        expiresAt: this.expiresAt,
        
        // Metadata
        metadata: this.metadata,
        qualityMetrics: this.qualityMetrics,
        requestedBy: this.requestedBy,
        
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
 * Create empty AI processing result helper for null/undefined results
 * @returns {Object} Empty AI processing result helper with safe defaults
 */
function createEmptyAIProcessedHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get contentId() { return null; },
    get taskType() { return 'summarization'; },
    get providerUsed() { return 'openai'; },
    get modelUsed() { return 'gpt-3.5-turbo'; },
    
    // Task Type Checks
    get isSummarization() { return true; },
    get isClassification() { return false; },
    get isSentimentAnalysis() { return false; },
    get isExtraction() { return false; },
    get isGeneration() { return false; },
    get isTranslation() { return false; },
    get isModeration() { return false; },
    
    // Provider Checks
    get usedOpenAI() { return true; },
    get usedAnthropic() { return false; },
    get usedHuggingFace() { return false; },
    get usedGoogle() { return false; },
    get usedLocal() { return false; },
    
    // Input/Output
    get inputHash() { return null; },
    get outputData() { return {}; },
    get confidenceScore() { return 0; },
    get hasOutput() { return false; },
    get confidenceLevel() { return 'very-low'; },
    get isHighConfidence() { return false; },
    get isLowConfidence() { return true; },
    
    // Performance Metrics
    get processingTime() { return 0; },
    get tokenUsage() { return 0; },
    get cost() { return 0; },
    get processingTimeSeconds() { return 0; },
    get costPerToken() { return 0; },
    get processingSpeed() { return 'unknown'; },
    get isFastProcessing() { return false; },
    get isSlowProcessing() { return false; },
    get isExpensive() { return false; },
    get isCostEffective() { return true; },
    
    // Versioning & Status
    get version() { return 1; },
    get status() { return 'queued'; },
    get errorDetails() { return null; },
    get retryCount() { return 0; },
    get isQueued() { return true; },
    get isProcessing() { return false; },
    get isCompleted() { return false; },
    get isFailed() { return false; },
    get isCached() { return false; },
    get isExpired() { return false; },
    get isSuccessful() { return false; },
    get hasErrors() { return false; },
    get hasRetries() { return false; },
    get isRetryable() { return false; },
    
    // Quality & Validation
    get feedbackScore() { return 0; },
    get humanValidated() { return false; },
    get validatorId() { return null; },
    get validatedAt() { return null; },
    get hasFeedback() { return false; },
    get hasValidator() { return false; },
    get qualityRating() { return 'unrated'; },
    get isHighQuality() { return false; },
    get needsValidation() { return false; },
    
    // Usage & Caching
    get reuseCount() { return 0; },
    get cacheKey() { return null; },
    get expiresAt() { return null; },
    get isReused() { return false; },
    get isPopular() { return false; },
    get isCacheable() { return false; },
    get hasExpiration() { return false; },
    get isExpiredCache() { return false; },
    get timeUntilExpiration() { return null; },
    
    // Metadata
    get metadata() { return {}; },
    get qualityMetrics() { return {}; },
    get requestedBy() { return null; },
    get hasMetadata() { return false; },
    get hasQualityMetrics() { return false; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get ageHours() { return 0; },
    get daysSinceUpdate() { return 0; },
    get isRecent() { return false; },
    get isStale() { return false; },
    get isFresh() { return false; },
    
    // WordPress Integration
    get wpSynced() { return false; },
    get lastWpSync() { return null; },
    get isSyncedToWordPress() { return false; },
    get needsWordPressSync() { return false; },
    
    // Output Data Helpers
    get summary() { return ''; },
    get keywords() { return []; },
    get entities() { return []; },
    get classification() { return ''; },
    get sentiment() { return 'neutral'; },
    get translation() { return ''; },
    get generatedText() { return ''; },
    get moderationFlags() { return []; },
    
    // Output Analysis
    get hasSummary() { return false; },
    get hasKeywords() { return false; },
    get hasEntities() { return false; },
    get hasClassification() { return false; },
    get hasTranslation() { return false; },
    get hasGeneratedText() { return false; },
    get hasModerationFlags() { return false; },
    get keywordCount() { return 0; },
    get entityCount() { return 0; },
    get flagCount() { return 0; },
    
    // Sentiment Analysis
    get isPositiveSentiment() { return false; },
    get isNegativeSentiment() { return false; },
    get isNeutralSentiment() { return true; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isUsable() { return false; },
    get isReliable() { return false; },
    
    // Utility Methods
    canUserValidate(userId) { return false; },
    getOutputValue(key, defaultValue = null) { return defaultValue; },
    hasOutputKey(key) { return false; },
    getCostPerSecond() { return 0; },
    getEfficiencyScore() { return 0; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        contentId: null,
        taskType: 'summarization',
        status: 'queued',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        contentId: null,
        taskType: 'summarization',
        status: 'queued',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for AI Processing Results
 */

/**
 * Create new AI processing result
 * @param {Object} resultData - Initial result data
 * @returns {Promise<Object>} Created AI processing result
 */
export async function createAIProcessingResult(resultData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create AI processing result');
    }

    const newResult = {
      id: crypto.randomUUID(),
      ...resultData,
      requestedBy: currentUser.id,
      status: 'queued',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.aiProcessingResults.create(newResult);
    }

    // Update local store
    aiProcessingResultsStore.update(results => [...results, newResult]);

    log(`[AI Processing] Created new result: ${newResult.id}`, 'info');
    return getAIProcessedData(newResult);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Processing] Error creating result: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing AI processing result
 * @param {string} resultId - Result ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated AI processing result
 */
export async function updateAIProcessingResult(resultId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.aiProcessingResults.update(resultId, updatedData);
    }

    // Update local store
    aiProcessingResultsStore.update(results => 
      results.map(result => 
        result.id === resultId 
          ? { ...result, ...updatedData }
          : result
      )
    );

    log(`[AI Processing] Updated result: ${resultId}`, 'info');
    
    const updatedResult = await getAIProcessingResultById(resultId);
    return updatedResult;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Processing] Error updating result: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete AI processing result
 * @param {string} resultId - Result ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteAIProcessingResult(resultId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.aiProcessingResults.delete(resultId);
    }

    // Update local store
    aiProcessingResultsStore.update(results => 
      results.filter(result => result.id !== resultId)
    );

    log(`[AI Processing] Deleted result: ${resultId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Processing] Error deleting result: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get AI processing result by ID
 * @param {string} resultId - Result ID
 * @returns {Promise<Object|null>} AI processing result data or null
 */
export async function getAIProcessingResultById(resultId) {
  try {
    let result = null;

    // Try LiveStore first
    if (browser && liveStore) {
      result = await liveStore.aiProcessingResults.findById(resultId);
    }

    // Fallback to local store
    if (!result) {
      const allResults = await new Promise(resolve => {
        aiProcessingResultsStore.subscribe(value => resolve(value))();
      });
      result = allResults.find(r => r.id === resultId);
    }

    return result ? getAIProcessedData(result) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Processing] Error getting result by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get AI processing results by content ID
 * @param {string} contentId - Content ID
 * @returns {Promise<Object[]>} Array of AI processing result data objects
 */
export async function getAIProcessingResultsByContent(contentId) {
  try {
    let results = [];

    // Try LiveStore first
    if (browser && liveStore) {
      results = await liveStore.aiProcessingResults.findMany({
        where: { contentId }
      });
    }

    // Fallback to local store
    if (results.length === 0) {
      const allResults = await new Promise(resolve => {
        aiProcessingResultsStore.subscribe(value => resolve(value))();
      });
      results = allResults.filter(result => result.contentId === contentId);
    }

    return results.map(result => getAIProcessedData(result));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Processing] Error getting results by content: ${errorMessage}`, 'error');
    return [];
  }
}

/**
 * Get AI processing results by task type
 * @param {AITaskType} taskType - Task type
 * @returns {Promise<Object[]>} Array of AI processing result data objects
 */
export async function getAIProcessingResultsByTask(taskType) {
  try {
    let results = [];

    // Try LiveStore first
    if (browser && liveStore) {
      results = await liveStore.aiProcessingResults.findMany({
        where: { taskType }
      });
    }

    // Fallback to local store
    if (results.length === 0) {
      const allResults = await new Promise(resolve => {
        aiProcessingResultsStore.subscribe(value => resolve(value))();
      });
      results = allResults.filter(result => result.taskType === taskType);
    }

    return results.map(result => getAIProcessedData(result));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[AI Processing] Error getting results by task: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: aiProcessingResultsStore,
  getAIProcessedData,
  createAIProcessingResult,
  updateAIProcessingResult,
  deleteAIProcessingResult,
  getAIProcessingResultById,
  getAIProcessingResultsByContent,
  getAIProcessingResultsByTask
}; 