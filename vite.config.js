import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/filament/app/theme.css',
                'resources/js/app.js',
                // './vendor/tomatophp/filament-simple-theme/resources/css/theme.css'
            ],
            refresh: true,
        }),
    ],
});
