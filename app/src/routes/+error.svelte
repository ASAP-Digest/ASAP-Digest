<script>
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';

  /** @type {string} */
  let message = $derived($page.error?.message || 'Something went wrong');
  
  /** @type {number} */
  let status = $derived($page.status || 500);
  
  /** @type {string} */
  let title = $derived(() => {
    switch (status) {
      case 404:
        return 'Page not found';
      case 403:
        return 'Access denied';
      case 401:
        return 'Unauthorized';
      default:
        return 'Error occurred';
    }
  });

  function goBack() {
    if (window.history.length > 2) {
      window.history.back();
    } else {
      goto('/');
    }
  }
</script>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80vh; padding: 1rem; text-align: center; background-color: #222; color: #eee;">
  <h1 style="font-size: 2.25rem; font-weight: bold; margin-bottom: 1rem;">{status}</h1>
  <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">{title}</h2>
  <p style="color: #aaa; margin-bottom: 2rem;">{message}</p>
  
  <!-- Basic HTML button -->
  <button 
    type="button"
    onclick={goBack} 
    style="padding: 0.5rem 1rem; border: 1px solid #555; background-color: #333; border-radius: 0.375rem; cursor: pointer;"
  >
    Go Back
  </button>
  
  <!-- Display stack trace in development -->
  {#if $page.error?.stack && import.meta.env.DEV}
    <div class="mt-6 p-4 bg-gray-100 dark:bg-gray-800 rounded-md overflow-auto">
      <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-200">Stack Trace (Dev Only):</h3>
      <!-- @ts-ignore - Allow access to potentially non-standard stack property in dev --> 
      <pre class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{JSON.stringify($page.error.stack, null, 2)}</pre>
    </div>
  {/if}
</div> 