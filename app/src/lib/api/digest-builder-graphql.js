/**
 * Digest Builder GraphQL API
 * Handles all GraphQL operations for the digest creation and management flow
 * Replaces REST API proxy approach to align with original architecture vision
 * 
 * SCHEMA COMPLIANCE:
 * - Follows ASAP_DIGEST_TEMPLATE_ENTITY_SCHEMA.md for template fields
 * - Uses custom wp_asap_digests table for digest storage (not CPTs)
 * - Uses wp_asap_digest_module_placements for Gridstack positioning
 * - Integrates wp_asap_ingested_content + wp_asap_ai_processed_content
 * - All GraphQL field names match the schema meta field specifications
 * - Data transformations preserve schema-defined field structures
 */

import { fetchGraphQL } from '$lib/utils/fetchGraphQL.js';
import { browser } from '$app/environment';
import { authStore } from '$lib/utils/auth-persistence.js';

/**
 * @typedef {Object} ApiResponse
 * @property {boolean} success - Whether the operation was successful
 * @property {any} [data] - Response data if successful
 * @property {string} [error] - Error message if unsuccessful
 */

/**
 * @typedef {Object} LayoutTemplate
 * Following ASAP_DIGEST_TEMPLATE_ENTITY_SCHEMA.md specifications
 * @property {string} id - Template slug identifier
 * @property {string} name - Template display name (_asap_digest_template_name)
 * @property {string} description - Template description (_asap_digest_template_description)
 * @property {string} preview_image_url - Preview image URL (_asap_digest_template_preview_image_url)
 * @property {Object} gridstack_config - GridStack configuration (_asap_digest_template_gridstack_config_json)
 * @property {Array<string>} associated_plan_levels - Plan levels with access (_asap_digest_template_associated_plan_levels)
 * @property {number} sort_order - Display sort order (_asap_digest_template_sort_order)
 * @property {number} minimum_module_items - Minimum modules (_asap_digest_template_minimum_module_items)
 * @property {number} maximum_module_items - Maximum modules (_asap_digest_template_maximum_module_items)
 */

/**
 * @typedef {Object} DigestData
 * Following ASAP_DIGEST_DIGEST_ENTITY_SCHEMA.md specifications
 * @property {string|number} id - Digest database ID
 * @property {string} title - Digest title (post_title)
 * @property {string} status - Digest status (post_status)
 * @property {string} created_at - Creation timestamp (post_date)
 * @property {string} updated_at - Last modified timestamp (post_modified)
 * @property {string} layout_template_id - Layout template slug (_asap_digest_layout_template_slug)
 * @property {string} source_template_id - Source template ID (_asap_digest_source_template_id)
 * @property {Array<number>} module_ids - Module IDs array (_asap_digest_module_ids)
 * @property {Object} gridstack_config - GridStack configuration (_asap_digest_gridstack_config_json)
 * @property {Object} compiled_content - Compiled content (_asap_digest_compiled_content_json)
 * @property {Array<Object>} version_history - Version history (_asap_digest_version_history_json)
 * @property {number} aggregated_sentiment_score - Sentiment score (_asap_digest_aggregated_sentiment_score)
 * @property {string} sentiment_calculated_at - Sentiment calculation timestamp (_asap_digest_sentiment_calculated_at)
 * @property {number} public_views_count - Public view count (_asap_digest_public_views_count)
 * @property {boolean} is_featured_in_explore - Featured in explore flag (_asap_digest_is_featured_in_explore)
 * @property {Array<Object>} linked_digests - Linked digests (_asap_digest_linked_digests_json)
 */

/**
 * GraphQL query to fetch layout templates
 * Updated to match actual backend schema using layoutTemplates query
 * and AsapLayoutTemplate type field names
 */
const GET_LAYOUT_TEMPLATES_QUERY = `
  query GetLayoutTemplates {
    layoutTemplates {
      id
      slug
      title
      description
      gridstackConfig
      defaultPlacements
      maxModules
      isActive
    }
  }
`;

