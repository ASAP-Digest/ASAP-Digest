// ASAP Digest Service Worker
// Works with SvelteKit 2 and Svelte 5

// Check for development mode with support for testing mode
const IS_DEVELOPMENT = self.location.hostname === 'localhost' ||
    self.location.hostname === '127.0.0.1' ||
    self.location.port === '5173' ||
    self.location.port === '3000';

// Add query parameter capability for testing in development
const url = new URL(self.location.href);
const forceTest = url.searchParams.has('pwa-test');

// Exit immediately in development to prevent conflicts with HMR
// Unless the pwa-test parameter is present
if (IS_DEVELOPMENT && !forceTest) {
    self.addEventListener('install', () => {
        console.log('[Service Worker] Development mode detected - skipping installation');
        self.skipWaiting();
    });
    self.addEventListener('activate', (event) => {
        console.log('[Service Worker] Development mode detected - skipping activation');
        event.waitUntil(self.clients.claim());
    });
} else {
    // Production mode or development testing mode - normal service worker functionality
    console.log('[Service Worker] Running in ' + (IS_DEVELOPMENT ? 'development test mode' : 'production mode'));

    // Cache name includes version for easier updates
    const CACHE_NAME = 'asapdigest-cache-v1';
    const OFFLINE_PAGE = '/offline.html';

    // Files to cache immediately on service worker installation
    const INITIAL_CACHED_RESOURCES = [
        '/',
        '/offline.html',
        '/favicon.png',
        '/manifest.json',
        '/icons/icon-192x192.png',
        '/icons/icon-512x512.png',
        '/icons/maskable-icon.png'
    ];

    // Install event handler - cache critical assets
    self.addEventListener('install', (event) => {
        event.waitUntil(
            caches.open(CACHE_NAME)
                .then((cache) => {
                    console.log('[Service Worker] Caching initial resources');
                    return cache.addAll(INITIAL_CACHED_RESOURCES);
                })
                .then(() => {
                    console.log('[Service Worker] Installation complete, skipping waiting');
                    return self.skipWaiting();
                })
                .catch(error => {
                    console.error('[Service Worker] Installation failed:', error);
                })
        );
    });

    // Activate event handler - clean up old caches
    self.addEventListener('activate', (event) => {
        event.waitUntil(
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[Service Worker] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }).then(() => {
                console.log('[Service Worker] Claiming clients');
                return self.clients.claim();
            })
        );
    });

    // Message event handler - for communication with the app
    self.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'SKIP_WAITING') {
            console.log('[Service Worker] Skip waiting message received');
            self.skipWaiting();
        }
    });

    // Fetch event handler - stale-while-revalidate strategy for most requests
    self.addEventListener('fetch', (event) => {
        // Skip non-GET requests
        if (event.request.method !== 'GET') return;

        // Skip cross-origin requests
        if (!event.request.url.startsWith(self.location.origin)) return;

        // Skip WebSocket connections and Vite HMR
        if (event.request.url.includes('/ws') ||
            event.request.url.includes('/socket') ||
            event.request.url.includes('/_app/immutable') ||
            event.request.url.includes('vite') ||
            event.request.url.includes('/__vite_ping') ||
            event.request.url.includes('/@vite/') ||
            event.request.url.includes('/@fs/') ||
            event.request.headers?.get('upgrade') === 'websocket') {
            return;
        }

        // Network first for API requests
        if (event.request.url.includes('/api/')) {
            event.respondWith(
                fetch(event.request)
                    .then(response => {
                        // Clone the response for caching
                        const responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(() => {
                        return caches.match(event.request)
                            .then(cachedResponse => {
                                if (cachedResponse) {
                                    return cachedResponse;
                                }
                                return caches.match(OFFLINE_PAGE);
                            });
                    })
            );
            return;
        }

        // Stale-while-revalidate for all other requests
        event.respondWith(
            caches.match(event.request)
                .then(cachedResponse => {
                    // Return cached response immediately if available
                    const fetchPromise = fetch(event.request)
                        .then(networkResponse => {
                            // Update cache with new response
                            if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                                const responseToCache = networkResponse.clone();
                                caches.open(CACHE_NAME)
                                    .then(cache => {
                                        cache.put(event.request, responseToCache);
                                    });
                            }
                            return networkResponse;
                        })
                        .catch(error => {
                            console.error('[Service Worker] Fetch failed:', error);
                            // If both cache and network fail, show offline page
                            return caches.match(OFFLINE_PAGE);
                        });

                    return cachedResponse || fetchPromise;
                })
        );
    });

    // Push notification handler
    self.addEventListener('push', (event) => {
        if (!event.data) return;

        try {
            const data = event.data.json();

            const options = {
                body: data.body || 'New update from ASAP Digest',
                icon: '/icons/icon-192x192.png',
                badge: '/icons/icon-72x72.png',
                data: {
                    url: data.url || '/'
                },
                vibrate: [100, 50, 100],
                tag: data.tag || 'asap-digest-notification',
                // Allow multiple notifications if different tags
                renotify: data.tag ? true : false
            };

            event.waitUntil(
                self.registration.showNotification(data.title || 'ASAP Digest', options)
            );
        } catch (error) {
            console.error('[Service Worker] Error processing push notification:', error);
        }
    });

    // Click handler for notifications
    self.addEventListener('notificationclick', (event) => {
        event.notification.close();

        // Focus on existing tab if open, otherwise open new window
        event.waitUntil(
            clients.matchAll({
                type: 'window',
                includeUncontrolled: true
            }).then(windowClients => {
                // Check if there is already a window/tab open with the target URL
                const targetUrl = event.notification.data?.url || '/';

                for (let i = 0; i < windowClients.length; i++) {
                    const client = windowClients[i];
                    // If so, focus it
                    if (client.url === targetUrl && 'focus' in client) {
                        return client.focus();
                    }
                }

                // If not, open a new window/tab
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
        );
    });
} 