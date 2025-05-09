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
  @implementation-context: SvelteKit, Better Auth, Svelte Forms
-->
<script>
  import { page } from '$app/stores';
  import { invalidateAll } from '$app/navigation';
  import { Switch } from '$lib/components/ui/switch';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Label } from '$lib/components/ui/label';
  import { Separator } from '$lib/components/ui/separator';
  import { AlertCircle, Shield, Smartphone, KeyRound, LogOut, History, Terminal, AlertTriangle } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { user as userStore } from '$lib/stores/user.js';
  
  // Security settings state
  let twoFactorEnabled = $state(false);
  let securityAlerts = $state(true);
  let loginNotifications = $state(true);
  let apiAccess = $state(false);
  let passwordExpiry = $state(true);
  
  // Password form state
  let currentPassword = $state('');
  let newPassword = $state('');
  let confirmPassword = $state('');
  
  // Demo session data
  const activeSessions = [
    {
      id: 'sess_1',
      device: 'Chrome on macOS',
      location: 'San Francisco, CA',
      lastActive: 'Current session',
      ip: '192.168.1.1'
    },
    {
      id: 'sess_2',
      device: 'Safari on iPhone',
      location: 'San Francisco, CA',
      lastActive: '2 hours ago',
      ip: '192.168.1.2'
    },
    {
      id: 'sess_3',
      device: 'Firefox on Windows',
      location: 'New York, NY',
      lastActive: '3 days ago',
      ip: '192.168.1.3'
    }
  ];
  
  // Demo login history
  const loginHistory = [
    {
      device: 'Chrome on macOS',
      location: 'San Francisco, CA',
      time: 'Today, 10:25 AM',
      status: 'success'
    },
    {
      device: 'Safari on iPhone',
      location: 'San Francisco, CA',
      time: 'Yesterday, 6:42 PM',
      status: 'success'
    },
    {
      device: 'Unknown Device',
      location: 'Seattle, WA',
      time: 'Apr 10, 2025, 3:15 PM',
      status: 'failed'
    }
  ];
  
  // Track form submission state
  let savingPassword = $state(false);
  let savingSecurity = $state(false);
  let success = $state('');
  let error = $state('');
  
  /**
   * Save security settings
   */
  async function saveSecuritySettings() {
    savingSecurity = true;
    success = '';
    error = '';
    
    try {
      // In a real implementation, this would save to API
      // For demo, simulate a network request
      await new Promise(resolve => setTimeout(resolve, 800));
      
      const settings = {
        twoFactorEnabled,
        securityAlerts,
        loginNotifications,
        apiAccess,
        passwordExpiry
      };
      
      console.log('Saving security settings:', settings);
      
      // Simulate successful save
      success = 'Security settings saved successfully!';
      // After successful save, you might want to invalidate related data
      // await invalidateAll();
    } catch (e) {
      console.error('Error saving security settings:', e);
      error = 'Failed to save security settings. Please try again.';
    } finally {
      savingSecurity = false;
      
      // Auto-hide success message after 3 seconds
      if (success) {
        setTimeout(() => {
          success = '';
        }, 3000);
      }
    }
  }
  
  /**
   * Update password
   */
  async function updatePassword() {
    savingPassword = true;
    success = '';
    error = '';
    
    // Validate passwords
    if (!currentPassword) {
      error = 'Current password is required';
      savingPassword = false;
      return;
    }
    
    if (!newPassword) {
      error = 'New password is required';
      savingPassword = false;
      return;
    }
    
    if (newPassword !== confirmPassword) {
      error = 'New passwords do not match';
      savingPassword = false;
      return;
    }
    
    // Check password strength (basic example)
    if (newPassword.length < 8) {
      error = 'Password must be at least 8 characters long';
      savingPassword = false;
      return;
    }
    
    try {
      // In a real implementation, this would call an API
      // For demo, simulate a network request
      await new Promise(resolve => setTimeout(resolve, 800));
      
      console.log('Updating password');
      
      // Simulate successful update
      success = 'Password updated successfully!';
      
      // Clear form
      currentPassword = '';
      newPassword = '';
      confirmPassword = '';
    } catch (e) {
      console.error('Error updating password:', e);
      error = 'Failed to update password. Please check your current password and try again.';
    } finally {
      savingPassword = false;
      
      // Auto-hide success message after 3 seconds
      if (success) {
        setTimeout(() => {
          success = '';
        }, 3000);
      }
    }
  }
  
  /**
   * Enable two-factor authentication
   */
  function setupTwoFactor() {
    // In a real implementation, this would start the 2FA setup flow
    alert('Two-factor authentication setup would be initiated here.');
  }
  
  /**
   * Logout session
   * @param {string} sessionId - ID of session to log out
   */
  function logoutSession(sessionId) {
    if (sessionId === 'sess_1') {
      alert('Cannot log out current session');
      return;
    }
    
    // In a real implementation, this would call an API
    alert(`Session ${sessionId} has been logged out`);
  }
  
  /**
   * Generate API key
   */
  function generateApiKey() {
    // In a real implementation, this would generate an API key
    alert('API key generation would be initiated here');
  }
</script>

