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
  @implementation-context: SvelteKit, Better Auth, Local First
-->
<script>
  import { page } from '$app/stores';
  import { invalidateAll } from '$app/navigation';
  import { Switch } from '$lib/components/ui/switch';
  import { Button } from '$lib/components/ui/button';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Label } from '$lib/components/ui/label';
  import { user as userStore } from '$lib/utils/auth-persistence';
  
  /**
   * @typedef {Object} PageData
   * @property {Object} user - User data
   * @property {boolean} [usingLocalAuth] - Whether using cached local auth
   */
  
  /** @type {PageData} */
  let { data } = $props();
  
  // Create reactive derived state for user data to ensure updates during navigation
  let user = $derived(data?.user || null);
  
  // Initialize privacy settings with defaults
  let dataSharing = $state(false);
  let contentTracking = $state(true);
  let personalization = $state(true);
  let thirdPartyIntegration = $state(false);
  
  // Update privacy settings when user data changes
  $effect(() => {
    if (user?.privacy) {
      dataSharing = user.privacy.dataSharing ?? false;
      contentTracking = user.privacy.contentTracking ?? true;
      personalization = user.privacy.personalization ?? true;
      thirdPartyIntegration = user.privacy.thirdPartyIntegration ?? false;
    }
  });
  
  let isSavingSettings = $state(false);
  let errorMessage = $state('');
  let successMessage = $state('');
  
  /**
   * Save privacy settings
   */
  async function savePrivacySettings() {
    isSavingSettings = true;
    errorMessage = '';
    successMessage = '';
    
    try {
      const response = await fetch('/api/user/privacy', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          dataSharing,
          contentTracking,
          personalization,
          thirdPartyIntegration
        })
      });
      
      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Failed to update privacy settings');
      }
      
      // Update local user store with new privacy settings
      $userStore = {
        ...$userStore,
        privacy: {
          dataSharing,
          contentTracking,
          personalization,
          thirdPartyIntegration
        }
      };
      
      // Invalidate page data to refresh
      await invalidateAll();
      
      successMessage = 'Privacy settings updated successfully!';
    } catch (error) {
      errorMessage = error.message || 'An error occurred';
      console.error('Failed to update privacy settings:', error);
    } finally {
      isSavingSettings = false;
    }
  }
  
  /**
   * Request data export
   */
  async function requestDataExport() {
    alert('Data export request would be submitted here');
    // In a real implementation, this would submit a data export request
  }
  
  /**
   * Request data deletion
   */
  async function requestDataDeletion() {
    alert('Data deletion request would be submitted here');
    // In a real implementation, this would initiate a data deletion flow
  }
</script>

<div class="grid-stack-item">
<div>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Privacy Settings</h1>
    <a href=".." class="text-sm text-blue-600 hover:underline">← Back to Settings</a>
  </div>
  
  <div class="space-y-6">
    {#if errorMessage}
      <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
        {errorMessage}
      </div>
    {/if}
    
    {#if successMessage}
      <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
        {successMessage}
      </div>
    {/if}
    
    <Card>
      <CardHeader>
        <CardTitle>Data Sharing & Privacy Controls</CardTitle>
        <CardDescription>Manage how your data is used and shared</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">Data Sharing</h3>
              <p class="text-sm text-muted-foreground">
                Share your data with partners to improve services
              </p>
            </div>
            <Switch checked={dataSharing} onchange={() => dataSharing = !dataSharing} />
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">Content Tracking</h3>
              <p class="text-sm text-muted-foreground">
                Allow tracking of content you view to improve recommendations
              </p>
            </div>
            <Switch checked={contentTracking} onchange={() => contentTracking = !contentTracking} />
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">Personalization</h3>
              <p class="text-sm text-muted-foreground">
                Allow us to personalize content based on your activity
              </p>
            </div>
            <Switch checked={personalization} onchange={() => personalization = !personalization} />
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">Third-Party Integrations</h3>
              <p class="text-sm text-muted-foreground">
                Allow third-party services to access your account data
              </p>
            </div>
            <Switch checked={thirdPartyIntegration} onchange={() => thirdPartyIntegration = !thirdPartyIntegration} />
          </div>
        </div>
      </CardContent>
      <CardFooter>
        <Button class="w-full sm:w-auto" disabled={isSavingSettings} onclick={savePrivacySettings}>
          {isSavingSettings ? 'Saving...' : 'Save Settings'}
        </Button>
      </CardFooter>
    </Card>
    
    <Card>
      <CardHeader>
        <CardTitle>Your Data</CardTitle>
        <CardDescription>Manage your personal data</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <div>
            <h3 class="text-sm font-medium">Data Export</h3>
            <p class="text-sm text-muted-foreground mb-2">
              Download a copy of all your personal data
            </p>
            <Button variant="outline" onclick={requestDataExport}>Request Data Export</Button>
          </div>
          
          <div>
            <h3 class="text-sm font-medium">Data Deletion</h3>
            <p class="text-sm text-muted-foreground mb-2">
              Request deletion of your personal data (excluding required account information)
            </p>
            <Button variant="destructive" onclick={requestDataDeletion}>Request Data Deletion</Button>
          </div>
        </div>
      </CardContent>
    </Card>
    </div>
  </div>
</div> 