import preact from '@preact/preset-vite';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [preact()],
    resolve: {
        alias: {
            react: 'preact/compat',
            'react-dom': 'preact/compat',
            'react/jsx-runtime': 'preact/jsx-runtime',
        },
    },
    test: {
        environment: 'jsdom',
        include: ['src/**/*.test.{ts,tsx}'],
        globals: true,
        setupFiles: ['./src/test-setup.ts'],
    },
});
