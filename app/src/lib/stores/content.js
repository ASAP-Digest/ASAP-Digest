/**
 * Content Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Content business object management
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Content status types
 * @typedef {'pending' | 'approved' | 'rejected' | 'archived'} ContentStatus
 */

/**
 * Content type categories
 * @typedef {'article' | 'video' | 'podcast' | 'social' | 'newsletter' | 'blog' | 'news'} ContentType
 */

/**
 * Moderation status
 * @typedef {'unmoderated' | 'pending' | 'approved' | 'rejected' | 'flagged'} ModerationStatus
 */

/**
 * Media attachment object
 * @typedef {Object} MediaAttachment
 * @property {string} id - Attachment identifier
 * @property {string} type - Media type (image, video, audio, document)
 * @property {string} url - Media URL
 * @property {string} filename - Original filename
 * @property {number} size - File size in bytes
 * @property {Object} metadata - Additional metadata
 */

/**
 * AI processed data
 * @typedef {Object} AIProcessedData
 * @property {string} summary - AI-generated summary
 * @property {string[]} keywords - Extracted keywords
 * @property {string[]} entities - Named entities
 * @property {string} classification - Content classification
 * @property {string} sentiment - Sentiment analysis
 * @property {number} qualityScore - AI quality assessment
 * @property {Object} metadata - Additional AI metadata
 */

/**
 * Enhanced Content object with comprehensive fields
 * @typedef {Object} Content
 * @property {string} id - Content identifier
 * @property {string} sourceId - Source identifier
 * @property {string} title - Content title
 * @property {string} content - Full content text
 * @property {string} excerpt - Content excerpt/summary
 * @property {string} url - Original content URL
 * @property {string} author - Content author
 * @property {Date} publicationDate - Original publication date
 * @property {Date} ingestionDate - When content was ingested
 * @property {Date} lastUpdated - Last update timestamp
 * @property {ContentType} contentType - Type of content
 * @property {ContentStatus} status - Current status
 * @property {number} qualityScore - Quality assessment score
 * @property {number} relevanceScore - Relevance score
 * @property {number} engagementScore - Engagement metrics
 * @property {string} fingerprint - Content fingerprint for deduplication
 * @property {string} duplicateOf - ID of original if duplicate
 * @property {string[]} similarContent - IDs of similar content
 * @property {string} rawContent - Original raw content
 * @property {string} processedContent - Processed/cleaned content
 * @property {string} sanitizedContent - Sanitized content for display
 * @property {MediaAttachment[]} mediaAttachments - Attached media
 * @property {Object} metadata - Content metadata (word count, reading time, etc)
 * @property {ModerationStatus} moderationStatus - Moderation status
 * @property {string} moderationNotes - Moderation notes
 * @property {string} moderatorId - Moderator user ID
 * @property {Date} moderatedAt - Moderation timestamp
 * @property {AIProcessedData} aiProcessedData - AI analysis results
 * @property {number} usageCount - How many times used in digests
 * @property {string} lastUsedInDigest - Last digest ID where used
 * @property {Date} lastUsedAt - Last usage timestamp
 * @property {Object} performanceMetrics - Performance analytics
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {number} wpPostId - WordPress post ID
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<Content[]>} */
export const contentStore = writable([]);

/**
 * Normalize content data from any source to consistent format
 * @param {Object} rawContentData - Raw content data
 * @returns {Object|null} Normalized content data
 */
