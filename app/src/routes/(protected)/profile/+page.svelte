<script>
  import { preventDefault } from 'svelte/legacy';
  import { page } from '$app/stores';
  import { User, Settings, Bell, LogOut, BookOpen, Share2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import * as Avatar from '$lib/components/ui/avatar';
  import { Card, CardContent, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { CircleUser, Mail, Globe, Shield } from '$lib/utils/lucide-compat.js';
  import { getAvatarUrl, createGravatarUrl } from '$lib/stores/user.js';
  import * as RadioGroup from '$lib/components/ui/radio-group';
  import { toasts } from '$lib/stores/toast.js';
  import { invalidateAll } from '$app/navigation';
  import { getCSRFToken } from '$lib/auth-client.js';
  import { Label } from '$lib/components/ui/label';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';

  /** @type {import('./$types').PageData} */
  const { data } = $props();

  /** 
   * Local reactive copy of user data for form binding
   * @type {import('app').App.User | null} 
   */
  let userForm = $state(data.user ? {
    id: data.user.id,
    email: data.user.email,
    displayName: data.user.displayName,
    avatarUrl: data.user.avatarUrl,
    gravatarUrl: data.user.gravatarUrl || createGravatarUrl(data.user.email),
    preferences: data.user.preferences || { 
      avatarSource: 'synced' // Default to synced as primary option
    },
    roles: data.user.roles || []
  } : null);
  
  // Original user data for comparison/reference
  const user = $derived(data.user);
  
  // Tab management
  let activeTab = $state('profile');
  
  /**
   * @description Sets the currently active tab.
   * @param {string} tab - The name of the tab to activate.
   * @returns {void}
   */
  function setActiveTab(tab) {
    activeTab = tab;
  }
  
  /**
   * @description Handle saving the user profile data and avatar preferences
   * @returns {Promise<void>}
   */
  async function saveProfile() {
    if (!userForm) return;
    
    console.log('Saving profile', userForm);
    
    try {
      isSaving = true;
      
      // Get CSRF token
      const csrfToken = await getCSRFToken();
      
      // Get all existing cookies to ensure we're sending the session cookie
      const allCookies = document.cookie;
      console.log('Using cookies for auth:', allCookies);
      
      // Prepare the data to save - include all necessary fields
      const profileData = {
        id: userForm.id,
        displayName: userForm.displayName,
        email: userForm.email,
        avatarUrl: userForm.avatarUrl, // Include the current avatarUrl
        updatedAt: new Date().toISOString(),
        preferences: {
          ...(userForm.preferences || {}),
          avatar: {
            type: avatarType,
            url: avatarUrl
          }
        }
      };
      
      console.log('Sending profile data:', profileData);
      
      const response = await fetch('/api/auth/profile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
          'Cookie': allCookies
        },
        body: JSON.stringify(profileData),
        credentials: 'include'
      });
      
      // Log full response for debugging
      console.log('Profile save response status:', response.status);
      console.log('Response headers:', Object.fromEntries([...response.headers.entries()]));
      
      const result = await response.json();
      console.log('Profile save result:', result);
      
      if (!response.ok) {
        throw new Error(result.error || `Failed to save profile: ${response.status} ${response.statusText}`);
      }
      
      if (result.success) {
        toasts.show('Profile updated successfully', 'success');
        await invalidateAll();
      } else {
        throw new Error(result.error || 'Unknown error saving profile');
      }
    } catch (error) {
      console.error('Error saving profile:', error);
      toasts.show(error.message || 'Failed to save profile', 'error');
    } finally {
      isSaving = false;
    }
  }

  /**
   * Handle avatar source preference change
   * @param {Event} event - Change event from radio button
   */
  function handleAvatarPreferenceChange(event) {
    if (!userForm || !userForm.preferences) return;
    
    // Get the selected value from the radio button
    const target = /** @type {HTMLInputElement} */ (event.target);
    const newSource = /** @type {import('app').App.AvatarPreference} */ (target.value);
    
    console.log(`Changing avatar source to ${newSource}`);
    
    // Update the userForm preferences
    userForm.preferences.avatarSource = newSource;
  }

  /**
   * Get the current avatar URL based on selected preference
   * @returns {string} The URL for the user's avatar
   */
  function getCurrentAvatarUrl() {
    if (!userForm) return '/images/default-avatar.svg';
    
    // Use the getAvatarUrl function from user store
    return getAvatarUrl(userForm);
  }

  // Add image error handler
  /**
   * Handle errors loading images
   * @param {Event} event - The error event
   */
  function handleImageError(event) {
    const img = event.currentTarget;
    if (img instanceof HTMLImageElement) {
      img.src = '/images/default-avatar.svg';
    }
  }

  // Initialize avatar state from user data
  let avatarUrl = '';
  let avatarType = 'gravatar'; // default to gravatar
  let avatarLoaded = false; // Track if avatar has been loaded
  let isSaving = false; // Track form submission status
  
  // Effect to initialize avatar from user data
  $effect(() => {
    if (data && data.user && !avatarLoaded) {
      console.log('Initializing avatar from user data:', data.user);
      
      // Check user preferences
      if (data.user.preferences?.avatar?.type) {
        // Use the saved preference
        avatarType = data.user.preferences.avatar.type;
        avatarUrl = data.user.preferences.avatar.url || '';
      } else if (data.user.avatarUrl) {
        // If there's an avatar URL but no preference, assume it's a custom avatar
        avatarType = 'custom';
        avatarUrl = data.user.avatarUrl;
      } else {
        // Fall back to gravatar
        avatarType = 'gravatar';
        avatarUrl = createGravatarUrl(data.user.email);
      }
      
      console.log('Avatar initialized:', { avatarType, avatarUrl });
      avatarLoaded = true;
    }
  });
  
  // Update avatar on type change
  function handleAvatarTypeChange(type) {
    console.log('Avatar type changed:', type);
    avatarType = type;
    
    if (type === 'gravatar') {
      avatarUrl = createGravatarUrl(userForm.email);
    } else if (type === 'custom') {
      // Keep existing custom URL if there is one
      if (!avatarUrl || avatarUrl.includes('gravatar.com')) {
        avatarUrl = '';
      }
    }
    
    // Update user form with new preference
    if (userForm && userForm.preferences) {
      userForm.preferences = {
        ...userForm.preferences,
        avatar: {
          type: avatarType,
          url: avatarUrl
        }
      };
    }
    
    console.log('Updated avatar:', { avatarType, avatarUrl });
  }
