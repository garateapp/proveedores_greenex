import '../css/app.css';
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Fragment, StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        const AppWrapper = import.meta.env.DEV ? Fragment : StrictMode;

        root.render(
            <AppWrapper>
                <App {...props} />
            </AppWrapper>,
        );
    },
    progress: {
        color: '#038c34',
    },
});

// This will set light / dark mode on load...
initializeTheme();