/**
 * GraphQL query to fetch user's digests from custom wp_asap_digests table
 * Updated to use userDigests query which matches the actual backend resolver
 */
const GET_USER_DIGESTS_QUERY = `
  query GetUserDigests($userId: ID!, $status: String) {
    userDigests(userId: $userId, status: $status) {
      id
      userId
      status
      layoutTemplateId
      content
      sentimentScore
      lifeMoment
      isSaved
      reminders
      createdAt
      shareLink
      podcastUrl
      modulePlacements {
        id
        moduleId
        gridX
        gridY
        gridWidth
        gridHeight
        orderInGrid
      }
    }
  }
`;

/**
 * GraphQL query to fetch a specific digest with module placements
 * Updated to match actual backend schema using digest query
 */
const GET_DIGEST_QUERY = `
  query GetDigest($id: ID!) {
    digest(id: $id) {
      id
      userId
      status
      layoutTemplateId
      content
      sentimentScore
      lifeMoment
      isSaved
      reminders
      createdAt
      shareLink
      podcastUrl
      modulePlacements {
        id
        moduleId
        gridX
        gridY
        gridWidth
        gridHeight
        orderInGrid
        module {
          id
          title
          type
          content
          sourceUrl
          publishDate
          aiProcessedContent {
            summary
            keywords
            entities
            classification
            qualityScore
            sentiment
          }
        }
      }
    }
  }
`;

/**
 * GraphQL query to fetch AI-enhanced content for module selection
 * Integrates wp_asap_ingested_content + wp_asap_ai_processed_content
 */
const GET_ENHANCED_CONTENT_QUERY = `
  query GetEnhancedContent($filters: ContentFilters, $pagination: PaginationInput) {
    ingestedContent(where: $filters, pagination: $pagination) {
      nodes {
        id
        type
        title
        content
        sourceUrl
        sourceId
        publishDate
        ingestionDate
        qualityScore
        status
        aiProcessedContent {
          id
          aiModel
          aiModelVersion
          processingType
          processedContent
          processedAt
          qualityScore
          summary
          keywords
          entities
          classification
          sentiment
        }
      }
      pageInfo {
        total
        totalPages
        currentPage
        hasNextPage
        hasPreviousPage
      }
    }
  }
`;

/**
 * GraphQL mutation to create a new digest in custom wp_asap_digests table
 * Updated to match actual backend schema using createDigest mutation
 */
const CREATE_DIGEST_MUTATION = `
  mutation CreateDigest($input: CreateDigestInput!) {
    createDigest(input: $input) {
      id
      userId
      status
      layoutTemplateId
      content
      createdAt
      modulePlacements {
        id
        moduleId
        gridX
        gridY
        gridWidth
        gridHeight
        orderInGrid
      }
    }
  }
`;

/**
 * GraphQL mutation to add a module placement to a digest
 * Uses wp_asap_digest_module_placements table
 */
const ADD_MODULE_PLACEMENT_MUTATION = `
  mutation AddModulePlacement($input: AddModulePlacementInput!) {
    addModulePlacement(input: $input) {
      placement {
        id
        digestId
        moduleId
        gridX
        gridY
        gridWidth
        gridHeight
        orderInGrid
        module {
          id
          title
          type
          content
          aiProcessedContent {
            summary
            keywords
            entities
          }
        }
      }
    }
  }
`;

/**
 * GraphQL mutation to update digest status
 */
const UPDATE_DIGEST_STATUS_MUTATION = `
  mutation UpdateDigestStatus($input: UpdateDigestStatusInput!) {
    updateDigestStatus(input: $input) {
      digest {
        id
        status
        createdAt
      }
    }
  }
`;

/**
 * GraphQL mutation to remove a module placement
 */
const REMOVE_MODULE_PLACEMENT_MUTATION = `
  mutation RemoveModulePlacement($placementId: ID!) {
    removeModulePlacement(placementId: $placementId) {
      success
      message
    }
  }
`;

/**
 * GraphQL mutation to update module placements (for Gridstack repositioning)
 */
