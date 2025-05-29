/**
 * Content Discovery Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Content Discovery business object management for intelligent content recommendation and trending analysis
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Discovery algorithm types
 * @typedef {'trending' | 'personalized' | 'collaborative' | 'content_based' | 'hybrid'} DiscoveryAlgorithm
 */

/**
 * Enhanced Content Discovery object with comprehensive fields
 * @typedef {Object} ContentDiscoveryData
 * @property {string} id - Discovery instance identifier
 * @property {string} userId - User identifier
 * @property {Object[]} trendingContent - Trending content items
 * @property {Object[]} recommendedSources - Recommended content sources
 * @property {Object[]} topicClusters - Content topic clusters
 * @property {Object[]} contentRelationships - Content relationship mappings
 * @property {Object[]} userInterests - User interest profiles
 * @property {Object} discoveryPatterns - Discovery behavior patterns
 * @property {Object} algorithmSettings - Algorithm configuration
 * @property {Object} personalizationWeights - Personalization weights
 * @property {Object} discoveryMetrics - Discovery performance metrics
 * @property {Object} engagementTracking - Engagement tracking data
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 */

/** @type {import('svelte/store').Writable<ContentDiscoveryData[]>} */
export const discoveryStore = writable([]);

/**
 * Normalize discovery data from any source to consistent format
 * @param {Object} rawDiscoveryData - Raw discovery data
 * @returns {Object|null} Normalized discovery data
 */
function normalizeDiscoveryData(rawDiscoveryData) {
  if (!rawDiscoveryData || typeof rawDiscoveryData !== 'object' || !rawDiscoveryData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawDiscoveryData.id,
    userId: rawDiscoveryData.userId || rawDiscoveryData.user_id || null,
    
    // Content Discovery
    trendingContent: Array.isArray(rawDiscoveryData.trendingContent) ? rawDiscoveryData.trendingContent :
                     Array.isArray(rawDiscoveryData.trending_content) ? rawDiscoveryData.trending_content : [],
    recommendedSources: Array.isArray(rawDiscoveryData.recommendedSources) ? rawDiscoveryData.recommendedSources :
                        Array.isArray(rawDiscoveryData.recommended_sources) ? rawDiscoveryData.recommended_sources : [],
    
    // Topic Analysis
    topicClusters: Array.isArray(rawDiscoveryData.topicClusters) ? rawDiscoveryData.topicClusters :
                   Array.isArray(rawDiscoveryData.topic_clusters) ? rawDiscoveryData.topic_clusters : [],
    contentRelationships: Array.isArray(rawDiscoveryData.contentRelationships) ? rawDiscoveryData.contentRelationships :
                          Array.isArray(rawDiscoveryData.content_relationships) ? rawDiscoveryData.content_relationships : [],
    
    // User Profiling
    userInterests: Array.isArray(rawDiscoveryData.userInterests) ? rawDiscoveryData.userInterests :
                   Array.isArray(rawDiscoveryData.user_interests) ? rawDiscoveryData.user_interests : [],
    discoveryPatterns: rawDiscoveryData.discoveryPatterns || rawDiscoveryData.discovery_patterns || {
      preferredCategories: [],
      preferredSources: [],
      readingTime: 0,
      engagementRate: 0
    },
    
    // Algorithm Configuration
    algorithmSettings: rawDiscoveryData.algorithmSettings || rawDiscoveryData.algorithm_settings || {
      primaryAlgorithm: 'hybrid',
      trendingWeight: 0.3,
      personalizedWeight: 0.4,
      collaborativeWeight: 0.2,
      contentBasedWeight: 0.1
    },
    personalizationWeights: rawDiscoveryData.personalizationWeights || rawDiscoveryData.personalization_weights || {
      categoryPreference: 0.3,
      sourcePreference: 0.2,
      topicSimilarity: 0.25,
      temporalRelevance: 0.15,
      socialSignals: 0.1
    },
    
    // Metrics & Analytics
    discoveryMetrics: rawDiscoveryData.discoveryMetrics || rawDiscoveryData.discovery_metrics || {
      recommendationAccuracy: 0,
      clickThroughRate: 0,
      engagementRate: 0,
      diversityScore: 0,
      noveltyScore: 0,
      serendipityScore: 0
    },
    engagementTracking: rawDiscoveryData.engagementTracking || rawDiscoveryData.engagement_tracking || {
      totalInteractions: 0,
      uniqueContentViewed: 0,
      averageTimeSpent: 0,
      shareRate: 0,
      bookmarkRate: 0
    },
    
    // Timestamps
    createdAt: rawDiscoveryData.createdAt || rawDiscoveryData.created_at || new Date().toISOString(),
    updatedAt: rawDiscoveryData.updatedAt || rawDiscoveryData.updated_at || new Date().toISOString(),
    
    // Metadata
    metadata: rawDiscoveryData.metadata || {}
  };
}

