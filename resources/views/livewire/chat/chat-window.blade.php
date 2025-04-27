<div
    class="flex flex-col h-full bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200"
    x-data="{
        init() {
            const channelIdKey = 'selectedChannelId_' + '{{ Auth::user()->currentTeam->id }}'; // <-- Added quotes
            $wire.on('checkStoredChannel', () => {
                const storedChannelId = localStorage.getItem(channelIdKey);
                $wire.restoreChannel(storedChannelId);
            });
            $wire.on('storeChannelId', (channelId) => {
                localStorage.setItem(channelIdKey, channelId);
            });
            $wire.on('clearStoredChannel', () => {
                localStorage.removeItem(channelIdKey);
            });
            $wire.on('requestInitialChannelId', () => {
                const currentChannelId = $wire.selectedChannelId;
                if(currentChannelId) {
                    $wire.dispatch('setActiveChannel', { channelId: currentChannelId });
                }
            });
        }
    }"
>
    @if ($selectedChannel)
        {{-- Header --}}
        <div class="flex-shrink-0 h-12 border-b border-gray-300 dark:border-gray-700 flex items-center justify-between px-4 bg-gray-50 dark:bg-gray-800">
            <div class="flex items-center min-w-0">
                @if($selectedChannel->is_dm && $otherUser)
                    {{-- Direct Message Header --}}
                    <img src="{{ $otherUser->profile_photo_url }}" alt="{{ $otherUser->name }}" class="w-6 h-6 rounded-full mr-2">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ $otherUser->name }}</h2>
                @else
                    {{-- Regular Channel Header --}}
                    <span class="text-primary-600 dark:text-primary-400 text-xl mr-2 font-light">#</span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ $selectedChannel->name }}</h2>
                    @if($selectedChannel->description)
                        <div class="ml-2 pl-2 border-l border-gray-200 dark:border-gray-600 hidden md:block min-w-0">
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $selectedChannel->description }}</p>
                        </div>
                    @endif
                @endif
            </div>
            <div class="flex items-center space-x-3 flex-shrink-0">
                @if(!$selectedChannel->is_dm) {{-- Hide these buttons for DMs --}}
                <button class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none">
                    <x-heroicon-o-bell class="w-5 h-5" />
                </button>
                <button class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none">
                    <x-heroicon-o-users class="w-5 h-5" />
                </button>
                @endif
            </div>
        </div>

        {{-- Messages Area --}}
        <div
            id="messages-container"
            class="flex-1 overflow-y-auto px-4 pt-2 pb-4 bg-gray-50 dark:bg-gray-900"
            x-data="{
                shouldScroll: true,
                init() {
                    this.scrollToBottom();
                    $wire.on('messageReceived', () => {
                         if (this.shouldScroll) {
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    });
                    this.$el.addEventListener('scroll', () => {
                        const bottomThreshold = 100;
                        this.shouldScroll = this.$el.scrollHeight - this.$el.scrollTop - this.$el.clientHeight < bottomThreshold;
                    });
                },
                scrollToBottom() { this.$el.scrollTo({ top: this.$el.scrollHeight, behavior: 'smooth' }); }
            }"
            x-init="$nextTick(() => scrollToBottom())"
        >
            @php
                $previousUserId = null;
                $previousTime = null;
                $currentUserId = auth()->id();
            @endphp
            @forelse ($messages as $index => $message)
                @php
                    $isCurrentUser = $message['user_id'] === $currentUserId;
                    $isSameUser = $previousUserId === $message['user_id'];
                    $timeGap = $previousTime && (strtotime($message['created_at']) - strtotime($previousTime) > 300);
                    $showUserInfo = !$isSameUser || $timeGap;
                    $previousUserId = $message['user_id'];
                    $previousTime = $message['created_at'];
                @endphp
                @if($timeGap)
                    <div class="relative my-4">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-2 bg-gray-50 dark:bg-gray-900 text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($message['created_at'])->format('F j, Y') }}
                            </span>
                        </div>
                    </div>
                    @php $showUserInfo = true; @endphp
                @endif
                <div class="flex items-start group px-1 py-0.5 hover:bg-primary-50 dark:hover:bg-primary-900 rounded {{ $showUserInfo ? 'mt-3' : 'mt-0' }}">
                    <div class="flex-shrink-0 w-10 h-10 mr-3 {{ $showUserInfo ? '' : 'opacity-0' }}">
                        @if($showUserInfo)
                            <img src="{{ $message['user']['avatar'] }}" alt="{{ $message['user']['name'] }}" class="w-10 h-10 rounded-full">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($showUserInfo)
                            <div class="flex items-baseline mb-0.5">
                                <span class="font-semibold text-gray-900 dark:text-white mr-2">{{ $message['user']['name'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('g:i A') }}
                                </span>
                            </div>
                        @endif
                        <div class="text-sm text-gray-800 dark:text-gray-200 break-words">
                            @if(!$showUserInfo)
                                <span class="hidden group-hover:inline-block w-10 mr-3 text-xs text-gray-400 text-right">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('g:i a') }}
                                </span>
                            @endif
                            <span>{!! nl2br(e($message['content'])) !!}</span>
                            @if($message['is_edited'])
                                <span class="text-xs text-gray-400 ml-1 italic">(edited)</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-full text-center text-gray-400 py-10">
                    @if($selectedChannel->is_dm && $otherUser)
                        <img src="{{ $otherUser->profile_photo_url }}" alt="{{ $otherUser->name }}" class="w-20 h-20 rounded-full mb-4">
                        <p class="text-lg font-medium">This is the beginning of your direct message history with {{ $otherUser->name }}.</p>
                        <p class="text-sm">Messages sent here are private between you and {{ $otherUser->name }}.</p>
                    @else
                        <x-heroicon-o-chat-bubble-left-right class="w-16 h-16 mb-4 text-primary-600 dark:text-primary-400" />
                        <p class="text-lg font-medium">Welcome to #{{ $selectedChannel->name }}!</p>
                        <p class="text-sm">This is the start of the channel.</p>
                    @endif
                </div>
            @endforelse
            <div id="scroll-anchor"></div>
        </div>
        {{-- Input Area --}}
        <div class="flex-shrink-0 p-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <form wire:submit.prevent="sendMessage" x-data="{ messageContent: '' }" class="relative">
                <div class="flex items-center space-x-3 rounded-lg bg-gray-100 dark:bg-gray-700 px-3 py-2 border border-gray-200 dark:border-gray-600">
                    <button type="button" class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400">
                        <x-heroicon-o-plus-circle class="w-5 h-5" />
                    </button>
                    <textarea
                        x-ref="messageInput"
                        x-model="messageContent"
                        wire:model.live="newMessageContent"
                        :placeholder="$wire.selectedChannel?.is_dm && $wire.otherUser ? 'Message ' + $wire.otherUser.name : 'Message #' + ($wire.selectedChannel?.name ?? '')"
                        rows="1"
                        class="flex-1 bg-transparent text-gray-900 dark:text-gray-200 placeholder-gray-400 border-none focus:ring-0 focus:outline-none resize-none max-h-40 py-1 px-0 text-sm"
                        @keydown.enter.prevent="if ($event.shiftKey) { return; } $wire.sendMessage(); messageContent = ''; $nextTick(() => $refs.messageInput.style.height = 'auto');"
                        @input="const textarea = $refs.messageInput; textarea.style.height = 'auto'; textarea.style.height = Math.min(textarea.scrollHeight, 160) + 'px';"
                        maxlength="2000"
                        @disabled(!$selectedChannelId)
                    ></textarea>
                    <button type="button" class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400">
                        <x-heroicon-o-face-smile class="w-5 h-5" />
                    </button>
                    <button
                        type="submit"
                        class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 disabled:opacity-50 disabled:hover:text-gray-400"
                        :disabled="!messageContent.trim()"
                    >
                        <x-heroicon-o-paper-airplane class="w-5 h-5 -rotate-45 transform -translate-y-px" />
                    </button>
                </div>
                @error('newMessageContent')
                    <p class="text-xs text-danger-600 mt-1 px-1">{{ $message }}</p>
                @enderror
                <div class="text-xs text-gray-500 mt-1 px-1" wire:loading wire:target="sendMessage">Sending...</div>
            </form>
        </div>
    @else
        <div class="flex-1 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 text-gray-400">
            <x-heroicon-o-chat-bubble-left-right class="w-24 h-24 mb-6 text-primary-600 dark:text-primary-400" />
            <h3 class="text-xl font-medium text-gray-500 mb-2">No channel selected</h3>
            <p class="text-sm">Select a channel to start chatting.</p>
        </div>
    @endif
</div>
