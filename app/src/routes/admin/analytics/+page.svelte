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
  import Icon from '$lib/components/ui/icon/icon.svelte';

  let usageMetrics = [];
  let costAnalysis = $state([]);
  let isLoading = $state(true);
  let error = $state('');
  let form = $state({ service: '', usage: '', timestamp: '', user_id: '', cost: '' });
  let formError = $state('');
  let formSuccess = '';
  let loading = $state(true);

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

<!-- The outermost div with grid-stack-item is already added by a previous edit -->
<!-- Remove the inner wrapper and make sections direct grid-stack-items -->

<!-- Analytics Header - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <h1 class="text-2xl font-bold mb-6">Analytics Dashboard</h1>
  </div>
</div>

{#if loading}
  <!-- Loading State - Treat as Gridstack item -->
  <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="4">
    <div class="grid-stack-item-content">
      <div class="flex justify-center items-center h-full">
        <p>Loading analytics data...</p>
      </div>
    </div>
  </div>
{:else if error}
  <!-- Error State - Treat as Gridstack item -->
  <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="4">
    <div class="grid-stack-item-content">
      <div class="flex justify-center items-center h-full text-red-500">
        <p>Error loading analytics data: {error.message}</p>
      </div>
    </div>
  </div>
{:else}
  <!-- User Signups Section - Treat as Gridstack item -->
  <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
    <div class="grid-stack-item-content">
      <section class="mb-8">
        <h2 class="text-xl font-semibold mb-4">User Signups</h2>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
          <p>Signups data coming soon...</p>
          <!-- Placeholder for signup chart/data -->
        </div>
      </section>
    </div>
  </div>

  <!-- Content Trends Section - Treat as Gridstack item -->
  <div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
    <div class="grid-stack-item-content">
      <section>
        <h2 class="text-xl font-semibold mb-4">Content Trends</h2>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
          <p>Content trends data coming soon...</p>
          <!-- Placeholder for content trends chart/data -->
        </div>
      </section>
    </div>
  </div>
{/if} 