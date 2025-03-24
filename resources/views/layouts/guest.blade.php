<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Styles -->
        @livewireStyles
        
        <style>
            :root {
                --font-heading: 'Outfit', sans-serif;
                --font-body: 'Inter', sans-serif;
                --font-mono: 'JetBrains Mono', monospace;
            }
            
            h1, h2, h3, h4, h5, h6 {
                font-family: var(--font-heading);
            }
            
            body {
                font-family: var(--font-body);
            }
            
            code, pre {
                font-family: var(--font-mono);
            }
            
            .gradient-text {
                background-clip: text;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        </style>
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-gray-900 antialiased">
        <div class="font-sans text-gray-900 dark:text-gray-100">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
