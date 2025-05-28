<script>
  import { onMount } from 'svelte';
  import { fetchGraphQL } from '$lib/utils/fetchGraphQL.js';

  let testResults = [];
  let loading = false;

  async function testGraphQLConnection() {
    loading = true;
    testResults = [];

    // Test 1: Basic introspection query
    try {
      testResults.push({ test: 'Basic Introspection', status: 'running' });
      const introspectionQuery = `{ __schema { types { name } } }`;
      const result = await fetchGraphQL(introspectionQuery);
      testResults.push({ 
        test: 'Basic Introspection', 
        status: 'success', 
        data: `Found ${result.__schema.types.length} types` 
      });
    } catch (error) {
      testResults.push({ 
        test: 'Basic Introspection', 
        status: 'error', 
        error: error.message 
      });
    }

    // Test 2: Check if AsapLayoutTemplate type exists
    try {
      testResults.push({ test: 'AsapLayoutTemplate Type Check', status: 'running' });
      const typeQuery = `{ __type(name: "AsapLayoutTemplate") { name fields { name type { name } } } }`;
      const result = await fetchGraphQL(typeQuery);
      testResults.push({ 
        test: 'AsapLayoutTemplate Type Check', 
        status: result.__type ? 'success' : 'error', 
        data: result.__type ? `Type exists with ${result.__type.fields.length} fields` : 'Type not found'
      });
    } catch (error) {
      testResults.push({ 
        test: 'AsapLayoutTemplate Type Check', 
        status: 'error', 
        error: error.message 
      });
    }

    // Test 3: Try layoutTemplates query
    try {
      testResults.push({ test: 'Layout Templates Query', status: 'running' });
      const layoutQuery = `{ layoutTemplates { id slug title description } }`;
      const result = await fetchGraphQL(layoutQuery);
      testResults.push({ 
        test: 'Layout Templates Query', 
        status: 'success', 
        data: `Found ${result.layoutTemplates ? result.layoutTemplates.length : 0} templates`
      });
    } catch (error) {
      testResults.push({ 
        test: 'Layout Templates Query', 
        status: 'error', 
        error: error.message 
      });
    }

    // Test 4: Check available queries
    try {
      testResults.push({ test: 'Available Queries', status: 'running' });
      const queriesQuery = `{ __schema { queryType { fields { name description } } } }`;
      const result = await fetchGraphQL(queriesQuery);
      const queryNames = result.__schema.queryType.fields.map(f => f.name);
      testResults.push({ 
        test: 'Available Queries', 
        status: 'success', 
        data: `Available queries: ${queryNames.join(', ')}`
      });
    } catch (error) {
      testResults.push({ 
        test: 'Available Queries', 
        status: 'error', 
        error: error.message 
      });
    }

    loading = false;
  }

  onMount(() => {
    testGraphQLConnection();
  });
</script>

<div class="container mx-auto p-8">
  <h1 class="text-3xl font-bold mb-6">GraphQL Connection Test</h1>
  
  <div class="mb-4">
    <button 
      on:click={testGraphQLConnection} 
      disabled={loading}
      class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
    >
      {loading ? 'Testing...' : 'Run Tests'}
    </button>
  </div>

  <div class="space-y-4">
    {#each testResults as result}
      <div class="border rounded p-4 {result.status === 'success' ? 'border-green-500 bg-green-50' : result.status === 'error' ? 'border-red-500 bg-red-50' : 'border-yellow-500 bg-yellow-50'}">
        <h3 class="font-semibold text-lg">{result.test}</h3>
        <p class="text-sm text-gray-600 mb-2">Status: {result.status}</p>
        {#if result.data}
          <p class="text-green-700">{result.data}</p>
        {/if}
        {#if result.error}
          <p class="text-red-700 font-mono text-sm">{result.error}</p>
        {/if}
      </div>
    {/each}
  </div>

  <div class="mt-8 p-4 bg-gray-100 rounded">
    <h2 class="text-xl font-semibold mb-2">Debug Info</h2>
    <p><strong>GraphQL Endpoint:</strong> {import.meta.env.VITE_WORDPRESS_GRAPHQL_URL || 'https://asapdigest.local/graphql'}</p>
    <p><strong>Current Origin:</strong> {typeof window !== 'undefined' ? window.location.origin : 'Server-side'}</p>
  </div>
</div> 