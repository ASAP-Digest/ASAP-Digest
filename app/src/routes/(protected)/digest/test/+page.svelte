<script>
  import { onMount } from 'svelte';
  import { 
    fetchLayoutTemplates, 
    createDraftDigest, 
    addModuleToDigest,
    fetchDigest,
    fetchUserDigests
  } from '$lib/api/digest-builder.js';
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
      
      // Test 1: Fetch Layout Templates
      addResult('Fetching layout templates...', null, null);
      try {
        console.log('[Test] About to call fetchLayoutTemplates...');
        const layoutResponse = await fetchLayoutTemplates();
        console.log('[Test] Layout response:', layoutResponse);
        addResult('Fetch Layout Templates', true, layoutResponse);
      } catch (err) {
        console.error('[Test] Layout fetch error:', err);
        addResult('Fetch Layout Templates', false, null, err.message);
      }

      // Test 2: Fetch User Digests
      if (userData.wpUserId) {
        addResult('Fetching user digests...', null, null);
        try {
          const userDigestsResponse = await fetchUserDigests(userData.wpUserId);
          addResult('Fetch User Digests', true, userDigestsResponse);
        } catch (err) {
          addResult('Fetch User Digests', false, null, err.message);
        }

        // Test 3: Create Draft Digest
        addResult('Creating draft digest...', null, null);
        try {
          const draftResponse = await createDraftDigest(userData.wpUserId, 'solo-focus');
          addResult('Create Draft Digest', true, draftResponse);

          if (draftResponse.success && draftResponse.data?.digest_id) {
            const digestId = draftResponse.data.digest_id;

            // Test 4: Fetch Created Digest
            addResult('Fetching created digest...', null, null);
            try {
              const fetchResponse = await fetchDigest(digestId);
              addResult('Fetch Created Digest', true, fetchResponse);
            } catch (err) {
              addResult('Fetch Created Digest', false, null, err.message);
            }

            // Test 5: Add Module to Digest (using dummy module ID)
            addResult('Adding module to digest...', null, null);
            try {
              const moduleResponse = await addModuleToDigest(digestId, 1, {
                grid_x: 0,
                grid_y: 0,
                grid_width: 12,
                grid_height: 6
              });
              addResult('Add Module to Digest', true, moduleResponse);
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
    <h1 class="text-3xl font-bold mb-2">Digest Builder API Test</h1>
    <p class="text-muted-foreground">
      Test the digest builder API endpoints to ensure they're working correctly.
    </p>
  </div>

  <div class="mb-6">
    <div class="flex gap-4">
      <Button onclick={runTests} disabled={isRunning}>
        {isRunning ? 'Running Tests...' : 'Run API Tests'}
      </Button>
      <Button variant="outline" onclick={clearResults} disabled={testResults.length === 0}>
        Clear Results
      </Button>
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