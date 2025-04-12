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
  
  <!-- Display stack trace in dev mode -->
  {#if $page.error?.stack && import.meta.env.DEV}
    <div style="margin-top: 1.5rem; background-color: #333; padding: 1rem; border-radius: 0.375rem; text-align: left; font-size: 0.75rem; font-family: monospace; max-width: 800px; overflow-x: auto;">
      <h3 style="margin-bottom: 0.5rem; font-weight: 600;">Stack Trace (Dev Mode)</h3>
      <pre style="white-space: pre-wrap; word-break: break-all;">{$page.error.stack}</pre>
    </div>
  {/if}
</div> 