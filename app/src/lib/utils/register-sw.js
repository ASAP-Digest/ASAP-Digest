/**
 * Service Worker registration utility
 * Only registers in production environment and only in browser context
 */

/**
 * Register the service worker
 * @returns {Promise<void>}
 */
export async function registerServiceWorker() {
    // Skip if not in browser environment
    if (typeof window === 'undefined') {
        return;
    }

    // Skip registration in development
    if (import.meta.env.DEV) {
        console.log('[SW] Service Worker registration skipped in development');
        return;
    }

    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js', {
                scope: '/'
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
                    }
                });
            });

            // Handle controller changes
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('[SW] Service Worker controller changed');
            });

        } catch (error) {
            console.error('[SW] Registration failed:', error);
        }
    }
} 