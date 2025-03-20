<div class="flex h-[calc(100vh-4rem)]">
    <!-- Left Sidebar - Chat History -->
    <div class="w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <!-- New Chat Button -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <button wire:click="createNewChat" class="w-full flex items-center justify-center px-4 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 shadow-sm">
                <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                <span class="font-medium">New Chat</span>
            </button>
        </div>

        <!-- Chat List -->
        <div class="flex-1 overflow-y-auto py-2">
            <h3 class="px-4 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recent Conversations</h3>
            <div class="space-y-1 px-2">
                @foreach($chats as $chat)
                    <button 
                        wire:click="switchChat({{ $chat->id }})" 
                        class="w-full flex items-center px-3 py-2.5 text-sm rounded-lg transition-colors duration-150 {{ $currentChat?->id === $chat->id ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 font-medium' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                    >
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 mr-2 {{ $currentChat?->id === $chat->id ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}" />
                        <span class="truncate">{{ $chat->title }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900 relative">
        @if($currentChat)
            <!-- Chat Header -->
            <div class="py-3 px-6 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white truncate">{{ $currentChat->title }}</h2>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                        {{ $model && array_key_exists($model, $availableModels) ? $availableModels[$model] : 'Unknown Model' }}
                    </span>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6" id="messages">
                @foreach($currentChat->messages as $message)
                    <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }} items-start">
                        <div class="flex items-start gap-3 max-w-3xl {{ $message->role === 'user' ? 'flex-row-reverse' : 'flex-row' }}">
                            <!-- Avatar -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $message->role === 'user' ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                                @if($message->role === 'user')
                                    <x-heroicon-s-user class="w-5 h-5 text-white" />
                                @else
                                    <x-heroicon-s-sparkles class="w-5 h-5 {{ $message->role === 'user' ? 'text-white' : 'text-primary-600 dark:text-primary-400' }}" />
                                @endif
                            </div>
                            
                            <!-- Message Content -->
                            <div class="{{ $message->role === 'user' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' }} rounded-2xl px-4 py-3 shadow-sm">
                                <div class="prose dark:prose-invert max-w-none prose-sm">
                                    {!! $message->formatted_content !!}
                                </div>
                                <div class="mt-1 text-xs {{ $message->role === 'user' ? 'text-primary-100' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $message->created_at ? $message->created_at->format('g:i A') : 'Just now' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Input Area - Centered at the bottom -->
            <div class="p-6 pt-4 bg-transparent">
                <div class="max-w-3xl mx-auto">
                    <form wire:submit="sendMessage" class="relative">
                        <textarea
                            wire:model="message"
                            rows="1"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 resize-none pl-4 pr-12 py-3 shadow-sm"
                            placeholder="Type your message..."
                            x-data
                            x-init="$el.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $wire.sendMessage(); } })"
                        ></textarea>
                        <button type="submit" class="absolute right-3 bottom-3 inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                            <x-heroicon-s-paper-airplane class="w-4 h-4 -rotate-45 transform -translate-y-px" />
                        </button>
                    </form>
                    <p class="text-xs text-center mt-2 text-gray-500 dark:text-gray-400">
                        Press Enter to send, Shift+Enter for new line
                    </p>
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="flex-1 flex items-center justify-center p-6">
                <div class="text-center max-w-md mx-auto">
                    <div class="w-16 h-16 bg-primary-50 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Start a New Conversation</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Select a model and begin an AI-powered conversation with our advanced language models.</p>
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                            <label for="model-empty" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Choose AI Model</label>
                            <select wire:model.live="model" id="model-empty" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                @foreach($availableModels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button wire:click="createNewChat" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors shadow-sm font-medium">
                            <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                            New Conversation
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Right Sidebar - Model Selection & Options -->
    <div class="w-64 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
        @if($currentChat)
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">AI Model</h3>
                <select wire:model.live="model" id="model" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500">
                    @foreach($availableModels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Changing the model will apply to new messages only
                </p>
            </div>
            
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Chat Information</h3>
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Created:</span>
                        <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $currentChat->created_at ? $currentChat->created_at->format('M d, Y') : 'Just now' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Messages:</span>
                        <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $currentChat->messages->count() }}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex-1"></div>
            
            <div class="p-4">
                <button class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 mr-2" />
                    Export Chat
                </button>
            </div>
        @else
            <div class="p-4 text-center text-gray-500 dark:text-gray-400 flex-1 flex items-center justify-center">
                <div>
                    <x-heroicon-o-cog-6-tooth class="w-8 h-8 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Options will appear here when a chat is active</p>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Scroll to bottom when new messages arrive
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('message-sent', () => {
            const messages = document.getElementById('messages');
            if (messages) {
                messages.scrollTop = messages.scrollHeight;
            }
        });
        
        // Also scroll on initial load
        const messages = document.getElementById('messages');
        if (messages) {
            messages.scrollTop = messages.scrollHeight;
        }
    });
</script>
@endpush 