/**
 * Service Worker registration utility
 * Only registers in production environment and only in browser context
 * Also supports testing mode in development with query parameter
 */

/**
 * Register the service worker
 * @param {boolean} [forceRegistration=false] - Force registration even in development
 * @returns {Promise<ServiceWorkerRegistration | undefined>}
 */
export async function registerServiceWorker(forceRegistration = false) {
    // Skip if not in browser environment
    if (typeof window === 'undefined') {
        return undefined;
    }

    // Check for pwa-test parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const isTestMode = urlParams.has('pwa-test');

    // Skip registration in development unless forced or test mode is active
    if (import.meta.env.DEV && !forceRegistration && !isTestMode) {
        console.log('[SW] Service Worker registration skipped in development mode');
        console.log('[SW] Add ?pwa-test to the URL to test in development mode');
        return undefined;
    }

    if ('serviceWorker' in navigator) {
        try {
            // Wait for the page to be fully loaded to avoid network contention
            if (document.readyState !== 'complete') {
                await new Promise(resolve => {
                    window.addEventListener('load', resolve, { once: true });
                });
            }

            const swUrl = isTestMode ? '/service-worker.js?pwa-test' : '/service-worker.js';

            console.log(`[SW] Registering service worker at ${swUrl}`);
            const registration = await navigator.serviceWorker.register(swUrl, {
                scope: '/',
                // Use type: 'module' in SvelteKit 2 + Vite environments
                type: 'classic'
            });

            if (registration.installing) {
                console.log('[SW] Service Worker installing');
            } else if (registration.waiting) {
                console.log('[SW] Service Worker installed');
            } else if (registration.active) {
                console.log('[SW] Service Worker active');
            }

            // Handle updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                if (!newWorker) return;

                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New version available
                        console.log('[SW] New version available');

                        // Optionally show update notification here
                        if (window.confirm('New content is available. Reload to update?')) {
                            // Tell the service worker to skip waiting
                            newWorker.postMessage({ type: 'SKIP_WAITING' });
                            window.location.reload();
                        }
                    }
                });
            });

            // Handle controller changes
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('[SW] Service Worker controller changed');
            });

            return registration;
        } catch (error) {
            console.error('[SW] Registration failed:', error);
            throw error; // Allow caller to handle or ignore the error
        }
    } else {
        console.log('[SW] Service Workers not supported in this browser');
        return undefined;
    }
} 