<script>
  import { onMount } from 'svelte';
  import { Calendar, Clock, Plus } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  // @ts-ignore - Svelte component import
  import DigestLayoutSelector from '$lib/components/digest/DigestLayoutSelector.svelte';
  import { getUsersDigests } from '$lib/api/digest-builder-api';
  import { useSession } from '$lib/auth-client';
  import { error } from '@sveltejs/kit';
  import { goto } from '$app/navigation';
  
  /** @typedef {Object} Digest 
   * @property {string} id - Digest ID
   * @property {string} title - Digest title
   * @property {string} date - Publication date
   * @property {number} articleCount - Number of articles
   * @property {string} category - Digest category
   * @property {string} description - Digest description
   */
  
  /** @typedef {Object} UserDigest
   * @property {number} id - Digest ID
   * @property {number} user_id - User ID
   * @property {string} layout_template_id - Layout template ID
   * @property {string} status - Digest status (e.g., 'draft', 'published')
   * @property {string} created_at - Creation timestamp
   * @property {string|null} published_at - Publication timestamp
   * @property {string|null} updated_at - Last updated timestamp
   */

  /**
   * @typedef {import('@sveltejs/kit').PageLoadEvent} PageLoadEvent
   */

  /**
   * @typedef {Object} PageData
   * @property {Array<Digest>} digests - Public/trending digests (assuming this comes from somewhere else)
   * @property {Array<UserDigest>} userDigests - Digests owned by the current user
   */

  /** @type {PageData} */
  const { data } = $props();

  // Get session data using Better Auth
  const { data: session } = useSession();
  
  // Make a copy of the server data for local use
  let recentDigests = $state(data.digests.slice(0, 4));
  let trendingDigests = $state(data.digests.slice(4));

  let myDigests = $state(data.userDigests);
  
  /**
   * Format date to a more readable format
   * @param {string} dateString - ISO date string
   * @returns {string} - Formatted date
   */
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }
  
  /**
   * Navigate to digest detail page
   * @param {string} id - Digest ID
   */
  function viewDigest(id) {
    console.log(`Navigating to digest/${id}`);
    // This function seems intended for public viewing, keep as is for now.
    // window.location.href = `/digest/${id}`;
    goto(`/digest/${id}`); // Using SvelteKit goto
  }

  /**
   * Navigate to digest edit page
   * @param {number} id - Digest ID
   */
  function editDigest(id) {
      console.log(`Navigating to digest/${id}/edit`);
      goto(`/digest/${id}/edit`);
  }

</script>