/**
 * Get comprehensive discovery data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} discovery - Raw discovery data
 * @returns {Object} Discovery helper with getters and methods
 */
export function getDiscoveryData(discovery) {
  const normalizedDiscovery = normalizeDiscoveryData(discovery);
  
  if (!normalizedDiscovery) {
    return createEmptyDiscoveryHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedDiscovery.id; },
    get userId() { return normalizedDiscovery.userId; },
    
    // Trending Content Analysis
    get trendingContent() { return normalizedDiscovery.trendingContent; },
    get trendingContentCount() { return this.trendingContent.length; },
    get hasTrendingContent() { return this.trendingContentCount > 0; },
    get topTrendingContent() { return this.trendingContent.slice(0, 10); },
    get trendingCategories() {
      return [...new Set(this.trendingContent.map(content => content.category))];
    },
    
    // Source Recommendations
    get recommendedSources() { return normalizedDiscovery.recommendedSources; },
    get recommendedSourceCount() { return this.recommendedSources.length; },
    get hasRecommendedSources() { return this.recommendedSourceCount > 0; },
    get topRecommendedSources() { return this.recommendedSources.slice(0, 5); },
    
    // Topic Analysis
    get topicClusters() { return normalizedDiscovery.topicClusters; },
    get contentRelationships() { return normalizedDiscovery.contentRelationships; },
    get topicClusterCount() { return this.topicClusters.length; },
    get hasTopicClusters() { return this.topicClusterCount > 0; },
    get primaryTopics() {
      return this.topicClusters
        .sort((a, b) => (b.weight || 0) - (a.weight || 0))
        .slice(0, 5)
        .map(cluster => cluster.topic);
    },
    
    // User Interest Profiling
    get userInterests() { return normalizedDiscovery.userInterests; },
    get discoveryPatterns() { return normalizedDiscovery.discoveryPatterns; },
    get userInterestCount() { return this.userInterests.length; },
    get hasUserInterests() { return this.userInterestCount > 0; },
    get preferredCategories() { return this.discoveryPatterns.preferredCategories; },
    get preferredSources() { return this.discoveryPatterns.preferredSources; },
    get averageReadingTime() { return this.discoveryPatterns.readingTime; },
    get userEngagementRate() { return this.discoveryPatterns.engagementRate; },
    
    // Algorithm Configuration
    get algorithmSettings() { return normalizedDiscovery.algorithmSettings; },
    get personalizationWeights() { return normalizedDiscovery.personalizationWeights; },
    get primaryAlgorithm() { return this.algorithmSettings.primaryAlgorithm; },
    get isHybridAlgorithm() { return this.primaryAlgorithm === 'hybrid'; },
    get isTrendingBased() { return this.primaryAlgorithm === 'trending'; },
    get isPersonalized() { return this.primaryAlgorithm === 'personalized'; },
    
    // Algorithm Weight Analysis
    get trendingWeight() { return this.algorithmSettings.trendingWeight; },
    get personalizedWeight() { return this.algorithmSettings.personalizedWeight; },
    get collaborativeWeight() { return this.algorithmSettings.collaborativeWeight; },
    get contentBasedWeight() { return this.algorithmSettings.contentBasedWeight; },
    get isBalancedWeights() {
      const weights = [this.trendingWeight, this.personalizedWeight, 
                      this.collaborativeWeight, this.contentBasedWeight];
      const maxWeight = Math.max(...weights);
      const minWeight = Math.min(...weights);
      return (maxWeight - minWeight) <= 0.2; // Within 20% range
    },
    
    // Discovery Metrics
    get discoveryMetrics() { return normalizedDiscovery.discoveryMetrics; },
    get engagementTracking() { return normalizedDiscovery.engagementTracking; },
    get recommendationAccuracy() { return this.discoveryMetrics.recommendationAccuracy; },
    get clickThroughRate() { return this.discoveryMetrics.clickThroughRate; },
    get engagementRate() { return this.discoveryMetrics.engagementRate; },
    get diversityScore() { return this.discoveryMetrics.diversityScore; },
    get noveltyScore() { return this.discoveryMetrics.noveltyScore; },
    get serendipityScore() { return this.discoveryMetrics.serendipityScore; },
    
    // Performance Ratings
    get accuracyRating() {
      const accuracy = this.recommendationAccuracy;
      if (accuracy >= 90) return 'excellent';
      if (accuracy >= 75) return 'good';
      if (accuracy >= 60) return 'average';
      if (accuracy >= 40) return 'poor';
      return 'very-poor';
    },
    get engagementRating() {
      const engagement = this.engagementRate;
      if (engagement >= 80) return 'excellent';
      if (engagement >= 60) return 'good';
      if (engagement >= 40) return 'average';
      if (engagement >= 20) return 'poor';
      return 'very-poor';
    },
    
    // Engagement Analysis
    get totalInteractions() { return this.engagementTracking.totalInteractions; },
    get uniqueContentViewed() { return this.engagementTracking.uniqueContentViewed; },
    get averageTimeSpent() { return this.engagementTracking.averageTimeSpent; },
    get shareRate() { return this.engagementTracking.shareRate; },
    get bookmarkRate() { return this.engagementTracking.bookmarkRate; },
    get hasEngagement() { return this.totalInteractions > 0; },
    get isHighEngagement() { return this.engagementRate >= 60; },
    
    // Discovery Quality Score
    get discoveryQualityScore() {
      let score = 0;
      
      // Accuracy (0-25 points)
      score += (this.recommendationAccuracy / 100) * 25;
      
      // Engagement (0-25 points)
      score += (this.engagementRate / 100) * 25;
      
      // Diversity (0-20 points)
      score += (this.diversityScore / 100) * 20;
      
      // Novelty (0-15 points)
      score += (this.noveltyScore / 100) * 15;
      
      // Serendipity (0-15 points)
      score += (this.serendipityScore / 100) * 15;
      
      return Math.min(100, Math.round(score));
    },
    get qualityRating() {
      const score = this.discoveryQualityScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'needs-improvement';
    },
    
    // Content Diversity Analysis
    get isContentDiverse() { return this.diversityScore >= 70; },
    get isContentNovel() { return this.noveltyScore >= 60; },
    get providesSerendipity() { return this.serendipityScore >= 50; },
    get isWellRounded() {
      return this.isContentDiverse && this.isContentNovel && this.providesSerendipity;
    },
    
    // Timestamps
    get createdAt() { return normalizedDiscovery.createdAt; },
    get updatedAt() { return normalizedDiscovery.updatedAt; },
    
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
    get needsRefresh() { return this.daysSinceUpdate > 1; },
    
    // Metadata
    get metadata() { return normalizedDiscovery.metadata; },
    
    // Validation
    get isValid() {
      return !!(this.id && this.userId);
    },
    get isComplete() {
      return this.isValid && (this.hasTrendingContent || this.hasRecommendedSources);
    },
    get isOperational() {
      return this.isComplete && this.hasEngagement;
    },
    
    // Utility Methods
    getTrendingContentByCategory(category) {
      return this.trendingContent.filter(content => content.category === category);
    },
    getTopicCluster(topicId) {
      return this.topicClusters.find(cluster => cluster.id === topicId);
    },
    getUserInterest(interestId) {
      return this.userInterests.find(interest => interest.id === interestId);
    },
    getRecommendedSource(sourceId) {
      return this.recommendedSources.find(source => source.id === sourceId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        trendingContentCount: this.trendingContentCount,
        recommendedSourceCount: this.recommendedSourceCount,
        topicClusterCount: this.topicClusterCount,
        primaryAlgorithm: this.primaryAlgorithm,
        discoveryQualityScore: this.discoveryQualityScore,
        qualityRating: this.qualityRating,
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
        
        // Content data
        trendingContent: this.trendingContent,
        recommendedSources: this.recommendedSources,
        topicClusters: this.topicClusters,
        contentRelationships: this.contentRelationships,
        
        // User data
        userInterests: this.userInterests,
        discoveryPatterns: this.discoveryPatterns,
        
        // Configuration
        algorithmSettings: this.algorithmSettings,
        personalizationWeights: this.personalizationWeights,
        
        // Metrics
        discoveryMetrics: this.discoveryMetrics,
        engagementTracking: this.engagementTracking,
        
        // Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        
        // Metadata
        metadata: this.metadata
      };
    }
  };
}

