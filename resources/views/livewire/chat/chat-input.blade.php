<div>
    {{-- Nothing in the world is as soft and yielding as water. --}}
</div>

<form wire:submit.prevent="sendMessage" class="w-full max-w-3xl fixed bottom-0 left-0 right-0 mx-auto mb-4 z-50">
    <div class="w-full rounded-xl bg-gray-100 dark:bg-gray-800 shadow-sm transition-all duration-200 hover:shadow-md focus-within:shadow-md">
        <!-- Message input area -->
        <div class="flex p-3 gap-2">
            <div class="w-full min-h-[4.5rem]">
                <textarea 
                    wire:model.live="message" 
                    placeholder="How can I help you today?" 
                    class="w-full h-full min-h-[4.5rem] max-h-96 resize-none border-0 bg-transparent p-0 focus:ring-0 text-gray-950 dark:text-white"
                    wire:keydown.enter.prevent="sendMessage"
                    aria-label="Message input"
                    aria-describedby="message-help"
                    x-data="{
                        resize() {
                            $el.style.height = '0px';
                            $el.style.height = $el.scrollHeight + 'px';
                        }
                    }"
                    x-init="resize()"
                    @input="resize()"
                ></textarea>
                <div class="absolute bottom-2 right-2 text-xs text-gray-400 dark:text-gray-500" x-data x-text="$wire.message.length + ' characters'"></div>
            </div>
            <button 
                type="submit" 
                class="self-end rounded-lg bg-primary-600 dark:bg-primary-500 p-2 text-white transition-colors hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M200,32V216a8,8,0,0,1-13.66,5.66l-48-48L128,163.31l-10.34,10.35-48,48A8,8,0,0,1,56,216V32a8,8,0,0,1,8-8H192A8,8,0,0,0,200,32Z"></path>
                </svg>
            </button>
        </div>

        <!-- Bottom toolbar -->
        <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 p-2">
            <div class="flex items-center gap-2">
                <!-- Model selector -->
                <div class="relative" x-data="{ open: false }">
                    <button 
                        type="button" 
                        class="flex items-center gap-1 rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                        @click="open = !open"
                    >
                        <span>{{ $selectedModel }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                            <path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
                        </svg>
                    </button>
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        class="absolute left-0 mt-1 w-48 rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg z-10"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                    >
                        <div class="py-1">
                            @foreach($availableModels as $model)
                            <button 
                                type="button"
                                wire:click="changeModel('{{ $model }}')"
                                class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 {{ $selectedModel === $model ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}"
                                @click="open = false"
                            >
                                {{ $model }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <!-- Style selector -->
                <div class="relative" x-data="{ open: false }">
                    <button 
                        type="button" 
                        class="flex items-center gap-1 rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                        @click="open = !open"
                    >
                        <span>{{ $selectedStyle }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                            <path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
                        </svg>
                    </button>
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        class="absolute left-0 mt-1 w-48 rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg z-10"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                    >
                        <div class="py-1">
                            @foreach($availableStyles as $styleKey => $styleName)
                            <button 
                                type="button"
                                wire:click="changeStyle('{{ $styleKey }}')"
                                class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 {{ $selectedStyle === $styleKey ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}"
                                @click="open = false"
                            >
                                {{ $styleName }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center">
                <button 
                    type="button" 
                    wire:click="newConversation"
                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
                >
                    New chat
                </button>
                <div class="hidden sm:flex items-center text-xs text-gray-400 dark:text-gray-500">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 mr-1">⌘/Ctrl</kbd>+<kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 mx-1">N</kbd>for new chat
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="mt-3 bg-app rounded-xl border border-gray-200 dark:border-gray-700 p-3">
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Quick actions</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach($quickActions as $action)
            <button 
                type="button" 
                wire:click="applyQuickAction('{{ $action['name'] }}')"
                class="text-left rounded-lg border border-gray-200 dark:border-gray-700 p-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $action['name'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $action['description'] }}</div>
            </button>
            @endforeach
        </div>
    </div>
</form>
