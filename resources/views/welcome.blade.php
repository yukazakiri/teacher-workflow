<x-guest-layout>
    <div class="flex flex-col min-h-screen">
        <!-- Header Component -->
        <x-landing.header />

        <!-- Main Content -->
        <main class="flex-grow">
            <!-- Hero Section Component -->
            <x-landing.hero />

            <!-- Features Section Component -->
            <section id="features" class="py-24 bg-gradient-to-b from-white to-gray-50 dark:from-gray-900 dark:to-gray-800">
                <x-landing.features />
            </section>

            <!-- Pricing Section Component -->
            <section id="pricing" class="py-24 bg-white dark:bg-gray-900">
                <x-landing.pricing />
            </section>
            
            <!-- Pricing Comparison Component -->
            <section class="py-16 bg-gray-50 dark:bg-gray-800">
                <x-landing.pricing-comparison />
            </section>

            <!-- Testimonials Section Component -->
            <section id="testimonials" class="py-24 bg-white dark:bg-gray-900">
                <x-landing.testimonials />
            </section>

            <!-- FAQ Section Component -->
            <section id="faq" class="py-24 bg-gray-50 dark:bg-gray-800">
                <x-landing.faq />
            </section>
        </main>

        <!-- Footer Component -->
        <x-landing.footer />
    </div>
</x-guest-layout>
