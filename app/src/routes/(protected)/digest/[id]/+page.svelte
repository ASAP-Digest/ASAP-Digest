<script>
  import { page } from '$app/stores';
  import * as Icons from '$lib/utils/lucide-compat.js';
  import { AudioPlayer } from '$lib/components/atoms';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { Button } from '$lib/components/ui/button';
  
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
    audioUrl: '', // This would be fetched from an API
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
  
  // Fetch audio URL (would be implemented with actual API call)
  function fetchAudioUrl() {
    // Simulate API call with setTimeout
    setTimeout(() => {
      digest.audioUrl = `https://example.com/api/digests/${digestId}/audio.mp3`;
    }, 1000);
  }
  
  // Toggle audio playback
  function togglePlayback() {
    digest.isPlaying = !digest.isPlaying;
    
    // If we don't have audio URL yet, fetch it
    if (!digest.audioUrl) {
      fetchAudioUrl();
    }
  }
  
  // Toggle bookmark
  function toggleBookmark() {
    digest.isSaved = !digest.isSaved;
    // TODO: Implement actual bookmark functionality
  }
  
  // Share digest
  function shareDigest() {
    if (typeof window !== 'undefined' && navigator.share) {
      navigator.share({
        title: digest.title,
        text: digest.summary,
        url: window.location.href
      }).catch(error => {
        console.error('Error sharing:', error);
      });
    } else {
      // Fallback for browsers that don't support Web Share API
      alert('Sharing functionality will be implemented soon!');
    }
  }
  
  // Download digest as audio
  function downloadAudio() {
    if (typeof window === 'undefined') return;
    
    if (digest.audioUrl) {
      const link = document.createElement('a');
      link.href = digest.audioUrl;
      link.download = `${digest.title}.mp3`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      alert('Audio is not available yet.');
    }
  }
  
  // Navigation between digests
  function goToPreviousDigest() {
    if (typeof window === 'undefined') return;
    
    const prevId = parseInt(digestId) - 1;
    if (prevId > 0) {
      window.location.href = `/digest/${prevId}`;
    }
  }
  
  function goToNextDigest() {
    if (typeof window === 'undefined') return;
    
    const nextId = parseInt(digestId) + 1;
    window.location.href = `/digest/${nextId}`;
  }
  
  // Audio player event handlers
  function handleAudioPlay() {
    digest.isPlaying = true;
  }
  
  function handleAudioPause() {
    digest.isPlaying = false;
  }
  
  function handleAudioEnded() {
    digest.isPlaying = false;
  }
</script>

<div class="max-w-4xl mx-auto">
  <!-- Navigation and actions -->
  <div class="flex justify-between items-center mb-6">
    <a href="/" class="text-[hsl(var(--primary))] hover:underline flex items-center gap-1">
      <Icon icon={Icons.ChevronLeft} size={16} color="currentColor" />
      <span>Back to Digests</span>
    </a>
    
    <div class="flex space-x-4">
      <Button variant="outline" size="sm" on:click={toggleBookmark}>
        {#if digest.isSaved}
          <Icon icon={Icons.BookmarkCheck} size={18} class="text-[hsl(var(--primary))]" color="currentColor" />
          <span class="text-sm ml-1">Saved</span>
        {:else}
          <Icon icon={Icons.Bookmark} size={18} color="currentColor" />
          <span class="text-sm ml-1">Save</span>
        {/if}
      </Button>
      
      <Button variant="outline" size="sm" on:click={shareDigest}>
        <Icon icon={Icons.Share2} size={18} color="currentColor" />
        <span class="text-sm ml-1">Share</span>
      </Button>
    </div>
  </div>
  
  <!-- Digest header -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
    <div class="p-6">
      <h1 class="text-2xl md:text-3xl font-bold mb-3">{digest.title}</h1>
      
      <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
        <div class="flex items-center gap-1">
          <Icon icon={Icons.Calendar} size={16} color="currentColor" />
          <span>{digest.date}</span>
        </div>
        <div class="flex items-center gap-1">
          <Icon icon={Icons.Clock} size={16} color="currentColor" />
          <span>{digest.readTime}</span>
        </div>
      </div>
      
      <p class="text-gray-700 dark:text-gray-300 mb-6">
        {digest.summary}
      </p>
      
      <!-- Simple button controls for mobile users -->
      <div class="md:hidden flex flex-col sm:flex-row gap-4 mb-4">
        <Button 
          on:click={togglePlayback}
          size="lg"
          class="flex-1"
        >
          {#if digest.isPlaying}
            <Icon icon={Icons.Pause} size={18} color="currentColor" />
            <span class="ml-2">Pause</span>
          {:else}
            <Icon icon={Icons.Play} size={18} color="currentColor" />
            <span class="ml-2">Listen</span>
          {/if}
        </Button>
        
        <Button 
          on:click={downloadAudio}
          size="lg"
          class="flex-1"
        >
          <Icon icon={Icons.Download} size={18} color="currentColor" />
          <span class="sr-only">Download Audio</span>
        </Button>
      </div>
      
      <!-- Advanced audio player for larger screens -->
      <div class="hidden md:block">
        <div class="mt-6 mb-6">
          <AudioPlayer
            src={digest.audioUrl}
            bind:isPlaying={digest.isPlaying}
            variant="accent"
            size="default"
            showControls={true}
            showVolume={true}
            showTime={true}
            showSeek={true}
            showSkip={true}
            skipAmount={15}
            onpause={handleAudioPause}
            onended={handleAudioEnded}
            ontimeupdate={handleAudioTimeUpdate}
            className="mb-4"
          />
        </div>
      </div>
      
      <div class="md:flex items-center justify-end hidden">
        <Button 
          on:click={downloadAudio}
          size="lg"
        >
          <Icon icon={Icons.Download} size={18} color="currentColor" />
          <span class="sr-only">Download Audio</span>
        </Button>
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
              <div class="text-xs font-medium {item.change?.startsWith('+') ? 'text-green-600' : 'text-red-600'}">
                {item.change}
              </div>
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
                <div class="text-xs font-medium {item.change?.startsWith('+') ? 'text-green-600' : 'text-red-600'}">
                  {item.change}
                </div>
              </div>
            {/if}
          </div>
          
          {#if item.url}
            <a href={item.url} class="text-[hsl(var(--primary))] hover:underline text-sm">Read more</a>
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
      class="flex items-center gap-1 text-gray-600 dark:text-gray-300 hover:text-[hsl(var(--primary))] dark:hover:text-[hsl(var(--primary))]"
      disabled={parseInt(digestId) <= 1}
    >
      <Icon icon={Icons.ChevronLeft} size={18} color="currentColor" />
      <span>Previous Digest</span>
    </button>
    
    <button 
      onclick={goToNextDigest}
      class="flex items-center gap-1 text-gray-600 dark:text-gray-300 hover:text-[hsl(var(--primary))] dark:hover:text-[hsl(var(--primary))]"
    >
      <span>Next Digest</span>
      <Icon icon={Icons.ChevronRight} size={18} color="currentColor" />
    </button>
  </div>
</div> 