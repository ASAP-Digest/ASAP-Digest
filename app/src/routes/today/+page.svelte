<script>
  import { Calendar, Play, Download, Share2, Clock, Bookmark } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/Icon.svelte';
  
  /**
   * @typedef {import('./$types').PageData} PageData
   */
  
  /** @type {PageData} */
  let { data } = $props();
  
  let highlights = $state(data?.highlights || []);
  
  const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
  
  /**
   * Format the read time or duration
   * @param {Object} item - Highlight item
   * @returns {string} Formatted read time or duration
   */
  function getReadTime(item) {
    return item.readTime || item.duration || `${Math.floor(Math.random() * 5) + 2} min read`;
  }
</script>

<div class="space-y-[2rem]">
  <section class="bg-[hsl(var(--primary)/0.1)] rounded-[0.5rem] p-[1.5rem]">
    <div class="flex items-center gap-[0.5rem] mb-[0.5rem] text-[0.875rem] text-[hsl(var(--muted-foreground))]">
      <Icon icon={Calendar} size={16} color="currentColor" />
      <span>{today}</span>
    </div>
    
    <h1 class="text-[1.5rem] md:text-[1.875rem] font-bold mb-[1rem]">Today's Digest</h1>
    <p class="text-[1.125rem] mb-[1.5rem]">Your curated summary of essential updates across AI, tech, and finance.</p>
    
    <div class="flex flex-wrap gap-[1rem] mb-[1.5rem]">
      <Button size="lg" class="flex items-center justify-center gap-[0.5rem]">
        <Icon icon={Play} size={18} color="currentColor" />
        <span>Listen Now</span>
      </Button>
      <Button size="lg" variant="secondary" class="flex items-center justify-center gap-[0.5rem]">
        <Icon icon={Download} size={18} color="currentColor" />
        <span>Download Audio</span>
      </Button>
      <Button size="lg" variant="secondary" class="flex items-center justify-center gap-[0.5rem]">
        <Icon icon={Share2} size={18} color="currentColor" />
        <span>Share</span>
      </Button>
    </div>
  </section>

  <section>
    <h2 class="text-[1.25rem] font-semibold mb-[1rem]">Today's Highlights</h2>
    
    <div class="space-y-[1rem]">
      {#each highlights as item, i}
        <div class="bg-white dark:bg-[hsl(var(--card))] rounded-[0.5rem] shadow-md p-[1rem] border border-[hsl(var(--border))]">
          <div class="flex justify-between items-start mb-[0.75rem]">
            <div class="text-[0.75rem] font-medium text-[hsl(var(--muted-foreground))] uppercase">
              {item.type}
            </div>
            <button class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--primary))]">
              <Icon icon={Bookmark} size={16} color="currentColor" />
            </button>
          </div>
          
          <h3 class="font-medium mb-[0.5rem]">{item.title || item.term || ''}</h3>
          <p class="text-[0.875rem] text-[hsl(var(--muted-foreground))] mb-[0.75rem]">
            {item.summary || item.definition || item.content || item.description || ''}
          </p>
          
          <div class="flex justify-between items-center text-[0.75rem] text-[hsl(var(--muted-foreground))]">
            <div class="flex items-center gap-[0.25rem]">
              <Icon icon={Clock} size={14} color="currentColor" />
              <span>{getReadTime(item)}</span>
            </div>
            <span>{item.source || ''}</span>
          </div>
        </div>
      {/each}
    </div>
  </section>
</div> 