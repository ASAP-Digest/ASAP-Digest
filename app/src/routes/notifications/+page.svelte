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

<div class="space-y-[2rem]">
  <section>
    <div class="flex justify-between items-center mb-[1.5rem]">
      <h1 class="text-[1.5rem] font-bold flex items-center gap-[0.5rem]">
        <Bell size={20} />
        <span>Notifications</span>
      </h1>
      
      <div class="flex items-center gap-[1rem]">
        <button 
          onclick={markAllAsRead}
          class="text-[0.875rem] text-[hsl(var(--primary))] hover:underline flex items-center gap-[0.25rem]"
        >
          <Check size={16} />
          <span>Mark all as read</span>
        </button>
        
        <a href="/profile" class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]">
          <Settings size={18} />
        </a>
      </div>
    </div>
    
    {#if notifications.length === 0}
      <div class="bg-white dark:bg-[hsl(var(--card))] rounded-[0.5rem] shadow-md p-[1.5rem] text-center border border-[hsl(var(--border))]">
        <Bell size={32} class="mx-auto mb-[0.75rem] text-[hsl(var(--muted-foreground))]" />
        <p class="text-[hsl(var(--muted-foreground))]">You don't have any notifications yet.</p>
      </div>
    {:else}
      <div class="space-y-[1rem]">
        {#each notifications as notification}
          {@const SvelteComponent = getIcon(notification.type)}
          <div class="bg-white dark:bg-[hsl(var(--card))] rounded-[0.5rem] shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] p-[1rem] border border-[hsl(var(--border))] {notification.read ? '' : 'border-l-4 border-l-[hsl(var(--primary))]'}">
            <div class="flex items-start gap-[0.75rem]">
              <div class="mt-[0.25rem] {notification.read ? 'text-[hsl(var(--muted-foreground))]' : 'text-[hsl(var(--primary))]'}">
                <SvelteComponent size={20} />
              </div>
              
              <div class="flex-1">
                <div class="flex justify-between items-start">
                  <div>
                    <h3 class="font-medium {notification.read ? 'text-[hsl(var(--muted-foreground))]' : 'text-[hsl(var(--foreground))]'}">{notification.title}</h3>
                    <p class="text-[0.875rem] text-[hsl(var(--muted-foreground))] mt-[0.25rem]">{notification.message}</p>
                  </div>
                  
                  {#if !notification.read}
                    <button 
                      onclick={() => markAsRead(notification.id)} 
                      class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
                    >
                      <X size={16} />
                    </button>
                  {/if}
                </div>
                
                <div class="flex justify-between items-center mt-[0.75rem]">
                  <div class="text-[0.75rem] text-[hsl(var(--muted-foreground))] flex items-center gap-[0.25rem]">
                    <Clock size={14} />
                    <span>{formatRelativeTime(notification.date)}</span>
                  </div>
                  
                  <a href="/" class="text-[hsl(var(--primary))] hover:underline text-[0.875rem] flex items-center gap-[0.25rem]">
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