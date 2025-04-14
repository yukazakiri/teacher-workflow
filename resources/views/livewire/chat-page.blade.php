<div 
    x-data="{
        isChannelListOpen: window.innerWidth >= 1024, // Default open on desktop
        isUserListOpen: false,
        // Function to close sidebars
        closeSidebars() {
            if (window.innerWidth < 1024) { 
                 this.isChannelListOpen = false;
            }
            this.isUserListOpen = false;
        },
        // Initialize and handle resizing
        init() {
            // Watchers for debugging
            this.$watch('isChannelListOpen', value => console.log('Channel List:', value));
            this.$watch('isUserListOpen', value => console.log('User List:', value));
            
            let lastWidth = window.innerWidth;
            window.addEventListener('resize', () => {
                const newWidth = window.innerWidth;
                if (newWidth >= 1024 && lastWidth < 1024) {
                    // Resized up to desktop: force channel list open
                    this.isChannelListOpen = true; 
                }
                // No automatic closing when resizing down - maintain state
                lastWidth = newWidth;
            });
        }
    }"
    x-init="init()"
    class="flex h-screen bg-gray-100 dark:bg-gray-900 text-gray-950 dark:text-white overflow-hidden"
>
    {{-- Server List Column (Slides with Channel List on Mobile) --}}
    <div 
        :class="{
            'translate-x-0': isChannelListOpen || window.innerWidth >= 1024,
            '-translate-x-full': !isChannelListOpen && window.innerWidth < 1024
        }" 
        class="fixed lg:sticky {{-- Fixed mobile, sticky desktop --}}
               inset-y-0 left-0 {{-- Position from edge --}}
               w-16 z-40 {{-- High z-index --}}
               transform lg:translate-x-0 {{-- Start hidden mobile, visible desktop --}}
               transition-transform duration-300 ease-in-out 
               flex-shrink-0 bg-gray-200 dark:bg-gray-800/50 p-2 space-y-2 overflow-y-auto fi-sidebar-nav scrollable"
    >
        {{-- Pass current team ID if available, needed for active state --}}
        @php $currentTeamId = Auth::user()?->currentTeam?->id; @endphp
        <x-chat.server-list :currentTeamId="$currentTeamId" />
    </div>

    {{-- Channel List (Slides next to Server List on Mobile) --}}
    {{-- Mobile Overlay Background (Covers main content area) --}}
    <div 
        x-show="isChannelListOpen && window.innerWidth < 1024"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        @click="isChannelListOpen = false" 
        class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        x-cloak
    ></div>
    {{-- Sidebar Content - Position left-16 on mobile when open --}}
    <div 
        :class="{
            'translate-x-0': isChannelListOpen,
            '-translate-x-full': !isChannelListOpen && window.innerWidth < 1024
        }" 
        class="fixed lg:static {{-- Fixed mobile, static desktop --}}
               inset-y-0 left-0 lg:left-auto {{-- Start at left edge --}}
               w-60 z-40 lg:z-auto {{-- High z-index mobile --}}
               transform lg:translate-x-0 {{-- Default hidden mobile, visible desktop --}}
               {{-- Apply left margin matching server list width ONLY when open on mobile --}}
               {{-- This seems complex with transform, let's rethink: Position left-0 and rely on transform --}}
               transition-transform duration-300 ease-in-out 
               flex-shrink-0 bg-gray-100 dark:bg-gray-800/75 
               flex flex-col border-r border-gray-200 dark:border-gray-700 
               fi-sidebar scrollable
               {{-- Adjust left position when open --}}
               lg:ml-0"
        {{-- Use style binding for dynamic left offset on mobile --}}
        :style="(isChannelListOpen && window.innerWidth < 1024) ? 'left: 4rem;' : ''" 
    >
        <x-chat.channel-list :currentTeamId="$currentTeamId" @close-panel="isChannelListOpen = false" />
    </div>

    {{-- Main Chat Area --}}
    <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-gray-900">
        {{-- Pass alpine state control to header --}}
        <x-chat.chat-header 
            @toggle-channels="isChannelListOpen = !isChannelListOpen" 
            @toggle-users="isUserListOpen = !isUserListOpen"
            ::user-list-open="isUserListOpen" 
            ::channel-list-open="isChannelListOpen" {{-- Pass channel state too --}}
        />
        <x-chat.message-area />
        <x-chat.message-input />
    </div>

    {{-- User List (Mobile Overlay / Desktop Static, Toggleable) --}}
    {{-- Mobile Overlay Background --}}
     <div 
        x-show="isUserListOpen && window.innerWidth < 1024" {{-- Only show overlay on mobile --}}
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        @click="isUserListOpen = false" 
        class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        x-cloak
    ></div>
    {{-- Sidebar Content --}}
    <div 
        :class="{
            'translate-x-0': isUserListOpen,
            'translate-x-full': !isUserListOpen && window.innerWidth < 1024,
            'hidden': !isUserListOpen && window.innerWidth >= 1024 {{-- Hide completely on desktop when closed --}}
        }" 
        class="fixed lg:static {{-- Fixed mobile, static desktop --}}
               inset-y-0 right-0 {{-- Position right --}}
               w-60 z-40 lg:z-auto {{-- High z-index mobile --}}
               transform lg:translate-x-0 {{-- Start off-screen mobile, default visible desktop (unless hidden by :class) --}}
               transition-transform duration-300 ease-in-out 
               flex-shrink-0 bg-gray-100 dark:bg-gray-800/75 
               flex flex-col border-l border-gray-200 dark:border-gray-700 
               fi-sidebar scrollable"
    >
        <x-chat.user-list @close-panel="isUserListOpen = false" />
    </div>

</div>
