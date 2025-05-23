<script>
  import { Calendar, Play, Download, Share2, Clock, Bookmark } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import Button from '$lib/components/ui/button/button.svelte';
  import { ButtonGroup, Link } from '$lib/components/atoms';
  
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

<!-- The outermost div with grid-stack-item is already added by a previous edit -->
<!-- Remove the inner wrapper and make sections direct grid-stack-items -->

<!-- Today's Digest Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="4">
  <div class="grid-stack-item-content">
  <section class="bg-[hsl(var(--primary)/0.1)] rounded-lg p-6">
    <div class="flex items-center gap-2 mb-2 text-[0.875rem] text-[hsl(var(--muted-foreground))]">
      <Icon icon={Calendar} size={16} />
      <span>Today's Content • {today}</span>
    </div>
    
    <h1 class="text-[var(--font-size-lg)] md:text-[var(--font-size-xl)] font-bold mb-4">Today's Digest</h1>
    <p class="text-[var(--font-size-base)] mb-6">Your curated summary of essential updates across AI, tech, and finance.</p>
    
    <div class="flex flex-wrap gap-4 mb-6">
      <Button size="lg" class="flex items-center justify-center gap-2">
        <Icon icon={Play} size={20} />
        Listen Now
      </Button>
      <Button size="lg" variant="secondary" class="flex items-center justify-center gap-2">
        <Icon icon={Download} size={20} />
        Download Audio
      </Button>
      <Button size="lg" variant="secondary" class="flex items-center justify-center gap-2">
        <Icon icon={Share2} size={20} />
        Share
      </Button>
    </div>
  </section>
  </div>
</div>

<!-- Today's Highlights Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
  <section>
    <h2 class="text-[var(--font-size-lg)] font-semibold mb-4">Today's Highlights</h2>
    
    <div class="space-y-4">
      {#each highlights as item, i}
          <div class="bg-white dark:bg-[hsl(var(--card))] rounded-lg shadow-md p-4 border border-[hsl(var(--border))] grid-stack-item-content">
          <div class="flex justify-between items-start mb-3">
            <div class="bg-[hsl(var(--muted))] text-[hsl(var(--muted-foreground))] text-[0.75rem] font-medium rounded-full px-2 py-1">
              {item.type}
            </div>
            
            <ButtonGroup variant="ghost" size="icon" tooltip="Options">
              <Icon icon={Bookmark} size={18} />
            </ButtonGroup>
          </div>
          
          <h3 class="font-medium mb-2">{item.title || item.term || ''}</h3>
          <p class="text-[0.875rem] text-[hsl(var(--muted-foreground))] mb-3">
            {item.summary || item.definition || item.content || item.description || ''}
          </p>
          
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-1">
              <Icon icon={Clock} size={14} />
              <span>{getReadTime(item)}</span>
            </div>
            
            <Link href={`/discover/${item.slug || '#'}`} variant="muted" size="sm">
              Read More
            </Link>
          </div>
        </div>
      {/each}
    </div>
  </section>
  </div>
</div> 