<div class="container py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold mb-2 text-[hsl(var(--foreground))]">Your Digests</h1>
    <p class="text-[hsl(var(--muted-foreground))]">
      Personalized content collections based on your interests and reading history.
    </p>
  </div>
  
  <!-- Recent Digests -->
  <section class="mb-10">
    <h2 class="text-xl font-semibold mb-4 text-[hsl(var(--foreground))]">Recent Digests</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {#each recentDigests as digest}
        <button 
          type="button"
          class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer text-left"
          onclick={() => viewDigest(digest.id)}
          onkeydown={(e) => e.key === 'Enter' && viewDigest(digest.id)}
        >
          <div class="p-5">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium px-2 py-1 rounded-full bg-[hsl(var(--muted))] text-[hsl(var(--muted-foreground))]">
                {digest.category}
              </span>
              <span class="text-sm text-[hsl(var(--muted-foreground))] flex items-center">
                <Icon icon={Calendar} size={14} class="mr-1" color="currentColor" />
                {formatDate(digest.date)}
              </span>
            </div>
            
            <h3 class="text-lg font-semibold mb-2 text-[hsl(var(--foreground))]">{digest.title}</h3>
            <p class="text-sm text-[hsl(var(--muted-foreground))] mb-4">{digest.description}</p>
            
            <div class="flex items-center text-xs text-[hsl(var(--muted-foreground))]">
              <Icon icon={Clock} size={14} class="mr-1" color="currentColor" />
              <span>{digest.articleCount} articles</span>
            </div>
          </div>
          
          <div class="p-3 bg-[hsl(var(--muted)/0.3)] border-t border-[hsl(var(--border))] text-center">
            <span class="text-sm font-medium text-[hsl(var(--primary))]">Read Digest</span>
          </div>
        </button>
      {/each}
    </div>
  </section>
  
  <!-- Trending Digests -->
  <section>
    <h2 class="text-xl font-semibold mb-4 text-[hsl(var(--foreground))]">Trending Digests</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {#each trendingDigests as digest}
        <button 
          type="button"
          class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer text-left"
          onclick={() => viewDigest(digest.id)}
          onkeydown={(e) => e.key === 'Enter' && viewDigest(digest.id)}
        >
          <div class="p-5">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium px-2 py-1 rounded-full bg-[hsl(var(--muted))] text-[hsl(var(--muted-foreground))]">
                {digest.category}
              </span>
              <span class="text-sm text-[hsl(var(--muted-foreground))] flex items-center">
                <Icon icon={Calendar} size={14} class="mr-1" color="currentColor" />
                {formatDate(digest.date)}
              </span>
            </div>
            
            <h3 class="text-lg font-semibold mb-2 text-[hsl(var(--foreground))]">{digest.title}</h3>
            <p class="text-sm text-[hsl(var(--muted-foreground))] mb-4">{digest.description}</p>
            
            <div class="flex items-center text-xs text-[hsl(var(--muted-foreground))]">
              <Icon icon={Clock} size={14} class="mr-1" color="currentColor" />
              <span>{digest.articleCount} articles</span>
            </div>
          </div>
          
          <div class="p-3 bg-[hsl(var(--muted)/0.3)] border-t border-[hsl(var(--border))] text-center">
            <span class="text-sm font-medium text-[hsl(var(--primary))]">Read Digest</span>
          </div>
        </button>
      {/each}
    </div>
  </section>

  <div class="new-digest-section mb-8">
    <h2 class="text-xl font-semibold mb-4">Create New Digest</h2>
    <div class="flex gap-4">
      <a href="/digest/create" class="px-6 py-3 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-lg hover:bg-[hsl(var(--primary)/0.9)] transition-colors font-medium flex items-center gap-2">
        <Icon icon={Plus} size={20} />
        Create New Digest
      </a>
      <a href="/digest/test" class="px-4 py-2 bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))] rounded hover:bg-[hsl(var(--secondary)/0.9)] transition-colors">
        Test API
      </a>
    </div>
    <p class="text-sm text-[hsl(var(--muted-foreground))] mt-2">
      Start with content selection or choose from layout templates
    </p>
  </div>

  <section class="mb-10">
    <h2 class="text-xl font-semibold mb-4 text-[hsl(var(--foreground))]">My Editable Digests</h2>
    {#if myDigests && myDigests.length > 0}
        <ul class="list-none p-0">
            {#each myDigests as digest (digest.id)}
                <li class="border border-[hsl(var(--border))] rounded-lg p-4 mb-4 bg-[hsl(var(--card))]">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-[hsl(var(--foreground))]">Digest #{digest.id}</h3>
                            <p class="text-sm text-[hsl(var(--muted-foreground))]">Status: {digest.status}</p>
                            <p class="text-xs text-[hsl(var(--muted-foreground))]">Layout: {digest.layout_template_id}</p>
                        </div>
                        <div>
                            <button class="px-4 py-2 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded hover:bg-[hsl(var(--primary)/0.9)]" onclick={() => editDigest(digest.id)}>
                                Edit
                            </button>
                        </div>
                    </div>
                </li>
            {/each}
        </ul>
    {:else}
        <p class="text-[hsl(var(--muted-foreground))]">You have no editable digests yet. Create one above!</p>
    {/if}
</section>

</div>

<style>
  .digest-management-page {
    /* Page container styles */
  }

  .new-digest-section {
    /* Section for creating a new digest */
    margin-top: 40px;
  }
</style>