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
    const graphqlEndpoint = import.meta.env.VITE_PUBLIC_WP_GRAPHQL_URL || 'https://asapdigest.local/graphql';

    if (!graphqlEndpoint) {
        throw new Error('GraphQL endpoint URL is not configured (VITE_PUBLIC_WP_GRAPHQL_URL).');
    }

    try {
        const response = await fetch(graphqlEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // Add other headers like Authorization if needed for non-cookie auth in the future
            },
            // IMPORTANT: Include credentials (cookies) for authenticated requests
            credentials: 'include', 
            body: JSON.stringify({
                query,
                variables,
            }),
        });

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
        // Re-throw the error to be caught by the calling function
        throw error; 
    }
}
