<script>
  import { Calendar, Play, Download, Share2, Clock, Bookmark } from '$lib/utils/lucide-icons.js';
  
  const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
</script>

<div class="space-y-[2rem]">
  <section class="bg-[hsl(var(--primary)/0.1)] rounded-[0.5rem] p-[1.5rem]">
    <div class="flex items-center gap-[0.5rem] mb-[0.5rem] text-[0.875rem] text-[hsl(var(--muted-foreground))]">
      <Calendar size={16} />
      <span>{today}</span>
    </div>
    
    <h1 class="text-[1.5rem] md:text-[1.875rem] font-bold mb-[1rem]">Today's Digest</h1>
    <p class="text-[1.125rem] mb-[1.5rem]">Your curated summary of essential updates across AI, tech, and finance.</p>
    
    <div class="flex flex-col sm:flex-row gap-[1rem]">
      <button class="bg-[hsl(var(--primary))] text-white hover:bg-[hsl(var(--primary)/0.9)] px-[1rem] py-[0.5rem] rounded-[0.375rem] flex items-center justify-center gap-[0.5rem]">
        <Play size={18} />
        <span>Listen Now</span>
      </button>
      <button class="bg-[hsl(var(--muted))] hover:bg-[hsl(var(--muted)/0.8)] px-[1rem] py-[0.5rem] rounded-[0.375rem] flex items-center justify-center gap-[0.5rem]">
        <Download size={18} />
        <span>Download Audio</span>
      </button>
      <button class="bg-[hsl(var(--muted))] hover:bg-[hsl(var(--muted)/0.8)] px-[1rem] py-[0.5rem] rounded-[0.375rem] flex items-center justify-center gap-[0.5rem]">
        <Share2 size={18} />
        <span>Share</span>
      </button>
    </div>
  </section>

  <section>
    <h2 class="text-[1.25rem] font-semibold mb-[1rem]">Today's Highlights</h2>
    
    <div class="space-y-[1rem]">
      {#each ['Article', 'Podcast', 'Key Term', 'Financial Bite', 'X Post', 'Reddit Buzz', 'Event', 'Polymarket'] as type, i}
        <div class="bg-white dark:bg-[hsl(var(--card))] rounded-[0.5rem] shadow-md p-[1rem] border border-[hsl(var(--border))]">
          <div class="flex justify-between items-start mb-[0.75rem]">
            <div class="text-[0.75rem] font-medium text-[hsl(var(--muted-foreground))] uppercase">
              {type}
            </div>
            <button class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--primary))]">
              <Bookmark size={16} />
            </button>
          </div>
          
          <h3 class="font-medium mb-[0.5rem]">Placeholder {type} #{i + 1}</h3>
          <p class="text-[0.875rem] text-[hsl(var(--muted-foreground))] mb-[0.75rem]">
            This is a placeholder for the {type} content that would appear in today's digest.
            {#if i % 3 === 0}
              It includes additional details that make it longer than the others.
            {/if}
          </p>
          
          <div class="flex justify-between items-center text-[0.75rem] text-[hsl(var(--muted-foreground))]">
            <div class="flex items-center gap-[0.25rem]">
              <Clock size={14} />
              <span>{2 + i} min read</span>
            </div>
            <span>Source Name</span>
          </div>
        </div>
      {/each}
    </div>
  </section>
</div> 