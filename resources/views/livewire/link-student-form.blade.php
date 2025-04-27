<div>
    @if($showModal)
        <div 
            x-data="{ show: @entangle('showModal') }"
            x-show="show"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto p-4 pt-[15vh] flex items-center justify-center bg-gray-900/50 dark:bg-gray-900/75"
        >
            <div 
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden"
            >
                <div class="p-6">
                    <div class="text-center mb-5">
                        <div class="mb-4 inline-flex items-center justify-center w-14 h-14 rounded-full bg-primary-100 text-primary-600 dark:bg-primary-700/20 dark:text-primary-400">
                            <x-heroicon-o-user-plus class="w-8 h-8" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('Link to Your Student') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('To view your child\'s academic information, please enter their student ID.') }}
                        </p>
                    </div>

                    <form wire:submit="linkStudent" class="space-y-4">
                        <div>
                            <label for="studentId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Student ID') }}
                            </label>
                            <div class="mt-1">
                                <input 
                                    wire:model="studentId" 
                                    type="text" 
                                    id="studentId" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Enter student ID"
                                    required
                                >
                            </div>
                        </div>

                        @if($error)
                            <div class="px-4 py-3 rounded-md bg-danger-50 text-danger-700 dark:bg-danger-900/50 dark:text-danger-400 text-sm">
                                {{ $error }}
                            </div>
                        @endif

                        <div class="mt-6 flex items-center justify-end gap-x-3">
                            <button
                                type="button"
                                wire:click="$set('showModal', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-400"
                            >
                                {{ __('Skip for now') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                            >
                                <x-heroicon-m-link class="-ml-1 mr-2 h-4 w-4" />
                                {{ __('Link Student') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
