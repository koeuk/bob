import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            // Suppress harmless "/* #__PURE__ */ annotation" warnings emitted
            // by bundled deps (e.g. @vueuse/core) under Rollup 4 / Vite 7.
            onwarn(warning, defaultHandler) {
                if (warning.message?.includes('#__PURE__')) return;
                defaultHandler(warning);
            },
        },
    },
});
