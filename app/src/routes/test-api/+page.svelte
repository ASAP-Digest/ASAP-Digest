<script>
  import { onMount } from 'svelte';
  import { fetchLayoutTemplates } from '$lib/api/digest-builder.js';

  let status = 'Loading...';
  let templates = [];
  let error = null;

  onMount(async () => {
    try {
      status = 'Fetching layout templates...';
      const response = await fetchLayoutTemplates();
      
      if (response.success) {
        templates = response.data;
        status = `Success! Found ${templates.length} templates`;
      } else {
        error = response.error;
        status = 'Failed to fetch templates';
      }
    } catch (err) {
      error = err.message;
      status = 'Error occurred';
    }
  });
</script>

<div class="test-container">
  <h1>API Test Page</h1>
  
  <div class="status">
    <strong>Status:</strong> {status}
  </div>
  
  {#if error}
    <div class="error">
      <strong>Error:</strong> {error}
    </div>
  {/if}
  
  {#if templates.length > 0}
    <div class="templates">
      <h2>Layout Templates:</h2>
      <ul>
        {#each templates as template}
          <li>
            <strong>{template.name}</strong> (ID: {template.id})
            <br>
            <small>{template.description}</small>
          </li>
        {/each}
      </ul>
    </div>
  {/if}
  
  <div class="debug">
    <h3>Debug Info:</h3>
    <pre>{JSON.stringify({ status, error, templateCount: templates.length }, null, 2)}</pre>
  </div>
</div>

<style>
  .test-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    font-family: system-ui, sans-serif;
  }
  
  .status {
    padding: 1rem;
    background: #f0f0f0;
    border-radius: 4px;
    margin: 1rem 0;
  }
  
  .error {
    padding: 1rem;
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 4px;
    margin: 1rem 0;
    color: #c00;
  }
  
  .templates {
    margin: 2rem 0;
  }
  
  .templates ul {
    list-style: none;
    padding: 0;
  }
  
  .templates li {
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 0.5rem 0;
  }
  
  .debug {
    margin-top: 2rem;
    padding: 1rem;
    background: #f8f8f8;
    border-radius: 4px;
  }
  
  .debug pre {
    background: white;
    padding: 1rem;
    border-radius: 4px;
    overflow: auto;
  }
</style> 