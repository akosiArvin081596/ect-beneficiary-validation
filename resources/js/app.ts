import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// ── Offline error handling ────────────────────────────────────────────────────
// Prevent Inertia from showing modals / throwing when offline.
//
// "exception" fires on network errors (fetch rejection / Response.error()).
// Returning false cancels the default re-throw.
router.on('exception', () => {
    if (!navigator.onLine) {
        showToast(
            'You are offline. This page has not been cached yet.',
            'error',
        );
        return false;
    }
});

// "invalid" fires on non-Inertia responses (e.g. 503 plain text from old SW).
// Returning false cancels the default modal.
router.on('invalid', () => {
    if (!navigator.onLine) {
        showToast(
            'You are offline. This page has not been cached yet.',
            'error',
        );
        return false;
    }
});

const TOAST_STYLES: Record<
    string,
    { color: string; bg: string; border: string }
> = {
    success: { color: '#166534', bg: '#f0fdf4', border: '#bbf7d0' },
    error: { color: '#991b1b', bg: '#fef2f2', border: '#fecaca' },
};

function showToast(message: string, type: 'success' | 'error') {
    document.getElementById('offline-toast')?.remove();

    const s = TOAST_STYLES[type];
    const toast = document.createElement('div');
    toast.id = 'offline-toast';
    toast.textContent = message;
    Object.assign(toast.style, {
        position: 'fixed',
        bottom: '1.5rem',
        right: '1.5rem',
        zIndex: '9999',
        padding: '0.75rem 1rem',
        borderRadius: '0.5rem',
        fontSize: '0.875rem',
        color: s.color,
        backgroundColor: s.bg,
        border: `1px solid ${s.border}`,
        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
    });
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// This will set light / dark mode on page load...
initializeTheme();

// Register service worker for offline support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}

// ── Precache key pages after login ───────────────────────────────────────────
// Fetches key pages in parallel so the SW caches them before the user goes
// offline. Shows a toast when caching is complete so the user knows it's safe.
let precached = false;

router.on('navigate', (event) => {
    if (precached) return;
    const page = (event as CustomEvent).detail?.page;
    if (!page?.props?.auth?.user) return;

    precached = true;

    const pages = ['/dashboard', '/beneficiaries', '/beneficiaries/create'];

    navigator.serviceWorker.ready.then(() => {
        const fetches = pages
            .filter((url) => url !== page.url)
            .map((url) =>
                fetch(url, {
                    headers: {
                        'X-Inertia': 'true',
                        Accept: 'text/html, application/xhtml+xml',
                    },
                    credentials: 'same-origin',
                }),
            );

        Promise.all(fetches)
            .then(() => showToast('Ready for offline use.', 'success'))
            .catch(() =>
                showToast(
                    'Some pages could not be cached for offline use.',
                    'error',
                ),
            );
    });
});
