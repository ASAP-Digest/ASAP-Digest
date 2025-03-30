<script>
  import { onMount } from 'svelte';
  
  onMount(() => {
    console.log('Dashboard page mounted');
  });
</script>

<div class="container py-8">
  <h1 class="text-3xl font-bold mb-6 text-[hsl(var(--foreground))]">Dashboard</h1>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Dashboard widgets will go here -->
    <div class="p-6 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
      <h2 class="text-xl font-semibold mb-4">Recent Activity</h2>
      <p class="text-[hsl(var(--muted-foreground))]">Your recent activity will appear here.</p>
    </div>
    
    <div class="p-6 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
      <h2 class="text-xl font-semibold mb-4">Reading Stats</h2>
      <p class="text-[hsl(var(--muted-foreground))]">Your reading statistics will appear here.</p>
    </div>
    
    <div class="p-6 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm">
      <h2 class="text-xl font-semibold mb-4">Saved Items</h2>
      <p class="text-[hsl(var(--muted-foreground))]">Your saved items will appear here.</p>
    </div>
  </div>
</div> 