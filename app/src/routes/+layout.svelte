<script>
  import "../app.css";
</script>

<div class="min-h-screen bg-background text-foreground flex flex-col">
  <header class="bg-primary text-primary-foreground p-4 shadow-md">
    <h1 class="text-xl font-bold">ASAP Digest</h1>
  </header>
  
  <main class="flex-1 container mx-auto p-4">
    <slot />
  </main>
  
  <footer class="bg-muted text-muted-foreground p-4 text-center">
    <p class="text-sm">© 2024 ASAP Digest</p>
  </footer>
</div>