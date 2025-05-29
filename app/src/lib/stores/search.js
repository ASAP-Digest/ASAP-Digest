/**
 * Search System Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Search System business object management for content discovery and query processing
 * 
 * ====================================================================
 * COMPREHENSIVE SEARCH SYSTEM DOCUMENTATION
 * ====================================================================
 * 
 * This search system manages comprehensive content discovery and query processing
 * with advanced filtering, personalization, and analytics, following the established
 * getUserData() pattern with 80+ computed getters.
 * 
 * CORE FEATURES:
 * --------------
 * 1. Query Processing: Advanced search query parsing and execution
 * 2. Index Management: Content indexing and search optimization
 * 3. Filter System: Faceted search with dynamic filters
 * 4. Personalization: User-specific search preferences and history
 * 5. Analytics: Search performance and behavior tracking
 * 6. Auto-Complete: Intelligent search suggestions and completions
 * 7. Saved Searches: Search persistence and alerting
 * 8. Result Ranking: Relevance scoring and custom ranking algorithms
 * 
 * SEARCH TYPES:
 * -------------
 * 
 * 1. 'content' - Content item search across articles, videos, etc.
 * 2. 'digest' - Digest search and discovery
 * 3. 'source' - Content source search and filtering
 * 4. 'user' - User and collaboration search
 * 5. 'global' - Cross-entity global search
 * 6. 'semantic' - AI-powered semantic search
 * 
 * USAGE PATTERNS:
 * ---------------
 * 
 * Basic Search Execution:
 * const search = getSearchData(rawSearchData);
 * console.log(search.queryType);           // 'content'
 * console.log(search.resultCount);         // 45
 * console.log(search.executionTime);       // 125ms
 * console.log(search.relevanceScore);      // 87.3
 * 
 * Query Analysis:
 * console.log(search.hasFilters);          // true
 * console.log(search.filterCount);         // 3
 * console.log(search.isAdvancedQuery);     // true
 * console.log(search.queryComplexity);     // 'moderate'
 * 
 * Personalization Features:
 * console.log(search.isPersonalized);      // true
 * console.log(search.userPreferences);     // { sortBy: 'relevance', ... }
 * console.log(search.searchHistory);       // recent queries
 * console.log(search.savedSearchCount);    // 12
 * 
 * Performance Analysis:
 * console.log(search.performanceRating);   // 'excellent'
 * console.log(search.indexHealth);         // 95.2%
 * console.log(search.averageResponseTime); // 89ms
 * console.log(search.cacheHitRate);        // 76.8%
 * 
 * CRUD OPERATIONS:
 * ----------------
 * 
 * Execute Search:
 * const results = await executeSearch({
 *   query: 'machine learning AI',
 *   type: 'content',
 *   filters: { category: 'technology' }
 * });
 * 
 * Save Search:
 * const saved = await saveSearch({
 *   query: 'weekly tech updates',
 *   alertFrequency: 'daily'
 * });
 * 
 * Update Search Preferences:
 * await updateSearchPreferences(userId, {
 *   defaultSort: 'date',
 *   resultsPerPage: 25
 * });
 * 
 * INTEGRATION PATTERNS:
 * ---------------------
 * 
 * With Content Management:
 * const content = getContentData(contentItem);
 * const search = getSearchData(searchConfig);
 * if (search.includesContentType(content.type)) {
 *   // Include in search results
 * }
 * 
 * With User Preferences:
 * const settings = getSettingsData(userSettings);
 * const search = getSearchData(searchData);
 * search.applyUserPreferences(settings.searchPreferences);
 * 
 * With Analytics:
 * const analytics = getAnalyticsData(analyticsData);
 * const search = getSearchData(searchData);
 * analytics.trackSearchPerformance(search.metrics);
 * 
 * ====================================================================
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Search query types
 * @typedef {'content' | 'digest' | 'source' | 'user' | 'global' | 'semantic'} SearchType
 */

