<!--
  Security Settings
  ----------------
  Allows users to configure their security settings including:
  - Password management
  - Two-factor authentication
  - Session management
  - Login history
  - API access
  - Security notifications
  
  @file-marker security-settings-page
  @implementation-context: SvelteKit, Better Auth, Local First
-->
<script>
  import { page } from '$app/stores';
  import { invalidateAll } from '$app/navigation';
  import { Switch } from '$lib/components/ui/switch';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
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
  let user = $derived(data.user);
  
  // Password fields
  let currentPassword = $state('');
  let newPassword = $state('');
  let confirmPassword = $state('');
  
  // Two-factor authentication
  let is2FAEnabled = $state(user?.security?.twoFactorEnabled || false);
  
  // Session tracking
  let trackSessions = $state(user?.security?.trackSessions || true);
  
  // API access
  let allowAPIAccess = $state(user?.security?.allowAPIAccess || false);
  
  // Email notifications for security events
  let securityAlerts = $state(user?.security?.securityAlerts || true);
  
  // Update security settings when user data changes
  $effect(() => {
    if (user?.security) {
      is2FAEnabled = user.security.twoFactorEnabled || false;
      trackSessions = user.security.trackSessions || true;
      allowAPIAccess = user.security.allowAPIAccess || false;
      securityAlerts = user.security.securityAlerts || true;
    }
  });
  
  let isSavingPassword = $state(false);
  let passwordErrorMessage = $state('');
  let passwordSuccessMessage = $state('');
  
  let isSavingSettings = $state(false);
  let settingsErrorMessage = $state('');
  let settingsSuccessMessage = $state('');
  
  /**
   * Update password
   */
  async function updatePassword() {
    if (newPassword !== confirmPassword) {
      passwordErrorMessage = 'New passwords do not match';
      return;
    }
    
    isSavingPassword = true;
    passwordErrorMessage = '';
    passwordSuccessMessage = '';
    
    try {
      const response = await fetch('/api/user/password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          currentPassword,
          newPassword
        })
      });
      
      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Failed to update password');
      }
      
      passwordSuccessMessage = 'Password updated successfully!';
      currentPassword = '';
      newPassword = '';
      confirmPassword = '';
    } catch (error) {
      passwordErrorMessage = error.message || 'An error occurred';
      console.error('Failed to update password:', error);
    } finally {
      isSavingPassword = false;
    }
  }
  
  /**
   * Update security settings
   */
  async function updateSecuritySettings() {
    isSavingSettings = true;
    settingsErrorMessage = '';
    settingsSuccessMessage = '';
    
    try {
      const response = await fetch('/api/user/security', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          twoFactorEnabled: is2FAEnabled,
          trackSessions,
          allowAPIAccess,
          securityAlerts
        })
      });
      
      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Failed to update security settings');
      }
      
      // Update local user store with new security settings
      $userStore = {
        ...$userStore,
        security: {
          twoFactorEnabled: is2FAEnabled,
          trackSessions,
          allowAPIAccess,
          securityAlerts
        }
      };
      
      // Invalidate page data to refresh
      await invalidateAll();
      
      settingsSuccessMessage = 'Security settings updated successfully!';
    } catch (error) {
      settingsErrorMessage = error.message || 'An error occurred';
      console.error('Failed to update security settings:', error);
    } finally {
      isSavingSettings = false;
    }
  }
  
  /**
   * Setup 2FA
   */
  async function setupTwoFactor() {
    // In a real app, this would open a 2FA setup flow
    alert('2FA setup would be implemented here');
  }
</script>

<div>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Security Settings</h1>
    <a href=".." class="text-sm text-blue-600 hover:underline">← Back to Settings</a>
  </div>
  
  <div class="space-y-6">
    <!-- Password Management -->
    <Card>
      <CardHeader>
        <CardTitle>Password</CardTitle>
        <CardDescription>Update your password</CardDescription>
      </CardHeader>
      <CardContent>
        {#if passwordErrorMessage}
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-4">
            {passwordErrorMessage}
          </div>
        {/if}
        
        {#if passwordSuccessMessage}
          <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-4">
            {passwordSuccessMessage}
          </div>
        {/if}
        
        <form class="space-y-4" on:submit|preventDefault={updatePassword}>
          <div class="space-y-2">
            <Label for="currentPassword">Current Password</Label>
            <Input id="currentPassword" type="password" bind:value={currentPassword} required />
          </div>
          
          <div class="space-y-2">
            <Label for="newPassword">New Password</Label>
            <Input id="newPassword" type="password" bind:value={newPassword} required />
          </div>
          
          <div class="space-y-2">
            <Label for="confirmPassword">Confirm New Password</Label>
            <Input id="confirmPassword" type="password" bind:value={confirmPassword} required />
          </div>
        </form>
      </CardContent>
      <CardFooter>
        <Button class="w-full sm:w-auto" disabled={isSavingPassword} onclick={updatePassword}>
          {isSavingPassword ? 'Updating...' : 'Update Password'}
        </Button>
      </CardFooter>
    </Card>
    
    <!-- Two-Factor Authentication -->
    <Card>
      <CardHeader>
        <CardTitle>Two-Factor Authentication</CardTitle>
        <CardDescription>Add an extra layer of security to your account</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-medium">Enable Two-Factor Authentication</h3>
            <p class="text-sm text-muted-foreground">
              Protect your account with an additional authentication step
            </p>
          </div>
          <Switch checked={is2FAEnabled} onchange={() => {
            if (!is2FAEnabled) {
              // If enabling 2FA, show setup dialog
              setupTwoFactor();
            } else {
              // If disabling, just toggle the switch (will be saved later)
              is2FAEnabled = !is2FAEnabled;
            }
          }} />
        </div>
      </CardContent>
    </Card>
    
    <!-- Other Security Settings -->
    <Card>
      <CardHeader>
        <CardTitle>Other Security Settings</CardTitle>
        <CardDescription>Configure additional security options</CardDescription>
      </CardHeader>
      <CardContent>
        {#if settingsErrorMessage}
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-4">
            {settingsErrorMessage}
          </div>
        {/if}
        
        {#if settingsSuccessMessage}
          <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-4">
            {settingsSuccessMessage}
          </div>
        {/if}
        
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">Track Login Sessions</h3>
              <p class="text-sm text-muted-foreground">
                Track and manage your active login sessions
              </p>
            </div>
            <Switch checked={trackSessions} onchange={() => trackSessions = !trackSessions} />
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">API Access</h3>
              <p class="text-sm text-muted-foreground">
                Allow third-party applications to access your account via API
              </p>
            </div>
            <Switch checked={allowAPIAccess} onchange={() => allowAPIAccess = !allowAPIAccess} />
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium">Security Alerts</h3>
              <p class="text-sm text-muted-foreground">
                Receive email notifications about important security events
              </p>
            </div>
            <Switch checked={securityAlerts} onchange={() => securityAlerts = !securityAlerts} />
          </div>
        </div>
      </CardContent>
      <CardFooter>
        <Button class="w-full sm:w-auto" disabled={isSavingSettings} onclick={updateSecuritySettings}>
          {isSavingSettings ? 'Saving...' : 'Save Settings'}
        </Button>
      </CardFooter>
    </Card>
  </div>
</div> 