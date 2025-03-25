<script>
  import { onMount } from 'svelte';
  import { registerServiceWorker } from '$lib/utils/register-sw';
  import { browser } from '$app/environment';
  
  // State variables
  let serviceWorkerStatus = $state("Not registered");
  let installPromptEvent = $state(null);
  let isInstalled = $state(false);
  let isOnline = $state(true);
  let showControls = $state(false);
  
  // Actions section
  let installPromptAvailable = $state(false);
  let installPromptVisible = $state(false);
  
  /**
   * Check if app is installed as PWA
   * @returns {boolean}
   */
  function checkIfInstalled() {
    if (!browser) return false;
    
    return (
      window.matchMedia('(display-mode: standalone)').matches ||
      window.matchMedia('(display-mode: fullscreen)').matches ||
      window.matchMedia('(display-mode: minimal-ui)').matches ||
      window.navigator.standalone // iOS Safari
    );
  }
  
  /**
   * Check service worker registration status
   */
  async function checkServiceWorkerStatus() {
    if (!browser) return;
    
    try {
      if (!('serviceWorker' in navigator)) {
        serviceWorkerStatus = "Service Workers not supported";
        return;
      }
      
      const registrations = await navigator.serviceWorker.getRegistrations();
      if (registrations.length === 0) {
        serviceWorkerStatus = "No Service Workers registered";
      } else {
        const registration = registrations[0];
        if (registration.installing) {
          serviceWorkerStatus = "Installing";
        } else if (registration.waiting) {
          serviceWorkerStatus = "Waiting";
        } else if (registration.active) {
          serviceWorkerStatus = "Active";
        } else {
          serviceWorkerStatus = "Registered (unknown state)";
        }
      }
    } catch (error) {
      console.error('[TestPwaControls] Error checking service worker status:', error);
      serviceWorkerStatus = `Error: ${error.message}`;
    }
  }
  
  /**
   * Force register service worker
   */
  async function forceRegisterServiceWorker() {
    if (!browser) return;
    
    try {
      await registerServiceWorker(true);
      await checkServiceWorkerStatus();
    } catch (error) {
      console.error('[TestPwaControls] Error registering service worker:', error);
      serviceWorkerStatus = `Registration failed: ${error.message}`;
    }
  }
  
  /**
   * Unregister all service workers
   */
  async function unregisterServiceWorkers() {
    if (!browser) return;
    
    try {
      if (!('serviceWorker' in navigator)) return;
      
      const registrations = await navigator.serviceWorker.getRegistrations();
      for (const registration of registrations) {
        await registration.unregister();
      }
      serviceWorkerStatus = "Unregistered";
    } catch (error) {
      console.error('[TestPwaControls] Error unregistering service workers:', error);
      serviceWorkerStatus = `Unregister failed: ${error.message}`;
    }
  }
  
  /**
   * Clear all caches
   */
  async function clearCaches() {
    if (!browser) return;
    
    try {
      const cacheNames = await caches.keys();
      await Promise.all(
        cacheNames.map(name => caches.delete(name))
      );
      alert(`Cleared ${cacheNames.length} caches`);
    } catch (error) {
      console.error('[TestPwaControls] Error clearing caches:', error);
      alert(`Failed to clear caches: ${error.message}`);
    }
  }
  
  /**
   * Toggle online/offline state
   */
  function toggleOfflineMode() {
    if (!browser) return;
    
    try {
      // We can't actually go offline, but we can simulate it for testing
      if (isOnline) {
        // Simulate offline by disabling all fetch requests
        window.addEventListener('fetch', function(event) {
          event.respondWith(
            new Promise((resolve, reject) => {
              reject(new Error('Simulated offline mode'));
            })
          );
        }, { capture: true });
        isOnline = false;
      } else {
        window.location.reload(); // Reload to restore normal network behavior
      }
    } catch (error) {
      console.error('[TestPwaControls] Error toggling offline mode:', error);
    }
  }
  
  /**
   * Trigger installation prompt
   */
  function promptInstall() {
    if (!browser) return;
    
    try {
      if (installPromptEvent) {
        installPromptEvent.prompt();
      } else {
        alert("Install prompt not available. The app must be served over HTTPS, have a valid manifest, and meet other PWA criteria.");
      }
    } catch (error) {
      console.error('[TestPwaControls] Error prompting install:', error);
    }
  }
  
  /**
   * Force reset the install prompt
   */
  function resetInstallPrompt() {
    if (!browser) return;
    
    try {
      window.dispatchEvent(new CustomEvent('pwa-test-control', {
        detail: { type: 'reset-install-prompt' }
      }));
      alert('Install prompt settings reset. The prompt should appear shortly.');
    } catch (error) {
      console.error('[TestPwaControls] Error resetting install prompt:', error);
    }
  }
  
  /**
   * Force show the install prompt
   */
  function showInstallPrompt() {
    if (!browser) return;
    
    try {
      window.dispatchEvent(new CustomEvent('pwa-test-control', {
        detail: { type: 'force-show-prompt' }
      }));
      installPromptVisible = true;
    } catch (error) {
      console.error('[TestPwaControls] Error showing install prompt:', error);
    }
  }
  
  /**
   * Force hide the install prompt
   */
  function hideInstallPrompt() {
    if (!browser) return;
    
    try {
      window.dispatchEvent(new CustomEvent('pwa-test-control', {
        detail: { type: 'force-hide-prompt' }
      }));
      installPromptVisible = false;
    } catch (error) {
      console.error('[TestPwaControls] Error hiding install prompt:', error);
    }
  }
  
  // Initialize PWA testing only on the client-side
  onMount(() => {
    if (!browser) return;
    
    try {
      // Check if app is already installed
      isInstalled = checkIfInstalled();
      
      // Check if we're in PWA test mode
      showControls = window.location.search.includes('pwa-test');
      
      // Check service worker status
      checkServiceWorkerStatus();
      
      // Check online status
      isOnline = navigator.onLine;
      window.addEventListener('online', () => isOnline = true);
      window.addEventListener('offline', () => isOnline = false);
      
      // Capture install prompt event
      window.addEventListener('beforeinstallprompt', (e) => {
        try {
          e.preventDefault();
          installPromptEvent = e;
          installPromptAvailable = true;
        } catch (error) {
          console.error('[TestPwaControls] Error handling beforeinstallprompt event:', error);
        }
      });
      
      // Listen for install prompt availability from InstallPrompt component
      window.addEventListener('pwa-install-prompt-available', () => {
        try {
          installPromptAvailable = true;
        } catch (error) {
          console.error('[TestPwaControls] Error handling pwa-install-prompt-available event:', error);
        }
      });
      
      // Check if app was installed
      window.addEventListener('appinstalled', () => {
        try {
          isInstalled = true;
          installPromptEvent = null;
          installPromptAvailable = false;
        } catch (error) {
          console.error('[TestPwaControls] Error handling appinstalled event:', error);
        }
      });

      // Listen for app installed event from InstallPrompt component
      window.addEventListener('pwa-app-installed', () => {
        try {
          isInstalled = true;
          installPromptEvent = null;
          installPromptAvailable = false;
        } catch (error) {
          console.error('[TestPwaControls] Error handling pwa-app-installed event:', error);
        }
      });
      
      // Check every 5 seconds
      const interval = setInterval(() => {
        try {
          checkServiceWorkerStatus();
          isInstalled = checkIfInstalled();
        } catch (error) {
          console.error('[TestPwaControls] Error in status check interval:', error);
        }
      }, 5000);
      
      return () => {
        clearInterval(interval);
      };
    } catch (error) {
      console.error('[TestPwaControls] Error initializing component:', error);
    }
  });