<div class="container max-w-4xl py-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Security Settings</h1>
    <a href=".." class="text-sm text-blue-600 hover:underline">← Back to Settings</a>
  </div>
  
  {#if success}
    <div class="p-3 mb-6 bg-green-100 text-green-600 rounded-md">
      {success}
    </div>
  {/if}
  
  {#if error}
    <div class="p-3 mb-6 bg-red-100 text-red-600 rounded-md flex items-center">
      <Icon icon={AlertCircle} class="h-5 w-5 mr-1" />
      {error}
    </div>
  {/if}
  
  <div class="space-y-6">
    <!-- Password Management -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={KeyRound} class="h-5 w-5" />
          Password Management
        </CardTitle>
        <CardDescription>Update your password and security settings</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <form on:submit|preventDefault={updatePassword} class="space-y-4">
          <div class="grid gap-4">
            <div class="grid w-full items-center gap-1.5">
              <Label for="current-password">Current Password</Label>
              <Input id="current-password" type="password" bind:value={currentPassword} placeholder="Your current password" />
            </div>
            
            <div class="grid w-full items-center gap-1.5">
              <Label for="new-password">New Password</Label>
              <Input id="new-password" type="password" bind:value={newPassword} placeholder="Create a new password" />
            </div>
            
            <div class="grid w-full items-center gap-1.5">
              <Label for="confirm-password">Confirm New Password</Label>
              <Input id="confirm-password" type="password" bind:value={confirmPassword} placeholder="Confirm your new password" />
            </div>
          </div>
          
          <Button type="submit" disabled={savingPassword}>
            {savingPassword ? 'Updating...' : 'Update Password'}
          </Button>
        </form>
        
        <Separator class="my-4" />
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="password-expiry" class="text-base">Password Expiry</Label>
            <p class="text-sm text-muted-foreground">Require password change every 90 days</p>
          </div>
          <Switch id="password-expiry" bind:checked={passwordExpiry} />
        </div>
      </CardContent>
    </Card>
    
    <!-- Two-Factor Authentication -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Smartphone} class="h-5 w-5" />
          Two-Factor Authentication (2FA)
        </CardTitle>
        <CardDescription>Add an extra layer of security to your account</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="two-factor" class="text-base">Enable Two-Factor Authentication</Label>
            <p class="text-sm text-muted-foreground">Require a verification code when signing in</p>
          </div>
          <Switch id="two-factor" bind:checked={twoFactorEnabled} />
        </div>
        
        {#if twoFactorEnabled}
          <Button variant="outline" class="mt-2" on:click={setupTwoFactor}>
            Setup Two-Factor Authentication
          </Button>
        {/if}
      </CardContent>
    </Card>
    
    <!-- Session Management -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={LogOut} class="h-5 w-5" />
          Session Management
        </CardTitle>
        <CardDescription>View and manage your active sessions</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="rounded-md border">
          <div class="grid grid-cols-3 p-3 bg-muted font-medium text-sm">
            <div>Device</div>
            <div>Location</div>
            <div>Action</div>
          </div>
          
          {#each activeSessions as session}
            <div class="grid grid-cols-3 p-3 border-t items-center">
              <div>
                <div class="font-medium">{session.device}</div>
                <div class="text-xs text-muted-foreground">{session.lastActive}</div>
              </div>
              <div>
                <div>{session.location}</div>
                <div class="text-xs text-muted-foreground">IP: {session.ip}</div>
              </div>
              <div>
                <Button 
                  variant="outline" 
                  size="sm" 
                  class={session.id === 'sess_1' ? 'opacity-50 cursor-not-allowed' : ''} 
                  disabled={session.id === 'sess_1'}
                  on:click={() => logoutSession(session.id)}
                >
                  {session.id === 'sess_1' ? 'Current Session' : 'Log Out'}
                </Button>
              </div>
            </div>
          {/each}
        </div>
      </CardContent>
    </Card>
    
    <!-- Login History -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={History} class="h-5 w-5" />
          Login History
        </CardTitle>
        <CardDescription>Recent account login activity</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="rounded-md border">
          <div class="grid grid-cols-4 p-3 bg-muted font-medium text-sm">
            <div>Device</div>
            <div>Location</div>
            <div>Time</div>
            <div>Status</div>
          </div>
          
          {#each loginHistory as login}
            <div class="grid grid-cols-4 p-3 border-t items-center">
              <div>{login.device}</div>
              <div>{login.location}</div>
              <div>{login.time}</div>
              <div>
                {#if login.status === 'success'}
                  <span class="text-green-600 font-medium">Successful</span>
                {:else}
                  <span class="text-red-600 font-medium flex items-center">
                    <Icon icon={AlertTriangle} class="h-4 w-4 mr-1" />
                    Failed
                  </span>
                {/if}
              </div>
            </div>
          {/each}
        </div>
      </CardContent>
    </Card>
    
    <!-- API Access -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Terminal} class="h-5 w-5" />
          API Access
        </CardTitle>
        <CardDescription>Manage API keys for programmatic access</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="api-access" class="text-base">Enable API Access</Label>
            <p class="text-sm text-muted-foreground">Allow programmatic access to your account</p>
          </div>
          <Switch id="api-access" bind:checked={apiAccess} />
        </div>
        
        {#if apiAccess}
          <Button variant="outline" on:click={generateApiKey}>
            Generate API Key
          </Button>
        {/if}
      </CardContent>
    </Card>
    
    <!-- Security Notifications -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Shield} class="h-5 w-5" />
          Security Notifications
        </CardTitle>
        <CardDescription>Configure security alerts and notifications</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <Label for="security-alerts" class="text-base">Security Alerts</Label>
            <p class="text-sm text-muted-foreground">Receive alerts about suspicious activity</p>
          </div>
          <Switch id="security-alerts" bind:checked={securityAlerts} />
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <Label for="login-notifications" class="text-base">Login Notifications</Label>
            <p class="text-sm text-muted-foreground">Get notified about new logins to your account</p>
          </div>
          <Switch id="login-notifications" bind:checked={loginNotifications} />
        </div>
      </CardContent>
      <CardFooter>
        <Button type="button" on:click={saveSecuritySettings} disabled={savingSecurity}>
          {savingSecurity ? 'Saving...' : 'Save Security Settings'}
        </Button>
      </CardFooter>
    </Card>
  </div>
</div> 