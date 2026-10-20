import './bootstrap';
import '../css/app.css';
import './Utils/webNotification';
import { updateBrowserFavicon } from './Utils/branding';
import InAppToastContainer from './Components/InAppToastContainer';

import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'ITDeskService';

if (typeof window !== 'undefined') {
    router.on('navigate', (event) => {
        const favicon = event.detail.page?.props?.branding?.app_favicon;
        if (favicon) {
            updateBrowserFavicon(favicon);
        }
    });

    router.on('success', (event) => {
        const favicon = event.detail.page?.props?.branding?.app_favicon;
        if (favicon) {
            updateBrowserFavicon(favicon);
        }
    });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const initialFavicon = props.initialPage?.props?.branding?.app_favicon;
        if (initialFavicon) {
            updateBrowserFavicon(initialFavicon);
        }

        const root = createRoot(el);
        root.render(
            <>
                <App {...props} />
                <InAppToastContainer />
            </>
        );
    },
    progress: {
        color: '#4f46e5',
    },
});