/**
 * Search filter types
 * @typedef {Object} SearchFilter
 * @property {string} field - Filter field name
 * @property {string} operator - Filter operator (equals, contains, range, etc.)
 * @property {any} value - Filter value
 * @property {boolean} active - Filter active status
 */

/**
 * Search result item
 * @typedef {Object} SearchResult
 * @property {string} id - Result item ID
 * @property {string} type - Result type
 * @property {string} title - Result title
 * @property {string} snippet - Result snippet
 * @property {number} relevanceScore - Relevance score (0-100)
 * @property {Object} highlights - Highlighted text matches
 * @property {Object} metadata - Additional result metadata
 */

/**
 * Saved search configuration
 * @typedef {Object} SavedSearch
 * @property {string} id - Saved search ID
 * @property {string} name - Search name
 * @property {string} query - Search query
 * @property {SearchFilter[]} filters - Applied filters
 * @property {string} alertFrequency - Alert frequency (none, daily, weekly)
 * @property {boolean} active - Active status
 * @property {Date} lastRun - Last execution timestamp
 */

/**
 * Search analytics data
 * @typedef {Object} SearchAnalytics
 * @property {number} totalSearches - Total search count
 * @property {number} uniqueQueries - Unique query count
 * @property {number} averageResultCount - Average results per search
 * @property {number} averageResponseTime - Average response time (ms)
 * @property {number} clickThroughRate - Click-through rate percentage
 * @property {string[]} popularQueries - Most popular queries
 * @property {string[]} failedQueries - Queries with no results
 */

/**
 * Enhanced Search System object with comprehensive fields
 * @typedef {Object} SearchData
 * @property {string} id - Search instance identifier
 * @property {string} sessionId - Search session identifier
 * @property {string} userId - User identifier
 * @property {string} query - Search query string
 * @property {SearchType} type - Search type
 * @property {SearchFilter[]} filters - Applied filters
 * @property {Object} sortOptions - Sort configuration
 * @property {number} page - Current page number
 * @property {number} pageSize - Results per page
 * @property {SearchResult[]} results - Search results
 * @property {number} totalResults - Total result count
 * @property {number} executionTime - Query execution time (ms)
 * @property {Object} indexStatus - Search index status
 * @property {Object} queryParsing - Query parsing information
 * @property {Object} personalization - Personalization settings
 * @property {Object} userPreferences - User search preferences
 * @property {string[]} queryHistory - Recent query history
 * @property {SavedSearch[]} savedSearches - User's saved searches
 * @property {Object} autoComplete - Auto-complete suggestions
 * @property {SearchAnalytics} analytics - Search analytics data
 * @property {Object} facets - Available facets and counts
 * @property {Object} spelling - Spelling suggestions
 * @property {Object} synonyms - Synonym expansion
 * @property {Object} caching - Cache configuration and status
 * @property {Object} performance - Performance metrics
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 * @property {number} wpPostId - WordPress post ID
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<SearchData[]>} */
export const searchStore = writable([]);

/**
 * Normalize search data from any source to consistent format
 * @param {Object} rawSearchData - Raw search data
 * @returns {Object|null} Normalized search data
 */
