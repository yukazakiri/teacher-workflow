<!-- Header Navigation - Filament Inspired -->
<header class="bg-white dark:bg-gray-900 backdrop-blur-md bg-opacity-95 dark:bg-opacity-95 z-50 sticky top-0 transition-all duration-300 border-b border-gray-100 dark:border-gray-800" 
       x-data="{ scrolled: false, mobileMenuOpen: false }" 
       @scroll.window="scrolled = (window.pageYOffset > 20)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" :class="{ 'py-2': scrolled, 'py-4': !scrolled }">
        <div class="flex justify-between items-center">
            <!-- Logo and Brand -->
            <div class="flex items-center space-x-2">
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('welcome') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 bg-gradient-to-tr from-primary-600 to-primary-400 rounded-md flex items-center justify-center shadow transition-all duration-300 group-hover:shadow-md group-hover:translate-y-[-2px]">
                            <span class="text-white font-bold text-xl">TW</span>
                        </div>
                        <span class="text-gray-900 dark:text-white font-bold text-xl tracking-tight">
                            <span class="bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300">
                                {{ config('app.name', 'Teacher Workflow') }}
                            </span>
                        </span>
                    </a>
                </div>
                
                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex md:ml-10 items-center">
                    <ul class="flex space-x-6">
                        <li>
                            <a href="#features" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                                Features
                            </a>
                        </li>
                        <li>
                            <a href="#pricing" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                                Pricing
                            </a>
                        </li>
                        <li>
                            <a href="#testimonials" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                                Testimonials
                            </a>
                        </li>
                        <li>
                            <a href="#faq" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                                FAQ
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- Login / Register Links -->
            <div class="hidden md:flex md:items-center space-x-5">
                @auth
                    <a href="{{ url('/dashboard') }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-transparent text-white bg-primary-600 shadow hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-300 hover:translate-y-[-2px]">
                        <span>Dashboard</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="relative overflow-hidden inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-gradient-to-r from-primary-600 to-primary-500 shadow hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-300 hover:translate-y-[-2px] group">
                            <span class="relative z-10">Get Started</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1.5 relative z-10 transition-transform duration-300 transform group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif
                @endauth
            </div>
            
            <!-- Mobile menu button -->
            <div class="flex md:hidden">
                <button type="button" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 transition-all duration-200" 
                        aria-controls="mobile-menu" 
                        aria-expanded="false" 
                        @click="mobileMenuOpen = !mobileMenuOpen">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="h-6 w-6" x-show="mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu -->
    <div class="md:hidden" id="mobile-menu" x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 transform -translate-y-2" 
         x-transition:enter-end="opacity-100 transform translate-y-0" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 transform translate-y-0" 
         x-transition:leave-end="opacity-0 transform -translate-y-2" 
         @click.away="mobileMenuOpen = false">
        <nav class="px-4 pt-2 pb-3 space-y-1 bg-white dark:bg-gray-900 border-t dark:border-gray-800">
            <a href="#features" class="block px-3 py-2 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                Features
            </a>
            <a href="#pricing" class="block px-3 py-2 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                Pricing
            </a>
            <a href="#testimonials" class="block px-3 py-2 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                Testimonials
            </a>
            <a href="#faq" class="block px-3 py-2 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                FAQ
            </a>
            
            <!-- Mobile Auth Links -->
            <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700 mt-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="block w-full px-3 py-2 text-base font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-all duration-200 text-center">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="mt-2 block w-full px-3 py-2 text-base font-medium rounded-md bg-gradient-to-r from-primary-600 to-primary-500 text-white hover:from-primary-700 hover:to-primary-600 transition-all duration-200 text-center">
                            Get Started
                        </a>
                    @endif
                @endauth
            </div>
        </nav>
    </div>
</header>
