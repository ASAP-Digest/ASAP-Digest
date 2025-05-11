<?php
// ASAP Digest Analytics Dashboard
?>
<div class="wrap asap-analytics-dashboard">
  <h1>Analytics Dashboard</h1>
  <div id="asap-analytics-summary">
    <h2>Summary Metrics</h2>
    <div id="asap-analytics-loading">Loading analytics data...</div>
    <div id="asap-analytics-content" style="display:none;">
      <div id="asap-analytics-charts" class="asap-analytics-charts-area">
        <div id="asap-no-data-visual" style="display:none; text-align:center; margin:1em 0; min-height:120px;">
          <svg width="100" height="80" viewBox="0 0 100 80" aria-hidden="true" focusable="false" style="display:block;margin:0 auto;">
            <!-- Simple bar chart icon -->
            <rect x="15" y="50" width="10" height="20" rx="2" fill="#e0e0e0"/>
            <rect x="35" y="40" width="10" height="30" rx="2" fill="#ccd0d4"/>
            <rect x="55" y="30" width="10" height="40" rx="2" fill="#bfc9d1"/>
            <rect x="75" y="60" width="10" height="10" rx="2" fill="#e0e0e0"/>
            <line x1="10" y1="70" x2="90" y2="70" stroke="#bbb" stroke-width="2"/>
          </svg>
          <div style="color:#999; font-size:14px; margin-top:0.5em;">No analytics data yet<br><span style="font-size:12px;">Data will appear here as your site is used.</span></div>
        </div>
        <canvas id="asap-usage-chart" style="height:150px;"></canvas>
        <canvas id="asap-cost-chart" style="height:150px;"></canvas>
      </div>
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
    </div>
  </div>
  <script>
  (function() {
    const loading = document.getElementById('asap-analytics-loading');
    const content = document.getElementById('asap-analytics-content');
    const sourceTable = document.getElementById('asap-source-performance-table').querySelector('tbody');
    const aiTable = document.getElementById('asap-ai-usage-table').querySelector('tbody');
    const usageChartEl = document.getElementById('asap-usage-chart');
    const costChartEl = document.getElementById('asap-cost-chart');
    const noDataVisual = document.getElementById('asap-no-data-visual');
    let usageData = [];
    let costData = [];

    // Helper to render a default empty chart
    function renderEmptyChart(ctx, label) {
      if (!window.Chart) return;
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['No data'],
          datasets: [{
            label: label,
            data: [0],
            backgroundColor: '#e0e0e0',
          }]
        },
        options: {
          plugins: {
            legend: { display: false },
            title: { display: true, text: 'No data available' }
          },
          scales: {
            x: { display: false },
            y: { display: false }
          }
        }
      });
    }

    // Show/hide the no-data visual
    function updateNoDataVisual() {
      if (usageData.length === 0 && costData.length === 0) {
        noDataVisual.style.display = '';
      } else {
        noDataVisual.style.display = 'none';
      }
    }

    // Fetch usage metrics
    fetch('/wp-json/asap/v1/usage-metrics')
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data && data.data.length) {
          usageData = data.data;
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
          .then(costResp => {
            if (costResp.success && costResp.data && costResp.data.length) {
              costData = costResp.data;
              costResp.data.forEach(row => {
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

            // Render charts after both data sets are loaded
            setTimeout(function() {
              if (window.Chart) {
                // Usage Chart
                if (usageData.length) {
                  const usageLabels = usageData.map(row => row.service + ' (' + (row.user_id || '') + ')');
                  const usageValues = usageData.map(row => Number(row.usage) || 0);
                  new Chart(usageChartEl, {
                    type: 'bar',
                    data: {
                      labels: usageLabels,
                      datasets: [{
                        label: 'Usage',
                        data: usageValues,
                        backgroundColor: '#2271b1',
                      }]
                    },
                    options: {
                      plugins: {
                        title: { display: true, text: 'Source Usage' }
                      },
                      responsive: true,
                      scales: {
                        x: { title: { display: true, text: 'Service (User)' } },
                        y: { title: { display: true, text: 'Usage' }, beginAtZero: true }
                      }
                    }
                  });
                } else {
                  renderEmptyChart(usageChartEl.getContext('2d'), 'Usage');
                }
                // Cost Chart
                if (costData.length) {
                  const costLabels = costData.map(row => row.service);
                  const costValues = costData.map(row => Number(row.cost) || 0);
                  new Chart(costChartEl, {
                    type: 'bar',
                    data: {
                      labels: costLabels,
                      datasets: [{
                        label: 'Cost',
                        data: costValues,
                        backgroundColor: '#d63638',
                      }]
                    },
                    options: {
                      plugins: {
                        title: { display: true, text: 'AI Usage Costs' }
                      },
                      responsive: true,
                      scales: {
                        x: { title: { display: true, text: 'Service' } },
                        y: { title: { display: true, text: 'Cost ($)' }, beginAtZero: true }
                      }
                    }
                  });
                } else {
                  renderEmptyChart(costChartEl.getContext('2d'), 'Cost');
                }
              }
              updateNoDataVisual();
            }, 100);
          })
          .catch(() => {
            aiTable.innerHTML = '<tr><td colspan="4">Failed to load cost analysis data.</td></tr>';
            loading.style.display = 'none';
            content.style.display = '';
            if (window.Chart) {
              renderEmptyChart(usageChartEl.getContext('2d'), 'Usage');
              renderEmptyChart(costChartEl.getContext('2d'), 'Cost');
            }
            updateNoDataVisual();
          });
      })
      .catch(() => {
        sourceTable.innerHTML = '<tr><td colspan="4">Failed to load usage metrics.</td></tr>';
        loading.style.display = 'none';
        content.style.display = '';
        if (window.Chart) {
          renderEmptyChart(usageChartEl.getContext('2d'), 'Usage');
          renderEmptyChart(costChartEl.getContext('2d'), 'Cost');
        }
        updateNoDataVisual();
      });
  })();
  </script>
  <style>
    .asap-analytics-dashboard table.widefat { margin-bottom: 2em; }
    .asap-analytics-charts-area {
      width: 100%;
      max-width: none;
      background: #f8f9fa;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      margin: 0 0 2em 0;
      padding: 1em 1em 1em 1em;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 180px;
      /* Reduce height for compactness */
    }
    .asap-analytics-charts-area canvas {
      width: 100% !important;
      max-width: 700px;
      margin-bottom: 1em;
      background: #fff;
      border-radius: 4px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    @media (max-width: 900px) {
      .asap-analytics-charts-area {
        padding: 0.5em 0.25em 0.5em 0.25em;
      }
      .asap-analytics-charts-area canvas {
        max-width: 100%;
      }
    }
  </style>
</div> 