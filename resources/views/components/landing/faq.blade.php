<!-- FAQ Section -->
<section id="faq" class="py-12 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-base font-semibold text-primary-600 dark:text-primary-400 tracking-wide uppercase">FAQ</h2>
            <p class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
                Frequently asked questions
            </p>
            <p class="mt-4 max-w-2xl text-xl text-gray-600 dark:text-gray-300 mx-auto">
                Find answers to common questions about our platform.
            </p>
        </div>
        
        <div class="mt-12 max-w-3xl mx-auto">
            <dl class="space-y-6 divide-y divide-gray-200 dark:divide-gray-700" x-data="{selected:null}">
                <!-- Question 1 -->
                <div class="pt-6" x-data="{id: 1}" :class="{'pb-6': selected !== id}">
                    <dt>
                        <button type="button" class="text-left w-full flex justify-between items-start text-gray-900 dark:text-white" @click="selected !== id ? selected = id : selected = null">
                            <span class="text-lg font-medium">How does the AI-powered lesson planning work?</span>
                            <span class="ml-6 h-7 flex items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-200" :class="{'rotate-180': selected == id}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                    </dt>
                    <dd class="mt-2 pr-12 transition-all duration-200 ease-in-out" x-show="selected == id" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
                        <p class="text-base text-gray-600 dark:text-gray-300">
                            Our AI-powered lesson planning tool analyzes curriculum standards, your teaching style, and student needs to generate personalized lesson plans. You can customize templates, add your own content, and generate creative activities to engage your students. The AI continuously learns from your preferences to improve future suggestions.
                        </p>
                    </dd>
                </div>
                
                <!-- Question 2 -->
                <div class="pt-6" x-data="{id: 2}" :class="{'pb-6': selected !== id}">
                    <dt>
                        <button type="button" class="text-left w-full flex justify-between items-start text-gray-900 dark:text-white" @click="selected !== id ? selected = id : selected = null">
                            <span class="text-lg font-medium">Is my data secure on your platform?</span>
                            <span class="ml-6 h-7 flex items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-200" :class="{'rotate-180': selected == id}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                    </dt>
                    <dd class="mt-2 pr-12 transition-all duration-200 ease-in-out" x-show="selected == id" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
                        <p class="text-base text-gray-600 dark:text-gray-300">
                            Yes, we take data security and privacy very seriously. Our platform uses industry-standard encryption for all data in transit and at rest. We comply with educational data privacy regulations and never share your data with third parties without your explicit consent. You maintain ownership of all your content and student information.
                        </p>
                    </dd>
                </div>
                
                <!-- Question 3 -->
                <div class="pt-6" x-data="{id: 3}" :class="{'pb-6': selected !== id}">
                    <dt>
                        <button type="button" class="text-left w-full flex justify-between items-start text-gray-900 dark:text-white" @click="selected !== id ? selected = id : selected = null">
                            <span class="text-lg font-medium">Can I integrate with other educational tools I already use?</span>
                            <span class="ml-6 h-7 flex items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-200" :class="{'rotate-180': selected == id}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                    </dt>
                    <dd class="mt-2 pr-12 transition-all duration-200 ease-in-out" x-show="selected == id" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
                        <p class="text-base text-gray-600 dark:text-gray-300">
                            Yes, our platform offers integrations with popular educational tools like Google Classroom, Microsoft Teams, Canvas, and more. The Professional and Enterprise plans include more advanced integration options. If you need a custom integration, our Enterprise plan includes support for developing specialized connections to your existing systems.
                        </p>
                    </dd>
                </div>
                
                <!-- Question 4 -->
                <div class="pt-6" x-data="{id: 4}" :class="{'pb-6': selected !== id}">
                    <dt>
                        <button type="button" class="text-left w-full flex justify-between items-start text-gray-900 dark:text-white" @click="selected !== id ? selected = id : selected = null">
                            <span class="text-lg font-medium">How do I get started with the platform?</span>
                            <span class="ml-6 h-7 flex items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-200" :class="{'rotate-180': selected == id}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                    </dt>
                    <dd class="mt-2 pr-12 transition-all duration-200 ease-in-out" x-show="selected == id" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
                        <p class="text-base text-gray-600 dark:text-gray-300">
                            Getting started is easy! Simply sign up for an account, choose your plan, and you'll have immediate access to the platform. We offer a guided onboarding process with tutorials and templates to help you set up your classes and start using the features. Our support team is also available to assist you with any questions during the setup process.
                        </p>
                    </dd>
                </div>
                
                <!-- Question 5 -->
                <div class="pt-6" x-data="{id: 5}" :class="{'pb-6': selected !== id}">
                    <dt>
                        <button type="button" class="text-left w-full flex justify-between items-start text-gray-900 dark:text-white" @click="selected !== id ? selected = id : selected = null">
                            <span class="text-lg font-medium">Can I change my plan later?</span>
                            <span class="ml-6 h-7 flex items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-200" :class="{'rotate-180': selected == id}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                    </dt>
                    <dd class="mt-2 pr-12 transition-all duration-200 ease-in-out" x-show="selected == id" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
                        <p class="text-base text-gray-600 dark:text-gray-300">
                            Yes, you can upgrade or downgrade your plan at any time through your account settings. When upgrading, you'll immediately gain access to the additional features. If you downgrade, the changes will take effect at the end of your current billing cycle. We also offer flexible options for schools and districts that may need to adjust their plans based on changing needs throughout the school year.
                        </p>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</section>
