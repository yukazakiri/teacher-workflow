<div class="flex-1 overflow-y-auto pb-32">
    @if($conversation)
        <div class="max-w-3xl mx-auto px-4">
            <!-- Conversation header -->
            <div class="sticky top-0 z-10 bg-app/80 backdrop-blur-sm py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button 
                            type="button"
                            wire:click="newConversation"
                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                                <path d="M200,32V216a8,8,0,0,1-13.66,5.66l-48-48L128,163.31l-10.34,10.35-48,48A8,8,0,0,1,56,216V32a8,8,0,0,1,8-8H192A8,8,0,0,0,200,32Z"></path>
                            </svg>
                        </button>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $conversation->title }}</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            type="button"
                            wire:click="regenerateLastMessage"
                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                            title="Regenerate last message"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                                <path d="M197.67,186.37a8,8,0,0,1,0,11.29C196.58,198.81,188.84,202,184,202c-4.83,0-12.57-3.19-13.69-4.31a8,8,0,0,1,11.3-11.3c.31.31,7.45,4.76,12.39,4.76s12.08-4.45,12.39-4.76A8,8,0,0,1,197.67,186.37ZM184,176c-4.83,0-12.57,3.19-13.69,4.31a8,8,0,0,0,11.3,11.3c.31-.31,7.45-4.76,12.39-4.76s12.08,4.45,12.39,4.76a8,8,0,0,0,11.3-11.3C196.58,179.19,188.84,176,184,176ZM128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"></path>
                            </svg>
                        </button>
                        <button 
                            type="button"
                            x-data="{ open: false }"
                            @click="open = true"
                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                            title="Rename conversation"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                                <path d="M227.31,73.37l-44.68-44.69a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H92.69A15.86,15.86,0,0,0,104,219.31L227.31,96a16,16,0,0,0,0-22.63ZM92.69,208H48V163.31l88-88L180.69,120Z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="py-4 space-y-4">
                @foreach($conversation->messages as $message)
                    <div class="flex gap-4 {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="flex-1 max-w-[85%] {{ $message->role === 'user' ? 'order-2' : 'order-1' }}">
                            <div class="rounded-2xl {{ $message->role === 'user' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800' }} p-4">
                                <div class="prose dark:prose-invert max-w-none">
                                    {!! Str::markdown($message->content) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($isProcessing)
                    <div class="flex justify-start">
                        <div class="flex-1 max-w-[85%]">
                            <div class="rounded-2xl bg-gray-100 dark:bg-gray-800 p-4">
                                <div class="typing-indicator">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Rename conversation modal -->
<div 
    x-data="{ open: false, title: '{{ $conversation?->title ?? '' }}' }" 
    x-show="open" 
    class="fixed inset-0 z-50 overflow-y-auto" 
    x-cloak
>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            Rename conversation
                        </h3>
                        <div class="mt-2">
                            <input 
                                type="text" 
                                x-model="title"
                                class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 rounded-md dark:bg-gray-900 dark:text-white"
                                placeholder="Enter conversation title"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button 
                    type="button" 
                    wire:click="renameConversation($wire.entangle('title'))"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                    @click="open = false"
                >
                    Rename
                </button>
                <button 
                    type="button" 
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-900 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    @click="open = false"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
