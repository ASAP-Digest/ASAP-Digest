<script>
  import { onMount, onDestroy } from 'svelte';
  
  // Configuration props with defaults
  let isEnabled = $state(true);
  let enableConsoleLogging = $state(true);
  let isVisible = $state(true);
  
  // Toggle visibility
  function toggleVisibility() {
    isVisible = !isVisible;
    if (typeof window !== 'undefined') {
      localStorage.setItem('perfMonitorVisible', isVisible.toString());
    }
  }
  
  // Enable/disable monitor
  /** @param {boolean} value */
  function setEnabled(value) {
    isEnabled = value;
    if (typeof window !== 'undefined') {
      localStorage.setItem('perfMonitorEnabled', value.toString());
    }
    if (!value) {
      cleanup();
    } else if (animationFrameId === null) {
      animationFrameId = requestAnimationFrame(updateMetrics);
    }
  }
  
  // Enable/disable console logging
  /** @param {boolean} value */
  function setConsoleLogging(value) {
    enableConsoleLogging = value;
    if (typeof window !== 'undefined') {
      localStorage.setItem('perfMonitorLogging', value.toString());
    }
  }
  
  // Performance metrics
  let fps = $state(0);
  let memory = $state(0);
  let frameTime = $state(0);
  let loadTime = $state(0);
  
  // Animation frame ID for cleanup
  /** @type {number|null} */
  let animationFrameId = null;
  
  // Timing variables
  let lastFrameTime = performance.now();
  let frameCount = 0;
  let lastFpsUpdate = performance.now();
  
  // Type declaration for non-standard performance.memory
  /** @typedef {{ jsHeapSizeLimit: number, totalJSHeapSize: number, usedJSHeapSize: number }} MemoryInfo */
  
  /**
   * @typedef {Performance & { memory?: MemoryInfo }} PerformanceWithMemory
   */
  
  // Cleanup function
  function cleanup() {
    if (animationFrameId) {
      cancelAnimationFrame(animationFrameId);
      animationFrameId = null;
    }
  }
  
  // Update performance metrics
  function updateMetrics() {
    if (!isEnabled) return;
    
    const now = performance.now();
    
    // Calculate FPS
    frameCount++;
    if (now - lastFpsUpdate > 1000) {
      fps = Math.round(frameCount * 1000 / (now - lastFpsUpdate));
      frameCount = 0;
      lastFpsUpdate = now;
      
      if (enableConsoleLogging) {
        console.log(`Performance Monitor - FPS: ${fps}, Frame Time: ${frameTime}ms, Memory: ${memory}MB`);
      }
    }
    
    // Calculate frame time
    frameTime = Math.round(now - lastFrameTime);
    lastFrameTime = now;
    
    // Get memory usage if available (Chrome only)
    /** @type {PerformanceWithMemory} */
    const perf = /** @type {any} */ (window.performance);
    if (perf && perf.memory) {
      memory = Math.round(perf.memory.usedJSHeapSize / (1024 * 1024));
    }
    
    // Schedule next update if enabled
    if (isEnabled) {
      animationFrameId = requestAnimationFrame(updateMetrics);
    }
  }
  
  onMount(() => {
    // Initialize state from localStorage if available
    if (typeof window !== 'undefined') {
      isEnabled = localStorage.getItem('perfMonitorEnabled') !== 'false';
      enableConsoleLogging = localStorage.getItem('perfMonitorLogging') !== 'false';
      isVisible = localStorage.getItem('perfMonitorVisible') !== 'false';
    }
    
    if (!isEnabled) return;
    
    // Calculate page load time
    loadTime = Math.round(performance.now());
    
    // Start metrics monitoring
    animationFrameId = requestAnimationFrame(updateMetrics);
  });
  
  onDestroy(cleanup);
</script>

<style>
  .performance-monitor {
    position: fixed;
    bottom: 5rem;
    right: 1rem;
    z-index: 1000;
    background-color: hsl(var(--background)/0.8);
    color: hsl(var(--foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 0.375rem;
    padding: 0.5rem;
    font-family: monospace;
    font-size: 0.75rem;
    backdrop-filter: blur(4px);
    box-shadow: 0 2px 10px hsl(var(--foreground)/0.1);
    width: auto;
    min-width: 200px;
    max-width: 100%;
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
  
  .performance-monitor.hidden {
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
  }
  
  .performance-monitor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid hsl(var(--border)/0.5);
    padding-bottom: 0.25rem;
  }
  
  .performance-monitor-title {
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  
  .performance-monitor-toggle {
    background: none;
    border: none;
    cursor: pointer;
    color: hsl(var(--foreground));
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
    padding: 0;
  }
  
  .performance-monitor-toggle:hover {
    background-color: hsl(var(--accent)/0.1);
  }
  
  .performance-monitor-body {
    width: 100%;
  }
  
  .performance-monitor-metrics {
    width: 100%;
    border-collapse: collapse;
  }
  
  .performance-monitor-metrics td {
    padding: 0.25rem;
  }
  
  .performance-monitor-metrics td:first-child {
    font-weight: bold;
    color: hsl(var(--primary));
  }
  
  .performance-monitor-metrics td:last-child {
    text-align: right;
  }
  
  .toggle-button {
    position: fixed;
    bottom: 25rem;
    right: 1rem;
    z-index: 1000;
    background-color: hsl(var(--background)/0.8);
    color: hsl(var(--foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 50%;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px hsl(var(--foreground)/0.1);
    backdrop-filter: blur(4px);
    transition: transform 0.2s ease, background-color 0.2s ease;
  }
  
  .toggle-button:hover {
    transform: scale(1.05);
    background-color: hsl(var(--accent)/0.1);
  }
  
  .performance-monitor-controls {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid hsl(var(--border)/0.5);
  }
  
  .control-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.25rem;
    cursor: pointer;
  }
  
  .control-label input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    cursor: pointer;
  }
</style>

{#if !isVisible}
  <!-- Toggle button to show monitor -->
  <button 
    class="toggle-button" 
    onclick={toggleVisibility}
    aria-label="Show performance monitor"
  >
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6m0 0V4m0 10h6m-6 0H6"/></svg>
  </button>
{:else}
  <div class="performance-monitor">
    <div class="performance-monitor-header">
      <div class="performance-monitor-title">Performance</div>
      <button 
        class="performance-monitor-toggle" 
        onclick={toggleVisibility}
        aria-label="Hide performance monitor"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="performance-monitor-body">
      <table class="performance-monitor-metrics">
        <tbody>
          <tr>
            <td>FPS</td>
            <td>{fps}</td>
          </tr>
          <tr>
            <td>Frame Time</td>
            <td>{frameTime} ms</td>
          </tr>
          {#if memory > 0}
            <tr>
              <td>Memory</td>
              <td>{memory} MB</td>
            </tr>
          {/if}
          <tr>
            <td>Load Time</td>
            <td>{loadTime} ms</td>
          </tr>
        </tbody>
      </table>
      
      <div class="performance-monitor-controls">
        <label class="control-label">
          <input 
            type="checkbox" 
            checked={isEnabled}
            onclick={(/** @type {Event} */ e) => setEnabled(/** @type {HTMLInputElement} */ (e.target).checked)}
          />
          Enable Monitor
        </label>
        
        <label class="control-label">
          <input 
            type="checkbox" 
            checked={enableConsoleLogging}
            onclick={(/** @type {Event} */ e) => setConsoleLogging(/** @type {HTMLInputElement} */ (e.target).checked)}
          />
          Console Logging
        </label>
      </div>
    </div>
  </div>
{/if} 