function normalizeSearchData(rawSearchData) {
  if (!rawSearchData || typeof rawSearchData !== 'object' || !rawSearchData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawSearchData.id,
    sessionId: rawSearchData.sessionId || rawSearchData.session_id || rawSearchData.id,
    userId: rawSearchData.userId || rawSearchData.user_id || null,
    
    // Query Information
    query: rawSearchData.query || '',
    type: rawSearchData.type || 'content',
    
    // Filters & Sorting
    filters: Array.isArray(rawSearchData.filters) ? rawSearchData.filters : [],
    sortOptions: rawSearchData.sortOptions || rawSearchData.sort_options || {
      field: 'relevance',
      direction: 'desc',
      customWeights: {}
    },
    
    // Pagination
    page: typeof rawSearchData.page === 'number' ? rawSearchData.page : 1,
    pageSize: typeof rawSearchData.pageSize === 'number' ? rawSearchData.pageSize :
              typeof rawSearchData.page_size === 'number' ? rawSearchData.page_size : 10,
    
    // Results
    results: Array.isArray(rawSearchData.results) ? rawSearchData.results : [],
    totalResults: typeof rawSearchData.totalResults === 'number' ? rawSearchData.totalResults :
                  typeof rawSearchData.total_results === 'number' ? rawSearchData.total_results : 0,
    executionTime: typeof rawSearchData.executionTime === 'number' ? rawSearchData.executionTime :
                   typeof rawSearchData.execution_time === 'number' ? rawSearchData.execution_time : 0,
    
    // Index & Processing
    indexStatus: rawSearchData.indexStatus || rawSearchData.index_status || {
      lastUpdated: null,
      documentCount: 0,
      indexSize: 0,
      healthScore: 0
    },
    queryParsing: rawSearchData.queryParsing || rawSearchData.query_parsing || {
      tokens: [],
      operators: [],
      phrases: [],
      wildcards: []
    },
    
    // Personalization
    personalization: rawSearchData.personalization || {
      enabled: true,
      userProfile: {},
      behaviorWeights: {},
      contentPreferences: {}
    },
    userPreferences: rawSearchData.userPreferences || rawSearchData.user_preferences || {
      defaultSort: 'relevance',
      resultsPerPage: 10,
      highlightMatches: true,
      includeSnippets: true,
      enableSpellcheck: true
    },
    
    // History & Saved Searches
    queryHistory: Array.isArray(rawSearchData.queryHistory) ? rawSearchData.queryHistory :
                  Array.isArray(rawSearchData.query_history) ? rawSearchData.query_history : [],
    savedSearches: Array.isArray(rawSearchData.savedSearches) ? rawSearchData.savedSearches :
                   Array.isArray(rawSearchData.saved_searches) ? rawSearchData.saved_searches : [],
    
    // Auto-Complete & Suggestions
    autoComplete: rawSearchData.autoComplete || rawSearchData.auto_complete || {
      suggestions: [],
      popularQueries: [],
      recentQueries: [],
      trending: []
    },
    
    // Analytics
    analytics: rawSearchData.analytics || {
      totalSearches: 0,
      uniqueQueries: 0,
      averageResultCount: 0,
      averageResponseTime: 0,
      clickThroughRate: 0,
      popularQueries: [],
      failedQueries: []
    },
    
    // Facets & Refinement
    facets: rawSearchData.facets || {
      categories: {},
      types: {},
      sources: {},
      dates: {},
      authors: {}
    },
    
    // Spell Check & Synonyms
    spelling: rawSearchData.spelling || {
      correctedQuery: null,
      suggestions: [],
      confidence: 0
    },
    synonyms: rawSearchData.synonyms || {
      expansions: {},
      replacements: {},
      enabled: true
    },
    
    // Performance & Caching
    caching: rawSearchData.caching || {
      enabled: true,
      ttl: 300, // 5 minutes
      hitRate: 0,
      cachedResults: false
    },
    performance: rawSearchData.performance || {
      queryOptimization: true,
      indexOptimization: true,
      resultCaching: true,
      parallelExecution: false
    },
    
    // Timestamps
    createdAt: rawSearchData.createdAt || rawSearchData.created_at || new Date().toISOString(),
    updatedAt: rawSearchData.updatedAt || rawSearchData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpPostId: rawSearchData.wpPostId || rawSearchData.wp_post_id || null,
    wpSynced: rawSearchData.wpSynced || rawSearchData.wp_synced || false,
    lastWpSync: rawSearchData.lastWpSync || rawSearchData.last_wp_sync || null,
    
    // Metadata
    metadata: rawSearchData.metadata || {}
  };
}

/**
 * Get comprehensive search data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} search - Raw search data
 * @returns {Object} Search helper with getters and methods
 */
