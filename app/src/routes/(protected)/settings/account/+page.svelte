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

  /**
   * @typedef {Object} PageData
   * @property {Object} user - User data
   * @property {boolean} [usingLocalAuth] - Whether using cached local auth
   */
  
  /** @type {PageData} */
  let { data } = $props();
  
  // Create reactive derived state for user data to ensure updates during navigation
  let user = $derived(data.user);
  let displayName = $state(user?.displayName || '');
  let email = $state(user?.email || '');
  
  // Update form values when user data changes
  $effect(() => {
    if (user) {
      displayName = user.displayName || '';
      email = user.email || '';
    }
  });
  
  let isSaving = $state(false);
  let errorMessage = $state('');
  let successMessage = $state('');
  
  /**
   * Save profile information
   */
  async function saveProfile() {
    isSaving = true;
    errorMessage = '';
    successMessage = '';
    
    try {
      const response = await fetch('/api/user/profile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          displayName,
          email
        })
      });
      
      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Failed to update profile');
      }
      
      // Update local user store
      $userStore = {
        ...$userStore,
        displayName,
        email
      };
      
      // Invalidate page data to refresh
      await invalidateAll();
      
      successMessage = 'Profile updated successfully!';
    } catch (error) {
      errorMessage = error.message || 'An error occurred';
      console.error('Failed to update profile:', error);
    } finally {
      isSaving = false;
    }
  }
</script>

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
        <form class="space-y-4" on:submit|preventDefault={saveProfile}>
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
          {#if user && user.subscription}
            <p>Current plan: <strong>{user.subscription.name}</strong></p>
            <p>Status: <span class="text-green-600">Active</span></p>
            <p>Next billing date: {user.subscription.nextBillingDate || 'N/A'}</p>
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