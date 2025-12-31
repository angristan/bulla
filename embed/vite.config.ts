import preact from '@preact/preset-vite';
import { defineConfig } from 'vite';
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js';

export default defineConfig({
    plugins: [preact(), cssInjectedByJsPlugin()],
    resolve: {
        alias: {
            react: 'preact/compat',
            'react-dom': 'preact/compat',
            'react/jsx-runtime': 'preact/jsx-runtime',
        },
    },
    build: {
        outDir: '../public/embed',
        emptyOutDir: true,
        lib: {
            entry: 'src/index.tsx',
            name: 'Bulla',
            fileName: () => 'embed.js',
            formats: ['iife'],
        },
        rollupOptions: {
            output: {
                // Single file output
                inlineDynamicImports: true,
            },
        },
        minify: 'esbuild',
        cssCodeSplit: false,
    },
    css: {
        // Inject CSS into JS
        modules: {
            localsConvention: 'camelCase',
        },
    },
});