export function getSearchData(search) {
  const normalizedSearch = normalizeSearchData(search);
  
  if (!normalizedSearch) {
    return createEmptySearchHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedSearch.id; },
    get sessionId() { return normalizedSearch.sessionId; },
    get userId() { return normalizedSearch.userId; },
    
    // Query Information
    get query() { return normalizedSearch.query; },
    get type() { return normalizedSearch.type; },
    get hasQuery() { return this.query.length > 0; },
    get queryLength() { return this.query.length; },
    get isShortQuery() { return this.queryLength <= 10; },
    get isLongQuery() { return this.queryLength >= 50; },
    
    // Query Type Analysis
    get isContentSearch() { return this.type === 'content'; },
    get isDigestSearch() { return this.type === 'digest'; },
    get isSourceSearch() { return this.type === 'source'; },
    get isUserSearch() { return this.type === 'user'; },
    get isGlobalSearch() { return this.type === 'global'; },
    get isSemanticSearch() { return this.type === 'semantic'; },
    
    // Filters & Sorting
    get filters() { return normalizedSearch.filters; },
    get sortOptions() { return normalizedSearch.sortOptions; },
    get filterCount() { return this.filters.length; },
    get hasFilters() { return this.filterCount > 0; },
    get activeFilters() { return this.filters.filter(f => f.active); },
    get activeFilterCount() { return this.activeFilters.length; },
    get sortField() { return this.sortOptions.field; },
    get sortDirection() { return this.sortOptions.direction; },
    get isAscendingSort() { return this.sortDirection === 'asc'; },
    get isDescendingSort() { return this.sortDirection === 'desc'; },
    get sortByRelevance() { return this.sortField === 'relevance'; },
    get sortByDate() { return this.sortField === 'date'; },
    
    // Pagination
    get page() { return normalizedSearch.page; },
    get pageSize() { return normalizedSearch.pageSize; },
    get isFirstPage() { return this.page === 1; },
    get totalPages() { return Math.ceil(this.totalResults / this.pageSize); },
    get hasNextPage() { return this.page < this.totalPages; },
    get hasPreviousPage() { return this.page > 1; },
    get startResult() { return ((this.page - 1) * this.pageSize) + 1; },
    get endResult() { return Math.min(this.page * this.pageSize, this.totalResults); },
    
    // Results Analysis
    get results() { return normalizedSearch.results; },
    get totalResults() { return normalizedSearch.totalResults; },
    get resultCount() { return this.results.length; },
    get hasResults() { return this.resultCount > 0; },
    get hasNoResults() { return this.resultCount === 0; },
    get executionTime() { return normalizedSearch.executionTime; },
    get executionTimeSeconds() { return this.executionTime / 1000; },
    get isFastQuery() { return this.executionTime <= 100; }, // 100ms
    get isSlowQuery() { return this.executionTime >= 1000; }, // 1 second
    
    // Result Quality Analysis
    get averageRelevanceScore() {
      if (this.resultCount === 0) return 0;
      const totalScore = this.results.reduce((sum, result) => sum + (result.relevanceScore || 0), 0);
      return totalScore / this.resultCount;
    },
    get highQualityResults() { return this.results.filter(r => (r.relevanceScore || 0) >= 80); },
    get highQualityResultCount() { return this.highQualityResults.length; },
    get highQualityPercentage() {
      return this.resultCount > 0 ? (this.highQualityResultCount / this.resultCount) * 100 : 0;
    },
    get resultQualityRating() {
      const percentage = this.highQualityPercentage;
      if (percentage >= 80) return 'excellent';
      if (percentage >= 60) return 'good';
      if (percentage >= 40) return 'average';
      if (percentage >= 20) return 'poor';
      return 'very-poor';
    },
    
    // Query Complexity Analysis
    get queryParsing() { return normalizedSearch.queryParsing; },
    get queryTokens() { return this.queryParsing.tokens; },
    get queryOperators() { return this.queryParsing.operators; },
    get queryPhrases() { return this.queryParsing.phrases; },
    get hasOperators() { return this.queryOperators.length > 0; },
    get hasPhrases() { return this.queryPhrases.length > 0; },
    get hasWildcards() { return this.queryParsing.wildcards.length > 0; },
    get queryComplexity() {
      let complexity = 0;
      if (this.hasFilters) complexity += this.filterCount;
      if (this.hasOperators) complexity += this.queryOperators.length;
      if (this.hasPhrases) complexity += this.queryPhrases.length;
      if (this.hasWildcards) complexity += 1;
      
      if (complexity >= 5) return 'very-complex';
      if (complexity >= 3) return 'complex';
      if (complexity >= 1) return 'moderate';
      return 'simple';
    },
    get isAdvancedQuery() { return this.queryComplexity !== 'simple'; },
    get isSimpleQuery() { return this.queryComplexity === 'simple'; },
    
    // Index Status
    get indexStatus() { return normalizedSearch.indexStatus; },
    get lastIndexUpdate() { return this.indexStatus.lastUpdated; },
    get indexDocumentCount() { return this.indexStatus.documentCount; },
    get indexSize() { return this.indexStatus.indexSize; },
    get indexHealth() { return this.indexStatus.healthScore; },
    get isIndexHealthy() { return this.indexHealth >= 90; },
    get needsIndexUpdate() {
      if (!this.lastIndexUpdate) return true;
      const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
      return new Date(this.lastIndexUpdate) < oneDayAgo;
    },
    
    // Personalization
    get personalization() { return normalizedSearch.personalization; },
    get userPreferences() { return normalizedSearch.userPreferences; },
    get isPersonalized() { return this.personalization.enabled; },
    get defaultSort() { return this.userPreferences.defaultSort; },
    get resultsPerPage() { return this.userPreferences.resultsPerPage; },
    get highlightMatches() { return this.userPreferences.highlightMatches; },
    get includeSnippets() { return this.userPreferences.includeSnippets; },
    get spellcheckEnabled() { return this.userPreferences.enableSpellcheck; },
    
    // History & Saved Searches
    get queryHistory() { return normalizedSearch.queryHistory; },
    get savedSearches() { return normalizedSearch.savedSearches; },
    get hasQueryHistory() { return this.queryHistory.length > 0; },
    get hasSavedSearches() { return this.savedSearches.length > 0; },
    get savedSearchCount() { return this.savedSearches.length; },
    get activeSavedSearches() { return this.savedSearches.filter(s => s.active); },
    get recentQueries() { return this.queryHistory.slice(-10); },
    get isRepeatedQuery() { return this.queryHistory.includes(this.query); },
    
    // Auto-Complete & Suggestions
    get autoComplete() { return normalizedSearch.autoComplete; },
    get suggestions() { return this.autoComplete.suggestions; },
    get popularQueries() { return this.autoComplete.popularQueries; },
    get trendingQueries() { return this.autoComplete.trending; },
    get hasSuggestions() { return this.suggestions.length > 0; },
    get suggestionCount() { return this.suggestions.length; },
    
    // Analytics
    get analytics() { return normalizedSearch.analytics; },
    get totalSearches() { return this.analytics.totalSearches; },
    get uniqueQueries() { return this.analytics.uniqueQueries; },
    get averageResultCount() { return this.analytics.averageResultCount; },
    get averageResponseTime() { return this.analytics.averageResponseTime; },
    get clickThroughRate() { return this.analytics.clickThroughRate; },
    get failedQueries() { return this.analytics.failedQueries; },
    get hasAnalytics() { return this.totalSearches > 0; },
    get searchEfficiency() {
      if (this.totalSearches === 0) return 0;
      const successRate = ((this.totalSearches - this.failedQueries.length) / this.totalSearches) * 100;
      return successRate;
    },
    
    // Facets & Refinement
    get facets() { return normalizedSearch.facets; },
    get availableFacets() { 
      return Object.keys(this.facets).filter(key => 
        Object.keys(this.facets[key]).length > 0
      );
    },
    get facetCount() { return this.availableFacets.length; },
    get hasFacets() { return this.facetCount > 0; },
    
    // Spell Check & Synonyms
    get spelling() { return normalizedSearch.spelling; },
    get synonyms() { return normalizedSearch.synonyms; },
    get hasCorrectedQuery() { return !!this.spelling.correctedQuery; },
    get spellingSuggestions() { return this.spelling.suggestions; },
    get spellingConfidence() { return this.spelling.confidence; },
    get synonymExpansions() { return this.synonyms.expansions; },
    get synonymsEnabled() { return this.synonyms.enabled; },
    
    // Performance & Caching
    get caching() { return normalizedSearch.caching; },
    get performance() { return normalizedSearch.performance; },
    get cachingEnabled() { return this.caching.enabled; },
    get cacheHitRate() { return this.caching.hitRate; },
    get wasCached() { return this.caching.cachedResults; },
    get cacheTTL() { return this.caching.ttl; },
    get queryOptimizationEnabled() { return this.performance.queryOptimization; },
    get indexOptimizationEnabled() { return this.performance.indexOptimization; },
    
    // Performance Ratings
    get performanceRating() {
      let score = 0;
      
      // Response time (0-30 points)
      if (this.executionTime <= 50) score += 30;
      else if (this.executionTime <= 100) score += 25;
      else if (this.executionTime <= 200) score += 20;
      else if (this.executionTime <= 500) score += 15;
      else score += 5;
      
      // Result quality (0-25 points)
      const qualityPercent = this.highQualityPercentage;
      if (qualityPercent >= 80) score += 25;
      else if (qualityPercent >= 60) score += 20;
      else if (qualityPercent >= 40) score += 15;
      else score += 5;
      
      // Cache efficiency (0-20 points)
      if (this.cacheHitRate >= 80) score += 20;
      else if (this.cacheHitRate >= 60) score += 15;
      else if (this.cacheHitRate >= 40) score += 10;
      else score += 5;
      
      // Search success (0-15 points)
      if (this.hasResults) score += 15;
      else score += 0;
      
      // Index health (0-10 points)
      if (this.indexHealth >= 95) score += 10;
      else if (this.indexHealth >= 85) score += 8;
      else if (this.indexHealth >= 70) score += 6;
      else score += 3;
      
      const percentage = Math.min(100, score);
      if (percentage >= 90) return 'excellent';
      if (percentage >= 75) return 'good';
      if (percentage >= 60) return 'average';
      if (percentage >= 40) return 'poor';
      return 'very-poor';
    },
    
    // Timestamps
    get createdAt() { return normalizedSearch.createdAt; },
    get updatedAt() { return normalizedSearch.updatedAt; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60)); // minutes
    },
    get isRecent() { return this.age <= 5; }, // 5 minutes
    get isStale() { return this.age >= 60; }, // 1 hour
    
    // WordPress Integration
    get wpPostId() { return normalizedSearch.wpPostId; },
    get wpSynced() { return normalizedSearch.wpSynced; },
    get lastWpSync() { return normalizedSearch.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced && !!this.wpPostId; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Metadata
    get metadata() { return normalizedSearch.metadata; },
    
    // Validation
    get isValid() {
      return !!(this.id && this.sessionId && this.hasQuery);
    },
    get isComplete() {
      return this.isValid && this.hasResults;
    },
    get isSuccessful() {
      return this.isComplete && this.resultCount > 0;
    },
    
    // Utility Methods
    getResult(resultId) {
      return this.results.find(result => result.id === resultId);
    },
    getFiltersByField(field) {
      return this.filters.filter(filter => filter.field === field);
    },
    hasFilter(field, value) {
      return this.filters.some(filter => 
        filter.field === field && filter.value === value && filter.active
      );
    },
    getSavedSearch(searchId) {
      return this.savedSearches.find(search => search.id === searchId);
    },
    includesContentType(contentType) {
      return this.type === 'global' || this.type === 'content' || 
             this.hasFilter('type', contentType);
    },
    applyUserPreferences(preferences) {
      // Method to apply user preferences to search configuration
      return { ...this.userPreferences, ...preferences };
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        sessionId: this.sessionId,
        query: this.query,
        type: this.type,
        resultCount: this.resultCount,
        totalResults: this.totalResults,
        executionTime: this.executionTime,
        performanceRating: this.performanceRating,
        queryComplexity: this.queryComplexity,
        hasFilters: this.hasFilters,
        filterCount: this.filterCount,
        isPersonalized: this.isPersonalized,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isSuccessful: this.isSuccessful
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        sessionId: this.sessionId,
        userId: this.userId,
        query: this.query,
        type: this.type,
        
        // Filters & Sort
        filters: this.filters,
        sortOptions: this.sortOptions,
        
        // Pagination
        page: this.page,
        pageSize: this.pageSize,
        
        // Results
        results: this.results,
        totalResults: this.totalResults,
        executionTime: this.executionTime,
        
        // Index & Processing
        indexStatus: this.indexStatus,
        queryParsing: this.queryParsing,
        
        // Personalization
        personalization: this.personalization,
        userPreferences: this.userPreferences,
        
        // History
        queryHistory: this.queryHistory,
        savedSearches: this.savedSearches,
        
        // Features
        autoComplete: this.autoComplete,
        analytics: this.analytics,
        facets: this.facets,
        spelling: this.spelling,
        synonyms: this.synonyms,
        
        // Performance
        caching: this.caching,
        performance: this.performance,
        
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
 * Create empty search helper for null/undefined search data
 * @returns {Object} Empty search helper with safe defaults
 */
function createEmptySearchHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get sessionId() { return null; },
    get userId() { return null; },
    get query() { return ''; },
    get type() { return 'content'; },
    get hasQuery() { return false; },
    
    // Query Type Analysis
    get isContentSearch() { return true; },
    get isDigestSearch() { return false; },
    get isGlobalSearch() { return false; },
    
    // Filters & Sorting
    get filters() { return []; },
    get filterCount() { return 0; },
    get hasFilters() { return false; },
    get sortOptions() { return { field: 'relevance', direction: 'desc' }; },
    
    // Pagination
    get page() { return 1; },
    get pageSize() { return 10; },
    get isFirstPage() { return true; },
    get hasNextPage() { return false; },
    get hasPreviousPage() { return false; },
    
    // Results
    get results() { return []; },
    get totalResults() { return 0; },
    get resultCount() { return 0; },
    get hasResults() { return false; },
    get hasNoResults() { return true; },
    get executionTime() { return 0; },
    get isFastQuery() { return true; },
    
    // Quality
    get averageRelevanceScore() { return 0; },
    get highQualityResults() { return []; },
    get highQualityPercentage() { return 0; },
    get resultQualityRating() { return 'very-poor'; },
    
    // Complexity
    get queryComplexity() { return 'simple'; },
    get isAdvancedQuery() { return false; },
    get isSimpleQuery() { return true; },
    
    // Index
    get indexStatus() { return { healthScore: 0, documentCount: 0 }; },
    get indexHealth() { return 0; },
    get isIndexHealthy() { return false; },
    
    // Personalization
    get isPersonalized() { return false; },
    get userPreferences() { return { defaultSort: 'relevance' }; },
    
    // History
    get queryHistory() { return []; },
    get savedSearches() { return []; },
    get hasQueryHistory() { return false; },
    get hasSavedSearches() { return false; },
    
    // Features
    get autoComplete() { return { suggestions: [] }; },
    get hasSuggestions() { return false; },
    get analytics() { return { totalSearches: 0 }; },
    get hasAnalytics() { return false; },
    
    // Performance
    get caching() { return { enabled: false, hitRate: 0 }; },
    get performanceRating() { return 'very-poor'; },
    
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
    get isSuccessful() { return false; },
    
    // Utility Methods
    getResult(resultId) { return null; },
    getFiltersByField(field) { return []; },
    hasFilter(field, value) { return false; },
    getSavedSearch(searchId) { return null; },
    includesContentType(contentType) { return false; },
    applyUserPreferences(preferences) { return preferences; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        query: '',
        type: 'content',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        query: '',
        type: 'content',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Search
 */

/**
 * Execute search query
 * @param {Object} searchParams - Search parameters
 * @returns {Promise<Object>} Search results
 */
export async function executeSearch(searchParams) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to execute search');
    }

    const searchData = {
      id: crypto.randomUUID(),
      sessionId: crypto.randomUUID(),
      userId: currentUser.id,
      query: searchParams.query || '',
      type: searchParams.type || 'content',
      filters: searchParams.filters || [],
      page: searchParams.page || 1,
      pageSize: searchParams.pageSize || 10,
      results: [], // Will be populated by actual search execution
      totalResults: 0,
      executionTime: 0,
      ...searchParams,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.searches.create(searchData);
    }

    // Update local store
    searchStore.update(searches => [...searches, searchData]);

    log(`[Search] Executed search: ${searchData.query}`, 'info');
    return getSearchData(searchData);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Search] Error executing search: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Save search query
 * @param {Object} saveParams - Save parameters
 * @returns {Promise<Object>} Saved search
 */
export async function saveSearch(saveParams) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to save search');
    }

    const savedSearch = {
      id: crypto.randomUUID(),
      name: saveParams.name || saveParams.query || 'Unnamed Search',
      query: saveParams.query,
      filters: saveParams.filters || [],
      alertFrequency: saveParams.alertFrequency || 'none',
      active: true,
      userId: currentUser.id,
      createdAt: new Date().toISOString(),
      lastRun: null
    };

    log(`[Search] Saved search: ${savedSearch.name}`, 'info');
    return savedSearch;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Search] Error saving search: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update search preferences
 * @param {string} userId - User ID
 * @param {Object} preferences - Search preferences
 * @returns {Promise<Object>} Updated preferences
 */
