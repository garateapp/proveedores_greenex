import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react({
            // babel: {
            //     plugins: ['babel-plugin-react-compiler'],
            // },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    build: {
        rollupOptions: {
            // Esto es el "freno de mano" para que no busque index.html
            input: {
                app: 'resources/js/app.tsx',
                css: 'resources/css/app.css'
            },
        },
    },


    // esbuild: {
    //     jsx: 'automatic',
    // },
    // define: {
    //      global: 'globalThis',
    //  },
    // optimizeDeps: {
    //     esbuildOptions: {
    //         define: {
    //             global: 'globalThis',
    //         },
    //     },
    // },
});
