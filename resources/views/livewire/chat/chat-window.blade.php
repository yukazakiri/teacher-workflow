<div
    class="flex flex-col h-full bg-gray-750 text-gray-300" {{-- Match sidebar's darker theme --}}
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
        <div class="flex-shrink-0 h-12 border-b border-black/20 flex items-center justify-between px-4 bg-gray-800 shadow-sm"> {{-- Darker header, matching border --}}
            <div class="flex items-center min-w-0">
                {{-- Mobile Back Button --}}
                <button
                    type="button"
                    class="md:hidden mr-2 p-1 text-gray-400 hover:text-white focus:outline-none" {{-- Adjusted colors --}}
                    @click="view = 'sidebar'" {{-- Directly set Alpine view state --}}
                >
                    <x-heroicon-o-arrow-left class="h-5 w-5" />
                </button>

                {{-- Existing Header Content --}}
                @if($selectedChannel->is_dm && $otherUser)
                    {{-- Direct Message Header --}}
                    <img src="{{ $otherUser->profile_photo_url }}" alt="{{ $otherUser->name }}" class="w-7 h-7 rounded-full mr-2.5"> {{-- Slightly larger avatar --}}
                    <h2 class="text-base font-semibold text-white truncate">{{ $otherUser->name }}</h2> {{-- White text --}}
                @else
                    {{-- Regular Channel Header --}}
                    <span class="text-gray-500 text-xl mr-1.5 font-medium">#</span> {{-- Adjusted color/spacing --}}
                    <h2 class="text-base font-semibold text-white truncate">{{ $selectedChannel->name }}</h2> {{-- White text --}}
                    @if($selectedChannel->description)
                        <div class="ml-2 pl-2 border-l border-gray-600 hidden md:block min-w-0"> {{-- Darker border --}}
                            <p class="text-sm text-gray-400 truncate">{{ $selectedChannel->description }}</p> {{-- Adjusted color --}}
                        </div>
                    @endif
                @endif
            </div>
            <div class="flex items-center space-x-3 flex-shrink-0">
                @if(!$selectedChannel->is_dm) {{-- Hide these buttons for DMs --}}
                <button class="text-gray-400 hover:text-white focus:outline-none transition-colors duration-150"> {{-- Adjusted colors --}}
                    <x-heroicon-o-bell class="w-5 h-5" />
                </button>
                <button class="text-gray-400 hover:text-white focus:outline-none transition-colors duration-150"> {{-- Adjusted colors --}}
                    <x-heroicon-o-users class="w-5 h-5" />
                </button>
                @endif
            </div>
        </div>

        {{-- Messages Area --}}
        <div
            id="messages-container"
            class="flex-1 overflow-y-auto px-4 pt-4 pb-4 bg-gray-750 scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800" {{-- Darker bg, added scrollbar styles, more top padding --}}
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
                        $messageCreatedAt = \Carbon\Carbon::parse($message['created_at']);
                        $isCurrentUser = $message['user_id'] === $currentUserId;
                        $isSameUser = $previousUserId === $message['user_id'];
                        // Check if previous message exists and time difference is more than 5 minutes
                        $timeGap = $previousTime && ($messageCreatedAt->diffInMinutes($previousTime) > 5);
                        // Check if the date has changed
                        $dateChanged = $previousTime && !$messageCreatedAt->isSameDay($previousTime);
                        // Show user info if it's a different user, or if there's a significant time gap, or if the date changed
                        $showUserInfo = !$isSameUser || $timeGap || $dateChanged;
                        // Update previous user/time for the next iteration
                        $previousUserId = $message['user_id'];
                        $previousTime = $messageCreatedAt;
                    @endphp

                    {{-- Date Separator --}}
                    @if($dateChanged || $loop->first)
                        <div class="relative my-5"> {{-- Increased margin --}}
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-gray-600/50"></div> {{-- Darker, semi-transparent border --}}
                            </div>
                            <div class="relative flex justify-center">
                                <span class="px-3 bg-gray-750 text-xs font-medium text-gray-400 rounded-full"> {{-- Darker bg, rounded span --}}
                                    {{ $messageCreatedAt->calendar(null, ['sameDay' => '[Today]', 'lastDay' => '[Yesterday]', 'lastWeek' => 'l', 'sameElse' => 'F j, Y']) }}
                                </span>
                            </div>
                        </div>
                        @php $showUserInfo = true; @endphp {{-- Ensure user info shows after date change --}}
                    @endif

                    {{-- Message Group --}}
                    <div class="flex items-start group px-1 py-0.5 hover:bg-gray-700/50 rounded {{ $showUserInfo ? 'mt-4' : 'mt-0.5' }}"> {{-- Adjusted hover, spacing --}}
                        {{-- Avatar Placeholder/Display --}}
                        <div class="flex-shrink-0 w-9 h-9 mr-3 {{ $showUserInfo ? '' : 'opacity-0' }}"> {{-- Smaller avatar size --}}
                            @if($showUserInfo)
                                <img src="{{ $message['user']['avatar'] }}" alt="{{ $message['user']['name'] }}" class="w-9 h-9 rounded-full shadow">
                            @endif
                        </div>
                        {{-- Message Content --}}
                        <div class="flex-1 min-w-0">
                            @if($showUserInfo)
                                <div class="flex items-baseline mb-0.5">
                                    <span class="font-semibold text-white mr-2 text-sm">{{ $message['user']['name'] }}</span> {{-- White name --}}
                                    <span class="text-xs text-gray-400"> {{-- Adjusted timestamp color --}}
                                        {{ $messageCreatedAt->format('g:i A') }}
                                    </span>
                                </div>
                            @endif
                            <div class="text-sm text-gray-200 break-words leading-relaxed"> {{-- Lighter text, more line height --}}
                                {{-- Timestamp for condensed messages (visible on hover) --}}
                                @if(!$showUserInfo)
                                    <span class="hidden group-hover:inline-block float-left w-10 mr-3 text-[10px] text-gray-500 text-right pt-0.5"> {{-- Smaller, adjusted position --}}
                                        {{ $messageCreatedAt->format('g:i a') }}
                                    </span>
                                @endif
                                <span>{!! nl2br(e($message['content'])) !!}</span>
                                @if($message['is_edited'])
                                    <span class="text-xs text-gray-400 ml-1 opacity-70">(edited)</span> {{-- Subtle edited indicator --}}
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- Empty State --}}
                    <div class="flex flex-col items-center justify-center h-full text-center text-gray-400 py-10">
                        @if($selectedChannel->is_dm && $otherUser)
                            <img src="{{ $otherUser->profile_photo_url }}" alt="{{ $otherUser->name }}" class="w-16 h-16 rounded-full mb-4 opacity-80"> {{-- Smaller avatar --}}
                            <p class="text-base font-medium text-gray-300">This is the beginning of your direct message history with {{ $otherUser->name }}.</p> {{-- Adjusted text --}}
                            <p class="text-xs text-gray-500 mt-1">Messages sent here are private.</p> {{-- Adjusted text --}}
                        @else
                            <div class="mb-4 p-3 bg-gray-800 rounded-full">
                                <span class="text-gray-500 text-3xl font-semibold">#</span>
                            </div>
                            {{-- <x-heroicon-o-chat-bubble-left-right class="w-16 h-16 mb-4 text-gray-600" /> --}}
                            <p class="text-lg font-semibold text-white">Welcome to #{{ $selectedChannel->name }}!</p> {{-- Adjusted text --}}
                            <p class="text-sm text-gray-400 mt-1">This is the start of the channel. {{ $selectedChannel->description ?? '' }}</p> {{-- Adjusted text --}}
                        @endif
                    </div>
                @endforelse
            <div id="scroll-anchor"></div>
        </div>
        {{-- Input Area --}}
        <div class="flex-shrink-0 p-3 bg-gray-750 border-t border-black/20"> {{-- Match message area bg, darker border --}}
            <form wire:submit.prevent="sendMessage" x-data="{ messageContent: $wire.entangle('newMessageContent').live }" class="relative"> {{-- Bind messageContent directly --}}
                <div class="flex items-end space-x-2 rounded-lg bg-gray-600/80 px-3 py-2"> {{-- Darker input bg, items-end for button alignment --}}
                    <button type="button" class="p-1.5 text-gray-400 hover:text-gray-200 transition-colors duration-150">
                        <x-heroicon-o-plus-circle class="w-5 h-5" />
                    </button>
                    <textarea
                        x-ref="messageInput"
                        x-model="messageContent" {{-- Already bound via entangle --}}
                        {{-- wire:model.live="newMessageContent" --}} {{-- Removed as it's handled by entangle --}}
                        :placeholder="$wire.selectedChannel?.is_dm && $wire.otherUser ? 'Message @' + $wire.otherUser.name : 'Message #' + ($wire.selectedChannel?.name ?? 'channel')" {{-- Improved placeholder --}}
                        rows="1"
                        class="flex-1 bg-transparent text-gray-100 placeholder-gray-400 border-none focus:ring-0 focus:outline-none resize-none max-h-40 py-1.5 px-0 text-sm scrollbar-thin scrollbar-thumb-gray-500 scrollbar-track-gray-600/80" {{-- Adjusted text/placeholder color, padding, added scrollbar --}}
                        @keydown.enter.prevent="if ($event.shiftKey) { return; } $wire.sendMessage(); $nextTick(() => { messageContent = ''; $refs.messageInput.style.height = 'auto'; });" {{-- Clear content after send --}}
                        @input="const textarea = $refs.messageInput; textarea.style.height = 'auto'; textarea.style.height = Math.min(textarea.scrollHeight, 160) + 'px';"
                        maxlength="2000"
                        aria-label="Message Input"
                        @disabled(!$selectedChannelId)
                    ></textarea>
                    <button type="button" class="p-1.5 text-gray-400 hover:text-gray-200 transition-colors duration-150">
                        <x-heroicon-o-face-smile class="w-5 h-5" />
                    </button>
                    <button
                        type="submit"
                        class="p-1.5 text-primary-500 hover:text-primary-400 disabled:text-gray-500 disabled:cursor-not-allowed transition-colors duration-150" {{-- Adjusted colors/disabled state --}}
                        :disabled="!messageContent || messageContent.trim() === ''" {{-- Disable if empty or only whitespace --}}
                        aria-label="Send Message"
                    >
                        <x-heroicon-o-paper-airplane class="w-5 h-5 rotate-45 transform" /> {{-- Adjusted rotation --}}
                    </button>
                </div>
                @error('newMessageContent')
                    <p class="text-xs text-danger-500 mt-1.5 px-1">{{ $message }}</p> {{-- Adjusted color/spacing --}}
                @enderror
                {{-- Loading indicator can be added here if needed, e.g., inside the form --}}
                {{-- <div class="text-xs text-gray-500 mt-1 px-1" wire:loading wire:target="sendMessage">Sending...</div> --}}
            </form>
        </div>
    @else
        {{-- No Channel Selected State --}}
        <div class="flex-1 flex flex-col items-center justify-center bg-gray-750 text-gray-500 p-6"> {{-- Match bg, add padding --}}
             <x-heroicon-o-chat-bubble-left-ellipsis class="w-20 h-20 mb-6 text-gray-600" /> {{-- Different icon, color --}}
            <h3 class="text-xl font-medium text-gray-400 mb-2">Select a Conversation</h3> {{-- Adjusted text --}}
            <p class="text-sm text-center">Choose a channel or direct message from the sidebar to start chatting.</p> {{-- Adjusted text --}}
        </div>
    @endif
</div>
