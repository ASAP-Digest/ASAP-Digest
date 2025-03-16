<script>
  import "../app.css";
  import Navigation from "$lib/components/layout/Navigation.svelte";
  import Footer from "$lib/components/layout/Footer.svelte";
  import PerformanceMonitor from "$lib/components/ui/PerformanceMonitor.svelte";
</script>

<div class="min-h-screen bg-background text-foreground flex flex-col">
  <Navigation />
  
  <main class="flex-1 container mx-auto px-4 py-4">
    <slot />
  </main>
  
  <Footer />
  
  {#if import.meta.env.DEV}
    <PerformanceMonitor />
  {/if}
</div>