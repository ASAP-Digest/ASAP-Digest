<script>
  import { onMount } from 'svelte';
  import ContentBrowser from '$lib/components/browsers/ContentBrowser.svelte';
  import { selectedItems } from '$lib/stores/selected-items-store.js';
  import { Button } from '$lib/components/ui/button';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Tabs, TabsContent, TabsList, TabsTrigger } from '$lib/components/ui/tabs';
  
  /** @type {import('$lib/api/content-fetcher').ContentItem[]} */
  // let selectedItems = $state([]);
  
  /** @type {string} */
  let activeTab = $state('browser');
  
  // Optional: Keep subscription if you need to react programmatically, 
  // but you might not need the local assignment if using $selectedItems in template
  // onMount(() => {
  //   const unsubscribe = selectedItems.subscribe(items => {
  //     // console.log('Store updated:', items); // Example logging
  //   });
  //   return () => {
  //     unsubscribe();
  //   };
  // }); 
  
  /**
   * Reset the selection
   */
  function resetSelection() {
    selectedItems.clear();
  }
</script>

<svelte:head>
  <title>New Items Selector Demo | ASAP Digest</title>
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold">New Items Selector</h1>
    <p class="text-lg text-muted-foreground mt-2">
      Demonstration of the Content Ingestion & Selection System
    </p>
  </div>
  
  <Tabs value={activeTab} class="w-full">
    <TabsList class="mb-4">
      <TabsTrigger value="browser" onclick={() => activeTab = 'browser'}>Content Browser</TabsTrigger>
      <TabsTrigger value="selection" onclick={() => activeTab = 'selection'}>Current Selection</TabsTrigger>
      <TabsTrigger value="docs" onclick={() => activeTab = 'docs'}>Documentation</TabsTrigger>
    </TabsList>
    
    <TabsContent value="browser" class="mt-0">
      <ContentBrowser />
    </TabsContent>
    
    <TabsContent value="selection" class="mt-0">
      <Card>
        <CardHeader>
          <CardTitle>Selected Items</CardTitle>
          <CardDescription>
            {$selectedItems.length ? 
              `You have selected ${$selectedItems.length} item${$selectedItems.length !== 1 ? 's' : ''} for your digest.` : 
              'No items selected yet. Go to the Content Browser tab to select items.'}
          </CardDescription>
        </CardHeader>
        <CardContent>
        {#if $selectedItems.length === 0}
            <div class="text-center py-8">
              <p class="text-muted-foreground">No items selected</p>
            </div>
        {:else}
            <div class="space-y-4">
            {#each $selectedItems as item (item.id)}
                <div class="flex gap-4 p-3 border rounded-md">
                  {#if item.image}
                    <div class="h-16 w-24 flex-shrink-0 overflow-hidden rounded-md">
                      <img src={item.image} alt={item.title} class="h-full w-full object-cover" />
                    </div>
                  {:else}
                    <div class="h-16 w-24 flex-shrink-0 bg-muted rounded-md flex items-center justify-center">
                      <span class="text-xs text-muted-foreground">No image</span>
                    </div>
                  {/if}
                  
                  <div>
                    <div class="flex items-center gap-2">
                      <div class="text-xs px-2 py-0.5 rounded-full bg-primary text-primary-foreground">
                        {item.type.charAt(0).toUpperCase() + item.type.slice(1)}
                      </div>
                      <span class="text-xs text-muted-foreground">{item.source || 'Unknown source'}</span>
                    </div>
                    <h3 class="font-semibold mt-1">{item.title}</h3>
                    <p class="text-sm text-muted-foreground line-clamp-1">
                      {item.summary || 'No summary available'}
                    </p>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </CardContent>
        <CardFooter class="flex justify-between">
          <Button variant="outline" onclick={resetSelection}>Clear Selection</Button>
          <Button onclick={() => window.location.href = '/digest/edit'}>
            Create Digest
          </Button>
        </CardFooter>
      </Card>
    </TabsContent>
    
    <TabsContent value="docs" class="mt-0">
      <Card>
        <CardHeader>
          <CardTitle>New Items Selector Documentation</CardTitle>
          <CardDescription>
            Overview of the Content Ingestion & Selection System
          </CardDescription>
        </CardHeader>
        <CardContent class="prose dark:prose-invert max-w-none">
          <h2>System Architecture</h2>
          <p>
            The New Items Selector (NIS) system provides a user-friendly interface for browsing, 
            filtering, and selecting content items to include in digests. The system implements
            the following components:
          </p>
          
          <h3>Core Components</h3>
          <ul>
            <li>
              <strong>Content Fetcher Service</strong> - Unified API for fetching different content types
              (Article, Podcast, Financial, Social) using GraphQL queries
            </li>
            <li>
              <strong>FilterPanel</strong> - User interface for filtering content by type, date, search term,
              categories, sources, etc.
            </li>
            <li>
              <strong>ContentBrowser</strong> - Main component that displays filterable, paginated content items
              in grid or list view
            </li>
            <li>
              <strong>Selected Items Store</strong> - Persistent store for tracking selected items across
              browser sessions using localforage
              </li>
          </ul>
          
          <h3>Data Architecture</h3>
          <p>
            The system implements a normalized content model that unifies different content types:
          </p>
          <pre><code>{`{
  id: string,        // Unique identifier
  type: string,      // Content type (article, podcast, financial, social)
  title: string,     // Content title
  summary: string,   // Brief summary
  source: string,    // Content source
  timestamp: string, // Publication date/time
  image: string,     // Featured image URL
  url: string,       // Content URL
  contentType: {...} // Type-specific metadata
}`}</code></pre>
          
          <h3>Key Files</h3>
          <ul>
            <li><code>app/src/lib/api/queries/content-queries.js</code> - GraphQL queries</li>
            <li><code>app/src/lib/api/content-fetcher.js</code> - Unified content service</li>
            <li><code>app/src/lib/utils/image-utils.js</code> - Image optimization utilities</li>
            <li><code>app/src/lib/stores/selected-items-store.js</code> - Selection persistence</li>
            <li><code>app/src/lib/components/selectors/FilterPanel.svelte</code> - Content filters</li>
            <li><code>app/src/lib/components/browsers/ContentBrowser.svelte</code> - Main browser component</li>
          </ul>
          
          <h2>Integration & Usage</h2>
          <p>
            To use the Content Browser in another page:
          </p>
          
          <pre><code>{`<script>
  import ContentBrowser from '$lib/components/browsers/ContentBrowser.svelte';
  import { selectedItems } from '$lib/stores/selected-items-store.js';
  
  // Subscribe to selected items if needed
  onMount(() => {
    const unsubscribe = selectedItems.subscribe(items => {
      // Do something with items
    });
    
    return () => unsubscribe();
  });
</script>

<ContentBrowser />`}</code></pre>
          
          <h2>Persistence</h2>
          <p>
            The system uses localforage for persistent storage of selected items, enabling:
          </p>
          <ul>
            <li>Selection persistence across page reloads</li>
            <li>Offline capability for selected items</li>
            <li>Browser tab synchronization</li>
          </ul>
          
          <h2>Next Steps</h2>
          <p>
            Future enhancements planned for the system include:
          </p>
          <ul>
            <li>Advanced caching strategies for faster content loading</li>
            <li>Real-time content updates via WebSocket</li>
            <li>AI-assisted content recommendations</li>
            <li>Advanced search with natural language processing</li>
          </ul>
        </CardContent>
      </Card>
    </TabsContent>
  </Tabs>
</div> 