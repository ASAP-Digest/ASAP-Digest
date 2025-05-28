<script>
  import { onMount } from 'svelte';
  import { 
    fetchLayoutTemplates, 
    createDraftDigest, 
    addModuleToDigest,
    fetchDigest,
    fetchUserDigests,
    fetchEnhancedContent,
    updateDigestStatus,
    removeModuleFromDigest,
    saveDigestLayout
  } from '$lib/api/digest-builder-graphql.js';
  import { fetchGraphQL } from '$lib/utils/fetchGraphQL.js';
  import Button from '$lib/components/ui/button/button.svelte';
  import Card from '$lib/components/ui/card/card.svelte';
  import CardContent from '$lib/components/ui/card/card-content.svelte';
  import CardHeader from '$lib/components/ui/card/card-header.svelte';
  import CardTitle from '$lib/components/ui/card/card-title.svelte';
  import { getUserData } from '$lib/stores/user.js';

  /** @type {import('./$types').PageData} */
  const { data } = $props();

  // Get user data helper for cleaner access
  const userData = $derived(getUserData(data.user));

  let testResults = $state([]);
  let isRunning = $state(false);
  let useHttpEndpoint = $state(false); // Toggle for HTTP vs HTTPS testing
  let useIpAddress = $state(false); // Toggle for using 127.0.0.1 instead of localhost

  function addResult(test, success, data, error = null) {
    testResults.push({
      test,
      success,
      data,
      error,
      timestamp: new Date().toLocaleTimeString()
    });
    testResults = [...testResults]; // Trigger reactivity
  }

  function getEndpointUrl(path = '') {
    const protocol = useHttpEndpoint ? 'http' : 'https';
    const baseUrl = `${protocol}://asapdigest.local`;
    return baseUrl + path;
  }

  function getCurrentOrigin() {
    if (typeof window === 'undefined') return 'Server-side';
    
    if (useIpAddress) {
      // Replace localhost with 127.0.0.1 in the current origin
      return window.location.origin.replace('localhost', '127.0.0.1');
    }
    return window.location.origin;
  }

  // Check for mixed content issues
  function checkMixedContentIssue() {
    const currentOrigin = getCurrentOrigin();
    const endpoint = getEndpointUrl('/graphql');
    
    const originIsHttps = currentOrigin.startsWith('https://');
    const endpointIsHttp = endpoint.startsWith('http://');
    
    return {
      hasMixedContent: originIsHttps && endpointIsHttp,
      currentOrigin,
      endpoint,
      originIsHttps,
      endpointIsHttp
    };
  }

  // Custom fetchGraphQL for testing that respects the HTTP/HTTPS toggle
  async function testFetchGraphQL(query, variables = {}) {
    const graphqlEndpoint = getEndpointUrl('/graphql');

    console.log('[testFetchGraphQL] Making request to:', graphqlEndpoint);
    console.log('[testFetchGraphQL] Query:', query.substring(0, 100) + '...');

    try {
      const headers = {
        'Content-Type': 'application/json',
      };

      // Add session token if available
      if (typeof window !== 'undefined') {
        try {
          const authData = JSON.parse(localStorage.getItem('asap_auth_data') || '{}');
          if (authData.sessionToken) {
            headers['Authorization'] = `Bearer ${authData.sessionToken}`;
          }
        } catch (e) {
          console.warn('[testFetchGraphQL] Could not parse auth data:', e);
        }
      }

      const response = await fetch(graphqlEndpoint, {
        method: 'POST',
        headers,
        credentials: 'include',
        body: JSON.stringify({ query, variables }),
      });

      if (!response.ok) {
        throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
      }

      const jsonResponse = await response.json();

      if (jsonResponse.errors) {
        throw new Error(jsonResponse.errors[0]?.message || 'GraphQL query returned errors.');
      }

      return jsonResponse.data;
    } catch (error) {
      console.error('[testFetchGraphQL] Error:', error);
      
      // Enhanced error analysis for Brave browser
      const enhancedError = {
        originalError: error,
        errorType: error.name,
        errorMessage: error.message,
        endpoint: graphqlEndpoint,
        timestamp: new Date().toISOString(),
        browserInfo: {
          userAgent: navigator.userAgent,
          isBrave: typeof navigator.brave !== 'undefined',
          cookiesEnabled: navigator.cookieEnabled,
          onLine: navigator.onLine
        },
        networkInfo: {
          effectiveType: navigator.connection?.effectiveType || 'unknown',
          downlink: navigator.connection?.downlink || 'unknown'
        },
        possibleCauses: []
      };

      // Analyze possible causes based on error type and browser
      if (error.name === 'TypeError' && error.message === 'Failed to fetch') {
        if (typeof navigator.brave !== 'undefined') {
          enhancedError.possibleCauses.push('Brave browser localhost access restriction');
          enhancedError.possibleCauses.push('Check brave://flags/#brave-localhost-access-permission');
        }
        enhancedError.possibleCauses.push('CORS policy blocking request');
        enhancedError.possibleCauses.push('Network connectivity issue');
        enhancedError.possibleCauses.push('Server not responding');
      }

      console.error('[testFetchGraphQL] Enhanced error analysis:', enhancedError);
      throw error;
    }
  }

  async function runGraphQLIntrospectionTests() {
    // Test -1: Mixed Content Detection
    const mixedContentCheck = checkMixedContentIssue();
    addResult('Mixed Content Analysis', !mixedContentCheck.hasMixedContent, {
      ...mixedContentCheck,
      browserInfo: {
        userAgent: navigator.userAgent,
        isBrave: navigator.userAgent.includes('Brave') || typeof navigator.brave !== 'undefined',
        isChrome: navigator.userAgent.includes('Chrome'),
      },
      recommendation: mixedContentCheck.hasMixedContent 
        ? 'Mixed content detected! Use HTTPS endpoint or enable Brave localhost access permission.'
        : 'No mixed content issues detected.'
    }, mixedContentCheck.hasMixedContent ? 'Mixed content: HTTPS origin requesting HTTP endpoint' : null);

    // Test -0.5: Same-origin test (to isolate cross-origin issues)
    addResult('Testing same-origin request...', null, null);
    try {
      // Test a request to the same origin (should always work)
      const sameOriginResponse = await fetch('/', {
        method: 'GET',
        credentials: 'include'
      });
      
      addResult('Same-Origin Request', true, {
        status: sameOriginResponse.status,
        statusText: sameOriginResponse.statusText,
        note: 'This confirms basic fetch() is working - testing homepage'
      });
    } catch (error) {
      addResult('Same-Origin Request', false, {
        errorName: error.name,
        errorMessage: error.message,
        note: 'If this fails, there\'s a deeper browser issue'
      }, error.message);
    }

    // Test 0: Basic WordPress site connectivity (homepage)
    addResult('Testing WordPress site homepage...', null, null);
    try {
      const homepageUrl = getEndpointUrl('/');
      const response = await fetch(homepageUrl, {
        method: 'GET',
        credentials: 'include'
      });
      
      addResult('WordPress Homepage', response.ok, {
        status: response.status,
        statusText: response.statusText,
        url: homepageUrl,
        headers: Object.fromEntries(response.headers.entries()),
        contentType: response.headers.get('content-type')
      });
    } catch (error) {
      addResult('WordPress Homepage', false, {
        errorName: error.name,
        errorMessage: error.message,
        url: getEndpointUrl('/'),
        troubleshooting: [
          'Check if Local by Flywheel is running',
          'Verify the site is started in Local',
          'Check if asapdigest.local resolves in your hosts file',
          'Try accessing https://asapdigest.local in your browser'
        ]
      }, error.message);
    }

    // Test 1: WordPress REST API connectivity test (to isolate GraphQL vs general connectivity)
    addResult('Testing WordPress REST API connectivity...', null, null);
    try {
      const wpApiUrl = getEndpointUrl('/wp-json/wp/v2/users/me');
      const response = await fetch(wpApiUrl, {
        method: 'GET',
        credentials: 'include'
      });
      
      addResult('WordPress REST API', response.ok, {
        status: response.status,
        statusText: response.statusText,
        url: wpApiUrl,
        headers: Object.fromEntries(response.headers.entries())
      });
    } catch (error) {
      addResult('WordPress REST API', false, null, `${error.name}: ${error.message}`);
    }

    // Test 2: Check if GraphQL endpoint exists (basic GET request)
    addResult('Testing GraphQL endpoint existence...', null, null);
    try {
      const endpoint = getEndpointUrl('/graphql');
      
      // Try a simple GET request to see if the endpoint exists
      const response = await fetch(endpoint, {
        method: 'GET',
        credentials: 'include'
      });
      
      addResult('GraphQL Endpoint Existence', true, {
        status: response.status,
        statusText: response.statusText,
        headers: Object.fromEntries(response.headers.entries()),
        url: endpoint,
        note: response.status === 405 ? 'Method Not Allowed is expected for GET on GraphQL endpoint' : 'Unexpected response'
      });
    } catch (error) {
      addResult('GraphQL Endpoint Existence', false, null, `${error.name}: ${error.message}`);
      
      // Add more detailed error information
      addResult('Detailed Connection Error', false, {
        errorName: error.name,
        errorMessage: error.message,
        errorStack: error.stack,
        endpoint: getEndpointUrl('/graphql'),
        userAgent: navigator.userAgent,
        currentOrigin: window.location.origin,
        troubleshooting: [
          '1. Verify Local by Flywheel is running',
          '2. Check that the asapdigest site is started in Local',
          '3. Confirm asapdigest.local resolves (try ping asapdigest.local)',
          '4. Test WordPress admin access: https://asapdigest.local/wp-admin',
          '5. Verify WPGraphQL plugin is installed and active',
          '6. Check Local site SSL certificate'
        ]
      }, error.message);
      
      return false; // Stop further tests if basic connectivity fails
    }

    // Test 3: Basic GraphQL Connection with detailed error handling
    addResult('Testing GraphQL connection...', null, null);
    try {
      const introspectionQuery = `{ __schema { types { name } } }`;
      
      // Add detailed logging before the request
      console.log('[GraphQL Test] Making introspection request...');
      console.log('[GraphQL Test] Query:', introspectionQuery);
      
      const result = await testFetchGraphQL(introspectionQuery);
      
      addResult('GraphQL Connection', true, {
        message: `Connected successfully. Found ${result.__schema.types.length} types`,
        typeCount: result.__schema.types.length,
        sampleTypes: result.__schema.types.slice(0, 10).map(t => t.name)
      });
    } catch (error) {
      console.error('[GraphQL Test] Connection error:', error);
      
      addResult('GraphQL Connection', false, {
        errorDetails: {
          name: error.name,
          message: error.message,
          stack: error.stack
        },
        requestInfo: {
          endpoint: getEndpointUrl('/graphql'),
          origin: window.location.origin,
          userAgent: navigator.userAgent
        }
      }, error.message);
      
      return false; // Stop further tests if basic connection fails
    }

    // Test 4: Check Available Queries
    addResult('Checking available queries...', null, null);
    try {
      const queriesQuery = `{ __schema { queryType { fields { name description } } } }`;
      const result = await testFetchGraphQL(queriesQuery);
      const queryNames = result.__schema.queryType.fields.map(f => f.name);
      addResult('Available Queries', true, {
        queries: queryNames,
        count: queryNames.length
      });
    } catch (error) {
      addResult('Available Queries', false, null, error.message);
    }

    // Test 5: Check Available Mutations
    addResult('Checking available mutations...', null, null);
    try {
      const mutationsQuery = `{ __schema { mutationType { fields { name description } } } }`;
      const result = await testFetchGraphQL(mutationsQuery);
      const mutationNames = result.__schema.mutationType ? result.__schema.mutationType.fields.map(f => f.name) : [];
      addResult('Available Mutations', true, {
        mutations: mutationNames,
        count: mutationNames.length
      });
    } catch (error) {
      addResult('Available Mutations', false, null, error.message);
    }

    // Test 6: Check AsapLayoutTemplate Type
    addResult('Checking AsapLayoutTemplate type...', null, null);
    try {
      const typeQuery = `{ __type(name: "AsapLayoutTemplate") { name fields { name type { name } } } }`;
      const result = await testFetchGraphQL(typeQuery);
      if (result.__type) {
        addResult('AsapLayoutTemplate Type', true, {
          typeName: result.__type.name,
          fields: result.__type.fields.map(f => ({ name: f.name, type: f.type.name })),
          fieldCount: result.__type.fields.length
        });
      } else {
        addResult('AsapLayoutTemplate Type', false, null, 'Type not found in schema');
      }
    } catch (error) {
      addResult('AsapLayoutTemplate Type', false, null, error.message);
    }

    return true;
  }

  async function runDigestBuilderTests() {
    // Test 1: Fetch Layout Templates
    addResult('Fetching layout templates...', null, null);
    try {
      console.log('[Test] About to call fetchLayoutTemplates...');
      const layoutResponse = await fetchLayoutTemplates();
      console.log('[Test] Layout response:', layoutResponse);
      addResult('Fetch Layout Templates', layoutResponse.success, layoutResponse);
    } catch (err) {
      console.error('[Test] Layout fetch error:', err);
      addResult('Fetch Layout Templates', false, null, err.message);
    }

    // Test 2: Fetch User Digests
    if (userData.wpUserId) {
      addResult('Fetching user digests...', null, null);
      try {
        const userDigestsResponse = await fetchUserDigests(userData.wpUserId);
        addResult('Fetch User Digests', userDigestsResponse.success, userDigestsResponse);
      } catch (err) {
        addResult('Fetch User Digests', false, null, err.message);
      }

      // Test 3: Create Draft Digest
      addResult('Creating draft digest...', null, null);
      try {
        const draftResponse = await createDraftDigest(userData.wpUserId, 'fallback-simple');
        addResult('Create Draft Digest', draftResponse.success, draftResponse);

        if (draftResponse.success && draftResponse.data?.digest_id) {
          const digestId = draftResponse.data.digest_id;

          // Test 4: Fetch Created Digest
          addResult('Fetching created digest...', null, null);
          try {
            const fetchResponse = await fetchDigest(digestId);
            addResult('Fetch Created Digest', fetchResponse.success, fetchResponse);
          } catch (err) {
            addResult('Fetch Created Digest', false, null, err.message);
          }

          // Test 5: Add Module to Digest (using dummy module data)
          addResult('Adding module to digest...', null, null);
          try {
            const moduleData = {
              id: 'test-module-1',
              title: 'Test Module',
              type: 'content',
              content: 'This is a test module for GraphQL testing'
            };
            const gridPosition = {
              x: 0,
              y: 0,
              w: 6,
              h: 4
            };
            const moduleResponse = await addModuleToDigest(digestId, moduleData, gridPosition);
            addResult('Add Module to Digest', moduleResponse.success, moduleResponse);

            // Test 6: Update Digest Status
            if (moduleResponse.success) {
              addResult('Updating digest status...', null, null);
              try {
                const statusResponse = await updateDigestStatus(digestId, 'published');
                addResult('Update Digest Status', statusResponse.success, statusResponse);
              } catch (err) {
                addResult('Update Digest Status', false, null, err.message);
              }
            }
          } catch (err) {
            addResult('Add Module to Digest', false, null, err.message);
          }
        }
      } catch (err) {
        addResult('Create Draft Digest', false, null, err.message);
      }
    } else {
      addResult('User Authentication', false, null, `User not authenticated - wpUserId: ${userData.wpUserId}, email: ${userData.email}`);
    }
  }

  async function runEnhancedContentTests() {
    // Test Enhanced Content Fetching
    addResult('Fetching enhanced content...', null, null);
    try {
      const contentResponse = await fetchEnhancedContent(
        { type: 'article', status: 'published' },
        { page: 1, per_page: 5 }
      );
      addResult('Fetch Enhanced Content', contentResponse.success, contentResponse);
    } catch (err) {
      addResult('Fetch Enhanced Content', false, null, err.message);
    }
  }

  async function runTests() {
    isRunning = true;
    testResults = [];

    try {
      // Enhanced Debug: Log all user data
      console.log('[Test] Raw data.user:', data.user);
      console.log('[Test] getUserData result:', userData);
      console.log('[Test] userData.debugInfo:', userData.debugInfo);
      
      // Add user data as first test result
      addResult('User Data Analysis', true, {
        rawUser: data.user,
        processedUser: userData.toJSON(),
        debugInfo: userData.debugInfo,
        wpUserId: userData.wpUserId,
        isValid: userData.isValid,
        isComplete: userData.isComplete
      });

      // Run GraphQL introspection tests first
      const graphqlWorking = await runGraphQLIntrospectionTests();
      
      if (graphqlWorking) {
        // Run digest builder tests
        await runDigestBuilderTests();
        
        // Run enhanced content tests
        await runEnhancedContentTests();
      } else {
        addResult('Test Suite', false, null, 'GraphQL connection failed - skipping remaining tests');
      }

    } catch (err) {
      addResult('Test Suite', false, null, err.message);
    } finally {
      isRunning = false;
    }
  }

  function clearResults() {
    testResults = [];
  }
