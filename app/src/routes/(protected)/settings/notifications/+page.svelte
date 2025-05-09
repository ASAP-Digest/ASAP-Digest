<!--
  Notification Settings
  -----------------
  Allows users to configure their notification preferences including:
  - Email notifications
  - Push notifications (browser/device)
  - Notification frequency
  - Content type preferences
  
  @file-marker notification-settings-page
  @implementation-context: SvelteKit, Better Auth, Svelte Forms
-->
<script>
  import { page } from '$app/stores';
  import { invalidateAll } from '$app/navigation';
  import { Switch } from '$lib/components/ui/switch';
  import { Button } from '$lib/components/ui/button';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '$lib/components/ui/select';
  import { RadioGroup, RadioGroupItem } from '$lib/components/ui/radio-group';
  import { AlertCircle, Bell, Mail } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  // Notification settings state
  let emailEnabled = $state(true);
  let pushEnabled = $state(true);
  let digestNotifications = $state(true);
  let contentUpdates = $state(true);
  let accountNotifications = $state(true);
  let marketingEmails = $state(false);
  let frequency = $state('daily');
  let preferredTime = $state('morning');
  
  // Track form submission state
  let saving = $state(false);
  let success = $state(false);
  let error = $state('');
  
  /**
   * Save notification preferences
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
        emailEnabled,
        pushEnabled,
        digestNotifications,
        contentUpdates,
        accountNotifications,
        marketingEmails,
        frequency,
        preferredTime
      };
      
      console.log('Saving notification settings:', settings);
      
      // Simulate successful save
      success = true;
      // After successful save, you might want to invalidate related data
      // await invalidateAll();
    } catch (e) {
      console.error('Error saving notification settings:', e);
      error = 'Failed to save notification settings. Please try again.';
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
</script>

<div class="container max-w-4xl py-6">
  <h1 class="text-3xl font-bold mb-6">Notification Settings</h1>
  
  <form on:submit|preventDefault={saveSettings} class="space-y-6">
    <!-- Email Notifications -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Mail} class="h-5 w-5" />
          Email Notifications
        </CardTitle>
        <CardDescription>Configure what emails you receive and how often</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="email-notifications" class="text-base">Enable Email Notifications</Label>
            <p class="text-sm text-muted-foreground">Receive updates via email</p>
          </div>
          <Switch id="email-notifications" bind:checked={emailEnabled} />
        </div>
        
        <div class="space-y-4 border-t pt-4 mt-4">
          <Label class="text-sm font-medium">Email Frequency</Label>
          <RadioGroup bind:value={frequency} disabled={!emailEnabled}>
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="daily" id="daily" />
              <Label for="daily">Daily Digest</Label>
            </div>
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="weekly" id="weekly" />
              <Label for="weekly">Weekly Summary</Label>
            </div>
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="immediate" id="immediate" />
              <Label for="immediate">Immediate (As Content Arrives)</Label>
            </div>
          </RadioGroup>
        </div>
        
        <div class="space-y-4 border-t pt-4 mt-4">
          <Label class="text-sm font-medium">Preferred Time of Day</Label>
          <RadioGroup bind:value={preferredTime} disabled={!emailEnabled || frequency === 'immediate'}>
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="morning" id="morning" />
              <Label for="morning">Morning (8:00 AM)</Label>
            </div>
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="afternoon" id="afternoon" />
              <Label for="afternoon">Afternoon (1:00 PM)</Label>
            </div>
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="evening" id="evening" />
              <Label for="evening">Evening (6:00 PM)</Label>
            </div>
          </RadioGroup>
        </div>
      </CardContent>
    </Card>
    
    <!-- Push Notifications -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Bell} class="h-5 w-5" />
          Push Notifications
        </CardTitle>
        <CardDescription>Configure browser and device notifications</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="push-notifications" class="text-base">Enable Push Notifications</Label>
            <p class="text-sm text-muted-foreground">Receive alerts in your browser or device</p>
          </div>
          <Switch id="push-notifications" bind:checked={pushEnabled} />
        </div>
      </CardContent>
    </Card>
    
    <!-- Notification Types -->
    <Card>
      <CardHeader>
        <CardTitle>Notification Types</CardTitle>
        <CardDescription>Control which types of notifications you receive</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="digest-notifications" class="text-base">Digest Notifications</Label>
            <p class="text-sm text-muted-foreground">When new digests are available</p>
          </div>
          <Switch id="digest-notifications" bind:checked={digestNotifications} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="content-updates" class="text-base">Content Updates</Label>
            <p class="text-sm text-muted-foreground">When new content matches your interests</p>
          </div>
          <Switch id="content-updates" bind:checked={contentUpdates} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="account-notifications" class="text-base">Account Notifications</Label>
            <p class="text-sm text-muted-foreground">Security and account-related alerts</p>
          </div>
          <Switch id="account-notifications" bind:checked={accountNotifications} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="marketing-emails" class="text-base">Marketing Updates</Label>
            <p class="text-sm text-muted-foreground">Product updates and promotional content</p>
          </div>
          <Switch id="marketing-emails" bind:checked={marketingEmails} />
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
          Settings saved successfully!
        </div>
      {/if}
      
      <Button type="submit" disabled={saving}>
        {saving ? 'Saving...' : 'Save Settings'}
      </Button>
    </div>
  </form>
</div> 