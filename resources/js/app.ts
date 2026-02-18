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
        showOfflineToast();
        return false;
    }
});

// "invalid" fires on non-Inertia responses (e.g. 503 plain text from old SW).
// Returning false cancels the default modal.
router.on('invalid', () => {
    if (!navigator.onLine) {
        showOfflineToast();
        return false;
    }
});

function showOfflineToast() {
    document.getElementById('offline-toast')?.remove();

    const toast = document.createElement('div');
    toast.id = 'offline-toast';
    toast.textContent = 'You are offline. This page has not been cached yet.';
    Object.assign(toast.style, {
        position: 'fixed',
        bottom: '1.5rem',
        right: '1.5rem',
        zIndex: '9999',
        padding: '0.75rem 1rem',
        borderRadius: '0.5rem',
        fontSize: '0.875rem',
        color: '#1e40af',
        backgroundColor: '#eff6ff',
        border: '1px solid #bfdbfe',
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
