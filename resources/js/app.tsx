import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { initializeTheme } from './hooks/use-appearance';

function readAppNameFromPage(page: { props?: Record<string, unknown> }): string | undefined {
    const name = page.props?.name;

    return typeof name === 'string' ? name : undefined;
}

function readAppNameFromDocumentBody(): string {
    if (typeof document === 'undefined') {
        return import.meta.env.VITE_APP_NAME || 'Laravel';
    }

    return document.body?.dataset.appName || import.meta.env.VITE_APP_NAME || 'Laravel';
}

let appNameForTitle = readAppNameFromDocumentBody();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appNameForTitle}` : appNameForTitle),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const fromInitial = readAppNameFromPage(props.initialPage);
        if (fromInitial) {
            appNameForTitle = fromInitial;
        }

        router.on('success', (event) => {
            const next = readAppNameFromPage(event.detail.page);
            if (next) {
                appNameForTitle = next;
            }
        });

        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