</script>

<div class="container mx-auto py-8">
  <Card class="">
    <CardHeader class="">
      <CardTitle class="">Profile Information</CardTitle>
    </CardHeader>
    <CardContent class="">
      <div class="flex flex-col items-center space-y-4 md:flex-row md:space-x-6 md:space-y-0">
        <div class="relative h-24 w-24 flex-shrink-0">
          <!-- Use getAvatarUrl to ensure consistent avatar display -->
          <Avatar.Root class="h-full w-full">
            <Avatar.Image class="" src={getAvatarUrl(data.user) || '/images/default-avatar.svg'} alt={data.user?.displayName || 'User'} />
            <Avatar.Fallback class="">
              <Icon icon={CircleUser} class="h-12 w-12 text-[hsl(var(--muted-foreground))]" />
            </Avatar.Fallback>
          </Avatar.Root>
        </div>
        
        <div class="flex flex-col space-y-4">
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <Icon icon={CircleUser} class="h-4 w-4 text-[hsl(var(--muted-foreground))]" />
              <span class="text-sm font-medium">Name</span>
            </div>
            <p class="text-lg font-semibold">{data.user?.displayName || 'User'}</p>
          </div>
          
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <Icon icon={Mail} class="h-4 w-4 text-[hsl(var(--muted-foreground))]" />
              <span class="text-sm font-medium">Email</span>
            </div>
            <p class="text-[hsl(var(--muted-foreground))]">{data.user?.email || 'user@example.com'}</p>
          </div>
          
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <Icon icon={Shield} class="h-4 w-4 text-[hsl(var(--muted-foreground))]" />
              <span class="text-sm font-medium">Roles</span>
            </div>
            <div class="flex flex-wrap gap-2">
              {#each data.user?.roles || [] as role}
                <span class="rounded-full bg-[hsl(var(--muted))] px-2.5 py-0.5 text-xs font-medium text-[hsl(var(--muted-foreground))]">
                  {role}
                </span>
              {/each}
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</div>

<style>
  .avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: hsl(var(--muted));
  }
</style>

