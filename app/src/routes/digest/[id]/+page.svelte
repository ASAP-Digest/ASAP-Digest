<script>
  import { page } from '$app/stores';
  import { Play, Pause, Download, Share2, Calendar, Clock, ChevronLeft, ChevronRight, Bookmark, BookmarkCheck } from '$lib/utils/lucide-icons.js';
  
  // Mock data - this would come from an API in a real application
  const digestId = $page.params.id;
  
  let digest = $state({
    id: digestId,
    date: new Date().toLocaleDateString(),
    title: `Daily Digest #${digestId}`,
    readTime: '5 min read',
    summary: 'Today\'s digest covers the latest in AI development, market trends, and technological innovations.',
    isSaved: false,
    isPlaying: false,
    content: [
      {
        type: 'article',
        title: 'OpenAI Releases New Reasoning Model',
        summary: 'OpenAI has released a new model that demonstrates enhanced reasoning capabilities across multiple domains.',
        source: 'AI News',
        url: '#'
      },
      {
        type: 'podcast',
        title: 'The Future of Decentralized Finance',
        summary: 'Experts discuss the emerging trends in DeFi and what to expect in the coming years.',
        duration: '24 min',
        url: '#'
      },
      {
        type: 'keyterm',
        term: 'Prompt Engineering',
        definition: 'The process of designing and refining prompts to effectively communicate with AI models to achieve desired outputs.'
      },
      {
        type: 'financial',
        title: 'Market Update',
        summary: 'The S&P 500 closed at a record high, driven by strong tech performance.',
        change: '+1.2%'
      },
      {
        type: 'xpost',
        author: '@techinsider',
        content: 'New research shows quantum computing making strides in error correction. This could accelerate practical applications.',
        engagement: '2.4K likes'
      },
      {
        type: 'reddit',
        subreddit: 'r/MachineLearning',
        title: 'Researchers develop new training method that cuts GPU requirements by 50%',
        upvotes: '1.2k',
        comments: '342'
      },
      {
        type: 'event',
        title: 'AI Summit 2024',
        date: 'June 15-17, 2024',
        location: 'San Francisco, CA',
        description: 'Annual conference bringing together AI researchers and industry professionals.'
      },
      {
        type: 'polymarket',
        question: 'Will AGI be achieved by 2030?',
        probability: '63%',
        volume: '$1.2M',
        change: '+5% (24h)'
      }
    ]
  });
  
  // Toggle audio playback
  function togglePlayback() {
    digest.isPlaying = !digest.isPlaying;
    // TODO: Implement actual audio playback
  }
  
  // Toggle bookmark
  function toggleBookmark() {
    digest.isSaved = !digest.isSaved;
    // TODO: Implement actual bookmark functionality
  }
  
  // Share digest
  function shareDigest() {
    // TODO: Implement sharing functionality
    alert('Sharing functionality will be implemented soon!');
  }
  
  // Download digest as audio
  function downloadAudio() {
    // TODO: Implement download functionality
    alert('Download functionality will be implemented soon!');
  }
  
  // Navigation between digests
  function goToPreviousDigest() {
    const prevId = parseInt(digestId) - 1;
    if (prevId > 0) {
      window.location.href = `/digest/${prevId}`;
    }
  }
  
  function goToNextDigest() {
    const nextId = parseInt(digestId) + 1;
    window.location.href = `/digest/${nextId}`;
  }
</script>

