const CACHE_PAGES  = 'ect-bv-pages-v2';
const CACHE_STATIC = 'ect-bv-static-v1';
const CACHE_FONTS  = 'ect-bv-fonts-v1';
const CACHE_SHELL  = 'ect-bv-shell-v1';

const ALL_CACHES = [CACHE_PAGES, CACHE_STATIC, CACHE_FONTS, CACHE_SHELL];
const SHELL_KEY  = '_app_shell_';

// ── Install: take over immediately ───────────────────────────────────────────
self.addEventListener('install', () => self.skipWaiting());

// ── Activate: clean stale caches, claim all clients ──────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((k) => !ALL_CACHES.includes(k))
                    .map((k) => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

// ── Fetch ────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET (POST submissions go through normally / offline queue handles them)
    if (request.method !== 'GET') return;

    // Skip non-HTTP schemes (chrome-extension, etc.)
    if (!url.protocol.startsWith('http')) return;

    // External fonts → CacheFirst (long-lived, never change)
    if (url.hostname === 'fonts.bunny.net' || url.hostname === 'fonts.googleapis.com') {
        event.respondWith(cacheFirst(CACHE_FONTS, request));
        return;
    }

    // Fingerprinted build assets → CacheFirst (content-hashed filenames)
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(CACHE_STATIC, request));
        return;
    }

    // Everything else (HTML pages, Inertia XHR) → NetworkFirst with cache fallback
    event.respondWith(networkFirst(CACHE_PAGES, request));
});

// ── Strategies ───────────────────────────────────────────────────────────────

async function cacheFirst(cacheName, request) {
    const cache  = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok) cache.put(request, response.clone());
    return response;
}

async function networkFirst(cacheName, request) {
    const cache = await caches.open(cacheName);
    const isNavigate = request.mode === 'navigate';
    const isInertia  = request.headers.get('X-Inertia') === 'true';

    try {
        const response = await fetch(request);

        if (response.ok && response.type !== 'opaque') {
            cache.put(request, response.clone());

            // When we get a full HTML page (non-Inertia), save a copy as the
            // app shell. This is used later as the offline fallback for any
            // navigation request — the Inertia client-side will handle routing.
            if (isNavigate && !isInertia) {
                const shellCache = await caches.open(CACHE_SHELL);
                shellCache.put(SHELL_KEY, response.clone());
            }
        }

        return response;
    } catch {
        // Network failed — try exact cache match (ignoreVary so Inertia XHR
        // responses can match even when the Vary: X-Inertia header differs)
        const cached = await cache.match(request, { ignoreVary: true });
        if (cached) return cached;

        // For page navigations, serve the saved app shell so the Inertia
        // client-side boots with a real HTML page instead of a 503 error.
        if (isNavigate) {
            const shellCache = await caches.open(CACHE_SHELL);
            const shell = await shellCache.match(SHELL_KEY);
            if (shell) return shell;
        }

        // Last resort
        return new Response(
            'You are offline and this page has not been cached yet. Please connect and reload.',
            { status: 503, headers: { 'Content-Type': 'text/plain' } }
        );
    }
}
