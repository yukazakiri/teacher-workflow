<div>
    @if($chats->isEmpty())
        <div class="flex flex-col items-center justify-center p-6 text-center bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="w-16 h-16 bg-primary-50 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-primary-600 dark:text-primary-400" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Chats Found</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Start a new conversation with our AI assistant.</p>
            <x-filament::button
                color="primary"
                icon="heroicon-o-plus"
                tag="a"
                href="{{ route('filament.app.pages.chat', ['tenant' => auth()->user()->currentTeam->id]) }}"
            >
                Start New Chat
            </x-filament::button>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($chats as $chat)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700 flex flex-col transition-all hover:shadow-md">
                    <!-- Chat Card Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between">
                        <h2 class="text-base font-medium text-gray-900 dark:text-white truncate flex-1">
                            {{ Str::limit($chat->title, 30) }}
                        </h2>
                        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                            {{ $chat->model }}
                        </span>
                    </div>
                    
                    <!-- Chat Card Content -->
                    <div class="p-4 flex-1">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <p class="line-clamp-3">
                                @if($chat->messages->isNotEmpty())
                                    {{ $chat->messages->last()->content }}
                                @else
                                    No messages yet.
                                @endif
                            </p>
                        </div>
                        
                        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                            <div>
                                <span>{{ $chat->messages->count() }} messages</span>
                            </div>
                            <div>
                                <span>{{ $chat->created_at ? $chat->created_at->diffForHumans() : 'Just now' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Card Actions -->
                    <div class="flex border-t border-gray-200 dark:border-gray-700 divide-x divide-gray-200 dark:divide-gray-700">
                        <a href="{{ route('filament.app.pages.chat.show', ['tenant' => auth()->user()->currentTeam->id, 'chat' => $chat->id]) }}" class="flex-1 flex items-center justify-center py-3 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                            <x-heroicon-o-chat-bubble-oval-left-ellipsis class="w-4 h-4 mr-2" />
                            Open
                        </a>
                        <button 
                            wire:click="deleteChat({{ $chat->id }})" 
                            wire:confirm="Are you sure you want to delete this chat?"
                            class="flex-1 flex items-center justify-center py-3 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                        >
                            <x-heroicon-o-trash class="w-4 h-4 mr-2" />
                            Delete
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
