/**
 * @description Helper function to send GraphQL queries to the WordPress backend.
 * Handles sending credentials (cookies) for authenticated queries like 'viewer'.
 * @param {string} query The GraphQL query string.
 * @param {object} [variables={}] Optional GraphQL variables.
 * @returns {Promise<object>} The JSON response data from the GraphQL endpoint.
 * @throws {Error} Throws an error if the fetch request fails or the response is not ok or contains GraphQL errors.
 * @example
 * import { fetchGraphQL } from '$lib/utils/fetchGraphQL';
 * 
 * const query = `query GetViewer { viewer { databaseId email } }`;
 * try {
 *   const data = await fetchGraphQL(query);
 *   console.log(data);
 * } catch (error) {
 *   console.error("GraphQL fetch failed:", error);
 * }
 * @created 04.30.25 | 03:38 PM PDT
 */
export async function fetchGraphQL(query, variables = {}) {
    // Get the GraphQL endpoint URL from environment variables
    // Fallback to local URL for safety, but production should have this set
    const graphqlEndpoint = import.meta.env.VITE_WORDPRESS_GRAPHQL_URL || 'https://asapdigest.local/graphql';

    if (!graphqlEndpoint) {
        throw new Error('GraphQL endpoint URL is not configured (VITE_WORDPRESS_GRAPHQL_URL).');
    }

    console.log('[fetchGraphQL] Making request to:', graphqlEndpoint);
    console.log('[fetchGraphQL] Query:', query.substring(0, 100) + '...');
    console.log('[fetchGraphQL] Variables:', variables);

    try {
        // Prepare headers
        const headers = {
            'Content-Type': 'application/json',
        };

        // Add session token if available in localStorage
        if (typeof window !== 'undefined') {
            try {
                const authData = JSON.parse(localStorage.getItem('asap_auth_data') || '{}');
                if (authData.sessionToken) {
                    headers['Authorization'] = `Bearer ${authData.sessionToken}`;
                    console.log('[fetchGraphQL] Added session token to headers');
                }
            } catch (e) {
                console.warn('[fetchGraphQL] Could not parse auth data from localStorage:', e);
            }
        }

        const response = await fetch(graphqlEndpoint, {
            method: 'POST',
            headers,
            // IMPORTANT: Include credentials (cookies) for authenticated requests
            credentials: 'include', 
            body: JSON.stringify({
                query,
                variables,
            }),
        });

        console.log('[fetchGraphQL] Response status:', response.status);
        console.log('[fetchGraphQL] Response headers:', Object.fromEntries(response.headers.entries()));

        if (!response.ok) {
            // Attempt to get more specific error info from the response body if possible
            let errorBody = '';
            try {
                errorBody = await response.text();
            } catch (e) {
                // Ignore if reading body fails
            }
            throw new Error(`HTTP error ${response.status} fetching GraphQL: ${response.statusText}. Body: ${errorBody}`);
        }

        const jsonResponse = await response.json();

        // Check for GraphQL-level errors
        if (jsonResponse.errors) {
            console.error('GraphQL Errors:', JSON.stringify(jsonResponse.errors, null, 2));
            // Throw the first error message, or a generic one
            throw new Error(jsonResponse.errors[0]?.message || 'GraphQL query returned errors.');
        }

        return jsonResponse.data; // Return only the data part of the response

    } catch (error) {
        console.error('fetchGraphQL Error:', error);
        
        // Enhanced error reporting for debugging
        if (error instanceof TypeError && error.message.includes('fetch')) {
            // Network-level error (CORS, DNS, connection refused, etc.)
            const enhancedError = new Error(`Network error connecting to GraphQL endpoint: ${error.message}`);
            enhancedError.name = 'NetworkError';
            // Add custom properties to the error object
            /** @type {any} */ (enhancedError).originalError = error;
            /** @type {any} */ (enhancedError).endpoint = graphqlEndpoint;
            /** @type {any} */ (enhancedError).debugInfo = {
                userAgent: typeof navigator !== 'undefined' ? navigator.userAgent : 'Server-side',
                origin: typeof window !== 'undefined' ? window.location.origin : 'Server-side',
                timestamp: new Date().toISOString()
            };
            throw enhancedError;
        }
        
        // Re-throw the error to be caught by the calling function
        throw error; 
    }
}