const UPDATE_MODULE_PLACEMENTS_MUTATION = `
  mutation UpdateModulePlacements($digestId: ID!, $placements: [ModulePlacementInput!]!) {
    updateModulePlacements(digestId: $digestId, placements: $placements) {
      digest {
        id
        modulePlacements {
          id
          moduleId
          gridX
          gridY
          gridWidth
          gridHeight
          orderInGrid
        }
      }
    }
  }
`;

/**
 * Fallback layout templates when GraphQL is not available
 * @returns {Array} Array of fallback template objects
 */
function getFallbackLayoutTemplates() {
  return [
    {
      id: 'fallback-simple',
      name: 'Simple Grid',
      description: 'A simple 2x2 grid layout for basic digest creation',
      preview_image_url: '',
      gridstack_config: {
        cellHeight: 100,
        verticalMargin: 10,
        horizontalMargin: 10,
        minRows: 2,
        maxRows: 4,
        column: 2,
        animate: true,
        float: false
      },
      associated_plan_levels: [],
      sort_order: 0,
      minimum_module_items: 1,
      maximum_module_items: 4,
      max_modules: 4,
      capacity: '4 modules'
    },
    {
      id: 'fallback-featured',
      name: 'Featured Layout',
      description: 'Layout with one large featured area and smaller content blocks',
      preview_image_url: '',
      gridstack_config: {
        cellHeight: 100,
        verticalMargin: 10,
        horizontalMargin: 10,
        minRows: 3,
        maxRows: 6,
        column: 3,
        animate: true,
        float: false
      },
      associated_plan_levels: [],
      sort_order: 1,
      minimum_module_items: 1,
      maximum_module_items: 6,
      max_modules: 6,
      capacity: '6 modules'
    }
  ];
}

/**
 * Fetch available layout templates using GraphQL
 * @returns {Promise<ApiResponse>} Response with layout templates data
 */
