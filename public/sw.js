/**
 * ManageTrans service worker.
 *
 * Scope is deliberately narrow: only versioned static theme assets are cached, plus an
 * offline fallback page. HTML responses are never cached, because every page embeds a
 * CSRF token and session-specific content — serving a stale one causes 419 errors and
 * leaks one user's page to the next.
 *
 * Bump CACHE_VERSION whenever the precache list changes.
 */
const CACHE_VERSION = 'v2';
const STATIC_CACHE = `managetrans-static-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    OFFLINE_URL,
    '/assets/css/bootstrap.min.css',
    '/assets/css/icons.min.css',
    '/assets/css/app.min.css',
    '/assets/css/custom.css',
    '/assets/css/dark-mode-custom.css',
    '/assets/js/layout.js',
    '/assets/js/app.js',
    '/assets/libs/bootstrap/js/bootstrap.bundle.min.js',
    '/assets/images/pwa/icon-192.png',
    '/assets/images/pwa/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) =>
            // A single missing file must not fail the whole install.
            Promise.all(PRECACHE.map((url) => cache.add(url).catch(() => null)))
        ).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key.startsWith('managetrans-static-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'skip-waiting') {
        self.skipWaiting();
    }
});

/**
 * Theme assets are content-addressed by path and rarely change, so they are safe to
 * serve from cache while a fresh copy is fetched in the background.
 */
function isCacheableAsset(url) {
    return url.pathname.startsWith('/assets/');
}

/**
 * Store a response and drop superseded revisions of the same file.
 *
 * Asset URLs carry a `?v=<mtime>` cache buster, so a new entry is created every time a
 * file changes. Without this the cache would grow with every deploy.
 */
async function putAndPrune(cache, request, response) {
    const url = new URL(request.url);

    if (url.search) {
        const stale = (await cache.keys()).filter((key) => {
            const keyUrl = new URL(key.url);

            return keyUrl.pathname === url.pathname && keyUrl.search !== url.search;
        });

        await Promise.all(stale.map((key) => cache.delete(key)));
    }

    await cache.put(request, response);
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    const network = fetch(request)
        .then((response) => {
            if (response && response.ok && response.type === 'basic') {
                putAndPrune(cache, request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    if (cached) {
        return cached;
    }

    const response = await network;

    if (response) {
        return response;
    }

    throw new Error(`Unable to fetch ${request.url}`);
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        // Always go to the network for pages; fall back to the offline shell only
        // when the device is genuinely unreachable.
        event.respondWith(
            fetch(request).catch(async () => {
                const cache = await caches.open(STATIC_CACHE);
                const offline = await cache.match(OFFLINE_URL);

                return offline || new Response('You are offline.', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain' },
                });
            })
        );
        return;
    }

    if (isCacheableAsset(url)) {
        event.respondWith(staleWhileRevalidate(request).catch(() => fetch(request)));
    }
});
