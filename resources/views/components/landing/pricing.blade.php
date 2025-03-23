<!-- Pricing Section -->
<section id="pricing" class="py-12 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-base font-semibold text-primary-600 dark:text-primary-400 tracking-wide uppercase">Pricing</h2>
            <p class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
                Plans for teachers of all levels
            </p>
            <p class="mt-4 max-w-2xl text-xl text-gray-600 dark:text-gray-300 mx-auto">
                Choose the plan that works best for your teaching needs. All plans include our core features.
            </p>
        </div>

        <!-- Billing Toggle -->
        <div class="mt-12 flex justify-center" x-data="{ annual: false }">
            <div class="relative flex rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
                <span class="sr-only">Toggle billing frequency</span>
                <button type="button" @click="annual = false" :class="{ 'bg-white dark:bg-gray-700 shadow-sm': !annual, 'text-gray-500 dark:text-gray-400': annual }" class="relative py-2 px-6 rounded-md text-sm font-medium whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary-500 focus:z-10">
                    Monthly
                </button>
                <button type="button" @click="annual = true" :class="{ 'bg-white dark:bg-gray-700 shadow-sm': annual, 'text-gray-500 dark:text-gray-400': !annual }" class="ml-0.5 relative py-2 px-6 rounded-md text-sm font-medium whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary-500 focus:z-10">
                    Annual
                </button>
            </div>
            <div class="ml-4 flex items-center">
                <span class="text-sm text-gray-500 dark:text-gray-400 italic">Save up to 20%</span>
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    Best Value
                </span>
            </div>
        </div>

        <!-- Money-back guarantee badge -->
        <div class="flex justify-center mt-4">
            <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                30-day money-back guarantee
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="mt-12 space-y-4 sm:mt-16 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-6 lg:max-w-4xl lg:mx-auto xl:max-w-none xl:mx-0 xl:grid-cols-3">
            <!-- Starter Plan -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm divide-y divide-gray-200 dark:divide-gray-700">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Starter</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Perfect for individual teachers just getting started.</p>
                    <p class="mt-4">
                        <span class="text-4xl font-extrabold text-gray-900 dark:text-white" x-text="annual ? '$15' : '$19'">$19</span>
                        <span class="text-base font-medium text-gray-500 dark:text-gray-400">/month</span>
                    </p>
                    <p class="mt-1">
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-show="annual">Billed annually ($180/year)</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-show="!annual">Billed monthly</span>
                    </p>
                    <a href="{{ route('register') }}" class="mt-8 block w-full bg-primary-600 border border-transparent rounded-md py-2 text-sm font-semibold text-white text-center hover:bg-primary-700">Get started</a>
                </div>
                <div class="pt-6 pb-8 px-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">What's included</h4>
                    <ul class="mt-4 space-y-3">
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">AI-powered lesson planning</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Basic student tracking</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Up to 30 students</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">1 GB storage</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Email support</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Professional Plan -->
            <div class="border border-primary-200 dark:border-primary-700 rounded-lg shadow-sm divide-y divide-gray-200 dark:divide-gray-700">
                <div class="p-6 relative">
                    <span class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/2 inline-flex px-4 py-1 rounded-full text-sm font-semibold tracking-wide uppercase bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100">
                        Popular
                    </span>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Professional</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">For experienced teachers who need more advanced features.</p>
                    <p class="mt-4">
                        <span class="text-4xl font-extrabold text-gray-900 dark:text-white" x-text="annual ? '$39' : '$49'">$49</span>
                        <span class="text-base font-medium text-gray-500 dark:text-gray-400">/month</span>
                    </p>
                    <p class="mt-1">
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-show="annual">Billed annually ($468/year)</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-show="!annual">Billed monthly</span>
                    </p>
                    <a href="{{ route('register') }}" class="mt-8 block w-full bg-primary-600 border border-transparent rounded-md py-2 text-sm font-semibold text-white text-center hover:bg-primary-700">Get started</a>
                </div>
                <div class="pt-6 pb-8 px-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">What's included</h4>
                    <ul class="mt-4 space-y-3">
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Everything in Starter</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Advanced analytics</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Up to 100 students</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">10 GB storage</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Priority email support</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Parent communication tools</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Enterprise Plan -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm divide-y divide-gray-200 dark:divide-gray-700">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Enterprise</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">For schools and districts with advanced requirements.</p>
                    <p class="mt-4">
                        <span class="text-4xl font-extrabold text-gray-900 dark:text-white" x-text="annual ? '$79' : '$99'">$99</span>
                        <span class="text-base font-medium text-gray-500 dark:text-gray-400">/month</span>
                    </p>
                    <p class="mt-1">
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-show="annual">Billed annually ($948/year)</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-show="!annual">Billed monthly</span>
                    </p>
                    <a href="#" class="mt-8 block w-full bg-primary-600 border border-transparent rounded-md py-2 text-sm font-semibold text-white text-center hover:bg-primary-700">Contact sales</a>
                </div>
                <div class="pt-6 pb-8 px-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">What's included</h4>
                    <ul class="mt-4 space-y-3">
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Everything in Professional</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Unlimited students</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">100 GB storage</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">24/7 phone & email support</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Custom integrations</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Dedicated account manager</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
