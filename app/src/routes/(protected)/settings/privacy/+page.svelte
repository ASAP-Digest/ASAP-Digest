<!--
  Privacy Settings
  ---------------
  Allows users to control their privacy settings including:
  - Data sharing preferences
  - Content tracking options
  - Personalization controls
  - Third-party integrations
  - Data retention and export options
  
  @file-marker privacy-settings-page
  @implementation-context: SvelteKit, Better Auth, Svelte Forms
-->
<script>
  import { page } from '$app/stores';
  import { invalidateAll } from '$app/navigation';
  import { Switch } from '$lib/components/ui/switch';
  import { Button } from '$lib/components/ui/button';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Label } from '$lib/components/ui/label';
  import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '$lib/components/ui/select';
  import { RadioGroup, RadioGroupItem } from '$lib/components/ui/radio-group';
  import { AlertCircle, Eye, Download, Scan, Layers, History } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  // Privacy settings state
  let dataSharing = $state({
    analytics: true,
    usageStats: true,
    contentPreferences: true,
    thirdPartyServices: false
  });
  
  let contentTracking = $state({
    readHistory: true,
    viewedContent: true,
    searchHistory: true
  });
  
  let personalization = $state({
    contentRecommendations: true,
    interestBasedContent: true,
    aiEnhancements: true
  });
  
  let dataRetention = $state('1-year');
  
  // Track form submission state
  let saving = $state(false);
  let success = $state(false);
  let error = $state('');
  
  /**
   * Save privacy settings
   */
  async function saveSettings() {
    saving = true;
    success = false;
    error = '';
    
    try {
      // In a real implementation, this would save to API
      // For demo, simulate a network request
      await new Promise(resolve => setTimeout(resolve, 800));
      
      const settings = {
        dataSharing,
        contentTracking,
        personalization,
        dataRetention
      };
      
      console.log('Saving privacy settings:', settings);
      
      // Simulate successful save
      success = true;
      // After successful save, you might want to invalidate related data
      // await invalidateAll();
    } catch (e) {
      console.error('Error saving privacy settings:', e);
      error = 'Failed to save privacy settings. Please try again.';
    } finally {
      saving = false;
      
      // Auto-hide success message after 3 seconds
      if (success) {
        setTimeout(() => {
          success = false;
        }, 3000);
      }
    }
  }
  
  /**
   * Request data export
   */
  function requestDataExport() {
    // In a real implementation, this would start a data export process
    alert('Your data export has been requested. You will be notified when it is ready for download.');
  }
</script>

<div class="container max-w-4xl py-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Privacy Settings</h1>
    <a href=".." class="text-sm text-blue-600 hover:underline">← Back to Settings</a>
  </div>
  
  <form on:submit|preventDefault={saveSettings} class="space-y-6">
    <!-- Data Sharing -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Scan} class="h-5 w-5" />
          Data Sharing
        </CardTitle>
        <CardDescription>Control how your data is used and shared</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="analytics-sharing" class="text-base">Analytics & Improvements</Label>
            <p class="text-sm text-muted-foreground">Help improve ASAP Digest by sharing usage data</p>
          </div>
          <Switch id="analytics-sharing" bind:checked={dataSharing.analytics} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="usage-stats" class="text-base">Usage Statistics</Label>
            <p class="text-sm text-muted-foreground">Share anonymous usage statistics</p>
          </div>
          <Switch id="usage-stats" bind:checked={dataSharing.usageStats} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="content-preferences" class="text-base">Content Preferences</Label>
            <p class="text-sm text-muted-foreground">Share content preferences to improve your experience</p>
          </div>
          <Switch id="content-preferences" bind:checked={dataSharing.contentPreferences} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="third-party" class="text-base">Third-Party Services</Label>
            <p class="text-sm text-muted-foreground">Allow data sharing with trusted partners</p>
          </div>
          <Switch id="third-party" bind:checked={dataSharing.thirdPartyServices} />
        </div>
      </CardContent>
    </Card>
    
    <!-- Content Tracking -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={History} class="h-5 w-5" />
          Content Tracking
        </CardTitle>
        <CardDescription>Manage what content activity we track</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="read-history" class="text-base">Read History</Label>
            <p class="text-sm text-muted-foreground">Track articles you've read</p>
          </div>
          <Switch id="read-history" bind:checked={contentTracking.readHistory} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="viewed-content" class="text-base">Viewed Content</Label>
            <p class="text-sm text-muted-foreground">Track content you've viewed</p>
          </div>
          <Switch id="viewed-content" bind:checked={contentTracking.viewedContent} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="search-history" class="text-base">Search History</Label>
            <p class="text-sm text-muted-foreground">Track your search queries</p>
          </div>
          <Switch id="search-history" bind:checked={contentTracking.searchHistory} />
        </div>
      </CardContent>
    </Card>
    
    <!-- Personalization -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Layers} class="h-5 w-5" />
          Personalization
        </CardTitle>
        <CardDescription>Customize your content experience</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="content-recommendations" class="text-base">Content Recommendations</Label>
            <p class="text-sm text-muted-foreground">Receive personalized content recommendations</p>
          </div>
          <Switch id="content-recommendations" bind:checked={personalization.contentRecommendations} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="interest-based" class="text-base">Interest-Based Content</Label>
            <p class="text-sm text-muted-foreground">Tailor content based on your interests</p>
          </div>
          <Switch id="interest-based" bind:checked={personalization.interestBasedContent} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="ai-enhancements" class="text-base">AI Enhancements</Label>
            <p class="text-sm text-muted-foreground">Use AI to enhance your content experience</p>
          </div>
          <Switch id="ai-enhancements" bind:checked={personalization.aiEnhancements} />
        </div>
      </CardContent>
    </Card>
    
    <!-- Data Retention & Export -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Eye} class="h-5 w-5" />
          Data Retention & Export
        </CardTitle>
        <CardDescription>Manage how long we keep your data and request exports</CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <div>
          <Label for="data-retention" class="text-base">Data Retention Period</Label>
          <p class="text-sm text-muted-foreground mb-3">Choose how long we store your activity data</p>
          
          <Select bind:value={dataRetention}>
            <SelectTrigger id="data-retention" class="w-full md:w-[250px]">
              <SelectValue placeholder="Select retention period" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="3-months">3 Months</SelectItem>
              <SelectItem value="6-months">6 Months</SelectItem>
              <SelectItem value="1-year">1 Year</SelectItem>
              <SelectItem value="2-years">2 Years</SelectItem>
              <SelectItem value="forever">Forever</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div>
          <Label class="text-base">Data Export</Label>
          <p class="text-sm text-muted-foreground mb-3">Request a copy of all your data</p>
          
          <Button variant="outline" type="button" on:click={requestDataExport} class="flex gap-2">
            <Icon icon={Download} class="h-4 w-4" />
            Request Data Export
          </Button>
        </div>
      </CardContent>
    </Card>
    
    <div class="flex justify-end">
      {#if error}
        <div class="p-3 bg-red-100 text-red-600 rounded-md flex items-center mr-auto">
          <Icon icon={AlertCircle} class="h-5 w-5 mr-1" />
          {error}
        </div>
      {/if}
      
      {#if success}
        <div class="p-3 bg-green-100 text-green-600 rounded-md mr-auto">
          Privacy settings saved successfully!
        </div>
      {/if}
      
      <Button type="submit" disabled={saving}>
        {saving ? 'Saving...' : 'Save Privacy Settings'}
      </Button>
    </div>
  </form>
</div> 