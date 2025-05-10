<!--
  Notification Settings
  -------------------
  Allows users to configure their notification preferences including:
  - Email notifications
  - In-app notifications
  - Mobile push notifications
  - Digest frequency
  - Content alerts
  
  @file-marker notification-settings-page
  @implementation-context: SvelteKit, Better Auth, Local First
-->
<script>
  import { page } from '$app/stores';
  import { invalidateAll } from '$app/navigation';
  import { Switch } from '$lib/components/ui/switch';
  import { Button } from '$lib/components/ui/button';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Label } from '$lib/components/ui/label';
  import { RadioGroup, RadioGroupItem } from '$lib/components/ui/radio-group';
  import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '$lib/components/ui/select';
  import { user as userStore } from '$lib/utils/auth-persistence';
  import { getCSRFToken } from '$lib/auth-client.js';
  import { toasts } from '$lib/stores/toast.js';
  
  /**
   * @typedef {Object} PageData
   * @property {Object} user - User data
   * @property {boolean} [usingLocalAuth] - Whether using cached local auth
   */
  
  /** @type {PageData} */
  let { data } = $props();
  
  // Create reactive derived state for user data to ensure updates during navigation
  let user = $derived(data?.user || null);
  
  // Initialize notification settings with defaults
  let emailNotifications = $state(true);
  let inAppNotifications = $state(true);
  let pushNotifications = $state(false);
  let digestFrequency = $state('daily');
  let contentAlerts = $state(true);
  
  // Update notification settings when user data changes
  $effect(() => {
    if (user?.notifications) {
      emailNotifications = user.notifications.email ?? true;
      inAppNotifications = user.notifications.inApp ?? true;
      pushNotifications = user.notifications.push ?? false;
      digestFrequency = user.notifications.digestFrequency || 'daily';
      contentAlerts = user.notifications.contentAlerts ?? true;
    }
  });
  
  let isSavingSettings = $state(false);
  let errorMessage = $state('');
  let successMessage = $state('');
  
  /**
   * @description Save notification preferences
   */
  async function saveNotificationSettings() {
    isSavingSettings = true;
    
    try {
      // Prepare update data
      const updateData = {
        id: data.user.id,
        preferences: {
          ...data.user.preferences,
          notifications: {
            email: emailNotifications,
            inApp: inAppNotifications,
            push: pushNotifications,
            digestFrequency,
            contentAlerts
          }
        },
        updatedAt: new Date().toISOString()
      };
      
      // Get CSRF token
      const csrfToken = await getCSRFToken();
      
      // Get all existing cookies to ensure we're sending the session cookie
      const allCookies = document.cookie;
      console.log('Using cookies for auth:', allCookies);
      
      // Make API call to save settings
      const response = await fetch('/api/auth/profile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
          'Accept': 'application/json',
          'Cookie': allCookies // Explicitly include cookies
        },
        body: JSON.stringify(updateData),
        credentials: 'include', // CRITICAL: Include credentials (cookies) with request
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Failed to update notification settings');
      }
      
      // Success
      toasts.show('Notification settings updated successfully', 'success');
      
      // Refresh page data
      await invalidateAll();
      
    } catch (error) {
      toasts.show(error.message || 'Failed to update notification settings', 'error');
      console.error('Error saving notification settings:', error);
    } finally {
      isSavingSettings = false;
    }
  }
</script>

<div>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Notification Settings</h1>
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
        <CardTitle>Notification Channels</CardTitle>
        <CardDescription>Configure how you receive notifications</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
              <h3 class="text-sm font-medium">Email Notifications</h3>
              <p class="text-sm text-muted-foreground">
                Receive notifications via email
              </p>
            </div>
            <Switch checked={emailNotifications} onchange={() => emailNotifications = !emailNotifications} />
        </div>
        
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">In-App Notifications</h3>
              <p class="text-sm text-muted-foreground">
                Receive notifications within the app
              </p>
            </div>
            <Switch checked={inAppNotifications} onchange={() => inAppNotifications = !inAppNotifications} />
        </div>
          
        <div class="flex items-center justify-between">
          <div>
              <h3 class="text-sm font-medium">Push Notifications</h3>
              <p class="text-sm text-muted-foreground">
                Receive push notifications on your devices
              </p>
            </div>
            <Switch checked={pushNotifications} onchange={() => pushNotifications = !pushNotifications} />
          </div>
        </div>
      </CardContent>
    </Card>
    
    <Card>
      <CardHeader>
        <CardTitle>Notification Preferences</CardTitle>
        <CardDescription>Customize your notification experience</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-6">
          <div>
            <Label class="text-sm font-medium mb-1">Digest Frequency</Label>
            <p class="text-sm text-muted-foreground mb-3">
              How often you want to receive content digest emails
            </p>
            
            <RadioGroup value={digestFrequency} onchange={e => digestFrequency = e.target.value}>
              <div class="flex items-center space-x-2 mb-2">
                <RadioGroupItem value="daily" id="daily" />
                <Label for="daily">Daily</Label>
              </div>
              <div class="flex items-center space-x-2 mb-2">
                <RadioGroupItem value="weekly" id="weekly" />
                <Label for="weekly">Weekly</Label>
          </div>
              <div class="flex items-center space-x-2">
                <RadioGroupItem value="never" id="never" />
                <Label for="never">Never</Label>
        </div>
            </RadioGroup>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
              <h3 class="text-sm font-medium">Content Alerts</h3>
              <p class="text-sm text-muted-foreground">
                Get notified about new content matching your interests
              </p>
          </div>
            <Switch checked={contentAlerts} onchange={() => contentAlerts = !contentAlerts} />
          </div>
        </div>
      </CardContent>
      <CardFooter>
        <Button class="w-full sm:w-auto" disabled={isSavingSettings} onclick={saveNotificationSettings}>
          {isSavingSettings ? 'Saving...' : 'Save Settings'}
        </Button>
      </CardFooter>
    </Card>
    </div>
</div> 