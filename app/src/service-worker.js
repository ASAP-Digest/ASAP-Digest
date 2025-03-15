import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst } from 'workbox-strategies';
import { setCacheNameDetails } from 'workbox-core';
import { BackgroundSyncPlugin } from 'workbox-background-sync';

setCacheNameDetails({
    prefix: 'asapdigest',
    precache: 'precache',
    runtime: 'runtime',
});

precacheAndRoute(self.__WB_MANIFEST);

// Cache API responses (e.g., GraphQL queries)
registerRoute(
    ({ url }) => url.pathname.startsWith('/graphql'),
    new NetworkFirst({
        cacheName: 'api-cache',
        plugins: [
            {
                cacheWillUpdate: async ({ request, response }) => {
                    return response && response.status === 200 ? response : null;
                },
            },
        ],
    })
);

// Cache static assets
registerRoute(
    ({ request }) => request.destination === 'image' || request.destination === 'script' || request.destination === 'style',
    new CacheFirst({
        cacheName: 'static-assets',
    })
);

// Background sync for API requests
const bgSyncPlugin = new BackgroundSyncPlugin('failed-requests', {
    maxRetentionTime: 24 * 60, // Retry for up to 24 hours
});

// Register background sync for form submissions
registerRoute(
    ({ url }) => url.pathname.startsWith('/api/'),
    new NetworkFirst({
        plugins: [bgSyncPlugin],
    }),
    'POST'
);

// Push event handler for notifications
self.addEventListener('push', (event) => {
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-192x192.png',
        data: {
            url: data.url || '/',
        },
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Click event handler for notifications
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
}); 