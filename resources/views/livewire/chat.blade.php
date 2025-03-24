<div class="min-h-full w-full min-w-0 flex-1 bg-app"
     x-data="{ 
         initKeyboardShortcuts() {
             document.addEventListener('keydown', (e) => {
                 // Cmd/Ctrl + Enter to send message
                 if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                     if (!$wire.isProcessing) {
                         $wire.sendMessage();
                     }
                 }
                 
                 // Cmd/Ctrl + / to focus on message input
                 if ((e.metaKey || e.ctrlKey) && e.key === '/') {
                     e.preventDefault();
                     document.querySelector('textarea[wire\\:model=\"message\"]').focus();
                 }
                 
                 // Cmd/Ctrl + R to regenerate last message
                 if ((e.metaKey || e.ctrlKey) && e.key === 'r' && $wire.conversation) {
                     e.preventDefault();
                     if (!$wire.isProcessing) {
                         $wire.regenerateLastMessage();
                     }
                 }
                 
                 // Cmd/Ctrl + N for new chat
                 if ((e.metaKey || e.ctrlKey) && e.key === 'n') {
                     e.preventDefault();
                     $wire.newConversation();
                 }
             });
         }
     }"
     x-init="initKeyboardShortcuts()"
>
    <style>
        /* Enhanced markdown styling */
        .markdown-content pre {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
        }
        
        .dark .markdown-content pre {
            background-color: #1e293b;
            border-color: #334155;
        }
        
        .markdown-content code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.875em;
            padding: 0.2em 0.4em;
            border-radius: 0.25rem;
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .dark .markdown-content code {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .markdown-content pre code {
            background-color: transparent;
            padding: 0;
        }
        
        /* Typing indicator animation */
        .typing-indicator {
            display: flex;
            align-items: center;
        }
        
        .typing-indicator span {
            height: 6px;
            width: 6px;
            background-color: #9ca3af;
            border-radius: 50%;
            display: inline-block;
            margin: 0 1px;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .dark .typing-indicator span {
            background-color: #6b7280;
        }
        
        .typing-indicator span:nth-child(1) {
            animation-delay: 0s;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0% {
                transform: translateY(0px);
                opacity: 0.4;
            }
            50% {
                transform: translateY(-5px);
                opacity: 0.9;
            }
            100% {
                transform: translateY(0px);
                opacity: 0.4;
            }
        }
    </style>
    <main class="mx-auto w-full max-w-5xl flex-1 px-4 pb-16 pt-6 min-h-screen flex flex-col items-center gap-6">
        <!-- Header with greeting and plan info -->
        <div class="w-full flex justify-between items-center max-w-4xl mb-2">
            <div class="font-medium text-2xl text-gray-950 dark:text-white">
                <span class="text-primary-600 dark:text-primary-400 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="w-6 h-6 inline-block fill-current">
                        <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path>
                    </svg>
                </span>
                Good {{ now()->format('A') === 'AM' ? 'morning' : (now()->format('H') < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}
            </div>
            <div class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                Free plan
                <div class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></div>
                <a href="/upgrade" class="text-primary-600 dark:text-primary-400 hover:underline">Upgrade</a>
            </div>
        </div>

        <!-- Chat input area -->
        @if(!$conversation)
        <div class="w-full max-w-3xl z-10">
            <form wire:submit.prevent="sendMessage" class="w-full">
                <div class="relative rounded-xl border border-gray-200 dark:border-gray-700 bg-app shadow-sm transition-all duration-200 hover:shadow-md focus-within:shadow-md">
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
        </div>
       

        <!-- Recent chats section -->
        <div class="w-full max-w-3xl mt-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-gray-800 dark:text-gray-200 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                        <path d="M232.07,194.34a80,80,0,0,0-62.5-114.17A80,80,0,1,0,23.93,138.76l-7.27,24.71a16,16,0,0,0,19.87,19.87l24.71-7.27a80.39,80.39,0,0,0,25.18,7.35,80,80,0,0,0,108.34,40.65l24.71,7.27a16,16,0,0,0,19.87-19.86ZM62,159.5a8.28,8.28,0,0,0-2.26.32L32,168l8.17-27.76a8,8,0,0,0-.63-6,64,64,0,1,1,26.26,26.26A8,8,0,0,0,62,159.5Zm153.79,28.73L224,216l-27.76-8.17a8,8,0,0,0-6,.63,64.05,64.05,0,0,1-85.87-24.88A79.93,79.93,0,0,0,174.7,89.71a64,64,0,0,1,41.75,92.48A8,8,0,0,0,215.82,188.23Z"></path>
                    </svg>
                    Recent chats
                </h2>
                <button 
                    wire:click="newConversation"
                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
                >
                    Start new
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @forelse($recentChats as $chat)
                <div 
                    wire:click="loadConversation({{ $chat['id'] }})"
                    class="cursor-pointer rounded-xl border border-gray-200 dark:border-gray-700 p-3 hover:border-gray-300 dark:hover:border-gray-600 transition-all"
                >
                    <div class="flex items-start gap-2">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-medium text-sm">
                            {{ substr($chat['title'], 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $chat['title'] }}</h3>
                                <div class="flex-shrink-0">
                                    <button 
                                        wire:click.stop="deleteConversation({{ $chat['id'] }})"
                                        class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                                            <path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="truncate">{{ $chat['model'] }}</span>
                                <span class="mx-1">•</span>
                                <span>{{ $chat['last_activity'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-3 text-center py-8">
                    <div class="text-gray-400 dark:text-gray-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="mx-auto">
                            <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80a8,8,0,0,1,8-8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,176Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">No recent chats found. Start a new conversation!</p>
                </div>
                @endforelse
            </div>
        </div>
        @endif
        <!-- Chat messages display area -->
        @if($conversation)
        <div class="w-full max-w-3xl mt-4" 
             x-data="{ 
                 scrollToBottom() {
                     const container = this.$refs.messagesContainer;
                     container.scrollTop = container.scrollHeight;
                 }
             }"
             x-init="scrollToBottom()"
             @refreshChat.window="scrollToBottom()"
        >
            <div x-data="{ showConversation: {{ $conversation ? 'true' : 'false' }} }"
                 x-show="showConversation"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 x-init="$watch('$wire.conversation', value => { showConversation = !!value })"
                 class="flex flex-col h-full"
                 style="display: {{ $conversation ? 'flex' : 'none' }};">
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2" x-data="{ isRenaming: false, newTitle: @js($conversation->title) }">
                            <template x-if="!isRenaming">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-medium text-gray-950 dark:text-white">{{ $conversation->title }}</h2>
                                    <button 
                                        @click="isRenaming = true; $nextTick(() => $refs.titleInput.focus())"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                                            <path d="M227.31,73.37,182.63,28.68a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H92.69A15.86,15.86,0,0,0,104,219.31L227.31,96a16,16,0,0,0,0-22.63ZM92.69,208H48V163.31l88-88L180.69,120ZM192,108.68,147.31,64l24-24L216,84.68Z"></path>
                                        </svg>
                                    </button>
                                    <span class="inline-flex items-center rounded-md bg-primary-50 dark:bg-primary-900/20 px-2 py-1 text-xs font-medium text-primary-700 dark:text-primary-400">
                                        {{ $conversation->model }}
                                    </span>
                                </div>
                            </template>
                            <template x-if="isRenaming">
                                <form @submit.prevent="isRenaming = false; $wire.renameConversation(newTitle)" class="flex items-center gap-2">
                                    <input 
                                        x-ref="titleInput"
                                        x-model="newTitle" 
                                        class="text-lg font-medium text-gray-950 dark:text-white bg-transparent border-b border-gray-300 dark:border-gray-700 focus:border-primary-500 dark:focus:border-primary-500 focus:ring-0 px-0 py-1"
                                        @keydown.escape="isRenaming = false"
                                        @blur="isRenaming = false; if(newTitle.trim()) $wire.renameConversation(newTitle)"
                                    />
                                    <button type="submit" class="text-primary-600 dark:text-primary-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                            <path d="M229.66,77.66l-128,128a8,8,0,0,1-11.32,0l-56-56a8,8,0,0,1,11.32-11.32L96,188.69,218.34,66.34a8,8,0,0,1,11.32,11.32Z"></path>
                                        </svg>
                                    </button>
                                </form>
                            </template>
                        </div>
                        <div class="flex items-center gap-2">
                            <button 
                                wire:click="regenerateLastMessage"
                                wire:loading.attr="disabled"
                                wire:target="regenerateLastMessage"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 flex items-center gap-1"
                                title="Regenerate last response (⌘/Ctrl+R)"
                                aria-label="Regenerate last response"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                                    <path d="M197.66,186.34a8,8,0,0,1,0,11.32C196.58,198.73,170.82,224,128,224c-23.36,0-46.13-9.1-66.28-26.41L45.66,213.66A8,8,0,0,1,32,208V160a8,8,0,0,1,8-8H88a8,8,0,0,1,5.66,13.66L73.08,186.24C86.08,197.15,104.83,208,128,208c36.27,0,56.67-20.53,56.82-20.71A8,8,0,0,1,197.66,186.34Zm26.34-90.34H176a8,8,0,0,0-5.66,13.66l20.58,20.58C177.92,141.15,159.17,152,136,152c-36.27,0-56.67-20.53-56.82-20.71a8,8,0,0,0-11.32,11.32C68.94,143.68,94.7,168.9,136,168.9c23.36,0,46.13-9.1,66.28-26.41l16.06,16.07A8,8,0,0,0,232,152V104A8,8,0,0,0,224,96Z"></path>
                                </svg>
                                <span>Regenerate</span>
                            </button>
                            <button 
                                wire:click="newConversation"
                                class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
                            >
                                New chat
                            </button>
                        </div>
                    </div>

                    <div wire:loading wire:target="regenerateLastMessage" class="flex justify-center py-4">
                        <div class="inline-flex items-center px-4 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-md">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Regenerating response...</span>
                        </div>
                    </div>

                    <!-- Search bar for messages -->
                    <div class="mb-3" x-data="{ searchVisible: false, searchQuery: '', highlightMatches() {
                        if (!this.searchQuery.trim()) return;
                        
                        const messages = document.querySelectorAll('.message-content');
                        messages.forEach(message => {
                            const content = message.innerHTML;
                            const regex = new RegExp(this.searchQuery, 'gi');
                            message.innerHTML = content.replace(regex, match => `<mark class='bg-yellow-200 dark:bg-yellow-800'>${match}</mark>`);
                        });
                    } }">
                        <div class="flex items-center justify-between mb-2">
                            <button 
                                @click="searchVisible = !searchVisible; if(!searchVisible) { searchQuery = ''; document.querySelectorAll('.message-content').forEach(el => el.innerHTML = el.dataset.originalContent); }"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 flex items-center gap-1"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256" class="inline">
                                    <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                                </svg>
                                <span x-text="searchVisible ? 'Hide search' : 'Search in conversation'"></span>
                            </button>
                            <div x-show="searchVisible" class="text-xs text-gray-500 dark:text-gray-400">
                                <span x-text="document.querySelectorAll('.message-content mark').length"></span> matches
                            </div>
                        </div>
                        <div x-show="searchVisible" x-transition class="mb-3">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    x-model="searchQuery" 
                                    @input="document.querySelectorAll('.message-content').forEach(el => el.innerHTML = el.dataset.originalContent); highlightMatches()"
                                    placeholder="Search in conversation..." 
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-app px-3 py-2 text-sm focus:border-primary-500 dark:focus:border-primary-500 focus:ring-primary-500 dark:focus:ring-primary-500"
                                />
                                <button 
                                    x-show="searchQuery" 
                                    @click="searchQuery = ''; document.querySelectorAll('.message-content').forEach(el => el.innerHTML = el.dataset.originalContent);"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                                        <path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 max-h-[60vh] overflow-y-auto px-1" x-ref="messagesContainer">
                        @foreach($conversation->messages as $message)
                        <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] {{ $message->role === 'user' ? 'bg-primary-50 dark:bg-primary-950/50 text-gray-950 dark:text-white' : 'bg-app border border-gray-200 dark:border-gray-700 text-gray-950 dark:text-white' }} rounded-lg p-3 shadow-sm">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between items-center">
                                    <span>{{ $message->role === 'user' ? 'You' : $conversation->model }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $message->created_at->format('g:i A') }}</span>
                                </div>
                                <div class="prose prose-sm dark:prose-invert max-w-none" x-data="{ 
                                    showCopied: false,
                                    copyToClipboard() {
                                        navigator.clipboard.writeText(@js($message->content));
                                        this.showCopied = true;
                                        setTimeout(() => this.showCopied = false, 2000);
                                    }
                                }">
                                    <div class="markdown-content message-content" data-original-content="{!! Str::markdown($message->content) !!}">
                                        {!! Str::markdown($message->content) !!}
                                    </div>
                                    <div class="flex justify-end mt-1">
                                        <button 
                                            @click="copyToClipboard()"
                                            class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex items-center gap-1"
                                        >
                                            <span x-show="!showCopied">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256" class="inline">
                                                    <path d="M216,32H88a8,8,0,0,0-8,8V80H40a8,8,0,0,0-8,8V216a8,8,0,0,0,8,8H168a8,8,0,0,0,8-8V176h40a8,8,0,0,0,8-8V40A8,8,0,0,0,216,32Zm-8,80H120a8,8,0,0,0,0-16h16A8,8,0,0,0,208,112Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"></path>
                                                </svg>
                                                Copy
                                            </span>
                                            <span x-show="showCopied" class="text-green-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256" class="inline">
                                                    <path d="M229.66,77.66l-128,128a8,8,0,0,1-11.32,0l-56-56a8,8,0,0,1,11.32-11.32L96,188.69,218.34,66.34a8,8,0,0,1,11.32,11.32Z"></path>
                                                </svg>
                                                Copied!
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                @if($message->is_streaming)
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Generating response</div>
                                    <div class="typing-indicator">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <form wire:submit.prevent="sendMessage" class="w-[50em] absolute ">
                <div class="w-full rounded-t-xl bg-gray-100 dark:bg-gray-800 shadow-sm transition-all duration-200 hover:shadow-md focus-within:shadow-md">
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
    
            </form>
        </div>
        
        @else

        <div class="flex flex-col h-full">
            <div x-data="{ showWelcome: {{ $conversation ? 'false' : 'true' }} }" 
                 x-show="showWelcome" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 x-init="$watch('$wire.conversation', value => { showWelcome = !value })"
                 class="h-full flex flex-col items-center justify-center p-4"
                 style="display: {{ $conversation ? 'none' : 'flex' }};">
                <div class="max-w-2xl w-full mx-auto bg-app rounded-xl border border-gray-200 dark:border-gray-700 p-8">
                    <div class="text-primary-600 dark:text-primary-400 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 256 256" class="mx-auto">
                            <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm16-40a8,8,0,0,1-8,8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,176Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-medium text-gray-950 dark:text-white mb-2">Welcome to Teacher Assistant</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Ask me anything to help with your teaching tasks</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left max-w-lg mx-auto">
                        <button 
                            wire:click="setPrompt('Help me create a lesson plan for a high school English class on Shakespeare.')"
                            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition-colors"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Create a lesson plan</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">For a high school English class</div>
                        </button>
                        
                        <button 
                            wire:click="setPrompt('Generate 5 discussion questions about climate change for a middle school science class.')"
                            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition-colors"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Generate discussion questions</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">About climate change for middle school</div>
                        </button>
                        
                        <button 
                            wire:click="setPrompt('Help me draft a parent-teacher conference email template.')"
                            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition-colors"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Draft an email template</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">For parent-teacher conferences</div>
                        </button>
                        
                        <button 
                            wire:click="setPrompt('Suggest activities for a virtual classroom to increase student engagement.')"
                            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition-colors"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Suggest virtual activities</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">To increase student engagement</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </main>
</div>