export async function fetchLayoutTemplates() {
  try {
    console.log('[Digest Builder GraphQL] Fetching layout templates...');
    
    const response = await fetchGraphQL(GET_LAYOUT_TEMPLATES_QUERY);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const templates = response.data?.layoutTemplates || [];
    
    // Transform GraphQL response to match schema specifications
    // Following ASAP_DIGEST_TEMPLATE_ENTITY_SCHEMA.md field definitions
    const transformedTemplates = templates.map(template => ({
      id: template.slug || template.id,
      name: template.title || '',
      description: template.description || '',
      preview_image_url: '',
      gridstack_config: template.gridstackConfig ? 
        JSON.parse(template.gridstackConfig) : 
        {
          cellHeight: 80,
          verticalMargin: 10,
          horizontalMargin: 10,
          column: 12,
          animate: true,
          float: false
        },
      associated_plan_levels: [],
      sort_order: 0,
      minimum_module_items: template.minModules || 1,
      maximum_module_items: template.maxModules || 10,
      // Legacy compatibility fields
      max_modules: template.maxModules || 10,
      capacity: `${template.maxModules || 10} modules`
    }));
    
    console.log('[Digest Builder GraphQL] Successfully fetched templates:', transformedTemplates.length);
    
    return {
      success: true,
      data: transformedTemplates
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error fetching layout templates:', error);
    
    // Return fallback templates if GraphQL fails
    console.log('[Digest Builder GraphQL] Returning fallback templates due to GraphQL error');
    return {
      success: true,
      data: getFallbackLayoutTemplates(),
      error: 'Using fallback templates due to connection issue'
    };
  }
}

/**
 * Create a new draft digest using GraphQL
 * @param {number} userId - WordPress user ID
 * @param {string} layoutTemplateId - Layout template ID
 * @returns {Promise<ApiResponse>} Response object
 */
export async function createDraftDigest(userId, layoutTemplateId) {
  try {
    console.log('[Digest Builder GraphQL] Creating draft digest...');
    console.log('[Digest Builder GraphQL] userId:', userId);
    console.log('[Digest Builder GraphQL] layoutTemplateId:', layoutTemplateId);
    
    const mutation = CREATE_DIGEST_MUTATION;
    // Following wp_asap_digests table schema (custom table, not CPT)
    const variables = {
      input: {
        userId: userId,
        status: 'draft',
        layoutTemplateId: layoutTemplateId,
        content: '{}', // Empty JSON content
        sentimentScore: null,
        lifeMoment: null,
        isSaved: false,
        reminders: null
      }
    };
    
    const response = await fetchGraphQL(mutation, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const digest = response.data?.createDigest;
    
    if (!digest) {
      throw new Error('No digest data returned from mutation');
    }
    
    console.log('[Digest Builder GraphQL] Successfully created digest:', digest);
    
    return {
      success: true,
      data: {
        digest_id: digest.id,
        id: digest.id,
        user_id: digest.userId,
        status: digest.status,
        layout_template_id: digest.layoutTemplateId,
        content: digest.content ? JSON.parse(digest.content) : {},
        created_at: digest.createdAt,
        module_placements: digest.modulePlacements || []
      }
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error creating draft digest:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Fetch user's digests using GraphQL
 * @param {number} userId - WordPress user ID
 * @param {string} [status='DRAFT'] - Digest status filter
 * @returns {Promise<ApiResponse>} Response object
 */
export async function fetchUserDigests(userId, status = 'DRAFT') {
  try {
    console.log('[Digest Builder GraphQL] Fetching user digests...');
    console.log('[Digest Builder GraphQL] userId:', userId, 'status:', status);
    
    const variables = {
      userId: userId,
      status: status.toUpperCase()
    };
    
    const response = await fetchGraphQL(GET_USER_DIGESTS_QUERY, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const digests = response.data?.userDigests || [];
    
    // Transform GraphQL response to match custom wp_asap_digests table schema
    // Following DATABASE_SCHEMA.md wp_asap_digests table structure
    const transformedDigests = digests.map(digest => ({
      id: digest.id,
      user_id: digest.userId,
      status: digest.status,
      layout_template_id: digest.layoutTemplateId,
      content: digest.content ? JSON.parse(digest.content) : {},
      sentiment_score: digest.sentimentScore,
      life_moment: digest.lifeMoment,
      is_saved: digest.isSaved,
      reminders: digest.reminders,
      created_at: digest.createdAt,
      share_link: digest.shareLink,
      podcast_url: digest.podcastUrl,
      module_placements: digest.modulePlacements || [],
      module_count: digest.modulePlacements ? digest.modulePlacements.length : 0
    }));
    
    console.log('[Digest Builder GraphQL] Successfully fetched user digests:', transformedDigests.length);
    
    return {
      success: true,
      data: transformedDigests
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error fetching user digests:', error);
    
    // Return empty array if GraphQL fails - this allows the UI to continue working
    console.log('[Digest Builder GraphQL] Returning empty digests array due to GraphQL error');
    return {
      success: true,
      data: [],
      error: 'Could not load user digests due to connection issue'
    };
  }
}

/**
 * Fetch a specific digest with its modules using GraphQL
 * @param {string|number} digestId - Digest ID
 * @returns {Promise<ApiResponse>} Response object
 */
export async function fetchDigest(digestId) {
  try {
    console.log('[Digest Builder GraphQL] Fetching digest:', digestId);
    
    const variables = {
      id: digestId
    };
    
    const response = await fetchGraphQL(GET_DIGEST_QUERY, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const digest = response.data?.digest;
    
    if (!digest) {
      throw new Error('Digest not found');
    }
    
    // Transform GraphQL response to match custom wp_asap_digests table schema
    // Following DATABASE_SCHEMA.md wp_asap_digests + wp_asap_digest_module_placements structure
    const transformedDigest = {
      digest_id: digest.id,
      id: digest.id,
      user_id: digest.userId,
      status: digest.status,
      layout_template_id: digest.layoutTemplateId,
      content: digest.content ? JSON.parse(digest.content) : {},
      sentiment_score: digest.sentimentScore,
      life_moment: digest.lifeMoment,
      is_saved: digest.isSaved,
      reminders: digest.reminders,
      created_at: digest.createdAt,
      share_link: digest.shareLink,
      podcast_url: digest.podcastUrl,
      module_placements: digest.modulePlacements || [],
      // Legacy compatibility for existing code
      modules: digest.modulePlacements || []
    };
    
    console.log('[Digest Builder GraphQL] Successfully fetched digest');
    
    return {
      success: true,
      data: transformedDigest
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error fetching digest:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Update digest status using GraphQL
 * @param {string|number} digestId - Digest ID
 * @param {string} status - New status
 * @returns {Promise<ApiResponse>} Response object
 */
export async function updateDigestStatus(digestId, status) {
  try {
    console.log('[Digest Builder GraphQL] Updating digest status:', digestId, status);
    
    const variables = {
      input: {
        id: digestId,
        status: status.toUpperCase()
      }
    };
    
    const response = await fetchGraphQL(UPDATE_DIGEST_STATUS_MUTATION, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const digest = response.data?.updateDigest?.digest;
    
    if (!digest) {
      throw new Error('No digest data returned from mutation');
    }
    
    console.log('[Digest Builder GraphQL] Successfully updated digest status');
    
    return {
      success: true,
      data: {
        id: digest.databaseId,
        status: digest.status.toLowerCase()
      }
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error updating digest status:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

// Note: Additional operations like addModuleToDigest, removeModuleFromDigest, 
// and saveDigestLayout will need corresponding GraphQL mutations to be 
// implemented on the WordPress side. For now, these can fall back to REST API
// or be implemented as the GraphQL schema is extended.

/**
 * Add a module placement to a digest using custom wp_asap_digest_module_placements table
 * @param {string|number} digestId - Digest ID
 * @param {Object} moduleData - Module data with content ID
 * @param {Object} gridPosition - Gridstack position data
 * @returns {Promise<ApiResponse>} Response object
 */
export async function addModuleToDigest(digestId, moduleData, gridPosition) {
  try {
    console.log('[Digest Builder GraphQL] Adding module to digest:', { digestId, moduleData, gridPosition });
    
    const variables = {
      input: {
        digestId: digestId,
        moduleId: moduleData.id || moduleData.content_id,
        gridX: gridPosition.x || 0,
        gridY: gridPosition.y || 0,
        gridWidth: gridPosition.w || 1,
        gridHeight: gridPosition.h || 1,
        orderInGrid: gridPosition.order || 0
      }
    };
    
    const response = await fetchGraphQL(ADD_MODULE_PLACEMENT_MUTATION, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const placement = response.data?.addModulePlacement?.placement;
    
    if (!placement) {
      throw new Error('No placement data returned from mutation');
    }
    
    console.log('[Digest Builder GraphQL] Successfully added module placement:', placement);
    
    return {
      success: true,
      data: {
        placement_id: placement.id,
        digest_id: placement.digestId,
        module_id: placement.moduleId,
        grid_position: {
          x: placement.gridX,
          y: placement.gridY,
          w: placement.gridWidth,
          h: placement.gridHeight
        },
        order: placement.orderInGrid,
        module: placement.module
      }
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error adding module to digest:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Remove a module placement from a digest using wp_asap_digest_module_placements table
 * @param {string|number} digestId - Digest ID (for logging/validation)
 * @param {string|number} placementId - Module placement ID
 * @returns {Promise<ApiResponse>} Response object
 */
export async function removeModuleFromDigest(digestId, placementId) {
  try {
    console.log('[Digest Builder GraphQL] Removing module placement:', { digestId, placementId });
    
    const variables = {
      placementId: placementId
    };
    
    const response = await fetchGraphQL(REMOVE_MODULE_PLACEMENT_MUTATION, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const result = response.data?.removeModulePlacement;
    
    if (!result?.success) {
      throw new Error(result?.message || 'Failed to remove module placement');
    }
    
    console.log('[Digest Builder GraphQL] Successfully removed module placement');
    
    return {
      success: true,
      data: { message: result.message }
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error removing module placement:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Update module placements for Gridstack repositioning
 * @param {string|number} digestId - Digest ID
 * @param {Array} layoutData - Array of module placement updates
 * @returns {Promise<ApiResponse>} Response object
 */
export async function saveDigestLayout(digestId, layoutData) {
  try {
    console.log('[Digest Builder GraphQL] Saving digest layout:', { digestId, layoutData });
    
    // Transform layout data to match GraphQL input format
    const placements = layoutData.map(item => ({
      id: item.placement_id || item.id,
      moduleId: item.module_id || item.moduleId,
      gridX: item.x || item.gridX,
      gridY: item.y || item.gridY,
      gridWidth: item.w || item.gridWidth,
      gridHeight: item.h || item.gridHeight,
      orderInGrid: item.order || item.orderInGrid || 0
    }));
    
    const variables = {
      digestId: digestId,
      placements: placements
    };
    
    const response = await fetchGraphQL(UPDATE_MODULE_PLACEMENTS_MUTATION, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const digest = response.data?.updateModulePlacements?.digest;
    
    if (!digest) {
      throw new Error('No digest data returned from layout update');
    }
    
    console.log('[Digest Builder GraphQL] Successfully updated digest layout');
    
    return {
      success: true,
      data: {
        digest_id: digest.id,
        module_placements: digest.modulePlacements
      }
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error saving digest layout:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
}

/**
 * Fetch AI-enhanced content for module selection
 * Integrates wp_asap_ingested_content + wp_asap_ai_processed_content
 * @param {Object} filters - Content filters (type, source, quality, etc.)
 * @param {Object} pagination - Pagination options
 * @returns {Promise<ApiResponse>} Response object with enhanced content
 */
export async function fetchEnhancedContent(filters = {}, pagination = {}) {
  try {
    console.log('[Digest Builder GraphQL] Fetching AI-enhanced content:', { filters, pagination });
    
    const variables = {
      filters: {
        type: filters.type,
        sourceId: filters.source_id,
        status: filters.status || 'published',
        qualityScoreMin: filters.min_quality_score,
        publishDateAfter: filters.publish_date_after,
        publishDateBefore: filters.publish_date_before
      },
      pagination: {
        page: pagination.page || 1,
        perPage: pagination.per_page || 20,
        orderBy: pagination.order_by || 'publishDate',
        order: pagination.order || 'DESC'
      }
    };
    
    const response = await fetchGraphQL(GET_ENHANCED_CONTENT_QUERY, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const contentData = response.data?.ingestedContent;
    
    if (!contentData) {
      throw new Error('No content data returned');
    }
    
    const transformedContent = contentData.nodes.map(item => ({
      id: item.id,
      type: item.type,
      title: item.title,
      content: item.content,
      source_url: item.sourceUrl,
      source_id: item.sourceId,
      publish_date: item.publishDate,
      ingestion_date: item.ingestionDate,
      quality_score: item.qualityScore,
      status: item.status,
      ai_enhanced: !!item.aiProcessedContent,
      ai_summary: item.aiProcessedContent?.summary,
      ai_keywords: item.aiProcessedContent?.keywords,
      ai_entities: item.aiProcessedContent?.entities,
      ai_classification: item.aiProcessedContent?.classification,
      ai_quality_score: item.aiProcessedContent?.qualityScore,
      ai_sentiment: item.aiProcessedContent?.sentiment
    }));
    
    console.log('[Digest Builder GraphQL] Successfully fetched enhanced content:', transformedContent.length);
    
    return {
      success: true,
      data: {
        items: transformedContent,
        pagination: {
          total: contentData.pageInfo.total,
          total_pages: contentData.pageInfo.totalPages,
          current_page: contentData.pageInfo.currentPage,
          has_next_page: contentData.pageInfo.hasNextPage,
          has_previous_page: contentData.pageInfo.hasPreviousPage
        }
      }
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error fetching enhanced content:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
    };
  }
} 