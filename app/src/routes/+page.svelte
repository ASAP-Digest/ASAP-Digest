<script>
  import { Calendar, Bookmark, Share2, ArrowUpRight } from 'lucide-svelte';
</script>

<div class="space-y-8">
  <section class="bg-primary/10 rounded-lg p-6 md:p-8">
    <h1 class="text-2xl md:text-3xl font-bold mb-4">Welcome to ASAP Digest</h1>
    <p class="text-lg mb-6">Your daily curated feed of essential AI, tech, and finance updates.</p>
    <div class="flex flex-col sm:flex-row gap-4">
      <a href="/today" class="btn bg-primary text-white hover:bg-primary/90 px-4 py-2 rounded-md flex items-center gap-2">
        <Calendar size={18} />
        <span>Today's Digest</span>
      </a>
      <a href="/auth/login" class="btn bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 px-4 py-2 rounded-md flex items-center gap-2">
        <Bookmark size={18} />
        <span>Personalize Your Feed</span>
      </a>
    </div>
  </section>

  <section>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Latest Digests</h2>
      <a href="/archive" class="text-primary flex items-center gap-1 text-sm">
        <span>View All</span>
        <ArrowUpRight size={16} />
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each Array(6) as _, i}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4">
            <div class="flex justify-between items-start mb-3">
              <span class="text-sm text-gray-500 dark:text-gray-400">{new Date().toLocaleDateString()}</span>
              <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <Share2 size={16} />
              </button>
            </div>
            <h3 class="font-medium mb-2">Daily Digest #{i + 1}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
            </p>
            <a href={`/digest/${i + 1}`} class="text-primary text-sm flex items-center gap-1">
              Read More
              <ArrowUpRight size={14} />
            </a>
          </div>
        </div>
      {/each}
    </div>
  </section>
</div>

<style>
  .btn {
    transition: all 0.2s ease;
  }
</style>