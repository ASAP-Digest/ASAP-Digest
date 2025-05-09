<!--
  Account Settings
  ---------------
  Allows users to update their account information:
  - Profile details (name, email, etc.)
  - Account preferences
  - Connected accounts
  
  @file-marker account-settings-page
  @implementation-context: SvelteKit, Better Auth, Svelte Forms
-->
<script>
  import { page } from '$app/stores';
  import { invalidateAll } from '$app/navigation';
  import { Button } from '$lib/components/ui/button';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Textarea } from '$lib/components/ui/textarea';
  import { Avatar, AvatarImage, AvatarFallback } from '$lib/components/ui/avatar';
  import { AlertCircle, User, Upload } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { user as userStore } from '$lib/stores/user.js';
  
  // Get the current user
  let userValue = null;
  const unsubscribe = userStore.subscribe(value => { userValue = value; });
  
  // Form state
  let form = $state({
    displayName: '',
    email: '',
    bio: '',
    avatarUrl: ''
  });
  
  // Loading/error states
  let saving = $state(false);
  let success = $state(false);
  let error = $state('');
  
  $effect(() => {
    // Populate form with user data when available
    if (userValue) {
      form.displayName = userValue.displayName || '';
      form.email = userValue.email || '';
      form.avatarUrl = userValue.avatarUrl || '';
      // Bio might be in metadata
      form.bio = userValue.metadata?.bio || '';
    }
  });
  
  /**
   * Save account settings
   */
  async function saveSettings() {
    saving = true;
    success = false;
    error = '';
    
    try {
      // In a real implementation, this would save to API
      // For demo, simulate a network request
      await new Promise(resolve => setTimeout(resolve, 800));
      
      console.log('Saving account settings:', form);
      
      // Simulate successful save
      success = true;
      // After successful save, you might want to invalidate related data
      // await invalidateAll();
    } catch (e) {
      console.error('Error saving account settings:', e);
      error = 'Failed to save account settings. Please try again.';
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
   * Handle avatar upload
   */
  function handleAvatarUpload() {
    // In a real implementation, this would open a file picker
    // and upload the selected file to a storage service
    alert('Avatar upload functionality would be implemented here.');
  }
</script>

<div class="container max-w-4xl py-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Account Settings</h1>
    <a href=".." class="text-sm text-blue-600 hover:underline">← Back to Settings</a>
  </div>
  
  <form on:submit|preventDefault={saveSettings} class="space-y-6">
    <!-- Profile Information -->
    <Card>
      <CardHeader>
        <CardTitle>Profile Information</CardTitle>
        <CardDescription>Update your personal information</CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Avatar Upload -->
        <div class="flex items-center space-x-4">
          <Avatar class="h-20 w-20">
            {#if form.avatarUrl}
              <AvatarImage src={form.avatarUrl} alt={form.displayName || 'User'} />
            {/if}
            <AvatarFallback>{form.displayName ? form.displayName[0] : 'U'}</AvatarFallback>
          </Avatar>
          
          <div>
            <h3 class="text-sm font-medium mb-1">Profile Photo</h3>
            <Button variant="outline" type="button" on:click={handleAvatarUpload} class="text-sm">
              <Icon icon={Upload} class="h-4 w-4 mr-2" />
              Upload New Image
            </Button>
          </div>
        </div>
        
        <!-- Display Name -->
        <div class="grid w-full items-center gap-1.5">
          <Label for="displayName">Display Name</Label>
          <Input 
            id="displayName" 
            placeholder="Your name" 
            bind:value={form.displayName} 
            required 
          />
        </div>
        
        <!-- Email -->
        <div class="grid w-full items-center gap-1.5">
          <Label for="email">Email Address</Label>
          <Input 
            id="email" 
            type="email" 
            placeholder="email@example.com" 
            bind:value={form.email} 
            required 
          />
        </div>
        
        <!-- Bio -->
        <div class="grid w-full items-center gap-1.5">
          <Label for="bio">Bio</Label>
          <Textarea 
            id="bio" 
            placeholder="Tell us a bit about yourself" 
            bind:value={form.bio} 
            rows={4}
          />
          <p class="text-sm text-muted-foreground">
            This will be displayed on your public profile.
          </p>
        </div>
      </CardContent>
      <CardFooter class="flex justify-between">
        <div>
          {#if error}
            <div class="p-3 bg-red-100 text-red-600 rounded-md flex items-center">
              <Icon icon={AlertCircle} class="h-5 w-5 mr-1" />
              {error}
            </div>
          {/if}
          
          {#if success}
            <div class="p-3 bg-green-100 text-green-600 rounded-md">
              Settings saved successfully!
            </div>
          {/if}
        </div>
        
        <Button type="submit" disabled={saving}>
          {saving ? 'Saving...' : 'Save Changes'}
        </Button>
      </CardFooter>
    </Card>
  </form>
</div> 