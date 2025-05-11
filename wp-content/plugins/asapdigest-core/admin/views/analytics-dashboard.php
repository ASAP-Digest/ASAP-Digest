<?php
// ASAP Digest Analytics Dashboard
?>
<div class="wrap asap-analytics-dashboard">
  <h1>Analytics Dashboard</h1>
  <div id="asap-analytics-summary">
    <h2>Summary Metrics</h2>
    <div id="asap-analytics-loading">Loading analytics data...</div>
    <div id="asap-analytics-content" style="display:none;">
      <h3>Source Performance</h3>
      <table class="widefat" id="asap-source-performance-table">
        <thead><tr><th>Service</th><th>Usage</th><th>User</th><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
      <h3>AI Usage & Costs</h3>
      <table class="widefat" id="asap-ai-usage-table">
        <thead><tr><th>Service</th><th>Usage</th><th>Cost</th><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
      <!-- Placeholder for future charts -->
      <div id="asap-analytics-charts">
        <h3>Visualizations (Coming Soon)</h3>
        <div id="asap-usage-chart" style="height:300px;"></div>
        <div id="asap-cost-chart" style="height:300px;"></div>
      </div>
    </div>
  </div>
  <script>
  (function() {
    const loading = document.getElementById('asap-analytics-loading');
    const content = document.getElementById('asap-analytics-content');
    const sourceTable = document.getElementById('asap-source-performance-table').querySelector('tbody');
    const aiTable = document.getElementById('asap-ai-usage-table').querySelector('tbody');
    // Fetch usage metrics
    fetch('/wp-json/asap/v1/usage-metrics')
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data && data.data.length) {
          data.data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${row.service || ''}</td><td>${row.usage || ''}</td><td>${row.user_id || ''}</td><td>${row.timestamp || ''}</td>`;
            sourceTable.appendChild(tr);
          });
        } else {
          const tr = document.createElement('tr');
          tr.innerHTML = '<td colspan="4">No usage metrics found.</td>';
          sourceTable.appendChild(tr);
        }
        // Fetch cost analysis next
        fetch('/wp-json/asap/v1/cost-analysis')
          .then(res2 => res2.json())
          .then(costData => {
            if (costData.success && costData.data && costData.data.length) {
              costData.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${row.service || ''}</td><td>${row.usage || ''}</td><td>${row.cost || ''}</td><td>${row.timestamp || ''}</td>`;
                aiTable.appendChild(tr);
              });
            } else {
              const tr = document.createElement('tr');
              tr.innerHTML = '<td colspan="4">No cost analysis data found.</td>';
              aiTable.appendChild(tr);
            }
            loading.style.display = 'none';
            content.style.display = '';
          })
          .catch(() => {
            aiTable.innerHTML = '<tr><td colspan="4">Failed to load cost analysis data.</td></tr>';
            loading.style.display = 'none';
            content.style.display = '';
          });
      })
      .catch(() => {
        sourceTable.innerHTML = '<tr><td colspan="4">Failed to load usage metrics.</td></tr>';
        loading.style.display = 'none';
        content.style.display = '';
      });
    // TODO: Integrate Chart.js or similar for visualizations
  })();
  </script>
  <style>
    .asap-analytics-dashboard table.widefat { margin-bottom: 2em; }
    #asap-analytics-charts { margin-top: 2em; }
  </style>
</div> 