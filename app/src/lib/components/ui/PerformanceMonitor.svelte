<script>
  import { onMount } from 'svelte';
  
  let fps = 0;
  let lastLoop = performance.now();
  let isVisible = false;
  let frameCount = 0;
  let lastFpsUpdate = 0;
  
  function toggleVisibility() {
    isVisible = !isVisible;
  }
  
  function calculateFPS(now) {
    frameCount++;
    
    // Update FPS once per second
    if (now - lastFpsUpdate >= 1000) {
      fps = Math.round((frameCount * 1000) / (now - lastFpsUpdate));
      frameCount = 0;
      lastFpsUpdate = now;
    }
    
    lastLoop = now;
    
    if (isVisible) {
      window.requestAnimationFrame(calculateFPS);
    }
  }
  
  function startMonitoring() {
    if (isVisible) {
      lastFpsUpdate = performance.now();
      frameCount = 0;
      window.requestAnimationFrame(calculateFPS);
    }
  }
  
  $: if (isVisible) {
    startMonitoring();
  }
  
  onMount(() => {
    return () => {
      isVisible = false;
    };
  });
</script>

<div class="fixed bottom-16 right-2 z-50">
  <button 
    on:click={toggleVisibility}
    class="bg-gray-800 text-white text-xs p-1 rounded-md opacity-60 hover:opacity-100"
  >
    perf
  </button>
  
  {#if isVisible}
    <div class="bg-gray-800 text-white text-xs p-2 rounded-md mt-1 opacity-80">
      <div>FPS: {fps}</div>
    </div>
  {/if}
</div> 