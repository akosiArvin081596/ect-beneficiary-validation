const CACHE_PAGES  = 'ect-bv-pages-v3';
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

            // Save full HTML pages as the app shell for offline fallback.
            if (isNavigate && !isInertia) {
                const shellCache = await caches.open(CACHE_SHELL);
                shellCache.put(SHELL_KEY, response.clone());
            }
        }

        return response;
    } catch {
        const cached = await cache.match(request, { ignoreVary: true });

        if (cached) {
            const isJSON = (cached.headers.get('Content-Type') || '').includes('application/json');

            // Inertia XHR → return cached JSON directly (normal offline navigation)
            if (!isNavigate) {
                return cached;
            }

            // Full page refresh with cached Inertia JSON → build an HTML page
            // by injecting the JSON into the app shell so Inertia can boot.
            if (isJSON) {
                const html = await buildOfflineHtml(cached);
                if (html) return html;
            }

            // Cached HTML → return directly
            return cached;
        }

        // No cache at all → serve app shell for navigations
        if (isNavigate) {
            const shellCache = await caches.open(CACHE_SHELL);
            const shell = await shellCache.match(SHELL_KEY);
            if (shell) return shell;
        }

        // Inertia XHR with no cache → network error so Inertia fires "exception"
        if (isInertia) {
            return Response.error();
        }

        // Last resort
        return new Response(
            'You are offline and this page has not been cached yet. Please connect and reload.',
            { status: 503, headers: { 'Content-Type': 'text/plain' } }
        );
    }
}

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Build an HTML page from the app shell + cached Inertia JSON.
 * Replaces the data-page attribute in the shell with the cached page data
 * so Inertia boots with the correct component and props.
 */
async function buildOfflineHtml(cachedJsonResponse) {
    try {
        const shellCache = await caches.open(CACHE_SHELL);
        const shell = await shellCache.match(SHELL_KEY);
        if (!shell) return null;

        const [shellHtml, pageData] = await Promise.all([
            shell.text(),
            cachedJsonResponse.json(),
        ]);

        // The shell contains: <div id="app" data-page="...encoded json..."></div>
        // Replace the data-page value with the cached Inertia page data.
        const encoded = JSON.stringify(pageData).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        const html = shellHtml.replace(
            /data-page="[^"]*"/,
            `data-page="${encoded}"`
        );

        return new Response(html, {
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        });
    } catch {
        return null;
    }
}
