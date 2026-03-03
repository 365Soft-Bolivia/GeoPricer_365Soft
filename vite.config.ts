// wayfinder plugin removed temporarily to avoid executing PHP artisan during JS build
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        // NOTE: The wayfinder plugin was removed here to prevent the build from
        // invoking `php artisan wayfinder:generate` on environments where the
        // PHP CLI does not match composer requirements. If you have PHP >= 8.2
        // on your CLI, re-add the plugin:
        // wayfinder({ formVariants: true }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
