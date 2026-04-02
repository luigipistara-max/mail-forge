const CACHE_VERSION = 'mail-forge-v1.0.0';
const STATIC_CACHE = [
    '/offline.html',
    '/assets/css/app.css',
    '/assets/js/app.js',
];

// Routes that must never be cached
const NO_CACHE_PATTERNS = [
    /^\/login/,
    /^\/logout/,
    /^\/forgot-password/,
    /^\/reset-password/,
    /^\/verify-email/,
    /^\/install/,
    /^\/settings/,
    /^\/campaigns\/[^/]+\/(schedule|queue|pause|cancel|send-test)/,
];

function isAuthOrAdminRoute(url) {
    const path = new URL(url).pathname;
    return NO_CACHE_PATTERNS.some(pattern => pattern.test(path));
}

function isStaticAsset(url) {
    return new URL(url).pathname.startsWith('/assets/');
}

// Precache static assets on install
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(cache => cache.addAll(STATIC_CACHE))
            .then(() => self.skipWaiting())
    );
});

// Remove outdated caches on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys
                    .filter(key => key !== CACHE_VERSION)
                    .map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

// Fetch strategy
self.addEventListener('fetch', event => {
    const { request } = event;

    // Only handle GET requests over HTTP/HTTPS
    if (request.method !== 'GET' || !request.url.startsWith('http')) {
        return;
    }

    // Never cache auth or admin routes — always go to network
    if (isAuthOrAdminRoute(request.url)) {
        event.respondWith(fetch(request));
        return;
    }

    // Cache-first for static assets
    if (isStaticAsset(request.url)) {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) {
                    return cached;
                }
                return fetch(request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_VERSION).then(cache => cache.put(request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Network-first for pages, fallback to offline.html
    event.respondWith(
        fetch(request)
            .then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_VERSION).then(cache => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request)
                .then(cached => cached || caches.match('/offline.html'))
            )
    );
});
