<div 
    class="flex flex-col h-full bg-gray-800 text-gray-300"
    x-data="{
        init() {
            const channelIdKey = 'selectedChannelId_' + {{ Auth::user()->currentTeam->id }}; // Team-specific key
            
            // Listen for Livewire to ask for stored channel
            $wire.on('checkStoredChannel', () => {
                const storedChannelId = localStorage.getItem(channelIdKey);
                console.log('Checking stored channel:', storedChannelId);
                // Tell Livewire to restore or load default
                $wire.restoreChannel(storedChannelId);
            });
            
            // Listen for Livewire to confirm a valid channel loaded
            $wire.on('storeChannelId', (channelId) => {
                console.log('Storing channel ID:', channelId);
                localStorage.setItem(channelIdKey, channelId);
                // Also notify sidebar if needed (can be done via Livewire dispatch now)
                // window.dispatchEvent(new CustomEvent('channel-selected', { detail: channelId }));
            });
            
            // Listen for Livewire to clear stored channel ID
            $wire.on('clearStoredChannel', () => {
                console.log('Clearing stored channel ID');
                localStorage.removeItem(channelIdKey);
            });
            
            // Listen for sidebar requesting initial ID
            $wire.on('requestInitialChannelId', () => {
                const currentChannelId = $wire.selectedChannelId; 
                if(currentChannelId) {
                    // If ChatWindow already has a channel loaded, tell sidebar
                    $wire.dispatch('setActiveChannel', { channelId: currentChannelId });
                }
            });
        }
    }"
