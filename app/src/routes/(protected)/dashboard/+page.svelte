<script>
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { log } from '$lib/utils/log';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { FileText, BarChart2, BookmarkIcon, Calendar, Clock, Activity } from '$lib/utils/lucide-compat.js';
  import { getUserData } from '$lib/stores/user.js';

  // Access the streamed data from the page store
  const dashboardData = $derived($page.data.streamed?.dashboardData);
  const user = $derived($page.data.user);
  
  // Get user data helper for cleaner access
  const userData = $derived(getUserData($page.data.session?.user));
  
  onMount(() => {
    log('Dashboard page mounted', 'debug');
  });
  
  // Format date to a more readable format
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }
</script>

<!-- The outermost div with grid-stack-item is already added by a previous edit -->
<!-- Remove the inner container wrapper and make sections direct grid-stack-items -->

<!-- Welcome Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <h1 class="text-2xl font-bold mb-6">Welcome, {userData.email}!</h1>
    <div class="bg-[hsl(var(--card))] p-6 rounded-lg shadow-sm mb-8">
      <p class="text-[hsl(var(--muted-foreground))]">
        This is your dashboard. Get a quick overview of your activity and access key features.
      </p>
    </div>
  </div>
</div>

<!-- Digest Overview Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-4">Your Digests</h2>
      <div class="bg-[hsl(var(--card))] p-6 rounded-lg shadow-sm">
        <p class="text-[hsl(var(--muted-foreground))]">Digest overview coming soon...</p>
        <!-- Placeholder for digest list or summary -->
      </div>
    </section>
  </div>
</div>

<!-- Quick Actions Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <section>
      <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="/digest/create" class="flex items-center justify-between bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))] p-4 rounded-lg shadow-sm hover:bg-[hsl(var(--secondary-foreground))] hover:text-[hsl(var(--secondary))] transition-colors">
          <span>Create New Digest</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
        </a>
        <a href="/explore" class="flex items-center justify-between bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))] p-4 rounded-lg shadow-sm hover:bg-[hsl(var(--secondary-foreground))] hover:text-[hsl(var(--secondary))] transition-colors">
          <span>Explore Content</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 4l-4 4 4 4" />
          </svg>
        </a>
      </div>
    </section>
  </div>
</div>

<!-- Loading state for streamed data -->
{#await dashboardData}
  <div class="flex justify-center items-center py-12">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[hsl(var(--link))]"></div>
  </div>
{:catch error}
  <div class="p-6 bg-[hsl(var(--functional-error)/0.1)] text-[hsl(var(--functional-error))] rounded-[var(--radius-md)] border border-[hsl(var(--functional-error)/0.2)] mt-6">
    <h3 class="font-[var(--font-weight-semibold)]">Error loading dashboard data</h3>
    <p class="mt-2">{error?.message || 'Failed to load dashboard data. Please try again later.'}</p>
  </div>
{/await} 