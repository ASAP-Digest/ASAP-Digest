/**
 * GraphQL queries for content types
 * @module api/queries/content-queries
 */

/**
 * @typedef {Object} ArticleFields
 * @property {string} summary - Article summary
 * @property {string} source - Source of the article
 * @property {string} timestamp - Publication timestamp
 * @property {Object} [image] - Featured image
 * @property {string} [image.sourceUrl] - URL of the image
 * @property {Object} [image.mediaDetails] - Image details
 * @property {number} [image.mediaDetails.width] - Image width
 * @property {number} [image.mediaDetails.height] - Image height
 */

/**
 * @typedef {Object} PodcastFields
 * @property {string} summary - Podcast summary
 * @property {string} source - Source of the podcast
 * @property {string} duration - Duration of the podcast
 * @property {string} audioUrl - URL to the audio file
 * @property {Object} [image] - Podcast image
 * @property {string} [image.sourceUrl] - URL of the image
 */

/**
 * @typedef {Object} KeyTermFields
 * @property {string} definition - Key term definition
 * @property {string} source - Source of the key term
 * @property {string[]} relatedTerms - Related key terms
 */

/**
 * @typedef {Object} FinancialFields
 * @property {string} summary - Financial data summary
 * @property {string} source - Source of the financial data
 * @property {string} type - Type of financial data
 * @property {string} value - Financial value
 * @property {string} change - Change in value
 * @property {string} period - Time period
 */

/**
 * @typedef {Object} XPostFields
 * @property {string} content - Post content
 * @property {string} author - Post author
 * @property {string} username - Author username
 * @property {string} postUrl - URL to the original post
 * @property {string} timestamp - Post timestamp
 * @property {number} likes - Number of likes
 * @property {number} reposts - Number of reposts
 */

/**
 * @typedef {Object} RedditFields
 * @property {string} content - Post content
 * @property {string} subreddit - Subreddit name
 * @property {string} author - Post author
 * @property {string} postUrl - URL to the original post
 * @property {string} timestamp - Post timestamp
 * @property {number} upvotes - Number of upvotes
 * @property {number} downvotes - Number of downvotes
 * @property {number} comments - Number of comments
 */

/**
 * @typedef {Object} EventFields
 * @property {string} description - Event description
 * @property {string} location - Event location
 * @property {string} startTime - Event start time
 * @property {string} endTime - Event end time
 * @property {string} organizer - Event organizer
 * @property {string} eventUrl - URL to the event
 */

/**
 * @typedef {Object} PolymarketFields
 * @property {string} question - Market question
 * @property {string} description - Market description
 * @property {string} closeTime - Market close time
 * @property {string} totalVolume - Total trading volume
 * @property {Object[]} outcomes - Market outcomes
 * @property {string} outcomes.name - Outcome name
 * @property {string} outcomes.probability - Outcome probability
 * @property {string} outcomes.price - Outcome price
 */

/**
 * Query for fetching article content
 */
export const ARTICLE_QUERY = `
  query GetArticles($limit: Int, $cursor: String, $search: String, $dateFrom: String, $dateTo: String, $categories: [ID]) {
    posts(first: $limit, after: $cursor, where: {
      search: $search, 
      dateQuery: { after: $dateFrom, before: $dateTo },
      categoryIn: $categories
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        date
        acfArticle {
          summary
          source
          timestamp
          image {
            sourceUrl
            mediaDetails {
              width
              height
            }
          }
        }
      }
    }
  }
`;

/**
 * Query for fetching podcast content
 */
export const PODCAST_QUERY = `
  query GetPodcasts($limit: Int, $cursor: String, $search: String, $dateFrom: String, $dateTo: String) {
    podcasts(first: $limit, after: $cursor, where: {
      search: $search, 
      dateQuery: { after: $dateFrom, before: $dateTo }
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        date
        acfPodcast {
          summary
          audioUrl
          duration
          host
          coverImage {
            sourceUrl
            mediaDetails {
              width
              height
            }
          }
        }
      }
    }
  }
`;

