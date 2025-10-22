/* eslint-env serviceworker */
// Service Worker for COPRRA
// Version 1.0.0

// const CACHE_NAME = 'coprra-v1.0.0';
const STATIC_CACHE = 'coprra-static-v1.0.0';
const DYNAMIC_CACHE = 'coprra-dynamic-v1.0.0';

// Files to cache immediately
const STATIC_FILES = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
    '/offline.html',
    // Add other static assets
];

// Install event
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');

    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then(cache => {
                console.log('Service Worker: Caching static files');
                return cache.addAll(STATIC_FILES);
            })
            .then(() => {
                console.log('Service Worker: Installation complete');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker: Installation failed', error);
            })
    );
});

// Activate event
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');

    event.waitUntil(
        caches
            .keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (
                            cacheName !== STATIC_CACHE &&
                            cacheName !== DYNAMIC_CACHE
                        ) {
                            console.log(
                                'Service Worker: Deleting old cache',
                                cacheName
                            );
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: Activation complete');
                return self.clients.claim();
            })
    );
});

// Fetch event
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip external requests
    if (url.origin !== location.origin) {
        return;
    }

    // Handle different types of requests
    if (isStaticAsset(request)) {
        event.respondWith(handleStaticAsset(request));
    } else if (isAPIRequest(request)) {
        event.respondWith(handleAPIRequest(request));
    } else {
        event.respondWith(handlePageRequest(request));
    }
});

// Check if request is for static asset
function isStaticAsset(request) {
    const url = new URL(request.url);
    return url.pathname.match(
        /\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/
    );
}

// Check if request is for API
function isAPIRequest(request) {
    const url = new URL(request.url);
    return url.pathname.startsWith('/api/');
}

// Handle static assets
async function handleStaticAsset(request) {
    try {
        const cache = await caches.open(STATIC_CACHE);
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('Service Worker: Error handling static asset', error);
        return new Response('Asset not available', { status: 404 });
    }
}

// Handle API requests
async function handleAPIRequest(request) {
    try {
        // Try network first for API requests
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('Service Worker: Error handling API request', error);

        // Try to return cached response
        const cache = await caches.open(DYNAMIC_CACHE);
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        return new Response(
            JSON.stringify({
                error: 'Network error',
                message: 'Please check your internet connection',
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' },
            }
        );
    }
}

// Handle page requests
async function handlePageRequest(request) {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('Service Worker: Error handling page request', error);

        // Try to return cached response
        const cache = await caches.open(DYNAMIC_CACHE);
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline page
        const offlineResponse = await cache.match('/offline.html');
        if (offlineResponse) {
            return offlineResponse;
        }

        return new Response('Offline', { status: 503 });
    }
}

// Background sync for offline actions
self.addEventListener('sync', event => {
    console.log('Service Worker: Background sync', event.tag);

    if (event.tag === 'price-alert') {
        event.waitUntil(syncPriceAlerts());
    } else if (event.tag === 'wishlist') {
        event.waitUntil(syncWishlist());
    }
});

// Sync price alerts
async function syncPriceAlerts() {
    try {
        // Get pending price alerts from IndexedDB
        const pendingAlerts = await getPendingPriceAlerts();

        for (const alert of pendingAlerts) {
            try {
                const response = await fetch('/api/price-alerts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': alert.csrf_token,
                    },
                    body: JSON.stringify(alert.data),
                });

                if (response.ok) {
                    await removePendingPriceAlert(alert.id);
                }
            } catch (error) {
                console.error(
                    'Service Worker: Error syncing price alert',
                    error
                );
            }
        }
    } catch (error) {
        console.error('Service Worker: Error in syncPriceAlerts', error);
    }
}

// Sync wishlist
async function syncWishlist() {
    try {
        // Get pending wishlist actions from IndexedDB
        const pendingActions = await getPendingWishlistActions();

        for (const action of pendingActions) {
            try {
                const response = await fetch(
                    `/api/wishlist/${action.product_id}`,
                    {
                        method: action.method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': action.csrf_token,
                        },
                    }
                );

                if (response.ok) {
                    await removePendingWishlistAction(action.id);
                }
            } catch (error) {
                console.error(
                    'Service Worker: Error syncing wishlist action',
                    error
                );
            }
        }
    } catch (error) {
        console.error('Service Worker: Error in syncWishlist', error);
    }
}

// Helper functions for IndexedDB operations
async function getPendingPriceAlerts() {
    // Implementation would depend on your IndexedDB setup
    return [];
}

async function removePendingPriceAlert(/* id */) {
    // Implementation would depend on your IndexedDB setup
}

async function getPendingWishlistActions() {
    // Implementation would depend on your IndexedDB setup
    return [];
}

async function removePendingWishlistAction(/* id */) {
    // Implementation would depend on your IndexedDB setup
}

// Push notification handling
self.addEventListener('push', event => {
    console.log('Service Worker: Push notification received');

    const options = {
        body: event.data ? event.data.text() : 'New notification from COPRRA',
        icon: '/icon-192x192.png',
        badge: '/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1,
        },
        actions: [
            {
                action: 'explore',
                title: 'View Details',
                icon: '/icon-192x192.png',
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/icon-192x192.png',
            },
        ],
    };

    event.waitUntil(self.registration.showNotification('COPRRA', options));
});

// Notification click handling
self.addEventListener('notificationclick', event => {
    console.log('Service Worker: Notification clicked');

    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(clients.openWindow('/'));
    } else if (event.action === 'close') {
        // Just close the notification
    } else {
        // Default action
        event.waitUntil(clients.openWindow('/'));
    }
});
