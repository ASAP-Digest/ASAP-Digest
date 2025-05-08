# Content Selection Components

This directory contains components related to content selection and filtering within the ASAP Digest system. These components are designed to be reusable across different parts of the application where content needs to be filtered, searched, or selected.

## Core Components

### FilterPanel.svelte

A comprehensive filtering UI component that allows users to filter content by various criteria:

- Full-text search
- Content types (articles, podcasts, financial data, social posts)
- Date ranges
- Categories
- Sources
- "Fresh content" toggle (last 24 hours)
- Items per page selection

The component emits a `filter` event with the complete filter state whenever filters are changed, making it easy to integrate with any content display component.

#### Usage

```svelte
<script>
  import FilterPanel from '$lib/components/selectors/FilterPanel.svelte';
  
  /**
   * Handle filter changes
   * @param {CustomEvent} event
   */
  function handleFilterChange(event) {
    const filters = event.detail;
    console.log('Filters changed:', filters);
    // Use filters to query content
  }
</script>

<FilterPanel on:filter={handleFilterChange} />
```

#### Filter Options Interface

```typescript
interface FilterOptions {
  search: string;           // Search term
  types: string[];          // Content types
  categories: string[];     // Categories
  sources: string[];        // Sources
  dateFrom: Date | null;    // Start date
  dateTo: Date | null;      // End date
  onlyFresh: boolean;       // Only show content from last 24 hours
  limit: number;            // Number of items per page
}
```

## Integration

These selector components are designed to work with:

1. The content fetcher services (`$lib/api/content-fetcher.js`)
2. The selected items store (`$lib/stores/selected-items-store.js`)
3. The content browser components (`$lib/components/browsers/ContentBrowser.svelte`)

## Design Principles

1. **Reusability** - Components are designed to be used in different contexts
2. **Accessibility** - All inputs are properly labeled and keyboard navigable
3. **Responsive** - Components adapt to different screen sizes
4. **Stateful** - Components maintain their own internal state but also emit events for parent integration

## Demo

A complete demo of these selection components is available at `/demo/new-items-selector`

## Future Enhancements

- Saved filter presets
- Advanced query builder
- Natural language filter input ("Show me articles about tech from last week")
- Category hierarchy filtering
- Save and share filter configurations 