>
    @if ($selectedChannel)
        {{-- Header --}}
        <div class="flex-shrink-0 h-12 border-b border-gray-900 flex items-center justify-between px-4 bg-gray-700">
            <div class="flex items-center min-w-0">
                <span class="text-gray-400 text-xl mr-2 font-light">#</span>
                <h2 class="text-base font-semibold text-white truncate">{{ $selectedChannel->name }}</h2>
                @if($selectedChannel->description)
                    <div class="ml-2 pl-2 border-l border-gray-600 hidden md:block min-w-0">
                        <p class="text-sm text-gray-400 truncate">{{ $selectedChannel->description }}</p>
                    </div>
                @endif
            </div>
            {{-- Header Icons (Placeholder) --}}
            <div class="flex items-center space-x-3 flex-shrink-0">
                <button class="text-gray-400 hover:text-gray-200 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </button>
                <button class="text-gray-400 hover:text-gray-200 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
                {{-- Search bar can go here --}}
            </div>
        </div>

        {{-- Messages Area --}}
        <div 
            id="messages-container" 
            class="flex-1 overflow-y-auto px-4 pt-2 pb-4 bg-gray-750"
            x-data="{ 
                shouldScroll: true,
                init() { 
                    this.scrollToBottom();
                    // Use Livewire event listener directly for simplicity
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
                    $timeGap = $previousTime && (strtotime($message['created_at']) - strtotime($previousTime) > 300); // 5 min gap
                    
                    // Show user info if new user or time gap
                    $showUserInfo = !$isSameUser || $timeGap;
                    
                    $previousUserId = $message['user_id'];
                    $previousTime = $message['created_at'];
                @endphp
                
                {{-- Time separator --}}
                @if($timeGap)
                    <div class="relative my-4">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-700"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-2 bg-gray-750 text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($message['created_at'])->format('F j, Y') }}
                            </span>
                        </div>
                    </div>
                    @php $showUserInfo = true; @endphp
                @endif
                
                {{-- Message Row --}}
                <div class="flex items-start group px-1 py-0.5 hover:bg-gray-800/50 rounded {{ $showUserInfo ? 'mt-3' : 'mt-0' }}">
                    {{-- Avatar (only show on first message of group) --}}
                    <div class="flex-shrink-0 w-10 h-10 mr-3 {{ $showUserInfo ? '' : 'opacity-0' }}">
                        @if($showUserInfo)
                            <img src="{{ $message['user']['avatar'] }}" alt="{{ $message['user']['name'] }}" class="w-10 h-10 rounded-full">
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        {{-- Username and Timestamp (only show on first message of group) --}}
                        @if($showUserInfo)
                            <div class="flex items-baseline mb-0.5">
                                <span class="font-semibold text-white mr-2">{{ $message['user']['name'] }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('g:i A') }}
                                </span>
                            </div>
                        @endif
                        
                        {{-- Message Content --}}
                        <div class="text-sm text-gray-200 break-words">
                            {{-- Show timestamp on hover for subsequent messages --}}
                            @if(!$showUserInfo)
                                <span class="hidden group-hover:inline-block w-10 mr-3 text-xs text-gray-500 text-right">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('g:i a') }}
                                </span>
                            @endif
                            <span>{!! nl2br(e($message['content'])) !!}</span>
                            
                            {{-- Edit indicator --}}
                            @if($message['is_edited'])
                                <span class="text-xs text-gray-500 ml-1 italic">(edited)</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-full text-center text-gray-500 py-10">
                    <svg class="w-16 h-16 mb-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17.668V13.5A7.003 7.003 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path></svg>
                    <p class="text-lg font-medium">Welcome to #{{ $selectedChannel->name }}!</p>
                    <p class="text-sm">This is the start of the channel.</p>
                </div>
            @endforelse
            <div id="scroll-anchor"></div>
        </div>

        {{-- Input Area --}}
        <div class="flex-shrink-0 p-3 bg-gray-750">
            <form wire:submit.prevent="sendMessage" x-data="{ messageContent: '' }" class="relative">
                <div class="flex items-center space-x-3 rounded-lg bg-gray-600 px-3 py-2">
                    <button type="button" class="text-gray-400 hover:text-gray-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>
                    </button>
                    <textarea 
                        x-ref="messageInput"
                        x-model="messageContent"
                        wire:model.live="newMessageContent"
                        placeholder="Message #{{ $selectedChannel->name }}"
                        rows="1"
                        class="flex-1 bg-transparent text-gray-200 placeholder-gray-400 border-none focus:ring-0 focus:outline-none resize-none max-h-40 py-1 px-0 text-sm"
                        @keydown.enter.prevent="if ($event.shiftKey) { return; } $wire.sendMessage(); messageContent = ''; $nextTick(() => $refs.messageInput.style.height = 'auto');"
                        @input="const textarea = $refs.messageInput; textarea.style.height = 'auto'; textarea.style.height = Math.min(textarea.scrollHeight, 160) + 'px';"
                        maxlength="2000"
                        @disabled(!$selectedChannelId)
                    ></textarea>
                    <button type="button" class="text-gray-400 hover:text-gray-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm7-1a1 1 0 11-2 0 1 1 0 012 0zm-.464 5.535a1 1 0 10-1.415-1.414 3 3 0 01-4.242 0 1 1 0 00-1.415 1.414 5 5 0 007.072 0z"></path></svg>
                    </button>
                    <button 
                        type="submit" 
                        class="text-gray-400 hover:text-primary-400 disabled:opacity-50 disabled:hover:text-gray-400"
                        :disabled="!messageContent.trim()"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
                @error('newMessageContent') 
                    <p class="text-xs text-red-400 mt-1 px-1">{{ $message }}</p> 
                @enderror
                <div class="text-xs text-gray-500 mt-1 px-1" wire:loading wire:target="sendMessage">Sending...</div>
            </form>
        </div>
    @else
        {{-- Placeholder --}}
        <div class="flex-1 flex flex-col items-center justify-center bg-gray-750 text-gray-500">
            <svg class="w-24 h-24 mb-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17.668V13.5A7.003 7.003 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path></svg>
            <h3 class="text-xl font-medium text-gray-400 mb-2">No channel selected</h3>
            <p class="text-sm">Select a channel to start chatting.</p>
        </div>
    @endif
</div>