/**
 * Create empty discovery helper for null/undefined discovery data
 * @returns {Object} Empty discovery helper with safe defaults
 */
function createEmptyDiscoveryHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get userId() { return null; },
    
    // Content Analysis
    get trendingContent() { return []; },
    get trendingContentCount() { return 0; },
    get hasTrendingContent() { return false; },
    get recommendedSources() { return []; },
    get recommendedSourceCount() { return 0; },
    get hasRecommendedSources() { return false; },
    
    // Topics
    get topicClusters() { return []; },
    get topicClusterCount() { return 0; },
    get hasTopicClusters() { return false; },
    get primaryTopics() { return []; },
    
    // User Interests
    get userInterests() { return []; },
    get userInterestCount() { return 0; },
    get hasUserInterests() { return false; },
    get discoveryPatterns() { return { preferredCategories: [], readingTime: 0 }; },
    
    // Algorithm
    get algorithmSettings() { return { primaryAlgorithm: 'hybrid' }; },
    get primaryAlgorithm() { return 'hybrid'; },
    get isHybridAlgorithm() { return true; },
    
    // Metrics
    get discoveryMetrics() { return { recommendationAccuracy: 0, engagementRate: 0 }; },
    get engagementTracking() { return { totalInteractions: 0 }; },
    get recommendationAccuracy() { return 0; },
    get engagementRate() { return 0; },
    get discoveryQualityScore() { return 0; },
    get qualityRating() { return 'needs-improvement'; },
    
    // Performance
    get accuracyRating() { return 'very-poor'; },
    get engagementRating() { return 'very-poor'; },
    get hasEngagement() { return false; },
    
    // Timestamps
    get createdAt() { return null; },
    get updatedAt() { return null; },
    get age() { return 0; },
    get isRecent() { return false; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    
    // Utility Methods
    getTrendingContentByCategory(category) { return []; },
    getTopicCluster(topicId) { return null; },
    getUserInterest(interestId) { return null; },
    getRecommendedSource(sourceId) { return null; },
    
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
 * CRUD Operations for Content Discovery
 */

/**
 * Create discovery configuration
 * @param {Object} discoveryData - Initial discovery data
 * @returns {Promise<Object>} Created discovery configuration
 */
export async function createDiscoveryConfig(discoveryData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create discovery config');
    }

    const newDiscovery = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      trendingContent: [],
      recommendedSources: [],
      topicClusters: [],
      ...discoveryData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.discovery.create(newDiscovery);
    }

    // Update local store
    discoveryStore.update(configs => [...configs, newDiscovery]);

    log(`[Discovery] Created discovery config: ${newDiscovery.id}`, 'info');
    return getDiscoveryData(newDiscovery);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Discovery] Error creating discovery config: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update discovery configuration
 * @param {string} discoveryId - Discovery ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated discovery configuration
 */
