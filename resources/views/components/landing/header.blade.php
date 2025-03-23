<!-- Header Navigation -->
<header class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 sticky top-0 z-50 transition-all duration-300" 
       x-data="{ scrolled: false, mobileMenuOpen: false }" 
       @scroll.window="scrolled = (window.pageYOffset > 20)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and Brand -->
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('welcome') }}" class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-primary-600 rounded-md flex items-center justify-center">
                            <span class="text-white font-bold text-sm">TW</span>
                        </div>
                        <span class="text-gray-900 dark:text-white font-bold text-lg tracking-tight">
                            {{ config('app.name', 'Teacher Workflow') }}
                        </span>
                    </a>
                </div>
                
                <!-- Desktop Navigation Links -->
                <nav class="hidden md:ml-8 md:flex md:items-center md:space-x-4">
                    <a href="#features" class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                        Features
                    </a>
                    <a href="#pricing" class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                        Pricing
                    </a>
                    <a href="#testimonials" class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                        Testimonials
                    </a>
                    <a href="#faq" class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                        FAQ
                    </a>
                </nav>
            </div>
            
            <!-- Right Side Navigation -->
            <div class="hidden md:flex md:items-center md:space-x-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                            Get Started
                        </a>
                    @endif
                @endauth
                
                <!-- Theme Toggle (Optional) -->
                <button type="button" class="p-1 text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none">
                    <span class="sr-only">Toggle theme</span>
                    <svg x-show="!$store.darkMode.on" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                    </svg>
                    <svg x-show="$store.darkMode.on" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            
            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <button type="button" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500" 
                        @click="mobileMenuOpen = !mobileMenuOpen">
                    <span class="sr-only">Open main menu</span>
                    <svg x-show="!mobileMenuOpen" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileMenuOpen" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t border-gray-200 dark:border-gray-800">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white dark:bg-gray-900">
            <a href="#features" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                Features
            </a>
            <a href="#pricing" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                Pricing
            </a>
            <a href="#testimonials" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                Testimonials
            </a>
            <a href="#faq" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                FAQ
            </a>
        </div>
        
        <div class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-800">
            <div class="px-2 space-y-1 sm:px-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-primary-600 hover:bg-primary-700 transition-colors duration-200">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-primary-600 hover:bg-primary-700 transition-colors duration-200">
                            Get Started
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</header>
