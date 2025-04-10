import preset from './vendor/filament/filament/tailwind.config.preset';

export default {
    presets: [preset],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/assistant-engine/filament-assistant/resources/**/*.blade.php',
        './resources/views/livewire/**/',
        './resources/views/components/**/'
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#eebebe',
                    '50': '#fdf6f6',
                    '100': '#fbeeee',
                    '200': '#f8dfdf',
                    '300': '#f4cfcf',
                    '400': '#f0bfbf',
                    '500': '#eebebe',
                    '600': '#e6a7a7',
                    '700': '#de9090',
                    '800': '#d67a7a',
                    '900': '#cc6363',
                    '950': '#c75757',
                },
                gray: {
                    DEFAULT: '#949cbb',
                    '500': '#949cbb',
                },
                info: {
                    DEFAULT: '#7287fd',
                    '500': '#7287fd',
                },
                danger: {
                    DEFAULT: '#f38ba8',
                    '500': '#f38ba8',
                },
                success: {
                    DEFAULT: '#a6d189',
                    '500': '#a6d189',
                },
                warning: {
                    DEFAULT: '#fe640b',
                    '500': '#fe640b',
                },
            }
        },
    },
    plugins: [
        require('tailwind-scrollbar'),
    ],
};