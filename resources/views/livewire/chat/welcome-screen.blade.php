<div class="flex-1 flex items-center justify-center">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Welcome to Teacher Workflow
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
            Your AI-powered teaching assistant. Start a conversation or try one of our quick actions below.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <button 
                type="button"
                wire:click="setPrompt('Polish this text to make it more professional and engaging:')"
                class="group relative rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors"
            >
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="text-primary-600 dark:text-primary-400">
                                <path d="M227.31,73.37l-44.68-44.69a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H92.69A15.86,15.86,0,0,0,104,219.31L227.31,96a16,16,0,0,0,0-22.63ZM92.69,208H48V163.31l88-88L180.69,120Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-left">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                            Polish Prose
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Make your text more professional and engaging
                        </p>
                    </div>
                </div>
            </button>

            <button 
                type="button"
                wire:click="setPrompt('Generate 5 thought-provoking questions about this topic:')"
                class="group relative rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors"
            >
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="text-primary-600 dark:text-primary-400">
                                <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-88a8,8,0,0,1,16,0v40a8,8,0,0,1-16,0Zm0-56a8,8,0,0,1,16,0v24a8,8,0,0,1-16,0Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-left">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                            Generate Questions
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Create thought-provoking questions for your students
                        </p>
                    </div>
                </div>
            </button>

            <button 
                type="button"
                wire:click="setPrompt('Write a detailed memo about this topic:')"
                class="group relative rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors"
            >
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="text-primary-600 dark:text-primary-400">
                                <path d="M216,32H40A16,16,0,0,0,24,48V208a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V48A16,16,0,0,0,216,32ZM40,48H216V96H40ZM216,208H40V112H216v96Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-left">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                            Write Memo
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Create detailed memos for your colleagues
                        </p>
                    </div>
                </div>
            </button>

            <button 
                type="button"
                wire:click="setPrompt('Summarize this text in a clear and concise way:')"
                class="group relative rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors"
            >
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="text-primary-600 dark:text-primary-400">
                                <path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM96,176V80l32,48,32-48v96a8,8,0,0,1-16,0Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-left">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                            Summarize Text
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Get clear and concise summaries of long texts
                        </p>
                    </div>
                </div>
            </button>

            <button 
                type="button"
                wire:click="setPrompt('Create a detailed lesson plan for this topic:')"
                class="group relative rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors"
            >
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="text-primary-600 dark:text-primary-400">
                                <path d="M216,32H40A16,16,0,0,0,24,48V208a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V48A16,16,0,0,0,216,32ZM40,48H216V96H40ZM216,208H40V112H216v96Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-left">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                            Lesson Plan
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Create detailed lesson plans for your classes
                        </p>
                    </div>
                </div>
            </button>

            <button 
                type="button"
                wire:click="setPrompt('Grade this assignment and provide detailed feedback:')"
                class="group relative rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors"
            >
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="text-primary-600 dark:text-primary-400">
                                <path d="M216,32H40A16,16,0,0,0,24,48V208a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V48A16,16,0,0,0,216,32ZM40,48H216V96H40ZM216,208H40V112H216v96Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-left">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                            Grade Assignment
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Get help grading assignments with detailed feedback
                        </p>
                    </div>
                </div>
            </button>
        </div>
    </div>
</div>
