<!--
  Content Crawler Moderation Queue Page
  -------------------------------------
  Lists all pending content items, allows approving or rejecting them.
  Uses crawler-api.js for backend operations.
-->
<script>
  import { onMount } from 'svelte';
  import { getQueuedContent, approveContent, rejectContent } from '$lib/api/crawler-api.js';
  import { Button } from '$lib/components/ui/button';
  import { Check, X, RefreshCw } from '$lib/utils/lucide-compat.js';

  let queue = [];
  let isLoading = true;
  let error = '';
  let actionLoading = {};
  let actionError = {};

  async function loadQueue() {
    isLoading = true;
    error = '';
    try {
      queue = await getQueuedContent();
    } catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
    }
  }

  onMount(loadQueue);

  async function handleApprove(id) {
    actionLoading[id] = true;
    actionError[id] = '';
    try {
      await approveContent(id);
      await loadQueue();
    } catch (e) {
      actionError[id] = e.message;
    } finally {
      actionLoading[id] = false;
    }
  }

  async function handleReject(id) {
    actionLoading[id] = true;
    actionError[id] = '';
    try {
      await rejectContent(id);
      await loadQueue();
    } catch (e) {
      actionError[id] = e.message;
    } finally {
      actionLoading[id] = false;
    }
  }
</script>

<div class="p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Moderation Queue</h1>
    <Button on:click={loadQueue} variant="outline">
      <RefreshCw class="w-4 h-4 mr-2" /> Refresh
    </Button>
  </div>

  {#if isLoading}
    <p>Loading moderation queue...</p>
  {:else if error}
    <p class="text-red-500">{error}</p>
  {:else if queue.length === 0}
    <p>No pending content items.</p>
  {:else}
    <table class="min-w-full border text-sm">
      <thead>
        <tr>
          <th class="px-3 py-2 border">Title</th>
          <th class="px-3 py-2 border">Type</th>
          <th class="px-3 py-2 border">Date</th>
          <th class="px-3 py-2 border">Status</th>
          <th class="px-3 py-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        {#each queue as item}
          <tr>
            <td class="px-3 py-2 border">{item.post_title}</td>
            <td class="px-3 py-2 border">{item.post_type}</td>
            <td class="px-3 py-2 border">{item.post_date}</td>
            <td class="px-3 py-2 border">{item.post_status}</td>
            <td class="px-3 py-2 border flex gap-2">
              <Button size="sm" variant="success" disabled={actionLoading[item.ID]} on:click={() => handleApprove(item.ID)}>
                <Check class="w-4 h-4" /> Approve
              </Button>
              <Button size="sm" variant="destructive" disabled={actionLoading[item.ID]} on:click={() => handleReject(item.ID)}>
                <X class="w-4 h-4" /> Reject
              </Button>
              {#if actionError[item.ID]}
                <span class="text-red-500 ml-2">{actionError[item.ID]}</span>
              {/if}
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  {/if}
</div> 