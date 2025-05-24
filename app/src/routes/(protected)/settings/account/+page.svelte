<!--
  Account Settings
  ---------------
  Allows users to manage their account settings including:
  - Profile information
  - Email settings
  - Subscription management
  - Account deletion
  
  @file-marker account-settings-page
  @implementation-context: SvelteKit, Better Auth, Local First
-->
<script>
  import { page } from '$app/stores';
  import { goto, invalidateAll } from '$app/navigation';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { user as userStore } from '$lib/utils/auth-persistence';
  import { getCSRFToken } from '$lib/auth-client.js';
  import { getUserData } from '$lib/stores/user.js';

  /**
   * @typedef {Object} PageData
   * @property {Object|null} user - User data (may be null during SSR or loading)
   * @property {boolean} [usingLocalAuth] - Whether using cached local auth
   */
  
  /** @type {PageData} */
  let { data } = $props();
  
  // Get user data helper for cleaner access
  const userData = $derived(getUserData(data.user));
  
  // Initialize form fields with user data
  let displayName = $state(userData.displayName || '');
  let email = $state(userData.email || '');
  
  let isSaving = $state(false);
  let errorMessage = $state('');
  let successMessage = $state('');
  
  /**
   * @description Save profile changes
   */
  async function saveProfile() {
    if (!formData.displayName.trim()) {
      toasts.show('Display name cannot be empty', 'error');
      return;
    }
    
    isSaving = true;
    
    try {
      // Prepare the update data
      const updateData = {
        id: userData.id,
        displayName: formData.displayName,
        email: formData.email,
        preferences: formData.preferences || {},
        updatedAt: new Date().toISOString()
      };
      
      // Get CSRF token
      const csrfToken = await getCSRFToken();
      
      // Get all existing cookies to ensure we're sending the session cookie
      const allCookies = document.cookie;
      console.log('Using cookies for auth:', allCookies);
      
      // Make API call to save profile
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
        throw new Error(errorData.error || 'Failed to update profile');
      }
      
      // Success
      toasts.show('Profile updated successfully', 'success');
      
      // Refresh page data
      await invalidateAll();
      
    } catch (error) {
      toasts.show(error.message || 'Failed to update profile', 'error');
      console.error('Error saving profile:', error);
    } finally {
      isSaving = false;
    }
  }

  // Form submission
  async function handleSubmit() {
    try {
      const response = await fetch('/api/user/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: userData.id,
          displayName,
          email
        })
      });

      if (response.ok) {
        toasts.show('Account settings updated successfully', 'success');
      } else {
        throw new Error('Failed to update account settings');
      }
    } catch (error) {
      toasts.show('Failed to update account settings', 'error');
    }
  }
</script>

<div class="grid-stack-item">
<div>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Account Settings</h1>
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
        <CardTitle>Profile Information</CardTitle>
        <CardDescription>Update your account details</CardDescription>
      </CardHeader>
      <CardContent>
        <form class="space-y-4" onsubmit={(e) => { e.preventDefault(); saveProfile(); }}>
          <div class="space-y-2">
            <Label for="displayName">Name</Label>
            <Input id="displayName" bind:value={displayName} required />
          </div>
          
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input id="email" type="email" bind:value={email} required />
          </div>
        </form>
      </CardContent>
      <CardFooter>
        <Button class="w-full sm:w-auto" disabled={isSaving} onclick={saveProfile}>
          {isSaving ? 'Saving...' : 'Save Changes'}
        </Button>
      </CardFooter>
    </Card>
    
    <Card>
      <CardHeader>
        <CardTitle>Subscription</CardTitle>
        <CardDescription>Manage your subscription details</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="text-sm">
          {#if userData.subscription}
            <p>Current plan: <strong>{userData.subscription.name}</strong></p>
            <p>Status: {userData.subscription.status}</p>
            <p>Next billing date: {userData.subscription.nextBillingDate || 'N/A'}</p>
          {:else}
            <p>You do not have an active subscription.</p>
          {/if}
        </div>
      </CardContent>
      <CardFooter>
        <Button variant="outline" class="w-full sm:w-auto" onclick={() => goto('/plans')}>
          Manage Subscription
        </Button>
      </CardFooter>
    </Card>
    
    <Card>
      <CardHeader>
        <CardTitle>Delete Account</CardTitle>
        <CardDescription>Permanently delete your account and all data</CardDescription>
      </CardHeader>
      <CardContent>
        <p class="text-sm text-red-600">
          This action is irreversible. All your data will be permanently deleted.
        </p>
      </CardContent>
      <CardFooter>
        <Button variant="destructive" class="w-full sm:w-auto">
          Delete Account
        </Button>
      </CardFooter>
    </Card>
    </div>
  </div>
</div> 