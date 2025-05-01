/**
 * @file WordPress GraphQL schema type definitions
 * @description Type definitions for WordPress GraphQL schema used in the application
 * @since 1.0.0
 */

/**
 * WordPress GraphQL viewer query response
 */
declare namespace WordPress {
  /**
   * WordPress user information from GraphQL viewer query
   */
  interface Viewer {
    /** WordPress user ID (databaseId) */
    databaseId: number;
    /** User email address */
    email: string;
    /** WordPress username */
    username: string;
    /** User display name */
    name: string;
    /** User avatar URL (if requested) */
    avatar?: {
      url: string;
    };
    /** User roles (if requested) */
    roles?: {
      nodes: {
        name: string;
      }[];
    };
  }

  /**
   * GraphQL query response containing viewer data
   */
  interface ViewerQueryResponse {
    data: {
      viewer: Viewer | null;
    };
    errors?: Array<{
      message: string;
      locations?: Array<{
        line: number;
        column: number;
      }>;
      path?: string[];
      extensions?: Record<string, any>;
    }>;
  }
}

export = WordPress;
export as namespace WordPress; 