/**
 * Query for fetching financial data content
 */
export const FINANCIAL_QUERY = `
  query GetFinancialData($limit: Int, $cursor: String, $search: String, $dateFrom: String, $dateTo: String) {
    financialData(first: $limit, after: $cursor, where: {
      search: $search, 
      dateQuery: { after: $dateFrom, before: $dateTo }
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        date
        acfFinancial {
          summary
          source
          dataPoints
          chartImage {
            sourceUrl
          }
        }
      }
    }
  }
`;

/**
 * Query for fetching social media posts
 */
export const SOCIAL_QUERY = `
  query GetSocialPosts($limit: Int, $cursor: String, $search: String, $dateFrom: String, $dateTo: String, $platforms: [String]) {
    socialPosts(first: $limit, after: $cursor, where: {
      search: $search, 
      dateQuery: { after: $dateFrom, before: $dateTo },
      metaQuery: { 
        relation: "AND",
        metaArray: [
          { key: "platform", compare: "IN", value: $platforms }
        ]
      }
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        date
        acfSocial {
          platform
          author
          content
          mediaUrl
          engagementStats
          link
        }
      }
    }
  }
`;

/**
 * Query for fetching multiple content types with a unified structure
 */
export const UNIFIED_CONTENT_QUERY = `
  query GetContentItems($types: [String], $limit: Int, $cursor: String, $search: String, $dateFrom: String, $dateTo: String) {
    contentItems(first: $limit, after: $cursor, where: {
      types: $types,
      search: $search,
      dateQuery: { after: $dateFrom, before: $dateTo }
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        type
        title
        date
        summary
        source
        imageUrl
        metadata {
          key
          value
        }
      }
    }
  }
`;

/**
 * GraphQL query for key terms
 */
export const KEYTERM_QUERY = `
  query GetKeyTerms($limit: Int, $cursor: String, $search: String) {
    keyTerms(first: $limit, after: $cursor, where: {
      search: $search
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        acfKeyTerm {
          definition
          source
          relatedTerms
        }
      }
    }
  }
`;

/**
 * GraphQL query for X posts
 */
export const XPOST_QUERY = `
  query GetXPosts($limit: Int, $cursor: String, $search: String) {
    xPosts(first: $limit, after: $cursor, where: {
      search: $search
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        date
        acfXPost {
          content
          author
          username
          postUrl
          timestamp
          likes
          reposts
        }
      }
    }
  }
`;

/**
 * GraphQL query for Reddit posts
 */
export const REDDIT_QUERY = `
  query GetRedditPosts($limit: Int, $cursor: String, $search: String, $subreddit: String) {
    redditPosts(first: $limit, after: $cursor, where: {
      search: $search,
      taxQuery: { subreddit: { terms: [$subreddit], field: SLUG, operator: IN } }
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        acfReddit {
          content
          subreddit
          author
          postUrl
          timestamp
          upvotes
          downvotes
          comments
        }
      }
    }
  }
`;

/**
 * GraphQL query for events
 */
export const EVENT_QUERY = `
  query GetEvents($limit: Int, $cursor: String, $search: String, $dateFrom: String, $dateTo: String) {
    events(first: $limit, after: $cursor, where: {
      search: $search,
      dateQuery: { after: $dateFrom, before: $dateTo }
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        acfEvent {
          description
          location
          startTime
          endTime
          organizer
          eventUrl
        }
      }
    }
  }
`;

/**
 * GraphQL query for Polymarket data
 */
export const POLYMARKET_QUERY = `
  query GetPolymarkets($limit: Int, $cursor: String, $search: String) {
    polymarkets(first: $limit, after: $cursor, where: {
      search: $search
    }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        title
        acfPolymarket {
          question
          description
          closeTime
          totalVolume
          outcomes {
            name
            probability
            price
          }
        }
      }
    }
  }
`; 