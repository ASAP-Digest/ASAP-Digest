<script>
  import { page } from '$app/stores';
  import { Bell, Calendar, BookOpen, ArrowUpRight, Settings, Clock, Check, X } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
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

<div class="space-y-[2rem]">
  <section>
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-2">
        <Icon icon={Bell} class="w-5 h-5" />
        <h1 class="text-2xl font-bold">Notifications</h1>
      </div>
      <a
        href="/settings/notifications"
        class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
      >
        <Icon icon={Settings} class="w-[1.125rem] h-[1.125rem]" />
      </a>
    </div>
    
    {#if notifications.length === 0}
      <div class="text-center py-12">
        <Icon icon={Bell} class="w-8 h-8 mx-auto mb-3 text-[hsl(var(--muted-foreground))]" />
        <p class="text-[hsl(var(--muted-foreground))]">No notifications yet</p>
      </div>
    {:else}
      <div class="space-y-4">
        {#each notifications as notification}
          <div class="flex items-start gap-4 p-4 bg-[hsl(var(--card))] rounded-lg">
            <div class="flex-1">
              <p class="font-medium">{notification.title}</p>
              <p class="text-sm text-[hsl(var(--muted-foreground))]">{notification.message}</p>
              <div class="flex items-center gap-1 mt-2 text-xs text-[hsl(var(--muted-foreground))]">
                <Icon icon={Clock} class="w-[0.875rem] h-[0.875rem]" />
                <span>{formatRelativeTime(notification.date)}</span>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </section>
</div> 