<!--
  Admin Analytics Dashboard
  ------------------------
  Displays usage metrics and cost analysis from the backend analytics API.
  Allows posting new service tracking records.
  Uses metrics.js API client.
  @file-marker admin-analytics-dashboard
  @implementation-context: SvelteKit, Better Auth, Analytics
-->
<script>
  import { onMount } from 'svelte';
  import { getUsageMetrics, getCostAnalysis, postServiceTracking } from '$lib/api/metrics.js';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { RefreshCw } from '$lib/utils/lucide-compat.js';

  let usageMetrics = [];
  let costAnalysis = [];
  let isLoading = true;
  let error = '';
  let form = { service: '', usage: '', timestamp: '', user_id: '', cost: '' };
  let formError = '';
  let formSuccess = '';

  async function loadAnalytics() {
    isLoading = true;
    error = '';
    try {
      const usage = await getUsageMetrics();
      const cost = await getCostAnalysis();
      usageMetrics = usage.data || [];
      costAnalysis = cost.data || [];
    } catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
    }
  }

  async function submitTracking() {
    formError = '';
    formSuccess = '';
    try {
      // TODO: Validate form fields
      const payload = {
        service: form.service,
        usage: parseFloat(form.usage),
        timestamp: form.timestamp,
        user_id: form.user_id ? parseInt(form.user_id) : undefined,
        cost: form.cost ? parseFloat(form.cost) : undefined
      };
      const res = await postServiceTracking(payload);
      if (res.success) {
        formSuccess = 'Tracking record submitted!';
        form = { service: '', usage: '', timestamp: '', user_id: '', cost: '' };
        await loadAnalytics();
      } else {
        formError = res.error || 'Submission failed.';
      }
    } catch (e) {
      formError = e.message;
    }
  }

  onMount(loadAnalytics);
</script>

<div class="p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Analytics Dashboard</h1>
    <Button on:click={loadAnalytics} variant="outline">
      <RefreshCw class="w-4 h-4 mr-2" /> Refresh
    </Button>
  </div>

  {#if isLoading}
    <p>Loading analytics...</p>
  {:else if error}
    <p class="text-red-500">{error}</p>
  {:else}
    <div class="mb-8">
      <h2 class="text-lg font-semibold mb-2">Usage Metrics</h2>
      <table class="min-w-full border text-sm mb-4">
        <thead>
          <tr>
            <th class="px-3 py-2 border">ID</th>
            <th class="px-3 py-2 border">Service</th>
            <th class="px-3 py-2 border">Usage</th>
            <th class="px-3 py-2 border">Timestamp</th>
            <th class="px-3 py-2 border">User ID</th>
          </tr>
        </thead>
        <tbody>
          {#each usageMetrics as m}
            <tr>
              <td class="px-3 py-2 border">{m.id}</td>
              <td class="px-3 py-2 border">{m.service}</td>
              <td class="px-3 py-2 border">{m.usage}</td>
              <td class="px-3 py-2 border">{m.timestamp}</td>
              <td class="px-3 py-2 border">{m.user_id}</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
    <div class="mb-8">
      <h2 class="text-lg font-semibold mb-2">Cost Analysis</h2>
      <table class="min-w-full border text-sm mb-4">
        <thead>
          <tr>
            <th class="px-3 py-2 border">ID</th>
            <th class="px-3 py-2 border">Service</th>
            <th class="px-3 py-2 border">Cost</th>
            <th class="px-3 py-2 border">Timestamp</th>
          </tr>
        </thead>
        <tbody>
          {#each costAnalysis as c}
            <tr>
              <td class="px-3 py-2 border">{c.id}</td>
              <td class="px-3 py-2 border">{c.service}</td>
              <td class="px-3 py-2 border">{c.cost}</td>
              <td class="px-3 py-2 border">{c.timestamp}</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
    <div class="mb-8">
      <h2 class="text-lg font-semibold mb-2">Submit Service Tracking</h2>
      <form on:submit|preventDefault={submitTracking} class="space-y-4 max-w-xl">
        <div class="flex gap-4">
          <Input placeholder="Service" bind:value={form.service} required class="flex-1" />
          <Input placeholder="Usage" type="number" step="any" bind:value={form.usage} required class="flex-1" />
        </div>
        <div class="flex gap-4">
          <Input placeholder="Timestamp (YYYY-MM-DDTHH:MM:SSZ)" bind:value={form.timestamp} required class="flex-1" />
          <Input placeholder="User ID (optional)" type="number" bind:value={form.user_id} class="flex-1" />
          <Input placeholder="Cost (optional)" type="number" step="any" bind:value={form.cost} class="flex-1" />
        </div>
        <Button type="submit">Submit</Button>
        {#if formError}
          <p class="text-red-500 mt-2">{formError}</p>
        {/if}
        {#if formSuccess}
          <p class="text-green-600 mt-2">{formSuccess}</p>
        {/if}
      </form>
    </div>
  {/if}
</div> 