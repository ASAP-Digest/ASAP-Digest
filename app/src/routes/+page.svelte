<script>
  import ArticleWidget from '$lib/components/widgets/ArticleWidget.svelte';
  import PodcastWidget from '$lib/components/widgets/PodcastWidget.svelte';
  
  // Widget data for each section with properly typed fields
  const newsWidgets = [
    { id: 'news-1', type: 'article', title: 'AI Transforms Healthcare', excerpt: 'New research shows how artificial intelligence is revolutionizing diagnostics and treatment planning.', source: 'Tech Health Journal', date: '2024-03-18', tags: ['AI', 'Healthcare', 'Research'], sourceUrl: 'https://example.com/article' },
    { id: 'news-2', type: 'podcast', title: 'AI in Healthcare', episode: 'Episode 42', duration: 28, summary: 'Discussing the latest AI breakthroughs in medical diagnostics and treatment planning.' }
  ];
  
  const financeWidgets = [
    { id: 'finance-1', type: 'article', title: 'Market Update: Crypto Trends', excerpt: 'Bitcoin reaches new heights as institutional adoption continues to grow.', source: 'Crypto Financial News', date: '2024-03-18', tags: ['Crypto', 'Bitcoin', 'Finance'], sourceUrl: 'https://example.com/crypto' },
    { id: 'finance-2', type: 'podcast', title: 'Crypto Market Analysis', episode: 'Episode 43', duration: 32, summary: 'Analyzing the latest trends in cryptocurrency markets and institutional adoption.' }
  ];
  
  const personalWidgets = [
    { id: 'personal-1', type: 'article', title: 'Personalized Topic', excerpt: 'Content based on your interests and reading history.', source: 'ASAP Digest', date: '2024-03-18', tags: ['Personalized', 'Interests'], sourceUrl: 'https://example.com/personalized' },
    { id: 'personal-2', type: 'podcast', title: 'Topics You Follow', episode: 'Daily Update', duration: 15, summary: 'Audio updates on topics you have expressed interest in.' }
  ];
</script>

<!-- Main layout container -->
<div class="container mx-auto p-[1.5rem]">
  <!-- Grid with proper Tailwind 4 responsive columns -->
  <div class="grid w-full gap-[2rem]
    grid-cols-4 
    sm:grid-cols-6 
    md:grid-cols-6 
    lg:grid-cols-12 
    xl:grid-cols-12 
    2xl:grid-cols-12"
  >
    <!-- News Section - Takes full width on mobile (4 cols), 3/6 cols on sm/md, 4/12 cols on lg+ -->
    <section class="col-span-4 sm:col-span-3 lg:col-span-4 flex flex-col gap-[1.5rem]">
      <h2 class="text-[1.5rem] font-bold text-[hsl(var(--foreground))]">Latest News</h2>
      {#each newsWidgets as widget}
        <div class="w-full cursor-move">
          {#if widget.type === 'article'}
            <ArticleWidget 
              id={widget.id || ''}
              title={widget.title || ''}
              excerpt={widget.excerpt || ''}
              source={widget.source || ''}
              date={widget.date || ''}
              tags={widget.tags || []}
              sourceUrl={widget.sourceUrl || ''}
            />
          {:else if widget.type === 'podcast'}
            <PodcastWidget
              id={widget.id || ''}
              title={widget.title || ''}
              episode={widget.episode || ''}
              duration={typeof widget.duration === 'number' ? widget.duration : 0}
              summary={widget.summary || ''}
            />
          {/if}
        </div>
      {/each}
    </section>
    
    <!-- Finance Section -->
    <section class="col-span-4 sm:col-span-3 lg:col-span-4 flex flex-col gap-[1.5rem]">
      <h2 class="text-[1.5rem] font-bold text-[hsl(var(--foreground))]">Financial Updates</h2>
      {#each financeWidgets as widget}
        <div class="w-full cursor-move">
          {#if widget.type === 'article'}
            <ArticleWidget 
              id={widget.id || ''}
              title={widget.title || ''}
              excerpt={widget.excerpt || ''}
              source={widget.source || ''}
              date={widget.date || ''}
              tags={widget.tags || []}
              sourceUrl={widget.sourceUrl || ''}
            />
          {:else if widget.type === 'podcast'}
            <PodcastWidget
              id={widget.id || ''}
              title={widget.title || ''}
              episode={widget.episode || ''}
              duration={typeof widget.duration === 'number' ? widget.duration : 0}
              summary={widget.summary || ''}
            />
          {/if}
        </div>
      {/each}
    </section>
    
    <!-- Personalized Section -->
    <section class="col-span-4 sm:col-span-3 lg:col-span-4 flex flex-col gap-[1.5rem]">
      <h2 class="text-[1.5rem] font-bold text-[hsl(var(--foreground))]">Your Interests</h2>
      {#each personalWidgets as widget}
        <div class="w-full cursor-move">
          {#if widget.type === 'article'}
            <ArticleWidget 
              id={widget.id || ''}
              title={widget.title || ''}
              excerpt={widget.excerpt || ''}
              source={widget.source || ''}
              date={widget.date || ''}
              tags={widget.tags || []}
              sourceUrl={widget.sourceUrl || ''}
            />
          {:else if widget.type === 'podcast'}
            <PodcastWidget
              id={widget.id || ''}
              title={widget.title || ''}
              episode={widget.episode || ''}
              duration={typeof widget.duration === 'number' ? widget.duration : 0}
              summary={widget.summary || ''}
            />
          {/if}
        </div>
      {/each}
    </section>
  </div>
</div>

<style>
  /* Styling for draggable elements */
  .cursor-move {
    transition: all 0.2s ease;
  }
  
  .cursor-move:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  }
  
  /* Enhance grid layout for better visualization */
  :global(.grid-layout) {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    column-gap: 1.5rem;
    row-gap: 1.5rem;
    width: 100%;
  }
  
  :global(.col-span-full) {
    grid-column: 1 / -1;
  }
</style>