<div class="max-w-4xl mx-auto">
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
    <!-- User header -->
    <div class="bg-[hsl(var(--primary))]/10 p-6 flex flex-col md:flex-row items-center gap-6">
      <!-- Use background-image for avatar to avoid event handler issues -->
      <div 
        class="w-24 h-24 rounded-full border-4 border-white dark:border-gray-700 shadow-md bg-cover bg-center"
        style="background-image: url({getAvatarUrl(data.user) || '/images/default-avatar.svg'});"
      ></div>
      <div class="text-center md:text-left">
        <h1 class="text-2xl font-bold">{data.user?.displayName || 'User'}</h1>
        <p class="text-gray-600 dark:text-gray-400">{data.user?.email || 'user@example.com'}</p>
      </div>
    </div>
    
    <!-- Tabs -->
    <div class="flex border-b border-gray-200 dark:border-gray-700">
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'profile' ? 'text-[hsl(var(--primary))] border-b-2 border-[hsl(var(--primary))]' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        onclick={() => setActiveTab('profile')}
      >
        <Icon icon={User} class="w-[1.125rem] h-[1.125rem]" />
        <span>Profile</span>
      </button>
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'preferences' ? 'text-[hsl(var(--primary))] border-b-2 border-[hsl(var(--primary))]' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        onclick={() => setActiveTab('preferences')}
      >
        <Icon icon={Settings} class="w-[1.125rem] h-[1.125rem]" />
        <span>Preferences</span>
      </button>
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'notifications' ? 'text-[hsl(var(--primary))] border-b-2 border-[hsl(var(--primary))]' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        onclick={() => setActiveTab('notifications')}
      >
        <Icon icon={Bell} class="w-[1.125rem] h-[1.125rem]" />
        <span>Notifications</span>
      </button>
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'digests' ? 'text-[hsl(var(--primary))] border-b-2 border-[hsl(var(--primary))]' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        onclick={() => setActiveTab('digests')}
      >
        <Icon icon={BookOpen} class="w-[1.125rem] h-[1.125rem]" />
        <span>My Digests</span>
      </button>
    </div>
    
    <!-- Tab Content -->
    <div class="p-6">
      <!-- Profile Tab -->
      {#if activeTab === 'profile'}
        <form onsubmit={preventDefault(saveProfile)} class="space-y-6">
          <!-- Form title section -->
          <div class="mb-4">
            <h2 class="text-2xl font-semibold text-[hsl(var(--text-1))]">Profile Settings</h2>
            <p class="text-[hsl(var(--text-2))]">Manage your account information and preferences.</p>
          </div>

          <!-- Avatar section -->
          <div class="mb-6 p-4 bg-[hsl(var(--surface-2))] rounded-md">
            <h3 class="text-xl font-semibold mb-3 text-[hsl(var(--text-1))]">Profile Picture</h3>
            
            <div class="flex flex-col sm:flex-row gap-4 items-center">
              <!-- Avatar display -->
              <div class="w-24 h-24 relative">
                {#if avatarUrl}
                  <img src={avatarUrl} alt="User avatar" class="w-full h-full object-cover rounded-full border-2 border-[hsl(var(--brand))]" />
                {:else}
                  <div class="w-full h-full flex items-center justify-center rounded-full bg-[hsl(var(--surface-1))] border-2 border-[hsl(var(--brand))]">
                    <Icon icon={CircleUser} class="h-12 w-12 text-[hsl(var(--text-2))]" />
                  </div>
                {/if}
              </div>
              
              <!-- Avatar options -->
              <div class="flex-1">
                <RadioGroup.Root value={avatarType} onValueChange={handleAvatarTypeChange} class="flex flex-col gap-2">
                  <div class="flex items-center space-x-2">
                    <RadioGroup.Item value="gravatar" id="avatar-gravatar" />
                    <Label for="avatar-gravatar">Use Gravatar (based on your email)</Label>
                  </div>
                  
                  <div class="flex items-center space-x-2">
                    <RadioGroup.Item value="custom" id="avatar-custom" />
                    <Label for="avatar-custom">Use custom URL</Label>
                  </div>
                </RadioGroup.Root>
                
                {#if avatarType === 'custom'}
                  <div class="mt-2">
                    <Label for="avatar-url" class="mb-1 block">Avatar URL</Label>
                    <Input 
                      type="url"
                      id="avatar-url"
                      placeholder="https://example.com/your-avatar.png"
                      value={avatarUrl}
                      oninput={(e) => { avatarUrl = e.target.value; }}
                      class="w-full sm:w-96"
                    />
                    <p class="text-xs text-[hsl(var(--text-3))] mt-1">Enter a direct link to your avatar image</p>
                  </div>
                {/if}
              </div>
            </div>
          </div>

          <!-- Basic information section -->
          <div class="p-4 bg-[hsl(var(--surface-2))] rounded-md mb-6">
            <h3 class="text-xl font-semibold mb-3 text-[hsl(var(--text-1))]">Basic Information</h3>
            <div class="space-y-4">
              <!-- Display Name -->
              <div>
                <Label for="displayName" class="mb-1 block">Display Name</Label>
                <Input
                  type="text"
                  id="displayName"
                  value={userForm?.displayName || ''}
                  oninput={(e) => { if(userForm) userForm.displayName = e.target.value; }}
                  class="w-full"
                  required
                />
              </div>

              <!-- Email -->
              <div>
                <Label for="email" class="mb-1 block">Email</Label>
                <Input
                  type="email"
                  id="email"
                  value={userForm?.email || ''}
                  oninput={(e) => { if(userForm) userForm.email = e.target.value; }}
                  class="w-full"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Submit button -->
          <div class="flex justify-end">
            <Button type="submit" disabled={isSaving} class="min-w-24">
              {isSaving ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </form>
      {/if}
      
      <!-- Preferences Tab -->
      {#if activeTab === 'preferences'}
        <div class="space-y-6">
          <div class="space-y-4">
            <h3 class="font-medium">Categories</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Select your interests to personalize your digest</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              {#each ['AI', 'Tech', 'Finance', 'Web3', 'Crypto', 'Science', 'Business', 'Policy', 'Startups'] as category}
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    checked={false}
                    class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                  />
                  <span class="ml-2 text-sm">{category}</span>
                </label>
              {/each}
            </div>
          </div>
        </div>
      {/if}
      
      <!-- Notifications Tab -->
      {#if activeTab === 'notifications'}
        <!-- START COMMENT OUT: Section depends on undefined user.preferences -->
        <!--
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">Push Notifications</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Receive notifications when new digests are available</p>
            </div>
            <button 
              class="flex items-center justify-center w-10 h-6 bg-gray-200 dark:bg-gray-700 rounded-full relative {user.preferences.notifications ? 'bg-[hsl(var(--primary))]' : ''}"
            >
              <span 
                class="block w-5 h-5 bg-white rounded-full shadow-md transform transition-transform {user.preferences.notifications ? 'translate-x-4' : 'translate-x-0'}"
              ></span>
              <span class="sr-only">Toggle Notifications</span>
            </button>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">Email Digest</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Receive daily digest summaries via email</p>
            </div>
            <button 
              class="flex items-center justify-center w-10 h-6 bg-gray-200 dark:bg-gray-700 rounded-full relative {user.preferences.emailDigest ? 'bg-[hsl(var(--primary))]' : ''}"
            >
              <span 
                class="block w-5 h-5 bg-white rounded-full shadow-md transform transition-transform {user.preferences.emailDigest ? 'translate-x-4' : 'translate-x-0'}"
              ></span>
              <span class="sr-only">Toggle Email Digest</span>
            </button>
          </div>
        </div>
        -->
        <!-- END COMMENT OUT -->
        <div>Notifications settings are currently unavailable.</div> <!-- Placeholder message -->
      {/if}
      
      <!-- Digests Tab -->
      {#if activeTab === 'digests'}
        <div class="space-y-6">
          <h3 class="font-medium">My Saved Digests</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">Access your saved daily digests</p>
          
          <div class="space-y-4">
            {#each Array(5) as _, i}
              <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium">Digest - {new Date(Date.now() - i * 86400000).toLocaleDateString()}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {3 + i} articles, {1 + Math.floor(i / 2)} podcasts
                    </p>
                  </div>
                  <div class="flex space-x-2">
                    <button class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                      <Icon icon={Share2} class="w-[1.125rem] h-[1.125rem]" />
                    </button>
                    <button class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                      <Icon icon={BookOpen} class="w-[1.125rem] h-[1.125rem]" />
                    </button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        </div>
      {/if}
    </div>
  </div>
  
  <div class="mt-6 flex justify-end">
    <button class="flex items-center gap-2 text-red-600 hover:text-red-700">
      <Icon icon={LogOut} class="w-[1.125rem] h-[1.125rem]" />
      <span>Sign Out</span>
    </button>
  </div>
</div> 