<script>
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { log } from '$lib/utils/log';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { FileText, BarChart2, BookmarkIcon, Calendar, Clock, Activity } from '$lib/utils/lucide-compat.js';
  
  // Access the streamed data from the page store
  $: dashboardData = $page.data.streamed?.dashboardData;
  $: user = $page.data.user;
  
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

<div class="container py-8">
  <h1 class="text-3xl font-bold mb-6 text-[hsl(var(--foreground))]">Dashboard</h1>
  
  <!-- Welcome message -->
  <div class="mb-8 p-6 bg-[hsl(var(--primary)/0.1)] rounded-lg border border-[hsl(var(--primary)/0.2)]">
    <h2 class="text-2xl font-semibold mb-2">
      Welcome back, {user?.displayName || 'there'}!
    </h2>
    <p class="text-[hsl(var(--muted-foreground))]">
      Here's a summary of your recent activity and digest stats.
    </p>
  </div>
  
  <!-- Stats Overview -->
  <div class="mb-8">
    <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
      <Icon icon={BarChart2} class="w-5 h-5" />
      <span>Statistics Overview</span>
    </h2>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Digests -->
      <div class="p-4 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
        <div class="text-[hsl(var(--muted-foreground))] text-sm mb-1">Total Digests</div>
        <div class="text-2xl font-bold">{dashboardData?.digests?.total || 0}</div>
      </div>
      
      <!-- This Week -->
      <div class="p-4 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
        <div class="text-[hsl(var(--muted-foreground))] text-sm mb-1">Digests This Week</div>
        <div class="text-2xl font-bold">{dashboardData?.digests?.thisWeek || 0}</div>
      </div>
      
      <!-- Last Week -->
      <div class="p-4 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
        <div class="text-[hsl(var(--muted-foreground))] text-sm mb-1">Digests Last Week</div>
        <div class="text-2xl font-bold">{dashboardData?.digests?.lastWeek || 0}</div>
      </div>
      
      <!-- Topics -->
      <div class="p-4 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
        <div class="text-[hsl(var(--muted-foreground))] text-sm mb-1">Favorite Topics</div>
        <div class="flex flex-wrap gap-1 mt-2">
          {#if dashboardData?.preferences?.topics}
            {#each dashboardData.preferences.topics as topic}
              <span class="inline-block px-2 py-1 text-xs bg-[hsl(var(--muted)/0.5)] rounded-full">
                {topic}
              </span>
            {/each}
          {:else}
            <span class="text-[hsl(var(--muted-foreground))]">No topics yet</span>
          {/if}
        </div>
      </div>
    </div>
  </div>
  
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Activity -->
    <div class="p-6 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
      <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
        <Icon icon={Activity} class="w-5 h-5" />
        <span>Recent Activity</span>
      </h2>
      
      {#if dashboardData?.recentActivity?.length}
        <ul class="divide-y divide-[hsl(var(--border))]">
          {#each dashboardData.recentActivity as activity}
            <li class="py-3">
              <div class="flex items-start">
                <div class="mt-1 mr-3">
                  <Icon icon={FileText} class="w-4 h-4 text-[hsl(var(--primary))]" />
                </div>
                <div>
                  <div class="font-medium">{activity.title}</div>
                  <div class="text-sm text-[hsl(var(--muted-foreground))] flex items-center gap-2">
                    <Icon icon={Calendar} class="w-3 h-3" />
                    <span>{formatDate(activity.date)}</span>
                  </div>
                </div>
              </div>
            </li>
          {/each}
        </ul>
      {:else}
        <p class="text-[hsl(var(--muted-foreground))]">No recent activity to display.</p>
      {/if}
    </div>
    
    <!-- Usage Stats -->
    <div class="p-6 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
      <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
        <Icon icon={BarChart2} class="w-5 h-5" />
        <span>Usage Stats</span>
      </h2>
      
      {#if dashboardData?.usage}
        <div class="space-y-4">
          <!-- Digests Usage -->
          <div>
            <div class="flex justify-between mb-1">
              <span class="text-sm font-medium">Digests</span>
              <span class="text-sm text-[hsl(var(--muted-foreground))]">
                {dashboardData.usage.digests.used} / {dashboardData.usage.digests.limit}
              </span>
            </div>
            <div class="w-full bg-[hsl(var(--muted)/0.5)] rounded-full h-2">
              <div 
                class="bg-[hsl(var(--primary))] h-2 rounded-full" 
                style="width: {Math.min(100, (dashboardData.usage.digests.used / dashboardData.usage.digests.limit) * 100)}%"
              ></div>
            </div>
          </div>
          
          <!-- Searches Usage -->
          <div>
            <div class="flex justify-between mb-1">
              <span class="text-sm font-medium">Searches</span>
              <span class="text-sm text-[hsl(var(--muted-foreground))]">
                {dashboardData.usage.searches.used} / {dashboardData.usage.searches.limit}
              </span>
            </div>
            <div class="w-full bg-[hsl(var(--muted)/0.5)] rounded-full h-2">
              <div 
                class="bg-[hsl(var(--primary))] h-2 rounded-full" 
                style="width: {Math.min(100, (dashboardData.usage.searches.used / dashboardData.usage.searches.limit) * 100)}%"
              ></div>
            </div>
          </div>
        </div>
      {:else}
        <p class="text-[hsl(var(--muted-foreground))]">Usage statistics unavailable.</p>
      {/if}
    </div>
    
    <!-- Preferences -->
    <div class="p-6 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
      <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
        <Icon icon={BookmarkIcon} class="w-5 h-5" />
        <span>Your Preferences</span>
      </h2>
      
      {#if dashboardData?.preferences}
        <ul class="space-y-3">
          <li class="flex justify-between">
            <span class="text-[hsl(var(--muted-foreground))]">Notifications</span>
            <span class="font-medium">{dashboardData.preferences.notificationsEnabled ? 'Enabled' : 'Disabled'}</span>
          </li>
          <li class="flex justify-between">
            <span class="text-[hsl(var(--muted-foreground))]">Email Frequency</span>
            <span class="font-medium capitalize">{dashboardData.preferences.emailFrequency}</span>
          </li>
          <li>
            <span class="text-[hsl(var(--muted-foreground))] block mb-2">Topics of Interest</span>
            <div class="flex flex-wrap gap-1">
              {#each dashboardData.preferences.topics as topic}
                <span class="inline-block px-2 py-1 text-xs bg-[hsl(var(--muted)/0.5)] rounded-full capitalize">
                  {topic}
                </span>
              {/each}
            </div>
          </li>
        </ul>
      {:else}
        <p class="text-[hsl(var(--muted-foreground))]">Preferences unavailable.</p>
      {/if}
    </div>
  </div>
</div>

<!-- Loading state for streamed data -->
{#await dashboardData}
  <div class="flex justify-center items-center py-12">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[hsl(var(--primary))]"></div>
  </div>
{:catch error}
  <div class="p-6 bg-[hsl(var(--destructive)/0.1)] text-[hsl(var(--destructive))] rounded-lg border border-[hsl(var(--destructive)/0.2)] mt-6">
    <h3 class="font-semibold">Error loading dashboard data</h3>
    <p class="mt-2">{error?.message || 'Failed to load dashboard data. Please try again later.'}</p>
  </div>
{/await} 