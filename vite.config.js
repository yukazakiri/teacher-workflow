import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import findMingles from './vendor/ijpatricio/mingle/resources/js/autoImport.js'
const mingles = findMingles('resources/js')
// Optional: Output the mingles to the console, for a visual check
console.log('Auto-importing mingles:', mingles)
export default defineConfig({
    resolve: {
        alias: {
            "@mingle": path.resolve(__dirname, "/vendor/ijpatricio/mingle/resources/js"),
        },
    },
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        laravel({
            input: [
                'resources/css/filament/app/theme.css',
                'resources/js/app.js',
                ...mingles,
                // './vendor/tomatophp/filament-simple-theme/resources/css/theme.css'
            ],
            refresh: true,
        }),
    ],
});