function normalizeContentData(rawContentData) {
  if (!rawContentData || typeof rawContentData !== 'object' || !rawContentData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawContentData.id,
    sourceId: rawContentData.sourceId || rawContentData.source_id || null,
    title: rawContentData.title || 'Untitled Content',
    content: rawContentData.content || '',
    excerpt: rawContentData.excerpt || rawContentData.summary || '',
    url: rawContentData.url || rawContentData.link || '',
    
    // Authorship & Publication
    author: rawContentData.author || rawContentData.creator || 'Unknown',
    publicationDate: rawContentData.publicationDate || rawContentData.publication_date || 
                     rawContentData.publishedAt || rawContentData.published_at || null,
    ingestionDate: rawContentData.ingestionDate || rawContentData.ingestion_date || 
                   rawContentData.createdAt || new Date().toISOString(),
    lastUpdated: rawContentData.lastUpdated || rawContentData.last_updated || 
                 rawContentData.updatedAt || new Date().toISOString(),
    
    // Classification
    contentType: rawContentData.contentType || rawContentData.content_type || 
                 rawContentData.type || 'article',
    status: rawContentData.status || 'pending',
    
    // Scoring
    qualityScore: typeof rawContentData.qualityScore === 'number' ? rawContentData.qualityScore :
                  typeof rawContentData.quality_score === 'number' ? rawContentData.quality_score : 0,
    relevanceScore: typeof rawContentData.relevanceScore === 'number' ? rawContentData.relevanceScore :
                    typeof rawContentData.relevance_score === 'number' ? rawContentData.relevance_score : 0,
    engagementScore: typeof rawContentData.engagementScore === 'number' ? rawContentData.engagementScore :
                     typeof rawContentData.engagement_score === 'number' ? rawContentData.engagement_score : 0,
    
    // Deduplication
    fingerprint: rawContentData.fingerprint || rawContentData.hash || null,
    duplicateOf: rawContentData.duplicateOf || rawContentData.duplicate_of || null,
    similarContent: Array.isArray(rawContentData.similarContent) ? rawContentData.similarContent :
                    Array.isArray(rawContentData.similar_content) ? rawContentData.similar_content : [],
    
    // Content Versions
    rawContent: rawContentData.rawContent || rawContentData.raw_content || rawContentData.content || '',
    processedContent: rawContentData.processedContent || rawContentData.processed_content || rawContentData.content || '',
    sanitizedContent: rawContentData.sanitizedContent || rawContentData.sanitized_content || rawContentData.content || '',
    
    // Media
    mediaAttachments: Array.isArray(rawContentData.mediaAttachments) ? rawContentData.mediaAttachments :
                      Array.isArray(rawContentData.media_attachments) ? rawContentData.media_attachments :
                      Array.isArray(rawContentData.attachments) ? rawContentData.attachments : [],
    
    // Metadata
    metadata: rawContentData.metadata || {
      wordCount: 0,
      readingTime: 0,
      language: 'en',
      sentiment: 'neutral'
    },
    
    // Moderation
    moderationStatus: rawContentData.moderationStatus || rawContentData.moderation_status || 'unmoderated',
    moderationNotes: rawContentData.moderationNotes || rawContentData.moderation_notes || '',
    moderatorId: rawContentData.moderatorId || rawContentData.moderator_id || null,
    moderatedAt: rawContentData.moderatedAt || rawContentData.moderated_at || null,
    
    // AI Processing
    aiProcessedData: rawContentData.aiProcessedData || rawContentData.ai_processed_data || {
      summary: '',
      keywords: [],
      entities: [],
      classification: '',
      sentiment: 'neutral',
      qualityScore: 0,
      metadata: {}
    },
    
    // Usage Tracking
    usageCount: typeof rawContentData.usageCount === 'number' ? rawContentData.usageCount :
                typeof rawContentData.usage_count === 'number' ? rawContentData.usage_count : 0,
    lastUsedInDigest: rawContentData.lastUsedInDigest || rawContentData.last_used_in_digest || null,
    lastUsedAt: rawContentData.lastUsedAt || rawContentData.last_used_at || null,
    performanceMetrics: rawContentData.performanceMetrics || rawContentData.performance_metrics || {},
    
    // Timestamps
    createdAt: rawContentData.createdAt || rawContentData.created_at || new Date().toISOString(),
    updatedAt: rawContentData.updatedAt || rawContentData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpPostId: rawContentData.wpPostId || rawContentData.wp_post_id || null,
    wpSynced: rawContentData.wpSynced || rawContentData.wp_synced || false,
    lastWpSync: rawContentData.lastWpSync || rawContentData.last_wp_sync || null
  };
}

/**
 * Get comprehensive content data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} content - Raw content data
 * @returns {Object} Content helper with getters and methods
 */
