<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Teacher Workflow Optimization Platform - Streamline administrative tasks, enhance instructional efficiency, and foster collaboration between teachers, students, and parents.">

        <title>{{ config('app.name', 'Teacher Workflow') }} - Optimize Your Teaching Experience</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:300,400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
        
            {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
            <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="antialiased min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col">
        <div class="flex-grow flex flex-col">
            <!-- Header Component -->
            <x-landing.header />

            <!-- Main Content -->
            <main class="flex-grow">
                <!-- Hero Section Component -->
                <x-landing.hero />

                <!-- Features Section Component -->
                <x-landing.features />

                <!-- Pricing Section Component -->
                <x-landing.pricing />
                
                <!-- Pricing Comparison Component -->
                <x-landing.pricing-comparison />

                <!-- Testimonials Section Component -->
                <x-landing.testimonials />

                <!-- FAQ Section Component -->
                <x-landing.faq />
            </main>

            <!-- Footer Component -->
            <x-landing.footer />
        </div>
    </body>
</html>
