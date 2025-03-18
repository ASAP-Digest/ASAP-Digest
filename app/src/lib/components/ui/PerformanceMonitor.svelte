<script>
  import { onMount, onDestroy } from 'svelte';
  
  // State for the performance monitor
  let panelVisible = $state(false);
  let metrics = $state({
    fps: 0,
    memory: {
      jsHeapSizeLimit: 0,
      totalJSHeapSize: 0,
      usedJSHeapSize: 0
    },
    timing: {
      navigationStart: 0,
      loadEventEnd: 0,
      domComplete: 0,
      domInteractive: 0,
      firstContentfulPaint: 0
    },
    resourceCounts: {
      script: 0,
      css: 0,
      img: 0,
      font: 0,
      other: 0
    }
  });

  // Variables for FPS calculation
  let frames = 0;
  let prevTime = 0;
  let rafId = null;
  
  // Function to calculate FPS
  function updateFPS(timestamp) {
    frames++;
    
    if (!prevTime) {
      prevTime = timestamp;
    }
    
    const elapsed = timestamp - prevTime;
    
    if (elapsed >= 1000) {
      metrics.fps = Math.round((frames * 1000) / elapsed);
      frames = 0;
      prevTime = timestamp;
      
      // Update memory stats if available
      if (performance.memory) {
        metrics.memory = {
          jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
          totalJSHeapSize: performance.memory.totalJSHeapSize,
          usedJSHeapSize: performance.memory.usedJSHeapSize
        };
      }
    }
    
    rafId = requestAnimationFrame(updateFPS);
  }
  
  // Function to format bytes to human readable format
  function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
  }
  
  // Function to reset metrics
  function resetMetrics() {
    metrics = {
      fps: 0,
      memory: {
        jsHeapSizeLimit: 0,
        totalJSHeapSize: 0,
        usedJSHeapSize: 0
      },
      timing: {
        navigationStart: 0,
        loadEventEnd: 0,
        domComplete: 0,
        domInteractive: 0,
        firstContentfulPaint: 0
      },
      resourceCounts: {
        script: 0,
        css: 0,
        img: 0,
        font: 0,
        other: 0
      }
    };
  }

  // Initialize performance monitoring on mount
  onMount(() => {
    // Start FPS monitoring
    rafId = requestAnimationFrame(updateFPS);
    
    // Get timing metrics
    if (performance && performance.timing) {
      const timing = performance.timing;
      metrics.timing = {
        navigationStart: timing.navigationStart,
        loadEventEnd: timing.loadEventEnd,
        domComplete: timing.domComplete,
        domInteractive: timing.domInteractive
      };
      
      // Try to get First Contentful Paint from PerformanceObserver if available
      if (PerformanceObserver && PerformanceObserver.supportedEntryTypes.includes('paint')) {
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();
          entries.forEach(entry => {
            if (entry.name === 'first-contentful-paint') {
              metrics.timing.firstContentfulPaint = entry.startTime;
            }
          });
        });
        
        observer.observe({ entryTypes: ['paint'] });
      }
    }
    
    // Count resources by type
    if (performance && performance.getEntriesByType) {
      const resources = performance.getEntriesByType('resource');
      
      resources.forEach(resource => {
        const url = resource.name;
        if (url.match(/\.js($|\?)/)) {
          metrics.resourceCounts.script++;
        } else if (url.match(/\.css($|\?)/)) {
          metrics.resourceCounts.css++;
        } else if (url.match(/\.(jpg|jpeg|png|gif|webp|svg)($|\?)/)) {
          metrics.resourceCounts.img++;
        } else if (url.match(/\.(woff|woff2|ttf|otf|eot)($|\?)/)) {
          metrics.resourceCounts.font++;
        } else {
          metrics.resourceCounts.other++;
        }
      });
    }
  });
  
  // Clean up on component destruction
  onDestroy(() => {
    if (rafId) {
      cancelAnimationFrame(rafId);
    }
  });
</script>

<div class="fixed bottom-5 right-5 z-50 font-mono text-xs">
  <button 
    class="bg-gray-800 text-white border-none py-[0.5rem] px-[0.75rem] rounded-sm cursor-pointer opacity-80 hover:opacity-100 transition-opacity"
    class:bg-gray-700={panelVisible}
    onclick={() => panelVisible = !panelVisible}>
    {metrics.fps} FPS
  </button>
  
  {#if panelVisible}
    <div class="bg-gray-900/90 text-white rounded-sm p-[0.75rem] mt-[0.5rem] max-w-[400px] max-h-[400px] overflow-y-auto shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),_0_2px_4px_-1px_rgba(0,0,0,0.06)]">
      <div class="flex justify-between items-center mb-[0.75rem] pb-[0.5rem] border-b border-gray-600">
        <h3 class="m-0 text-sm font-bold">Performance Metrics</h3>
        <button 
          class="bg-gray-700 text-white border-none p-[0.25rem] px-[0.5rem] rounded-sm cursor-pointer text-[11px] hover:bg-gray-600"
          onclick={resetMetrics}>
          Reset
        </button>
      </div>
      
      <table class="w-full border-collapse">
        <thead>
          <tr>
            <th class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700 text-gray-400">Metric</th>
            <th class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700 text-gray-400">Value</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">FPS</td>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">
              <span class={metrics.fps > 30 ? 'text-green-500' : 'text-orange-500'}>
                {metrics.fps}
              </span>
            </td>
          </tr>
          
          {#if metrics.memory.usedJSHeapSize > 0}
            <tr>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">Memory Usage</td>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">
                {formatBytes(metrics.memory.usedJSHeapSize)} / {formatBytes(metrics.memory.jsHeapSizeLimit)}
              </td>
            </tr>
          {/if}
          
          {#if metrics.timing.navigationStart > 0}
            <tr>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">Page Load</td>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">
                {metrics.timing.loadEventEnd - metrics.timing.navigationStart}ms
              </td>
            </tr>
            <tr>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">DOM Ready</td>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">
                {metrics.timing.domComplete - metrics.timing.navigationStart}ms
              </td>
            </tr>
          {/if}
          
          {#if metrics.timing.firstContentfulPaint > 0}
            <tr>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">First Paint</td>
              <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">
                {Math.round(metrics.timing.firstContentfulPaint)}ms
              </td>
            </tr>
          {/if}
        </tbody>
      </table>
      
      <h4 class="mt-[1rem] mb-[0.5rem] text-sm font-medium text-gray-300">Resource Counts</h4>
      <table class="w-full border-collapse">
        <thead>
          <tr>
            <th class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700 text-gray-400">Type</th>
            <th class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700 text-gray-400">Count</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">JS Files</td>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">{metrics.resourceCounts.script}</td>
          </tr>
          <tr>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">CSS Files</td>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">{metrics.resourceCounts.css}</td>
          </tr>
          <tr>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">Images</td>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">{metrics.resourceCounts.img}</td>
          </tr>
          <tr>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">Fonts</td>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">{metrics.resourceCounts.font}</td>
          </tr>
          <tr>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">Other</td>
            <td class="py-[0.375rem] px-[0.5rem] text-left border-b border-gray-700">{metrics.resourceCounts.other}</td>
          </tr>
        </tbody>
      </table>
      
      <div class="mt-[0.75rem] pt-[0.5rem] border-t border-gray-700 text-gray-500 text-right text-xs">
        ASAP Digest Performance Monitor
      </div>
    </div>
  {/if}
</div> 