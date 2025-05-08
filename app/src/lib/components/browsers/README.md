# Content Browser and Selection System

This directory contains components for the New Items Selector (NIS) system, which enables browsing, filtering, and selecting content from various sources for digest creation.

## Core Components

- `ContentBrowser.svelte` - Main component that displays and manages content items
- `../selectors/FilterPanel.svelte` - Filter UI for content search and filtering

## Architecture

The browser system follows a multi-layer architecture:

1. **Data Layer**:
   - `$lib/api/queries/content-queries.js` - GraphQL queries for different content types
   - `$lib/api/content-fetcher.js` - Unified service that normalizes content from different sources

2. **State Layer**:
   - `$lib/stores/selected-items-store.js` - Svelte store that manages and persists selected items

3. **UI Layer**:
   - `ContentBrowser.svelte` - Main content display and interaction component
   - `FilterPanel.svelte` - Advanced filtering component

## Usage

To integrate the Content Browser in a page:

```svelte
<script>
  import ContentBrowser from '$lib/components/browsers/ContentBrowser.svelte';
  import { selectedItemsStore } from '$lib/stores/selected-items-store.js';
  
  onMount(() => {
    const unsubscribe = selectedItemsStore.subscribe(items => {
      // Do something with the selected items
      console.log('Selected items:', items);
    });
    
    return () => unsubscribe();
  });
</script>

<ContentBrowser />
```

## Features

- Unified content model for diverse content types (articles, podcasts, financial data, social posts)
- Advanced filtering with search, date ranges, content types, categories, sources
- Grid and list views
- Local caching of selected items using localforage
- Pagination and infinite scrolling support
- Responsive design

## Demo

A complete demo of the ContentBrowser is available at `/demo/new-items-selector`

## Integration with Digest Creation

Selected items in the Content Browser are automatically synchronized with the `selectedItemsStore`, making them available for digest creation. The store persists selections across browser sessions using localforage.

## Next Steps

- Enhanced caching strategy for improved performance
- Real-time content updates via WebSocket
- AI-powered content recommendations
- Advanced natural language search capabilities 