</script>

<div class="container py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold mb-2">GraphQL Digest Builder API Test</h1>
    <p class="text-muted-foreground">
      Test the GraphQL digest builder API endpoints to ensure they're working correctly.
      This includes introspection queries, layout templates, digest operations, and enhanced content.
    </p>
  </div>

  <div class="mb-6">
    <div class="flex gap-4 items-center">
      <Button onclick={runTests} disabled={isRunning}>
        {isRunning ? 'Running Tests...' : 'Run GraphQL API Tests'}
      </Button>
      <Button variant="outline" onclick={clearResults} disabled={testResults.length === 0}>
        Clear Results
      </Button>
      
      <!-- HTTP/HTTPS Toggle -->
      <div class="flex items-center gap-2 ml-4">
        <label class="text-sm font-medium">
          <input 
            type="checkbox" 
            bind:checked={useHttpEndpoint}
            class="mr-2"
          />
          Use HTTP (bypass SSL)
        </label>
      </div>

      <!-- IP Address Toggle -->
      <div class="flex items-center gap-2 ml-4">
        <label class="text-sm font-medium">
          <input 
            type="checkbox" 
            bind:checked={useIpAddress}
            class="mr-2"
          />
          Use 127.0.0.1 instead of localhost
        </label>
      </div>
    </div>
    
    {#if useHttpEndpoint}
      <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded text-sm text-blue-700 dark:text-blue-300">
        <strong>HTTP Mode:</strong> Testing with HTTP to bypass SSL certificate issues. This is useful for Local by Flywheel development.
      </div>
    {/if}
  </div>

  <!-- GraphQL Endpoint Info -->
  <div class="mb-4 p-4 bg-muted/30 rounded-lg">
    <h3 class="font-semibold mb-2">GraphQL Configuration</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <p><strong>Endpoint:</strong> {getEndpointUrl('/graphql')}</p>
        <p><strong>Current Origin:</strong> {getCurrentOrigin()}</p>
      </div>
      <div>
        <p><strong>Auth Method:</strong> Session cookies + Bearer token</p>
        <p><strong>CORS Enabled:</strong> Yes</p>
      </div>
    </div>
  </div>

  <!-- Enhanced User Info Display -->
  <div class="mb-4 p-4 bg-muted/30 rounded-lg">
    <h3 class="font-semibold mb-2">User Authentication Status</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <p><strong>Email:</strong> {userData.email || 'Not available'}</p>
        <p><strong>Display Name:</strong> {userData.displayName || 'Not available'}</p>
        <p><strong>WP User ID:</strong> {userData.wpUserId || 'Not available'}</p>
      </div>
      <div>
        <p><strong>Is Valid:</strong> {userData.isValid ? '✅' : '❌'}</p>
        <p><strong>Is Complete:</strong> {userData.isComplete ? '✅' : '❌'}</p>
        <p><strong>Sync Status:</strong> {userData.syncStatus}</p>
      </div>
    </div>
  </div>

  <!-- Troubleshooting Section -->
  <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
    <h3 class="font-semibold mb-2 text-yellow-800 dark:text-yellow-200">🔧 Troubleshooting Brave Browser & Mixed Content Issues</h3>
    <div class="text-sm text-yellow-700 dark:text-yellow-300 space-y-2">
      <p><strong>If you're seeing "Failed to fetch" errors in Brave browser:</strong></p>
      
      <div class="bg-yellow-100 dark:bg-yellow-800 p-3 rounded mb-3">
        <p class="font-semibold">🛡️ Brave Browser Specific Fixes:</p>
        <ol class="list-decimal list-inside space-y-1 ml-2 mt-1">
          <li>Go to <code class="bg-yellow-200 dark:bg-yellow-700 px-1 rounded">brave://flags/#brave-localhost-access-permission</code></li>
          <li>Set "Enable Localhost access permission prompt" to <strong>Enabled</strong></li>
          <li><strong>Also try:</strong> <code class="bg-yellow-200 dark:bg-yellow-700 px-1 rounded">brave://flags/#block-insecure-private-network-requests</code></li>
          <li>Set "Block insecure private network requests" to <strong>Disabled</strong></li>
          <li><strong>Restart Brave browser completely</strong></li>
          <li>Try the test again - you should get a permission prompt</li>
        </ol>
        <p class="mt-2 text-sm"><strong>Alternative:</strong> Try testing in Chrome or Safari to confirm it's Brave-specific</p>
      </div>

      <p><strong>General troubleshooting steps:</strong></p>
      <ol class="list-decimal list-inside space-y-1 ml-4">
        <li><strong>Check Local by Flywheel:</strong> Ensure the app is running and the "asapdigest" site is started (green play button)</li>
        <li><strong>Test WordPress access:</strong> Try opening <a href="https://asapdigest.local" target="_blank" class="underline">https://asapdigest.local</a> in your browser</li>
        <li><strong>Check admin access:</strong> Try <a href="https://asapdigest.local/wp-admin" target="_blank" class="underline">https://asapdigest.local/wp-admin</a></li>
        <li><strong>Verify GraphQL plugin:</strong> Ensure WPGraphQL plugin is installed and active</li>
        <li><strong>Test GraphQL endpoint:</strong> Try <a href="https://asapdigest.local/graphql" target="_blank" class="underline">https://asapdigest.local/graphql</a></li>
        <li><strong>Mixed content issue:</strong> If using HTTP endpoint from HTTPS origin, enable the Brave flag above</li>
      </ol>
      <p class="mt-2"><strong>Quick test:</strong> Run the tests below to see which specific step is failing.</p>
    </div>
  </div>

  {#if !userData.wpUserId}
    <div class="mb-4 p-4 bg-destructive/10 rounded-lg">
      <p class="text-sm text-destructive">
        <strong>Warning:</strong> User not authenticated or missing WordPress User ID. Some tests may fail.
      </p>
      <details class="mt-2">
        <summary class="cursor-pointer text-sm font-medium">View Debug Info</summary>
        <pre class="mt-2 p-3 bg-muted rounded text-xs overflow-auto">{JSON.stringify(userData.debugInfo, null, 2)}</pre>
      </details>
    </div>
  {/if}

  {#if testResults.length > 0}
    <div class="space-y-4">
      <h2 class="text-xl font-semibold">Test Results</h2>
      
      {#each testResults as result}
        <Card class="test-result {result.success === true ? 'success' : result.success === false ? 'error' : 'info'}">
          <CardHeader>
            <CardTitle class="text-base flex items-center gap-2">
              <span class="status-indicator">
                {#if result.success === true}
                  ✅
                {:else if result.success === false}
                  ❌
                {:else}
                  ℹ️
                {/if}
              </span>
              {result.test}
              <span class="text-sm text-muted-foreground ml-auto">
                {result.timestamp}
              </span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            {#if result.error}
              <div class="error-message">
                <strong>Error:</strong> {result.error}
              </div>
            {/if}
            {#if result.data}
              <details class="mt-2">
                <summary class="cursor-pointer text-sm font-medium">View Response Data</summary>
                <pre class="mt-2 p-3 bg-muted rounded text-xs overflow-auto">{JSON.stringify(result.data, null, 2)}</pre>
              </details>
            {/if}
          </CardContent>
        </Card>
      {/each}
    </div>
  {/if}
</div>

<style>
  .test-result.success {
    border-left: 4px solid hsl(var(--success, 142 76% 36%));
  }
  
  .test-result.error {
    border-left: 4px solid hsl(var(--destructive, 0 84% 60%));
  }
  
  .test-result.info {
    border-left: 4px solid hsl(var(--primary, 221 83% 53%));
  }
  
  .error-message {
    color: hsl(var(--destructive, 0 84% 60%));
    font-family: monospace;
    font-size: 0.875rem;
  }
  
  .status-indicator {
    font-size: 1rem;
  }
</style> 