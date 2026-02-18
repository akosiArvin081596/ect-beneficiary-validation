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

// Prevent Inertia from showing a modal when navigating offline.
// The SW returns Response.error() for uncached Inertia XHR requests,
// which causes a network error that triggers this callback.
router.on('error', () => {
    if (!navigator.onLine) {
        showOfflineToast();
        return true; // prevent default handling
    }
});

// Non-Inertia responses (e.g. 503 from SW) trigger the "invalid" event.
// Returning false prevents the default modal behavior.
router.on('invalid', (event) => {
    if (!navigator.onLine) {
        event.preventDefault();
        showOfflineToast();
    }
});

function showOfflineToast() {
    // Remove existing offline toast if present
    document.getElementById('offline-toast')?.remove();

    const toast = document.createElement('div');
    toast.id = 'offline-toast';
    toast.textContent = 'You are offline. Please reconnect to navigate.';
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