export function getContentData(content) {
  const normalizedContent = normalizeContentData(content);
  
  if (!normalizedContent) {
    return createEmptyContentHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedContent.id; },
    get sourceId() { return normalizedContent.sourceId; },
    get title() { return normalizedContent.title; },
    get content() { return normalizedContent.content; },
    get excerpt() { return normalizedContent.excerpt; },
    get url() { return normalizedContent.url; },
    
    // Authorship & Publication
    get author() { return normalizedContent.author; },
    get publicationDate() { return normalizedContent.publicationDate; },
    get ingestionDate() { return normalizedContent.ingestionDate; },
    get lastUpdated() { return normalizedContent.lastUpdated; },
    
    // Classification
    get contentType() { return normalizedContent.contentType; },
    get status() { return normalizedContent.status; },
    
    // Status Checks
    get isPending() { return this.status === 'pending'; },
    get isApproved() { return this.status === 'approved'; },
    get isRejected() { return this.status === 'rejected'; },
    get isArchived() { return this.status === 'archived'; },
    get isActive() { return this.status === 'approved'; },
    
    // Content Type Checks
    get isArticle() { return this.contentType === 'article'; },
    get isVideo() { return this.contentType === 'video'; },
    get isPodcast() { return this.contentType === 'podcast'; },
    get isSocial() { return this.contentType === 'social'; },
    get isNewsletter() { return this.contentType === 'newsletter'; },
    
    // Scoring
    get qualityScore() { return normalizedContent.qualityScore; },
    get relevanceScore() { return normalizedContent.relevanceScore; },
    get engagementScore() { return normalizedContent.engagementScore; },
    get overallScore() { 
      return (this.qualityScore + this.relevanceScore + this.engagementScore) / 3;
    },
    
    // Quality Ratings
    get qualityRating() {
      const score = this.qualityScore;
      if (score >= 80) return 'excellent';
      if (score >= 60) return 'good';
      if (score >= 40) return 'average';
      if (score >= 20) return 'poor';
      return 'very-poor';
    },
    
    // Deduplication
    get fingerprint() { return normalizedContent.fingerprint; },
    get duplicateOf() { return normalizedContent.duplicateOf; },
    get similarContent() { return normalizedContent.similarContent; },
    get isDuplicate() { return !!this.duplicateOf; },
    get hasSimilarContent() { return this.similarContent.length > 0; },
    get similarContentCount() { return this.similarContent.length; },
    
    // Content Versions
    get rawContent() { return normalizedContent.rawContent; },
    get processedContent() { return normalizedContent.processedContent; },
    get sanitizedContent() { return normalizedContent.sanitizedContent; },
    get hasProcessedContent() { return this.processedContent !== this.rawContent; },
    
    // Media
    get mediaAttachments() { return normalizedContent.mediaAttachments; },
    get hasMedia() { return this.mediaAttachments.length > 0; },
    get mediaCount() { return this.mediaAttachments.length; },
    get mediaTypes() {
      return [...new Set(this.mediaAttachments.map(media => media.type))];
    },
    get hasImages() { return this.mediaAttachments.some(media => media.type === 'image'); },
    get hasVideos() { return this.mediaAttachments.some(media => media.type === 'video'); },
    get hasAudio() { return this.mediaAttachments.some(media => media.type === 'audio'); },
    
    // Metadata
    get metadata() { return normalizedContent.metadata; },
    get wordCount() { return this.metadata.wordCount || 0; },
    get readingTime() { return this.metadata.readingTime || Math.ceil(this.wordCount / 200); },
    get language() { return this.metadata.language || 'en'; },
    get sentiment() { return this.metadata.sentiment || 'neutral'; },
    
    // Content Analysis
    get isLongForm() { return this.wordCount > 1000; },
    get isShortForm() { return this.wordCount < 300; },
    get isMediumForm() { return this.wordCount >= 300 && this.wordCount <= 1000; },
    get readingTimeMinutes() { return Math.ceil(this.readingTime); },
    get isQuickRead() { return this.readingTime <= 2; },
    
    // Moderation
    get moderationStatus() { return normalizedContent.moderationStatus; },
    get moderationNotes() { return normalizedContent.moderationNotes; },
    get moderatorId() { return normalizedContent.moderatorId; },
    get moderatedAt() { return normalizedContent.moderatedAt; },
    get isModerated() { return this.moderationStatus !== 'unmoderated'; },
    get needsModeration() { return this.moderationStatus === 'unmoderated' || this.moderationStatus === 'pending'; },
    get isFlagged() { return this.moderationStatus === 'flagged'; },
    
    // AI Processing
    get aiProcessedData() { return normalizedContent.aiProcessedData; },
    get aiSummary() { return this.aiProcessedData.summary; },
    get aiKeywords() { return this.aiProcessedData.keywords; },
    get aiEntities() { return this.aiProcessedData.entities; },
    get aiClassification() { return this.aiProcessedData.classification; },
    get aiSentiment() { return this.aiProcessedData.sentiment; },
    get aiQualityScore() { return this.aiProcessedData.qualityScore; },
    get hasAIProcessing() { return !!(this.aiSummary || this.aiKeywords.length > 0); },
    get aiKeywordCount() { return this.aiKeywords.length; },
    get aiEntityCount() { return this.aiEntities.length; },
    
    // Usage Tracking
    get usageCount() { return normalizedContent.usageCount; },
    get lastUsedInDigest() { return normalizedContent.lastUsedInDigest; },
    get lastUsedAt() { return normalizedContent.lastUsedAt; },
    get performanceMetrics() { return normalizedContent.performanceMetrics; },
    get hasBeenUsed() { return this.usageCount > 0; },
    get isPopular() { return this.usageCount >= 5; },
    get isUnused() { return this.usageCount === 0; },
    
    // Timestamps
    get createdAt() { return normalizedContent.createdAt; },
    get updatedAt() { return normalizedContent.updatedAt; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get daysSinceUpdate() {
      const updated = new Date(this.lastUpdated);
      const now = new Date();
      return Math.floor((now.getTime() - updated.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get daysSincePublication() {
      if (!this.publicationDate) return null;
      const published = new Date(this.publicationDate);
      const now = new Date();
      return Math.floor((now.getTime() - published.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get isRecent() { return this.age <= 7; },
    get isStale() { return this.daysSinceUpdate > 30; },
    get isFresh() { return this.daysSincePublication !== null && this.daysSincePublication <= 1; },
    
    // WordPress Integration
    get wpPostId() { return normalizedContent.wpPostId; },
    get wpSynced() { return normalizedContent.wpSynced; },
    get lastWpSync() { return normalizedContent.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced && !!this.wpPostId; },
    get needsWordPressSync() { 
      if (!this.wpPostId) return true;
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.title && this.content);
    },
    get isComplete() {
      return this.isValid && this.author && this.contentType;
    },
    get isReadyForUse() {
      return this.isComplete && this.isApproved && !this.isDuplicate;
    },
    get isHighQuality() {
      return this.qualityScore >= 70 && this.overallScore >= 60;
    },
    
    // Utility Methods
    hasKeyword(keyword) {
      return this.aiKeywords.some(k => 
        k.toLowerCase().includes(keyword.toLowerCase())
      );
    },
    hasEntity(entity) {
      return this.aiEntities.some(e => 
        e.toLowerCase().includes(entity.toLowerCase())
      );
    },
    getMediaByType(type) {
      return this.mediaAttachments.filter(media => media.type === type);
    },
    getFirstImage() {
      const images = this.getMediaByType('image');
      return images.length > 0 ? images[0] : null;
    },
    
    // Content Analysis Methods
    containsText(searchText) {
      const text = searchText.toLowerCase();
      return this.title.toLowerCase().includes(text) ||
             this.content.toLowerCase().includes(text) ||
             this.excerpt.toLowerCase().includes(text);
    },
    
    // Performance Methods
    getEngagementRate() {
      if (!this.hasBeenUsed) return 0;
      const metrics = this.performanceMetrics;
      return metrics.engagementRate || 0;
    },
    getClickThroughRate() {
      const metrics = this.performanceMetrics;
      return metrics.clickThroughRate || 0;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        title: this.title,
        contentType: this.contentType,
        status: this.status,
        qualityScore: this.qualityScore,
        usageCount: this.usageCount,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isReadyForUse: this.isReadyForUse,
        hasAIProcessing: this.hasAIProcessing,
        age: this.age,
        qualityRating: this.qualityRating
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        sourceId: this.sourceId,
        title: this.title,
        content: this.content,
        excerpt: this.excerpt,
        url: this.url,
        
        // Authorship
        author: this.author,
        publicationDate: this.publicationDate,
        ingestionDate: this.ingestionDate,
        lastUpdated: this.lastUpdated,
        
        // Classification
        contentType: this.contentType,
        status: this.status,
        
        // Scoring
        qualityScore: this.qualityScore,
        relevanceScore: this.relevanceScore,
        engagementScore: this.engagementScore,
        
        // Deduplication
        fingerprint: this.fingerprint,
        duplicateOf: this.duplicateOf,
        similarContent: this.similarContent,
        
        // Content versions
        rawContent: this.rawContent,
        processedContent: this.processedContent,
        sanitizedContent: this.sanitizedContent,
        
        // Media
        mediaAttachments: this.mediaAttachments,
        
        // Metadata
        metadata: this.metadata,
        
        // Moderation
        moderationStatus: this.moderationStatus,
        moderationNotes: this.moderationNotes,
        moderatorId: this.moderatorId,
        moderatedAt: this.moderatedAt,
        
        // AI Processing
        aiProcessedData: this.aiProcessedData,
        
        // Usage
        usageCount: this.usageCount,
        lastUsedInDigest: this.lastUsedInDigest,
        lastUsedAt: this.lastUsedAt,
        performanceMetrics: this.performanceMetrics,
        
        // Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        
        // WordPress
        wpPostId: this.wpPostId,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync
      };
    }
  };
}

/**
 * Create empty content helper for null/undefined content
 * @returns {Object} Empty content helper with safe defaults
 */
function createEmptyContentHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get sourceId() { return null; },
    get title() { return 'New Content'; },
    get content() { return ''; },
    get excerpt() { return ''; },
    get url() { return ''; },
    
    // Authorship & Publication
    get author() { return 'Unknown'; },
    get publicationDate() { return null; },
    get ingestionDate() { return null; },
    get lastUpdated() { return null; },
    
    // Classification
    get contentType() { return 'article'; },
    get status() { return 'pending'; },
    
    // Status Checks
    get isPending() { return true; },
    get isApproved() { return false; },
    get isRejected() { return false; },
    get isArchived() { return false; },
    get isActive() { return false; },
    
    // Content Type Checks
    get isArticle() { return true; },
    get isVideo() { return false; },
    get isPodcast() { return false; },
    get isSocial() { return false; },
    get isNewsletter() { return false; },
    
    // Scoring
    get qualityScore() { return 0; },
    get relevanceScore() { return 0; },
    get engagementScore() { return 0; },
    get overallScore() { return 0; },
    get qualityRating() { return 'unrated'; },
    
    // Deduplication
    get fingerprint() { return null; },
    get duplicateOf() { return null; },
    get similarContent() { return []; },
    get isDuplicate() { return false; },
    get hasSimilarContent() { return false; },
    get similarContentCount() { return 0; },
    
    // Content Versions
    get rawContent() { return ''; },
    get processedContent() { return ''; },
    get sanitizedContent() { return ''; },
    get hasProcessedContent() { return false; },
    
    // Media
    get mediaAttachments() { return []; },
    get hasMedia() { return false; },
    get mediaCount() { return 0; },
    get mediaTypes() { return []; },
    get hasImages() { return false; },
    get hasVideos() { return false; },
    get hasAudio() { return false; },
    
    // Metadata
    get metadata() { return {}; },
    get wordCount() { return 0; },
    get readingTime() { return 0; },
    get language() { return 'en'; },
    get sentiment() { return 'neutral'; },
    
    // Content Analysis
    get isLongForm() { return false; },
    get isShortForm() { return true; },
    get isMediumForm() { return false; },
    get readingTimeMinutes() { return 0; },
    get isQuickRead() { return true; },
    
    // Moderation
    get moderationStatus() { return 'unmoderated'; },
    get moderationNotes() { return ''; },
    get moderatorId() { return null; },
    get moderatedAt() { return null; },
    get isModerated() { return false; },
    get needsModeration() { return true; },
    get isFlagged() { return false; },
    
    // AI Processing
    get aiProcessedData() { return { summary: '', keywords: [], entities: [], classification: '', sentiment: 'neutral', qualityScore: 0, metadata: {} }; },
    get aiSummary() { return ''; },
    get aiKeywords() { return []; },
    get aiEntities() { return []; },
    get aiClassification() { return ''; },
    get aiSentiment() { return 'neutral'; },
    get aiQualityScore() { return 0; },
    get hasAIProcessing() { return false; },
    get aiKeywordCount() { return 0; },
    get aiEntityCount() { return 0; },
    
    // Usage Tracking
    get usageCount() { return 0; },
    get lastUsedInDigest() { return null; },
    get lastUsedAt() { return null; },
    get performanceMetrics() { return {}; },
    get hasBeenUsed() { return false; },
    get isPopular() { return false; },
    get isUnused() { return true; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get daysSinceUpdate() { return 0; },
    get daysSincePublication() { return null; },
    get isRecent() { return false; },
    get isStale() { return false; },
    get isFresh() { return false; },
    
    // WordPress Integration
    get wpPostId() { return null; },
    get wpSynced() { return false; },
    get lastWpSync() { return null; },
    get isSyncedToWordPress() { return false; },
    get needsWordPressSync() { return false; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isReadyForUse() { return false; },
    get isHighQuality() { return false; },
    
    // Utility Methods
    hasKeyword(keyword) { return false; },
    hasEntity(entity) { return false; },
    getMediaByType(type) { return []; },
    getFirstImage() { return null; },
    containsText(searchText) { return false; },
    getEngagementRate() { return 0; },
    getClickThroughRate() { return 0; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        title: 'New Content',
        contentType: 'article',
        status: 'pending',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        title: 'New Content',
        status: 'pending',
        contentType: 'article',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Content
 */

/**
 * Create new content
 * @param {Object} contentData - Initial content data
 * @returns {Promise<Object>} Created content
 */
export async function createContent(contentData) {
  try {
    const newContent = {
      id: crypto.randomUUID(),
      ...contentData,
      status: 'pending',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      ingestionDate: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.content.create(newContent);
    }

    // Update local store
    contentStore.update(content => [...content, newContent]);

    log(`[Content] Created new content: ${newContent.id}`, 'info');
    return getContentData(newContent);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Content] Error creating content: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update existing content
 * @param {string} contentId - Content ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated content
 */
export async function updateContent(contentId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.content.update(contentId, updatedData);
    }

    // Update local store
    contentStore.update(content => 
      content.map(item => 
        item.id === contentId 
          ? { ...item, ...updatedData }
          : item
      )
    );

    log(`[Content] Updated content: ${contentId}`, 'info');
    
    const updatedContent = await getContentById(contentId);
    return updatedContent;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Content] Error updating content: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Delete content
 * @param {string} contentId - Content ID
 * @returns {Promise<boolean>} Success status
 */
export async function deleteContent(contentId) {
  try {
    // Delete from LiveStore
    if (browser && liveStore) {
      await liveStore.content.delete(contentId);
    }

    // Update local store
    contentStore.update(content => 
      content.filter(item => item.id !== contentId)
    );

    log(`[Content] Deleted content: ${contentId}`, 'info');
    return true;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Content] Error deleting content: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get content by ID
 * @param {string} contentId - Content ID
 * @returns {Promise<Object|null>} Content data or null
 */
export async function getContentById(contentId) {
  try {
    let content = null;

    // Try LiveStore first
    if (browser && liveStore) {
      content = await liveStore.content.findById(contentId);
    }

    // Fallback to local store
    if (!content) {
      const allContent = await new Promise(resolve => {
        contentStore.subscribe(value => resolve(value))();
      });
      content = allContent.find(c => c.id === contentId);
    }

    return content ? getContentData(content) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Content] Error getting content by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get content by status
 * @param {ContentStatus} status - Content status
 * @returns {Promise<Object[]>} Array of content data objects
 */
export async function getContentByStatus(status) {
  try {
    let content = [];

    // Try LiveStore first
    if (browser && liveStore) {
      content = await liveStore.content.findMany({
        where: { status }
      });
    }

    // Fallback to local store
    if (content.length === 0) {
      const allContent = await new Promise(resolve => {
        contentStore.subscribe(value => resolve(value))();
      });
      content = allContent.filter(item => item.status === status);
    }

    return content.map(item => getContentData(item));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Content] Error getting content by status: ${errorMessage}`, 'error');
    return [];
  }
}

/**
 * Search content
 * @param {string} query - Search query
 * @param {Object} filters - Additional filters
 * @returns {Promise<Object[]>} Array of matching content
 */
export async function searchContent(query, filters = {}) {
  try {
    let content = [];

    // Try LiveStore first
    if (browser && liveStore) {
      // Build search criteria
      const searchCriteria = {
        OR: [
          { title: { contains: query } },
          { content: { contains: query } },
          { excerpt: { contains: query } }
        ]
      };

      // Add filters
      if (filters.contentType) {
        searchCriteria.contentType = filters.contentType;
      }
      if (filters.status) {
        searchCriteria.status = filters.status;
      }

      content = await liveStore.content.findMany({
        where: searchCriteria
      });
    }

    // Fallback to local store
    if (content.length === 0) {
      const allContent = await new Promise(resolve => {
        contentStore.subscribe(value => resolve(value))();
      });
      
      content = allContent.filter(item => {
        const contentData = getContentData(item);
        let matches = contentData.containsText(query);
        
        if (filters.contentType && item.contentType !== filters.contentType) {
          matches = false;
        }
        if (filters.status && item.status !== filters.status) {
          matches = false;
        }
        
        return matches;
      });
    }

    return content.map(item => getContentData(item));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Content] Error searching content: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: contentStore,
  getContentData,
  createContent,
  updateContent,
  deleteContent,
  getContentById,
  getContentByStatus,
  searchContent
}; 