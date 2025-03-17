<script>
  import { onMount, onDestroy } from 'svelte';
  import { getPerformanceMetrics, clearPerformanceMetrics, THRESHOLDS } from '$lib/utils/performance';
  
  let metrics = $state({});
  let isOpen = $state(false);
  let interval;
  
  // Update metrics periodically
  onMount(() => {
    updateMetrics();
    interval = setInterval(updateMetrics, 2000);
    
    return () => {
      clearInterval(interval);
    };
  });
  
  onDestroy(() => {
    if (interval) clearInterval(interval);
  });
  
  function updateMetrics() {
    metrics = getPerformanceMetrics();
  }
  
  function resetMetrics() {
    clearPerformanceMetrics();
    updateMetrics();
  }
  
  function getStatusColor(name, value) {
    const threshold = THRESHOLDS[name] || 1000;
    return value <= threshold ? 'text-green-500' : 'text-orange-500';
  }
  
  function formatValue(name, value) {
    if (name === 'CLS') {
      return value.toFixed(3);
    }
    return `${value.toFixed(1)}ms`;
  }
  
  function togglePanel() {
    isOpen = !isOpen;
  }
</script>

<div class="performance-monitor">
  <button 
    class="toggle-button" 
    onclick={togglePanel}
    class:open={isOpen}
  >
    {isOpen ? 'Hide' : 'Show'} Performance
  </button>
  
  {#if isOpen}
    <div class="metrics-panel">
      <div class="panel-header">
        <h3>Performance Metrics</h3>
        <button class="reset-button" onclick={resetMetrics}>Reset</button>
      </div>
      
      {#if Object.keys(metrics).length === 0}
        <p class="no-metrics">No metrics collected yet...</p>
      {:else}
        <table>
          <thead>
            <tr>
              <th>Metric</th>
              <th>Value</th>
              <th>Threshold</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {#each Object.entries(metrics) as [name, data]}
              <tr>
                <td>{name}</td>
                <td class={getStatusColor(name, data.value)}>
                  {formatValue(name, data.value)}
                </td>
                <td>
                  {name === 'CLS' 
                    ? THRESHOLDS[name]?.toFixed(3) || 'N/A'
                    : `${THRESHOLDS[name] || 'N/A'}ms`}
                </td>
                <td class={data.status === 'good' ? 'text-green-500' : 'text-orange-500'}>
                  {data.status}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      {/if}
      
      <div class="panel-footer">
        <small>Last updated: {new Date().toLocaleTimeString()}</small>
      </div>
    </div>
  {/if}
</div>

<style>
  .performance-monitor {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: monospace;
    font-size: 12px;
  }
  
  .toggle-button {
    background-color: #333;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
  }
  
  .toggle-button:hover {
    opacity: 1;
  }
  
  .toggle-button.open {
    background-color: #555;
  }
  
  .metrics-panel {
    background-color: rgba(0, 0, 0, 0.85);
    color: white;
    border-radius: 4px;
    padding: 12px;
    margin-top: 8px;
    max-width: 400px;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }
  
  .panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #555;
  }
  
  .panel-header h3 {
    margin: 0;
    font-size: 14px;
  }
  
  .reset-button {
    background-color: #555;
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 11px;
  }
  
  .reset-button:hover {
    background-color: #777;
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
  }
  
  th, td {
    padding: 6px 8px;
    text-align: left;
    border-bottom: 1px solid #444;
  }
  
  th {
    font-weight: bold;
    color: #aaa;
  }
  
  .text-green-500 {
    color: #10b981;
  }
  
  .text-orange-500 {
    color: #f97316;
  }
  
  .no-metrics {
    color: #aaa;
    font-style: italic;
    text-align: center;
    padding: 20px 0;
  }
  
  .panel-footer {
    margin-top: 12px;
    padding-top: 8px;
    border-top: 1px solid #444;
    color: #777;
    text-align: right;
  }
</style> 