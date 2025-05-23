<!--
  Content Crawler Analytics Page
  -----------------------------
  Displays crawler and storage metrics from the backend.
  Uses crawler-api.js for backend operations.
-->
<script>
  import { onMount } from 'svelte';
  import { getAllMetrics } from '$lib/api/crawler-api.js';
  import { Button } from '$lib/components/ui/button';
  import { RefreshCw } from '$lib/utils/lucide-compat.js';

  let sourceMetrics = [];
  let storageMetrics = [];
  let isLoading = true;
  let error = $state('');

  async function loadMetrics() {
    isLoading = true;
    error = '';
    try {
      const data = await getAllMetrics();
      sourceMetrics = data.source_metrics || [];
      storageMetrics = data.storage_metrics || [];
    } catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
    }
  }

  onMount(loadMetrics);
</script>

<div class="p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Crawler Analytics</h1>
    <Button on:click={loadMetrics} variant="outline">
      <RefreshCw class="w-4 h-4 mr-2" /> Refresh
    </Button>
  </div>

  {#if isLoading}
    <p>Loading metrics...</p>
  {:else if error}
    <p class="text-red-500">{error}</p>
  {:else}
    <div class="mb-8">
      <h2 class="text-lg font-semibold mb-2">Source Metrics</h2>
      <table class="min-w-full border text-sm mb-4">
        <thead>
          <tr>
            <th class="px-3 py-2 border">Source ID</th>
            <th class="px-3 py-2 border">Date</th>
            <th class="px-3 py-2 border">Items Found</th>
            <th class="px-3 py-2 border">Items Stored</th>
            <th class="px-3 py-2 border">Items Rejected</th>
            <th class="px-3 py-2 border">Processing Time</th>
            <th class="px-3 py-2 border">Error Count</th>
          </tr>
        </thead>
        <tbody>
          {#each sourceMetrics as m}
            <tr>
              <td class="px-3 py-2 border">{m.source_id}</td>
              <td class="px-3 py-2 border">{m.date}</td>
              <td class="px-3 py-2 border">{m.items_found}</td>
              <td class="px-3 py-2 border">{m.items_stored}</td>
              <td class="px-3 py-2 border">{m.items_rejected}</td>
              <td class="px-3 py-2 border">{m.processing_time}</td>
              <td class="px-3 py-2 border">{m.error_count}</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
    <div>
      <h2 class="text-lg font-semibold mb-2">Storage Metrics</h2>
      <table class="min-w-full border text-sm">
        <thead>
          <tr>
            <th class="px-3 py-2 border">Source ID</th>
            <th class="px-3 py-2 border">Content Type</th>
            <th class="px-3 py-2 border">Date</th>
            <th class="px-3 py-2 border">Item Count</th>
            <th class="px-3 py-2 border">Total Size</th>
          </tr>
        </thead>
        <tbody>
          {#each storageMetrics as m}
            <tr>
              <td class="px-3 py-2 border">{m.source_id}</td>
              <td class="px-3 py-2 border">{m.content_type}</td>
              <td class="px-3 py-2 border">{m.date}</td>
              <td class="px-3 py-2 border">{m.item_count}</td>
              <td class="px-3 py-2 border">{m.total_size}</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</div> 