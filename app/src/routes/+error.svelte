<script>
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';
  import { authStore } from '$lib/utils/auth-persistence.js';
  import { dev } from '$app/environment';
  import { getApiUrl, getWpApiUrl } from '$lib/utils/api-config.js';
  import { getUserData } from '$lib/stores/user.js';
  
  // Import global styles to inherit theme
  import '../app.css';

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
      case 500:
        return 'Internal server error';
      default:
        return 'Error occurred';
    }
  });

  /** @type {string} */
  let description = $derived(() => {
    switch (status) {
      case 404:
        return 'The page you are looking for does not exist or has been moved.';
      case 403:
        return 'You do not have permission to access this resource.';
      case 401:
        return 'You need to be logged in to access this page.';
      case 500:
        return 'An internal server error occurred. Please try again later.';
      default:
        return 'An unexpected error occurred. Please try again.';
    }
  });

  // Get current user for admin-level debugging
  let currentUser = $state(null);
  let showDebugInfo = $state(false);
  
  $effect(() => {
    const user = authStore.get();
    currentUser = user;
    // Show debug info for administrators in development or if user has admin role
    const userData = getUserData(user);
    showDebugInfo = dev || userData.isAdmin;
  });

  // Get user data helpers for cleaner access
  const currentUserData = $derived(getUserData(currentUser));

  // Debug information for administrators
  let debugInfo = $derived(() => {
    if (!showDebugInfo) return null;
    
    return {
      timestamp: new Date().toISOString(),
      url: $page.url.href,
      route: $page.route.id,
      status: status,
      message: message,
      stack: $page.error?.stack,
      user: {
        id: currentUserData.id,
        email: currentUserData.email,
        roles: currentUserData.roles
      },
      environment: dev ? 'development' : 'production',
      apiConfig: {
        wordpressUrl: getApiUrl(),
        wpApiUrl: getWpApiUrl(),
        currentOrigin: typeof window !== 'undefined' ? window.location.origin : 'SSR'
      },
      headers: typeof window !== 'undefined' ? {
        userAgent: navigator.userAgent,
        language: navigator.language,
        cookieEnabled: navigator.cookieEnabled
      } : null
    };
  });

  function goHome() {
    goto('/');
  }

  function goBack() {
    if (typeof window !== 'undefined' && window.history.length > 1) {
      window.history.back();
    } else {
      goto('/');
    }
  }

  function copyDebugInfo() {
    if (debugInfo && typeof navigator !== 'undefined' && navigator.clipboard) {
      navigator.clipboard.writeText(JSON.stringify(debugInfo, null, 2))
        .then(() => {
          alert('Debug information copied to clipboard');
        })
        .catch(() => {
          alert('Failed to copy debug information');
        });
    }
  }

  function retryPage() {
    if (typeof window !== 'undefined') {
      window.location.reload();
    }
  }
</script>

