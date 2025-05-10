# ASAP Digest GraphQL Schema Documentation

---
**Protocol Compliance:**
- roadmap-syntax-validation-protocol v1.2
- update-memory
- testing-verification-protocol
- server-memory-rules
- work-session-management-protocol

---

## GraphQL Endpoint

- **URL (Local):** `https://asapdigest.local/graphql`
- **URL (Production):** `https://asapdigest.com/graphql`
- **Authentication:** Required for most queries (Better Auth session or token)

---

## Registered Types (Custom Post Types)

All types support the following standard fields (unless otherwise noted):
- `id`
- `title`
- `content`
- `excerpt`
- `featuredImage` (thumbnail)
- `date`
- `slug`

### Article
- **Type:** `Article`
- **Plural:** `Articles`

### Podcast
- **Type:** `Podcast`
- **Plural:** `Podcasts`

### KeyTerm
- **Type:** `KeyTerm`
- **Plural:** `KeyTerms`

### Financial
- **Type:** `Financial`
- **Plural:** `Financials`

### XPost
- **Type:** `XPost`
- **Plural:** `XPosts`

### Reddit
- **Type:** `Reddit`
- **Plural:** `Reddits`

### Event
- **Type:** `Event`
- **Plural:** `Events`

### Polymarket
- **Type:** `Polymarket`
- **Plural:** `Polymarkets`

---

## Example Queries

### Fetch All Articles
```graphql
query {
  articles(first: 10) {
    nodes {
      id
      title
      content
      date
      featuredImage {
        sourceUrl
      }
    }
  }
}
```

### Fetch a Single Podcast by Slug
```graphql
query {
  podcastBy(slug: "my-podcast-slug") {
    id
    title
    content
    date
    featuredImage {
      sourceUrl
    }
  }
}
```

---

## Custom Fields & Mutations

*To be completed as discovered. If you add custom fields or mutations, document them here with field names, types, and example queries/mutations.*

---

## Advanced Custom Fields (ACF) Integration

> **Note:** The following fields are available only if WPGraphQL for ACF is installed and configured in WordPress.

### Article (`acfArticle`)
- `summary`: Article summary
- `source`: Source of the article
- `timestamp`: Publication timestamp
- `image`: Featured image
  - `sourceUrl`: URL of the image
  - `mediaDetails`: Image details
    - `width`: Image width
    - `height`: Image height

**Example Query:**
```graphql
query {
  articles(first: 10) {
    nodes {
      id
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
```

### Podcast (`acfPodcast`)
- `summary`: Podcast summary
- `audioUrl`: URL to the audio file
- `duration`: Duration of the podcast
- `host`: Host of the podcast
- `coverImage`: Podcast image
  - `sourceUrl`: URL of the image
  - `mediaDetails`: Image details
    - `width`: Image width
    - `height`: Image height

**Example Query:**
```graphql
query {
  podcasts(first: 10) {
    nodes {
      id
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
```

### Financial (`acfFinancial`)
- `summary`: Financial data summary
- `source`: Source of the financial data
- `dataPoints`: Array of data points
- `chartImage`: Chart image
  - `sourceUrl`: URL of the chart image

**Example Query:**
```graphql
query {
  financials(first: 10) {
    nodes {
      id
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
```

### KeyTerm (`acfKeyTerm`)
- `definition`: Key term definition
- `source`: Source of the key term
- `relatedTerms`: Related key terms (array)

**Example Query:**
```graphql
query {
  keyTerms(first: 10) {
    nodes {
      id
      title
      date
      acfKeyTerm {
        definition
        source
        relatedTerms
      }
    }
  }
}
```

### XPost (`acfXPost`)
- `content`: Post content
- `author`: Post author
- `username`: Author username
- `postUrl`: URL to the original post
- `timestamp`: Post timestamp
- `likes`: Number of likes
- `reposts`: Number of reposts

**Example Query:**
```graphql
query {
  xPosts(first: 10) {
    nodes {
      id
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
```

### Reddit (`acfReddit`)
- `content`: Post content
- `subreddit`: Subreddit name
- `author`: Post author
- `postUrl`: URL to the original post
- `timestamp`: Post timestamp
- `upvotes`: Number of upvotes
- `downvotes`: Number of downvotes
- `comments`: Number of comments

**Example Query:**
```graphql
query {
  reddits(first: 10) {
    nodes {
      id
      title
      date
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
```

### Event (`acfEvent`)
- `description`: Event description
- `location`: Event location
- `startTime`: Event start time
- `endTime`: Event end time
- `organizer`: Event organizer
- `eventUrl`: URL to the event

**Example Query:**
```graphql
query {
  events(first: 10) {
    nodes {
      id
      title
      date
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
```

### Polymarket (`acfPolymarket`)
- `question`: Market question
- `description`: Market description
- `closeTime`: Market close time
- `totalVolume`: Total trading volume
- `outcomes`: Market outcomes (array)
  - `name`: Outcome name
  - `probability`: Outcome probability
  - `price`: Outcome price

**Example Query:**
```graphql
query {
  polymarkets(first: 10) {
    nodes {
      id
      title
      date
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
```

---

## Notes
- All types are public and support archive queries.
- All types are exposed in GraphQL with the names above.
- For advanced filtering, sorting, and pagination, see WPGraphQL documentation.
- Authentication is required for most mutations and some queries.

---

*This file is protocol-compliant and should be updated as the schema evolves.* 