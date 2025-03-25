# Content Selector Components

This directory contains components for content selection in the ASAP Digest application.

## NewItemsSelector

The `NewItemsSelector` component is a versatile and visually appealing UI for selecting and adding content items to the user's digest.

### Features

- **Floating Action Button (FAB)**: Accessible button that can be positioned at the bottom-center or bottom-right of the screen
- **Configurable Position**: Users can toggle the FAB position based on their preference
- **Content Type Flyout Menu**: Visual menu for selecting content types
- **Visual Grid Selection**: Rich card-based UI for browsing and selecting content
- **Multi-Select Capability**: Users can select multiple items before adding them
- **Search Functionality**: Users can filter available content by searching
- **Persistent Settings**: User preferences for FAB position are saved to localStorage

### Usage

```svelte
<script>
  import NewItemsSelector from '$lib/components/selectors/NewItemsSelector.svelte';
  
  // Control visibility
  let showSelector = $state(false);
  
  // Handle selected items
  function handleAdd(event) {
    const { items, type } = event.detail;
    // Do something with the selected items
    console.log('Added items:', items);
  }
</script>

<!-- Toggle button -->
<button onclick={() => showSelector = !showSelector}>
  Add Content
</button>

<!-- Render the selector when needed -->
{#if showSelector}
  <NewItemsSelector 
    on:close={() => showSelector = false} 
    on:add={handleAdd} 
  />
{/if}
```

### Events

- `close`: Dispatched when the selector is closed
- `add`: Dispatched when items are selected and added, with details containing:
  - `items`: Array of selected content items
  - `type`: The content type that was selected

### Implementation Notes

- Uses Svelte 5 runes for reactivity
- Implements ShadCN UI components for consistent styling
- Uses Tailwind HSL variables for theming compatibility
- Compatible with both light and dark modes
- Sample content is currently hardcoded but designed to be replaced with API data

### Future Enhancements

- Integration with backend API for dynamic content loading
- Pagination for large content libraries
- Category filtering
- Recently added section
- Favorites/bookmarks
- Accessibility improvements
- Animation refinements 