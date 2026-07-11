const CACHE_NAME = 'apf-pos-cache-v1';
const ASSETS = [
    '/pos',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
];

// Install Service Worker and cache core shell assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('[Service Worker] Pre-caching offline POS shell');
            return cache.addAll(ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activate SW and clean up older caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        console.log('[Service Worker] Clearing old cache', key);
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch events: Stale-While-Revalidate for static assets, network-only for APIs
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip POST requests (they can't be cached anyway) and sync API calls
    if (event.request.method !== 'GET' || url.pathname.includes('/api/pos/')) {
        return;
    }

    // Serve static assets and CDN calls from Cache, fetching updates in the background
    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            const fetchPromise = fetch(event.request).then(networkResponse => {
                // If response is valid, update the cache
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Ignore network errors when fetching updates
            });

            // Return cached response immediately if exists, otherwise wait for network fetch
            return cachedResponse || fetchPromise;
        })
    );
});
