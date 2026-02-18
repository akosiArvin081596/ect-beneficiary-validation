const CACHE_PAGES  = 'ect-bv-pages-v4';
const CACHE_STATIC = 'ect-bv-static-v1';
const CACHE_FONTS  = 'ect-bv-fonts-v1';
const CACHE_SHELL  = 'ect-bv-shell-v2';

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

    // Skip non-GET
    if (request.method !== 'GET') return;

    // Skip non-HTTP schemes (chrome-extension, etc.)
    if (!url.protocol.startsWith('http')) return;

    // External fonts → CacheFirst
    if (url.hostname === 'fonts.bunny.net' || url.hostname === 'fonts.googleapis.com') {
        event.respondWith(cacheFirst(CACHE_FONTS, request));
        return;
    }

    // Fingerprinted build assets → CacheFirst
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(CACHE_STATIC, request));
        return;
    }

    // Everything else (HTML pages, Inertia XHR) → NetworkFirst
    event.respondWith(networkFirst(request));
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

async function networkFirst(request) {
    const cache = await caches.open(CACHE_PAGES);
    const isNavigate = request.mode === 'navigate';
    const isInertia  = request.headers.get('X-Inertia') === 'true';

    // Use URL string as cache key so header differences (X-Inertia,
    // X-Inertia-Version, Vary, etc.) never cause cache misses.
    const cacheKey = request.url;

    try {
        const response = await fetch(request);

        if (response.ok && response.type !== 'opaque') {
            // Store by URL string — both Inertia XHR and full navigations
            // for the same URL will overwrite each other, which is what we want
            // (latest response wins).
            cache.put(cacheKey, response.clone());

            // Save full HTML pages as the app shell for offline fallback.
            if (isNavigate && !isInertia) {
                const shellCache = await caches.open(CACHE_SHELL);
                shellCache.put(SHELL_KEY, response.clone());
            }
        }

        return response;
    } catch {
        // Network failed — look up cache by URL string
        const cached = await cache.match(cacheKey);

        if (cached) {
            const ct = cached.headers.get('Content-Type') || '';
            const isJSON = ct.includes('application/json');

            // Inertia XHR offline navigation → return cached JSON directly
            if (!isNavigate) {
                return cached;
            }

            // Full page refresh → cached entry might be Inertia JSON.
            // Build an HTML page by injecting the JSON into the app shell.
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
        // event instead of showing a modal with a plain-text body.
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
 * Build an HTML page by injecting cached Inertia JSON into the app shell.
 * The shell's data-page attribute is replaced so Inertia boots with the
 * correct component and props.
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

        const encoded = JSON.stringify(pageData)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;');

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
