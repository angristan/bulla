import '@mantine/core/styles.css';
import '@mantine/notifications/styles.css';
import '@mantine/spotlight/styles.css';
import '@mantine/dropzone/styles.css';
import '@mantine/charts/styles.css';

import { createInertiaApp } from '@inertiajs/react';
import { MantineProvider, createTheme } from '@mantine/core';
import { ModalsProvider } from '@mantine/modals';
import { Notifications } from '@mantine/notifications';
import { createRoot } from 'react-dom/client';

const theme = createTheme({
    primaryColor: 'blue',
    defaultRadius: 'md',
});

createInertiaApp({
    title: (title) => (title ? `${title} - Opaska` : 'Opaska'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
        return pages[`./Pages/${name}.tsx`];
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <MantineProvider theme={theme} defaultColorScheme="auto">
                <ModalsProvider>
                    <Notifications position="top-right" />
                    <App {...props} />
                </ModalsProvider>
            </MantineProvider>,
        );
    },
});