<div class="error-page">
  <div class="error-container">
    <!-- Main Error Card -->
    <div class="error-card">
      <!-- Header -->
      <div class="error-header">
        <div class="error-icon-container">
          <div class="error-icon">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="error-title-container">
            <h1 class="error-title">{status} - {title}</h1>
            <p class="error-subtitle">ASAP Digest Application Error</p>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="error-content">
        <p class="error-description">{description}</p>
        
        {#if message && message !== title}
          <div class="error-details">
            <h3 class="error-details-title">Error Details:</h3>
            <p class="error-details-message">{message}</p>
          </div>
        {/if}

        <!-- Action Buttons -->
        <div class="error-actions">
          <button 
            on:click={retryPage}
            class="btn btn-primary"
          >
            Try Again
          </button>
  <button 
            on:click={goBack}
            class="btn btn-secondary"
  >
    Go Back
  </button>
          <button 
            on:click={goHome}
            class="btn btn-success"
          >
            Go Home
          </button>
        </div>

        <!-- Admin Debug Information -->
        {#if showDebugInfo && debugInfo}
          <div class="debug-section">
            <div class="debug-header">
              <h3 class="debug-title">
                🔧 Administrator Debug Information
              </h3>
              <button 
                on:click={copyDebugInfo}
                class="btn btn-small"
              >
                Copy Debug Info
              </button>
            </div>
            
            <div class="debug-output">
              <pre>{JSON.stringify(debugInfo, null, 2)}</pre>
            </div>
            
            <!-- Quick Debug Insights -->
            <div class="debug-insights">
              <div class="debug-card">
                <h4 class="debug-card-title">Authentication Status</h4>
                <p class="debug-card-content">
                  {currentUserData.isValid ? `✅ Logged in as ${currentUserData.email}` : '❌ Not authenticated'}
                </p>
                {#if currentUserData.roles}
                  <p class="debug-card-meta">Roles: {currentUserData.roles.join(', ')}</p>
                {/if}
              </div>
              
              <div class="debug-card">
                <h4 class="debug-card-title">API Configuration</h4>
                <p class="debug-card-content">WordPress: {getApiUrl()}</p>
                <p class="debug-card-meta">Current: {typeof window !== 'undefined' ? window.location.origin : 'SSR'}</p>
              </div>
            </div>

            {#if status === 500 || status === 404 || status === 403}
              <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-800 text-sm mb-2">💡 Troubleshooting Tips</h4>
                <ul class="text-yellow-700 text-sm space-y-1">
                  {#if status === 403}
                    <li>• Check if user has required permissions for this route</li>
                    <li>• Verify authentication session is valid</li>
                    <li>• Check if route is properly protected</li>
                  {:else if status === 404}
                    <li>• Verify the route exists in the SvelteKit app</li>
                    <li>• Check if WordPress REST API endpoints are available</li>
                    <li>• Ensure API configuration points to correct WordPress domain</li>
                  {:else if status === 500}
                    <li>• Check browser console for JavaScript errors</li>
                    <li>• Verify WordPress plugin is active and configured</li>
                    <li>• Check WordPress error logs for PHP errors</li>
                    <li>• Ensure database connections are working</li>
                  {/if}
                </ul>
              </div>
            {/if}
    </div>
  {/if}
</div> 
    </div>

    <!-- Additional Help -->
    <div class="mt-6 text-center">
      <p class="text-slate-500 text-sm">
        If this problem persists, please contact support with the error details above.
      </p>
    </div>
  </div>
</div>

<style>
  .error-page {
    min-height: 100vh;
    background: var(--color-background-primary, #f8fafc);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    font-family: var(--font-family-base, system-ui, -apple-system, sans-serif);
  }

  .error-container {
    max-width: 48rem;
    width: 100%;
  }

  .error-card {
    background: var(--color-surface-primary, #ffffff);
    border-radius: var(--border-radius-lg, 1rem);
    box-shadow: var(--shadow-xl, 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1));
    border: 1px solid var(--color-border-primary, #e2e8f0);
    overflow: hidden;
  }

  .error-header {
    background: linear-gradient(135deg, var(--color-error-primary, #ef4444), var(--color-error-secondary, #dc2626));
    padding: 2rem;
    color: white;
  }

  .error-icon-container {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .error-icon {
    width: 3rem;
    height: 3rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .icon {
    width: 1.5rem;
    height: 1.5rem;
  }

  .error-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
  }

  .error-subtitle {
    color: rgba(255, 255, 255, 0.8);
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
  }

  .error-content {
    padding: 2rem;
  }

  .error-description {
    color: var(--color-text-secondary, #64748b);
    font-size: 1.125rem;
    margin-bottom: 1.5rem;
  }

  .error-details {
    background: var(--color-background-secondary, #f1f5f9);
    border-radius: var(--border-radius-md, 0.5rem);
    padding: 1rem;
    margin-bottom: 1.5rem;
  }

  .error-details-title {
    font-weight: 600;
    color: var(--color-text-primary, #1e293b);
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
  }

  .error-details-message {
    color: var(--color-text-secondary, #64748b);
    font-family: var(--font-family-mono, 'Courier New', monospace);
    font-size: 0.875rem;
    margin: 0;
    word-break: break-word;
  }

  .error-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }

  .btn {
    padding: 0.5rem 1.5rem;
    border-radius: var(--border-radius-md, 0.5rem);
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
  }

  .btn-primary {
    background: var(--color-primary, #3b82f6);
    color: white;
  }

  .btn-primary:hover {
    background: var(--color-primary-hover, #2563eb);
  }

  .btn-secondary {
    background: var(--color-secondary, #64748b);
    color: white;
  }

  .btn-secondary:hover {
    background: var(--color-secondary-hover, #475569);
  }

  .btn-success {
    background: var(--color-success, #10b981);
    color: white;
  }

  .btn-success:hover {
    background: var(--color-success-hover, #059669);
  }

  .btn-small {
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    background: var(--color-background-tertiary, #e2e8f0);
    color: var(--color-text-secondary, #64748b);
  }

  .btn-small:hover {
    background: var(--color-background-quaternary, #cbd5e1);
  }

  .debug-section {
    border-top: 1px solid var(--color-border-primary, #e2e8f0);
    padding-top: 1.5rem;
  }

  .debug-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .debug-title {
    font-weight: 600;
    color: var(--color-text-primary, #1e293b);
    margin: 0;
    font-size: 1rem;
  }

  .debug-output {
    background: var(--color-background-code, #1e293b);
    color: var(--color-text-code, #10b981);
    border-radius: var(--border-radius-md, 0.5rem);
    padding: 1rem;
    font-family: var(--font-family-mono, 'Courier New', monospace);
    font-size: 0.75rem;
    overflow-x: auto;
    margin-bottom: 1rem;
  }

  .debug-insights {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
  }

  .debug-card {
    background: var(--color-background-secondary, #f1f5f9);
    border-radius: var(--border-radius-md, 0.5rem);
    padding: 0.75rem;
  }

  .debug-card-title {
    font-weight: 600;
    color: var(--color-text-primary, #1e293b);
    font-size: 0.875rem;
    margin: 0 0 0.25rem 0;
  }

  .debug-card-content {
    color: var(--color-text-secondary, #64748b);
    font-size: 0.875rem;
    margin: 0;
  }

  .debug-card-meta {
    color: var(--color-text-tertiary, #94a3b8);
    font-size: 0.75rem;
    margin: 0.25rem 0 0 0;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .error-page {
      padding: 0.5rem;
    }
    
    .error-header {
      padding: 1.5rem;
    }
    
    .error-content {
      padding: 1.5rem;
    }
    
    .error-icon-container {
      flex-direction: column;
      text-align: center;
      gap: 0.75rem;
    }
    
    .error-actions {
      flex-direction: column;
    }
    
    .btn {
      width: 100%;
      justify-content: center;
    }
  }
</style> 