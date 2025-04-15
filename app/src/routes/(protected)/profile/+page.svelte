<script>
  import { preventDefault } from 'svelte/legacy';
  import { page } from '$app/stores';
  import { User, Settings, Bell, LogOut, BookOpen, Share2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import * as Avatar from '$lib/components/ui/avatar';
  import { Card, CardContent, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { CircleUser, Mail, Globe, Shield } from '$lib/utils/lucide-compat.js';

  /** @type {import('./$types').PageData} */
  const { data } = $props();

  /** @type {import('app').App.User | null} */
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
   * @description Handle saving the user profile data.
   * @returns {void}
   */
  function saveProfile() {
    console.log('Saving profile', user);
    // TODO: Implement actual save functionality
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
          {#if user.avatarUrl}
            <Avatar.Root class="h-full w-full">
              <Avatar.Image class="" src={user.avatarUrl} alt={user.displayName} />
              <Avatar.Fallback class="">
                <Icon icon={CircleUser} class="h-12 w-12 text-[hsl(var(--muted-foreground))]" />
              </Avatar.Fallback>
            </Avatar.Root>
          {:else}
            <div class="avatar-placeholder h-full w-full">
              <Icon icon={CircleUser} class="h-12 w-12 text-[hsl(var(--muted-foreground))]" />
            </div>
          {/if}
        </div>
        
        <div class="flex flex-col space-y-4">
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <Icon icon={CircleUser} class="h-4 w-4 text-[hsl(var(--muted-foreground))]" />
              <span class="text-sm font-medium">Name</span>
            </div>
            <p class="text-lg font-semibold">{user.displayName}</p>
          </div>
          
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <Icon icon={Mail} class="h-4 w-4 text-[hsl(var(--muted-foreground))]" />
              <span class="text-sm font-medium">Email</span>
            </div>
            <p class="text-[hsl(var(--muted-foreground))]">{user.email}</p>
          </div>
          
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <Icon icon={Shield} class="h-4 w-4 text-[hsl(var(--muted-foreground))]" />
              <span class="text-sm font-medium">Roles</span>
            </div>
            <div class="flex flex-wrap gap-2">
              {#each user.roles as role}
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
      <img 
        src={user.avatarUrl} 
        alt={user.displayName} 
        class="w-24 h-24 rounded-full border-4 border-white dark:border-gray-700 shadow-md"
      />
      <div class="text-center md:text-left">
        <h1 class="text-2xl font-bold">{user.displayName}</h1>
        <p class="text-gray-600 dark:text-gray-400">{user.email}</p>
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
          <div class="space-y-2">
            <label for="name" class="text-sm font-medium">Name</label>
            <input 
              type="text" 
              id="name" 
              bind:value={user.displayName} 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            />
          </div>
          
          <div class="space-y-2">
            <label for="email" class="text-sm font-medium">Email</label>
            <input 
              type="email" 
              id="email" 
              bind:value={user.email} 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            />
          </div>
          
          <div class="space-y-2">
            <label for="avatar" class="text-sm font-medium">Avatar URL</label>
            <input 
              type="text" 
              id="avatar" 
              bind:value={user.avatarUrl} 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            />
          </div>
          
          <div class="flex justify-end">
            <button 
              type="submit" 
              class="bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] py-2 px-4 rounded-md hover:bg-[hsl(var(--primary)/0.9)] transition-colors"
            >
              Save Changes
            </button>
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