<script>
  import { Bell, Calendar, BookOpen, ArrowUpRight, Settings, Clock, Check, X } from 'lucide-svelte';
  
  // Mock notification data
  let notifications = $state([
    { 
      id: 1, 
      type: 'digest', 
      title: 'Today\'s Digest is Ready',
      message: 'Your personalized digest for September 24, 2024 is now available.',
      date: new Date(),
      read: false
    },
    { 
      id: 2, 
      type: 'article', 
      title: 'New Article in Your Feed',
      message: 'A new article about "Advanced AI Techniques" has been added to your feed.',
      date: new Date(Date.now() - 3600000), // 1 hour ago
      read: false
    },
    { 
      id: 3, 
      type: 'podcast', 
      title: 'Daily Podcast Released',
      message: 'Listen to today\'s AI and tech news podcast, now available.',
      date: new Date(Date.now() - 86400000), // 1 day ago
      read: true
    },
    { 
      id: 4, 
      type: 'system', 
      title: 'Welcome to ASAP Digest',
      message: 'Thank you for joining ASAP Digest! Customize your preferences to get started.',
      date: new Date(Date.now() - 172800000), // 2 days ago
      read: true
    }
  ]);
  
  // Format relative time
  function formatRelativeTime(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffDay > 0) {
      return diffDay === 1 ? 'Yesterday' : `${diffDay} days ago`;
    } else if (diffHour > 0) {
      return `${diffHour}h ago`;
    } else if (diffMin > 0) {
      return `${diffMin}m ago`;
    } else {
      return 'Just now';
    }
  }
  
  // Mark notification as read
  function markAsRead(id) {
    notifications = notifications.map(notification => {
      if (notification.id === id) {
        return { ...notification, read: true };
      }
      return notification;
    });
  }
  
  // Mark all as read
  function markAllAsRead() {
    notifications = notifications.map(notification => {
      return { ...notification, read: true };
    });
  }
  
  // Get icon for notification type
  function getIcon(type) {
    switch (type) {
      case 'digest':
        return Calendar;
      case 'article':
      case 'podcast':
        return BookOpen;
      default:
        return Bell;
    }
  }
</script>

<div class="space-y-8">
  <section>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold flex items-center gap-2">
        <Bell size={20} />
        <span>Notifications</span>
      </h1>
      
      <div class="flex items-center gap-4">
        <button 
          onclick={markAllAsRead}
          class="text-sm text-primary hover:underline flex items-center gap-1"
        >
          <Check size={16} />
          <span>Mark all as read</span>
        </button>
        
        <a href="/profile" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
          <Settings size={18} />
        </a>
      </div>
    </div>
    
    {#if notifications.length === 0}
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center border border-gray-200 dark:border-gray-700">
        <Bell size={32} class="mx-auto mb-3 text-gray-400" />
        <p class="text-gray-600 dark:text-gray-400">You don't have any notifications yet.</p>
      </div>
    {:else}
      <div class="space-y-4">
        {#each notifications as notification}
          {@const SvelteComponent = getIcon(notification.type)}
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700 {notification.read ? '' : 'border-l-4 border-l-primary'}">
            <div class="flex items-start gap-3">
              <div class="mt-1 {notification.read ? 'text-gray-500' : 'text-primary'}">
                <SvelteComponent size={20} />
              </div>
              
              <div class="flex-1">
                <div class="flex justify-between items-start">
                  <div>
                    <h3 class="font-medium {notification.read ? 'text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-gray-100'}">{notification.title}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{notification.message}</p>
                  </div>
                  
                  {#if !notification.read}
                    <button 
                      onclick={() => markAsRead(notification.id)} 
                      class="text-gray-400 hover:text-gray-600"
                    >
                      <X size={16} />
                    </button>
                  {/if}
                </div>
                
                <div class="flex justify-between items-center mt-3">
                  <div class="text-xs text-gray-500 dark:text-gray-500 flex items-center gap-1">
                    <Clock size={14} />
                    <span>{formatRelativeTime(notification.date)}</span>
                  </div>
                  
                  <a href="/" class="text-primary hover:underline text-sm flex items-center gap-1">
                    <span>View</span>
                    <ArrowUpRight size={14} />
                  </a>
                </div>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </section>
</div> 