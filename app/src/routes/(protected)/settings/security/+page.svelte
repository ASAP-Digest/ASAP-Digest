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
  
  // Password fields
  let currentPassword = $state('');
  let newPassword = $state('');
  let confirmPassword = $state('');
  
  // Initialize security settings with defaults
  let is2FAEnabled = $state(false);
  let trackSessions = $state(true);
  let allowAPIAccess = $state(false);
  let securityAlerts = $state(true);
  
  // Update security settings when user data changes
  $effect(() => {
    if (user?.security) {
      is2FAEnabled = user.security.twoFactorEnabled ?? false;
      trackSessions = user.security.trackSessions ?? true;
      allowAPIAccess = user.security.allowAPIAccess ?? false;
      securityAlerts = user.security.securityAlerts ?? true;
    }
  });
  
  let isSavingPassword = $state(false);
  let passwordErrorMessage = $state('');
  let passwordSuccessMessage = $state('');
  
  let isSavingSettings = $state(false);
  let settingsErrorMessage = $state('');
  let settingsSuccessMessage = $state('');
  
  /**
   * Handle password update
   * @param {SubmitEvent} event The form submission event
   * @returns {Promise<void>}
   */
  async function updatePassword(event) {
    event.preventDefault();
    
    if (newPassword !== confirmPassword) {
      toasts.show('Passwords do not match', 'error');
      return;
    }
    
    try {
      isSavingPassword = true;
      passwordErrorMessage = '';
      passwordSuccessMessage = '';
      
      // Get CSRF token
      const csrfToken = await getCSRFToken();
      
      // Get all existing cookies to ensure we're sending the session cookie
      const allCookies = document.cookie;
      console.log('Using cookies for auth:', allCookies);
      
      const response = await fetch('/api/auth/password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
          'Accept': 'application/json',
          'Cookie': allCookies // Explicitly include cookies
        },
        body: JSON.stringify({
          currentPassword,
          newPassword
        }),
        credentials: 'include', // CRITICAL: Include credentials (cookies) with request
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Failed to update password');
      }
      
      // Success
      toasts.show('Password updated successfully', 'success');
      
      // Reset form
      currentPassword = '';
      newPassword = '';
      confirmPassword = '';
      
    } catch (error) {
      toasts.show(error.message || 'Failed to update password', 'error');
    } finally {
      isSavingPassword = false;
    }
  }
  
  /**
   * Handle security settings update
   * @param {SubmitEvent} event The form submission event
   * @returns {Promise<void>}
   */
  async function updateSecuritySettings(event) {
    event.preventDefault();
    
    try {
      isSavingSettings = true;
      settingsErrorMessage = '';
      settingsSuccessMessage = '';
      
      // Get CSRF token
      const csrfToken = await getCSRFToken();
      
      // Get all existing cookies to ensure we're sending the session cookie
      const allCookies = document.cookie;
      console.log('Using cookies for auth:', allCookies);
      
      // Create settings object based on current form state
      const securitySettings = {
        twoFactorEnabled: is2FAEnabled,
        trackSessions,
        allowAPIAccess,
        securityAlerts
      };
      
      const response = await fetch('/api/auth/security-settings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
          'Accept': 'application/json',
          'Cookie': allCookies // Explicitly include cookies
        },
        body: JSON.stringify(securitySettings),
        credentials: 'include', // CRITICAL: Include credentials (cookies) with request
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Failed to update security settings');
      }
      
      // Success
      toasts.show('Security settings updated successfully', 'success');
      
      // Refresh page data to reflect changes
      await invalidateAll();
      
    } catch (error) {
      toasts.show(error.message || 'Failed to update security settings', 'error');
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

<div class="grid-stack-item">
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
        
        <form class="space-y-4" onsubmit={(e) => { e.preventDefault(); updatePassword(e); }}>
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
        <Button class="w-full sm:w-auto" disabled={isSavingPassword} onclick={(e) => { e.preventDefault(); updatePassword(e); }}>
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
        <Button class="w-full sm:w-auto" disabled={isSavingSettings} onclick={(e) => { e.preventDefault(); updateSecuritySettings(e); }}>
          {isSavingSettings ? 'Saving...' : 'Save Settings'}
        </Button>
      </CardFooter>
    </Card>
    </div>
  </div>
</div> 