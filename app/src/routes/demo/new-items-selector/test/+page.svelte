<!-- Test page for NewItemsSelector2 GraphQL Integration -->
<script>
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';
  
  // Import the component we're testing
  import NewItemsSelector2 from '$lib/components/selectors/NewItemsSelector2.svelte';
  
  // Import our debug utilities
  import { testContentFetcher, runAllTests } from '../debug.js';
  
  // Import the selected items store
  import { selectedItems, getSelectionSummary } from '$lib/stores/selected-items-store.js';
  
  // Import UI components
  import Button from '$lib/components/ui/button/button.svelte';
  import Card from '$lib/components/ui/card/card.svelte';
  import CardHeader from '$lib/components/ui/card/card-header.svelte';
  import CardTitle from '$lib/components/ui/card/card-title.svelte';
  import CardContent from '$lib/components/ui/card/card-content.svelte';
  import CardFooter from '$lib/components/ui/card/card-footer.svelte';
  import { Badge } from '$lib/components/ui/badge';
  
  // State
  let testResults = undefined;
  let isRunningTests = false;
  let showSelector = false;
  
  // Handle selection changes
  function handleSelectionChange(items) {
    console.log('Selection changed:', items);
  }
  
  // Run the GraphQL tests
  async function runTests() {
    if (!browser) return;
    
    isRunningTests = true;
    testResults = undefined;
    
    try {
      testResults = await testContentFetcher({ limit: 3 });
      console.log('Test results:', testResults);
    } catch (error) {
      console.error('Test error:', error);
    } finally {
      isRunningTests = false;
    }
  }
  
  // Run all debug tests
  function runFullTests() {
    if (!browser) return;
    runAllTests();
  }
  
  // Clear selected items
  function clearSelection() {
    selectedItems.clear();
  }
  
  // Toggle selector visibility
  function toggleSelector() {
    showSelector = !showSelector;
  }
  
  // Auto-run tests when requested via URL param
  onMount(() => {
    if (browser && new URL(window.location.href).searchParams.get('autotest') === 'true') {
      setTimeout(runTests, 500);
    }
  });
</script>

<div class="container mx-auto py-8 px-4">
  <div class="mb-8">
    <h1 class="text-3xl font-bold">NewItemsSelector2 GraphQL Integration Test</h1>
    <p class="text-gray-600 mt-2">
      This page tests the integration between the NewItemsSelector2 component and the GraphQL API.
    </p>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Selector Control Card -->
    <Card>
      <CardHeader>
        <CardTitle>Component Testing</CardTitle>
      </CardHeader>
      <CardContent>
        <p class="mb-4">Test the selector component with real GraphQL data.</p>
        <div class="flex flex-col space-y-4">
          <Button onclick={toggleSelector}>
            {showSelector ? 'Hide Selector' : 'Show Selector'}
          </Button>
          
          {#if showSelector}
            <div class="pt-4">
              <h3 class="font-semibold mb-2">Selector Options:</h3>
              <ul class="list-disc list-inside space-y-1 text-sm">
                <li>UI uses real GraphQL data</li>
                <li>Selections persist (using the store)</li>
                <li>Image optimization is enabled</li>
              </ul>
            </div>
          {/if}
        </div>
      </CardContent>
    </Card>
    
    <!-- API Testing Card -->
    <Card>
      <CardHeader>
        <CardTitle>GraphQL API Testing</CardTitle>
      </CardHeader>
      <CardContent>
        <p class="mb-4">Run tests against the GraphQL API to verify connectivity.</p>
        <div class="flex flex-col space-y-4">
          <Button 
            variant={isRunningTests ? 'outline' : 'default'} 
            disabled={isRunningTests}
            onclick={runTests}
          >
            {isRunningTests ? 'Running Tests...' : 'Test GraphQL Endpoints'}
          </Button>
          
          <Button 
            variant="outline"
            onclick={runFullTests}
          >
            Run Full Debug Suite
          </Button>
          
          {#if testResults}
            <div class="pt-2">
              <h3 class="font-semibold mb-2">Test Results Summary:</h3>
              <div class="flex flex-wrap gap-2">
                {#each Object.entries(testResults.results) as [type, result]}
                  <Badge variant={result.items.length > 0 ? 'default' : 'outline'}>
                    {type}: {result.items.length} items
                  </Badge>
                {/each}
              </div>
              
              {#if Object.keys(testResults.errors).length > 0}
                <div class="mt-2 text-red-500">
                  <p class="font-semibold">Errors:</p>
                  <ul class="list-disc list-inside text-sm">
                    {#each Object.entries(testResults.errors) as [type, error]}
                      <li>{type}: {error}</li>
                    {/each}
                  </ul>
                </div>
              {/if}
            </div>
          {/if}
        </div>
      </CardContent>
    </Card>
  </div>
  
  <!-- Selection Visualization -->
  <Card class="mb-8">
    <CardHeader>
      <CardTitle>Selected Items</CardTitle>
    </CardHeader>
    <CardContent>
      {#if $selectedItems.length === 0}
        <p class="text-center text-gray-500 py-8">No items selected</p>
      {:else}
        <div class="mb-4">
          <h3 class="font-semibold mb-2">Selection Summary:</h3>
          <div class="flex flex-wrap gap-2">
            {#each Object.entries(getSelectionSummary()) as [type, count]}
              <Badge variant="default">
                {type}: {count}
              </Badge>
            {/each}
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-4">
          {#each $selectedItems as item}
            <div class="border rounded-lg p-3 hover:shadow-md transition-shadow">
              <div class="font-medium truncate">{item.title}</div>
              <div class="text-sm text-gray-500 mt-1">
                {item.type} {#if item.formattedDate}• {item.formattedDate}{/if}
              </div>
              {#if item.excerpt}
                <p class="text-sm mt-2 line-clamp-2">{item.excerpt}</p>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    </CardContent>
    <CardFooter>
      <Button 
        variant="outline" 
        onclick={clearSelection} 
        disabled={$selectedItems.length === 0}
      >
        Clear All
      </Button>
    </CardFooter>
  </Card>
  
  <!-- Component Instance -->
  {#if showSelector}
    <div class="mb-4 border-t border-b py-4">
      <h2 class="text-xl font-semibold mb-4">NewItemsSelector2 Component</h2>
      <NewItemsSelector2 
        maxItems={10}
        enabledContentTypes={['article', 'podcast', 'keyterm']}
        onSelectionChange={handleSelectionChange}
      />
    </div>
  {/if}
  
  <!-- Debug Info -->
  <div class="text-xs text-gray-500 mt-8">
    <p>To run tests automatically on page load, add <code>?autotest=true</code> to the URL.</p>
    <p>To run the full debug suite, add <code>?test=true</code> to the URL.</p>
  </div>
</div> 