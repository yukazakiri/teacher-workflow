import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/assistant-engine/filament-assistant/resources/**/*.blade.php',
        './vendor/jaocero/radio-deck/resources/views/**/*.blade.php',
    ],
    plugins: [
        require('tailwind-scrollbar'),
    ],
}