export async function updateDiscoveryConfig(discoveryId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.discovery.update(discoveryId, updatedData);
    }

    // Update local store
    discoveryStore.update(configs => 
      configs.map(config => 
        config.id === discoveryId 
          ? { ...config, ...updatedData }
          : config
      )
    );

    log(`[Discovery] Updated discovery config: ${discoveryId}`, 'info');
    
    // Return updated discovery data
    const updatedDiscovery = await getDiscoveryById(discoveryId);
    return updatedDiscovery;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Discovery] Error updating discovery config: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get discovery configuration by ID
 * @param {string} discoveryId - Discovery ID
 * @returns {Promise<Object|null>} Discovery data or null
 */
export async function getDiscoveryById(discoveryId) {
  try {
    let discovery = null;

    // Try LiveStore first
    if (browser && liveStore) {
      discovery = await liveStore.discovery.findById(discoveryId);
    }

    // Fallback to local store
    if (!discovery) {
      const configs = await new Promise(resolve => {
        discoveryStore.subscribe(value => resolve(value))();
      });
      discovery = configs.find(c => c.id === discoveryId);
    }

    return discovery ? getDiscoveryData(discovery) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Discovery] Error getting discovery by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get user's discovery configuration
 * @returns {Promise<Object|null>} User's discovery data or null
 */
export async function getUserDiscoveryConfig() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return null;

    let discovery = null;

    // Try LiveStore first
    if (browser && liveStore) {
      const configs = await liveStore.discovery.findMany({
        where: { userId: currentUser.id },
        orderBy: { updatedAt: 'desc' },
        take: 1
      });
      discovery = configs[0] || null;
    }

    // Fallback to local store
    if (!discovery) {
      const allConfigs = await new Promise(resolve => {
        discoveryStore.subscribe(value => resolve(value))();
      });
      const userConfigs = allConfigs.filter(config => config.userId === currentUser.id);
      discovery = userConfigs.length > 0 ? userConfigs[userConfigs.length - 1] : null;
    }

    return discovery ? getDiscoveryData(discovery) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Discovery] Error getting user discovery config: ${errorMessage}`, 'error');
    return null;
  }
}

export default {
  store: discoveryStore,
  getDiscoveryData,
  createDiscoveryConfig,
  updateDiscoveryConfig,
  getDiscoveryById,
  getUserDiscoveryConfig
}; 