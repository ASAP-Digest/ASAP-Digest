<script>
  import { User, Bell, Settings, BookOpen, Share2, Moon, Sun, LogOut } from 'lucide-svelte';
  
  // Mock user data
  let user = {
    name: 'John Doe',
    email: 'john@example.com',
    avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=John',
    joined: '2024-01-15',
    preferences: {
      darkMode: false,
      notifications: true,
      emailDigest: true,
      categories: ['AI', 'Web3', 'Finance', 'Tech']
    }
  };
  
  // Tab management
  let activeTab = 'profile';
  
  function setActiveTab(tab) {
    activeTab = tab;
  }
  
  // Toggle dark mode
  function toggleDarkMode() {
    user.preferences.darkMode = !user.preferences.darkMode;
    // TODO: Implement actual dark mode toggle
  }
  
  // Toggle notification settings
  function toggleNotifications() {
    user.preferences.notifications = !user.preferences.notifications;
  }
  
  // Toggle email digest
  function toggleEmailDigest() {
    user.preferences.emailDigest = !user.preferences.emailDigest;
  }
  
  // Handle save profile
  function saveProfile() {
    console.log('Saving profile', user);
    // TODO: Implement actual save functionality
  }
</script>

<div class="max-w-4xl mx-auto">
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
    <!-- User header -->
    <div class="bg-primary/10 p-6 flex flex-col md:flex-row items-center gap-6">
      <img 
        src={user.avatar} 
        alt={user.name} 
        class="w-24 h-24 rounded-full border-4 border-white dark:border-gray-700 shadow-md"
      />
      <div class="text-center md:text-left">
        <h1 class="text-2xl font-bold">{user.name}</h1>
        <p class="text-gray-600 dark:text-gray-400">{user.email}</p>
        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
          Member since {new Date(user.joined).toLocaleDateString()}
        </p>
      </div>
    </div>
    
    <!-- Tabs -->
    <div class="flex border-b border-gray-200 dark:border-gray-700">
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'profile' ? 'text-primary border-b-2 border-primary' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        on:click={() => setActiveTab('profile')}
      >
        <User size={18} />
        <span>Profile</span>
      </button>
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'preferences' ? 'text-primary border-b-2 border-primary' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        on:click={() => setActiveTab('preferences')}
      >
        <Settings size={18} />
        <span>Preferences</span>
      </button>
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'notifications' ? 'text-primary border-b-2 border-primary' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        on:click={() => setActiveTab('notifications')}
      >
        <Bell size={18} />
        <span>Notifications</span>
      </button>
      <button 
        class="px-4 py-3 font-medium flex items-center gap-2 {activeTab === 'digests' ? 'text-primary border-b-2 border-primary' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'}"
        on:click={() => setActiveTab('digests')}
      >
        <BookOpen size={18} />
        <span>My Digests</span>
      </button>
    </div>
    
    <!-- Tab Content -->
    <div class="p-6">
      <!-- Profile Tab -->
      {#if activeTab === 'profile'}
        <form on:submit|preventDefault={saveProfile} class="space-y-6">
          <div class="space-y-2">
            <label for="name" class="text-sm font-medium">Name</label>
            <input 
              type="text" 
              id="name" 
              bind:value={user.name} 
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
              bind:value={user.avatar} 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            />
          </div>
          
          <div class="flex justify-end">
            <button 
              type="submit" 
              class="bg-primary text-white py-2 px-4 rounded-md hover:bg-primary/90 transition-colors"
            >
              Save Changes
            </button>
          </div>
        </form>
      {/if}
      
      <!-- Preferences Tab -->
      {#if activeTab === 'preferences'}
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">Dark Mode</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Enable dark theme for your application</p>
            </div>
            <button 
              on:click={toggleDarkMode}
              class="flex items-center justify-center w-10 h-6 bg-gray-200 dark:bg-gray-700 rounded-full relative {user.preferences.darkMode ? 'bg-primary' : ''}"
            >
              <span 
                class="block w-5 h-5 bg-white rounded-full shadow-md transform transition-transform {user.preferences.darkMode ? 'translate-x-4' : 'translate-x-0'}"
              ></span>
              <span class="sr-only">Toggle Dark Mode</span>
            </button>
          </div>
          
          <div class="space-y-4">
            <h3 class="font-medium">Categories</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Select your interests to personalize your digest</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              {#each ['AI', 'Tech', 'Finance', 'Web3', 'Crypto', 'Science', 'Business', 'Policy', 'Startups'] as category}
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    checked={user.preferences.categories.includes(category)}
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
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">Push Notifications</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Receive notifications when new digests are available</p>
            </div>
            <button 
              on:click={toggleNotifications}
              class="flex items-center justify-center w-10 h-6 bg-gray-200 dark:bg-gray-700 rounded-full relative {user.preferences.notifications ? 'bg-primary' : ''}"
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
              on:click={toggleEmailDigest}
              class="flex items-center justify-center w-10 h-6 bg-gray-200 dark:bg-gray-700 rounded-full relative {user.preferences.emailDigest ? 'bg-primary' : ''}"
            >
              <span 
                class="block w-5 h-5 bg-white rounded-full shadow-md transform transition-transform {user.preferences.emailDigest ? 'translate-x-4' : 'translate-x-0'}"
              ></span>
              <span class="sr-only">Toggle Email Digest</span>
            </button>
          </div>
        </div>
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
                      <Share2 size={16} />
                    </button>
                    <button class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                      <BookOpen size={16} />
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
      <LogOut size={18} />
      <span>Sign Out</span>
    </button>
  </div>
</div> 