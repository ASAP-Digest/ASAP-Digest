/**
 * Digest Builder GraphQL API
 * Handles all GraphQL operations for the digest creation and management flow
 * Replaces REST API proxy approach to align with original architecture vision
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
 * @property {string} id - Template ID
 * @property {string} name - Template name
 * @property {string} description - Template description
 * @property {Object} config - Template configuration
 */

/**
 * @typedef {Object} DigestData
 * @property {string|number} id - Digest ID
 * @property {string} title - Digest title
 * @property {string} status - Digest status
 * @property {string} layoutTemplateId - Layout template ID
 * @property {Array} modules - Associated modules
 */

/**
 * GraphQL query to fetch layout templates
 */
const GET_LAYOUT_TEMPLATES_QUERY = `
  query GetLayoutTemplates {
    digestTemplates(where: {status: PUBLISH}) {
      nodes {
        id
        databaseId
        title
        asapDigestTemplateName
        asapDigestTemplateSlug
        asapDigestTemplateDescription
        asapDigestTemplateMaxModules
        asapDigestTemplateGridstackConfigJson
        asapDigestTemplateDefaultPlacementsJson
        asapDigestTemplateSortOrder
      }
    }
  }
`;

/**
 * GraphQL query to fetch user's digests
 */
const GET_USER_DIGESTS_QUERY = `
  query GetUserDigests($userId: ID!, $status: PostStatusEnum) {
    digests(where: {author: $userId, status: $status}) {
      nodes {
        id
        databaseId
        title
        status
        date
        modified
        asapDigestLayoutTemplateSlug
        asapDigestModuleIds {
          asapDigestModuleId
          asapDigestModuleType
          asapDigestModuleGridX
          asapDigestModuleGridY
          asapDigestModuleGridWidth
          asapDigestModuleGridHeight
        }
      }
    }
  }
`;

/**
 * GraphQL query to fetch a specific digest with modules
 */
const GET_DIGEST_QUERY = `
  query GetDigest($id: ID!) {
    digest(id: $id, idType: DATABASE_ID) {
      id
      databaseId
      title
      status
      date
      modified
      asapDigestLayoutTemplateSlug
      asapDigestGridstackConfigJson
      asapDigestModuleIds {
        asapDigestModuleId
        asapDigestModuleType
        asapDigestModuleGridX
        asapDigestModuleGridY
        asapDigestModuleGridWidth
        asapDigestModuleGridHeight
      }
    }
  }
`;

/**
 * GraphQL mutation to create a new digest
 */
const CREATE_DIGEST_MUTATION = `
  mutation CreateDigest($input: CreateDigestInput!) {
    createDigest(input: $input) {
      digest {
        id
        databaseId
        title
        status
        asapDigestLayoutTemplateSlug
      }
    }
  }
`;

/**
 * GraphQL mutation to update digest status
 */
const UPDATE_DIGEST_STATUS_MUTATION = `
  mutation UpdateDigestStatus($input: UpdateDigestInput!) {
    updateDigest(input: $input) {
      digest {
        id
        databaseId
        status
      }
    }
  }
`;

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
    
    const templates = response.data?.digestTemplates?.nodes || [];
    
    // Transform GraphQL response to match expected format
    const transformedTemplates = templates.map(template => ({
      id: template.asapDigestTemplateSlug || template.databaseId,
      name: template.asapDigestTemplateName || template.title,
      description: template.asapDigestTemplateDescription || '',
      max_modules: template.asapDigestTemplateMaxModules || 1,
      capacity: `${template.asapDigestTemplateMaxModules || 1} modules`,
      gridstack_config: template.asapDigestTemplateGridstackConfigJson ? 
        JSON.parse(template.asapDigestTemplateGridstackConfigJson) : 
        {
          cellHeight: 80,
          verticalMargin: 10,
          horizontalMargin: 10,
          column: 12,
          animate: true,
          float: false
        },
      default_placements: template.asapDigestTemplateDefaultPlacementsJson ?
        JSON.parse(template.asapDigestTemplateDefaultPlacementsJson) : []
    }));
    
    console.log('[Digest Builder GraphQL] Successfully fetched templates:', transformedTemplates.length);
    
    return {
      success: true,
      data: transformedTemplates
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error fetching layout templates:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
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
    const variables = {
      input: {
        title: `Draft Digest - ${new Date().toLocaleDateString()}`,
        status: 'DRAFT',
        authorId: userId,
        asapDigestLayoutTemplateSlug: layoutTemplateId
      }
    };
    
    const response = await fetchGraphQL(mutation, variables);
    
    if (response.errors) {
      throw new Error(`GraphQL errors: ${response.errors.map(e => e.message).join(', ')}`);
    }
    
    const digest = response.data?.createDigest?.digest;
    
    if (!digest) {
      throw new Error('No digest data returned from mutation');
    }
    
    console.log('[Digest Builder GraphQL] Successfully created digest:', digest);
    
    return {
      success: true,
      data: {
        digest_id: digest.databaseId,
        id: digest.databaseId,
        title: digest.title,
        status: digest.status,
        layout_template_id: digest.asapDigestLayoutTemplateSlug
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
    
    const digests = response.data?.digests?.nodes || [];
    
    // Transform GraphQL response to match expected format
    const transformedDigests = digests.map(digest => ({
      id: digest.databaseId,
      title: digest.title,
      status: digest.status.toLowerCase(),
      created_at: digest.date,
      updated_at: digest.modified,
      layout_template_id: digest.asapDigestLayoutTemplateSlug,
      module_count: digest.asapDigestModuleIds?.length || 0
    }));
    
    console.log('[Digest Builder GraphQL] Successfully fetched user digests:', transformedDigests.length);
    
    return {
      success: true,
      data: transformedDigests
    };
  } catch (error) {
    console.error('[Digest Builder GraphQL] Error fetching user digests:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred'
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
    
    // Transform GraphQL response to match expected format
    const transformedDigest = {
      digest_id: digest.databaseId,
      id: digest.databaseId,
      title: digest.title,
      status: digest.status.toLowerCase(),
      created_at: digest.date,
      updated_at: digest.modified,
      layout_template_id: digest.asapDigestLayoutTemplateSlug,
      gridstack_config: digest.asapDigestGridstackConfigJson ? 
        JSON.parse(digest.asapDigestGridstackConfigJson) : null,
      modules: digest.asapDigestModuleIds || []
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
 * Placeholder for module operations - these will need GraphQL mutations
 * implemented on the WordPress side
 */
export async function addModuleToDigest(digestId, moduleData, gridPosition) {
  console.warn('[Digest Builder GraphQL] addModuleToDigest not yet implemented in GraphQL - falling back to REST');
  // TODO: Implement GraphQL mutation for adding modules
  return {
    success: false,
    error: 'GraphQL mutation for adding modules not yet implemented'
  };
}

export async function removeModuleFromDigest(digestId, placementId) {
  console.warn('[Digest Builder GraphQL] removeModuleFromDigest not yet implemented in GraphQL - falling back to REST');
  // TODO: Implement GraphQL mutation for removing modules
  return {
    success: false,
    error: 'GraphQL mutation for removing modules not yet implemented'
  };
}

export async function saveDigestLayout(digestId, layoutData) {
  console.warn('[Digest Builder GraphQL] saveDigestLayout not yet implemented in GraphQL - falling back to REST');
  // TODO: Implement GraphQL mutation for saving layout
  return {
    success: false,
    error: 'GraphQL mutation for saving layout not yet implemented'
  };
} 