<div class="max-w-4xl mx-auto">
  <!-- Navigation and actions -->
  <div class="flex justify-between items-center mb-6">
    <a href="/" class="text-primary hover:underline flex items-center gap-1">
      <ChevronLeft size={16} />
      <span>Back to Digests</span>
    </a>
    
    <div class="flex space-x-4">
      <button 
        onclick={toggleBookmark}
        class="flex items-center gap-1 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
      >
        {#if digest.isSaved}
          <BookmarkCheck size={18} class="text-primary" />
          <span class="text-sm">Saved</span>
        {:else}
          <Bookmark size={18} />
          <span class="text-sm">Save</span>
        {/if}
      </button>
      
      <button 
        onclick={shareDigest}
        class="flex items-center gap-1 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
      >
        <Share2 size={18} />
        <span class="text-sm">Share</span>
      </button>
    </div>
  </div>
  
  <!-- Digest header -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
    <div class="p-6">
      <h1 class="text-2xl md:text-3xl font-bold mb-3">{digest.title}</h1>
      
      <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
        <div class="flex items-center gap-1">
          <Calendar size={16} />
          <span>{digest.date}</span>
        </div>
        <div class="flex items-center gap-1">
          <Clock size={16} />
          <span>{digest.readTime}</span>
        </div>
      </div>
      
      <p class="text-gray-700 dark:text-gray-300 mb-6">
        {digest.summary}
      </p>
      
      <div class="flex flex-col sm:flex-row gap-4">
        <button 
          onclick={togglePlayback}
          class="flex items-center justify-center gap-2 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] py-2 px-4 rounded-md hover:bg-[hsl(var(--primary)/0.9)] transition-colors"
        >
          {#if digest.isPlaying}
            <Pause size={18} />
            <span>Pause</span>
          {:else}
            <Play size={18} />
            <span>Listen</span>
          {/if}
        </button>
        
        <button 
          onclick={downloadAudio}
          class="flex items-center justify-center gap-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-2 px-4 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
        >
          <Download size={18} />
          <span>Download Audio</span>
        </button>
      </div>
    </div>
  </div>
  
  <!-- Digest content -->
  <div class="space-y-6">
    {#each digest.content.slice(0, 3) as item}
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] overflow-hidden border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-start justify-between">
          <div>
            <div class="mb-2">
              <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                {item.type}
              </span>
            </div>
            
            {#if item.type === 'article'}
              <h3 class="font-medium mb-2">{item.title}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{item.summary}</p>
              <div class="text-xs text-gray-500 dark:text-gray-500">Source: {item.source}</div>
            {:else if item.type === 'podcast'}
              <h3 class="font-medium mb-2">{item.title}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{item.summary}</p>
              <div class="text-xs text-gray-500 dark:text-gray-500">Duration: {item.duration}</div>
            {:else if item.type === 'keyterm'}
              <h3 class="font-medium mb-2">{item.term}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">{item.definition}</p>
            {:else if item.type === 'financial'}
              <h3 class="font-medium mb-2">{item.title}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">{item.summary}</p>
              <div class="text-xs font-medium {item.change.startsWith('+') ? 'text-green-600' : 'text-red-600'}">{item.change}</div>
            {:else if item.type === 'xpost'}
              <h3 class="font-medium mb-2">{item.author}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{item.content}</p>
              <div class="text-xs text-gray-500 dark:text-gray-500">{item.engagement}</div>
            {:else if item.type === 'reddit'}
              <h3 class="font-medium mb-2">{item.title}</h3>
              <div class="text-xs text-gray-500 dark:text-gray-500 flex space-x-3">
                <span>{item.subreddit}</span>
                <span>⬆️ {item.upvotes}</span>
                <span>💬 {item.comments}</span>
              </div>
            {:else if item.type === 'event'}
              <h3 class="font-medium mb-2">{item.title}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{item.description}</p>
              <div class="text-xs text-gray-500 dark:text-gray-500">
                {item.date} • {item.location}
              </div>
            {:else if item.type === 'polymarket'}
              <h3 class="font-medium mb-2">{item.question}</h3>
              <div class="flex items-center space-x-4 text-sm">
                <div>
                  <span class="font-bold">{item.probability}</span>
                </div>
                <div>
                  <span class="text-xs text-gray-500">Volume: {item.volume}</span>
                </div>
                <div class="text-xs font-medium {item.change.startsWith('+') ? 'text-green-600' : 'text-red-600'}">
                  {item.change}
                </div>
              </div>
            {/if}
          </div>
          
          {#if item.url}
            <a href={item.url} class="text-primary hover:underline text-sm">Read more</a>
          {/if}
        </div>
      </div>
    {/each}
    
    {#if digest.content.length > 3}
      <div class="py-4 text-center">
        <button 
          class="px-4 py-2 bg-[hsl(var(--primary)/0.1)] text-[hsl(var(--primary))] rounded-md hover:bg-[hsl(var(--primary)/0.2)] transition-colors"
          onclick={() => {
            digest.content = [...digest.content]; // Trigger reactivity
          }}
        >
          Load More ({digest.content.length - 3} items)
        </button>
      </div>
    {/if}
  </div>
  
  <!-- Pagination -->
  <div class="mt-8 flex justify-between">
    <button 
      onclick={goToPreviousDigest}
      class="flex items-center gap-1 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
      disabled={parseInt(digestId) <= 1}
    >
      <ChevronLeft size={18} />
      <span>Previous Digest</span>
    </button>
    
    <button 
      onclick={goToNextDigest}
      class="flex items-center gap-1 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
    >
      <span>Next Digest</span>
      <ChevronRight size={18} />
    </button>
  </div>
</div> 