</script>

{#if browser}
  {#if showControls}
  <div class="pwa-test-controls">
    <div class="controls-header">
      <h2>PWA Testing Tools</h2>
      <button class="close-btn" onclick={() => showControls = false}>×</button>
    </div>
    
    <div class="controls-body">
      <div class="status-section">
        <h3>Status</h3>
        <p><strong>SW Status:</strong> {serviceWorkerStatus}</p>
        <p><strong>Install Prompt:</strong> {installPromptEvent ? 'Available' : 'Not available'}</p>
        <p><strong>Installed:</strong> {isInstalled ? 'Yes' : 'No'}</p>
        <p><strong>Network:</strong> {isOnline ? 'Online' : 'Offline'}</p>
        <p><strong>Installation UI:</strong> {installPromptVisible ? 'Visible' : 'Hidden'}</p>
      </div>
      
      <div class="actions-section">
        <h3>Actions</h3>
        <div class="button-row">
          <button 
            class="action-btn" 
            onclick={forceRegisterServiceWorker}
          >Register SW</button>
          
          <button 
            class="action-btn" 
            onclick={unregisterServiceWorkers}
          >Unregister SW</button>
        </div>
        
        <div class="button-row">
          <button 
            class="action-btn" 
            onclick={clearCaches}
          >Clear Caches</button>
          
          <button 
            class="action-btn" 
            onclick={toggleOfflineMode}
          >{isOnline ? 'Simulate Offline' : 'Go Online'}</button>
        </div>
        
        <div class="button-row">
          <button 
            class="action-btn install-btn"
            onclick={promptInstall}
            disabled={!installPromptEvent || isInstalled}
          >{isInstalled ? 'Already Installed' : 'Prompt Install'}</button>
          
          <button 
            class="action-btn reload-btn"
            onclick={() => window.location.reload()}
          >Reload Page</button>
        </div>
        
        <h3 class="mt-[1rem]">Install Prompt Controls</h3>
        <div class="button-row">
          <button 
            class="action-btn" 
            onclick={resetInstallPrompt}
          >Reset Prompt</button>
          
          <button 
            class="action-btn" 
            onclick={showInstallPrompt}
          >Show Prompt</button>
          
          <button 
            class="action-btn" 
            onclick={hideInstallPrompt}
          >Hide Prompt</button>
        </div>
      </div>
      
      <div class="info-section">
        <h3>Testing Instructions</h3>
        <ol>
          <li>Register the service worker</li>
          <li>Wait for installation to complete</li>
          <li>Test offline functionality by clicking "Simulate Offline"</li>
          <li>Test installation if the prompt is available</li>
          <li>To reset, use "Unregister SW" and "Clear Caches", then reload</li>
        </ol>
      </div>
    </div>
  </div>
  {:else if browser && window.location && new URLSearchParams(window.location.search).has('pwa-test')}
    <button class="show-controls-btn" onclick={() => showControls = true}>Show PWA Controls</button>
  {/if}
{/if}

<style>
  .pwa-test-controls {
    position: fixed;
    bottom: 0;
    right: 0;
    width: 23.75rem; /* 380px */
    background-color: hsl(var(--background));
    border: 1px solid hsl(var(--border)/0.8);
    border-top-left-radius: 0.5rem; /* 8px */
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    font-size: 0.875rem; /* 14px */
  }
  
  .controls-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0.9375rem; /* 10px 15px */
    background-color: hsl(var(--primary)/0.1);
    border-bottom: 1px solid hsl(var(--border)/0.8);
    border-top-left-radius: 0.5rem; /* 8px */
  }
  
  .controls-header h2 {
    margin: 0;
    font-size: 1rem; /* 16px */
    font-weight: 600;
    color: hsl(var(--primary));
  }
  
  .close-btn {
    background: none;
    border: none;
    font-size: 1.25rem; /* 20px */
    cursor: pointer;
    color: hsl(var(--foreground)/0.8);
  }
  
  .controls-body {
    padding: 0.9375rem; /* 15px */
    max-height: 31.25rem; /* 500px */
    overflow-y: auto;
  }
  
  .status-section,
  .actions-section,
  .info-section {
    margin-bottom: 1.25rem; /* 20px */
  }
  
  .status-section h3,
  .actions-section h3,
  .info-section h3 {
    margin-top: 0;
    margin-bottom: 0.625rem; /* 10px */
    font-size: 0.875rem; /* 14px */
    font-weight: 600;
    color: hsl(var(--foreground)/0.8);
  }
  
  .button-row {
    display: flex;
    gap: 0.625rem; /* 10px */
    margin-bottom: 0.625rem; /* 10px */
  }
  
  .action-btn {
    padding: 0.5rem 0.75rem; /* 8px 12px */
    background-color: hsl(var(--secondary)/0.8);
    color: hsl(var(--secondary-foreground));
    border: none;
    border-radius: 0.25rem; /* 4px */
    cursor: pointer;
    flex: 1;
    font-size: 0.8125rem; /* 13px */
    transition: background-color 0.2s ease;
  }
  
  .action-btn:hover {
    background-color: hsl(var(--secondary));
  }
  
  .action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .install-btn {
    background-color: hsl(var(--primary)/0.8);
    color: hsl(var(--primary-foreground));
  }
  
  .install-btn:hover {
    background-color: hsl(var(--primary));
  }
  
  .reload-btn {
    background-color: hsl(var(--muted)/0.8);
    color: hsl(var(--muted-foreground));
  }
  
  .reload-btn:hover {
    background-color: hsl(var(--muted));
  }
  
  .info-section ol {
    margin: 0;
    padding-left: 1.25rem; /* 20px */
  }
  
  .info-section li {
    margin-bottom: 0.3125rem; /* 5px */
  }
  
  .show-controls-btn {
    position: fixed;
    bottom: 1.25rem; /* 20px */
    right: 1.25rem; /* 20px */
    padding: 0.625rem 0.9375rem; /* 10px 15px */
    background-color: hsl(var(--primary)/0.8);
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: 0.25rem; /* 4px */
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    font-size: 0.875rem; /* 14px */
  }
  
  .show-controls-btn:hover {
    background-color: hsl(var(--primary));
  }
</style> 