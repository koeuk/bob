import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Keep the browser-tab favicon in sync with the branding logo. The blade
// <head> only renders on a full page load, so update it on Inertia visits too.
const syncFavicon = (page) => {
    const logo = page?.props?.branding?.logo;
    if (!logo) return;
    let link = document.querySelector('link[rel="icon"]');
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }
    link.href = logo;
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        syncFavicon(props.initialPage);
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#4B5563' },
});

router.on('navigate', (event) => syncFavicon(event.detail.page));

initializeTheme();
