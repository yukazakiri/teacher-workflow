<div class=" border-r border-gray-200 dark:border-gray-700 ">
    <div class="p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Chats</h2>
            <button 
                type="button"
                wire:click="newConversation"
                class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M200,32V216a8,8,0,0,1-13.66,5.66l-48-48L128,163.31l-10.34,10.35-48,48A8,8,0,0,1,56,216V32a8,8,0,0,1,8-8H192A8,8,0,0,0,200,32Z"></path>
                </svg>
            </button>
        </div>

        <div class="space-y-2">
            @forelse($recentChats as $chat)
                <div class="group relative">
                    <button 
                        type="button"
                        wire:click="loadConversation({{ $chat['id'] }})"
                        class="w-full text-left rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $chat['title'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $chat['last_activity'] }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 ml-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $chat['model'] }}
                                </span>
                                <button 
                                    type="button"
                                    wire:click="deleteConversation({{ $chat['id'] }})"
                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 transition-opacity"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                        <path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192V208Z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </button>
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No recent chats</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
