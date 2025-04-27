<x-filament-panels::page>
    {{-- Alpine component to manage mobile view state --}}
    <div 
        class="flex h-[calc(100vh-10rem)] md:h-[calc(100vh-8rem)]" {{-- Adjust height as needed, potentially different for mobile/desktop --}}
        x-data="{
            view: 'sidebar', // Can be 'sidebar' or 'chat'
            isMobile: window.innerWidth < 768, // md breakpoint
            init() {
                // Ensure sidebar is shown initially on mobile if no channel selected, 
                // or if a channel IS selected but we load on mobile.
                // ChatWindow should maybe dispatch an event if it loads a channel?
                // For simplicity, start with sidebar on mobile.
                if(this.isMobile) {
                    this.view = 'sidebar'; 
                }
                
                window.addEventListener('resize', () => {
                    this.isMobile = window.innerWidth < 768;
                    if (!this.isMobile) {
                        this.view = 'both'; // On desktop, view doesn't matter, both shown
                    }
                });
                
                // Listen for event from sidebar/window to switch view on mobile
                Livewire.on('channelSelected', (channelId) => {
                    if (this.isMobile && channelId) {
                        this.view = 'chat';
                    }
                });
            }
        }"
    >
        {{-- Sidebar --}}
        <div 
            class="border-r border-gray-200 dark:border-gray-700 overflow-y-auto shrink-0"
            :class="{
                'w-full': isMobile && view === 'sidebar',
                'w-0 hidden': isMobile && view === 'chat',
                'w-80 xl:w-96': !isMobile, // Fixed width sidebar on desktop (adjust as needed)
                'block': view === 'sidebar' || !isMobile, 
                'hidden': view === 'chat' && isMobile
            }"
             x-show="view === 'sidebar' || !isMobile" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 transform -translate-x-full" 
             x-transition:enter-end="opacity-100 transform translate-x-0" 
             x-transition:leave="transition ease-in duration-300" 
             x-transition:leave-start="opacity-100 transform translate-x-0" 
             x-transition:leave-end="opacity-0 transform -translate-x-full"
        >
             {{-- Pass channelId, let Livewire handle initial load logic --}}
            @livewire('chat.chat-sidebar', ['channelId' => $channelId], key('chat-sidebar'))
        </div>

        {{-- Chat Window --}}
        <div 
            class="flex flex-col grow"
            :class="{
                'w-full': isMobile && view === 'chat',
                'w-0 hidden': isMobile && view === 'sidebar',
                'grow': !isMobile,
                'flex': view === 'chat' || !isMobile, 
                'hidden': view === 'sidebar' && isMobile
            }"
            x-show="view === 'chat' || !isMobile"
            x-transition:enter="transition ease-out duration-300" 
            x-transition:enter-start="opacity-0 transform translate-x-full" 
            x-transition:enter-end="opacity-100 transform translate-x-0" 
            x-transition:leave="transition ease-in duration-300" 
            x-transition:leave-start="opacity-100 transform translate-x-0" 
            x-transition:leave-end="opacity-0 transform translate-x-full"
        >
            {{-- Pass channelId, ChatWindow handles loading messages --}}
            {{-- Use key() to ensure it re-renders if needed, though properties handle most cases --}}
           @livewire('chat.chat-window', ['channelId' => $channelId], key('chat-window-'.($channelId ?? 'none')))
        </div>
    </div>
</x-filament-panels::page>