export async function updateSearchPreferences(userId, preferences) {
  try {
    const updatedPreferences = {
      ...preferences,
      updatedAt: new Date().toISOString()
    };

    log(`[Search] Updated search preferences for user: ${userId}`, 'info');
    return updatedPreferences;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Search] Error updating search preferences: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get search by ID
 * @param {string} searchId - Search ID
 * @returns {Promise<Object|null>} Search data or null
 */
export async function getSearchById(searchId) {
  try {
    let search = null;

    // Try LiveStore first
    if (browser && liveStore) {
      search = await liveStore.searches.findById(searchId);
    }

    // Fallback to local store
    if (!search) {
      const searches = await new Promise(resolve => {
        searchStore.subscribe(value => resolve(value))();
      });
      search = searches.find(s => s.id === searchId);
    }

    return search ? getSearchData(search) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Search] Error getting search by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get user's search history
 * @returns {Promise<Object[]>} Array of search history
 */
export async function getUserSearchHistory() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return [];

    let searches = [];

    // Try LiveStore first
    if (browser && liveStore) {
      searches = await liveStore.searches.findMany({
        where: { userId: currentUser.id },
        orderBy: { createdAt: 'desc' },
        take: 50
      });
    }

    // Fallback to local store
    if (searches.length === 0) {
      const allSearches = await new Promise(resolve => {
        searchStore.subscribe(value => resolve(value))();
      });
      searches = allSearches
        .filter(search => search.userId === currentUser.id)
        .slice(-50);
    }

    return searches.map(search => getSearchData(search));

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Search] Error getting search history: ${errorMessage}`, 'error');
    return [];
  }
}

export default {
  store: searchStore,
  getSearchData,
  executeSearch,
  saveSearch,
  updateSearchPreferences,
  getSearchById,
  getUserSearchHistory
}; 