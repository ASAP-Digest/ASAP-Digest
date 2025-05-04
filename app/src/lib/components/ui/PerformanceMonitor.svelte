<script>
  import { onMount, onDestroy } from 'svelte';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { Plus, X } from '$lib/utils/lucide-compat.js';
  
  // Configuration props with defaults
  let isEnabled = $state(false);
  let enableConsoleLogging = $state(false);
  let isVisible = $state(false);
  
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
      isEnabled = localStorage.getItem('perfMonitorEnabled') === 'true';
      enableConsoleLogging = localStorage.getItem('perfMonitorLogging') === 'true';
      isVisible = localStorage.getItem('perfMonitorVisible') === 'true';
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
    background-color: hsl(var(--surface-2)/0.8);
    color: hsl(var(--text-1));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius-md);
    padding: 0.5rem;
    font-family: var(--font-mono);
    font-size: var(--font-size-xs);
    backdrop-filter: blur(4px);
    box-shadow: var(--shadow-md);
    width: auto;
    min-width: 200px;
    max-width: 100%;
    transition: all var(--duration-normal) var(--ease-out);
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
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
    letter-spacing: var(--tracking-wide);
  }
  
  .performance-monitor-toggle {
    background: none;
    border: none;
    cursor: pointer;
    color: hsl(var(--text-1));
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
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
    font-weight: var(--font-weight-semibold);
    color: hsl(var(--brand));
  }
  
  .performance-monitor-metrics td:last-child {
    text-align: right;
  }
  
  .toggle-button {
    position: fixed;
    bottom: 25rem;
    right: 1rem;
    background-color: hsl(var(--surface-2)/0.8);
    color: hsl(var(--text-1));
    border: 1px solid hsl(var(--border));
    border-radius: 50%;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: var(--shadow-sm);
    backdrop-filter: blur(4px);
    transition: transform var(--duration-fast) var(--ease-out), background-color var(--duration-fast) var(--ease-out);
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
    font-size: var(--font-size-xs);
    color: hsl(var(--text-2));
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
    <Icon icon={Plus} size={16} />
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
        <Icon icon={X} size={16} />
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